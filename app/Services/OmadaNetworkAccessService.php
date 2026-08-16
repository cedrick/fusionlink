<?php

/**
 * Block/unblock customer internet on TP-Link Omada (ER605) by static LAN IP.
 *
 * Supports:
 * - OpenAPI OAuth (client_id + client_secret + omadac_id)
 * - Local controller login (username + password) as fallback
 *
 * Suspend creates (or reuses) an IP group + gateway ACL deny LAN→WAN rule.
 * Restore deletes that ACL (and IP group when unused).
 */
class OmadaNetworkAccessService
{
    public static function ensureSchema(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'customers')) {
            if (!self::columnExists($pdo, 'customers', 'static_ip')) {
                $pdo->exec("ALTER TABLE customers ADD COLUMN static_ip VARCHAR(45) NULL AFTER phone");
            }
            if (!self::columnExists($pdo, 'customers', 'network_blocked')) {
                $pdo->exec("ALTER TABLE customers ADD COLUMN network_blocked TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
            }
            if (!self::columnExists($pdo, 'customers', 'omada_ip_group_id')) {
                $pdo->exec("ALTER TABLE customers ADD COLUMN omada_ip_group_id VARCHAR(64) NULL AFTER network_blocked");
            }
            if (!self::columnExists($pdo, 'customers', 'omada_acl_id')) {
                $pdo->exec("ALTER TABLE customers ADD COLUMN omada_acl_id VARCHAR(64) NULL AFTER omada_ip_group_id");
            }
        }

        if (self::tableExists($pdo, 'settings')) {
            $cols = [
                'omada_enabled' => "TINYINT(1) NOT NULL DEFAULT 0",
                'omada_base_url' => "VARCHAR(255) NULL",
                'omada_omadac_id' => "VARCHAR(64) NULL",
                'omada_site_id' => "VARCHAR(64) NULL",
                'omada_client_id' => "VARCHAR(190) NULL",
                'omada_client_secret' => "VARCHAR(255) NULL",
                'omada_username' => "VARCHAR(190) NULL",
                'omada_password' => "VARCHAR(255) NULL",
                'omada_allow_insecure' => "TINYINT(1) NOT NULL DEFAULT 1",
            ];
            foreach ($cols as $name => $def) {
                if (!self::columnExists($pdo, 'settings', $name)) {
                    $pdo->exec("ALTER TABLE settings ADD COLUMN {$name} {$def}");
                }
            }
        }

        if (!self::tableExists($pdo, 'network_access_logs')) {
            $pdo->exec("
                CREATE TABLE network_access_logs (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    customer_id INT UNSIGNED NULL,
                    action VARCHAR(32) NOT NULL,
                    static_ip VARCHAR(45) NULL,
                    success TINYINT(1) NOT NULL DEFAULT 0,
                    message VARCHAR(500) NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_network_logs_customer (customer_id),
                    KEY idx_network_logs_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
    }

    public static function getSettings(PDO $pdo): array
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->query("
            SELECT
                omada_enabled, omada_base_url, omada_omadac_id, omada_site_id,
                omada_client_id, omada_client_secret, omada_username, omada_password,
                omada_allow_insecure
            FROM settings
            ORDER BY id ASC
            LIMIT 1
        ");
        $row = $stmt ? $stmt->fetch() : false;
        if (!$row) {
            return [
                'omada_enabled' => 0,
                'omada_base_url' => '',
                'omada_omadac_id' => '',
                'omada_site_id' => '',
                'omada_client_id' => '',
                'omada_client_secret' => '',
                'omada_username' => '',
                'omada_password' => '',
                'omada_allow_insecure' => 1,
            ];
        }

        return $row;
    }

    public static function isEnabled(PDO $pdo): bool
    {
        $s = self::getSettings($pdo);
        return !empty($s['omada_enabled']) && trim((string)($s['omada_base_url'] ?? '')) !== '';
    }

    /**
     * @return array{ok:bool,message:string,sites?:array}
     */
    public static function testConnection(PDO $pdo): array
    {
        self::ensureSchema($pdo);
        try {
            $ctx = self::authenticate($pdo);
            $sites = self::listSites($ctx);
            return [
                'ok' => true,
                'message' => 'Connected to Omada. Found ' . count($sites) . ' site(s).',
                'sites' => $sites,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Suspend customer internet (block static IP LAN→WAN).
     *
     * @return array{ok:bool,message:string,skipped?:bool}
     */
    public static function suspendCustomer(PDO $pdo, int $customerId): array
    {
        self::ensureSchema($pdo);
        $customer = self::loadCustomer($pdo, $customerId);
        if (!$customer) {
            return ['ok' => false, 'message' => 'Customer not found.'];
        }

        $ip = trim((string)($customer['static_ip'] ?? ''));
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            self::log($pdo, $customerId, 'SUSPEND', $ip, false, 'Missing or invalid static_ip');
            return ['ok' => false, 'message' => 'Customer needs a valid static IP before network suspend.'];
        }

        $pdo->prepare("UPDATE customers SET status = 'DISCONNECTED' WHERE id = ?")->execute([$customerId]);

        if (!self::isEnabled($pdo)) {
            $pdo->prepare("UPDATE customers SET network_blocked = 1 WHERE id = ?")->execute([$customerId]);
            self::log($pdo, $customerId, 'SUSPEND', $ip, true, 'Omada disabled — billing status only');
            return ['ok' => true, 'message' => 'Marked DISCONNECTED (Omada control is disabled).', 'skipped' => true];
        }

        try {
            $ctx = self::authenticate($pdo);
            $settings = self::getSettings($pdo);
            $siteId = trim((string)$settings['omada_site_id']);
            if ($siteId === '') {
                throw new RuntimeException('Omada Site ID is not configured in Settings.');
            }

            $groupName = 'FL-Block-' . $customerId;
            $aclName = 'FL-Deny-' . $customerId;

            $groupId = trim((string)($customer['omada_ip_group_id'] ?? ''));
            if ($groupId === '') {
                $groupId = self::ensureIpGroup($ctx, $siteId, $groupName, $ip);
            }

            $aclId = trim((string)($customer['omada_acl_id'] ?? ''));
            if ($aclId === '') {
                $aclId = self::ensureDenyAcl($ctx, $siteId, $aclName, $groupId);
            }

            $pdo->prepare("
                UPDATE customers
                SET network_blocked = 1,
                    omada_ip_group_id = ?,
                    omada_acl_id = ?
                WHERE id = ?
            ")->execute([$groupId, $aclId, $customerId]);

            self::log($pdo, $customerId, 'SUSPEND', $ip, true, 'Blocked via Omada ACL ' . $aclId);
            return ['ok' => true, 'message' => 'Customer suspended and IP blocked on Omada/ER605.'];
        } catch (Throwable $e) {
            self::log($pdo, $customerId, 'SUSPEND', $ip, false, $e->getMessage());
            return ['ok' => false, 'message' => 'Marked DISCONNECTED but Omada block failed: ' . $e->getMessage()];
        }
    }

    /**
     * Restore customer internet (remove block).
     *
     * @return array{ok:bool,message:string,skipped?:bool}
     */
    public static function restoreCustomer(PDO $pdo, int $customerId): array
    {
        self::ensureSchema($pdo);
        $customer = self::loadCustomer($pdo, $customerId);
        if (!$customer) {
            return ['ok' => false, 'message' => 'Customer not found.'];
        }

        $ip = trim((string)($customer['static_ip'] ?? ''));
        $pdo->prepare("UPDATE customers SET status = 'ACTIVE' WHERE id = ?")->execute([$customerId]);

        if (!self::isEnabled($pdo)) {
            $pdo->prepare("
                UPDATE customers
                SET network_blocked = 0, omada_acl_id = NULL
                WHERE id = ?
            ")->execute([$customerId]);
            self::log($pdo, $customerId, 'RESTORE', $ip, true, 'Omada disabled — billing status only');
            return ['ok' => true, 'message' => 'Marked ACTIVE (Omada control is disabled).', 'skipped' => true];
        }

        try {
            $ctx = self::authenticate($pdo);
            $settings = self::getSettings($pdo);
            $siteId = trim((string)$settings['omada_site_id']);
            if ($siteId === '') {
                throw new RuntimeException('Omada Site ID is not configured in Settings.');
            }

            $aclId = trim((string)($customer['omada_acl_id'] ?? ''));
            $groupId = trim((string)($customer['omada_ip_group_id'] ?? ''));

            if ($aclId !== '') {
                self::deleteAcl($ctx, $siteId, $aclId);
            }
            if ($groupId !== '') {
                self::deleteIpGroup($ctx, $siteId, $groupId);
            }

            $pdo->prepare("
                UPDATE customers
                SET network_blocked = 0,
                    omada_acl_id = NULL,
                    omada_ip_group_id = NULL
                WHERE id = ?
            ")->execute([$customerId]);

            self::log($pdo, $customerId, 'RESTORE', $ip, true, 'Unblocked on Omada/ER605');
            return ['ok' => true, 'message' => 'Customer restored and IP unblocked on Omada/ER605.'];
        } catch (Throwable $e) {
            self::log($pdo, $customerId, 'RESTORE', $ip, false, $e->getMessage());
            return ['ok' => false, 'message' => 'Marked ACTIVE but Omada unblock failed: ' . $e->getMessage()];
        }
    }

    /**
     * Auto-suspend every ACTIVE customer who has overdue invoices and a static IP.
     */
    public static function suspendOverdueCustomers(PDO $pdo): array
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->query("
            SELECT DISTINCT c.id
            FROM customers c
            INNER JOIN invoices i ON i.customer_id = c.id
            WHERE c.status <> 'DISCONNECTED'
              AND i.status = 'OVERDUE'
              AND TRIM(IFNULL(c.static_ip, '')) <> ''
        ");
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        $ok = 0;
        $fail = 0;
        foreach ($ids as $id) {
            $result = self::suspendCustomer($pdo, $id);
            if (!empty($result['ok'])) {
                $ok++;
            } else {
                $fail++;
            }
        }

        return ['candidates' => count($ids), 'ok' => $ok, 'fail' => $fail];
    }

    /**
     * Restore customer if they have no remaining unpaid invoices.
     */
    public static function restoreIfFullyPaid(PDO $pdo, int $customerId): array
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM invoices
            WHERE customer_id = ?
              AND status IN ('ISSUED', 'OVERDUE', 'UNPAID')
        ");
        $stmt->execute([$customerId]);
        $open = (int)$stmt->fetchColumn();
        if ($open > 0) {
            return ['ok' => true, 'message' => 'Still has open invoices — left disconnected/blocked.', 'skipped' => true];
        }

        return self::restoreCustomer($pdo, $customerId);
    }

    private static function loadCustomer(PDO $pdo, int $customerId): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? LIMIT 1");
        $stmt->execute([$customerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function log(
        PDO $pdo,
        int $customerId,
        string $action,
        ?string $ip,
        bool $success,
        string $message
    ): void {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO network_access_logs (customer_id, action, static_ip, success, message)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $customerId,
                $action,
                $ip !== '' ? $ip : null,
                $success ? 1 : 0,
                mb_substr($message, 0, 500),
            ]);
        } catch (Throwable $e) {
            error_log('OmadaNetworkAccessService@log: ' . $e->getMessage());
        }
    }

    /**
     * @return array{mode:string,base:string,omadacId:string,token:?string,csrf:?string,cookie:?string,allowInsecure:bool}
     */
    private static function authenticate(PDO $pdo): array
    {
        $s = self::getSettings($pdo);
        $base = rtrim(trim((string)$s['omada_base_url']), '/');
        $omadacId = trim((string)$s['omada_omadac_id']);
        $allowInsecure = !empty($s['omada_allow_insecure']);
        if ($base === '') {
            throw new RuntimeException('Omada base URL is empty.');
        }

        $clientId = trim((string)$s['omada_client_id']);
        $clientSecret = trim((string)$s['omada_client_secret']);
        if ($clientId !== '' && $clientSecret !== '' && $omadacId !== '') {
            $token = self::fetchOpenApiToken($base, $omadacId, $clientId, $clientSecret, $allowInsecure);
            return [
                'mode' => 'openapi',
                'base' => $base,
                'omadacId' => $omadacId,
                'token' => $token,
                'csrf' => null,
                'cookie' => null,
                'allowInsecure' => $allowInsecure,
            ];
        }

        $username = trim((string)$s['omada_username']);
        $password = (string)($s['omada_password'] ?? '');
        if ($username !== '' && $password !== '' && $omadacId !== '') {
            $login = self::loginLocal($base, $omadacId, $username, $password, $allowInsecure);
            return [
                'mode' => 'local',
                'base' => $base,
                'omadacId' => $omadacId,
                'token' => null,
                'csrf' => $login['csrf'],
                'cookie' => $login['cookie'],
                'allowInsecure' => $allowInsecure,
            ];
        }

        throw new RuntimeException(
            'Configure either OpenAPI Client ID/Secret + Controller ID, or local Username/Password + Controller ID.'
        );
    }

    private static function fetchOpenApiToken(
        string $base,
        string $omadacId,
        string $clientId,
        string $clientSecret,
        bool $allowInsecure
    ): string {
        $url = $base . '/openapi/authorize/token?grant_type=client_credentials';
        $body = http_build_query([
            'omadacId' => $omadacId,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);
        $resp = self::httpRequest('POST', $url, [
            'Content-Type: application/x-www-form-urlencoded',
        ], $body, $allowInsecure);

        $json = json_decode($resp['body'], true);
        $token = $json['result']['accessToken']
            ?? $json['result']['access_token']
            ?? $json['accessToken']
            ?? $json['access_token']
            ?? null;
        if (!$token) {
            throw new RuntimeException('Omada OpenAPI token failed: ' . mb_substr($resp['body'], 0, 300));
        }

        return (string)$token;
    }

    /**
     * @return array{csrf:string,cookie:string}
     */
    private static function loginLocal(
        string $base,
        string $omadacId,
        string $username,
        string $password,
        bool $allowInsecure
    ): array {
        $url = $base . '/' . rawurlencode($omadacId) . '/api/v2/login';
        $resp = self::httpRequest('POST', $url, [
            'Content-Type: application/json',
        ], json_encode(['username' => $username, 'password' => $password]), $allowInsecure);

        $json = json_decode($resp['body'], true);
        $errorCode = (int)($json['errorCode'] ?? -1);
        if ($errorCode !== 0) {
            throw new RuntimeException('Omada local login failed: ' . mb_substr($resp['body'], 0, 300));
        }

        $csrf = (string)($json['result']['token'] ?? '');
        $cookie = '';
        foreach ($resp['headers'] as $header) {
            if (stripos($header, 'Set-Cookie:') === 0) {
                if (preg_match('/TPOMADA_SESSIONID=([^;]+)/i', $header, $m)) {
                    $cookie = 'TPOMADA_SESSIONID=' . $m[1];
                    break;
                }
            }
        }
        if ($csrf === '' || $cookie === '') {
            throw new RuntimeException('Omada local login missing CSRF token or session cookie.');
        }

        return ['csrf' => $csrf, 'cookie' => $cookie];
    }

    private static function listSites(array $ctx): array
    {
        if ($ctx['mode'] === 'openapi') {
            $url = $ctx['base'] . '/openapi/v1/' . rawurlencode($ctx['omadacId']) . '/sites?pageSize=100&page=1';
            $resp = self::apiCall($ctx, 'GET', $url);
            $json = json_decode($resp, true);
            $list = $json['result']['data'] ?? $json['result'] ?? [];
            return is_array($list) ? $list : [];
        }

        $url = $ctx['base'] . '/' . rawurlencode($ctx['omadacId']) . '/api/v2/sites?currentPage=1&currentPageSize=100';
        $resp = self::apiCall($ctx, 'GET', $url);
        $json = json_decode($resp, true);
        $list = $json['result']['data'] ?? [];
        return is_array($list) ? $list : [];
    }

    private static function ensureIpGroup(array $ctx, string $siteId, string $name, string $ip): string
    {
        $existing = self::findIpGroupByName($ctx, $siteId, $name);
        if ($existing) {
            return (string)$existing;
        }

        $payload = [
            'name' => $name,
            'type' => 0,
            'count' => 1,
            'ipList' => [
                ['ip' => $ip, 'mask' => 32, 'type' => 0],
            ],
        ];

        if ($ctx['mode'] === 'openapi') {
            $url = $ctx['base'] . '/openapi/v1/' . rawurlencode($ctx['omadacId'])
                . '/sites/' . rawurlencode($siteId) . '/firewall/groups';
        } else {
            $url = $ctx['base'] . '/' . rawurlencode($ctx['omadacId'])
                . '/api/v2/sites/' . rawurlencode($siteId) . '/setting/firewall/groups';
        }

        $resp = self::apiCall($ctx, 'POST', $url, $payload);
        $json = json_decode($resp, true);
        $id = $json['result']['id'] ?? $json['result'] ?? null;
        if (is_array($id)) {
            $id = $id['id'] ?? null;
        }
        if (!$id) {
            // Some controllers return only errorCode 0 with id elsewhere
            $found = self::findIpGroupByName($ctx, $siteId, $name);
            if ($found) {
                return $found;
            }
            throw new RuntimeException('Failed to create Omada IP group: ' . mb_substr($resp, 0, 400));
        }

        return (string)$id;
    }

    private static function findIpGroupByName(array $ctx, string $siteId, string $name): ?string
    {
        if ($ctx['mode'] === 'openapi') {
            $url = $ctx['base'] . '/openapi/v1/' . rawurlencode($ctx['omadacId'])
                . '/sites/' . rawurlencode($siteId) . '/firewall/groups?pageSize=100&page=1';
        } else {
            $url = $ctx['base'] . '/' . rawurlencode($ctx['omadacId'])
                . '/api/v2/sites/' . rawurlencode($siteId) . '/setting/firewall/groups?currentPage=1&currentPageSize=100';
        }

        try {
            $resp = self::apiCall($ctx, 'GET', $url);
            $json = json_decode($resp, true);
            $rows = $json['result']['data'] ?? $json['result'] ?? [];
            if (!is_array($rows)) {
                return null;
            }
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                if (($row['name'] ?? '') === $name && !empty($row['id'])) {
                    return (string)$row['id'];
                }
            }
        } catch (Throwable $e) {
            // ignore list failures; create will surface errors
        }

        return null;
    }

    private static function ensureDenyAcl(array $ctx, string $siteId, string $name, string $ipGroupId): string
    {
        $existing = self::findAclByName($ctx, $siteId, $name);
        if ($existing) {
            return $existing;
        }

        $payload = [
            'name' => $name,
            'status' => true,
            'policy' => 0, // deny
            'sourceType' => 2, // IP group
            'sourceIds' => [$ipGroupId],
            'destinationType' => 0,
            'destinationIds' => [],
            'protocols' => ['all'],
            'type' => 0, // gateway ACL
            'biDirectional' => false,
            'direction' => [
                'lanToWan' => true,
                'lanToLan' => false,
                'wanInIds' => [],
                'vpnInIds' => [],
            ],
            'syslog' => true,
        ];

        if ($ctx['mode'] === 'openapi') {
            $url = $ctx['base'] . '/openapi/v1/' . rawurlencode($ctx['omadacId'])
                . '/sites/' . rawurlencode($siteId) . '/firewall/acls';
        } else {
            $url = $ctx['base'] . '/' . rawurlencode($ctx['omadacId'])
                . '/api/v2/sites/' . rawurlencode($siteId) . '/setting/firewall/acls';
        }

        $resp = self::apiCall($ctx, 'POST', $url, $payload);
        $json = json_decode($resp, true);
        $id = $json['result']['id'] ?? $json['result'] ?? null;
        if (is_array($id)) {
            $id = $id['id'] ?? null;
        }
        if (!$id) {
            $found = self::findAclByName($ctx, $siteId, $name);
            if ($found) {
                return $found;
            }
            throw new RuntimeException('Failed to create Omada deny ACL: ' . mb_substr($resp, 0, 400));
        }

        return (string)$id;
    }

    private static function findAclByName(array $ctx, string $siteId, string $name): ?string
    {
        if ($ctx['mode'] === 'openapi') {
            $url = $ctx['base'] . '/openapi/v1/' . rawurlencode($ctx['omadacId'])
                . '/sites/' . rawurlencode($siteId) . '/firewall/acls?pageSize=100&page=1';
        } else {
            $url = $ctx['base'] . '/' . rawurlencode($ctx['omadacId'])
                . '/api/v2/sites/' . rawurlencode($siteId) . '/setting/firewall/acls?currentPage=1&currentPageSize=100';
        }

        try {
            $resp = self::apiCall($ctx, 'GET', $url);
            $json = json_decode($resp, true);
            $rows = $json['result']['data'] ?? $json['result'] ?? [];
            if (!is_array($rows)) {
                return null;
            }
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                if (($row['name'] ?? '') === $name && !empty($row['id'])) {
                    return (string)$row['id'];
                }
            }
        } catch (Throwable $e) {
            // ignore
        }

        return null;
    }

    private static function deleteAcl(array $ctx, string $siteId, string $aclId): void
    {
        if ($ctx['mode'] === 'openapi') {
            $url = $ctx['base'] . '/openapi/v1/' . rawurlencode($ctx['omadacId'])
                . '/sites/' . rawurlencode($siteId) . '/firewall/acls/' . rawurlencode($aclId);
        } else {
            $url = $ctx['base'] . '/' . rawurlencode($ctx['omadacId'])
                . '/api/v2/sites/' . rawurlencode($siteId) . '/setting/firewall/acls/' . rawurlencode($aclId);
        }
        self::apiCall($ctx, 'DELETE', $url);
    }

    private static function deleteIpGroup(array $ctx, string $siteId, string $groupId): void
    {
        if ($ctx['mode'] === 'openapi') {
            $url = $ctx['base'] . '/openapi/v1/' . rawurlencode($ctx['omadacId'])
                . '/sites/' . rawurlencode($siteId) . '/firewall/groups/' . rawurlencode($groupId);
        } else {
            $url = $ctx['base'] . '/' . rawurlencode($ctx['omadacId'])
                . '/api/v2/sites/' . rawurlencode($siteId) . '/setting/firewall/groups/' . rawurlencode($groupId);
        }
        try {
            self::apiCall($ctx, 'DELETE', $url);
        } catch (Throwable $e) {
            // Group may still be referenced; ACL delete is the critical path.
            error_log('Omada delete IP group: ' . $e->getMessage());
        }
    }

    private static function apiCall(array $ctx, string $method, string $url, ?array $payload = null): string
    {
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($ctx['mode'] === 'openapi') {
            $headers[] = 'Authorization: Bearer ' . $ctx['token'];
        } else {
            $headers[] = 'Csrf-Token: ' . $ctx['csrf'];
            $headers[] = 'Cookie: ' . $ctx['cookie'];
        }

        $body = $payload !== null ? json_encode($payload) : null;
        $resp = self::httpRequest($method, $url, $headers, $body, (bool)$ctx['allowInsecure']);
        $json = json_decode($resp['body'], true);
        if (is_array($json) && array_key_exists('errorCode', $json) && (int)$json['errorCode'] !== 0) {
            throw new RuntimeException('Omada API error: ' . mb_substr($resp['body'], 0, 400));
        }

        return $resp['body'];
    }

    /**
     * @return array{body:string,headers:array<int,string>,status:int}
     */
    private static function httpRequest(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        bool $allowInsecure
    ): array {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is required for Omada integration.');
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        if ($allowInsecure) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Omada HTTP error: ' . $err);
        }

        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headerText = substr($raw, 0, $headerSize);
        $responseBody = substr($raw, $headerSize);
        $headerLines = preg_split("/\r\n|\n|\r/", $headerText) ?: [];

        if ($status >= 400) {
            throw new RuntimeException('Omada HTTP ' . $status . ': ' . mb_substr($responseBody, 0, 300));
        }

        return [
            'body' => $responseBody,
            'headers' => $headerLines,
            'status' => $status,
        ];
    }

    private static function tableExists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetchColumn();
    }

    private static function columnExists(PDO $pdo, string $tableName, string $columnName): bool
    {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE ?");
        $stmt->execute([$columnName]);
        return (bool)$stmt->fetch();
    }
}

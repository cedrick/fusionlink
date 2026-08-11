<?php

class RememberMeService
{
    public const COOKIE_NAME = 'fl_remember';
    public const TTL_SECONDS = 2592000; // 30 days
    public const MAX_TOKENS_PER_USER = 5;

    public static function ensureSchema(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS remember_tokens (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                user_agent VARCHAR(255) NULL,
                last_used_at DATETIME NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_token_hash (token_hash),
                KEY idx_user_id (user_id),
                KEY idx_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public static function issue(PDO $pdo, int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        self::ensureSchema($pdo);
        self::pruneExpired($pdo);
        self::enforceTokenLimit($pdo, $userId);

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + self::TTL_SECONDS);

        $stmt = $pdo->prepare('
            INSERT INTO remember_tokens (user_id, token_hash, expires_at, user_agent, last_used_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        $stmt->execute([
            $userId,
            $tokenHash,
            $expiresAt,
            self::truncateUserAgent(),
        ]);

        self::setCookie($token, self::TTL_SECONDS);
        self::extendSessionCookie(self::TTL_SECONDS);
    }

    public static function attemptRestore(PDO $pdo): bool
    {
        if (!empty($_SESSION['user'])) {
            return false;
        }

        $token = trim((string)($_COOKIE[self::COOKIE_NAME] ?? ''));
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
            return false;
        }

        self::ensureSchema($pdo);

        $tokenHash = hash('sha256', $token);
        $stmt = $pdo->prepare('
            SELECT id, user_id, expires_at
            FROM remember_tokens
            WHERE token_hash = ?
            LIMIT 1
        ');
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            self::clearCookie();
            return false;
        }

        $expiresAt = strtotime((string)($row['expires_at'] ?? ''));
        if ($expiresAt === false || $expiresAt < time()) {
            self::deleteTokenById($pdo, (int)($row['id'] ?? 0));
            self::clearCookie();
            return false;
        }

        $userStmt = $pdo->prepare('
            SELECT id, customer_id, email, role
            FROM users
            WHERE id = ?
            LIMIT 1
        ');
        $userStmt->execute([(int)($row['user_id'] ?? 0)]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            self::deleteTokenById($pdo, (int)($row['id'] ?? 0));
            self::clearCookie();
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'customer_id' => $user['customer_id'] ?? null,
            'email' => (string)$user['email'],
            'role' => (string)$user['role'],
        ];

        $update = $pdo->prepare('UPDATE remember_tokens SET last_used_at = NOW() WHERE id = ?');
        $update->execute([(int)($row['id'] ?? 0)]);

        self::extendSessionCookie(self::TTL_SECONDS);

        return true;
    }

    public static function revokeCurrent(PDO $pdo): void
    {
        $token = trim((string)($_COOKIE[self::COOKIE_NAME] ?? ''));
        if ($token !== '' && preg_match('/^[a-f0-9]{64}$/i', $token)) {
            self::ensureSchema($pdo);
            $tokenHash = hash('sha256', $token);
            $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE token_hash = ?');
            $stmt->execute([$tokenHash]);
        }

        if (!empty($_SESSION['user']['id'])) {
            self::ensureSchema($pdo);
            $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
            $stmt->execute([(int)$_SESSION['user']['id']]);
        }

        self::clearCookie();
    }

    private static function enforceTokenLimit(PDO $pdo, int $userId): void
    {
        $stmt = $pdo->prepare('
            SELECT id
            FROM remember_tokens
            WHERE user_id = ?
            ORDER BY COALESCE(last_used_at, created_at) ASC, id ASC
        ');
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $overflow = count($rows) - self::MAX_TOKENS_PER_USER + 1;
        if ($overflow < 1) {
            return;
        }

        for ($i = 0; $i < $overflow; $i++) {
            self::deleteTokenById($pdo, (int)($rows[$i]['id'] ?? 0));
        }
    }

    private static function pruneExpired(PDO $pdo): void
    {
        $pdo->exec("DELETE FROM remember_tokens WHERE expires_at < NOW()");
    }

    private static function deleteTokenById(PDO $pdo, int $tokenId): void
    {
        if ($tokenId <= 0) {
            return;
        }

        $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE id = ?');
        $stmt->execute([$tokenId]);
    }

    private static function cookiePath(): string
    {
        $base = function_exists('base_path') ? base_path() : '';

        return $base === '' ? '/' : $base;
    }

    private static function setCookie(string $token, int $lifetimeSeconds): void
    {
        setcookie(self::COOKIE_NAME, $token, [
            'expires' => time() + $lifetimeSeconds,
            'path' => self::cookiePath(),
            'domain' => '',
            'secure' => function_exists('is_https') ? is_https() : false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $_COOKIE[self::COOKIE_NAME] = $token;
    }

    private static function clearCookie(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => self::cookiePath(),
            'domain' => '',
            'secure' => function_exists('is_https') ? is_https() : false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        unset($_COOKIE[self::COOKIE_NAME]);
    }

    private static function extendSessionCookie(int $lifetimeSeconds): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires' => time() + $lifetimeSeconds,
            'path' => $params['path'] ?? self::cookiePath(),
            'domain' => $params['domain'] ?? '',
            'secure' => $params['secure'] ?? (function_exists('is_https') ? is_https() : false),
            'httponly' => true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    private static function truncateUserAgent(): ?string
    {
        $agent = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));

        if ($agent === '') {
            return null;
        }

        return mb_substr($agent, 0, 255);
    }
}

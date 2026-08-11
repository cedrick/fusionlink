<?php

function app_timezone(): string
{
    static $timezone = null;

    if ($timezone !== null) {
        return $timezone;
    }

    $configFile = __DIR__ . '/../config/app.php';
    $configured = 'Asia/Manila';
    if (file_exists($configFile)) {
        $config = require $configFile;
        $configured = trim((string)($config['timezone'] ?? 'Asia/Manila'));
        if ($configured === '') {
            $configured = 'Asia/Manila';
        }
    }

    $timezone = $configured;
    return $timezone;
}

function app_now(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone(app_timezone()));
}

function base_path(): string
{
    static $path = null;

    if ($path !== null) {
        return $path;
    }

    $configFile = __DIR__ . '/../config/app.php';
    if (file_exists($configFile)) {
        $config = require $configFile;
        $configured = trim((string)($config['base_path'] ?? ''));
        if ($configured !== '') {
            $path = '/' . trim($configured, '/');
            return $path;
        }
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $script = preg_replace('#/(manifest\.php|service-worker\.php)$#', '', $script);
    $detected = rtrim(str_replace('/index.php', '', $script), '/');

    $path = ($detected === '/' || $detected === '') ? '' : $detected;
    return $path;
}

function url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    $base = base_path();

    if ($path === '/') {
        return $base === '' ? '/' : $base . '/';
    }

    return $base . $path;
}

function absolute_url(string $path = ''): string
{
    $scheme = is_https() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . url($path);
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();

    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base)) ?: '/';
    }

    return $path === '' ? '/' : $path;
}

function configure_session_cookie(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $base = base_path();
    $params = session_get_cookie_params();
    $lifetime = 86400 * 14; // 14 days — session persists in browser unless logout

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => $base === '' ? '/' : $base,
        'domain' => $params['domain'],
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.gc_maxlifetime', (string)$lifetime);
}

function remember_me_requested(): bool
{
    return isset($_POST['remember_me']) && (string)$_POST['remember_me'] === '1';
}

function attempt_remember_me_login(): void
{
    if (!empty($_SESSION['user'])) {
        return;
    }

    if (!class_exists('Database')) {
        return;
    }

    $serviceFile = __DIR__ . '/../app/Services/RememberMeService.php';
    if (!file_exists($serviceFile)) {
        return;
    }

    require_once $serviceFile;

    try {
        RememberMeService::attemptRestore(Database::connect());
    } catch (Throwable $e) {
        error_log('attempt_remember_me_login error: ' . $e->getMessage());
    }
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    $forwarded = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($forwarded === 'https') {
        return true;
    }

    $forwardedSsl = strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));
    return $forwardedSsl === 'on';
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('X-XSS-Protection: 1; mode=block');

    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string)$_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="_csrf" value="' . $token . '">';
}

function verify_csrf(bool $exitOnFailure = true): bool
{
    $token = (string)($_POST['_csrf'] ?? '');
    $sessionToken = (string)($_SESSION['_csrf_token'] ?? '');

    if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        if ($exitOnFailure) {
            http_response_code(419);
            echo 'Your session expired. Please refresh the page and try again.';
            exit;
        }

        return false;
    }

    return true;
}

function rate_limit(string $key, int $maxAttempts = 10, int $windowSeconds = 300): bool
{
    $now = time();
    $bucketKey = '_rate_' . $key;

    if (!isset($_SESSION[$bucketKey]) || !is_array($_SESSION[$bucketKey])) {
        $_SESSION[$bucketKey] = ['count' => 0, 'reset' => $now + $windowSeconds];
    }

    $bucket = &$_SESSION[$bucketKey];

    if ($now >= (int)($bucket['reset'] ?? 0)) {
        $bucket = ['count' => 0, 'reset' => $now + $windowSeconds];
    }

    if ((int)$bucket['count'] >= $maxAttempts) {
        return false;
    }

    $bucket['count'] = (int)$bucket['count'] + 1;
    return true;
}

function is_local_request(): bool
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));

    return $host === 'localhost'
        || str_starts_with($host, 'localhost:')
        || $host === '127.0.0.1'
        || str_starts_with($host, '127.0.0.1:');
}

function page_link(string $link): string
{
    $link = trim($link);

    if ($link === '' || $link === '/') {
        return url('/page');
    }

    if (preg_match('#^https?://#i', $link)) {
        return $link;
    }

    if ($link === '/apply' || $link === '/page/apply') {
        return url('/page') . '#apply';
    }

    if (str_starts_with($link, '#')) {
        return url('/page') . $link;
    }

    if (str_starts_with($link, '/page')) {
        return url($link);
    }

    if (str_starts_with($link, '/')) {
        return url($link);
    }

    return url('/page') . '#' . ltrim($link, '#');
}

function asset_url(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return url($path);
}

function otp_login_required(array $user): bool
{
    if (is_local_request()) {
        return false;
    }

    if (strtolower((string)($user['email'] ?? '')) === 'admin@isp.com') {
        return false;
    }

    $role = (string)($user['role'] ?? '');
    if (in_array($role, ['ROLE_ADMIN', 'ROLE_STAFF', 'ADMIN', 'STAFF', 'admin', 'staff'], true)) {
        return false;
    }

    return true;
}

function require_login()
{
    if (!isset($_SESSION['user'])) {
        redirect('/login');
    }
}

function require_role($roles)
{
    require_login();

    $role = $_SESSION['user']['role'] ?? '';

    if (!in_array($role, $roles)) {
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
}

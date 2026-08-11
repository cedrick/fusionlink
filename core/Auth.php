<?php

class Auth
{
    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/login');
        }
    }

    /**
     * Require one of the allowed roles
     * Example: Auth::requireRole(['ROLE_ADMIN', 'ROLE_STAFF']);
     */
    public static function requireRole(array $roles): void
    {
        self::requireLogin();

        $user = self::user();
        $role = $user['role'] ?? null;

        if (!$role || !in_array($role, $roles, true)) {
            http_response_code(403);
            echo "<h2>403 Forbidden</h2><p>You do not have permission to access this page.</p>";
            exit;
        }
    }
}

<?php

class Database
{
    public static function pdo(): PDO
    {
        $config = require __DIR__ . '/../config/database.php';

        $host = $config['host'] ?? '127.0.0.1';
        $dbname = $config['db'] ?? ($config['name'] ?? null); // accept both keys
        $user = $config['user'] ?? null;
        $pass = $config['pass'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        if (!$dbname) {
            throw new Exception("Database name missing in config/database.php (use 'db' or 'name').");
        }

        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    // Backward compatibility (if your controllers call Database::connect())
    public static function connect(): PDO
    {
        return self::pdo();
    }
}

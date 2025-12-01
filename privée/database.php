<?php

namespace Privee;

class Database {
    private static ?\PDO $pdo = null;
    
    public static function getPdo(): \PDO {
        if (self::$pdo === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $dbname = getenv('DB_NAME') ?: 'guardia_events';
            $username = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASS') ?: '';

            try {
                self::$pdo = new \PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $username,
                    $password,
                    [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                        \PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (\PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                throw new \RuntimeException("Service temporarily unavailable");
            }
        }
        return self::$pdo;
    }
}

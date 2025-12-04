<?php
namespace Privee;

class Database {
    public static function getPdo() {
        // Configuration pour Docker
        $host = getenv('DB_HOST') ?: 'db';  // 'db' est le nom du service dans docker-compose
        $dbname = getenv('DB_NAME') ?: 'guardia_app';
        $username = 'root';
        $password = getenv('MYSQL_ROOT_PASSWORD') ?: 'rootpassword';

        try {
            $pdo = new \PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch(\PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
}

<?php
namespace Privee;

class Database {
    public static function getPdo() {
        $host = getenv('DB_HOST');
        $dbname = getenv('DB_NAME');
        $username = getenv('DB_USER');
        $password = getenv('DB_PASS');

        try {
            $pdo = new \PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch(\PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
}

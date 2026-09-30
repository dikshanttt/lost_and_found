<?php
/**
 * Database Connection (database/db.php)
 */
require_once __DIR__ . '/../config/config.php';

function get_db(): ?PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = env_value('DB_HOST', 'localhost');
        $db   = env_value('DB_NAME', 'lost_found_hub');
        $user = env_value('DB_USER', 'root');
        $pass = env_value('DB_PASSWORD', '');
        $dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw $e;
        }
    }
    return $pdo;
}

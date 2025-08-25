<?php
// Simple PDO Database wrapper used by API endpoints
// Compatible with older PHP versions (no typed properties/return types)
class Database {
    private $host = 'localhost';
    private $db_name = 'my_little_thingz';
    private $username = 'root';
    private $password = '';

    private $conn = null; // PDO instance

    public function getConnection() {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        );

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Re-throw as a generic Exception so callers can handle uniformly
            throw new Exception('Connection error: ' . $e->getMessage(), 0, $e);
        }

        return $this->conn;
    }
}
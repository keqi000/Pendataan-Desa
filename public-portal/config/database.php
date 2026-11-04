<?php
// Database Configuration untuk Sistem Informasi Publik
// File: public-portal/config/database.php

class PublicDatabase {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $connection;
    
    public function __construct() {
        // Konfigurasi database terpisah untuk sistem publik
        $this->host = 'localhost';
        $this->dbname = 'pendataan_desa_public';
        $this->username = 'root';
        $this->password = '';
    }
    
    public function getConnection() {
        if ($this->connection === null) {
            try {
                $this->connection = new PDO(
                    "mysql:host={$this->host};dbname={$this->dbname};charset=utf8",
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }
        return $this->connection;
    }
    
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
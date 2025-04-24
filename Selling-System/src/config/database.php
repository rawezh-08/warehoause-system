<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u924953439_Rawezh_Jaza08');
define('DB_PASS', 'Rawezh.Jaza@0894');
define('DB_NAME', 'u924953439_warehouse_db');


class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbname, $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES utf8");
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
            die();
        }
    }
    
    /**
     * Get the PDO database connection
     * 
     * @return PDO The database connection
     */
    public function getConnection(): PDO {
        return $this->conn;
    }
}

// Create a global connection for backward compatibility
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die();
}


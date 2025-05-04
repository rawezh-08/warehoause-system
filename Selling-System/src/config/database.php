<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'warehouse_user');
define('DB_PASS', 'Rawezh.Jaza@0894');
define('DB_NAME', 'warehouse_db');

// Create mysqli connection - this is what most files are using
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Set charset to utf8
$db->set_charset("utf8");

// PDO class for those files that might be using it
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

// Create a PDO connection for backward compatibility
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    // Instead of outputting directly, we'll throw an exception
    throw new Exception("Database connection failed: " . $e->getMessage());
}


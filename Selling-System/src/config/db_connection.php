<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u924953439_Rawezh_Jaza08');
define('DB_PASS', 'Rawezh.Jaza@0894');
define('DB_NAME', 'u924953439_warehouse_db');

// Function to get database connection
function getDbConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("SET NAMES utf8");
        return $conn;
    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        die();
    }
}
?> 
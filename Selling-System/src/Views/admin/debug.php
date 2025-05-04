<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Include database connection
require_once '../../config/database.php';

// Output session info
echo "<h2>Session Information:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check database connection
echo "<h2>Database Connection:</h2>";
if ($db) {
    echo "Database connection successful";
    
    // Test query to check if user_roles table exists
    try {
        $test_query = "SELECT COUNT(*) as count FROM user_roles";
        $result = $db->query($test_query);
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p>Number of roles in database: " . $row['count'] . "</p>";
        } else {
            echo "<p>Error querying user_roles table: " . $db->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p>Exception: " . $e->getMessage() . "</p>";
    }
} else {
    echo "Database connection failed";
}

// Check if necessary tables exist
echo "<h2>Table Structure Check:</h2>";

$tables = array('user_roles', 'permissions', 'role_permissions', 'user_accounts');

foreach ($tables as $table) {
    try {
        $table_check = "SHOW TABLES LIKE '$table'";
        $result = $db->query($table_check);
        
        if ($result && $result->num_rows > 0) {
            echo "<p>Table '$table' exists</p>";
            
            // Show table structure
            $structure_query = "DESCRIBE $table";
            $structure_result = $db->query($structure_query);
            
            if ($structure_result) {
                echo "<details><summary>Table structure</summary><pre>";
                while ($row = $structure_result->fetch_assoc()) {
                    print_r($row);
                }
                echo "</pre></details>";
            }
        } else {
            echo "<p style='color: red;'>Table '$table' does not exist!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception checking table '$table': " . $e->getMessage() . "</p>";
    }
}
?> 
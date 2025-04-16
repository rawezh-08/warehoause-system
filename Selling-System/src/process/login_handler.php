<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: ../Selling-System/src/views/admin/dashboard.php");
    exit();
}

// Database connection
$db = new mysqli('localhost', 'root', '', 'warehouse_db');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "تکایە هەموو خانەکان پڕ بکەرەوە";
    } else {
        // Prepare statement to prevent SQL injection
        $stmt = $db->prepare("SELECT id, username, password_hash FROM admin_accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: /warehouse-system/Selling-System/src/views/admin/dashboard.php");
                exit();
            } else {
                $error = "ناوی بەکارهێنەر یان وشەی نهێنی هەڵەیە";
            }
        } else {
            $error = "ناوی بەکارهێنەر یان وشەی نهێنی هەڵەیە";
        }
        
        $stmt->close();
    }
}
?> 
<?php


session_start();

// Check if user is already logged in
if (isset($_SESSION['admin_id'])) {
    // Correctly redirect to dashboard using relative path
    header("Location: Selling-System/src/Views/admin/dashboard.php");
    exit();
}

// Database connection
$db = new mysqli('localhost', 'warehouse_user', 'Rawezh.Jaza@0894', 'warehouse_db');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$error = '';

// Check if an auth error message is set
if (isset($_SESSION['auth_error'])) {
    $error = $_SESSION['auth_error'];
    unset($_SESSION['auth_error']); // Clear the error message
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "تکایە هەموو خانەکان پڕ بکەرەوە";
    } else {
        // First check admin_accounts
        $stmt = $db->prepare("SELECT id, username, password_hash FROM admin_accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_activity'] = time(); // Set initial activity time
                
                // Set a session cookie that expires when browser closes
                setcookie('admin_id', session_id(), 0, '/', '', false, true);
                
                // Determine correct path based on script location
                $currentPath = $_SERVER['SCRIPT_NAME'];
                
                // Determine if we're in the main directory or a subdirectory
                if (basename(dirname($currentPath)) === 'process' && basename(dirname(dirname($currentPath))) === 'src') {
                    // If we're in src/process/, use relative path
                    header("Location: ../Views/admin/dashboard.php");
                } else {
                    // Otherwise use the standard path from project root
                    header("Location: Selling-System/src/Views/admin/dashboard.php");
                }
                exit();
            } else {
                $error = "ناوی بەکارهێنەر یان وشەی نهێنی هەڵەیە";
            }
        } else {
            // If not found in admin_accounts, check user_accounts
            $stmt->close();
            
            $stmt = $db->prepare("SELECT id, username, password_hash, is_active, role_id FROM user_accounts WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                if ($user['is_active'] != 1) {
                    $error = "ئەم هەژمارە چالاک نییە";
                } elseif (password_verify($password, $user['password_hash'])) {
                    // Update last login time
                    $update_stmt = $db->prepare("UPDATE user_accounts SET last_login = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['last_activity'] = time(); // Set initial activity time
                    
                    // Set a session cookie that expires when browser closes
                    setcookie('user_id', session_id(), 0, '/', '', false, true);
                    
                    // Redirect to dashboard
                    // Determine correct path based on script location
                    $currentPath = $_SERVER['SCRIPT_NAME'];
                    
                    // Determine if we're in the main directory or a subdirectory
                    if (basename(dirname($currentPath)) === 'process' && basename(dirname(dirname($currentPath))) === 'src') {
                        // If we're in src/process/, use relative path
                        header("Location: ../Views/admin/dashboard.php");
                    } else {
                        // Otherwise use the standard path from project root
                        header("Location: Selling-System/src/Views/admin/dashboard.php");
                    }
                    exit();
                } else {
                    $error = "ناوی بەکارهێنەر یان وشەی نهێنی هەڵەیە";
                }
            } else {
                $error = "ناوی بەکارهێنەر یان وشەی نهێنی هەڵەیە";
            }
        }
        
        $stmt->close();
    }
}
?> 
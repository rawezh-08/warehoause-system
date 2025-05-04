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
        // First check in admin_accounts table
        $stmt = $db->prepare("SELECT id, username, password_hash FROM admin_accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = 'admin'; // Mark as admin user
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
            // If not found in admin_accounts, check in user_accounts
            $stmt = $db->prepare("SELECT ua.id, ua.username, ua.password_hash, ua.role_id, ua.is_active, ur.name as role_name 
                                FROM user_accounts ua 
                                JOIN user_roles ur ON ua.role_id = ur.id 
                                WHERE ua.username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                // Check if account is active
                if (!$user['is_active']) {
                    $error = "ئەم هەژمارە چالاک نییە. تکایە پەیوەندی بکە بە بەڕێوەبەرەوە";
                } else if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['admin_id'] = $user['id']; // Keep the session key the same for simplicity
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = 'user'; // Mark as regular user
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['role_name'] = $user['role_name'];
                    $_SESSION['last_activity'] = time(); // Set initial activity time
                    
                    // Set a session cookie that expires when browser closes
                    setcookie('admin_id', session_id(), 0, '/', '', false, true);
                    
                    // Update last login time
                    $update_stmt = $db->prepare("UPDATE user_accounts SET last_login = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    
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
    }
}
?> 
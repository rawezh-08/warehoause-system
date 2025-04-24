<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is already logged in
if (isset($_SESSION['admin_id'])) {
    // Correctly redirect to dashboard using relative path
    header("Location: /Selling-System/src/views/admin/dashboard.php");
    exit();
}

// Log file access for debugging
$log_file = dirname(__FILE__) . '/login_debug.log';
file_put_contents($log_file, "Login attempt: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents($log_file, "Script path: " . $_SERVER['SCRIPT_NAME'] . "\n", FILE_APPEND);
file_put_contents($log_file, "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n", FILE_APPEND);

// Database connection - with try/catch for better error handling
try {
    // Hostinger database credentials
    $host = 'localhost';
    $user = 'u924953439_Rawezh_Jaza08';
    $pass = 'Rawezh.Jaza@0894';
    $dbname = 'u924953439_warehouse_db';
    
    $db = new mysqli($host, $user, $pass, $dbname);
    
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }
    
    file_put_contents($log_file, "Database connection successful\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents($log_file, "Database error: " . $e->getMessage() . "\n", FILE_APPEND);
    $error = "خەتای پەیوەندی بە داتابەیس: " . $e->getMessage();
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
    
    // Log login attempt
    file_put_contents($log_file, "Login attempt for user: $username\n", FILE_APPEND);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "تکایە هەموو خانەکان پڕ بکەرەوە";
    } else {
        try {
            // Prepare statement to prevent SQL injection
            $stmt = $db->prepare("SELECT id, username, password_hash FROM admin_accounts WHERE username = ?");
            
            if (!$stmt) {
                throw new Exception("SQL Error: " . $db->error);
            }
            
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            file_put_contents($log_file, "Query executed, rows: " . $result->num_rows . "\n", FILE_APPEND);
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['last_activity'] = time(); // Set initial activity time
                    
                    // Set a session cookie that expires when browser closes
                    setcookie('admin_id', session_id(), 0, '/', '', false, true);
                    
                    // Log successful login
                    file_put_contents($log_file, "Login successful for user: $username\n", FILE_APPEND);
                    
                    // Check if dashboard file exists
                    $dashboard_path = $_SERVER['DOCUMENT_ROOT'] . '/Selling-System/src/views/admin/dashboard.php';
                    if (file_exists($dashboard_path)) {
                        file_put_contents($log_file, "Dashboard file exists at: $dashboard_path\n", FILE_APPEND);
                    } else {
                        file_put_contents($log_file, "Dashboard file NOT found at: $dashboard_path\n", FILE_APPEND);
                        // Try alternative path
                        $alt_dashboard_path = dirname(dirname(dirname(__FILE__))) . '/views/admin/dashboard.php';
                        if (file_exists($alt_dashboard_path)) {
                            file_put_contents($log_file, "Dashboard file found at alternative path: $alt_dashboard_path\n", FILE_APPEND);
                            header("Location: /Selling-System/src/views/admin/dashboard.php");
                        } else {
                            file_put_contents($log_file, "Dashboard file NOT found at alternative path either: $alt_dashboard_path\n", FILE_APPEND);
                            throw new Exception("Dashboard file not found. Please check file paths.");
                        }
                    }
                    
                    // Adjust redirects for Hostinger
                    header("Location: /Selling-System/src/views/admin/dashboard.php");
                    exit();
                } else {
                    $error = "ناوی بەکارهێنەر یان وشەی نهێنی هەڵەیە";
                    file_put_contents($log_file, "Password verification failed\n", FILE_APPEND);
                }
            } else {
                $error = "ناوی بەکارهێنەر یان وشەی نهێنی هەڵەیە";
                file_put_contents($log_file, "User not found\n", FILE_APPEND);
            }
            
            $stmt->close();
        } catch (Exception $e) {
            file_put_contents($log_file, "Error during login: " . $e->getMessage() . "\n", FILE_APPEND);
            $error = "Error: " . $e->getMessage();
        }
    }
}
?> 
<?php
// Set session configuration
ini_set('session.gc_maxlifetime', 28800); // 8 hours
ini_set('session.cookie_lifetime', 28800); // 8 hours
session_start();

// Check if user is logged in
function checkAuth() {
    // Check if the session has an admin_id
    if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
        // Set a flash message
        $_SESSION['auth_error'] = "تکایە چوونە ژوورەوە بکەن بۆ بینینی ئەم پەرەیە";
        
        // Calculate path to root directory based on current script location
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        $scriptPath = dirname($_SERVER['SCRIPT_FILENAME']);
        $relativePath = "";
        
        // Get the path segments needed to navigate back to the web root
        $baseDir = str_replace('\\', '/', $documentRoot);
        $currentDir = str_replace('\\', '/', $scriptPath);
        
        if (strpos($currentDir, '/Selling-System/src/views/') !== false) {
            $relativePath = "../../../../";
        } else if (strpos($currentDir, '/Selling-System/src/') !== false) {
            $relativePath = "../../../";
        } else {
            $relativePath = "./";
        }
        
        // Redirect to login page
        header("Location: " . $relativePath . "index.php");
        exit();
    }
    
    // Check if the session is expired (optional)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 28800)) {
        // Session expired (after 8 hours)
        session_unset();
        session_destroy();
        
        // Redirect to login with message
        $_SESSION['auth_error'] = "سێشن کۆتایی هات، تکایە دووبارە چوونە ژوورەوە بکەن";
        
        // Use the same relative path logic as above
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        $scriptPath = dirname($_SERVER['SCRIPT_FILENAME']);
        $relativePath = "";
        
        if (strpos($scriptPath, '/Selling-System/src/views/') !== false) {
            $relativePath = "../../../../";
        } else if (strpos($scriptPath, '/Selling-System/src/') !== false) {
            $relativePath = "../../../";
        } else {
            $relativePath = "./";
        }
        
        header("Location: " . $relativePath . "index.php");
        exit();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Initialize authentication - called automatically when this file is included
checkAuth();
?> 
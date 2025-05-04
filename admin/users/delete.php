<?php
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/check_permission.php';

// Check if user has permission to manage accounts
checkPermission('manage_accounts');

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id > 0) {
    // Delete user
    $query = "DELETE FROM user_accounts WHERE id = $user_id";
    
    if (mysqli_query($conn, $query)) {
        header("Location: index.php?success=3");
    } else {
        header("Location: index.php?error=1");
    }
} else {
    header("Location: index.php");
}
exit; 
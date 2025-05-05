<?php
/**
 * Admin check utility
 * فایلی پشکنینی ئەکاونتی بەڕێوەبەر
 */

// Include required files if not already included
if (!class_exists('Database')) {
    require_once __DIR__ . '/../config/database.php';
}

/**
 * Check if the current session belongs to an admin
 * 
 * @return bool True if user is an admin, false otherwise
 */
function isAdmin() {
    // Check if admin is logged in
    if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
        return true;
    }
    
    return false;
}

/**
 * Check if the user has admin ID in the admin_accounts table
 * 
 * @param int $user_id The user ID to check
 * @return bool True if user is in admin table, false otherwise
 */
function isUserInAdminTable($user_id) {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user ID exists in admin_accounts table
    $query = "SELECT COUNT(*) as count FROM admin_accounts WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return ($row['count'] > 0);
}
?> 
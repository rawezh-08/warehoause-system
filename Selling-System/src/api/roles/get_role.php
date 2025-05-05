<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../models/Permission.php';

// Check if user has permission to manage roles
// require_once '../../includes/check_permission.php';
// checkPermission('manage_roles');

header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_GET['role_id']) || empty($_GET['role_id'])) {
        throw new Exception('ناسنامەی ڕۆڵ پێویستە');
    }

    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();

    // Get role details
    $stmt = $conn->prepare("
        SELECT r.*, GROUP_CONCAT(rp.permission_id) as permissions
        FROM user_roles r
        LEFT JOIN role_permissions rp ON r.id = rp.role_id
        WHERE r.id = :role_id
        GROUP BY r.id
    ");
    $stmt->bindParam(':role_id', $_GET['role_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('ڕۆڵی داواکراو نەدۆزرایەوە');
    }

    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Convert permissions string to array
    $role['permissions'] = $role['permissions'] ? explode(',', $role['permissions']) : [];

    echo json_encode([
        'status' => 'success',
        'data' => $role
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 
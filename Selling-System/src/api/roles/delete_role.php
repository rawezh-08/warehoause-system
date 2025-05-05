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
    if (!isset($_POST['role_id']) || empty($_POST['role_id'])) {
        throw new Exception('ناسنامەی ڕۆڵ پێویستە');
    }

    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();

    try {
        // Check if role is in use
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_accounts WHERE role_id = :role_id");
        $stmt->bindParam(':role_id', $_POST['role_id']);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($count > 0) {
            throw new Exception('ناتوانرێت ئەم ڕۆڵە بسڕدرێتەوە چونکە بەکارهێنەری هەیە');
        }

        // Delete role permissions
        $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
        $stmt->bindParam(':role_id', $_POST['role_id']);
        $stmt->execute();

        // Delete role
        $stmt = $conn->prepare("DELETE FROM user_roles WHERE id = :role_id");
        $stmt->bindParam(':role_id', $_POST['role_id']);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'ڕۆڵەکە بە سەرکەوتوویی سڕایەوە'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 
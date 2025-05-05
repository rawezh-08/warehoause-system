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

    if (!isset($_POST['name']) || empty($_POST['name'])) {
        throw new Exception('ناوی ڕۆڵ پێویستە');
    }

    if (!isset($_POST['permissions']) || !is_array($_POST['permissions']) || empty($_POST['permissions'])) {
        throw new Exception('هەڵبژاردنی دەسەڵات پێویستە');
    }

    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();

    try {
        // Update role
        $stmt = $conn->prepare("UPDATE user_roles SET name = :name, description = :description WHERE id = :id");
        $stmt->bindParam(':name', $_POST['name']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':id', $_POST['role_id']);
        $stmt->execute();

        // Delete existing permissions
        $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
        $stmt->bindParam(':role_id', $_POST['role_id']);
        $stmt->execute();

        // Insert new permissions
        $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
        
        foreach ($_POST['permissions'] as $permission_id) {
            $stmt->bindParam(':role_id', $_POST['role_id']);
            $stmt->bindParam(':permission_id', $permission_id);
            $stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'ڕۆڵەکە بە سەرکەوتوویی نوێکرایەوە'
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
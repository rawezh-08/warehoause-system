<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['draft_id'])) {
        throw new Exception('Draft ID is required');
    }

    $draft_id = $_POST['draft_id'];

    // Get draft information with customer details
    $stmt = $conn->prepare("
        SELECT 
            s.*,
            c.name as customer_name
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.id = :draft_id AND s.is_draft = 1
    ");

    $stmt->execute([':draft_id' => $draft_id]);
    $draft = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$draft) {
        throw new Exception('Draft not found');
    }

    // Get draft items
    $stmt = $conn->prepare("
        SELECT 
            si.*,
            p.name as product_name,
            p.current_quantity as available_quantity
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = :draft_id
    ");

    $stmt->execute([':draft_id' => $draft_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $draft['items'] = $items;

    echo json_encode([
        'status' => 'success',
        'draft' => $draft
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 
<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || !isset($_POST['type'])) {
        throw new Exception('داواکاری نادروستە');
    }

    $id = intval($_POST['id']);
    $type = $_POST['type'];

    if (!in_array($type, ['sale', 'purchase'])) {
        throw new Exception('جۆری پسووڵە نادروستە');
    }

    if ($type === 'sale') {
        $stmt = $conn->prepare("
            SELECT si.*, p.name as product_name, p.pieces_per_box, p.boxes_per_set
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT pi.*, p.name as product_name, p.pieces_per_box, p.boxes_per_set
            FROM purchase_items pi
            JOIN products p ON pi.product_id = p.id
            WHERE pi.purchase_id = ?
        ");
    }

    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $items
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
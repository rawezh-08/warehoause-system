<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Get filter parameters
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    $type = $_POST['type'] ?? '';
    $customerName = $_POST['customer_name'] ?? '';
    $supplierName = $_POST['supplier_name'] ?? '';
    $invoiceNumber = $_POST['invoice_number'] ?? '';

    // Base query depending on type
    switch ($type) {
        case 'selling':
            $query = "SELECT s.*, c.name as customer_name,
                     COALESCE(SUM(si.total_price), 0) as subtotal,
                     (COALESCE(SUM(si.total_price), 0) + s.shipping_cost + s.other_costs - s.discount) as total_amount,
                     GROUP_CONCAT(
                         CONCAT(p.name, ' (', si.quantity, ' ', 
                         CASE si.unit_type 
                             WHEN 'piece' THEN 'دانە'
                             WHEN 'box' THEN 'کارتۆن'
                             WHEN 'set' THEN 'سێت'
                         END, ')')
                         SEPARATOR ', '
                     ) as products_list
                     FROM sales s
                     LEFT JOIN customers c ON s.customer_id = c.id
                     LEFT JOIN sale_items si ON s.id = si.sale_id
                     LEFT JOIN products p ON si.product_id = p.id
                     WHERE 1=1 AND s.is_draft = 0";

            if ($startDate) {
                $query .= " AND DATE(s.date) >= :start_date";
            }
            if ($endDate) {
                $query .= " AND DATE(s.date) <= :end_date";
            }
            if ($customerName) {
                $query .= " AND c.name LIKE :customer_name";
            }
            if ($invoiceNumber) {
                $query .= " AND s.invoice_number LIKE :invoice_number";
            }
            
            $query .= " GROUP BY s.id ORDER BY s.date DESC";
            break;

        case 'buying':
            $query = "SELECT p.*, s.name as supplier_name,
                     COALESCE(SUM(pi.total_price), 0) as subtotal,
                     (COALESCE(SUM(pi.total_price), 0) + p.shipping_cost + p.other_cost - p.discount) as total_amount,
                     GROUP_CONCAT(
                         CONCAT(pr.name, ' (', pi.quantity, ' ', 
                         CASE pi.unit_type 
                             WHEN 'piece' THEN 'دانە'
                             WHEN 'box' THEN 'کارتۆن'
                             WHEN 'set' THEN 'سێت'
                         END, ')')
                         SEPARATOR ', '
                     ) as products_list
                     FROM purchases p
                     LEFT JOIN suppliers s ON p.supplier_id = s.id
                     LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
                     LEFT JOIN products pr ON pi.product_id = pr.id
                     WHERE 1=1";

            if ($startDate) {
                $query .= " AND DATE(p.date) >= :start_date";
            }
            if ($endDate) {
                $query .= " AND DATE(p.date) <= :end_date";
            }
            if ($supplierName) {
                $query .= " AND s.name LIKE :supplier_name";
            }
            if ($invoiceNumber) {
                $query .= " AND p.invoice_number LIKE :invoice_number";
            }
            
            $query .= " GROUP BY p.id ORDER BY p.date DESC";
            break;

        default:
            throw new Exception('Invalid receipt type');
    }

    $stmt = $conn->prepare($query);

    // Bind parameters
    if ($startDate) {
        $stmt->bindParam(':start_date', $startDate);
    }
    if ($endDate) {
        $stmt->bindParam(':end_date', $endDate);
    }
    if ($customerName) {
        $customerNameParam = "%$customerName%";
        $stmt->bindParam(':customer_name', $customerNameParam);
    }
    if ($supplierName) {
        $supplierNameParam = "%$supplierName%";
        $stmt->bindParam(':supplier_name', $supplierNameParam);
    }
    if ($invoiceNumber) {
        $invoiceNumberParam = "%$invoiceNumber%";
        $stmt->bindParam(':invoice_number', $invoiceNumberParam);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the results
    foreach ($results as &$row) {
        // Convert amounts to formatted strings with د.ع suffix
        $row['total_amount'] = number_format($row['total_amount'], 0, '.', ',') . ' د.ع';
        if (isset($row['subtotal'])) {
            $row['subtotal'] = number_format($row['subtotal'], 0, '.', ',') . ' د.ع';
        }
        if (isset($row['shipping_cost'])) {
            $row['shipping_cost'] = number_format($row['shipping_cost'], 0, '.', ',') . ' د.ع';
        }
        if (isset($row['other_costs']) || isset($row['other_cost'])) {
            $otherCosts = isset($row['other_costs']) ? $row['other_costs'] : $row['other_cost'];
            $row['other_costs'] = number_format($otherCosts, 0, '.', ',') . ' د.ع';
        }
        if (isset($row['discount'])) {
            $row['discount'] = number_format($row['discount'], 0, '.', ',') . ' د.ع';
        }
        
        // Format date
        $row['date'] = date('Y/m/d', strtotime($row['date']));
    }

    echo json_encode([
        'success' => true,
        'data' => $results
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
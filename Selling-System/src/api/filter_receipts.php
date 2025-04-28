<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Get filter parameters
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    $customerName = $_POST['customer_name'] ?? null;
    $type = $_POST['type'] ?? 'sale';
    
    // For debugging, log the request parameters
    error_log("Filter receipts API called with type: " . $type);
    error_log("Parameters: " . json_encode($_POST));
    
    // Normalize type value to handle both 'sale' and 'selling'
    if ($type === 'selling') {
        $type = 'sale';
    }

    // Start building the query
    $query = "";
    $params = [];
    
    if ($type === 'sale') {
        $query = "
            SELECT s.*, 
                   c.name as customer_name,
                   GROUP_CONCAT(
                       CONCAT(p.name, ' (', si.quantity, ' ', 
                       CASE si.unit_type 
                           WHEN 'piece' THEN 'دانە'
                           WHEN 'box' THEN 'کارتۆن'
                           WHEN 'set' THEN 'سێت'
                       END, ')')
                   SEPARATOR ', ') as products_list,
                   COALESCE(SUM(si.total_price), 0) as subtotal,
                   (COALESCE(SUM(si.total_price), 0) + s.shipping_cost + s.other_costs - s.discount) as total_amount
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN sale_items si ON s.id = si.sale_id
            LEFT JOIN products p ON si.product_id = p.id
            WHERE s.is_draft = 0
        ";

        // Add date filters if provided
        if ($startDate) {
            $query .= " AND DATE(s.date) >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $query .= " AND DATE(s.date) <= ?";
            $params[] = $endDate;
        }
        
        // Add customer name filter if provided
        if ($customerName) {
            $query .= " AND c.name LIKE ?";
            $params[] = "%$customerName%";
        }

        $query .= " GROUP BY s.id ORDER BY s.date DESC";
    } else if ($type === 'buying') {
        // Handle buying receipts
        $supplierName = $_POST['supplier_name'] ?? null;
        $invoiceNumber = $_POST['invoice_number'] ?? null;
        
        $query = "
            SELECT p.*, 
                   s.name as supplier_name,
                   GROUP_CONCAT(
                       CONCAT(pr.name, ' (', pi.quantity, ' ', 
                       CASE pi.unit_type 
                           WHEN 'piece' THEN 'دانە'
                           WHEN 'box' THEN 'کارتۆن'
                           WHEN 'set' THEN 'سێت'
                       END, ')')
                   SEPARATOR ', ') as products_list,
                   COALESCE(SUM(pi.total_price), 0) as subtotal,
                   (COALESCE(SUM(pi.total_price), 0) + p.shipping_cost + p.other_costs - p.discount) as total_amount
            FROM purchases p
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
            LEFT JOIN products pr ON pi.product_id = pr.id
            WHERE p.is_draft = 0
        ";
        
        // Add date filters if provided
        if ($startDate) {
            $query .= " AND DATE(p.date) >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $query .= " AND DATE(p.date) <= ?";
            $params[] = $endDate;
        }
        
        // Add supplier name filter if provided
        if ($supplierName) {
            $query .= " AND s.name LIKE ?";
            $params[] = "%$supplierName%";
        }
        
        // Add invoice number filter if provided
        if ($invoiceNumber) {
            $query .= " AND p.invoice_number LIKE ?";
            $params[] = "%$invoiceNumber%";
        }
        
        $query .= " GROUP BY p.id ORDER BY p.date DESC";
    } else {
        // Invalid type
        throw new Exception("Invalid receipt type: " . $type);
    }
    
    // Log the SQL query for debugging
    error_log("SQL Query: " . $query);
    error_log("SQL Params: " . json_encode($params));

    // Prepare the statement
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        // Handle SQL preparation error
        $error = $conn->errorInfo();
        throw new Exception("SQL Prepare Error: " . $error[2]);
    }
    
    // Execute the query with parameters
    $result = false;
    if (!empty($params)) {
        $result = $stmt->execute($params);
    } else {
        $result = $stmt->execute();
    }
    
    if ($result === false) {
        // Handle SQL execution error
        $error = $stmt->errorInfo();
        throw new Exception("SQL Execution Error: " . $error[2]);
    }
    
    // Fetch results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Results count: " . count($results));

    // Format only the date, leave numbers as is
    foreach ($results as &$row) {
        // Format date only
        if (isset($row['date'])) {
            $row['date'] = date('Y/m/d', strtotime($row['date']));
        }
        
        // Ensure numeric values are returned as numbers, not strings
        if (isset($row['total_amount'])) {
            $row['total_amount'] = floatval($row['total_amount']);
        }
        if (isset($row['subtotal'])) {
            $row['subtotal'] = floatval($row['subtotal']);
        }
        if (isset($row['shipping_cost'])) {
            $row['shipping_cost'] = floatval($row['shipping_cost']);
        }
        if (isset($row['other_costs'])) {
            $row['other_costs'] = floatval($row['other_costs']);
        }
        if (isset($row['discount'])) {
            $row['discount'] = floatval($row['discount']);
        }
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $results
    ]);

} catch (Exception $e) {
    // Log the detailed error
    error_log("Error in filter_receipts.php: " . $e->getMessage());
    error_log("File: " . $e->getFile() . " on line " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
} 
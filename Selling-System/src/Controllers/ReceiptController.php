<?php
require_once '../../config/database.php';

// Function to get sales data with customer info
function getSalesData($limit = 0, $offset = 0, $filters = []) {
    global $conn;
    
    // Base query to get sales with joined customer info and calculated total amount
    $sql = "SELECT 
                s.*, 
                c.name as customer_name,
                (
                    SELECT GROUP_CONCAT(
                        CONCAT(p.name, ' (', si.quantity, ' ', 
                            CASE si.unit_type 
                                WHEN 'piece' THEN 'دانە'
                                WHEN 'box' THEN 'کارتۆن'
                                WHEN 'set' THEN 'سێت'
                            END,
                            ')'
                        ) SEPARATOR ', '
                    )
                    FROM sale_items si 
                    LEFT JOIN products p ON si.product_id = p.id 
                    WHERE si.sale_id = s.id
                ) as products_list,
                SUM(si.total_price) as subtotal,
                s.shipping_cost + s.other_costs as additional_costs,
                SUM(si.total_price) + s.shipping_cost + s.other_costs - s.discount as total_amount
            FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            LEFT JOIN sale_items si ON s.id = si.sale_id
            LEFT JOIN products p ON si.product_id = p.id
            WHERE 1=1";
    
    $params = [];
    
    // Apply filters if any
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(s.date) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(s.date) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    if (!empty($filters['customer_name'])) {
        $sql .= " AND c.name LIKE :customer_name";
        $params[':customer_name'] = '%' . $filters['customer_name'] . '%';
    }
    if (!empty($filters['invoice_number'])) {
        $sql .= " AND s.invoice_number LIKE :invoice_number";
        $params[':invoice_number'] = '%' . $filters['invoice_number'] . '%';
    }
    
    $sql .= " GROUP BY s.id ORDER BY s.date DESC";
    
    // Only apply limit if it's greater than 0
    if ($limit > 0) {
        $sql .= " LIMIT :offset, :limit";
        $params[':offset'] = (int)$offset;
        $params[':limit'] = (int)$limit;
    }
    
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            if (($key == ':offset' || $key == ':limit') && $limit > 0) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

// Function to get purchases data with supplier info
function getPurchasesData($limit = 0, $offset = 0, $filters = []) {
    global $conn;
    
    // Base query to get purchases with joined supplier info and calculated total amount
    $sql = "SELECT 
                p.*, 
                s.name as supplier_name,
                (
                    SELECT GROUP_CONCAT(
                        CONCAT(pr.name, ' (', pi.quantity, ' دانە)') 
                        SEPARATOR ', '
                    )
                    FROM purchase_items pi 
                    LEFT JOIN products pr ON pi.product_id = pr.id 
                    WHERE pi.purchase_id = p.id
                ) as products_list,
                SUM(pi.total_price) as subtotal,
                SUM(pi.total_price) - p.discount as total_amount
            FROM purchases p 
            LEFT JOIN suppliers s ON p.supplier_id = s.id 
            LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
            LEFT JOIN products pr ON pi.product_id = pr.id
            WHERE 1=1";
    
    $params = [];
    
    // Apply filters if any
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(p.date) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(p.date) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    if (!empty($filters['supplier_name'])) {
        $sql .= " AND s.name LIKE :supplier_name";
        $params[':supplier_name'] = '%' . $filters['supplier_name'] . '%';
    }
    
    $sql .= " GROUP BY p.id ORDER BY p.date DESC";
    
    // Only apply limit if it's greater than 0
    if ($limit > 0) {
        $sql .= " LIMIT :offset, :limit";
        $params[':offset'] = (int)$offset;
        $params[':limit'] = (int)$limit;
    }
    
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            if (($key == ':offset' || $key == ':limit') && $limit > 0) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'filter':
            $filters = [
                'start_date' => $_POST['start_date'] ?? null,
                'end_date' => $_POST['end_date'] ?? null
            ];
            
            if ($_POST['type'] == 'sales') {
                if (!empty($_POST['customer_name'])) {
                    $filters['customer_name'] = $_POST['customer_name'];
                }
                if (!empty($_POST['invoice_number'])) {
                    $filters['invoice_number'] = $_POST['invoice_number'];
                }
                $data = getSalesData(0, 0, $filters);
                echo json_encode(['success' => true, 'data' => $data]);
            } else if ($_POST['type'] == 'purchases') {
                if (!empty($_POST['supplier_name'])) {
                    $filters['supplier_name'] = $_POST['supplier_name'];
                }
                if (!empty($_POST['invoice_number'])) {
                    $filters['invoice_number'] = $_POST['invoice_number'];
                }
                $data = getPurchasesData(0, 0, $filters);
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request type']);
            }
            break;
            
        case 'update_sale':
            try {
                $stmt = $conn->prepare("UPDATE sales SET 
                    invoice_number = :invoice_number,
                    customer_id = :customer_id,
                    date = :date,
                    shipping_cost = :shipping_cost,
                    other_costs = :other_costs,
                    discount = :discount,
                    payment_type = :payment_type,
                    notes = :notes
                    WHERE id = :id");
                
                $stmt->execute([
                    ':id' => $_POST['id'],
                    ':invoice_number' => $_POST['invoice_number'],
                    ':customer_id' => $_POST['customer_id'],
                    ':date' => $_POST['date'],
                    ':shipping_cost' => $_POST['shipping_cost'],
                    ':other_costs' => $_POST['other_costs'],
                    ':discount' => $_POST['discount'],
                    ':payment_type' => $_POST['payment_type'],
                    ':notes' => $_POST['notes']
                ]);
                
                echo json_encode(['success' => true]);
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Database error occurred']);
            }
            break;
            
        case 'update_purchase':
            try {
                $stmt = $conn->prepare("UPDATE purchases SET 
                    invoice_number = :invoice_number,
                    supplier_id = :supplier_id,
                    date = :date,
                    discount = :discount,
                    payment_type = :payment_type,
                    notes = :notes
                    WHERE id = :id");
                
                $stmt->execute([
                    ':id' => $_POST['id'],
                    ':invoice_number' => $_POST['invoice_number'],
                    ':supplier_id' => $_POST['supplier_id'],
                    ':date' => $_POST['date'],
                    ':discount' => $_POST['discount'],
                    ':payment_type' => $_POST['payment_type'],
                    ':notes' => $_POST['notes']
                ]);
                
                echo json_encode(['success' => true]);
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Database error occurred']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Get initial data for page load
$today = date('Y-m-d');
$startOfMonth = date('Y-m-01');

$defaultFilters = [
    'start_date' => $startOfMonth,
    'end_date' => $today
];

$salesData = getSalesData(0, 0, $defaultFilters);
$purchasesData = getPurchasesData(0, 0, $defaultFilters);

class ReceiptController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function generateReceipt($sale_id) {
        try {
            // Get sale details
            $saleQuery = "SELECT s.*, 
                                c.name as customer_name,
                                c.phone as customer_phone
                         FROM sales s
                         LEFT JOIN customers c ON s.customer_id = c.id
                         WHERE s.id = :sale_id";
            
            $stmt = $this->conn->prepare($saleQuery);
            $stmt->bindParam(':sale_id', $sale_id);
            $stmt->execute();
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sale) {
                throw new Exception("Sale not found");
            }
            
            // Get sale items
            $itemsQuery = "SELECT si.*, 
                                 p.name as product_name,
                                 p.code as product_code,
                                 p.image as product_image
                          FROM sale_items si
                          LEFT JOIN products p ON si.product_id = p.id
                          WHERE si.sale_id = :sale_id";
            
            $stmt = $this->conn->prepare($itemsQuery);
            $stmt->bindParam(':sale_id', $sale_id);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format products data
            $formatted_products = array_map(function($item) {
                return [
                    'image' => $item['product_image'],
                    'code' => $item['product_code'],
                    'name' => $item['product_name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'total' => $item['price'] * $item['quantity'],
                    'unit_price' => $item['price']
                ];
            }, $products);
            
            // Calculate totals
            $total_amount = array_sum(array_column($formatted_products, 'total'));
            $discount = $sale['discount'] ?? 0;
            $after_discount = $total_amount * (1 - ($discount / 100));
            $paid_amount = $sale['paid_amount'] ?? 0;
            $remaining_balance = $after_discount - $paid_amount;
            
            // Previous balance calculations (you may need to adjust this based on your business logic)
            $previous_balance = $sale['previous_balance'] ?? 0;
            $remaining_amount = $sale['remaining_amount'] ?? 0;
            $grand_total = $previous_balance + $remaining_amount;
            
            // Include all variables in the view
            include '../views/receipt/print_receipt.php';
            
        } catch (Exception $e) {
            // Handle error appropriately
            echo "Error generating receipt: " . $e->getMessage();
        }
    }
    
    public function generateQRCode($sale_id) {
        // Implement QR code generation logic here
        // You can use libraries like phpqrcode
        return "QR code data for sale #" . $sale_id;
    }
}

// Usage example:
if (isset($_GET['sale_id'])) {
    $receiptController = new ReceiptController($conn);
    $receiptController->generateReceipt($_GET['sale_id']);
}
?> 
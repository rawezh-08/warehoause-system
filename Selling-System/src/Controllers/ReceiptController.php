<?php
require_once '../config/database.php';

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
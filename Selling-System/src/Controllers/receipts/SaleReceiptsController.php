<?php
require_once '../../config/database.php';

class SaleReceiptsController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get sales data with filtering options
     */
    public function getSalesData($limit = 0, $offset = 0, $filters = []) {
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
                    SUM((si.quantity - COALESCE(si.returned_quantity, 0)) * si.unit_price) as subtotal,
                    s.shipping_cost + s.other_costs as additional_costs,
                    SUM((si.quantity - COALESCE(si.returned_quantity, 0)) * si.unit_price) + s.shipping_cost + s.other_costs - s.discount as total_amount
                FROM sales s 
                LEFT JOIN customers c ON s.customer_id = c.id 
                LEFT JOIN sale_items si ON s.id = si.sale_id
                LEFT JOIN products p ON si.product_id = p.id
                WHERE s.is_draft = 0";
        
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
            $stmt = $this->conn->prepare($sql);
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
            error_log("Database error in SaleReceiptsController: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update a sale record
     */
    public function updateSale($saleData) {
        try {
            $stmt = $this->conn->prepare("UPDATE sales SET 
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
                ':id' => $saleData['id'],
                ':invoice_number' => $saleData['invoice_number'],
                ':customer_id' => $saleData['customer_id'],
                ':date' => $saleData['date'],
                ':shipping_cost' => $saleData['shipping_cost'],
                ':other_costs' => $saleData['other_costs'],
                ':discount' => $saleData['discount'],
                ':payment_type' => $saleData['payment_type'],
                ':notes' => $saleData['notes']
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Database error in updateSale: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate receipt for a sale
     */
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
            
            // Previous balance calculations
            $previous_balance = $sale['previous_balance'] ?? 0;
            $remaining_amount = $sale['remaining_amount'] ?? 0;
            $grand_total = $previous_balance + $remaining_amount;
            
            return [
                'sale' => $sale,
                'products' => $formatted_products,
                'total_amount' => $total_amount,
                'discount' => $discount,
                'after_discount' => $after_discount,
                'paid_amount' => $paid_amount,
                'remaining_balance' => $remaining_balance,
                'previous_balance' => $previous_balance,
                'grand_total' => $grand_total
            ];
            
        } catch (Exception $e) {
            error_log("Error generating receipt: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a sale
     */
    public function deleteSale($sale_id) {
        try {
            // Begin transaction
            $this->conn->beginTransaction();
            
            // First delete related sale items
            $stmt = $this->conn->prepare("DELETE FROM sale_items WHERE sale_id = :sale_id");
            $stmt->execute([':sale_id' => $sale_id]);
            
            // Then delete the sale record
            $stmt = $this->conn->prepare("DELETE FROM sales WHERE id = :sale_id");
            $stmt->execute([':sale_id' => $sale_id]);
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            error_log("Database error in deleteSale: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get customers list
     */
    public function getCustomers() {
        try {
            $stmt = $this->conn->query("SELECT id, name FROM customers ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getCustomers: " . $e->getMessage());
            return [];
        }
    }
} 
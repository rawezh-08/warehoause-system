<?php
require_once '../../config/database.php';

class PurchaseReceiptsController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get purchases data with filtering options
     */
    public function getPurchasesData($limit = 0, $offset = 0, $filters = []) {
        // Base query to get purchases with joined supplier info and calculated total amount
        $sql = "SELECT 
                    p.*, 
                    s.name as supplier_name,
                    (
                        SELECT GROUP_CONCAT(
                            CONCAT(pr.name, ' (', pi.quantity, ' ', 
                                CASE pi.unit_type 
                                    WHEN 'piece' THEN 'دانە'
                                    WHEN 'box' THEN 'کارتۆن'
                                    WHEN 'set' THEN 'سێت'
                                END, ')')
                            SEPARATOR ', '
                        )
                        FROM purchase_items pi 
                        LEFT JOIN products pr ON pi.product_id = pr.id 
                        WHERE pi.purchase_id = p.id
                    ) as products_list,
                    SUM((pi.quantity - COALESCE(pi.returned_quantity, 0)) * pi.unit_price) as subtotal,
                    SUM((pi.quantity - COALESCE(pi.returned_quantity, 0)) * pi.unit_price) - p.discount as total_amount
                FROM purchases p 
                LEFT JOIN suppliers s ON p.supplier_id = s.id 
                LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
                LEFT JOIN products pr ON pi.product_id = pr.id";
        
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
        if (!empty($filters['invoice_number'])) {
            $sql .= " AND p.invoice_number LIKE :invoice_number";
            $params[':invoice_number'] = '%' . $filters['invoice_number'] . '%';
        }
        
        $sql .= " GROUP BY p.id ORDER BY p.date DESC";
        
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
            error_log("Database error in PurchaseReceiptsController: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update a purchase record
     */
    public function updatePurchase($purchaseData) {
        try {
            $stmt = $this->conn->prepare("UPDATE purchases SET 
                invoice_number = :invoice_number,
                supplier_id = :supplier_id,
                date = :date,
                discount = :discount,
                payment_type = :payment_type,
                notes = :notes
                WHERE id = :id");
            
            $stmt->execute([
                ':id' => $purchaseData['id'],
                ':invoice_number' => $purchaseData['invoice_number'],
                ':supplier_id' => $purchaseData['supplier_id'],
                ':date' => $purchaseData['date'],
                ':discount' => $purchaseData['discount'],
                ':payment_type' => $purchaseData['payment_type'],
                ':notes' => $purchaseData['notes']
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Database error in updatePurchase: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate receipt for a purchase
     */
    public function generateReceipt($purchase_id) {
        try {
            // Get purchase details
            $purchaseQuery = "SELECT p.*, 
                                    s.name as supplier_name,
                                    s.phone as supplier_phone
                             FROM purchases p
                             LEFT JOIN suppliers s ON p.supplier_id = s.id
                             WHERE p.id = :purchase_id";
            
            $stmt = $this->conn->prepare($purchaseQuery);
            $stmt->bindParam(':purchase_id', $purchase_id);
            $stmt->execute();
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$purchase) {
                throw new Exception("Purchase not found");
            }
            
            // Get purchase items
            $itemsQuery = "SELECT pi.*, 
                                 pr.name as product_name,
                                 pr.code as product_code,
                                 pr.image as product_image
                          FROM purchase_items pi
                          LEFT JOIN products pr ON pi.product_id = pr.id
                          WHERE pi.purchase_id = :purchase_id";
            
            $stmt = $this->conn->prepare($itemsQuery);
            $stmt->bindParam(':purchase_id', $purchase_id);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format products data
            $formatted_products = array_map(function($item) {
                return [
                    'image' => $item['product_image'],
                    'code' => $item['product_code'],
                    'name' => $item['product_name'],
                    'price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'total' => $item['unit_price'] * $item['quantity'],
                    'unit_price' => $item['unit_price']
                ];
            }, $products);
            
            // Calculate totals
            $total_amount = array_sum(array_column($formatted_products, 'total'));
            $discount = $purchase['discount'] ?? 0;
            $after_discount = $total_amount - $discount;
            $paid_amount = $purchase['paid_amount'] ?? 0;
            $remaining_balance = $after_discount - $paid_amount;
            
            return [
                'purchase' => $purchase,
                'products' => $formatted_products,
                'total_amount' => $total_amount,
                'discount' => $discount,
                'after_discount' => $after_discount,
                'paid_amount' => $paid_amount,
                'remaining_balance' => $remaining_balance
            ];
            
        } catch (Exception $e) {
            error_log("Error generating purchase receipt: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a purchase
     */
    public function deletePurchase($purchase_id) {
        try {
            // Begin transaction
            $this->conn->beginTransaction();
            
            // First delete related purchase items
            $stmt = $this->conn->prepare("DELETE FROM purchase_items WHERE purchase_id = :purchase_id");
            $stmt->execute([':purchase_id' => $purchase_id]);
            
            // Then delete the purchase record
            $stmt = $this->conn->prepare("DELETE FROM purchases WHERE id = :purchase_id");
            $stmt->execute([':purchase_id' => $purchase_id]);
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            error_log("Database error in deletePurchase: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get suppliers list
     */
    public function getSuppliers() {
        try {
            $stmt = $this->conn->query("SELECT id, name FROM suppliers ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getSuppliers: " . $e->getMessage());
            return [];
        }
    }
} 
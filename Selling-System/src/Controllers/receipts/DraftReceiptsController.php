<?php
require_once '../../config/database.php';

class DraftReceiptsController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get draft receipts with filtering options
     */
    public function getDraftReceipts($limit = 0, $offset = 0, $filters = []) {
        // Base query for drafts
        $sql = "SELECT s.*, 
                    c.name as customer_name,
                    COALESCE(SUM(si.total_price), 0) as subtotal,
                    (COALESCE(SUM(si.total_price), 0) + s.shipping_cost + s.other_costs - s.discount) as total_amount
                FROM sales s
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN sale_items si ON s.id = si.sale_id
                WHERE s.is_draft = 1";
        
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
        
        $sql .= " GROUP BY s.id ORDER BY s.created_at DESC";
        
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
            error_log("Database error in DraftReceiptsController: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get draft receipt details by ID
     */
    public function getDraftReceiptById($receipt_id) {
        try {
            // Get receipt header
            $headerQuery = "SELECT s.*, c.name as customer_name, c.phone as customer_phone
                           FROM sales s
                           LEFT JOIN customers c ON s.customer_id = c.id
                           WHERE s.id = :id AND s.is_draft = 1";
            
            $stmt = $this->conn->prepare($headerQuery);
            $stmt->bindParam(':id', $receipt_id);
            $stmt->execute();
            $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$receipt) {
                return false;
            }
            
            // Get receipt items
            $itemsQuery = "SELECT si.*, p.name as product_name, p.code as product_code
                          FROM sale_items si
                          LEFT JOIN products p ON si.product_id = p.id
                          WHERE si.sale_id = :sale_id";
            
            $stmt = $this->conn->prepare($itemsQuery);
            $stmt->bindParam(':sale_id', $receipt_id);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'header' => $receipt,
                'items' => $items
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getDraftReceiptById: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Finalize a draft receipt (convert it to a normal receipt)
     */
    public function finalizeDraftReceipt($receipt_id) {
        try {
            $stmt = $this->conn->prepare("UPDATE sales SET is_draft = 0 WHERE id = :id");
            $stmt->execute([':id' => $receipt_id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Database error in finalizeDraftReceipt: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a draft receipt
     */
    public function deleteDraftReceipt($receipt_id) {
        try {
            // Begin transaction
            $this->conn->beginTransaction();
            
            // First delete related sale items
            $stmt = $this->conn->prepare("DELETE FROM sale_items WHERE sale_id = :sale_id");
            $stmt->execute([':sale_id' => $receipt_id]);
            
            // Then delete the sale record
            $stmt = $this->conn->prepare("DELETE FROM sales WHERE id = :id AND is_draft = 1");
            $stmt->execute([':id' => $receipt_id]);
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            error_log("Database error in deleteDraftReceipt: " . $e->getMessage());
            return false;
        }
    }
} 
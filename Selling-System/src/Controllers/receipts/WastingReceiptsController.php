<?php
require_once '../../config/database.php';

class WastingReceiptsController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get wasting data with filtering options
     */
    public function getWastingData($limit = 0, $offset = 0, $filters = []) {
        // Base query
        $sql = "SELECT w.*, 
                    GROUP_CONCAT(
                        CONCAT(p.name, ' (', wi.quantity, ' ', 
                        CASE wi.unit_type 
                            WHEN 'piece' THEN 'دانە'
                            WHEN 'box' THEN 'کارتۆن'
                            WHEN 'set' THEN 'سێت'
                        END, ')')
                    SEPARATOR ', ') as products_list,
                    SUM(wi.total_price) as total_amount
                FROM wastings w
                LEFT JOIN wasting_items wi ON w.id = wi.wasting_id
                LEFT JOIN products p ON wi.product_id = p.id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters if any
        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(w.date) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(w.date) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        
        $sql .= " GROUP BY w.id ORDER BY w.date DESC";
        
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
            error_log("Database error in WastingReceiptsController: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get wasting details by ID
     */
    public function getWastingById($wasting_id) {
        try {
            // Get wasting header
            $headerQuery = "SELECT * FROM wastings WHERE id = :id";
            $stmt = $this->conn->prepare($headerQuery);
            $stmt->bindParam(':id', $wasting_id);
            $stmt->execute();
            $wasting = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$wasting) {
                return false;
            }
            
            // Get wasting items
            $itemsQuery = "SELECT wi.*, 
                               p.name as product_name,
                               p.code as product_code
                           FROM wasting_items wi
                           LEFT JOIN products p ON wi.product_id = p.id
                           WHERE wi.wasting_id = :wasting_id";
            
            $stmt = $this->conn->prepare($itemsQuery);
            $stmt->bindParam(':wasting_id', $wasting_id);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate total
            $total = 0;
            foreach ($items as $item) {
                $total += $item['total_price'];
            }
            
            return [
                'header' => $wasting,
                'items' => $items,
                'total' => $total
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getWastingById: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a wasting record
     */
    public function deleteWasting($wasting_id) {
        try {
            // Begin transaction
            $this->conn->beginTransaction();
            
            // First delete related wasting items
            $stmt = $this->conn->prepare("DELETE FROM wasting_items WHERE wasting_id = :wasting_id");
            $stmt->execute([':wasting_id' => $wasting_id]);
            
            // Then delete the wasting record
            $stmt = $this->conn->prepare("DELETE FROM wastings WHERE id = :wasting_id");
            $stmt->execute([':wasting_id' => $wasting_id]);
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            error_log("Database error in deleteWasting: " . $e->getMessage());
            return false;
        }
    }
} 
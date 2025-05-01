<?php
class PurchaseReceiptsController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getPurchasesData($filters = []) {
        try {
            $where = [];
            $params = [];

            // Apply date filters
            if (!empty($filters['start_date'])) {
                $where[] = "p.date >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }
            if (!empty($filters['end_date'])) {
                $where[] = "p.date <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }

            // Apply supplier name filter
            if (!empty($filters['supplier_name'])) {
                $where[] = "s.name LIKE :supplier_name";
                $params[':supplier_name'] = "%{$filters['supplier_name']}%";
            }

            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $query = "
                SELECT 
                    p.id,
                    p.invoice_number,
                    p.date,
                    p.payment_type,
                    p.shipping_cost,
                    p.other_cost,
                    p.discount,
                    GROUP_CONCAT(DISTINCT pr.name SEPARATOR ', ') as products_list,
                    GROUP_CONCAT(DISTINCT pr.code SEPARATOR ', ') as product_code,
                    SUM(pi.quantity) as quantity,
                    AVG(pi.unit_price) as unit_price,
                    SUM(pi.total_price) as total_amount,
                    s.name as supplier_name
                FROM purchases p
                LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
                LEFT JOIN products pr ON pi.product_id = pr.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                $whereClause
                GROUP BY p.id
                ORDER BY p.date DESC
            ";

            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPurchasesData: " . $e->getMessage());
            return [];
        }
    }

    public function getPurchaseItems($purchaseId) {
        try {
            $query = "
                SELECT 
                    pi.*,
                    p.name as product_name,
                    p.code as product_code
                FROM purchase_items pi
                JOIN products p ON pi.product_id = p.id
                WHERE pi.purchase_id = :purchase_id
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':purchase_id', $purchaseId);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPurchaseItems: " . $e->getMessage());
            return [];
        }
    }

    public function deletePurchase($purchaseId) {
        try {
            $this->conn->beginTransaction();

            // Delete purchase items first
            $stmt = $this->conn->prepare("DELETE FROM purchase_items WHERE purchase_id = :purchase_id");
            $stmt->bindValue(':purchase_id', $purchaseId);
            $stmt->execute();

            // Then delete the purchase
            $stmt = $this->conn->prepare("DELETE FROM purchases WHERE id = :purchase_id");
            $stmt->bindValue(':purchase_id', $purchaseId);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in deletePurchase: " . $e->getMessage());
            return false;
        }
    }
} 
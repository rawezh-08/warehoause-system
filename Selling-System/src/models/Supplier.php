<?php
require_once __DIR__ . '/../config/database.php';

class Supplier {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Add a new supplier to the database
     * 
     * @param array $data Supplier data
     * @return int|bool The ID of the new supplier or false on failure
     */
    public function add($data) {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['phone1'])) {
                throw new Exception('هەموو خانە پێویستەکان بنووسە');
            }
            
            // Check if supplier with same phone number already exists
            if ($this->phoneExists($data['phone1'])) {
                throw new Exception('دابینکەر بە هەمان ژمارەی مۆبایل پێشتر زیادکراوە');
            }
            
            // Begin transaction
            $this->conn->beginTransaction();
            
            // Insert supplier data
            $sql = "INSERT INTO suppliers (name, phone1, phone2, debt_on_myself, notes) 
                    VALUES (:name, :phone1, :phone2, :debt_on_myself, :notes)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':phone1' => $data['phone1'],
                ':phone2' => $data['phone2'] ?? '',
                ':debt_on_myself' => $data['debt_on_myself'] ?? 0,
                ':notes' => $data['notes'] ?? null
            ]);
            
            // Get the new supplier ID
            $supplierId = $this->conn->lastInsertId();
            
            // If we owe debt to this supplier, add a debt transaction if needed
            if (!empty($data['debt_on_myself']) && $data['debt_on_myself'] > 0) {
                // Here you would add code to record the initial debt transaction
                // Similar to how you'd handle customer debt transactions
                // This would depend on how your system tracks supplier debts
                
                // Example:
                // $this->addDebtTransaction($supplierId, $data['debt_on_myself'], 'initial');
            }
            
            // Commit the transaction
            $this->conn->commit();
            
            return $supplierId;
            
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            
            error_log("Error adding supplier: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if a supplier with the given phone number already exists
     * 
     * @param string $phone Phone number to check
     * @return bool True if exists, false otherwise
     */
    private function phoneExists($phone) {
        $sql = "SELECT COUNT(*) FROM suppliers WHERE phone1 = :phone OR phone2 = :phone";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':phone' => $phone]);
        
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Get all suppliers
     * 
     * @return array Array of suppliers
     */
    public function getAll() {
        try {
            $sql = "SELECT * FROM suppliers ORDER BY name ASC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting suppliers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a supplier by ID
     * 
     * @param int $id Supplier ID
     * @return array|bool Supplier data or false if not found
     */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM suppliers WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting supplier by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a supplier
     * 
     * @param int $id Supplier ID
     * @param array $data Supplier data
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        try {
            // Get current supplier data
            $currentSupplier = $this->getById($id);
            if (!$currentSupplier) {
                throw new Exception('دابینکەر نەدۆزرایەوە');
            }
            
            // Check if phone number already exists with another supplier
            if (($data['phone1'] != $currentSupplier['phone1']) && $this->phoneExists($data['phone1'])) {
                throw new Exception('دابینکەر بە هەمان ژمارەی مۆبایل پێشتر زیادکراوە');
            }
            
            // Begin transaction
            $this->conn->beginTransaction();
            
            // Update supplier data
            $sql = "UPDATE suppliers SET 
                    name = :name, 
                    phone1 = :phone1, 
                    phone2 = :phone2, 
                    debt_on_myself = :debt_on_myself, 
                    notes = :notes 
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':phone1' => $data['phone1'],
                ':phone2' => $data['phone2'] ?? '',
                ':debt_on_myself' => $data['debt_on_myself'] ?? 0,
                ':notes' => $data['notes'] ?? null
            ]);
            
            // Handle debt changes if needed
            if ($currentSupplier['debt_on_myself'] != $data['debt_on_myself']) {
                // Here you would add code to record a debt transaction
                // Similar to how you'd handle customer debt changes
            }
            
            // Commit the transaction
            $this->conn->commit();
            
            return $result;
            
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            
            error_log("Error updating supplier: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete a supplier
     * 
     * @param int $id Supplier ID
     * @return bool True on success, false on failure
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM suppliers WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (Exception $e) {
            error_log("Error deleting supplier: " . $e->getMessage());
            return false;
        }
    }
} 
<?php
/**
 * Customer Model
 * Handles database operations for customers
 */
class Customer {
    private $conn;

    /**
     * Constructor
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Add a new customer
     * @param array $data Customer data
     * @return int|bool The ID of the new customer or false on failure
     */
    public function add($data) {
        // Start transaction
        $this->conn->beginTransaction();

        try {
            // Validate phone number format - with better error handling
            if (empty($data['phone1'])) {
                throw new Exception('ژمارەی مۆبایل بەتاڵە');
            }
            
            if (!$this->validatePhoneFormat($data['phone1'])) {
                throw new Exception('ژمارەی مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت');
            }
            
            // Check if phone1 already exists
            if ($this->phoneExists($data['phone1'])) {
                throw new Exception('ئەم ژمارە مۆبایلە پێشتر تۆمارکراوە بۆ کڕیارێکی تر');
            }
            
            // Check if phone2 is provided and validate
            if (!empty($data['phone2'])) {
                if (!$this->validatePhoneFormat($data['phone2'])) {
                    throw new Exception('ژمارەی مۆبایلی دووەم دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت');
                }
                
                // Check if phone2 already exists
                if ($this->phoneExists($data['phone2'])) {
                    throw new Exception('ژمارەی مۆبایلی دووەم پێشتر تۆمارکراوە بۆ کڕیارێکی تر');
                }
            }
            
            // Check if guarantor phone is provided and validate
            if (!empty($data['guarantor_phone'])) {
                if (!$this->validatePhoneFormat($data['guarantor_phone'])) {
                    throw new Exception('ژمارەی مۆبایلی کەفیل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت');
                }
            }
            
            // Handle business partner relationship
            $isBusinessPartner = isset($data['is_business_partner']) ? 1 : 0;
            $supplierId = isset($data['supplier_id']) ? $data['supplier_id'] : null;
            
            // Insert customer data with business partner fields
            $stmt = $this->conn->prepare("
                INSERT INTO customers (
                    name, phone1, phone2, guarantor_name, guarantor_phone, 
                    address, debit_on_business, debt_on_customer, notes,
                    is_business_partner, supplier_id
                ) VALUES (
                    :name, :phone1, :phone2, :guarantor_name, :guarantor_phone,
                    :address, :debit_on_business, :debt_on_customer, :notes,
                    :is_business_partner, :supplier_id
                )
            ");

            // Clean and bind parameters
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':phone1', $data['phone1']);
            $stmt->bindParam(':phone2', $data['phone2']);
            $stmt->bindParam(':guarantor_name', $data['guarantor_name']);
            $stmt->bindParam(':guarantor_phone', $data['guarantor_phone']);
            $stmt->bindParam(':address', $data['address']);
            
            // Clean numerical values (remove commas)
            $debitOnBusiness = !empty($data['debit_on_business']) ? 
                (float)str_replace(',', '', $data['debit_on_business']) : 0;
            
            $debtOnCustomer = !empty($data['debt_on_customer']) ? 
                (float)str_replace(',', '', $data['debt_on_customer']) : 0;
            
            $stmt->bindParam(':debit_on_business', $debitOnBusiness);
            $stmt->bindParam(':debt_on_customer', $debtOnCustomer);
            $stmt->bindParam(':notes', $data['notes']);
            $stmt->bindParam(':is_business_partner', $isBusinessPartner, PDO::PARAM_INT);
            $stmt->bindParam(':supplier_id', $supplierId, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Database error: " . $errorInfo[2]);
            }
            
            // Get the ID of the new customer
            $customerId = $this->conn->lastInsertId();
            
            if (!$customerId) {
                throw new Exception("Failed to get the last inserted ID");
            }
            
            // Add debt transactions if initial debts exist
            if ($debitOnBusiness > 0) {
                $debtResult = $this->addDebtTransaction($customerId, $debitOnBusiness, 'business', null, 'بڕی سەرەتایی قەرز بەسەر کڕیار');
                if (!$debtResult) {
                    throw new Exception("Failed to add debt transaction for customer debt");
                }
            }
            
            if ($debtOnCustomer > 0) {
                $debtResult = $this->addDebtTransaction($customerId, $debtOnCustomer, 'customer', null, 'بڕی سەرەتایی قەرزی کڕیار لەسەر من');
                if (!$debtResult) {
                    throw new Exception("Failed to add debt transaction for debt on customer");
                }
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return $customerId;
        } catch (Exception $e) {
            // Rollback on error
            $this->conn->rollBack();
            // Log detailed error information
            error_log("Error adding customer: " . $e->getMessage() . " - Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Add a debt transaction
     * @param int $customerId Customer ID
     * @param float $amount Transaction amount
     * @param string $type Transaction type ('business' for customer debt, 'myself' for our debt)
     * @param int|null $referenceId Reference ID
     * @param string $notes Transaction notes
     * @return bool Success status
     * @throws Exception On database error
     */
    public function addDebtTransaction($customerId, $amount, $type, $referenceId = null, $notes = '') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO debt_transactions (
                    customer_id, amount, transaction_type, reference_id, notes
                ) VALUES (
                    :customer_id, :amount, :transaction_type, :reference_id, :notes
                )
            ");
            
            // Make sure the parameters are correctly typed
            $customerId = (int)$customerId;
            $amount = (float)$amount;
            
            $stmt->bindParam(':customer_id', $customerId);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':transaction_type', $type);
            $stmt->bindParam(':reference_id', $referenceId);
            $stmt->bindParam(':notes', $notes);
            
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Database error adding debt transaction: " . $errorInfo[2]);
            }
            
            // Now update the customer's debt balance based on type
            if ($type === 'business') {
                // Customer debt (debit_on_business)
                $updateStmt = $this->conn->prepare("
                    UPDATE customers SET 
                    debit_on_business = :amount 
                    WHERE id = :customer_id
                ");
                $updateStmt->bindParam(':amount', $amount);
                $updateStmt->bindParam(':customer_id', $customerId);
            } else if ($type === 'myself') {
                // Our debt (debit_on_myself)
                $updateStmt = $this->conn->prepare("
                    UPDATE customers SET 
                    debit_on_myself = :amount 
                    WHERE id = :customer_id
                ");
                $updateStmt->bindParam(':amount', $amount);
                $updateStmt->bindParam(':customer_id', $customerId);
            } else if ($type === 'customer') {
                // Customer's debt to us (debt_on_customer)
                $updateStmt = $this->conn->prepare("
                    UPDATE customers SET 
                    debt_on_customer = :amount 
                    WHERE id = :customer_id
                ");
                $updateStmt->bindParam(':amount', $amount);
                $updateStmt->bindParam(':customer_id', $customerId);
            } else {
                // Handle traditional transaction types (payment/collection)
                if ($type === 'payment') {
                    // Increase customer debt
                    $updateStmt = $this->conn->prepare("
                        UPDATE customers SET 
                        debit_on_business = debit_on_business + :amount 
                        WHERE id = :customer_id
                    ");
                } else if ($type === 'collection') {
                    // Decrease customer debt
                    $updateStmt = $this->conn->prepare("
                        UPDATE customers SET 
                        debit_on_business = debit_on_business - :amount 
                        WHERE id = :customer_id
                    ");
                }
                $updateStmt->bindParam(':amount', $amount);
                $updateStmt->bindParam(':customer_id', $customerId);
            }
            
            if (!$updateStmt->execute()) {
                $errorInfo = $updateStmt->errorInfo();
                throw new Exception("Database error updating debt balance: " . $errorInfo[2]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error adding debt transaction: " . $e->getMessage() . " - Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Get all customers
     * @return array List of customers
     */
    public function getAll() {
        $query = "SELECT * FROM customers ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get a customer by ID
     * @param int $id Customer ID
     * @return array|bool Customer data or false
     */
    public function getById($id) {
        $query = "SELECT * FROM customers WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update customer data
     * @param int $id Customer ID
     * @param array $data New customer data
     * @return bool Success status
     */
    public function update($id, $data) {
        // Start transaction
        $this->conn->beginTransaction();
        
        try {
            // Get current customer data to compare debt changes
            $currentCustomer = $this->getById($id);
            
            if (!$currentCustomer) {
                throw new Exception("Customer not found");
            }
            
            // Validate phone number format
            if (!empty($data['phone1']) && !$this->validatePhoneFormat($data['phone1'])) {
                throw new Exception('ژمارەی مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت');
            }
            
            if (!empty($data['phone2']) && !$this->validatePhoneFormat($data['phone2'])) {
                throw new Exception('ژمارەی مۆبایلی دووەم دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت');
            }
            
            if (!empty($data['guarantor_phone']) && !$this->validatePhoneFormat($data['guarantor_phone'])) {
                throw new Exception('ژمارەی مۆبایلی کەفیل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت');
            }
            
            // Check if phone numbers already exist (excluding current customer)
            if (!empty($data['phone1']) && $this->phoneExistsExcept($data['phone1'], $id)) {
                throw new Exception('ئەم ژمارە مۆبایلە پێشتر تۆمارکراوە بۆ کڕیارێکی تر');
            }
            
            if (!empty($data['phone2']) && $this->phoneExistsExcept($data['phone2'], $id)) {
                throw new Exception('ژمارەی مۆبایلی دووەم پێشتر تۆمارکراوە بۆ کڕیارێکی تر');
            }
            
            // Clean numerical values (remove commas)
            $debitOnBusiness = isset($data['debit_on_business']) ? 
                (float)str_replace(',', '', $data['debit_on_business']) : 
                $currentCustomer['debit_on_business'];
            
            $debtOnCustomer = isset($data['debt_on_customer']) ? 
                (float)str_replace(',', '', $data['debt_on_customer']) : 
                $currentCustomer['debt_on_customer'];
            
            // Update customer data
            $stmt = $this->conn->prepare("
                UPDATE customers SET 
                name = :name,
                phone1 = :phone1,
                phone2 = :phone2,
                guarantor_name = :guarantor_name,
                guarantor_phone = :guarantor_phone,
                address = :address,
                debit_on_business = :debit_on_business,
                debt_on_customer = :debt_on_customer,
                notes = :notes
                WHERE id = :id
            ");
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':phone1', $data['phone1']);
            $stmt->bindParam(':phone2', $data['phone2']);
            $stmt->bindParam(':guarantor_name', $data['guarantor_name']);
            $stmt->bindParam(':guarantor_phone', $data['guarantor_phone']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':debit_on_business', $debitOnBusiness);
            $stmt->bindParam(':debt_on_customer', $debtOnCustomer);
            $stmt->bindParam(':notes', $data['notes']);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating customer data");
            }
            
            // Check if debt values have changed
            if ($currentCustomer['debit_on_business'] != $debitOnBusiness) {
                // Delete existing debt transactions for this customer
                $deleteStmt = $this->conn->prepare("DELETE FROM debt_transactions WHERE customer_id = :customer_id AND transaction_type = 'business'");
                $deleteStmt->bindParam(':customer_id', $id);
                $deleteStmt->execute();
                
                // Add new debt transaction
                $this->addDebtTransaction(
                    $id,
                    $debitOnBusiness,
                    'business',
                    null,
                    'تازەکردنەوەی قەرزی کڕیار'
                );
            }
            
            // Check if customer debt to us has changed
            if ($currentCustomer['debt_on_customer'] != $debtOnCustomer) {
                // Delete existing debt transactions for this customer debt to us
                $deleteStmt = $this->conn->prepare("DELETE FROM debt_transactions WHERE customer_id = :customer_id AND transaction_type = 'customer'");
                $deleteStmt->bindParam(':customer_id', $id);
                $deleteStmt->execute();
                
                // Add new debt transaction
                $this->addDebtTransaction(
                    $id,
                    $debtOnCustomer,
                    'customer',
                    null,
                    'تازەکردنەوەی قەرزی کڕیار لەسەر من'
                );
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback on error
            $this->conn->rollBack();
            error_log("Error updating customer: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete a customer
     * @param int $id Customer ID
     * @return bool Success status
     */
    public function delete($id) {
        // Check if customer has transactions before deleting
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM debt_transactions WHERE customer_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            // Don't delete customers with transactions
            return false;
        }
        
        $stmt = $this->conn->prepare("DELETE FROM customers WHERE id = :id");
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Check if a phone number already exists in the database
     * @param string $phone Phone number to check
     * @return bool True if phone exists, false otherwise
     */
    public function phoneExists($phone) {
        // Clean phone number before checking
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Log the query for debugging
        error_log("Checking if phone exists: " . $phone);
        
        $query = "SELECT COUNT(*) FROM customers WHERE phone1 = :phone OR phone2 = :phone";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();
        
        $result = ($stmt->fetchColumn() > 0);
        error_log("Phone exists result: " . ($result ? "true" : "false"));
        
        return $result;
    }
    
    /**
     * Check if a phone number already exists in the database, excluding a specific customer
     * @param string $phone Phone number to check
     * @param int $excludeId Customer ID to exclude from check
     * @return bool True if phone exists, false otherwise
     */
    public function phoneExistsExcept($phone, $excludeId) {
        // Clean phone number before checking
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $excludeId = (int)$excludeId;
        
        $query = "SELECT COUNT(*) FROM customers WHERE (phone1 = :phone OR phone2 = :phone) AND id != :exclude_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':exclude_id', $excludeId);
        $stmt->execute();
        
        return ($stmt->fetchColumn() > 0);
    }
    
    /**
     * Validate phone number format (must start with 07 and be 11 digits)
     * @param string $phone Phone number to validate
     * @return bool True if valid, false otherwise
     */
    public function validatePhoneFormat($phone) {
        // First, clean the phone number from any whitespace or special characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Now validate the format (must start with 07 and have 11 digits)
        return (preg_match('/^07\d{9}$/', $phone) === 1);
    }
} 
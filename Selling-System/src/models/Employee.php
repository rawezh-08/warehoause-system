<?php
/**
 * Employee Model
 * بەڕێوەبردنی کارمەندەکان
 */
class Employee {
    // Database connection
    private $conn;
    private $table = 'employees';

    /**
     * Constructor
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all employees
     */
    public function getAllEmployees() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee by ID
     */
    public function getEmployeeById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Add new employee
     */
    public function addEmployee($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (name, phone, salary, notes) 
                  VALUES 
                  (:name, :phone, :salary, :notes)";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':phone', $data['phone'], PDO::PARAM_STR);
        $stmt->bindParam(':salary', $data['salary'], PDO::PARAM_STR);
        $stmt->bindParam(':notes', $data['notes'], PDO::PARAM_STR);
        
        // Execute query
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    /**
     * Update employee
     */
    public function updateEmployee($data) {
        $query = "UPDATE " . $this->table . " SET 
                  name = :name,
                  phone = :phone,
                  salary = :salary,
                  notes = :notes,
                  updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':phone', $data['phone'], PDO::PARAM_STR);
        $stmt->bindParam(':salary', $data['salary'], PDO::PARAM_STR);
        $stmt->bindParam(':notes', $data['notes'], PDO::PARAM_STR);
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        
        // Execute query
        return $stmt->execute();
    }

    /**
     * Delete employee
     */
    public function deleteEmployee($id) {
        // First check if employee is associated with any user account
        $query = "SELECT COUNT(*) as count FROM user_accounts WHERE employee_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['count'] > 0) {
            // Employee is associated with user accounts, cannot delete
            return false;
        }
        
        // If no associations, proceed with deletion
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Get employees with pagination
     */
    public function getEmployeesWithPagination($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Base query
        $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
        
        // Add search condition if provided
        if (!empty($search)) {
            $search = "%$search%";
            $query .= " AND (name LIKE :search OR phone LIKE :search)";
        }
        
        // Count total records
        $countQuery = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
        $countStmt = $this->conn->prepare($countQuery);
        
        if (!empty($search)) {
            $countStmt->bindParam(':search', $search, PDO::PARAM_STR);
        }
        
        $countStmt->execute();
        $totalRow = $countStmt->fetch(PDO::FETCH_ASSOC);
        $total = $totalRow['total'];
        
        // Add sorting and pagination
        $query .= " ORDER BY name ASC LIMIT :offset, :limit";
        
        // Prepare and execute the query
        $stmt = $this->conn->prepare($query);
        
        if (!empty($search)) {
            $stmt->bindParam(':search', $search, PDO::PARAM_STR);
        }
        
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        // Return results with total count
        return [
            'records' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total
        ];
    }

    /**
     * Check if phone exists
     */
    public function phoneExists($phone, $exceptId = null) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE phone = :phone";
        
        if ($exceptId !== null) {
            $query .= " AND id != :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
        
        if ($exceptId !== null) {
            $stmt->bindParam(':id', $exceptId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }
} 
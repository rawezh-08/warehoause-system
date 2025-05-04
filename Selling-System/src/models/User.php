<?php
/**
 * User Model
 * بەڕێوەبردنی هەژمارەکانی بەکارهێنەران
 */
class User {
    // Database connection
    private $conn;
    private $table = 'user_accounts';

    /**
     * Constructor
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get user by username
     */
    public function getUserByUsername($username) {
        $query = "SELECT * FROM " . $this->table . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if username exists
     */
    public function usernameExists($username) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }

    /**
     * Check if username exists for any user except the one with given ID
     */
    public function usernameExistsExcept($username, $id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE username = :username AND id != :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }

    /**
     * Get all users with pagination and search
     */
    public function getUsers($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Base query
        $query = "
            SELECT ua.*, ur.name as role_name, e.name as employee_name 
            FROM " . $this->table . " ua
            LEFT JOIN user_roles ur ON ua.role_id = ur.id
            LEFT JOIN employees e ON ua.employee_id = e.id
            WHERE 1=1
        ";
        
        // Add search condition if provided
        if (!empty($search)) {
            $search = "%$search%";
            $query .= " AND (ua.username LIKE :search OR e.name LIKE :search OR ur.name LIKE :search)";
        }
        
        // Count total records
        $countQuery = str_replace("ua.*, ur.name as role_name, e.name as employee_name", "COUNT(*) as total", $query);
        $countStmt = $this->conn->prepare($countQuery);
        
        if (!empty($search)) {
            $countStmt->bindParam(':search', $search, PDO::PARAM_STR);
        }
        
        $countStmt->execute();
        $totalRow = $countStmt->fetch(PDO::FETCH_ASSOC);
        $total = $totalRow['total'];
        
        // Add sorting and pagination
        $query .= " ORDER BY ua.id DESC LIMIT :offset, :limit";
        
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
     * Add new user
     */
    public function addUser($userData) {
        $query = "INSERT INTO " . $this->table . " 
                  (username, password_hash, employee_id, role_id, is_active, created_by) 
                  VALUES 
                  (:username, :password_hash, :employee_id, :role_id, :is_active, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':username', $userData['username'], PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $userData['password_hash'], PDO::PARAM_STR);
        $stmt->bindParam(':employee_id', $userData['employee_id'], PDO::PARAM_INT);
        $stmt->bindParam(':role_id', $userData['role_id'], PDO::PARAM_INT);
        $stmt->bindParam(':is_active', $userData['is_active'], PDO::PARAM_INT);
        $stmt->bindParam(':created_by', $userData['created_by'], PDO::PARAM_INT);
        
        // Execute query
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    /**
     * Update user
     */
    public function updateUser($userData) {
        // Start with base query
        $query = "UPDATE " . $this->table . " SET 
                  username = :username,
                  employee_id = :employee_id,
                  role_id = :role_id,
                  is_active = :is_active,
                  updated_at = NOW()";
        
        // Add password_hash to query if provided
        if (isset($userData['password_hash'])) {
            $query .= ", password_hash = :password_hash";
        }
        
        // Add WHERE clause
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':username', $userData['username'], PDO::PARAM_STR);
        $stmt->bindParam(':employee_id', $userData['employee_id'], PDO::PARAM_INT);
        $stmt->bindParam(':role_id', $userData['role_id'], PDO::PARAM_INT);
        $stmt->bindParam(':is_active', $userData['is_active'], PDO::PARAM_INT);
        $stmt->bindParam(':id', $userData['id'], PDO::PARAM_INT);
        
        // Bind password_hash if provided
        if (isset($userData['password_hash'])) {
            $stmt->bindParam(':password_hash', $userData['password_hash'], PDO::PARAM_STR);
        }
        
        // Execute query
        return $stmt->execute();
    }

    /**
     * Delete user
     */
    public function deleteUser($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Update last login time
     */
    public function updateLastLogin($id) {
        $query = "UPDATE " . $this->table . " SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
} 
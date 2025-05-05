<?php
/**
 * Permission Model
 * بەڕێوەبردنی دەسەڵاتەکان و ڕۆڵەکان
 */
class Permission {
    // Database connection
    private $conn;
    private $table_permissions = 'permissions';
    private $table_roles = 'user_roles';
    private $table_role_permissions = 'role_permissions';

    /**
     * Constructor
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all permissions
     */
    public function getAllPermissions() {
        $query = "SELECT * FROM " . $this->table_permissions . " ORDER BY `group`, name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get permissions by group
     */
    public function getPermissionsByGroup() {
        $query = "SELECT * FROM " . $this->table_permissions . " ORDER BY `group`, id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $grouped = [];
        
        foreach ($results as $permission) {
            $group = $permission['group'];
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $permission;
        }
        
        return $grouped;
    }

    /**
     * Get all roles
     */
    public function getAllRoles() {
        $query = "SELECT * FROM " . $this->table_roles . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get role by ID
     */
    public function getRoleById($id) {
        $query = "SELECT * FROM " . $this->table_roles . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get permissions for a role
     */
    public function getRolePermissions($role_id) {
        $query = "SELECT p.* FROM " . $this->table_permissions . " p 
                  JOIN " . $this->table_role_permissions . " rp ON p.id = rp.permission_id 
                  WHERE rp.role_id = :role_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get permission IDs for a role
     */
    public function getRolePermissionIds($role_id) {
        $query = "SELECT permission_id FROM " . $this->table_role_permissions . " WHERE role_id = :role_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_column($results, 'permission_id');
    }

    /**
     * Add new role
     */
    public function addRole($data) {
        $query = "INSERT INTO " . $this->table_roles . " (name, description) VALUES (:name, :description)";
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        
        // Execute query
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    /**
     * Update role
     */
    public function updateRole($data) {
        $query = "UPDATE " . $this->table_roles . " SET name = :name, description = :description WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        
        // Execute query
        return $stmt->execute();
    }

    /**
     * Delete role
     */
    public function deleteRole($id) {
        // First check if role is associated with any user
        $query = "SELECT COUNT(*) as count FROM user_accounts WHERE role_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['count'] > 0) {
            // Role is associated with users, cannot delete
            return false;
        }
        
        // Begin transaction
        $this->conn->beginTransaction();
        
        try {
            // Delete role permissions
            $query = "DELETE FROM " . $this->table_role_permissions . " WHERE role_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Delete role
            $query = "DELETE FROM " . $this->table_roles . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Assign permissions to a role
     */
    public function assignPermissions($role_id, $permission_ids) {
        // Begin transaction
        $this->conn->beginTransaction();
        
        try {
            // First, remove all existing permissions for this role
            $query = "DELETE FROM " . $this->table_role_permissions . " WHERE role_id = :role_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Then, add the new permissions
            $query = "INSERT INTO " . $this->table_role_permissions . " (role_id, permission_id) VALUES (:role_id, :permission_id)";
            $stmt = $this->conn->prepare($query);
            
            foreach ($permission_ids as $permission_id) {
                $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
                $stmt->bindParam(':permission_id', $permission_id, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Check if user has a specific permission
     */
    public function userHasPermission($user_id, $permission_code) {
        // First check if user is in the admin_accounts table
        $admin_query = "SELECT COUNT(*) as count FROM admin_accounts WHERE id = :user_id";
        $admin_stmt = $this->conn->prepare($admin_query);
        $admin_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $admin_stmt->execute();
        
        $admin_row = $admin_stmt->fetch(PDO::FETCH_ASSOC);
        
        // If user is an admin, they have all permissions
        if ($admin_row['count'] > 0) {
            return true;
        }
        
        // Otherwise, check permissions based on their role
        $query = "SELECT COUNT(*) as count 
                  FROM user_accounts ua
                  JOIN " . $this->table_role_permissions . " rp ON ua.role_id = rp.role_id
                  JOIN " . $this->table_permissions . " p ON rp.permission_id = p.id
                  WHERE ua.id = :user_id AND p.code = :permission_code";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':permission_code', $permission_code, PDO::PARAM_STR);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }
} 
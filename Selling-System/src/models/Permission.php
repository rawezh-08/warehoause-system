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
        $query = "SELECT * FROM " . $this->table_permissions . " ORDER BY `group`, name";
        $result = $this->conn->query($query);
        
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row;
        }
        
        return $permissions;
    }

    /**
     * Get all roles
     */
    public function getAllRoles() {
        $query = "SELECT * FROM " . $this->table_roles . " ORDER BY name";
        $result = $this->conn->query($query);
        
        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
        
        return $roles;
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
    public function getRolePermissions($roleId) {
        $query = "SELECT permission_id FROM " . $this->table_role_permissions . " WHERE role_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $roleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row['permission_id'];
        }
        
        return $permissions;
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
    public function addRole($name, $description, $permissions) {
        $this->conn->begin_transaction();
        
        try {
            // Insert role
            $stmt = $this->conn->prepare("INSERT INTO " . $this->table_roles . " (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            $stmt->execute();
            
            $roleId = $this->conn->insert_id;
            
            // Insert permissions
            $stmt = $this->conn->prepare("INSERT INTO " . $this->table_role_permissions . " (role_id, permission_id) VALUES (?, ?)");
            foreach ($permissions as $permissionId) {
                $stmt->bind_param("ii", $roleId, $permissionId);
                $stmt->execute();
            }
            
            $this->conn->commit();
            return $roleId;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Update role
     */
    public function updateRole($roleId, $name, $description, $permissions) {
        $this->conn->begin_transaction();
        
        try {
            // Update role
            $stmt = $this->conn->prepare("UPDATE " . $this->table_roles . " SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $roleId);
            $stmt->execute();
            
            // Delete existing permissions
            $stmt = $this->conn->prepare("DELETE FROM " . $this->table_role_permissions . " WHERE role_id = ?");
            $stmt->bind_param("i", $roleId);
            $stmt->execute();
            
            // Insert new permissions
            $stmt = $this->conn->prepare("INSERT INTO " . $this->table_role_permissions . " (role_id, permission_id) VALUES (?, ?)");
            foreach ($permissions as $permissionId) {
                $stmt->bind_param("ii", $roleId, $permissionId);
                $stmt->execute();
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Delete role
     */
    public function deleteRole($roleId) {
        $this->conn->begin_transaction();
        
        try {
            // Check if role is in use
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM user_accounts WHERE role_id = ?");
            $stmt->bind_param("i", $roleId);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                throw new Exception('ناتوانرێت ئەم ڕۆڵە بسڕدرێتەوە چونکە بەکارهێنەری هەیە');
            }
            
            // Delete permissions
            $stmt = $this->conn->prepare("DELETE FROM " . $this->table_role_permissions . " WHERE role_id = ?");
            $stmt->bind_param("i", $roleId);
            $stmt->execute();
            
            // Delete role
            $stmt = $this->conn->prepare("DELETE FROM " . $this->table_roles . " WHERE id = ?");
            $stmt->bind_param("i", $roleId);
            $stmt->execute();
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Get role
     */
    public function getRole($roleId) {
        $query = "
            SELECT r.*, GROUP_CONCAT(rp.permission_id) as permissions
            FROM " . $this->table_roles . " r
            LEFT JOIN " . $this->table_role_permissions . " rp ON r.id = rp.role_id
            WHERE r.id = ?
            GROUP BY r.id
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $roleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $role = $result->fetch_assoc();
        $role['permissions'] = $role['permissions'] ? explode(',', $role['permissions']) : [];
        
        return $role;
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
     * Check if user has a specific permission
     */
    public function userHasPermission($user_id, $permission_code) {
        $query = "SELECT COUNT(*) as count 
                  FROM user_accounts ua
                  JOIN role_permissions rp ON ua.role_id = rp.role_id
                  JOIN permissions p ON rp.permission_id = p.id
                  WHERE ua.id = :user_id AND p.code = :permission_code";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':permission_code', $permission_code, PDO::PARAM_STR);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }
} 
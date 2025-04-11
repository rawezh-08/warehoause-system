<?php

namespace App\Models;

use App\Core\Database\Connection;
use App\Core\Application;
use PDO;
use PDOException;

class Category {
    private $conn;
    
    public function __construct() {
        $this->conn = Application::container()->get(Connection::class)->getPdo();
    }
    
    public function getAll() {
        try {
            $stmt = $this->conn->query("SELECT * FROM categories ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }
    
    public function add($name, $description = '') {
        try {
            $sql = "INSERT INTO categories (name, description) VALUES (:name, :description)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':name' => $name,
                ':description' => $description
            ]);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
    public function update($id, $name, $description = '') {
        try {
            $sql = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':description' => $description
            ]);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $sql = "DELETE FROM categories WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
} 
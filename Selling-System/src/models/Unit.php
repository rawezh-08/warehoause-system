<?php

namespace App\Models;

use App\Core\Database\Connection;
use App\Core\Application;
use PDO;
use PDOException;

class Unit {
    private $conn;
    
    public function __construct() {
        $this->conn = Application::container()->get(Connection::class)->getPdo();
    }
    
    public function getAll() {
        try {
            $stmt = $this->conn->query("SELECT * FROM units ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }
    
    public function add($name, $is_piece = true, $is_box = false, $is_set = false) {
        try {
            $sql = "INSERT INTO units (name, is_piece, is_box, is_set) VALUES (:name, :is_piece, :is_box, :is_set)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':name' => $name,
                ':is_piece' => $is_piece,
                ':is_box' => $is_box,
                ':is_set' => $is_set
            ]);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
    public function update($id, $name, $is_piece = true, $is_box = false, $is_set = false) {
        try {
            $sql = "UPDATE units SET name = :name, is_piece = :is_piece, is_box = :is_box, is_set = :is_set WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':is_piece' => $is_piece,
                ':is_box' => $is_box,
                ':is_set' => $is_set
            ]);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $sql = "DELETE FROM units WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
} 
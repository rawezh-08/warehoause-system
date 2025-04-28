<?php

namespace App\Models;

use App\Core\Database\Connection;
use App\Core\Application;
use PDO;
use PDOException;
use Exception;

class Product {
    private $db;
    private $table = 'products';
    private $uploadDir = __DIR__ . '/../uploads/products/';
    
    public function __construct() {
        $this->db = Application::container()->get(Connection::class);
        // دروستکردنی فۆڵدەری وێنەکان ئەگەر بوونی نییە
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }
    
    public function checkDuplicate($code, $barcode) {
        try {
            return $this->db->fetch(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE code = ? OR barcode = ?",
                [$code, $barcode]
            );
        } catch(PDOException $e) {
            throw new Exception("Error checking duplicate: " . $e->getMessage());
        }
    }
    
    public function create($data) {
        try {
            return $this->db->insert($this->table, $data);
        } catch(PDOException $e) {
            throw new Exception("Error creating product: " . $e->getMessage());
        }
    }
    
    private function uploadImage($file) {
        // پشتڕاستکردنەوەی جۆری فایل
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('تەنها فایلی وێنە (JPG, PNG, GIF) قبوڵ دەکرێت');
        }
        
        // پشتڕاستکردنەوەی قەبارەی فایل (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('قەبارەی وێنە دەبێت کەمتر بێت لە 5 مێگابایت');
        }
        
        // دروستکردنی ناوی فایل
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $this->uploadDir . $filename;
        
        // گواستنەوەی فایل
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('هەڵە لە هەڵبژاردنی وێنە');
        }
        
        // Always return the relative path to the image
        return 'uploads/products/' . $filename;
    }
    
    public function getAll() {
        try {
            return $this->db->fetchAll(
                "SELECT p.*, c.name as category_name, u.name as unit_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN units u ON p.unit_id = u.id 
                ORDER BY p.created_at DESC"
            );
        } catch(PDOException $e) {
            throw new Exception("Error fetching products: " . $e->getMessage());
        }
    }
    
    public function getById($id) {
        try {
            return $this->db->fetch(
                "SELECT p.*, c.name as category_name, u.name as unit_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN units u ON p.unit_id = u.id 
                WHERE p.id = ?",
                [$id]
            );
        } catch(PDOException $e) {
            throw new Exception("Error fetching product: " . $e->getMessage());
        }
    }
    
    public function update($id, $data) {
        try {
            $this->db->update(
                $this->table,
                $data,
                'id = ?',
                [$id]
            );
        } catch(PDOException $e) {
            throw new Exception("Error updating product: " . $e->getMessage());
        }
    }
    
    public function delete($id) {
        try {
            $this->db->delete(
                $this->table,
                'id = ?',
                [$id]
            );
        } catch(PDOException $e) {
            throw new Exception("Error deleting product: " . $e->getMessage());
        }
    }

    public function getLatest($limit = 5) {
        try {
            return $this->db->fetchAll(
                "SELECT p.*, c.name as category_name, u.name as unit_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN units u ON p.unit_id = u.id 
                ORDER BY p.created_at DESC 
                LIMIT ?",
                [$limit]
            );
        } catch(PDOException $e) {
            throw new Exception("Error fetching latest products: " . $e->getMessage());
        }
    }

    public function search($keyword) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM {$this->table} WHERE name LIKE ? OR description LIKE ?",
                ["%{$keyword}%", "%{$keyword}%"]
            );
        } catch(PDOException $e) {
            throw new Exception("Error searching products: " . $e->getMessage());
        }
    }

    public function getByCategory($categoryId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM {$this->table} WHERE category_id = ?",
                [$categoryId]
            );
        } catch(PDOException $e) {
            throw new Exception("Error fetching products by category: " . $e->getMessage());
        }
    }

    public function updateStock($id, $quantity) {
        try {
            $this->db->query(
                "UPDATE {$this->table} SET stock = stock + ? WHERE id = ?",
                [$quantity, $id]
            );
        } catch(PDOException $e) {
            throw new Exception("Error updating stock: " . $e->getMessage());
        }
    }
} 
<?php
require_once __DIR__ . '/../config/database.php';

class Product {
    private $conn;
    private $uploadDir = __DIR__ . '/../uploads/products/';
    
    public function __construct($conn) {
        $this->conn = $conn;
        // دروستکردنی فۆڵدەری وێنەکان ئەگەر بوونی نییە
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }
    
    private function checkDuplicateCodeOrBarcode($code, $barcode) {
        try {
            $sql = "SELECT COUNT(*) as count FROM products WHERE code = :code OR barcode = :barcode";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':code' => $code,
                ':barcode' => $barcode
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch(PDOException $e) {
            throw new Exception('هەڵە لە پشتڕاستکردنەوەی کۆد و بارکۆد');
        }
    }
    
    public function add($data) {
        try {
            // پشتڕاستکردنەوەی کۆد و بارکۆدی دووبارە
            if ($this->checkDuplicateCodeOrBarcode($data['code'], $data['barcode'])) {
                throw new Exception('کۆد یان بارکۆد پێشتر تۆمار کراوە');
            }

            // هەڵبژاردنی وێنە
            $imagePath = null;
            if (isset($data['image']) && $data['image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->uploadImage($data['image']);
            }
            
            $sql = "INSERT INTO products (
                name, code, barcode, category_id, unit_id, 
                pieces_per_box, boxes_per_set, purchase_price, 
                selling_price_single, selling_price_wholesale, 
                min_quantity,  notes, image
            ) VALUES (
                :name, :code, :barcode, :category_id, :unit_id, 
                :pieces_per_box, :boxes_per_set, :purchase_price, 
                :selling_price_single, :selling_price_wholesale, 
                :min_quantity, :notes, :image
            )";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':name' => $data['name'],
                ':code' => $data['code'],
                ':barcode' => $data['barcode'],
                ':category_id' => $data['category_id'],
                ':unit_id' => $data['unit_id'],
                ':pieces_per_box' => $data['pieces_per_box'] ?? null,
                ':boxes_per_set' => $data['boxes_per_set'] ?? null,
                ':purchase_price' => $data['purchase_price'],
                ':selling_price_single' => $data['selling_price_single'],
                ':selling_price_wholesale' => $data['selling_price_wholesale'],
                ':min_quantity' => $data['min_quantity'],

                ':notes' => $data['notes'] ?? null,
                ':image' => $imagePath
            ]);
            
            return $result;
        } catch(PDOException $e) {
            error_log("Error in add product: " . $e->getMessage());
            return false;
        } catch(Exception $e) {
            error_log("Error in add product: " . $e->getMessage());
            throw $e;
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
        
        return 'uploads/products/' . $filename;
    }
    
    public function getAll() {
        try {
            $sql = "SELECT p.*, c.name as category_name, u.name as unit_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    LEFT JOIN units u ON p.unit_id = u.id 
                    ORDER BY p.created_at DESC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }
    
    public function getById($id) {
        try {
            $sql = "SELECT p.*, c.name as category_name, u.name as unit_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    LEFT JOIN units u ON p.unit_id = u.id 
                    WHERE p.id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return null;
        }
    }
    
    public function update($id, $data) {
        try {
            // هەڵبژاردنی وێنە
            $imagePath = null;
            if (isset($data['image']) && $data['image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->uploadImage($data['image']);
                
                // سڕینەوەی وێنەی کۆن
                $oldProduct = $this->getById($id);
                if ($oldProduct && $oldProduct['image']) {
                    $oldImagePath = __DIR__ . '/../' . $oldProduct['image'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
            }
            
            $sql = "UPDATE products SET 
                    name = :name,
                    code = :code,
                    barcode = :barcode,
                    category_id = :category_id,
                    unit_id = :unit_id,
                    pieces_per_box = :pieces_per_box,
                    boxes_per_set = :boxes_per_set,
                    purchase_price = :purchase_price,
                    selling_price_single = :selling_price_single,
                    selling_price_wholesale = :selling_price_wholesale,
                    current_quantity = :current_quantity,
                    min_quantity = :min_quantity,
            
                    notes = :notes,
                    image = COALESCE(:image, image)
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':code' => $data['code'],
                ':barcode' => $data['barcode'],
                ':category_id' => $data['category_id'],
                ':unit_id' => $data['unit_id'],
                ':pieces_per_box' => $data['pieces_per_box'] ?? 1,
                ':boxes_per_set' => $data['boxes_per_set'] ?? 1,
                ':purchase_price' => $data['purchase_price'],
                ':selling_price_single' => $data['selling_price_single'],
                ':selling_price_wholesale' => $data['selling_price_wholesale'],
                ':current_quantity' => $data['current_quantity'],
                ':min_quantity' => $data['min_quantity'],
            
                ':notes' => $data['notes'] ?? null,
                ':image' => $imagePath
            ]);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
    public function delete($id) {
        try {
            // سڕینەوەی وێنە
            $product = $this->getById($id);
            if ($product && $product['image']) {
                $imagePath = __DIR__ . '/../' . $product['image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $sql = "DELETE FROM products WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    public function getLatest($limit = 5) {
        try {
            $sql = "SELECT p.*, c.name as category_name, u.name as unit_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    LEFT JOIN units u ON p.unit_id = u.id 
                    ORDER BY p.created_at DESC 
                    LIMIT :limit";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // Log the error instead of displaying it
            error_log("Error fetching latest products: " . $e->getMessage());
            return [];
        }
    }
} 
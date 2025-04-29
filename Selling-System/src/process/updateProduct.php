<?php

// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['id', 'name', 'code', 'category_id', 'unit_id'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }

        $id = $_POST['id'];
        $name = $_POST['name'];
        $code = $_POST['code'];
        $barcode = $_POST['barcode'] ?? '';
    
        $notes = $_POST['notes'] ?? '';
        $category_id = $_POST['category_id'];
        $unit_id = $_POST['unit_id'];
        // Clean number inputs
        $purchasePrice = isset($_POST['purchase_price']) ? (int)str_replace(',', '', $_POST['purchase_price']) : 0;
        $sellingPriceSingle = isset($_POST['selling_price_single']) ? (int)str_replace(',', '', $_POST['selling_price_single']) : 0;
        $sellingPriceWholesale = isset($_POST['selling_price_wholesale']) ? (int)str_replace(',', '', $_POST['selling_price_wholesale']) : 0;
        $minQuantity = isset($_POST['min_quantity']) ? (int)str_replace(',', '', $_POST['min_quantity']) : 0;
        $piecesPerBox = isset($_POST['pieces_per_box']) && $_POST['pieces_per_box'] !== '' ? 
            (int)str_replace(',', '', $_POST['pieces_per_box']) : 0;
        $boxesPerSet = isset($_POST['boxes_per_set']) && $_POST['boxes_per_set'] !== '' ? 
            (int)str_replace(',', '', $_POST['boxes_per_set']) : 0;

        // Handle image upload
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(__DIR__) . '/uploads/products/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Get file extension
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            
            // Allowed file types
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception('جۆری فایلەکە ڕێگەپێدراو نییە. تەنها jpg, jpeg, png, gif ڕێگەپێدراون.');
            }
            
            // Generate unique filename
            $new_filename = uniqid('product_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Get the old image path from database
                $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $old_image = $stmt->fetchColumn();
                
                // Delete old image if exists
                if ($old_image && file_exists(dirname(__DIR__) . '/' . $old_image)) {
                    unlink(dirname(__DIR__) . '/' . $old_image);
                }
                
                // Update image path in database
                $image_path = 'uploads/products/' . $new_filename;
                $image_url = "../../api/product_image.php?filename=" . urlencode($new_filename);
            } else {
                throw new Exception('کێشەیەک هەیە لە باکردنی وێنەکە');
            }
        }

        // Update SQL query to include image if uploaded
        $sql = "UPDATE products SET 
                name = ?, 
                code = ?,
                barcode = ?,
                notes = ?,
                category_id = ?,
                unit_id = ?,
                pieces_per_box = ?,
                boxes_per_set = ?,
                purchase_price = ?,
                selling_price_single = ?,
                selling_price_wholesale = ?,
                min_quantity = ?" .
                ($image_url ? ", image = ?" : "") .
                " WHERE id = ?";

        $params = [
            $name,
            $code,
            $barcode,
            $notes,
            $category_id,
            $unit_id,
            $piecesPerBox,
            $boxesPerSet,
            $purchasePrice,
            $sellingPriceSingle,
            $sellingPriceWholesale,
            $minQuantity
        ];

        // Add image path to params if uploaded
        if ($image_url) {
            $params[] = $image_path;
        }
        
        // Add id as last parameter
        $params[] = $id;

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            echo json_encode([
                'success' => true,
                'image_url' => $image_url
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update product']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
} 
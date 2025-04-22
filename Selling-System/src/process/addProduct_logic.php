<?php
// Include authentication check


require_once '../../config/database.php';

// Get categories and units
$categoriesQuery = "SELECT * FROM categories ORDER BY name";
$unitsQuery = "SELECT * FROM units ORDER BY name";
$latestProductsQuery = "SELECT p.*, c.name as category_name, u.name as unit_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     LEFT JOIN units u ON p.unit_id = u.id 
                     ORDER BY p.created_at DESC 
                     LIMIT 5";

try {
    // Get categories
    $stmt = $conn->query($categoriesQuery);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get units
    $stmt = $conn->query($unitsQuery);
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get latest products
    $stmt = $conn->query($latestProductsQuery);
    $latestProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If there's an error, set empty arrays
    $categories = [];
    $units = [];
    $latestProducts = [];
    error_log("Error loading data: " . $e->getMessage());
}
?> 
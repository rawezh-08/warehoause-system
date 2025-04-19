<?php
// Set the content type to JSON
header('Content-Type: application/json');

// Include database connection
require_once '../config/database.php';
require_once '../includes/auth.php';

// Connect to database
$db = new Database();
$conn = $db->getConnection();

// Function to validate and sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'داواکاری نادروستە. تەنها POST ڕێگەپێدراوە.'
    ]);
    exit;
}

// Get and validate category data
$name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
$description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';

// Validate required fields
if (empty($name)) {
    echo json_encode([
        'success' => false,
        'message' => 'تکایە ناوی جۆر بنووسە.'
    ]);
    exit;
}

try {
    // Check if a category with the same name already exists
    $checkStmt = $conn->prepare("SELECT id FROM categories WHERE name = :name");
    $checkStmt->bindParam(':name', $name);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'جۆرێک بە هەمان ناو هەیە. تکایە ناوێکی تر هەڵبژێرە.'
        ]);
        exit;
    }
    
    // Insert the new category
    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->execute();
    
    // Get the ID of the newly inserted category
    $categoryId = $conn->lastInsertId();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'جۆر بە سەرکەوتوویی زیادکرا.',
        'category_id' => $categoryId
    ]);
    
} catch (PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
}
?> 
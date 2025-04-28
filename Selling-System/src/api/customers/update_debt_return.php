<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/update_debt_return_errors.log');

// Log the start of the script
error_log("Starting update_debt_return.php script");

// Set headers to prevent caching and ensure JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'تەنها داواکاری POST قبوڵ دەکرێت'
    ]);
    exit;
}

try {
    require_once '../../config/database.php';
    require_once '../../includes/auth.php';

    // Log POST data
    error_log("POST data received: " . print_r($_POST, true));

    // Validate required fields
    if (!isset($_POST['id']) || !isset($_POST['customer_id']) || !isset($_POST['amount']) || 
        !isset($_POST['return_date']) || !isset($_POST['payment_method'])) {
        throw new Exception('هەموو خانەکان پێویستن');
    }

    $id = intval($_POST['id']);
    $customer_id = intval($_POST['customer_id']);
    $amount = floatval($_POST['amount']);
    $return_date = $_POST['return_date'];
    $payment_method = $_POST['payment_method'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        error_log("Failed to establish database connection");
        throw new Exception('کێشە هەیە لە پەیوەندی بە داتابەیسەوە');
    }
    
    error_log("Database connection established successfully");

    // Begin transaction
    $conn->beginTransaction();

    try {
        // Get the original transaction
        $stmt = $conn->prepare("SELECT * FROM debt_transactions WHERE id = ? AND customer_id = ? AND transaction_type = 'collection'");
        $stmt->execute([$id, $customer_id]);
        $originalTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$originalTransaction) {
            throw new Exception('گەڕاندنەوەی قەرز نەدۆزرایەوە');
        }

        // Calculate the difference in amount
        $amountDifference = $amount - $originalTransaction['amount'];

        // Update the transaction
        $stmt = $conn->prepare("
            UPDATE debt_transactions 
            SET amount = ?,
                created_at = ?,
                notes = ?
            WHERE id = ? AND customer_id = ?
        ");

        $notesData = [
            'payment_method' => $payment_method,
            'notes' => $notes
        ];

        $stmt->execute([
            $amount,
            $return_date,
            json_encode($notesData),
            $id,
            $customer_id
        ]);

        // Update customer's debt
        $stmt = $conn->prepare("
            UPDATE customers 
            SET debit_on_business = debit_on_business - ? 
            WHERE id = ?
        ");
        $stmt->execute([$amountDifference, $customer_id]);

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'گەڕاندنەوەی قەرز بە سەرکەوتوویی دەستکاری کرا'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in update_debt_return.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
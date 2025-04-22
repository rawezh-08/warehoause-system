<?php


require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json; charset=utf-8');

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log all POST data
    error_log("All POST data: " . print_r($_POST, true));
    
    // Get form data with more detailed debugging
    $amount = isset($_POST['withdrawalAmount']) ? $_POST['withdrawalAmount'] : null;
    $withdrawalDate = isset($_POST['withdrawalDate']) ? $_POST['withdrawalDate'] : null;
    $notes = isset($_POST['withdrawalNotes']) ? $_POST['withdrawalNotes'] : '';
    
    // Debug: Log each field separately
    error_log("Amount: " . $amount);
    error_log("Date: " . $withdrawalDate);
    error_log("Notes: " . $notes);
    
    // Temporary: Use default user ID until login system is implemented
    $createdBy = 1;

    // Validate required fields with more detailed error messages
    if (!$amount) {
        echo json_encode([
            'success' => false,
            'message' => 'تکایە بڕی پارە پڕ بکەرەوە',
            'debug' => [
                'amount' => $amount,
                'all_post_data' => $_POST
            ]
        ]);
        exit;
    }

    if (!$withdrawalDate) {
        echo json_encode([
            'success' => false,
            'message' => 'تکایە بەروار پڕ بکەرەوە',
            'debug' => [
                'withdrawalDate' => $withdrawalDate,
                'all_post_data' => $_POST
            ]
        ]);
        exit;
    }

    // Clean and validate amount
    $amount = str_replace(',', '', $amount);
    if (!is_numeric($amount) || $amount <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'بڕی پارە دەبێت ژمارە بێت و گەورەتر بێت لە سفر',
            'debug' => [
                'amount' => $amount
            ]
        ]);
        exit;
    }

    try {
        // Debug: Log the SQL parameters
        error_log("SQL Parameters: " . print_r([
            'amount' => $amount,
            'withdrawalDate' => $withdrawalDate,
            'notes' => $notes,
            'createdBy' => $createdBy
        ], true));

        // Call the stored procedure
        $stmt = $conn->prepare("CALL add_expense(?, ?, ?, ?)");
        $stmt->execute([
            $amount,
            $withdrawalDate,
            $notes,
            $createdBy
        ]);

        // Get the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Debug: Log the result
        error_log("Stored procedure result: " . print_r($result, true));

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'دەرکردنی پارە بە سەرکەوتوویی زیاد کرا',
                'expenseId' => $result['result']
            ]);
        } else {
            throw new Exception("هەڵەیەک ڕوویدا لە کاتی زیادکردنی دەرکردنی پارە - نەدۆزرایەوە");
        }
    } catch (PDOException $e) {
        // Debug: Log the PDO error
        error_log("PDO Error: " . $e->getMessage());
        error_log("Error Code: " . $e->getCode());
        error_log("SQL State: " . $e->errorInfo[0]);
        
        echo json_encode([
            'success' => false,
            'message' => 'هەڵەیەک ڕوویدا لە کاتی زیادکردنی دەرکردنی پارە: ' . $e->getMessage(),
            'debug' => [
                'errorCode' => $e->getCode(),
                'sqlState' => $e->errorInfo[0],
                'errorInfo' => $e->errorInfo
            ]
        ]);
    } catch (Exception $e) {
        // Debug: Log the general error
        error_log("General Error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'debug' => [
                'error' => $e->getMessage()
            ]
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'تکایە فۆڕمەکە بە شێوەی دروست نارد بکەرەوە'
    ]);
}

$conn = null; // Close connection
?> 
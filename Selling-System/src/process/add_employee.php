<?php
require_once '../config/db_connection.php';

// Set content type to JSON
header('Content-Type: application/json; charset=utf-8');

// Get database connection
$conn = getDbConnection();

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['employeeName'] ?? '';
    $phone = $_POST['employeePhone'] ?? '';
    $salary = $_POST['employeeSalary'] ?? null;
    $notes = $_POST['employeeNotes'] ?? '';

    // Clean phone number (remove any non-digit characters)
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Validate required fields
    if (empty($name) || empty($phone)) {
        echo json_encode([
            'success' => false,
            'message' => 'تکایە ناو و ژمارەی مۆبایل داخڵ بکە'
        ]);
        exit;
    }

    // Validate phone number format
    if (!preg_match('/^07\d{9}$/', $phone)) {
        echo json_encode([
            'success' => false,
            'message' => 'ژمارە مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت'
        ]);
        exit;
    }

    try {
        // Check if phone number already exists in employees table
        $checkStmt = $conn->prepare("SELECT id FROM employees WHERE phone = ?");
        $checkStmt->execute([$phone]);
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'ژمارەی مۆبایل پێشتر تۆمار کراوە لە لیستی کارمەندەکان'
            ]);
            exit;
        }

        // Check if phone number exists in customers table
        $checkCustomerStmt = $conn->prepare("SELECT id FROM customers WHERE phone1 = ? OR phone2 = ?");
        $checkCustomerStmt->execute([$phone, $phone]);
        
        if ($checkCustomerStmt->rowCount() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'ژمارەی مۆبایل پێشتر تۆمار کراوە لە لیستی کڕیارەکان'
            ]);
            exit;
        }

        // Clean salary (remove commas and convert to decimal)
        if ($salary !== null) {
            $salary = str_replace(',', '', $salary);
            $salary = floatval($salary);
        }

        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO employees (name, phone, salary, notes) VALUES (?, ?, ?, ?)");
        
        // Execute the statement
        if ($stmt->execute([$name, $phone, $salary, $notes])) {
            echo json_encode([
                'success' => true,
                'message' => 'کارمەند بە سەرکەوتوویی زیاد کرا'
            ]);
        } else {
            throw new Exception("هەڵەیەک ڕوویدا لە کاتی زیادکردنی کارمەند");
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'هەڵەیەک ڕوویدا لە کاتی زیادکردنی کارمەند: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
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
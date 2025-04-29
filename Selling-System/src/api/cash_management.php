<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8mb4");

    switch ($method) {
        case 'POST':
            // Handle cash management transaction
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['amount']) || !isset($data['transaction_type'])) {
                throw new Exception('Amount and transaction type are required');
            }
            
            $amount = floatval($data['amount']);
            $transaction_type = $data['transaction_type'];
            $notes = $data['notes'] ?? null;
            
            // Get user ID from session or default to 1
            $created_by = getCurrentUserId() ?? 1;
            
            // Validate transaction type
            $valid_types = ['initial_balance', 'deposit', 'withdrawal', 'adjustment'];
            if (!in_array($transaction_type, $valid_types)) {
                throw new Exception('Invalid transaction type');
            }
            
            // Validate amount
            if ($amount <= 0) {
                throw new Exception('Amount must be greater than zero');
            }
            
            // For withdrawals, amount should be negative
            if ($transaction_type === 'withdrawal') {
                $amount = -$amount;
            }
            
            // Insert transaction
            $stmt = $conn->prepare("
                INSERT INTO cash_management (
                    amount, transaction_type, notes, created_by
                ) VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([$amount, $transaction_type, $notes, $created_by]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Transaction recorded successfully',
                'transaction_id' => $conn->lastInsertId()
            ]);
            break;
            
        case 'GET':
            // Get cash management transactions
            $stmt = $conn->prepare("
                SELECT 
                    id,
                    amount,
                    transaction_type,
                    notes,
                    created_at,
                    created_by
                FROM cash_management
                ORDER BY created_at DESC
            ");
            
            $stmt->execute();
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'transactions' => $transactions
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("Cash Management Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 
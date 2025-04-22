<?php

require_once __DIR__ . '/../config/database.php';

// Create a database connection
$db = new Database();
$conn = $db->getConnection();

// Fetch all employees
try {
    $stmt = $conn->prepare("SELECT id, name FROM employees ORDER BY name ASC");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $employees = [];
}

// Function to get employee payments data
function getEmployeePaymentsData($limit = 0, $offset = 0, $filters = []) {
    global $conn;
    
    // Base query to get employee payments
    $sql = "SELECT ep.id, ep.employee_id, ep.amount, ep.payment_type, ep.payment_date, ep.notes, 
                e.name as employee_name
            FROM employee_payments ep
            LEFT JOIN employees e ON ep.employee_id = e.id
            WHERE 1=1";
    
    $params = [];
    
    // Apply filters if any
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(ep.payment_date) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(ep.payment_date) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    if (!empty($filters['employee_name'])) {
        $sql .= " AND (e.name LIKE :employee_name OR e.phone LIKE :employee_name)";
        $params[':employee_name'] = '%' . $filters['employee_name'] . '%';
    }
    
    $sql .= " ORDER BY ep.payment_date DESC";
    
    // Only apply limit if it's greater than 0
    if ($limit > 0) {
        $sql .= " LIMIT :offset, :limit";
        $params[':offset'] = (int)$offset;
        $params[':limit'] = (int)$limit;
    }
    
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            if (($key == ':offset' || $key == ':limit') && $limit > 0) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

// Function to get employee payments statistics
function getEmployeePaymentsStats($filters = []) {
    global $conn;
    
    // Base query to get employee payments stats
    $sql = "SELECT 
                COUNT(*) as total_payments,
                SUM(ep.amount) as total_amount,
                SUM(CASE WHEN ep.payment_type = 'salary' THEN ep.amount ELSE 0 END) as total_salary,
                SUM(CASE WHEN ep.payment_type = 'bonus' THEN ep.amount ELSE 0 END) as total_bonus,
                SUM(CASE WHEN ep.payment_type = 'overtime' THEN ep.amount ELSE 0 END) as total_overtime
            FROM employee_payments ep
            LEFT JOIN employees e ON ep.employee_id = e.id
            WHERE 1=1";
    
    $params = [];
    
    // Apply filters if any
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(ep.payment_date) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(ep.payment_date) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    if (!empty($filters['employee_name'])) {
        $sql .= " AND e.name LIKE :employee_name";
        $params[':employee_name'] = '%' . $filters['employee_name'] . '%';
    }
    
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [
            'total_payments' => 0,
            'total_amount' => 0,
            'total_salary' => 0,
            'total_bonus' => 0,
            'total_overtime' => 0
        ];
    }
}

// Function to get withdrawal data
function getWithdrawalsData($limit = 0, $offset = 0, $filters = []) {
    global $conn;
    
    // Base query to get expenses
    $sql = "SELECT * FROM expenses WHERE 1=1";
    
    $params = [];
    
    // Apply filters if any
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(expense_date) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(expense_date) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    
    $sql .= " ORDER BY expense_date DESC";
    
    // Only apply limit if it's greater than 0
    if ($limit > 0) {
        $sql .= " LIMIT :offset, :limit";
        $params[':offset'] = (int)$offset;
        $params[':limit'] = (int)$limit;
    }
    
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            if (($key == ':offset' || $key == ':limit') && $limit > 0) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

// Default filter values
$today = date('Y-m-d');
$startOfMonth = date('Y-m-01');

// Default filters
$defaultFilters = [
    'start_date' => $startOfMonth,
    'end_date' => $today
];

// Get data with default filters - load all records (limit=0)
$employeePaymentsData = getEmployeePaymentsData(0, 0, $defaultFilters);
$employeePaymentsStats = getEmployeePaymentsStats($defaultFilters);
$withdrawalsData = getWithdrawalsData(0, 0, $defaultFilters);

// Handle AJAX filter requests
if (isset($_POST['action']) && $_POST['action'] == 'filter') {
    header('Content-Type: application/json');
    
    $filters = [
        'start_date' => $_POST['start_date'] ?? null,
        'end_date' => $_POST['end_date'] ?? null
    ];
    
    if ($_POST['type'] == 'employee_payments') {
        if (!empty($_POST['employee_name'])) {
            $filters['employee_name'] = $_POST['employee_name'];
        }
        $employeePaymentsData = getEmployeePaymentsData(0, 0, $filters);
        $employeePaymentsStats = getEmployeePaymentsStats($filters);
        echo json_encode([
            'success' => true, 
            'data' => $employeePaymentsData,
            'stats' => $employeePaymentsStats
        ]);
        exit;
    } else if ($_POST['type'] == 'withdrawals') {
        if (!empty($_POST['name'])) {
            $filters['name'] = $_POST['name'];
        }
        $withdrawalsData = getWithdrawalsData(0, 0, $filters);
        echo json_encode(['success' => true, 'data' => $withdrawalsData]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Handle delete requests
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'delete_employee_payment' && isset($_POST['id'])) {
        header('Content-Type: application/json');
        try {
            $stmt = $conn->prepare("DELETE FROM employee_payments WHERE id = :id");
            $stmt->bindParam(':id', $_POST['id']);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەدا']);
        }
        exit;
    }
    
    if ($_POST['action'] == 'delete_withdrawal' && isset($_POST['id'])) {
        header('Content-Type: application/json');
        try {
            $stmt = $conn->prepare("DELETE FROM expenses WHERE id = :id");
            $stmt->bindParam(':id', $_POST['id']);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەدا']);
        }
        exit;
    }
} 
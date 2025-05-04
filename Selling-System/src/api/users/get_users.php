<?php
// Include database connection
require_once '../../config/database.php';
require_once '../../models/User.php';
require_once '../../includes/auth.php';
require_once '../../models/Permission.php';

// Check if user has permission
$database = new Database();
$db = $database->getConnection();
$permissionModel = new Permission($db);

if (!isset($_SESSION['user_id']) || !$permissionModel->userHasPermission($_SESSION['user_id'], 'manage_accounts')) {
    exit(json_encode(['status' => 'error', 'message' => 'ڕێگەپێنەدراوە - دەسەڵاتی پێویست نییە']));
}

// Create user model
$userModel = new User($db);

// Get pagination parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get users with pagination
$result = $userModel->getUsers($page, $limit, $search);

// Calculate pagination info
$total = $result['total'] ?? 0;
$totalPages = ceil($total / $limit);
$start = ($page - 1) * $limit + 1;
$end = min($start + $limit - 1, $total);

// Prepare response
$response = [
    'status' => 'success',
    'data' => $result['records'] ?? [],
    'pagination' => [
        'total' => $total,
        'totalPages' => $totalPages,
        'page' => $page,
        'limit' => $limit,
        'start' => $start,
        'end' => min($end, $total)
    ]
];

// Output JSON response
header('Content-Type: application/json');
echo json_encode($response); 
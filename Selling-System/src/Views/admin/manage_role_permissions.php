<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page
    header("Location: ../../../index.php");
    exit();
}

// Include database connection
require_once '../../config/database.php';

// Check if the admin has permission to manage roles
$admin_id = $_SESSION['admin_id'];
$has_permission = false;

// First check if this is an admin account (admin_accounts table)
$admin_check_query = "SELECT id FROM admin_accounts WHERE id = $admin_id";
$admin_check_result = $db->query($admin_check_query);
if ($admin_check_result->num_rows > 0) {
    $has_permission = true; // Admin has all permissions
} else {
    // Check if it's a user account with appropriate permissions
    $permission_query = "CALL check_user_permission($admin_id, 'manage_roles')";
    $permission_result = $db->query($permission_query);
    if ($permission_result && $permission_result->num_rows > 0) {
        $permission_row = $permission_result->fetch_assoc();
        $has_permission = (bool)$permission_row['has_permission'];
    }
    $db->next_result(); // Clear the result
}

if (!$has_permission) {
    // Redirect to dashboard if no permission
    header("Location: dashboard.php");
    exit();
}

// Check if role_id is provided
if (!isset($_GET['role_id']) || empty($_GET['role_id'])) {
    // Redirect to roles page
    $_SESSION['error_message'] = "ڕۆڵی داواکراو نەدۆزرایەوە";
    header("Location: role_management.php");
    exit();
}

$role_id = (int)$_GET['role_id'];

// Get role information
$role_query = "SELECT * FROM user_roles WHERE id = ?";
$role_stmt = $db->prepare($role_query);
$role_stmt->bind_param("i", $role_id);
$role_stmt->execute();
$role_result = $role_stmt->get_result();

if ($role_result->num_rows === 0) {
    // Redirect to roles page if role not found
    $_SESSION['error_message'] = "ڕۆڵی داواکراو نەدۆزرایەوە";
    header("Location: role_management.php");
    exit();
}

$role = $role_result->fetch_assoc();

// Get permissions grouped by group
$permissions_query = "SELECT p.*, 
                      (SELECT COUNT(*) FROM role_permissions rp 
                       WHERE rp.role_id = ? AND rp.permission_id = p.id) AS has_permission 
                    FROM permissions p 
                    ORDER BY p.`group`, p.name";
$permissions_stmt = $db->prepare($permissions_query);
$permissions_stmt->bind_param("i", $role_id);
$permissions_stmt->execute();
$permissions_result = $permissions_stmt->get_result();

$grouped_permissions = [];
if ($permissions_result->num_rows > 0) {
    while ($row = $permissions_result->fetch_assoc()) {
        $row['has_permission'] = (bool)$row['has_permission'];
        if (!isset($grouped_permissions[$row['group']])) {
            $grouped_permissions[$row['group']] = [];
        }
        $grouped_permissions[$row['group']][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی دەسەڵاتەکانی ڕۆڵ - سیستەمی کۆگا</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .permission-group {
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
        }
        
        .permission-group-title {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #333;
        }
        
        .permission-checkbox {
            margin-bottom: 5px;
        }
        
        .permission-checkbox label {
            cursor: pointer;
        }
        
        .group-toggle {
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../../components/admin_navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <?php include '../../components/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">دەسەڵاتەکانی ڕۆڵی: <?php echo htmlspecialchars($role['name']); ?></h1>
                    <a href="role_management.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right"></i> گەڕانەوە بۆ ڕۆڵەکان
                    </a>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">دەسەڵاتەکان</h5>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" id="select-all-btn">
                                <i class="fas fa-check-square"></i> هەڵبژاردنی هەموو
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" id="deselect-all-btn">
                                <i class="fas fa-square"></i> ڕەتکردنەوەی هەموو
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="../../process/update_role_permissions.php" method="post">
                            <input type="hidden" name="role_id" value="<?php echo $role_id; ?>">
                            
                            <?php if (empty($grouped_permissions)): ?>
                                <div class="alert alert-info">هیچ دەسەڵاتێک نەدۆزرایەوە</div>
                            <?php else: ?>
                                <?php foreach ($grouped_permissions as $group => $permissions): ?>
                                    <div class="permission-group">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="permission-group-title"><?php echo htmlspecialchars($group); ?></div>
                                            <div class="group-toggle">
                                                <button type="button" class="btn btn-sm btn-outline-primary select-group" data-group="<?php echo htmlspecialchars($group); ?>">
                                                    <i class="fas fa-check-square"></i> هەڵبژاردن
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary deselect-group" data-group="<?php echo htmlspecialchars($group); ?>">
                                                    <i class="fas fa-square"></i> ڕەتکردنەوە
                                                </button>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <?php foreach ($permissions as $permission): ?>
                                                <div class="col-md-4 permission-checkbox">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="permissions[]" 
                                                               value="<?php echo $permission['id']; ?>" 
                                                               id="perm-<?php echo $permission['id']; ?>"
                                                               data-group="<?php echo htmlspecialchars($group); ?>"
                                                               <?php echo $permission['has_permission'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="perm-<?php echo $permission['id']; ?>">
                                                            <?php echo htmlspecialchars($permission['name']); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> پاشەکەوتکردنی دەسەڵاتەکان
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Select all permissions
            $('#select-all-btn').click(function(e) {
                e.preventDefault();
                $('input[name="permissions[]"]').prop('checked', true);
            });
            
            // Deselect all permissions
            $('#deselect-all-btn').click(function(e) {
                e.preventDefault();
                $('input[name="permissions[]"]').prop('checked', false);
            });
            
            // Select permissions by group
            $('.select-group').click(function(e) {
                e.preventDefault();
                const group = $(this).data('group');
                $('input[data-group="' + group + '"]').prop('checked', true);
            });
            
            // Deselect permissions by group
            $('.deselect-group').click(function(e) {
                e.preventDefault();
                const group = $(this).data('group');
                $('input[data-group="' + group + '"]').prop('checked', false);
            });
        });
    </script>
</body>
</html> 
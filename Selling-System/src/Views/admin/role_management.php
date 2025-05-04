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
require_once '../../process/db_connection.php';
require_once '../../process/auth_helper.php';

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

// Get roles from database
$roles_query = "SELECT * FROM user_roles ORDER BY id";
$roles_result = $db->query($roles_query);
$roles = [];
if ($roles_result->num_rows > 0) {
    while ($row = $roles_result->fetch_assoc()) {
        $roles[] = $row;
    }
}

// Get permissions grouped by group
$permissions_query = "SELECT * FROM permissions ORDER BY `group`, name";
$permissions_result = $db->query($permissions_query);
$grouped_permissions = [];
if ($permissions_result->num_rows > 0) {
    while ($row = $permissions_result->fetch_assoc()) {
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
    <title>بەڕێوەبردنی ڕۆڵەکان - سیستەمی کۆگا</title>
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
    </style>
</head>
<body class="bg-light">
    <?php include '../components/admin_navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <?php include '../components/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">بەڕێوەبردنی ڕۆڵەکان</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        <i class="fas fa-plus"></i> زیادکردنی ڕۆڵ
                    </button>
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

                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">لیستی ڕۆڵەکان</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>ناوی ڕۆڵ</th>
                                        <th>وەسف</th>
                                        <th>کردارەکان</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($roles)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">هیچ ڕۆڵێک نەدۆزرایەوە</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($roles as $index => $role): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($role['name']); ?></td>
                                                <td><?php echo htmlspecialchars($role['description']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-role-btn" 
                                                            data-id="<?php echo $role['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($role['name']); ?>"
                                                            data-description="<?php echo htmlspecialchars($role['description']); ?>"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editRoleModal">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($role['id'] > 5): // Prevent deletion of default roles ?>
                                                    <button class="btn btn-sm btn-outline-danger delete-role-btn"
                                                            data-id="<?php echo $role['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($role['name']); ?>"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteRoleModal">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <a href="manage_role_permissions.php?role_id=<?php echo $role['id']; ?>" 
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-key"></i> دەسەڵاتەکان
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRoleModalLabel">زیادکردنی ڕۆڵ</h5>
                    <button type="button" class="btn-close ms-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="../../process/add_role.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="role_name" class="form-label">ناوی ڕۆڵ</label>
                            <input type="text" class="form-control" id="role_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="role_description" class="form-label">وەسف</label>
                            <textarea class="form-control" id="role_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-primary">زیادکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoleModalLabel">دەستکاریکردنی ڕۆڵ</h5>
                    <button type="button" class="btn-close ms-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="../../process/update_role.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="edit_role_id" name="role_id">
                        <div class="mb-3">
                            <label for="edit_role_name" class="form-label">ناوی ڕۆڵ</label>
                            <input type="text" class="form-control" id="edit_role_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role_description" class="form-label">وەسف</label>
                            <textarea class="form-control" id="edit_role_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-primary">پاشەکەوتکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Role Modal -->
    <div class="modal fade" id="deleteRoleModal" tabindex="-1" aria-labelledby="deleteRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteRoleModalLabel">سڕینەوەی ڕۆڵ</h5>
                    <button type="button" class="btn-close ms-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>ئایا دڵنیایت لە سڕینەوەی ئەم ڕۆڵە؟</p>
                    <p class="text-danger" id="delete_role_name_display"></p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> ئاگاداری: سڕینەوەی ڕۆڵ دەبێتە هۆی سڕینەوەی دەسەڵاتەکانی ئەم ڕۆڵە.
                    </div>
                </div>
                <div class="modal-footer">
                    <form action="../../process/delete_role.php" method="post">
                        <input type="hidden" id="delete_role_id" name="role_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-danger">سڕینەوە</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Edit role modal
            $('.edit-role-btn').click(function() {
                const roleId = $(this).data('id');
                const roleName = $(this).data('name');
                const roleDescription = $(this).data('description');
                
                $('#edit_role_id').val(roleId);
                $('#edit_role_name').val(roleName);
                $('#edit_role_description').val(roleDescription);
            });
            
            // Delete role modal
            $('.delete-role-btn').click(function() {
                const roleId = $(this).data('id');
                const roleName = $(this).data('name');
                
                $('#delete_role_id').val(roleId);
                $('#delete_role_name_display').text(roleName);
            });
        });
    </script>
</body>
</html> 
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

// Get roles from database
$roles_query = "SELECT * FROM user_roles ORDER BY id";
$roles_result = $db->query($roles_query);
$roles = [];
if ($roles_result->num_rows > 0) {
    while ($row = $roles_result->fetch_assoc()) {
        $roles[] = $row;
    }
}

// Get employees from database
$employees_query = "SELECT id, name FROM employees ORDER BY name";
$employees_result = $db->query($employees_query);
$employees = [];
if ($employees_result->num_rows > 0) {
    while ($row = $employees_result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Check if the admin has permission to manage accounts
$admin_id = $_SESSION['admin_id'];
$has_permission = false;

// First check if this is an admin account (admin_accounts table)
$admin_check_query = "SELECT id FROM admin_accounts WHERE id = $admin_id";
$admin_check_result = $db->query($admin_check_query);
if ($admin_check_result->num_rows > 0) {
    $has_permission = true; // Admin has all permissions
} else {
    // Check if it's a user account with appropriate permissions
    $permission_query = "CALL check_user_permission($admin_id, 'manage_accounts')";
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

// Get existing users
$users_query = "SELECT ua.id, ua.username, ua.is_active, e.name as employee_name, ur.name as role_name 
                FROM user_accounts ua 
                LEFT JOIN employees e ON ua.employee_id = e.id 
                JOIN user_roles ur ON ua.role_id = ur.id
                ORDER BY ua.created_at DESC";
$users_result = $db->query($users_query);
$users = [];
if ($users_result->num_rows > 0) {
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی بەکارهێنەران - سیستەمی کۆگا</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        /* Custom styles for this page */
        .role-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .role-admin {
            background-color: #6c757d;
            color: white;
        }
        .role-supervisor {
            background-color: #0d6efd;
            color: white;
        }
        .role-cashier {
            background-color: #ffc107;
            color: black;
        }
        .role-salesperson {
            background-color: #198754;
            color: white;
        }
        .role-stockkeeper {
            background-color: #dc3545;
            color: white;
        }
        .status-active {
            color: #198754;
        }
        .status-inactive {
            color: #dc3545;
        }
    </style>
</head>
<body class="bg-light">



    <div class="container-fluid mt-4">
        <div class="row">
           
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">بەڕێوەبردنی بەکارهێنەران</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus"></i> زیادکردنی بەکارهێنەر
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
                        <h5 class="mb-0">لیستی بەکارهێنەران</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>ناوی بەکارهێنەر</th>
                                        <th>ناوی کارمەند</th>
                                        <th>ڕۆڵ</th>
                                        <th>دۆخ</th>
                                        <th>کردارەکان</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">هیچ بەکارهێنەرێک نەدۆزرایەوە</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $index => $user): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo $user['employee_name'] ? htmlspecialchars($user['employee_name']) : '<span class="text-muted">بەردەست نییە</span>'; ?></td>
                                                <td>
                                                    <?php 
                                                    $role_class = '';
                                                    switch($user['role_name']) {
                                                        case 'بەڕێوەبەر':
                                                            $role_class = 'role-admin';
                                                            break;
                                                        case 'سەرپەرشتیار':
                                                            $role_class = 'role-supervisor';
                                                            break;
                                                        case 'خەزنەدار':
                                                            $role_class = 'role-cashier';
                                                            break;
                                                        case 'فرۆشیار':
                                                            $role_class = 'role-salesperson';
                                                            break;
                                                        case 'کارمەندی کۆگا':
                                                            $role_class = 'role-stockkeeper';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="role-badge <?php echo $role_class; ?>">
                                                        <?php echo htmlspecialchars($user['role_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($user['is_active']): ?>
                                                        <span class="status-active"><i class="fas fa-check-circle"></i> چالاک</span>
                                                    <?php else: ?>
                                                        <span class="status-inactive"><i class="fas fa-times-circle"></i> ناچالاک</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-user-btn" 
                                                            data-id="<?php echo $user['id']; ?>"
                                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editUserModal">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-user-btn"
                                                            data-id="<?php echo $user['id']; ?>"
                                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteUserModal">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php if ($user['is_active']): ?>
                                                        <button class="btn btn-sm btn-outline-warning toggle-status-btn"
                                                                data-id="<?php echo $user['id']; ?>"
                                                                data-status="0"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#toggleStatusModal">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-success toggle-status-btn"
                                                                data-id="<?php echo $user['id']; ?>"
                                                                data-status="1"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#toggleStatusModal">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">زیادکردنی بەکارهێنەر</h5>
                    <button type="button" class="btn-close ms-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="../../process/add_user.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">ناوی بەکارهێنەر</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">وشەی نهێنی</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">دووبارەکردنەوەی وشەی نهێنی</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="employee_id" class="form-label">کارمەند</label>
                            <select class="form-select" id="employee_id" name="employee_id">
                                <option value="">هیچ کارمەندێک هەڵنەبژێردراوە</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="role_id" class="form-label">ڕۆڵ</label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
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

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">دەستکاریکردنی بەکارهێنەر</h5>
                    <button type="button" class="btn-close ms-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="../../process/update_user.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">ناوی بەکارهێنەر</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">وشەی نهێنی نوێ (بەجێی بهێڵە بۆ نەگۆڕینی)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="edit_employee_id" class="form-label">کارمەند</label>
                            <select class="form-select" id="edit_employee_id" name="employee_id">
                                <option value="">هیچ کارمەندێک هەڵنەبژێردراوە</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role_id" class="form-label">ڕۆڵ</label>
                            <select class="form-select" id="edit_role_id" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
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

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">سڕینەوەی بەکارهێنەر</h5>
                    <button type="button" class="btn-close ms-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>ئایا دڵنیایت لە سڕینەوەی ئەم بەکارهێنەرە؟</p>
                    <p class="text-danger" id="delete_username_display"></p>
                </div>
                <div class="modal-footer">
                    <form action="../../process/delete_user.php" method="post">
                        <input type="hidden" id="delete_user_id" name="user_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-danger">سڕینەوە</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toggle Status Modal -->
    <div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="toggleStatusModalLabel">گۆڕینی دۆخی بەکارهێنەر</h5>
                    <button type="button" class="btn-close ms-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="toggle_status_message">
                    <!-- Will be filled by JavaScript -->
                </div>
                <div class="modal-footer">
                    <form action="../../process/toggle_user_status.php" method="post">
                        <input type="hidden" id="toggle_user_id" name="user_id">
                        <input type="hidden" id="toggle_status" name="status">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-primary">پشتڕاستکردنەوە</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Initialize common elements -->

        
    <script>
        $(document).ready(function() {
            // Edit user modal
            $('.edit-user-btn').click(function() {
                const userId = $(this).data('id');
                const username = $(this).data('username');
                
                $('#edit_user_id').val(userId);
                $('#edit_username').val(username);
                
                // Get user details from the server
                $.ajax({
                    url: '../../process/get_user.php',
                    type: 'POST',
                    data: { user_id: userId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#edit_employee_id').val(response.user.employee_id || '');
                            $('#edit_role_id').val(response.user.role_id);
                        }
                    }
                });
            });
            
            // Delete user modal
            $('.delete-user-btn').click(function() {
                const userId = $(this).data('id');
                const username = $(this).data('username');
                
                $('#delete_user_id').val(userId);
                $('#delete_username_display').text(username);
            });
            
            // Toggle status modal
            $('.toggle-status-btn').click(function() {
                const userId = $(this).data('id');
                const status = $(this).data('status');
                
                $('#toggle_user_id').val(userId);
                $('#toggle_status').val(status);
                
                if (status == 1) {
                    $('#toggle_status_message').html('<p>ئایا دەتەوێت ئەم بەکارهێنەرە چالاک بکەیتەوە؟</p>');
                } else {
                    $('#toggle_status_message').html('<p>ئایا دەتەوێت ئەم بەکارهێنەرە ناچالاک بکەیت؟</p>');
                }
            });
            
            // Password confirmation validation
            $('form').submit(function(event) {
                const password = $('#password').val();
                const confirmPassword = $('#confirm_password').val();
                
                if (password && password !== confirmPassword) {
                    event.preventDefault();
                    alert('وشەی نهێنی و دووبارەکردنەوەکەی یەک ناگرنەوە');
                }
            });
        });
    </script>
</body>
</html> 
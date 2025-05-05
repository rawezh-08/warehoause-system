<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../models/Permission.php';

// Check if user has permission to manage roles
require_once '../../includes/check_permission.php';
checkPermission('manage_roles');

// Create a database connection
$db = new Database();
$conn = $db->getConnection();

// Create permission model
$permissionModel = new Permission($conn);

// Get all roles
$roles = $permissionModel->getAllRoles();

// Get permissions grouped by category
$permissionsByGroup = $permissionModel->getPermissionsByGroup();
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی ڕۆڵەکان - سیستەمی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- Global CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/input.css">
    <style>
        .permission-group {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        .permission-group-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #0d6efd;
        }
        .role-card {
            transition: all 0.3s ease;
        }
        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Main Content Wrapper -->
    <div id="content">
        <!-- Navbar container - will be populated by JavaScript -->
        <div id="navbar-container"></div>

        <!-- Sidebar container - will be populated by JavaScript -->
        <div id="sidebar-container"></div>

        <!-- Main content -->
        <div class="main-content p-3" id="main-content" style="margin-top: 100px;">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h3 class="page-title">بەڕێوەبردنی ڕۆڵەکان</h3>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                            <i class="fas fa-plus me-2"></i> زیادکردنی ڕۆڵی نوێ
                        </button>
                    </div>
                </div>

                <!-- Role Cards -->
                <div class="row">
                    <?php foreach($roles as $role): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card shadow-sm role-card h-100" style="border: 1px solid var(--blue-border-color); border-radius: 18px;">
                            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($role['name']); ?></h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary edit-role-btn" data-role-id="<?php echo $role['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-role-btn" data-role-id="<?php echo $role['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="text-muted"><?php echo htmlspecialchars($role['description']); ?></p>
                                <hr>
                                <p><strong>دەسەڵاتەکان:</strong></p>
                                <?php
                                $rolePermissions = $permissionModel->getRolePermissionIds($role['id']);
                                $permissionCount = count($rolePermissions);
                                ?>
                                <p class="mb-2"><?php echo $permissionCount; ?> دەسەڵات</p>
                                <button class="btn btn-sm btn-outline-info view-permissions-btn" data-role-id="<?php echo $role['id']; ?>">
                                    <i class="fas fa-eye me-1"></i> بینینی دەسەڵاتەکان
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRoleModalLabel">زیادکردنی ڕۆڵی نوێ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addRoleForm">
                        <div class="mb-3">
                            <label for="roleName" class="form-label">ناوی ڕۆڵ</label>
                            <input type="text" class="form-control" id="roleName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="roleDescription" class="form-label">وەسف</label>
                            <textarea class="form-control" id="roleDescription" name="description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">دەسەڵاتەکان</label>
                            <?php foreach($permissionsByGroup as $groupName => $permissions): ?>
                            <div class="permission-group">
                                <div class="permission-group-title"><?php echo htmlspecialchars($groupName); ?></div>
                                <div class="row">
                                    <?php foreach($permissions as $permission): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $permission['id']; ?>" id="perm_add_<?php echo $permission['id']; ?>">
                                            <label class="form-check-label" for="perm_add_<?php echo $permission['id']; ?>">
                                                <?php echo htmlspecialchars($permission['name']); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveRoleBtn">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoleModalLabel">دەستکاریکردنی ڕۆڵ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editRoleForm">
                        <input type="hidden" id="editRoleId" name="id">
                        <div class="mb-3">
                            <label for="editRoleName" class="form-label">ناوی ڕۆڵ</label>
                            <input type="text" class="form-control" id="editRoleName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editRoleDescription" class="form-label">وەسف</label>
                            <textarea class="form-control" id="editRoleDescription" name="description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">دەسەڵاتەکان</label>
                            <?php foreach($permissionsByGroup as $groupName => $permissions): ?>
                            <div class="permission-group">
                                <div class="permission-group-title"><?php echo htmlspecialchars($groupName); ?></div>
                                <div class="row">
                                    <?php foreach($permissions as $permission): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input edit-permission" type="checkbox" name="permissions[]" value="<?php echo $permission['id']; ?>" id="perm_edit_<?php echo $permission['id']; ?>">
                                            <label class="form-check-label" for="perm_edit_<?php echo $permission['id']; ?>">
                                                <?php echo htmlspecialchars($permission['name']); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="updateRoleBtn">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Permissions Modal -->
    <div class="modal fade" id="viewPermissionsModal" tabindex="-1" aria-labelledby="viewPermissionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPermissionsModalLabel">دەسەڵاتەکانی ڕۆڵ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="permissionsContainer">
                        <!-- Will be populated by JS -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Load navbar and sidebar
            $("#navbar-container").load("../../components/navbar.php");
            $("#sidebar-container").load("../../components/sidebar.php");

            // Add Role
            $('#saveRoleBtn').on('click', function() {
                const formData = new FormData(document.getElementById('addRoleForm'));
                
                // Convert FormData to JSON
                const jsonData = {};
                formData.forEach((value, key) => {
                    if (key === 'permissions[]') {
                        if (!jsonData.permissions) {
                            jsonData.permissions = [];
                        }
                        jsonData.permissions.push(value);
                    } else {
                        jsonData[key] = value;
                    }
                });

                $.ajax({
                    url: '../../api/roles/add_role.php',
                    type: 'POST',
                    data: JSON.stringify(jsonData),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو بوو!',
                                text: 'ڕۆڵی نوێ زیاد کرا',
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: response.message,
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'هەڵەیەک ڕوویدا: ' + error,
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });

            // Edit Role
            $('.edit-role-btn').on('click', function() {
                const roleId = $(this).data('role-id');
                
                // Reset form
                $('#editRoleForm')[0].reset();
                $('.edit-permission').prop('checked', false);
                
                // Fetch role data
                $.ajax({
                    url: '../../api/roles/get_role.php',
                    type: 'GET',
                    data: { id: roleId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            const role = response.data.role;
                            const permissions = response.data.permissions;
                            
                            // Fill form with role data
                            $('#editRoleId').val(role.id);
                            $('#editRoleName').val(role.name);
                            $('#editRoleDescription').val(role.description);
                            
                            // Check permissions
                            permissions.forEach(permId => {
                                $(`#perm_edit_${permId}`).prop('checked', true);
                            });
                            
                            // Show modal
                            $('#editRoleModal').modal('show');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: response.message,
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'هەڵەیەک ڕوویدا: ' + error,
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });

            // Update Role
            $('#updateRoleBtn').on('click', function() {
                const formData = new FormData(document.getElementById('editRoleForm'));
                
                // Convert FormData to JSON
                const jsonData = {
                    id: formData.get('id'),
                    name: formData.get('name'),
                    description: formData.get('description'),
                    permissions: []
                };

                // Get all checked permissions
                $('.edit-permission:checked').each(function() {
                    jsonData.permissions.push($(this).val());
                });

                $.ajax({
                    url: '../../api/roles/update_role.php',
                    type: 'POST',
                    data: JSON.stringify(jsonData),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو بوو!',
                                text: 'ڕۆڵ بە سەرکەوتوویی نوێ کرایەوە',
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: response.message,
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'هەڵەیەک ڕوویدا: ' + error,
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });

            // Delete Role
            $('.delete-role-btn').on('click', function() {
                const roleId = $(this).data('role-id');
                
                Swal.fire({
                    title: 'دڵنیای لە سڕینەوە؟',
                    text: "سڕینەوەی ڕۆڵ ناتوانرێت هەڵوەشێنرێتەوە!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'بەڵێ، بیسڕەوە!',
                    cancelButtonText: 'نەخێر'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../../api/roles/delete_role.php',
                            type: 'POST',
                            data: JSON.stringify({ id: roleId }),
                            contentType: 'application/json',
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'سەرکەوتوو بوو!',
                                        text: 'ڕۆڵ بە سەرکەوتوویی سڕایەوە',
                                        confirmButtonText: 'باشە'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'هەڵە',
                                        text: response.message,
                                        confirmButtonText: 'باشە'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە',
                                    text: 'هەڵەیەک ڕوویدا: ' + error,
                                    confirmButtonText: 'باشە'
                                });
                            }
                        });
                    }
                });
            });

            // View Role Permissions
            $('.view-permissions-btn').on('click', function() {
                const roleId = $(this).data('role-id');
                
                $.ajax({
                    url: '../../api/roles/get_role_permissions.php',
                    type: 'GET',
                    data: { id: roleId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            const permissions = response.data.permissions;
                            const roleName = response.data.role_name;
                            let html = `<h6>دەسەڵاتەکانی ڕۆڵی <strong>${roleName}</strong>:</h6>`;
                            
                            // Group permissions
                            const groupedPermissions = {};
                            permissions.forEach(perm => {
                                if (!groupedPermissions[perm.group]) {
                                    groupedPermissions[perm.group] = [];
                                }
                                groupedPermissions[perm.group].push(perm);
                            });
                            
                            // Build HTML
                            for (const group in groupedPermissions) {
                                html += `<div class="permission-group">
                                    <div class="permission-group-title">${group}</div>
                                    <div class="row">`;
                                
                                groupedPermissions[group].forEach(perm => {
                                    html += `<div class="col-md-4 mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span>${perm.name}</span>
                                        </div>
                                    </div>`;
                                });
                                
                                html += `</div></div>`;
                            }
                            
                            // Update modal content
                            $('#permissionsContainer').html(html);
                            $('#viewPermissionsModal').modal('show');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: response.message,
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'هەڵەیەک ڕوویدا: ' + error,
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html> 
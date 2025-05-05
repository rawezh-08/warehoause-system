<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../models/Permission.php';

// Create a database connection
$db = new Database();
$conn = $db->getConnection();

// Create models
$permissionModel = new Permission($conn);

// Get all roles and permissions
$roles = $permissionModel->getAllRoles();
$permissions = $permissionModel->getAllPermissions();

// Group permissions by their group
$groupedPermissions = [];
foreach ($permissions as $permission) {
    $group = $permission['group'];
    if (!isset($groupedPermissions[$group])) {
        $groupedPermissions[$group] = [];
    }
    $groupedPermissions[$group][] = $permission;
}
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
</head>
<body>
    <!-- Main Content Wrapper -->
    <div id="content">
        <!-- Navbar container -->
        <div id="navbar-container"></div>

        <!-- Sidebar container -->
        <div id="sidebar-container"></div>

        <!-- Main content -->
        <div class="main-content p-3" id="main-content" style="margin-top: 100px;">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h3 class="page-title">بەڕێوەبردنی ڕۆڵەکان</h3>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                            <i class="fas fa-plus me-2"></i> زیادکردنی ڕۆڵ
                        </button>
                    </div>
                </div>

                <!-- Roles List -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm card-qiuck-style">
                            <div class="card-header bg-transparent">
                                <h5 class="card-title mb-0">لیستی ڕۆڵەکان</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>ناوی ڕۆڵ</th>
                                                <th>وەسف</th>
                                                <th>کردارەکان</th>
                                            </tr>
                                        </thead>
                                        <tbody id="rolesTableBody">
                                            <?php foreach ($roles as $index => $role): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($role['name']); ?></td>
                                                <td><?php echo htmlspecialchars($role['description']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary me-2" 
                                                            onclick="editRole(<?php echo $role['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteRole(<?php echo $role['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">زیادکردنی ڕۆڵی نوێ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addRoleForm">
                        <div class="mb-3">
                            <label for="roleName" class="form-label">ناوی ڕۆڵ</label>
                            <input type="text" class="form-control" id="roleName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="roleDescription" class="form-label">وەسف</label>
                            <textarea class="form-control" id="roleDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">دەسەڵاتەکان</label>
                            <?php foreach ($groupedPermissions as $group => $groupPermissions): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <div class="form-check">
                                        <input class="form-check-input group-checkbox" type="checkbox" 
                                               data-group="<?php echo htmlspecialchars($group); ?>">
                                        <label class="form-check-label">
                                            <?php echo htmlspecialchars($group); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($groupPermissions as $permission): ?>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input permission-checkbox" 
                                                       type="checkbox" 
                                                       name="permissions[]" 
                                                       value="<?php echo $permission['id']; ?>"
                                                       data-group="<?php echo htmlspecialchars($group); ?>">
                                                <label class="form-check-label">
                                                    <?php echo htmlspecialchars($permission['name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" onclick="saveRole()">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">دەستکاریکردنی ڕۆڵ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editRoleForm">
                        <input type="hidden" id="editRoleId" name="role_id">
                        <div class="mb-3">
                            <label for="editRoleName" class="form-label">ناوی ڕۆڵ</label>
                            <input type="text" class="form-control" id="editRoleName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editRoleDescription" class="form-label">وەسف</label>
                            <textarea class="form-control" id="editRoleDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">دەسەڵاتەکان</label>
                            <?php foreach ($groupedPermissions as $group => $groupPermissions): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <div class="form-check">
                                        <input class="form-check-input edit-group-checkbox" type="checkbox" 
                                               data-group="<?php echo htmlspecialchars($group); ?>">
                                        <label class="form-check-label">
                                            <?php echo htmlspecialchars($group); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($groupPermissions as $permission): ?>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input edit-permission-checkbox" 
                                                       type="checkbox" 
                                                       name="permissions[]" 
                                                       value="<?php echo $permission['id']; ?>"
                                                       data-group="<?php echo htmlspecialchars($group); ?>">
                                                <label class="form-check-label">
                                                    <?php echo htmlspecialchars($permission['name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" onclick="updateRole()">نوێکردنەوە</button>
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

            // Handle group checkboxes
            $('.group-checkbox').change(function() {
                const group = $(this).data('group');
                const isChecked = $(this).prop('checked');
                $(`.permission-checkbox[data-group="${group}"]`).prop('checked', isChecked);
            });

            $('.edit-group-checkbox').change(function() {
                const group = $(this).data('group');
                const isChecked = $(this).prop('checked');
                $(`.edit-permission-checkbox[data-group="${group}"]`).prop('checked', isChecked);
            });

            // Update group checkbox when individual permissions are changed
            $('.permission-checkbox').change(function() {
                updateGroupCheckbox($(this));
            });

            $('.edit-permission-checkbox').change(function() {
                updateEditGroupCheckbox($(this));
            });
        });

        function updateGroupCheckbox(permissionCheckbox) {
            const group = permissionCheckbox.data('group');
            const totalPermissions = $(`.permission-checkbox[data-group="${group}"]`).length;
            const checkedPermissions = $(`.permission-checkbox[data-group="${group}"]:checked`).length;
            $(`.group-checkbox[data-group="${group}"]`).prop('checked', totalPermissions === checkedPermissions);
        }

        function updateEditGroupCheckbox(permissionCheckbox) {
            const group = permissionCheckbox.data('group');
            const totalPermissions = $(`.edit-permission-checkbox[data-group="${group}"]`).length;
            const checkedPermissions = $(`.edit-permission-checkbox[data-group="${group}"]:checked`).length;
            $(`.edit-group-checkbox[data-group="${group}"]`).prop('checked', totalPermissions === checkedPermissions);
        }

        function saveRole() {
            const formData = new FormData(document.getElementById('addRoleForm'));
            
            $.ajax({
                url: '../../api/roles/add_role.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو بوو!',
                            text: 'ڕۆڵەکە بە سەرکەوتوویی زیادکرا.',
                            confirmButtonText: 'باشە'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
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

        function editRole(roleId) {
            // Fetch role data
            $.ajax({
                url: '../../api/roles/get_role.php',
                type: 'GET',
                data: { role_id: roleId },
                success: function(response) {
                    if (response.status === 'success') {
                        const role = response.data;
                        
                        // Fill form with role data
                        $('#editRoleId').val(role.id);
                        $('#editRoleName').val(role.name);
                        $('#editRoleDescription').val(role.description);
                        
                        // Reset all checkboxes
                        $('.edit-permission-checkbox').prop('checked', false);
                        
                        // Check permissions
                        role.permissions.forEach(permissionId => {
                            $(`.edit-permission-checkbox[value="${permissionId}"]`).prop('checked', true);
                        });
                        
                        // Update group checkboxes
                        $('.edit-permission-checkbox').each(function() {
                            updateEditGroupCheckbox($(this));
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
        }

        function updateRole() {
            const formData = new FormData(document.getElementById('editRoleForm'));
            
            $.ajax({
                url: '../../api/roles/update_role.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو بوو!',
                            text: 'ڕۆڵەکە بە سەرکەوتوویی نوێکرایەوە.',
                            confirmButtonText: 'باشە'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
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

        function deleteRole(roleId) {
            Swal.fire({
                title: 'دڵنیای لە سڕینەوە؟',
                text: "ناتوانیت ئەم کردارە پاشگەز بکەیتەوە!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'بەڵێ، بیسڕەوە!',
                cancelButtonText: 'نەخێر'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '../../api/roles/delete_role.php',
                        type: 'POST',
                        data: { role_id: roleId },
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'سڕایەوە!',
                                    text: 'ڕۆڵەکە بە سەرکەوتوویی سڕایەوە.',
                                    confirmButtonText: 'باشە'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload();
                                    }
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
        }
    </script>
</body>
</html> 
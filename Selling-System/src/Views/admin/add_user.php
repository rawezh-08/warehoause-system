<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../models/Permission.php';
require_once '../../models/Employee.php';
require_once '../../models/User.php';


// Check if the user has permission to add users
require_once '../../includes/check_permission.php';
checkPermission('manage_users');

// Create a database connection
$db = new Database();
$conn = $db->getConnection();

// Create models
$employeeModel = new Employee($conn);
$permissionModel = new Permission($conn);
$userModel = new User($conn);

// Get all employees
$employees = $employeeModel->getAllEmployees();

// Get all roles
$roles = $permissionModel->getAllRoles();
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زیادکردنی بەکارهێنەر - سیستەمی کۆگا</title>
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
        <!-- Navbar container - will be populated by JavaScript -->
        <div id="navbar-container"></div>

        <!-- Sidebar container - will be populated by JavaScript -->
        <div id="sidebar-container"></div>

        <!-- Main content -->
        <div class="main-content p-3" id="main-content" style="margin-top: 100px;">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h3 class="page-title">زیادکردنی بەکارهێنەر</h3>
                        <a href="manage_users.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-right me-2"></i> گەڕانەوە بۆ لیست
                        </a>
                    </div>
                </div>

                <!-- Add User Form -->
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card shadow-sm" style="border: 1px solid var(--blue-border-color); border-radius: 18px;">
                            <div class="card-header bg-transparent">
                                <h5 class="card-title mb-0">زانیاری بەکارهێنەر</h5>
                            </div>
                            <div class="card-body">
                                <form id="userForm" class="needs-validation" novalidate>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="username" class="form-label">ناوی بەکارهێنەر</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                <input type="text" class="form-control" id="username" name="username" required>
                                                <div class="invalid-feedback">
                                                    تکایە ناوی بەکارهێنەر داخل بکە
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="employee_id" class="form-label">کارمەند</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                                                <select class="form-select" id="employee_id" name="employee_id">
                                                    <option value="">هیچ کارمەندێک هەڵنەبژێردراوە</option>
                                                    <?php foreach ($employees as $employee): ?>
                                                    <option value="<?php echo $employee['id']; ?>"><?php echo $employee['name']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="password" class="form-label">وشەی نهێنی</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <div class="invalid-feedback">
                                                    تکایە وشەی نهێنی داخل بکە
                                                </div>
                                            </div>
                                            <small class="text-muted">وشەی نهێنی دەبێت لانیکەم 8 پیت بێت</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="confirm_password" class="form-label">دووبارەکردنەوەی وشەی نهێنی</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <div class="invalid-feedback">
                                                    تکایە دووبارە وشەی نهێنی داخل بکە
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="role_id" class="form-label">ڕۆڵی بەکارهێنەر</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                                                <select class="form-select" id="role_id" name="role_id" required>
                                                    <option value="">هەڵبژاردنی ڕۆڵ</option>
                                                    <?php foreach ($roles as $role): ?>
                                                    <option value="<?php echo $role['id']; ?>"><?php echo $role['name']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <a href="manage_roles.php" class="btn btn-outline-secondary" title="بەڕێوەبردنی ڕۆڵەکان">
                                                    <i class="fas fa-cog"></i>
                                                </a>
                                                <div class="invalid-feedback">
                                                    تکایە ڕۆڵی بەکارهێنەر هەڵبژێرە
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="is_active" class="form-label">بارودۆخ</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                                <select class="form-select" id="is_active" name="is_active">
                                                    <option value="1" selected>چالاک</option>
                                                    <option value="0">ناچالاک</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 mt-4 text-end">
                                            <button type="button" class="btn btn-outline-secondary me-2" id="resetForm">
                                                <i class="fas fa-redo me-2"></i> ڕیسێت
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i> پاشەکەوتکردن
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
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
            $("#navbar-container").load("../../components/navbar.php", function(response, status, xhr) {
                if (status == "error") {
                    console.error("Error loading navbar:", xhr.status, xhr.statusText);
                    $("#navbar-container").html('<div class="alert alert-danger">Error loading navbar. Please refresh the page.</div>');
                }
            });

            $("#sidebar-container").load("../../components/sidebar.php", function(response, status, xhr) {
                if (status == "error") {
                    console.error("Error loading sidebar:", xhr.status, xhr.statusText);
                    $("#sidebar-container").html('<div class="alert alert-danger">Error loading sidebar. Please refresh the page.</div>');
                }
            });

            // Toggle password visibility
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });

            // Toggle confirm password visibility
            $('#toggleConfirmPassword').on('click', function() {
                const passwordField = $('#confirm_password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });

            // Reset form button
            $('#resetForm').on('click', function() {
                $('#userForm')[0].reset();
                $('#userForm').removeClass('was-validated');
            });

            // Form validation and submission
            $('#userForm').on('submit', function(e) {
                e.preventDefault();

                // Validate form
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    $(this).addClass('was-validated');
                    return;
                }

                // Check if passwords match
                const password = $('#password').val();
                const confirm_password = $('#confirm_password').val();

                if (password !== confirm_password) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'وشەی نهێنی و دووبارەکردنەوەی وشەی نهێنی یەک ناگرنەوە!'
                    });
                    return;
                }

                // Prepare form data
                const formData = {
                    username: $('#username').val(),
                    password: password,
                    employee_id: $('#employee_id').val() || null,
                    role_id: $('#role_id').val(),
                    is_active: $('#is_active').val()
                };

                // Submit form via AJAX
                $.ajax({
                    url: '../../api/users/add_user.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو بوو!',
                                text: 'بەکارهێنەر بە سەرکەوتوویی زیادکرا.',
                                confirmButtonText: 'باشە'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'manage_users.php';
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'هەڵەیەک ڕوویدا لە کاتی زیادکردن: ' + error
                        });
                    }
                });
            });
        });
    </script>
</body>
</html> 
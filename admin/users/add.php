<?php
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/check_permission.php';

// Check if user has permission to manage accounts
checkPermission('manage_accounts');

// Get all employees
$employees_query = "SELECT id, name FROM employees ORDER BY name";
$employees_result = mysqli_query($conn, $employees_query);

// Get all roles
$roles_query = "SELECT id, name FROM user_roles ORDER BY name";
$roles_result = mysqli_query($conn, $roles_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $employee_id = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;
    $role_id = (int)$_POST['role_id'];
    $created_by = $_SESSION['user_id'];

    // Check if username already exists
    $check_query = "SELECT id FROM user_accounts WHERE username = '$username'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "ئەم ناوی بەکارهێنەرە پێشتر بەکارهاتووە";
    } else {
        // Call the stored procedure to add user
        $query = "CALL add_user('$username', '$password', " . 
                 ($employee_id ? $employee_id : "NULL") . ", $role_id, $created_by)";
        
        if (mysqli_query($conn, $query)) {
            header("Location: index.php?success=1");
            exit;
        } else {
            $error = "هەڵەیەک ڕوویدا لە زیادکردنی بەکارهێنەر";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زیادکردنی بەکارهێنەر</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">زیادکردنی بەکارهێنەر</h1>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">ناوی بەکارهێنەر</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <div class="invalid-feedback">
                                    تکایە ناوی بەکارهێنەر بنووسە
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">وشەی نهێنی</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">
                                    تکایە وشەی نهێنی بنووسە
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="employee_id" class="form-label">کارمەند</label>
                                <select class="form-select" id="employee_id" name="employee_id">
                                    <option value="">-- هەڵبژاردنی کارمەند --</option>
                                    <?php while($employee = mysqli_fetch_assoc($employees_result)): ?>
                                        <option value="<?php echo $employee['id']; ?>">
                                            <?php echo htmlspecialchars($employee['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="role_id" class="form-label">ڕۆڵ</label>
                                <select class="form-select" id="role_id" name="role_id" required>
                                    <option value="">-- هەڵبژاردنی ڕۆڵ --</option>
                                    <?php while($role = mysqli_fetch_assoc($roles_result)): ?>
                                        <option value="<?php echo $role['id']; ?>">
                                            <?php echo htmlspecialchars($role['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">
                                    تکایە ڕۆڵ هەڵبژێرە
                                </div>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">زیادکردن</button>
                                <a href="index.php" class="btn btn-secondary">گەڕانەوە</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/fontawesome.min.js"></script>
    <script>
    // Form validation
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html> 
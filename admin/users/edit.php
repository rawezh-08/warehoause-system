<?php
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/check_permission.php';

// Check if user has permission to manage accounts
checkPermission('manage_accounts');

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get user data
$user_query = "SELECT * FROM user_accounts WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    header("Location: index.php");
    exit;
}

// Get all employees
$employees_query = "SELECT id, name FROM employees ORDER BY name";
$employees_result = mysqli_query($conn, $employees_query);

// Get all roles
$roles_query = "SELECT id, name FROM user_roles ORDER BY name";
$roles_result = mysqli_query($conn, $roles_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $employee_id = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;
    $role_id = (int)$_POST['role_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Check if username already exists (excluding current user)
    $check_query = "SELECT id FROM user_accounts WHERE username = '$username' AND id != $user_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "ئەم ناوی بەکارهێنەرە پێشتر بەکارهاتووە";
    } else {
        // Update user
        $query = "UPDATE user_accounts SET 
                  username = '$username',
                  employee_id = " . ($employee_id ? $employee_id : "NULL") . ",
                  role_id = $role_id,
                  is_active = $is_active";
        
        // Update password if provided
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $query .= ", password_hash = '$password'";
        }
        
        $query .= " WHERE id = $user_id";
        
        if (mysqli_query($conn, $query)) {
            header("Location: index.php?success=2");
            exit;
        } else {
            $error = "هەڵەیەک ڕوویدا لە دەستکاریکردنی بەکارهێنەر";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دەستکاریکردنی بەکارهێنەر</title>
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
                    <h1 class="h2">دەستکاریکردنی بەکارهێنەر</h1>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">ناوی بەکارهێنەر</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                <div class="invalid-feedback">
                                    تکایە ناوی بەکارهێنەر بنووسە
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">وشەی نهێنی (بەجێی هێشتن بۆ گۆڕانکاری نەکردن)</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>

                            <div class="mb-3">
                                <label for="employee_id" class="form-label">کارمەند</label>
                                <select class="form-select" id="employee_id" name="employee_id">
                                    <option value="">-- هەڵبژاردنی کارمەند --</option>
                                    <?php while($employee = mysqli_fetch_assoc($employees_result)): ?>
                                        <option value="<?php echo $employee['id']; ?>" 
                                                <?php echo $employee['id'] == $user['employee_id'] ? 'selected' : ''; ?>>
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
                                        <option value="<?php echo $role['id']; ?>"
                                                <?php echo $role['id'] == $user['role_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($role['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">
                                    تکایە ڕۆڵ هەڵبژێرە
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                                       <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">چالاک</label>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">پاشەکەوتکردن</button>
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
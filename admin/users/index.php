<?php
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/check_permission.php';

// Check if user has permission to manage accounts
checkPermission('manage_accounts');

// Get all users with their roles and employee information
$query = "SELECT ua.*, ur.name as role_name, e.name as employee_name 
          FROM user_accounts ua 
          LEFT JOIN user_roles ur ON ua.role_id = ur.id 
          LEFT JOIN employees e ON ua.employee_id = e.id 
          ORDER BY ua.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی بەکارهێنەران</title>
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
                    <h1 class="h2">بەڕێوەبردنی بەکارهێنەران</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> زیادکردنی بەکارهێنەر
                        </a>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php
                        switch ($_GET['success']) {
                            case 1:
                                echo "بەکارهێنەر بە سەرکەوتوویی زیادکرا";
                                break;
                            case 2:
                                echo "بەکارهێنەر بە سەرکەوتوویی دەستکاریکرا";
                                break;
                            case 3:
                                echo "بەکارهێنەر بە سەرکەوتوویی سڕایەوە";
                                break;
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <?php
                        switch ($_GET['error']) {
                            case 1:
                                echo "هەڵەیەک ڕوویدا لە کاتی سڕینەوەی بەکارهێنەر";
                                break;
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ناوی بەکارهێنەر</th>
                                <th>ناوی کارمەند</th>
                                <th>ڕۆڵ</th>
                                <th>دۆخی چالاکی</th>
                                <th>دوایین چوونەژوورەوە</th>
                                <th>کردارەکان</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['employee_name'] ?? '--'); ?></td>
                                    <td><?php echo htmlspecialchars($row['role_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $row['is_active'] ? 'چالاک' : 'ناچالاک'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['last_login'] ? date('Y-m-d H:i', strtotime($row['last_login'])) : '--'; ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/fontawesome.min.js"></script>
    <script>
    function confirmDelete(userId) {
        if (confirm('دڵنیای لە سڕینەوەی ئەم بەکارهێنەرە؟')) {
            window.location.href = 'delete.php?id=' + userId;
        }
    }
    </script>
</body>
</html> 
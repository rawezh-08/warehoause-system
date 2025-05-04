<!-- Add this to existing sidebar -->
<!-- Check if admin has permission to manage accounts -->
<?php
$can_manage_accounts = false;
$can_manage_roles = false;

// Check if it's an admin or has permission
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    $can_manage_accounts = true;
    $can_manage_roles = true;
} else {
    // Check for specific permissions
    require_once __DIR__ . '/../process/auth_helper.php';
    
    if (isset($_SESSION['admin_id']) && isset($db)) {
        $can_manage_accounts = checkUserPermission($db, $_SESSION['admin_id'], 'manage_accounts');
        $can_manage_roles = checkUserPermission($db, $_SESSION['admin_id'], 'manage_roles');
    }
}
?>

<!-- Add these menu items to your existing sidebar -->
<?php if ($can_manage_accounts || $can_manage_roles): ?>
<li class="nav-item">
    <a class="nav-link" data-bs-toggle="collapse" href="#userManagementSubmenu" role="button" aria-expanded="false" aria-controls="userManagementSubmenu">
        <i class="fas fa-users-cog"></i> بەڕێوەبردنی بەکارهێنەران
    </a>
    <div class="collapse" id="userManagementSubmenu">
        <ul class="nav flex-column ms-3">
            <?php if ($can_manage_accounts): ?>
            <li class="nav-item">
                <a class="nav-link" href="../Views/admin/user_management.php">
                    <i class="fas fa-user-plus"></i> بەکارهێنەران
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($can_manage_roles): ?>
            <li class="nav-item">
                <a class="nav-link" href="../Views/admin/role_management.php">
                    <i class="fas fa-user-tag"></i> ڕۆڵەکان و دەسەڵاتەکان
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</li>
<?php endif; ?> 
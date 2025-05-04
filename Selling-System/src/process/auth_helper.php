<?php
/**
 * ئەم فایلە فەنکشنەکانی پشکنینی دەسەڵات بەکارهێنەر لەخۆ دەگرێت
 */

/**
 * پشکنینی دەسەڵاتی بەکارهێنەر
 *
 * @param mysqli $db - Database connection
 * @param int $user_id - User ID
 * @param string $permission_code - Permission code to check
 * @return bool - True if user has permission, false otherwise
 */
function checkUserPermission($db, $user_id, $permission_code) {
    // Check if user is an admin (admin_accounts table)
    $admin_check_query = "SELECT id FROM admin_accounts WHERE id = ?";
    $admin_check_stmt = $db->prepare($admin_check_query);
    $admin_check_stmt->bind_param("i", $user_id);
    $admin_check_stmt->execute();
    $admin_check_result = $admin_check_stmt->get_result();
    
    if ($admin_check_result->num_rows > 0) {
        return true; // Admin has all permissions
    }
    
    // Check if it's a user account with the specific permission
    $query = "CALL check_user_permission(?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bind_param("is", $user_id, $permission_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (bool)$row['has_permission'];
    }
    
    $db->next_result(); // Clear the result
    return false;
}

/**
 * پشکنینی دەسەڵاتی بەکارهێنەر و ڕێدایرێکت کردن بۆ داشبۆرد ئەگەر مۆڵەتی نەبوو
 *
 * @param mysqli $db - Database connection
 * @param int $user_id - User ID
 * @param string $permission_code - Permission code to check
 * @return bool - True if user has permission, false otherwise
 */
function requirePermission($db, $user_id, $permission_code) {
    $has_permission = checkUserPermission($db, $user_id, $permission_code);
    
    if (!$has_permission) {
        $_SESSION['error_message'] = "مۆڵەتی پێویستت نییە بۆ ئەم کردارە";
        header("Location: ../Views/admin/dashboard.php");
        exit();
    }
    
    return true;
}

/**
 * پشکنینی دەسەڵاتی بەکارهێنەر بۆ چەند دەسەڵاتێک
 * پێویستە بەلایەنی کەمەوە یەکێک لە دەسەڵاتەکانی هەبێت
 * 
 * @param mysqli $db - Database connection
 * @param int $user_id - User ID
 * @param array $permission_codes - Array of permission codes to check
 * @return bool - True if user has any of the permissions, false otherwise
 */
function checkAnyPermission($db, $user_id, $permission_codes) {
    // Check if user is an admin (admin_accounts table)
    $admin_check_query = "SELECT id FROM admin_accounts WHERE id = ?";
    $admin_check_stmt = $db->prepare($admin_check_query);
    $admin_check_stmt->bind_param("i", $user_id);
    $admin_check_stmt->execute();
    $admin_check_result = $admin_check_stmt->get_result();
    
    if ($admin_check_result->num_rows > 0) {
        return true; // Admin has all permissions
    }
    
    foreach ($permission_codes as $code) {
        $query = "CALL check_user_permission(?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("is", $user_id, $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $has_permission = (bool)$row['has_permission'];
            $db->next_result(); // Clear the result
            
            if ($has_permission) {
                return true;
            }
        }
        
        $db->next_result(); // Clear the result
    }
    
    return false;
}

/**
 * گۆڕینی جۆری دەسەڵات بۆ ناوی کوردی بۆ نیشاندان لە سیستەم
 *
 * @param string $permission_code - Permission code
 * @return string - Kurdish name for permission
 */
function getPermissionName($permission_code) {
    $permission_names = [
        'view_employees' => 'بینینی کارمەندەکان',
        'add_employee' => 'زیادکردنی کارمەند',
        'edit_employee' => 'دەستکاریکردنی کارمەند',
        'delete_employee' => 'سڕینەوەی کارمەند',
        
        'manage_accounts' => 'بەڕێوەبردنی هەژمارەکان',
        'manage_roles' => 'بەڕێوەبردنی دەسەڵاتەکان',
        
        'view_purchases' => 'بینینی کڕینەکان',
        'add_purchase' => 'زیادکردنی کڕین',
        'edit_purchase' => 'دەستکاریکردنی کڕین',
        'delete_purchase' => 'سڕینەوەی کڕین',
        
        'view_sales' => 'بینینی فرۆشتنەکان',
        'add_sale' => 'زیادکردنی فرۆشتن',
        'edit_sale' => 'دەستکاریکردنی فرۆشتن',
        'delete_sale' => 'سڕینەوەی فرۆشتن',
        
        'view_products' => 'بینینی کاڵاکان',
        'add_product' => 'زیادکردنی کاڵا',
        'edit_product' => 'دەستکاریکردنی کاڵا',
        'delete_product' => 'سڕینەوەی کاڵا',
        
        'view_customers' => 'بینینی موشتەرییەکان',
        'add_customer' => 'زیادکردنی موشتەری',
        'edit_customer' => 'دەستکاریکردنی موشتەری',
        'delete_customer' => 'سڕینەوەی موشتەری',
        
        'view_suppliers' => 'بینینی دابینکەران',
        'add_supplier' => 'زیادکردنی دابینکەر',
        'edit_supplier' => 'دەستکاریکردنی دابینکەر',
        'delete_supplier' => 'سڕینەوەی دابینکەر',
        
        'view_reports' => 'بینینی ڕاپۆرتەکان',
        'view_financial_reports' => 'بینینی ڕاپۆرتی دارایی',
        'view_inventory_reports' => 'بینینی ڕاپۆرتی کۆگا'
    ];
    
    return isset($permission_names[$permission_code]) ? $permission_names[$permission_code] : $permission_code;
} 
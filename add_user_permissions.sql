-- دروستکردنی جەدوەلی ڕۆڵەکان
CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- دروستکردنی جەدوەلی هەژماری بەکارهێنەران
CREATE TABLE `user_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `employee_id` (`employee_id`),
  KEY `role_id` (`role_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `user_accounts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_accounts_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`id`),
  CONSTRAINT `user_accounts_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admin_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- دروستکردنی جەدوەلی دەسەڵاتەکان
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `group` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- دروستکردنی جەدوەلی پەیوەندی نێوان ڕۆڵەکان و دەسەڵاتەکان
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permission` (`role_id`, `permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- زیادکردنی ڕۆڵە سەرەکییەکان
INSERT INTO `user_roles` (`name`, `description`) VALUES
('بەڕێوەبەر', 'دەسەڵاتی تەواو بۆ هەموو بەشەکانی سیستەم'),
('سەرپەرشتیار', 'دەسەڵاتی بەڕێوەبردنی بەشەکانی کڕین و فرۆشتن'),
('خەزنەدار', 'دەسەڵاتی بەڕێوەبردنی پارە و ئەژمێریاری'),
('فرۆشیار', 'دەسەڵاتی فرۆشتن و موشتەرییەکان'),
('کارمەندی کۆگا', 'دەسەڵاتی بەڕێوەبردنی کۆگا و کاڵاکان');

-- زیادکردنی دەسەڵاتەکان
INSERT INTO `permissions` (`name`, `code`, `description`, `group`) VALUES
-- زیادکردنی کارمەندەکان
('بینینی کارمەندەکان', 'view_employees', 'توانای بینینی لیستی کارمەندەکان', 'کارمەندەکان'),
('زیادکردنی کارمەند', 'add_employee', 'توانای زیادکردنی کارمەندی نوێ', 'کارمەندەکان'),
('دەستکاریکردنی کارمەند', 'edit_employee', 'توانای دەستکاریکردنی زانیاری کارمەندەکان', 'کارمەندەکان'),
('سڕینەوەی کارمەند', 'delete_employee', 'توانای سڕینەوەی کارمەندەکان', 'کارمەندەکان'),

-- کارگێڕی سیستەم
('بەڕێوەبردنی هەژمارەکان', 'manage_accounts', 'توانای زیادکردن و دەستکاریکردنی هەژماری بەکارهێنەران', 'کارگێڕی'),
('بەڕێوەبردنی دەسەڵاتەکان', 'manage_roles', 'توانای دەستکاریکردنی ڕۆڵەکان و دەسەڵاتەکان', 'کارگێڕی'),

-- کڕین
('بینینی کڕینەکان', 'view_purchases', 'توانای بینینی پسولەکانی کڕین', 'کڕین'),
('زیادکردنی کڕین', 'add_purchase', 'توانای زیادکردنی پسولەی کڕین', 'کڕین'),
('دەستکاریکردنی کڕین', 'edit_purchase', 'توانای دەستکاریکردنی پسولەکانی کڕین', 'کڕین'),
('سڕینەوەی کڕین', 'delete_purchase', 'توانای سڕینەوەی پسولەکانی کڕین', 'کڕین'),

-- فرۆشتن
('بینینی فرۆشتنەکان', 'view_sales', 'توانای بینینی پسولەکانی فرۆشتن', 'فرۆشتن'),
('زیادکردنی فرۆشتن', 'add_sale', 'توانای زیادکردنی پسولەی فرۆشتن', 'فرۆشتن'),
('دەستکاریکردنی فرۆشتن', 'edit_sale', 'توانای دەستکاریکردنی پسولەکانی فرۆشتن', 'فرۆشتن'),
('سڕینەوەی فرۆشتن', 'delete_sale', 'توانای سڕینەوەی پسولەکانی فرۆشتن', 'فرۆشتن'),

-- کاڵاکان
('بینینی کاڵاکان', 'view_products', 'توانای بینینی لیستی کاڵاکان', 'کاڵاکان'),
('زیادکردنی کاڵا', 'add_product', 'توانای زیادکردنی کاڵای نوێ', 'کاڵاکان'),
('دەستکاریکردنی کاڵا', 'edit_product', 'توانای دەستکاریکردنی زانیاری کاڵاکان', 'کاڵاکان'),
('سڕینەوەی کاڵا', 'delete_product', 'توانای سڕینەوەی کاڵاکان', 'کاڵاکان'),

-- موشتەرییەکان
('بینینی موشتەرییەکان', 'view_customers', 'توانای بینینی لیستی موشتەرییەکان', 'موشتەرییەکان'),
('زیادکردنی موشتەری', 'add_customer', 'توانای زیادکردنی موشتەری نوێ', 'موشتەرییەکان'),
('دەستکاریکردنی موشتەری', 'edit_customer', 'توانای دەستکاریکردنی زانیاری موشتەرییەکان', 'موشتەرییەکان'),
('سڕینەوەی موشتەری', 'delete_customer', 'توانای سڕینەوەی موشتەرییەکان', 'موشتەرییەکان'),

-- دابینکەران
('بینینی دابینکەران', 'view_suppliers', 'توانای بینینی لیستی دابینکەران', 'دابینکەران'),
('زیادکردنی دابینکەر', 'add_supplier', 'توانای زیادکردنی دابینکەری نوێ', 'دابینکەران'),
('دەستکاریکردنی دابینکەر', 'edit_supplier', 'توانای دەستکاریکردنی زانیاری دابینکەران', 'دابینکەران'),
('سڕینەوەی دابینکەر', 'delete_supplier', 'توانای سڕینەوەی دابینکەران', 'دابینکەران'),

-- ڕاپۆرتەکان
('بینینی ڕاپۆرتەکان', 'view_reports', 'توانای بینینی ڕاپۆرتەکانی سیستەم', 'ڕاپۆرتەکان'),
('بینینی ڕاپۆرتی دارایی', 'view_financial_reports', 'توانای بینینی ڕاپۆرتە داراییەکان', 'ڕاپۆرتەکان'),
('بینینی ڕاپۆرتی کۆگا', 'view_inventory_reports', 'توانای بینینی ڕاپۆرتەکانی کۆگا', 'ڕاپۆرتەکان');

-- دانانی دەسەڵات بۆ ڕۆڵی بەڕێوەبەر (هەموو دەسەڵاتەکان)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- دانانی دەسەڵات بۆ ڕۆڵی سەرپەرشتیار (لەبەر ئەوەی زۆرن، تەنها هەندێکیان دادەنێین)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions` 
WHERE `code` IN (
    'view_employees', 'view_purchases', 'add_purchase', 'edit_purchase', 
    'view_sales', 'add_sale', 'edit_sale', 'view_products', 'add_product', 
    'edit_product', 'view_customers', 'add_customer', 'edit_customer',
    'view_suppliers', 'add_supplier', 'edit_supplier', 'view_reports', 
    'view_inventory_reports'
);

-- Stored procedure بۆ زیادکردنی بەکارهێنەر
DELIMITER $$
CREATE PROCEDURE `add_user` (
    IN `p_username` VARCHAR(50),
    IN `p_password` VARCHAR(255),
    IN `p_employee_id` INT,
    IN `p_role_id` INT,
    IN `p_created_by` INT
)
BEGIN
    DECLARE user_exists INT;
    
    -- بپشکنە ئایا بەکارهێنەر بوونی هەیە
    SELECT COUNT(*) INTO user_exists FROM user_accounts WHERE username = p_username;
    
    IF user_exists > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ئەم ناوی بەکارهێنەرە پێشتر بەکارهاتووە';
    END IF;
    
    -- زیادکردنی بەکارهێنەر
    INSERT INTO user_accounts (
        username, 
        password_hash, 
        employee_id, 
        role_id, 
        created_by
    ) VALUES (
        p_username, 
        p_password, 
        p_employee_id, 
        p_role_id, 
        p_created_by
    );
    
    SELECT LAST_INSERT_ID() AS user_id;
END$$
DELIMITER ;

-- Stored procedure بۆ پشکنینی دەسەڵاتی بەکارهێنەر
DELIMITER $$
CREATE PROCEDURE `check_user_permission` (
    IN `p_user_id` INT,
    IN `p_permission_code` VARCHAR(100)
)
BEGIN
    DECLARE has_permission BOOLEAN;
    
    -- بپشکنە ئایا بەکارهێنەر دەسەڵاتی هەیە
    SELECT EXISTS (
        SELECT 1 FROM user_accounts ua
        JOIN role_permissions rp ON ua.role_id = rp.role_id
        JOIN permissions p ON rp.permission_id = p.id
        WHERE ua.id = p_user_id AND p.code = p_permission_code
    ) INTO has_permission;
    
    -- گەڕاندنەوەی ئەنجام
    SELECT has_permission AS 'has_permission';
END$$
DELIMITER ; 
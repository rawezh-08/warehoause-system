-- Update permissions to match sidebar menu structure
UPDATE permissions SET code = 'add_product' WHERE code = 'manage_products';
UPDATE permissions SET code = 'view_products' WHERE code = 'view_products';
UPDATE permissions SET code = 'add_staff' WHERE code = 'manage_employees';
UPDATE permissions SET code = 'view_staff' WHERE code = 'view_employees';
UPDATE permissions SET code = 'manage_users' WHERE code = 'manage_users';
UPDATE permissions SET code = 'manage_roles' WHERE code = 'manage_roles';
UPDATE permissions SET code = 'add_user' WHERE code = 'manage_users';
UPDATE permissions SET code = 'add_receipt' WHERE code = 'manage_receipts';
UPDATE permissions SET code = 'view_sales' WHERE code = 'view_sales';
UPDATE permissions SET code = 'view_purchases' WHERE code = 'view_purchases';
UPDATE permissions SET code = 'add_expense' WHERE code = 'manage_expenses';
UPDATE permissions SET code = 'view_expenses' WHERE code = 'view_expenses';
UPDATE permissions SET code = 'manage_cash' WHERE code = 'manage_cash';
UPDATE permissions SET code = 'manage_customers' WHERE code = 'manage_customers';
UPDATE permissions SET code = 'manage_suppliers' WHERE code = 'manage_suppliers';
UPDATE permissions SET code = 'view_business_partners' WHERE code = 'view_business_partners';
UPDATE permissions SET code = 'view_reports' WHERE code = 'view_reports';

-- Add any missing permissions
INSERT INTO permissions (code, name, `group`) VALUES
('add_product', 'زیادکردنی کاڵا', 'Products'),
('view_products', 'بینینی کاڵاکان', 'Products'),
('add_staff', 'زیادکردنی هەژمار', 'Staff'),
('view_staff', 'بینینی هەژمارەکان', 'Staff'),
('manage_users', 'بەڕێوەبردنی بەکارهێنەران', 'Users'),
('manage_roles', 'بەڕێوەبردنی ڕۆڵەکان', 'Users'),
('add_user', 'زیادکردنی بەکارهێنەر', 'Users'),
('add_receipt', 'زیادکردنی پسوڵە', 'Sales'),
('view_sales', 'پسووڵەکانی فرۆشتن', 'Sales'),
('view_purchases', 'پسووڵەکانی کڕین', 'Sales'),
('add_expense', 'زیادکردنی خەرجی', 'Expenses'),
('view_expenses', 'لیستی خەرجییەکان', 'Expenses'),
('manage_cash', 'دەخیلە', 'Expenses'),
('manage_customers', 'کڕیارەکان', 'Debts'),
('manage_suppliers', 'دابینکەرەکان', 'Debts'),
('view_business_partners', 'کڕیار و دابینکەر', 'Debts'),
('view_reports', 'ڕاپۆرتەکان', 'Reports')
ON DUPLICATE KEY UPDATE name = VALUES(name), `group` = VALUES(`group`); 
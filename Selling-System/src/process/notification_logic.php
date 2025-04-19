<?php
// Notification Logic for ASHKAN system
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Connect to database
$db = new Database();
$conn = $db->getConnection();

// Get today's date
$today = date('Y-m-d');

// Get low stock items (inventory quantity less than minimum quantity)
$lowStockQuery = "SELECT p.id as product_id, p.name, i.quantity, p.min_quantity 
                 FROM inventory i 
                 JOIN products p ON i.product_id = p.id 
                 WHERE i.quantity <= p.min_quantity AND i.quantity > 0
                 ORDER BY (p.min_quantity - i.quantity) DESC";
$lowStockStmt = $conn->prepare($lowStockQuery);
$lowStockStmt->execute();
$lowStockItems = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);

// Get out of stock items (quantity = 0)
$outOfStockQuery = "SELECT p.id, p.name, p.code
                   FROM products p
                   JOIN inventory i ON p.id = i.product_id 
                   WHERE i.quantity = 0
                   ORDER BY p.name";
$outOfStockStmt = $conn->prepare($outOfStockQuery);
$outOfStockStmt->execute();
$outOfStockItems = $outOfStockStmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's sales count and total
$salesQuery = "SELECT COUNT(DISTINCT s.id) as count, 
               SUM(si.total_price) as total 
               FROM sales s 
               JOIN sale_items si ON s.id = si.sale_id
               WHERE DATE(s.date) = :today";
$salesStmt = $conn->prepare($salesQuery);
$salesStmt->bindParam(':today', $today);
$salesStmt->execute();
$todaySales = $salesStmt->fetch(PDO::FETCH_ASSOC);

// Get today's sales details
$todaySalesDetailsQuery = "SELECT s.id, s.date, TIME(s.date) as time, s.invoice_number, 
                          c.name as customer_name, s.payment_type, 
                          SUM(si.total_price) as total
                          FROM sales s 
                          LEFT JOIN customers c ON s.customer_id = c.id
                          JOIN sale_items si ON s.id = si.sale_id
                          WHERE DATE(s.date) = :today
                          GROUP BY s.id
                          ORDER BY s.date DESC";
$todaySalesDetailsStmt = $conn->prepare($todaySalesDetailsQuery);
$todaySalesDetailsStmt->bindParam(':today', $today);
$todaySalesDetailsStmt->execute();
$todaySalesDetails = $todaySalesDetailsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's purchases count
$purchasesQuery = "SELECT COUNT(DISTINCT p.id) as count, 
                  SUM(pi.total_price) as total 
                  FROM purchases p 
                  JOIN purchase_items pi ON p.id = pi.purchase_id
                  WHERE DATE(p.date) = :today";
$purchasesStmt = $conn->prepare($purchasesQuery);
$purchasesStmt->bindParam(':today', $today);
$purchasesStmt->execute();
$todayPurchases = $purchasesStmt->fetch(PDO::FETCH_ASSOC);

// Get today's purchases details
$todayPurchasesDetailsQuery = "SELECT p.id, p.date, TIME(p.date) as time, p.invoice_number, 
                             s.name as supplier_name, p.payment_type, 
                             SUM(pi.total_price) as total
                             FROM purchases p 
                             LEFT JOIN suppliers s ON p.supplier_id = s.id
                             JOIN purchase_items pi ON p.id = pi.purchase_id
                             WHERE DATE(p.date) = :today
                             GROUP BY p.id
                             ORDER BY p.date DESC";
$todayPurchasesDetailsStmt = $conn->prepare($todayPurchasesDetailsQuery);
$todayPurchasesDetailsStmt->bindParam(':today', $today);
$todayPurchasesDetailsStmt->execute();
$todayPurchasesDetails = $todayPurchasesDetailsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total notifications
$totalNotifications = count($lowStockItems) + (count($outOfStockItems) > 0 ? 1 : 0) + 
                     ($todaySales['count'] > 0 ? 1 : 0) + ($todayPurchases['count'] > 0 ? 1 : 0);
?> 
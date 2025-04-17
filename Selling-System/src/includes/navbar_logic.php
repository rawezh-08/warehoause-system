<?php
// Navbar Component for ASHKAN system
require_once '../includes/auth.php';
require_once '../config/database.php';

// Connect to database
$db = new Database();
$conn = $db->getConnection();

// Get today's date
$today = date('Y-m-d');

// Get low stock items (inventory quantity less than minimum quantity)
$lowStockQuery = "SELECT p.name, i.quantity, p.min_quantity 
                 FROM inventory i 
                 JOIN products p ON i.product_id = p.id 
                 WHERE i.quantity <= p.min_quantity AND i.quantity > 0";
$lowStockStmt = $conn->prepare($lowStockQuery);
$lowStockStmt->execute();
$lowStockItems = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);
$lowStockCount = count($lowStockItems);

// Get out of stock items (quantity = 0)
$outOfStockQuery = "SELECT COUNT(*) as count 
                   FROM products 
                   WHERE current_quantity = 0";
$outOfStockStmt = $conn->prepare($outOfStockQuery);
$outOfStockStmt->execute();
$outOfStockCount = $outOfStockStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get today's sales count
$salesQuery = "SELECT COUNT(DISTINCT s.id) as count, 
               SUM(si.total_price) as total 
               FROM sales s 
               JOIN sale_items si ON s.id = si.sale_id
               WHERE DATE(s.date) = :today";
$salesStmt = $conn->prepare($salesQuery);
$salesStmt->bindParam(':today', $today);
$salesStmt->execute();
$todaySales = $salesStmt->fetch(PDO::FETCH_ASSOC);

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

// Calculate total notifications
$totalNotifications = $lowStockCount + ($outOfStockCount > 0 ? 1 : 0) + ($todaySales['count'] > 0 ? 1 : 0) + ($todayPurchases['count'] > 0 ? 1 : 0);
?> 
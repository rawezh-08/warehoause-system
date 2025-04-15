<?php
// Database connection
require_once '../../config/database.php';

try {
    // Get current date and previous period date
    $currentDate = date('Y-m-d');
    $previousPeriodStart = date('Y-m-d', strtotime('-30 days'));

    // Fetch current period cash sales
    $currentCashSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                             FROM sales s 
                             JOIN sale_items si ON s.id = si.sale_id 
                             WHERE s.payment_type = 'cash'
                             AND s.date >= :previousPeriodStart";
    $stmt = $conn->prepare($currentCashSalesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $cashSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch previous period cash sales
    $previousCashSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                              FROM sales s 
                              JOIN sale_items si ON s.id = si.sale_id 
                              WHERE s.payment_type = 'cash'
                              AND s.date < :previousPeriodStart
                              AND s.date >= DATE_SUB(:previousPeriodStart, INTERVAL 30 DAY)";
    $stmt = $conn->prepare($previousCashSalesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $previousCashSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate cash sales percentage change
    $cashSalesPercentage = $previousCashSales > 0 ?
        round((($cashSales - $previousCashSales) / $previousCashSales) * 100, 1) : 0;

    // Fetch current period credit sales
    $currentCreditSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                               FROM sales s 
                               JOIN sale_items si ON s.id = si.sale_id 
                               WHERE s.payment_type = 'credit'
                               AND s.date >= :previousPeriodStart";
    $stmt = $conn->prepare($currentCreditSalesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $creditSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch previous period credit sales
    $previousCreditSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                                FROM sales s 
                                JOIN sale_items si ON s.id = si.sale_id 
                                WHERE s.payment_type = 'credit'
                                AND s.date < :previousPeriodStart
                                AND s.date >= DATE_SUB(:previousPeriodStart, INTERVAL 30 DAY)";
    $stmt = $conn->prepare($previousCreditSalesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $previousCreditSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate credit sales percentage change
    $creditSalesPercentage = $previousCreditSales > 0 ?
        round((($creditSales - $previousCreditSales) / $previousCreditSales) * 100, 1) : 0;

    // Fetch current period cash purchases
    $currentCashPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                                 FROM purchases p 
                                 JOIN purchase_items pi ON p.id = pi.purchase_id 
                                 WHERE p.payment_type = 'cash'
                                 AND p.date >= :previousPeriodStart";
    $stmt = $conn->prepare($currentCashPurchasesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $cashPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch previous period cash purchases
    $previousCashPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                                  FROM purchases p 
                                  JOIN purchase_items pi ON p.id = pi.purchase_id 
                                  WHERE p.payment_type = 'cash'
                                  AND p.date < :previousPeriodStart
                                  AND p.date >= DATE_SUB(:previousPeriodStart, INTERVAL 30 DAY)";
    $stmt = $conn->prepare($previousCashPurchasesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $previousCashPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate cash purchases percentage change
    $cashPurchasesPercentage = $previousCashPurchases > 0 ?
        round((($cashPurchases - $previousCashPurchases) / $previousCashPurchases) * 100, 1) : 0;

    // Fetch current period credit purchases
    $currentCreditPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                                   FROM purchases p 
                                   JOIN purchase_items pi ON p.id = pi.purchase_id 
                                   WHERE p.payment_type = 'credit'
                                   AND p.date >= :previousPeriodStart";
    $stmt = $conn->prepare($currentCreditPurchasesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $creditPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch previous period credit purchases
    $previousCreditPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                                    FROM purchases p 
                                    JOIN purchase_items pi ON p.id = pi.purchase_id 
                                    WHERE p.payment_type = 'credit'
                                    AND p.date < :previousPeriodStart
                                    AND p.date >= DATE_SUB(:previousPeriodStart, INTERVAL 30 DAY)";
    $stmt = $conn->prepare($previousCreditPurchasesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $previousCreditPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate credit purchases percentage change
    $creditPurchasesPercentage = $previousCreditPurchases > 0 ?
        round((($creditPurchases - $previousCreditPurchases) / $previousCreditPurchases) * 100, 1) : 0;

    // Fetch low stock products with unit information
    $lowStockQuery = "SELECT p.*, c.name as category_name, u.name as unit_name 
                      FROM products p 
                      JOIN categories c ON p.category_id = c.id 
                      JOIN units u ON p.unit_id = u.id
                      WHERE p.current_quantity <= p.min_quantity 
                      ORDER BY p.current_quantity ASC 
                      LIMIT 4";
    $lowStockProducts = $conn->query($lowStockQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Fetch top selling products with unit information
    $topSellingQuery = "SELECT p.*, c.name as category_name, u.name as unit_name,
                        SUM(si.quantity) as total_sold,
                        si.unit_type
                        FROM products p 
                        JOIN categories c ON p.category_id = c.id 
                        JOIN units u ON p.unit_id = u.id
                        JOIN sale_items si ON p.id = si.product_id 
                        GROUP BY p.id, si.unit_type
                        ORDER BY total_sold DESC 
                        LIMIT 4";
    $topSellingProducts = $conn->query($topSellingQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Calculate warehouse occupancy percentage
    $totalProductsQuery = "SELECT COUNT(*) as total FROM products";
    $totalProducts = $conn->query($totalProductsQuery)->fetch(PDO::FETCH_ASSOC)['total'];

    $lowStockCountQuery = "SELECT COUNT(*) as count FROM products WHERE current_quantity <= min_quantity";
    $lowStockCount = $conn->query($lowStockCountQuery)->fetch(PDO::FETCH_ASSOC)['count'];

    $warehouseOccupancy = $totalProducts > 0 ? round(($lowStockCount / $totalProducts) * 100) : 0;

    // Calculate total sales and purchases for chart
    $totalSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                        FROM sales s 
                        JOIN sale_items si ON s.id = si.sale_id";
    $totalSales = $conn->query($totalSalesQuery)->fetch(PDO::FETCH_ASSOC)['total'];

    $totalPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                           FROM purchases p 
                           JOIN purchase_items pi ON p.id = pi.purchase_id";
    $totalPurchases = $conn->query($totalPurchasesQuery)->fetch(PDO::FETCH_ASSOC)['total'];

    $totalTransactions = $totalSales + $totalPurchases;
    $salesPercentage = $totalTransactions > 0 ? round(($totalSales / $totalTransactions) * 100) : 0;
    $purchasesPercentage = $totalTransactions > 0 ? round(($totalPurchases / $totalTransactions) * 100) : 0;

    // Calculate monthly sales and purchases data for chart
    $monthlySalesQuery = "SELECT 
            DATE_FORMAT(s.date, '%Y-%m') as month,
            SUM(si.total_price) as total
        FROM 
            sales s
        JOIN 
            sale_items si ON s.id = si.sale_id
        WHERE 
            s.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY 
            DATE_FORMAT(s.date, '%Y-%m')
        ORDER BY 
            month ASC";

    $monthlyPurchasesQuery = "SELECT 
            DATE_FORMAT(p.date, '%Y-%m') as month,
            SUM(pi.total_price) as total
        FROM 
            purchases p
        JOIN 
            purchase_items pi ON p.id = pi.purchase_id
        WHERE 
            p.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY 
            DATE_FORMAT(p.date, '%Y-%m')
        ORDER BY 
            month ASC";

    $salesData = $conn->query($monthlySalesQuery)->fetchAll(PDO::FETCH_ASSOC);
    $purchasesData = $conn->query($monthlyPurchasesQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Format the data for the chart
    $chartMonths = [];
    $chartSales = [];
    $chartPurchases = [];

    // Get the last 6 months
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $months[] = date('Y-m', strtotime("-$i month"));
    }

    // Initialize arrays with zeros
    foreach ($months as $month) {
        $chartMonths[] = date('M Y', strtotime($month));
        $chartSales[$month] = 0;
        $chartPurchases[$month] = 0;
    }

    // Fill in actual data where it exists
    foreach ($salesData as $data) {
        if (isset($chartSales[$data['month']])) {
            $chartSales[$data['month']] = (float)$data['total'];
        }
    }

    foreach ($purchasesData as $data) {
        if (isset($chartPurchases[$data['month']])) {
            $chartPurchases[$data['month']] = (float)$data['total'];
        }
    }

    // Convert to simple arrays for the chart
    $salesValues = array_values($chartSales);
    $purchasesValues = array_values($chartPurchases);

    // JSON encode for JavaScript use with proper error handling
    $chartMonthsJson = json_encode(array_values($chartMonths)) ?: '[]';
    $salesValuesJson = json_encode($salesValues) ?: '[]';
    $purchasesValuesJson = json_encode($purchasesValues) ?: '[]';
    
    // Check if JSON encoding failed and provide fallback values
    if (json_last_error() !== JSON_ERROR_NONE) {
        $chartMonthsJson = '[]';
        $salesValuesJson = '[]';
        $purchasesValuesJson = '[]';
        $jsonError = json_last_error_msg();
        error_log("JSON encoding error: " . $jsonError);
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?> 
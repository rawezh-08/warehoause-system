<?php
// Include authentication check
require_once '../../includes/auth.php';

// Database connection
require_once '../../config/database.php';

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Get the period from POST request
    $filterPeriod = isset($_POST['period']) ? $_POST['period'] : 'today';

    // Define date ranges based on filter period
    $currentDate = date('Y-m-d');
    
    switch($filterPeriod) {
        case 'today':
            $startDate = $currentDate;
            $previousPeriodStart = date('Y-m-d', strtotime('-1 day'));
            $previousPeriodEnd = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'month':
            $startDate = date('Y-m-01'); // First day of current month
            $previousPeriodStart = date('Y-m-d', strtotime('first day of previous month'));
            $previousPeriodEnd = date('Y-m-d', strtotime('last day of previous month'));
            break;
        case 'year':
            $startDate = date('Y-01-01'); // First day of current year
            $previousPeriodStart = date('Y-m-d', strtotime('first day of january last year'));
            $previousPeriodEnd = date('Y-m-d', strtotime('last day of december last year'));
            break;
        default:
            $startDate = $currentDate;
            $previousPeriodStart = date('Y-m-d', strtotime('-1 day'));
            $previousPeriodEnd = date('Y-m-d', strtotime('-1 day'));
    }

    // Fetch current period cash sales
    $currentCashSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                             FROM sales s 
                             JOIN sale_items si ON s.id = si.sale_id 
                             WHERE s.payment_type = 'cash'
                             AND s.date >= :startDate";
    $stmt = $conn->prepare($currentCashSalesQuery);
    $stmt->execute(['startDate' => $startDate]);
    $cashSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch previous period cash sales
    $previousCashSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                              FROM sales s 
                              JOIN sale_items si ON s.id = si.sale_id 
                              WHERE s.payment_type = 'cash'
                              AND s.date >= :previousPeriodStart
                              AND s.date <= :previousPeriodEnd";
    $stmt = $conn->prepare($previousCashSalesQuery);
    $stmt->execute([
        'previousPeriodStart' => $previousPeriodStart,
        'previousPeriodEnd' => $previousPeriodEnd
    ]);
    $previousCashSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate cash sales percentage change
    $cashSalesPercentage = $previousCashSales > 0 ?
        round((($cashSales - $previousCashSales) / $previousCashSales) * 100, 1) : 0;

    // Fetch current period credit sales
    $currentCreditSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                               FROM sales s 
                               JOIN sale_items si ON s.id = si.sale_id 
                               WHERE s.payment_type = 'credit'
                               AND s.date >= :startDate";
    $stmt = $conn->prepare($currentCreditSalesQuery);
    $stmt->execute(['startDate' => $startDate]);
    $creditSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch previous period credit sales
    $previousCreditSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                                FROM sales s 
                                JOIN sale_items si ON s.id = si.sale_id 
                                WHERE s.payment_type = 'credit'
                                AND s.date >= :previousPeriodStart
                                AND s.date <= :previousPeriodEnd";
    $stmt = $conn->prepare($previousCreditSalesQuery);
    $stmt->execute([
        'previousPeriodStart' => $previousPeriodStart,
        'previousPeriodEnd' => $previousPeriodEnd
    ]);
    $previousCreditSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate credit sales percentage change
    $creditSalesPercentage = $previousCreditSales > 0 ?
        round((($creditSales - $previousCreditSales) / $previousCreditSales) * 100, 1) : 0;

    // Fetch current period cash purchases
    $currentCashPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                                 FROM purchases p 
                                 JOIN purchase_items pi ON p.id = pi.purchase_id 
                                 WHERE p.payment_type = 'cash'
                                 AND p.date >= :startDate";
    $stmt = $conn->prepare($currentCashPurchasesQuery);
    $stmt->execute(['startDate' => $startDate]);
    $cashPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch previous period cash purchases
    $previousCashPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                                  FROM purchases p 
                                  JOIN purchase_items pi ON p.id = pi.purchase_id 
                                  WHERE p.payment_type = 'cash'
                                  AND p.date >= :previousPeriodStart
                                  AND p.date <= :previousPeriodEnd";
    $stmt = $conn->prepare($previousCashPurchasesQuery);
    $stmt->execute([
        'previousPeriodStart' => $previousPeriodStart,
        'previousPeriodEnd' => $previousPeriodEnd
    ]);
    $previousCashPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate cash purchases percentage change
    $cashPurchasesPercentage = $previousCashPurchases > 0 ?
        round((($cashPurchases - $previousCashPurchases) / $previousCashPurchases) * 100, 1) : 0;

    // Fetch current period credit purchases
    $currentCreditPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                                   FROM purchases p 
                                   JOIN purchase_items pi ON p.id = pi.purchase_id 
                                   WHERE p.payment_type = 'credit'
                                   AND p.date >= :startDate";
    $stmt = $conn->prepare($currentCreditPurchasesQuery);
    $stmt->execute(['startDate' => $startDate]);
    $creditPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch previous period credit purchases
    $previousCreditPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                                    FROM purchases p 
                                    JOIN purchase_items pi ON p.id = pi.purchase_id 
                                    WHERE p.payment_type = 'credit'
                                    AND p.date >= :previousPeriodStart
                                    AND p.date <= :previousPeriodEnd";
    $stmt = $conn->prepare($previousCreditPurchasesQuery);
    $stmt->execute([
        'previousPeriodStart' => $previousPeriodStart,
        'previousPeriodEnd' => $previousPeriodEnd
    ]);
    $previousCreditPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate credit purchases percentage change
    $creditPurchasesPercentage = $previousCreditPurchases > 0 ?
        round((($creditPurchases - $previousCreditPurchases) / $previousCreditPurchases) * 100, 1) : 0;

    // Calculate total customer debt (from customers table)
    $totalCustomerDebtQuery = "SELECT COALESCE(SUM(debit_on_business), 0) as total 
                              FROM customers";
    $stmt = $conn->prepare($totalCustomerDebtQuery);
    $stmt->execute();
    $totalCustomerDebt = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate total supplier debt (from suppliers table)
    $totalSupplierDebtQuery = "SELECT COALESCE(SUM(debt_on_myself), 0) as total 
                              FROM suppliers";
    $stmt = $conn->prepare($totalSupplierDebtQuery);
    $stmt->execute();
    $totalSupplierDebt = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate percentage changes for debts
    // For customer debt - compare current debt with previous period
    $customerDebtPercentage = 0;
    try {
        $previousPeriodCustomerDebtQuery = "SELECT COALESCE(SUM(amount), 0) as total 
                                           FROM debt_transactions 
                                           WHERE transaction_type IN ('sale', 'purchase')
                                           AND created_at < :startDate
                                           AND created_at >= :previousPeriodStart";
        $stmt = $conn->prepare($previousPeriodCustomerDebtQuery);
        $stmt->execute([
            'startDate' => $startDate,
            'previousPeriodStart' => $previousPeriodStart
        ]);
        $previousPeriodCustomerDebt = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $currentPeriodCustomerDebtQuery = "SELECT COALESCE(SUM(amount), 0) as total 
                                          FROM debt_transactions 
                                          WHERE transaction_type IN ('sale', 'purchase')
                                          AND created_at >= :startDate";
        $stmt = $conn->prepare($currentPeriodCustomerDebtQuery);
        $stmt->execute(['startDate' => $startDate]);
        $currentPeriodCustomerDebt = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($previousPeriodCustomerDebt > 0) {
            $customerDebtPercentage = round((($currentPeriodCustomerDebt - $previousPeriodCustomerDebt) / $previousPeriodCustomerDebt) * 100, 1);
        }
    } catch (PDOException $e) {
        $customerDebtPercentage = 0;
    }

    // For supplier debt - compare current debt with previous period
    $supplierDebtPercentage = 0;
    try {
        $previousPeriodSupplierDebtQuery = "SELECT COALESCE(SUM(amount), 0) as total 
                                           FROM supplier_debt_transactions 
                                           WHERE transaction_type = 'purchase'
                                           AND created_at < :startDate
                                           AND created_at >= :previousPeriodStart";
        $stmt = $conn->prepare($previousPeriodSupplierDebtQuery);
        $stmt->execute([
            'startDate' => $startDate,
            'previousPeriodStart' => $previousPeriodStart
        ]);
        $previousPeriodSupplierDebt = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $currentPeriodSupplierDebtQuery = "SELECT COALESCE(SUM(amount), 0) as total 
                                          FROM supplier_debt_transactions 
                                          WHERE transaction_type = 'purchase'
                                          AND created_at >= :startDate";
        $stmt = $conn->prepare($currentPeriodSupplierDebtQuery);
        $stmt->execute(['startDate' => $startDate]);
        $currentPeriodSupplierDebt = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($previousPeriodSupplierDebt > 0) {
            $supplierDebtPercentage = round((($currentPeriodSupplierDebt - $previousPeriodSupplierDebt) / $previousPeriodSupplierDebt) * 100, 1);
        }
    } catch (PDOException $e) {
        $supplierDebtPercentage = 0;
    }

    // Calculate total expenses
    $totalExpensesQuery = "SELECT COALESCE(SUM(amount), 0) as total 
                          FROM expenses 
                          WHERE date >= :startDate";
    $stmt = $conn->prepare($totalExpensesQuery);
    $stmt->execute(['startDate' => $startDate]);
    $totalExpenses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate previous period expenses
    $previousExpensesQuery = "SELECT COALESCE(SUM(amount), 0) as total 
                             FROM expenses 
                             WHERE date >= :previousPeriodStart 
                             AND date <= :previousPeriodEnd";
    $stmt = $conn->prepare($previousExpensesQuery);
    $stmt->execute([
        'previousPeriodStart' => $previousPeriodStart,
        'previousPeriodEnd' => $previousPeriodEnd
    ]);
    $previousExpenses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate expenses percentage change
    $expensesPercentage = $previousExpenses > 0 ?
        round((($totalExpenses - $previousExpenses) / $previousExpenses) * 100, 1) : 0;

    // Calculate total profit
    $totalProfit = ($cashSales + $creditSales) - ($cashPurchases + $creditPurchases + $totalExpenses);
    $previousProfit = ($previousCashSales + $previousCreditSales) - ($previousCashPurchases + $previousCreditPurchases + $previousExpenses);
    $profitPercentage = $previousProfit > 0 ?
        round((($totalProfit - $previousProfit) / $previousProfit) * 100, 1) : 0;
    
    // Return the data as JSON
    echo json_encode([
        'success' => true,
        'data' => [
            'cashSales' => $cashSales,
            'cashSalesPercentage' => $cashSalesPercentage,
            'creditSales' => $creditSales,
            'creditSalesPercentage' => $creditSalesPercentage,
            'cashPurchases' => $cashPurchases,
            'cashPurchasesPercentage' => $cashPurchasesPercentage,
            'creditPurchases' => $creditPurchases,
            'creditPurchasesPercentage' => $creditPurchasesPercentage,
            'totalCustomerDebt' => $totalCustomerDebt,
            'customerDebtPercentage' => $customerDebtPercentage,
            'totalSupplierDebt' => $totalSupplierDebt,
            'supplierDebtPercentage' => $supplierDebtPercentage,
            'totalExpenses' => $totalExpenses,
            'expensesPercentage' => $expensesPercentage,
            'totalProfit' => $totalProfit,
            'profitPercentage' => $profitPercentage
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
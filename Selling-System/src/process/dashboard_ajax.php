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

    // Fetch all the required data
    // ... (copy all the data fetching logic from dashboard_logic.php)
    
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
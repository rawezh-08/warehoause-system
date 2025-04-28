<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Get wasting ID
if (!isset($_GET['id'])) {
    echo "ID is required";
    exit;
}

$wasting_id = intval($_GET['id']);

// Create database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Get wasting details
    $stmt = $conn->prepare("
        SELECT w.*
        FROM wastings w
        WHERE w.id = ?
    ");
    
    $stmt->execute([$wasting_id]);
    $wasting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wasting) {
        echo "Wasting record not found";
        exit;
    }
    
    // Get wasting items
    $stmt = $conn->prepare("
        SELECT wi.*, p.name as product_name
        FROM wasting_items wi
        JOIN products p ON wi.product_id = p.id
        WHERE wi.wasting_id = ?
    ");
    
    $stmt->execute([$wasting_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total amount
    $total_amount = 0;
    foreach ($items as $item) {
        $total_amount += floatval($item['total_price']);
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit;
}

// Get business info
$stmt = $conn->prepare("SELECT * FROM business_info LIMIT 1");
$stmt->execute();
$business = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڵگەی بەفیڕۆچوون - <?php echo $wasting_id; ?></title>
    <style>
        @media print {
            @page {
                size: 80mm 297mm;
                margin: 5mm;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
            background-color: white;
            color: black;
        }
        
        .receipt {
            width: 100%;
            max-width: 80mm;
            margin: 0 auto;
            padding: 10px;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .receipt-header h2 {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .receipt-header p {
            margin: 3px 0;
            font-size: 10px;
        }
        
        .receipt-info {
            margin: 10px 0;
            padding: 5px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }
        
        .receipt-info p {
            margin: 3px 0;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .items-table th, .items-table td {
            text-align: right;
            padding: 3px 5px;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 10px;
            padding-top: 5px;
            border-top: 1px dashed #000;
            font-size: 10px;
        }
        
        .total-row {
            font-weight: bold;
            border-top: 1px solid #000;
        }
        
        .no-print {
            margin: 20px 0;
            text-align: center;
        }
        
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="receipt-header">
            <?php if (!empty($business)): ?>
            <h2><?php echo $business['name'] ?? 'بزنسی من'; ?></h2>
            <p><?php echo $business['address'] ?? ''; ?></p>
            <p><?php echo $business['phone'] ?? ''; ?></p>
            <?php else: ?>
            <h2>بزنسی من</h2>
            <?php endif; ?>
            <h3>بەڵگەی بەفیڕۆچوون</h3>
        </div>
        
        <div class="receipt-info">
            <p><strong>ژمارەی تۆمار:</strong> <?php echo $wasting_id; ?></p>
            <p><strong>بەروار:</strong> <?php echo date('Y-m-d', strtotime($wasting['date'])); ?></p>
            <?php if (!empty($wasting['notes'])): ?>
            <p><strong>تێبینی:</strong> <?php echo $wasting['notes']; ?></p>
            <?php endif; ?>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%;">کاڵا</th>
                    <th style="width: 20%;">بڕ</th>
                    <th style="width: 20%;">نرخی تاک</th>
                    <th style="width: 20%;">نرخی گشتی</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <?php 
                    $unitType = '';
                    switch($item['unit_type']) {
                        case 'piece': $unitType = 'دانە'; break;
                        case 'box': $unitType = 'کارتۆن'; break;
                        case 'set': $unitType = 'سێت'; break;
                        default: $unitType = 'دانە';
                    }
                ?>
                <tr>
                    <td><?php echo $item['product_name']; ?></td>
                    <td><?php echo $item['quantity'] . ' ' . $unitType; ?></td>
                    <td><?php echo number_format($item['unit_price']); ?></td>
                    <td><?php echo number_format($item['total_price']); ?></td>
                </tr>
                <?php endforeach; ?>
                
                <tr class="total-row">
                    <td colspan="3" style="text-align: left;">کۆی گشتی:</td>
                    <td><?php echo number_format($total_amount); ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="receipt-footer">
            <p>بەڵگەی بەفیڕۆچوونی کاڵا - <?php echo date('Y-m-d H:i:s'); ?></p>
            <p>سیستەمی فرۆشتن</p>
        </div>
    </div>
    
    <div class="no-print">
        <button onclick="window.print()">چاپکردن</button>
        <button onclick="window.close()">داخستن</button>
    </div>
</body>
</html> 
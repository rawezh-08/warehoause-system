<?php
require_once '../../config/database.php';

// Get receipt ID from URL - handle both id and sale_id parameters
$receipt_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['sale_id']) ? intval($_GET['sale_id']) : 0);

if ($receipt_id <= 0) {
    die('پسووڵەی داواکراو نەدۆزرایەوە');
}

// Fetch receipt data
$stmt = $conn->prepare("
    SELECT s.*, c.name as customer_name, c.phone1 as customer_phone, c.address as customer_address
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$receipt_id]);
$receipt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$receipt) {
    die('پسووڵەی داواکراو نەدۆزرایەوە');
}

// Check if this is a delivery receipt
if (!isset($receipt['is_delivery']) || $receipt['is_delivery'] != 1) {
    // Redirect to print_receipt.php if this is not a delivery receipt
    header("Location: print_receipt.php?sale_id=" . $receipt_id);
    exit;
}

// Fetch receipt items
$stmt = $conn->prepare("
    SELECT si.*, p.name as product_name, p.code as product_code
    FROM sale_items si
    LEFT JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?
");
$stmt->execute([$receipt_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['total_price'];
}
$total = $subtotal + $receipt['shipping_cost'] + $receipt['other_costs'] - $receipt['discount'];
?>
<!DOCTYPE html>
<html lang="ckb" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسووڵەی گەیاندن</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Rabar';
            src: url('../../assets/fonts/Rabar_021.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --primary-color: #7380ec;
            --primary-light: rgba(115, 128, 236, 0.1);
            --primary-hover: #5b6be0;
            --danger-color: #ff7782;
            --success-color: #41f1b6;
            --warning-color: #ffbb55;
            --info-color: #7380ec;
            --dark-color: #363949; 
            --text-color: #363949;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --bg-light: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Rabar', sans-serif;
            background: #f0f0f0;
            color: var(--text-color);
            line-height: 1.6;
            padding: 0;
        }

        /* سایزی A5 ئاسۆیی */
        .receipt-container {
            width: 210mm; /* عەرزی A5 */
            height: 148mm; /* بەرزی A5 */
            margin: 10px auto;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            overflow: hidden;
            page-break-after: always;
            display: flex;
            flex-direction: column;
        }
        
        .receipt-header {
            background: #7380ec;
            color: white;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header-left {
            text-align: left;
            font-size: 11px;
        }
        
        .header-center {
            text-align: center;
            flex-grow: 1;
        }
        
        .store-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        
        .receipt-body {
            padding: 10px 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .section-title {
            border-bottom: 1px solid #3a6ea5;
            padding-bottom: 4px;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 13px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 11px;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .info-value {
            text-align: left;
        }
        
        .table {
            margin: 8px 0;
            font-size: 11px;
        }
        
        .table th, .table td {
            padding: 4px 6px;
        }
        
        .table th {
            background-color: #f0f4f8;
            font-weight: 600;
        }
        
        .receipt-footer {
            background-color: #f0f4f8;
            padding: 8px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px dashed #ccc;
            font-size: 11px;
            margin-top: auto;
        }
        
        .logo {
            width: 40px;
            height: 40px;
            background-color: white;
            border-radius: 50%;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .info-boxes {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .info-box {
            background-color: #e8f4fd;
            padding: 8px;
            border-radius: 5px;
            flex: 1;
        }
        
        .contact-info {
            display: flex;
            gap: 10px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 3px;
        }
        
        .products-table {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .table-responsive {
            flex-grow: 1;
        }
        
        @media print {
            body {
                background-color: white;
                margin: 0;
                padding: 0;
            }
            
            .receipt-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                width: 210mm;
                height: 148mm;
                page-break-after: avoid;
                page-break-inside: avoid;
                position: fixed;
                top: 0;
                left: 0;
            }
            
            .no-print {
                display: none;
            }
            
            @page {
                size: A5 landscape;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="receipt-container">
            <div class="receipt-header">
                <div class="logo">
                    <img src="../../assets/icons/logistics.png" alt="لۆگۆی کۆگا" class="img-fluid">
                </div>
                <div class="header-center">
                    <div class="store-name">کۆگای ئەحمەد و ئەشکان</div>
                </div>
                <div class="header-left">
                    <div class="info-row">
                        <span class="info-label">ژمارەی پسووڵە:</span>
                        <span class="info-value"><?php echo htmlspecialchars($receipt['invoice_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">بەرواری دەرچوون:</span>
                        <span class="info-value"><?php echo date('Y/m/d', strtotime($receipt['date'])); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="receipt-body">
                <div class="info-boxes">
                    <div class="info-box">
                        <h5 class="section-title">زانیاری داواکار</h5>
                        <div class="info-row">
                            <span class="info-label">ناو:</span>
                            <span class="info-value"><?php echo htmlspecialchars($receipt['customer_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">ژمارە:</span>
                            <span class="info-value"><?php echo htmlspecialchars($receipt['customer_phone']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">ناونیشانی گەیاندن:</span>
                            <span class="info-value"><?php echo htmlspecialchars($receipt['delivery_address']); ?></span>
                        </div>
                    </div>
                    <div class="info-box">
                        <h5 class="section-title">زانیاری زیاتر</h5>
                        <div class="info-row">
                            <span class="info-label">کات:</span>
                            <span class="info-value"><?php echo date('H:i', strtotime($receipt['date'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">پارەدان:</span>
                            <span class="info-value"><?php echo $receipt['payment_type'] === 'cash' ? 'کاش' : 'قەرز'; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="products-table">
                    <h5 class="section-title">لیستی کاڵاکان</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ناوی کاڵا</th>
                                    <th>کۆدی کاڵا</th>
                                    <th>ژمارە</th>
                                    <th>جۆری یەکە</th>
                                    <th>نرخی تاک</th>
                                    <th>کۆی گشتی</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $index => $item): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>
                                        <?php
                                        switch($item['unit_type']) {
                                            case 'piece':
                                                echo 'دانە';
                                                break;
                                            case 'box':
                                                echo 'کارتۆن';
                                                break;
                                            case 'set':
                                                echo 'سێت';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo number_format($item['unit_price']); ?></td>
                                    <td><?php echo number_format($item['total_price']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5"></td>
                                    <td><strong>کۆی گشتی:</strong></td>
                                    <td><strong><?php echo number_format($total); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="receipt-footer">
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>سلێمانی - کۆگاکانی غرفة تجارة - کۆگای 288</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone-alt"></i>
                        <span>5656 225 0771 - 8786 147 0750</span>
                    </div>
                </div>
                <button class="btn btn-primary btn-sm no-print" onclick="window.print()">
                    <i class="fas fa-print me-1"></i>چاپکردن
                </button>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
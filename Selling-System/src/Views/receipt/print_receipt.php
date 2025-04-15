<?php
// Sample data
$products = [
    [
        'image' => '../../uploads/products/67f00bba0f489_1743784890.jpg',
        'code' => '9878',
        'name' => 'شووشەی ئاو کەوانتەر',
        'price' => 12.349,
        'quantity' => 5,
        'total' => 582.353,
        'unit_price' => 435.456
    ],
    [
        'image' => '../../uploads/products/67f00bba0f489_1743784890.jpg',
        'code' => '9878',
        'name' => 'شووشەی ئاو کەوانتەر',
        'price' => 12.349,
        'quantity' => 5,
        'total' => 582.353,
        'unit_price' => 435.456
    ],
    [
        'image' => '../../uploads/products/67f00bba0f489_1743784890.jpg',
        'code' => '9878',
        'name' => 'شووشەی ئاو کەوانتەر',
        'price' => 12.349,
        'quantity' => 5,
        'total' => 582.353,
        'unit_price' => 435.456
    ],
    [
        'image' => '../../uploads/products/67f00bba0f489_1743784890.jpg',
        'code' => '9878',
        'name' => 'شووشەی ئاو کەوانتەر',
        'price' => 12.349,
        'quantity' => 5,
        'total' => 582.353,
        'unit_price' => 435.456
    ],
    [
        'image' => '../../uploads/products/67f00bba0f489_1743784890.jpg',
        'code' => '9878',
        'name' => 'شووشەی ئاو کەوانتەر',
        'price' => 12.349,
        'quantity' => 5,
        'total' => 582.353,
        'unit_price' => 435.456
    ],
    [
        'image' => '../../uploads/products/67f00bba0f489_1743784890.jpg',

        'code' => '9878',
        'name' => 'شووشەی ئاو کەوانتەر',
        'price' => 12.349,
        'quantity' => 5,
        'total' => 582.353,
        'unit_price' => 435.456
    ],
    [
        'image' => '../../uploads/products/67f00bba0f489_1743784890.jpg',
        'code' => '9878',
        'name' => 'شووشەی ئاو کەوانتەر',
        'price' => 12.349,
        'quantity' => 5,
        'total' => 582.353,
        'unit_price' => 435.456
    ]
];

// Sample calculations
$total_amount = 18320.00;
$discount = 0;
$after_discount = 18320.00;
$paid_amount = 916.00;
$remaining_balance = 916.00;
$previous_balance = 18320.00;
$remaining_amount = 916.00;
$grand_total = 19236.00;
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسووڵەی فرۆشتن</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;600&display=swap');
        @font-face {
    font-family: 'Rabar';
    src: url('../../assets/fonts/Rabar_021.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
}

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Rabar', sans-serif;
            background: #f8f9fa;
            padding: 20px;
            color: #333;
        }

        .receipt-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 20px;
            gap: 20px;
        }

        .qr-section {
            background: #F6F8FC;
            padding: 4px;
            border-radius: 32px;
            text-align: center;
            flex: 0 0 200px;
        }

        .qr-section img {
            width: 140px;
            height: 140px;
        }

        .qr-text {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
            text-align: center;
        }

        .company-info {
            position: relative;
            overflow: hidden;

            background: linear-gradient(135deg, #6B8CFF 0%, #737FFF 100%);
            padding: 36px;
            border-radius: 40px;
            color: white;
            flex-grow: 1;
        }

        .company-title {
            display: flex;
            align-items: center;
            /* justify-content: space-between; */
            margin-bottom: 20px;
      }

        .company-name {
            font-size: 28px;
            font-weight: 600;
        }

        .company-logo {
            width: 50px;
            height: 50px;
            
            /* padding: 5px; */
            /* margin: 10px; */
        }
      
        .company-details {
            display: flex;
            flex-direction: column;
            gap: 20px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
        }

        .icon-location{
            width: 20px;
            height: 20px;
        }
        .icon-call{
            width: 20px;
            height: 20px;
        }
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
        }

        .products-table th {
            background: #F8F9FA;
            padding: 15px;
            text-align: right;
            font-weight: 500;
            color: #666;
        }

        .products-table td {
            padding: 15px;
            border-bottom: 1px solid #F8F9FA;
        }

        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        .summary-section {
            display: flex;
            gap: 20px;
            padding: 20px;
        }

        .summary-left {
            position: relative;
            background: linear-gradient(135deg, #6B8CFF 0%, #737FFF 100%);
            border-radius: 32px;
            padding: 30px;
            padding-top:50px;

            color: white;
            flex: 1;
            overflow: hidden;
            box-shadow: 0 0 10px 0 rgba(80, 159, 255, 0.38);
            text-align: center;
        }

        .texture-2{
            position: absolute;
            top: -90%;
            right: -50%;
            width: 200%;
            height: 200%;
        }

        .summary-right {
            background: #F8F9FA;
            border-radius: 32px;
            padding: 30px;
            flex: 1;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .summary-right .summary-item {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .total-amount {
            font-size: 24px;
            font-weight: 600;
            margin-top: 10px;
        }
        .texture-1{
            position: absolute;
            top: -0%;
            left: -60%;
            width: 200%;
            height: 200%; 
        }

       .t-head{
            background:rgb(196, 18, 107);
        }

        .summary-left .summary-item {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .summary-left .total-amount {
            font-size: 24px;
            font-weight: 600;
            margin-top: 20px;
            text-align: center;
        }

        @media print {
            body {
                padding: 0;
            }
            .receipt-container {
                max-width: 100%;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header-section">
           
            <div class="company-info">
                <img src="../../assets/images/bg-texture.svg" alt="Logo" class="texture-1">
                <div class="company-title">
                    <img src="../../assets/images/company-logo.svg" alt="Logo" class="company-logo">
                    <div class="company-name">کۆگای ئەشکان</div>

                </div>
                <div class="company-details">
                    <div class="detail-item">
                    <img src="../../assets/icons/location.svg" class="icon-location" alt="">
                    <span>ناونیشان: سلێمانی - کانی با</span>
                    </div>
                    <div class="detail-item">
                        <img src="../../assets/icons/call.svg" class="icon-call" alt="">
                        <span>ژمارە تەلەفۆن: 0770 123 5678</span>
                    </div>
                </div>
            </div>

            <div class="qr-section">
                <img src="../../assets/images/sample_qr.svg" alt="QR Code">
                <div class="qr-text">
                  <p>سەردانی گروپ بکەن بۆ بینینی بابەتەکان</p>
                </div>
            </div>
        </div>

        <table class="products-table">
            <thead>
                <tr >
                    <th style="border-top-right-radius: 50px; border-bottom-right-radius: 50px;">وێنە</th>
                    <th>کۆد</th>
                    <th>جۆری کاڵا</th>
                    <th>نرخ</th>
                    <th>بڕ</th>
                    <th>کۆ</th>
                    <th style="border-top-left-radius: 50px; border-bottom-left-radius: 50px;">نرخی یەکە</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><img src="<?php echo $product['image']; ?>" alt="Product" class="product-image"></td>
                    <td><?php echo $product['code']; ?></td>
                    <td><?php echo $product['name']; ?></td>
                    <td><?php echo number_format($product['price'], 3) . '$'; ?></td>
                    <td><?php echo $product['quantity']; ?></td>
                    <td><?php echo number_format($product['total'], 3) . '$'; ?></td>
                    <td><?php echo number_format($product['unit_price'], 3) . '$'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="summary-section">
        <div class="summary-right">
                <div class="summary-item">
                    <span>کۆی گشتی پسولە</span>
                    <span>$<?php echo number_format($total_amount, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>داشکاندن</span>
                    <span><?php echo $discount; ?>%</span>
                </div>
                <div class="summary-item">
                    <span>دوای داشکاندن</span>
                    <span>$<?php echo number_format($after_discount, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>پارەی دراو</span>
                    <span>$<?php echo number_format($paid_amount, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>پارەی ماوە</span>
                    <span>$<?php echo number_format($remaining_balance, 2); ?></span>
                </div>
            </div>
            <div class="summary-left">
                <img src="../../assets/images/bg-texture-2.svg" alt="" class="texture-2">
                <div class="summary-item">
                    <span>قەرزی پێشوو</span>
                    <span>$<?php echo number_format($total_amount, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>پارەی ماوەی ئەم پسولە</span>
                    <span>$<?php echo number_format($remaining_amount, 2); ?></span>
                </div>
                <div class="total-amount">
                    <span>$<?php echo number_format($grand_total, 2); ?></span>
                </div>
            </div>
            
        </div>
    </div>
</body>
</html> 
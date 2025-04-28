<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if customer ID is provided
$customerId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get customer details
$customerQuery = "SELECT * FROM customers WHERE id = :id";
$customerStmt = $conn->prepare($customerQuery);
$customerStmt->bindParam(':id', $customerId);
$customerStmt->execute();
$customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

// If customer not found, redirect to customers list page
if (!$customer) {
    header("Location: customers.php");
    exit;
}

// Get all sales for this customer with detailed product information
$salesQuery = "SELECT s.*, 
               si.quantity, si.unit_type, si.pieces_count, si.unit_price, si.total_price,
               p.name as product_name, p.code as product_code,
               SUM(si.total_price) as sale_total,
               (SELECT SUM(total_price) FROM sale_items WHERE sale_id = s.id) as invoice_total
               FROM sales s 
               JOIN sale_items si ON s.id = si.sale_id 
               JOIN products p ON si.product_id = p.id
               WHERE s.customer_id = :customer_id 
               GROUP BY s.id, si.id, p.id
               ORDER BY s.date DESC";
$salesStmt = $conn->prepare($salesQuery);
$salesStmt->bindParam(':customer_id', $customerId);
$salesStmt->execute();
$sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all debt transactions for this customer (credit sales and payments)
$debtQuery = "SELECT dt.*, 
             CASE 
                WHEN dt.transaction_type = 'sale' THEN (SELECT invoice_number FROM sales WHERE id = dt.reference_id)
                ELSE '' 
             END as invoice_number,
             s.payment_type
             FROM debt_transactions dt
             LEFT JOIN sales s ON dt.reference_id = s.id AND dt.transaction_type = 'sale'
             WHERE dt.customer_id = :customer_id 
             ORDER BY dt.created_at DESC";
$debtStmt = $conn->prepare($debtQuery);
$debtStmt->bindParam(':customer_id', $customerId);
$debtStmt->execute();
$debtTransactions = $debtStmt->fetchAll(PDO::FETCH_ASSOC);

// Filter to get only credit transactions
$creditTransactions = array_filter($debtTransactions, function ($transaction) {
    // Include only sales with payment_type = 'credit' or manual payments/collections
    return ($transaction['transaction_type'] == 'sale' && $transaction['payment_type'] == 'credit')
        || $transaction['transaction_type'] == 'payment'
        || $transaction['transaction_type'] == 'collection';
});

// Calculate additional metrics
$totalReturns = 0;
$totalSales = 0;
$monthlySales = 0;

foreach ($sales as $sale) {
    $totalSales += $sale['sale_total'];
    if (date('Y-m', strtotime($sale['date'])) === date('Y-m')) {
        $monthlySales += $sale['sale_total'];
    }
}

foreach ($debtTransactions as $debtTransaction) {
    if ($debtTransaction['transaction_type'] === 'collection') {
        $totalReturns += $debtTransaction['amount'];
    }
}

// Get customer's recent sales
$salesQuery = "SELECT s.*, 
    (SELECT COUNT(*) FROM product_returns pr WHERE pr.receipt_id = s.id AND pr.receipt_type = 'selling') as return_count
    FROM sales s 
    WHERE s.customer_id = :customer_id 
    ORDER BY s.date DESC 
    LIMIT 10";
$salesStmt = $conn->prepare($salesQuery);
$salesStmt->bindParam(':customer_id', $customerId);
$salesStmt->execute();
$recentSales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle return submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_sale'])) {
    $saleId = intval($_POST['sale_id']);
    $returnDate = $_POST['return_date'];
    $reason = $_POST['reason'];
    $notes = $_POST['notes'];
    
    try {
        $conn->beginTransaction();
        
        // Create return record
        $returnQuery = "INSERT INTO product_returns (receipt_id, receipt_type, return_date, reason, notes) 
                       VALUES (:receipt_id, 'selling', :return_date, :reason, :notes)";
        $returnStmt = $conn->prepare($returnQuery);
        $returnStmt->bindParam(':receipt_id', $saleId);
        $returnStmt->bindParam(':return_date', $returnDate);
        $returnStmt->bindParam(':reason', $reason);
        $returnStmt->bindParam(':notes', $notes);
        $returnStmt->execute();
        $returnId = $conn->lastInsertId();
        
        // Get sale items
        $saleItemsQuery = "SELECT * FROM sale_items WHERE sale_id = :sale_id";
        $saleItemsStmt = $conn->prepare($saleItemsQuery);
        $saleItemsStmt->bindParam(':sale_id', $saleId);
        $saleItemsStmt->execute();
        $saleItems = $saleItemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalReturnAmount = 0;
        
        // Process each item
        foreach ($saleItems as $item) {
            if (isset($_POST['return_quantity'][$item['id']]) && $_POST['return_quantity'][$item['id']] > 0) {
                $returnQuantity = intval($_POST['return_quantity'][$item['id']]);
                $returnUnitType = $_POST['return_unit_type'][$item['id']];
                
                // Add return item
                $returnItemQuery = "INSERT INTO return_items (return_id, product_id, quantity, unit_price, unit_type, 
                                  original_unit_type, original_quantity, reason, total_price) 
                                  VALUES (:return_id, :product_id, :quantity, :unit_price, :unit_type, 
                                  :original_unit_type, :original_quantity, :reason, :total_price)";
                $returnItemStmt = $conn->prepare($returnItemQuery);
                $returnItemStmt->bindParam(':return_id', $returnId);
                $returnItemStmt->bindParam(':product_id', $item['product_id']);
                $returnItemStmt->bindParam(':quantity', $returnQuantity);
                $returnItemStmt->bindParam(':unit_price', $item['unit_price']);
                $returnItemStmt->bindParam(':unit_type', $returnUnitType);
                $returnItemStmt->bindParam(':original_unit_type', $item['unit_type']);
                $returnItemStmt->bindParam(':original_quantity', $item['quantity']);
                $returnItemStmt->bindParam(':reason', $reason);
                $totalPrice = $returnQuantity * $item['unit_price'];
                $returnItemStmt->bindParam(':total_price', $totalPrice);
                $returnItemStmt->execute();
                
                // Update inventory
                $inventoryQuery = "INSERT INTO inventory (product_id, quantity, reference_type, reference_id, notes) 
                                 VALUES (:product_id, :quantity, 'return', :reference_id, :notes)";
                $inventoryStmt = $conn->prepare($inventoryQuery);
                $inventoryStmt->bindParam(':product_id', $item['product_id']);
                $inventoryStmt->bindParam(':quantity', $returnQuantity);
                $inventoryStmt->bindParam(':reference_id', $returnId);
                $notes = "گەڕاندنەوە: " . $returnQuantity . " " . $returnUnitType . " (ئەسڵی: " . $item['quantity'] . " " . $item['unit_type'] . ")";
                $inventoryStmt->bindParam(':notes', $notes);
                $inventoryStmt->execute();
                
                // Update sale item returned quantity
                $updateSaleItemQuery = "UPDATE sale_items SET returned_quantity = returned_quantity + :return_quantity 
                                      WHERE id = :item_id";
                $updateSaleItemStmt = $conn->prepare($updateSaleItemQuery);
                $updateSaleItemStmt->bindParam(':return_quantity', $returnQuantity);
                $updateSaleItemStmt->bindParam(':item_id', $item['id']);
                $updateSaleItemStmt->execute();
                
                $totalReturnAmount += $totalPrice;
            }
        }
        
        // Update return total amount
        $updateReturnQuery = "UPDATE product_returns SET total_amount = :total_amount WHERE id = :return_id";
        $updateReturnStmt = $conn->prepare($updateReturnQuery);
        $updateReturnStmt->bindParam(':total_amount', $totalReturnAmount);
        $updateReturnStmt->bindParam(':return_id', $returnId);
        $updateReturnStmt->execute();
        
        // Update customer debt
        if ($totalReturnAmount > 0) {
            $debtQuery = "INSERT INTO debt_transactions (customer_id, amount, transaction_type, reference_id, notes) 
                         VALUES (:customer_id, :amount, '', :reference_id, :notes)";
            $debtStmt = $conn->prepare($debtQuery);
            $debtStmt->bindParam(':customer_id', $customerId);
            $amount = -$totalReturnAmount; // Negative amount to decrease debt
            $debtStmt->bindParam(':amount', $amount);
            $debtStmt->bindParam(':reference_id', $returnId);
            $notes = "گەڕاندنەوەی کاڵا - ";
            $debtStmt->bindParam(':notes', $notes);
            $debtStmt->execute();
            
            // Update customer's debt_on_customer
            $updateCustomerQuery = "UPDATE customers SET debt_on_customer = debt_on_customer - :amount 
                                  WHERE id = :customer_id";
            $updateCustomerStmt = $conn->prepare($updateCustomerQuery);
            $updateCustomerStmt->bindParam(':amount', $totalReturnAmount);
            $updateCustomerStmt->bindParam(':customer_id', $customerId);
            $updateCustomerStmt->execute();
        }
        
        $conn->commit();
        header("Location: customerProfile.php?id=" . $customerId . "&success=return_added");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Error processing return: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Profile - <?php echo htmlspecialchars($customer['name']); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1>Customer Profile</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'return_added'): ?>
            <div class="alert alert-success">Return processed successfully!</div>
        <?php endif; ?>
        
        <div class="customer-info">
            <h2><?php echo htmlspecialchars($customer['name']); ?></h2>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone1']); ?></p>
            <?php if ($customer['phone2']): ?>
                <p><strong>Secondary Phone:</strong> <?php echo htmlspecialchars($customer['phone2']); ?></p>
            <?php endif; ?>
            <?php if ($customer['address']): ?>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($customer['address']); ?></p>
            <?php endif; ?>
            <p><strong>Current Debt:</strong> <?php echo number_format($customer['debt_on_customer']); ?> IQD</p>
        </div>
        
        <div class="recent-sales">
            <h3>Recent Sales</h3>
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Payment Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentSales as $sale): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($sale['date'])); ?></td>
                            <td><?php echo number_format($sale['remaining_amount'] + $sale['paid_amount']); ?> IQD</td>
                            <td><?php echo ucfirst($sale['payment_type']); ?></td>
                            <td>
                                <?php if ($sale['remaining_amount'] > 0): ?>
                                    <span class="badge badge-warning">Unpaid</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Paid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-primary" onclick="showReturnModal(<?php echo $sale['id']; ?>)">
                                    <i class="fas fa-undo"></i> Return
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Return Modal -->
    <div id="returnModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Process Return</h2>
            <form method="POST" id="returnForm">
                <input type="hidden" name="sale_id" id="sale_id">
                <input type="hidden" name="return_sale" value="1">
                
                <div class="form-group">
                    <label for="return_date">Return Date:</label>
                    <input type="datetime-local" id="return_date" name="return_date" required>
                </div>
                
                <div class="form-group">
                    <label for="reason">Return Reason:</label>
                    <select id="reason" name="reason" required>
                        <option value="damaged">Damaged</option>
                        <option value="wrong_product">Wrong Product</option>
                        <option value="customer_request">Customer Request</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes"></textarea>
                </div>
                
                <div id="returnItems">
                    <!-- Return items will be loaded here -->
                </div>
                
                <button type="submit" class="btn btn-primary">Process Return</button>
            </form>
        </div>
    </div>
    
    <script>
        // Modal functionality
        const modal = document.getElementById('returnModal');
        const closeBtn = document.getElementsByClassName('close')[0];
        
        function showReturnModal(saleId) {
            document.getElementById('sale_id').value = saleId;
            document.getElementById('return_date').value = new Date().toISOString().slice(0, 16);
            
            // Load sale items
            fetch(`get_sale_items.php?sale_id=${saleId}`)
                .then(response => response.json())
                .then(data => {
                    const returnItemsDiv = document.getElementById('returnItems');
                    returnItemsDiv.innerHTML = '';
                    
                    data.forEach(item => {
                        const itemDiv = document.createElement('div');
                        itemDiv.className = 'return-item';
                        itemDiv.innerHTML = `
                            <h4>${item.product_name}</h4>
                            <div class="form-group">
                                <label>Return Quantity:</label>
                                <input type="number" name="return_quantity[${item.id}]" min="0" max="${item.quantity - item.returned_quantity}" required>
                            </div>
                            <div class="form-group">
                                <label>Unit Type:</label>
                                <select name="return_unit_type[${item.id}]" required>
                                    <option value="piece">Piece</option>
                                    <option value="box">Box</option>
                                    <option value="set">Set</option>
                                </select>
                            </div>
                        `;
                        returnItemsDiv.appendChild(itemDiv);
                    });
                });
            
            modal.style.display = 'block';
        }
        
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
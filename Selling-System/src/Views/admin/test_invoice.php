<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Connect to database
$db = new Database();
$conn = $db->getConnection();

// Get a test invoice number
$query = "SELECT invoice_number FROM sales ORDER BY id DESC LIMIT 1";
$stmt = $conn->query($query);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    die("No invoices found in the database");
}

$invoiceNumber = $invoice['invoice_number'];

// Get sale ID
$saleQuery = "SELECT id FROM sales WHERE invoice_number = :invoice_number";
$saleStmt = $conn->prepare($saleQuery);
$saleStmt->bindParam(':invoice_number', $invoiceNumber);
$saleStmt->execute();
$sale = $saleStmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die("Invoice not found");
}

$saleId = $sale['id'];

// Get sale items
$itemsQuery = "SELECT si.*, p.name as product_name, p.code as product_code 
              FROM sale_items si 
              LEFT JOIN products p ON si.product_id = p.id 
              WHERE si.sale_id = :sale_id";
$itemsStmt = $conn->prepare($itemsQuery);
$itemsStmt->bindParam(':sale_id', $saleId);
$itemsStmt->execute();
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Debug information
echo "<h2>Debug Information</h2>";
echo "<pre>";
echo "Invoice Number: " . htmlspecialchars($invoiceNumber) . "\n";
echo "Sale ID: " . $saleId . "\n";
echo "Number of items: " . count($items) . "\n\n";

echo "Items Data:\n";
print_r($items);
echo "</pre>";

// Display items in a table
echo "<h2>Items Table</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr>
        <th>#</th>
        <th>Product Name</th>
        <th>Product Code</th>
        <th>Quantity</th>
        <th>Unit Type</th>
        <th>Unit Price</th>
        <th>Total Price</th>
      </tr>";

foreach ($items as $index => $item) {
    echo "<tr>";
    echo "<td>" . ($index + 1) . "</td>";
    echo "<td>" . htmlspecialchars($item['product_name'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($item['product_code'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($item['quantity'] ?? '0') . "</td>";
    echo "<td>" . htmlspecialchars($item['unit_type'] ?? 'N/A') . "</td>";
    echo "<td>" . number_format($item['unit_price'] ?? 0) . " د.ع</td>";
    echo "<td>" . number_format($item['total_price'] ?? 0) . " د.ع</td>";
    echo "</tr>";
}

echo "</table>";

// Test AJAX request
echo "<h2>Test AJAX Request</h2>";
echo "<button onclick='testAjax()'>Test AJAX</button>";
echo "<div id='ajaxResult'></div>";

?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function testAjax() {
    $.ajax({
        url: '../../includes/get_invoice_items.php',
        type: 'POST',
        data: { invoice_number: '<?php echo $invoiceNumber; ?>' },
        dataType: 'json',
        success: function(response) {
            $('#ajaxResult').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
        },
        error: function(xhr, status, error) {
            $('#ajaxResult').html('Error: ' + error + '<br>Status: ' + status + '<br>Response: ' + xhr.responseText);
        }
    });
}
</script> 
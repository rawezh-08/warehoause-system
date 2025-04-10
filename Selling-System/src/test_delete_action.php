<?php
require_once __DIR__ . '/config/database.php';

// Get customer and supplier IDs
$db = new Database();
$conn = $db->getConnection();

$customers = $conn->query("SELECT id, name FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $conn->query("SELECT id, name FROM suppliers")->fetchAll(PDO::FETCH_ASSOC);

// Process deletion requests
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_customer']) && isset($_POST['customer_id'])) {
        $customerId = (int)$_POST['customer_id'];
        
        try {
            // Check if customer exists
            $checkStmt = $conn->prepare("SELECT id FROM customers WHERE id = ?");
            $checkStmt->execute([$customerId]);
            
            if ($checkStmt->rowCount() === 0) {
                throw new Exception('کڕیار نەدۆزرایەوە');
            }
            
            // Check if customer has debt or sales
            $checkDebtStmt = $conn->prepare("SELECT id FROM debt_transactions WHERE customer_id = ? LIMIT 1");
            $checkDebtStmt->execute([$customerId]);
            
            if ($checkDebtStmt->rowCount() > 0) {
                throw new Exception('ناتوانرێت ئەم کڕیارە بسڕێتەوە چونکە قەرزی هەیە یان فرۆشتنی بۆ تۆمارکراوە');
            }
            
            // Delete customer
            $deleteStmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
            $result = $deleteStmt->execute([$customerId]);
            
            if (!$result) {
                throw new Exception('هەڵەیەک ڕوویدا لە سڕینەوەی کڕیار');
            }
            
            $message = 'کڕیار بە سەرکەوتوویی سڕایەوە';
            $messageType = 'success';
            
            // Refresh customer list
            $customers = $conn->query("SELECT id, name FROM customers")->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
    
    if (isset($_POST['delete_supplier']) && isset($_POST['supplier_id'])) {
        $supplierId = (int)$_POST['supplier_id'];
        
        try {
            // Check if supplier exists
            $checkStmt = $conn->prepare("SELECT id FROM suppliers WHERE id = ?");
            $checkStmt->execute([$supplierId]);
            
            if ($checkStmt->rowCount() === 0) {
                throw new Exception('دابینکەر نەدۆزرایەوە');
            }
            
            // Check if supplier has debt or purchases
            $checkDebtStmt = $conn->prepare("SELECT id FROM supplier_debt_transactions WHERE supplier_id = ? LIMIT 1");
            $checkDebtStmt->execute([$supplierId]);
            
            if ($checkDebtStmt->rowCount() > 0) {
                throw new Exception('ناتوانرێت ئەم دابینکەرە بسڕێتەوە چونکە قەرزی هەیە یان کڕینی لێکراوە');
            }
            
            // Delete supplier
            $deleteStmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
            $result = $deleteStmt->execute([$supplierId]);
            
            if (!$result) {
                throw new Exception('هەڵەیەک ڕوویدا لە سڕینەوەی دابینکەر');
            }
            
            $message = 'دابینکەر بە سەرکەوتوویی سڕایەوە';
            $messageType = 'success';
            
            // Refresh supplier list
            $suppliers = $conn->query("SELECT id, name FROM suppliers")->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تێستی سڕینەوە</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">تێستی سڕینەوەی کڕیار و دابینکەر</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Delete Customer Form -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        سڕینەوەی کڕیار
                    </div>
                    <div class="card-body">
                        <form method="post" onsubmit="return confirm('دڵنیای لە سڕینەوەی ئەم کڕیارە؟');">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">هەڵبژاردنی کڕیار</label>
                                <select class="form-select" id="customer_id" name="customer_id" required>
                                    <option value="">کڕیارێک هەڵبژێرە</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="delete_customer" class="btn btn-danger">سڕینەوە</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Delete Supplier Form -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        سڕینەوەی دابینکەر
                    </div>
                    <div class="card-body">
                        <form method="post" onsubmit="return confirm('دڵنیای لە سڕینەوەی ئەم دابینکەرە؟');">
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">هەڵبژاردنی دابینکەر</label>
                                <select class="form-select" id="supplier_id" name="supplier_id" required>
                                    <option value="">دابینکەرێک هەڵبژێرە</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="delete_supplier" class="btn btn-danger">سڕینەوە</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="staff.php" class="btn btn-secondary">گەڕانەوە بۆ پەڕەی کارمەندان</a>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
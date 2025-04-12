<?php
require_once '../../config/database.php';

// Pagination settings
$records_per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_id = isset($_GET['category']) ? $_GET['category'] : '';
$unit_id = isset($_GET['unit']) ? $_GET['unit'] : '';

try {
    // Build base query
    $query = "SELECT p.*, c.name as category_name, u.name as unit_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              LEFT JOIN units u ON p.unit_id = u.id 
              WHERE 1=1";

    $params = array();

    // Add search conditions
    if (!empty($search)) {
        $query .= " AND (p.name LIKE ? OR p.code LIKE ? OR p.barcode LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    if (!empty($category_id)) {
        $query .= " AND p.category_id = ?";
        $params[] = $category_id;
    }

    if (!empty($unit_id)) {
        $query .= " AND p.unit_id = ?";
        $params[] = $unit_id;
    }

    // Get total count before adding LIMIT
    $count_query = str_replace("p.*, c.name as category_name, u.name as unit_name", "COUNT(*) as total", $query);
    $stmt = $conn->prepare($count_query);
    $stmt->execute($params);
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Add pagination
    $query .= " ORDER BY p.id DESC LIMIT $offset, $records_per_page";

    // Get products
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get categories and units for filters
    $categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $units = $conn->query("SELECT id, name FROM units ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("هەڵە ڕوویدا: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تێستی فلتەرکردنی کاڵاکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">گەڕان</label>
                        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="ناو، کۆد یان بارکۆد...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">جۆری کاڵا</label>
                        <select name="category" class="form-select">
                            <option value="">هەموو جۆرەکان</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">یەکە</label>
                        <select name="unit" class="form-select">
                            <option value="">هەموو یەکەکان</option>
                            <?php foreach ($units as $unit): ?>
                                <option value="<?php echo $unit['id']; ?>" <?php echo $unit_id == $unit['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($unit['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">گەڕان</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Table -->
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ناو</th>
                            <th>کۆد</th>
                            <th>بارکۆد</th>
                            <th>جۆر</th>
                            <th>یەکە</th>
                            <th>نرخی کڕین</th>
                            <th>نرخی فرۆشتن</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $index => $product): ?>
                            <tr>
                                <td><?php echo ($page - 1) * $records_per_page + $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['code']); ?></td>
                                <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['unit_name']); ?></td>
                                <td><?php echo number_format($product['purchase_price'], 0); ?> د.ع</td>
                                <td><?php echo number_format($product['selling_price_single'], 0); ?> د.ع</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_id); ?>&unit=<?php echo urlencode($unit_id); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- Debug Information -->
                <?php if (isset($_GET['debug'])): ?>
                    <div class="mt-4">
                        <h5>Debug Information:</h5>
                        <pre><?php 
                            echo "Query: " . $query . "\n";
                            echo "Parameters: " . print_r($params, true);
                            echo "Total Records: " . $total_records . "\n";
                            echo "Total Pages: " . $total_pages . "\n";
                        ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
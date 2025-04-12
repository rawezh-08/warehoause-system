<?php
require_once '../../config/database.php';

// Create a database connection
$db = new Database();
$conn = $db->getConnection();

// Function to get employee payments data
function getEmployeePaymentsData($filters = []) {
    global $conn;
    
    // Base query to get employee payments
    $sql = "SELECT 
                ep.*,
                e.name as employee_name
            FROM employee_payments ep
            LEFT JOIN employees e ON ep.employee_id = e.id
            WHERE 1=1";
    
    $params = [];
    
    // Apply filters if any
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(ep.payment_date) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(ep.payment_date) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    if (!empty($filters['employee_name'])) {
        $sql .= " AND e.name LIKE :employee_name";
        $params[':employee_name'] = '%' . $filters['employee_name'] . '%';
    }
    
    $sql .= " ORDER BY ep.payment_date DESC";
    
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

// Get initial data without filters
$employeePaymentsData = getEmployeePaymentsData();

// Handle AJAX filter requests
if (isset($_POST['action']) && $_POST['action'] == 'filter') {
    // Set content type to JSON
    header('Content-Type: application/json');
    
    // Build filters
    $filters = [];
    if (isset($_POST['start_date']) && !empty($_POST['start_date'])) {
        $filters['start_date'] = $_POST['start_date'];
    }
    if (isset($_POST['end_date']) && !empty($_POST['end_date'])) {
        $filters['end_date'] = $_POST['end_date'];
    }
    if (isset($_POST['employee_name']) && !empty($_POST['employee_name'])) {
        $filters['employee_name'] = $_POST['employee_name'];
    }
    
    // Get filtered data
    $filteredData = getEmployeePaymentsData($filters);
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'data' => $filteredData
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تێست - پارەدان بە کارمەند</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">تێست - پارەدان بە کارمەند</h1>
        
        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>فلتەر</h5>
            </div>
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-4">
                        <label for="startDate" class="form-label">بەرواری دەستپێک</label>
                        <input type="date" class="form-control" id="startDate" name="start_date">
                    </div>
                    <div class="col-md-4">
                        <label for="endDate" class="form-label">بەرواری کۆتایی</label>
                        <input type="date" class="form-control" id="endDate" name="end_date">
                    </div>
                    <div class="col-md-4">
                        <label for="employeeName" class="form-label">ناوی کارمەند</label>
                        <input type="text" class="form-control" id="employeeName" name="employee_name">
                    </div>
                    <div class="col-12">
                        <button type="button" id="filterBtn" class="btn btn-primary">پەستان</button>
                        <button type="button" id="resetBtn" class="btn btn-secondary">ڕیسێت</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Results Table -->
        <div class="card">
            <div class="card-header">
                <h5>ئەنجامەکان</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ناوی کارمەند</th>
                                <th>بەروار</th>
                                <th>بڕی پارە</th>
                                <th>جۆری پارەدان</th>
                                <th>تێبینی</th>
                            </tr>
                        </thead>
                        <tbody id="resultsTable">
                            <?php foreach ($employeePaymentsData as $index => $payment): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($payment['employee_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('Y/m/d', strtotime($payment['payment_date'])); ?></td>
                                <td><?php echo number_format($payment['amount']) . ' د.ع'; ?></td>
                                <td>
                                    <span class="badge rounded-pill <?php 
                                        echo $payment['payment_type'] == 'salary' ? 'bg-success' : 
                                            ($payment['payment_type'] == 'bonus' ? 'bg-warning' : 'bg-info'); 
                                    ?>">
                                        <?php 
                                        echo $payment['payment_type'] == 'salary' ? 'مووچە' : 
                                            ($payment['payment_type'] == 'bonus' ? 'پاداشت' : 'کاتژمێری زیادە'); 
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($payment['notes'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($employeePaymentsData)): ?>
                            <tr>
                                <td colspan="6" class="text-center">هیچ پارەدانێک نەدۆزرایەوە</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Filter button click
            $('#filterBtn').on('click', function() {
                applyFilter();
            });
            
            // Reset button click
            $('#resetBtn').on('click', function() {
                $('#filterForm')[0].reset();
                applyFilter();
            });
            
            // Apply filter function
            function applyFilter() {
                // Get form data
                const formData = {
                    action: 'filter',
                    start_date: $('#startDate').val(),
                    end_date: $('#endDate').val(),
                    employee_name: $('#employeeName').val()
                };
                
                // Show loading
                $('#resultsTable').html('<tr><td colspan="6" class="text-center">جاوەڕێ بکە...</td></tr>');
                
                // Send AJAX request
                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Response:', response);
                        if (response.success && Array.isArray(response.data)) {
                            updateTable(response.data);
                        } else {
                            $('#resultsTable').html('<tr><td colspan="6" class="text-center">هەڵە لە وەرگرتنی داتا</td></tr>');
                            console.error('Error in response format:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#resultsTable').html('<tr><td colspan="6" class="text-center">هەڵە لە پەیوەندیکردن</td></tr>');
                        console.error('AJAX Error:', xhr.responseText);
                    }
                });
            }
            
            // Update table function
            function updateTable(data) {
                let html = '';
                
                if (data.length === 0) {
                    html = '<tr><td colspan="6" class="text-center">هیچ پارەدانێک نەدۆزرایەوە</td></tr>';
                } else {
                    data.forEach(function(payment, index) {
                        const paymentTypeClass = payment.payment_type === 'salary' ? 'bg-success' : 
                                              (payment.payment_type === 'bonus' ? 'bg-warning' : 'bg-info');
                        
                        const paymentTypeText = payment.payment_type === 'salary' ? 'مووچە' : 
                                             (payment.payment_type === 'bonus' ? 'پاداشت' : 'کاتژمێری زیادە');
                        
                        // Format date to Y/m/d
                        const dateObj = new Date(payment.payment_date);
                        const formattedDate = dateObj.getFullYear() + '/' + 
                                           String(dateObj.getMonth() + 1).padStart(2, '0') + '/' + 
                                           String(dateObj.getDate()).padStart(2, '0');
                        
                        html += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${payment.employee_name || 'N/A'}</td>
                                <td>${formattedDate}</td>
                                <td>${new Intl.NumberFormat().format(payment.amount)} د.ع</td>
                                <td>
                                    <span class="badge rounded-pill ${paymentTypeClass}">
                                        ${paymentTypeText}
                                    </span>
                                </td>
                                <td>${payment.notes || ''}</td>
                            </tr>
                        `;
                    });
                }
                
                $('#resultsTable').html(html);
            }
        });
    </script>
</body>
</html> 
<?php
// Include database connection
require_once('../config/database.php');

header('Content-Type: application/json');

try {
    // Test basic connection
    $testConn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $testConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if stored procedures exist
    $stmt = $testConn->prepare("SHOW PROCEDURE STATUS WHERE Db = ?");
    $stmt->execute([DB_NAME]);
    $procedures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $procedureList = [];
    foreach ($procedures as $proc) {
        $procedureList[] = $proc['Name'];
    }
    
    // Check if specific procedures exist
    $requiredProcedures = [
        'business_pay_supplier',
        'handle_supplier_payment',
        'adjust_supplier_balance'
    ];
    
    $missingProcedures = [];
    foreach ($requiredProcedures as $proc) {
        if (!in_array($proc, $procedureList)) {
            $missingProcedures[] = $proc;
        }
    }
    
    // Test server variables
    $stmt = $testConn->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
    $maxPacket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get MySQL version
    $stmt = $testConn->query("SELECT VERSION() as version");
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'پەیوەندی سەرکەوتوو بوو',
        'database' => [
            'name' => DB_NAME,
            'host' => DB_HOST,
            'user' => DB_USER
        ],
        'procedures' => [
            'available' => $procedureList,
            'missing' => $missingProcedures,
            'status' => empty($missingProcedures) ? 'OK' : 'MISSING'
        ],
        'server' => [
            'version' => $version['version'],
            'max_packet' => $maxPacket['Value']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵە لە پەیوەندی کردن',
        'error' => $e->getMessage(),
        'database' => [
            'name' => DB_NAME,
            'host' => DB_HOST,
            'user' => DB_USER
        ]
    ]);
}
?> 
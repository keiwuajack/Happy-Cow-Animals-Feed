<?php
session_start();
require_once 'config/database.php';

if (PHP_SAPI !== 'cli' && (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin')) {
    http_response_code(403);
    exit('Unauthorized access');
}

echo "=== HAPPY COW FEEDS POS - SYSTEM TEST ===\n";
echo date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 60) . "\n\n";

$tests = [];
$passed = 0;
$failed = 0;
$warnings = 0;

// Test 1: Database Connection
echo "[TEST 1] Database Connection\n";
try {
    $result = query("SELECT 1");
    if ($result) {
        echo "✓ PASSED - Database connected successfully\n";
        $tests[] = ['name' => 'Database Connection', 'status' => 'PASSED'];
        $passed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED - " . $e->getMessage() . "\n";
    $tests[] = ['name' => 'Database Connection', 'status' => 'FAILED'];
    $failed++;
}
echo "\n";

// Test 2: Check critical tables exist
echo "[TEST 2] Database Tables\n";
$critical_tables = ['users', 'customers', 'products', 'categories', 'sales', 'sale_items', 
                   'quotations', 'sales_returns', 'credit_payments', 'expenses'];
$missing_tables = [];
foreach ($critical_tables as $table) {
    $result = query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $missing_tables[] = $table;
    }
}
if (empty($missing_tables)) {
    echo "✓ PASSED - All critical tables exist\n";
    $tests[] = ['name' => 'Database Tables', 'status' => 'PASSED'];
    $passed++;
} else {
    echo "✗ FAILED - Missing tables: " . implode(', ', $missing_tables) . "\n";
    $tests[] = ['name' => 'Database Tables', 'status' => 'FAILED'];
    $failed++;
}
echo "\n";

// Test 3: Users table
echo "[TEST 3] Users Table\n";
$result = query("SELECT COUNT(*) as count FROM users");
$row = $result->fetch_assoc();
if ($row['count'] > 0) {
    echo "✓ PASSED - {$row['count']} user(s) found\n";
    $tests[] = ['name' => 'Users Table', 'status' => 'PASSED'];
    $passed++;
} else {
    echo "✗ FAILED - No users in system\n";
    $tests[] = ['name' => 'Users Table', 'status' => 'FAILED'];
    $failed++;
}
echo "\n";

// Test 4: Products table
echo "[TEST 4] Products Table\n";
$result = query("SELECT COUNT(*) as count FROM products");
$row = $result->fetch_assoc();
if ($row['count'] > 0) {
    echo "✓ PASSED - {$row['count']} product(s) found\n";
    $tests[] = ['name' => 'Products Table', 'status' => 'PASSED'];
    $passed++;
} else {
    echo "⚠ WARNING - No products in system\n";
    $tests[] = ['name' => 'Products Table', 'status' => 'WARNING'];
    $warnings++;
}
echo "\n";

// Test 5: Sales table
echo "[TEST 5] Sales System\n";
$result = query("SELECT COUNT(*) as count, SUM(total) as total FROM sales");
$row = $result->fetch_assoc();
if ($row['count'] > 0) {
    echo "✓ PASSED - {$row['count']} sale(s), Total: KSh " . number_format($row['total'], 2) . "\n";
    $tests[] = ['name' => 'Sales System', 'status' => 'PASSED'];
    $passed++;
} else {
    echo "⚠ WARNING - No sales recorded yet\n";
    $tests[] = ['name' => 'Sales System', 'status' => 'WARNING'];
    $warnings++;
}
echo "\n";

// Test 6: API Files Existence
echo "[TEST 6] Critical API Files\n";
$critical_apis = [
    'api/get_products.php',
    'api/create_sale.php',
    'api/get_customers.php',
    'api/get_quotations.php',
    'api/create_quotation.php',
    'api/get_credit_customers.php',
    'api/record_credit_payment.php',
    'api/inventory_intelligence.php',
    'api/create_purchase_order.php',
    'api/receive_purchase_order.php'
    , 'api/shift_status.php', 'api/open_shift.php', 'api/close_shift.php', 'api/product_batches.php', 'api/save_product_batch.php'
];
$missing_apis = [];
foreach ($critical_apis as $api) {
    if (!file_exists($api)) {
        $missing_apis[] = $api;
    }
}
if (empty($missing_apis)) {
    echo "✓ PASSED - All critical API files exist\n";
    $tests[] = ['name' => 'API Files', 'status' => 'PASSED'];
    $passed++;
} else {
    echo "✗ FAILED - Missing: " . implode(', ', $missing_apis) . "\n";
    $tests[] = ['name' => 'API Files', 'status' => 'FAILED'];
    $failed++;
}
echo "\n";

// Test 7: JavaScript Files
echo "[TEST 7] JavaScript Files\n";
$critical_js = [
    'assets/js/main.js',
    'assets/js/pos.js',
    'assets/js/quotations.js',
    'assets/js/returns.js',
    'assets/js/credit-sales.js',
    'assets/js/barcode-scanner.js'
];
$missing_js = [];
foreach ($critical_js as $js) {
    if (!file_exists($js)) {
        $missing_js[] = $js;
    }
}
if (empty($missing_js)) {
    echo "✓ PASSED - All critical JavaScript files exist\n";
    $tests[] = ['name' => 'JavaScript Files', 'status' => 'PASSED'];
    $passed++;
} else {
    echo "✗ FAILED - Missing: " . implode(', ', $missing_js) . "\n";
    $tests[] = ['name' => 'JavaScript Files', 'status' => 'FAILED'];
    $failed++;
}
echo "\n";

// Test 8: Quotations Module
echo "[TEST 8] Quotations Module\n";
try {
    $result = query("SELECT COUNT(*) as count FROM quotations");
    $row = $result->fetch_assoc();
    echo "✓ PASSED - Quotations table accessible ({$row['count']} records)\n";
    $tests[] = ['name' => 'Quotations Module', 'status' => 'PASSED'];
    $passed++;
} catch (Exception $e) {
    echo "✗ FAILED - " . $e->getMessage() . "\n";
    $tests[] = ['name' => 'Quotations Module', 'status' => 'FAILED'];
    $failed++;
}
echo "\n";

// Test 9: Returns Module
echo "[TEST 9] Returns Module\n";
try {
    $result = query("SELECT COUNT(*) as count FROM sales_returns");
    $row = $result->fetch_assoc();
    echo "✓ PASSED - Returns table accessible ({$row['count']} records)\n";
    $tests[] = ['name' => 'Returns Module', 'status' => 'PASSED'];
    $passed++;
} catch (Exception $e) {
    echo "✗ FAILED - " . $e->getMessage() . "\n";
    $tests[] = ['name' => 'Returns Module', 'status' => 'FAILED'];
    $failed++;
}
echo "\n";

// Test 10: Credit Sales Module
echo "[TEST 10] Credit Sales Module\n";
try {
    $result = query("SELECT COUNT(*) as count FROM credit_payments");
    $row = $result->fetch_assoc();
    echo "✓ PASSED - Credit payments table accessible ({$row['count']} records)\n";
    $tests[] = ['name' => 'Credit Sales Module', 'status' => 'PASSED'];
    $passed++;
} catch (Exception $e) {
    echo "✗ FAILED - " . $e->getMessage() . "\n";
    $tests[] = ['name' => 'Credit Sales Module', 'status' => 'FAILED'];
    $failed++;
}
echo "\n";

// Test 11: Activity Log
echo "[TEST 11] Activity Log\n";
try {
    $result = query("SELECT COUNT(*) as count FROM activity_log");
    $row = $result->fetch_assoc();
    echo "✓ PASSED - Activity log working ({$row['count']} entries)\n";
    $tests[] = ['name' => 'Activity Log', 'status' => 'PASSED'];
    $passed++;
} catch (Exception $e) {
    echo "✗ FAILED - " . $e->getMessage() . "\n";
    $tests[] = ['name' => 'Activity Log', 'status' => 'FAILED'];
    $failed++;
}
echo "\n";

// Test 12: Page Files
echo "[TEST 12] Core Page Files\n";
$core_pages = [
    'index.php', 'pos.php', 'quotations.php', 'returns.php', 
    'credit-sales.php', 'reports.php', 'customers.php', 'purchase-orders.php', 'inventory-intelligence.php', 'cashier-shifts.php', 'product-batches.php'
];
$missing_pages = [];
foreach ($core_pages as $page) {
    if (!file_exists($page)) {
        $missing_pages[] = $page;
    }
}
if (empty($missing_pages)) {
    echo "✓ PASSED - All core pages exist\n";
    $tests[] = ['name' => 'Core Pages', 'status' => 'PASSED'];
    $passed++;
} else {
    echo "✗ FAILED - Missing: " . implode(', ', $missing_pages) . "\n";
    $tests[] = ['name' => 'Core Pages', 'status' => 'FAILED'];
    $failed++;
}
echo "\n";

// Summary
echo str_repeat('=', 60) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat('=', 60) . "\n";
$total = $passed + $failed + $warnings;
echo "Total Tests: $total\n";
echo "✓ Passed: $passed\n";
echo "✗ Failed: $failed\n";
echo "⚠ Warnings: $warnings\n";
$requiredTests = $passed + $failed;
$percentage = $requiredTests > 0 ? round(($passed / $requiredTests) * 100, 2) : 0;
echo "Required Checks Success Rate: $percentage%\n";
echo str_repeat('=', 60) . "\n\n";

if ($percentage >= 90) {
    echo "🎉 EXCELLENT! System is in great shape!\n";
} elseif ($percentage >= 75) {
    echo "✓ GOOD! System is operational with minor issues.\n";
} elseif ($percentage >= 50) {
    echo "⚠ WARNING! System has some issues that need attention.\n";
} else {
    echo "✗ CRITICAL! System has major issues.\n";
}

echo "\nOpen the login page at the URL for this installation.\n";
echo "Sign in using the administrator credentials created during installation.\n";
?>

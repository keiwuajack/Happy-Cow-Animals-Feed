<?php
require_once '../config/pdo.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([
        'products' => [],
        'customers' => [],
        'sales' => []
    ]);
    exit;
}

$searchTerm = "%{$query}%";
$results = [
    'products' => [],
    'customers' => [],
    'sales' => []
];

try {
    // Search Products (limit to 5 results)
    $stmt = $pdo->prepare("
        SELECT id, name, quantity as stock, selling_price as price, sku
        FROM products 
        WHERE name LIKE ? OR sku LIKE ? OR barcode LIKE ?
        ORDER BY name ASC 
        LIMIT 5
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $results['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search Customers (limit to 5 results)
    $stmt = $pdo->prepare("
        SELECT id, name, phone, email
        FROM customers 
        WHERE name LIKE ? OR phone LIKE ? OR email LIKE ?
        ORDER BY name ASC 
        LIMIT 5
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $results['customers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search Sales (limit to 5 results)
    $stmt = $pdo->prepare("
        SELECT s.id, s.invoice_number, s.total as grand_total, s.created_at, c.name as customer_name
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.invoice_number LIKE ? OR c.name LIKE ?
        ORDER BY s.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$searchTerm, $searchTerm]);
    $results['sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
    
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Search failed'
    ]);
}

<?php
require_once '../config/pdo.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$status = isset($_GET['status']) ? $_GET['status'] : null;

try {
    $sql = "SELECT sr.*, s.invoice_number, c.name as customer_name, u.full_name as processed_by_name
            FROM sales_returns sr
            JOIN sales s ON sr.sale_id = s.id
            LEFT JOIN customers c ON sr.customer_id = c.id
            JOIN users u ON sr.processed_by = u.id";
    
    if ($status) {
        $sql .= " WHERE sr.status = ?";
        $stmt = $pdo->prepare($sql . " ORDER BY sr.created_at DESC");
        $stmt->execute([$status]);
    } else {
        $stmt = $pdo->query($sql . " ORDER BY sr.created_at DESC");
    }
    
    $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'returns' => $returns
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

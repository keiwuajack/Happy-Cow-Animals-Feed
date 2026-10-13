<?php
require_once '../config/pdo.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$status = isset($_GET['status']) ? $_GET['status'] : null;

try {
    $sql = "SELECT q.*, c.name as customer_name, c.phone as customer_phone,
            u.full_name as created_by_name
            FROM quotations q
            LEFT JOIN customers c ON q.customer_id = c.id
            LEFT JOIN users u ON q.user_id = u.id";
    
    if ($status) {
        $sql .= " WHERE q.status = ?";
        $stmt = $pdo->prepare($sql . " ORDER BY q.created_at DESC");
        $stmt->execute([$status]);
    } else {
        $stmt = $pdo->query($sql . " ORDER BY q.created_at DESC");
    }
    
    $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'quotations' => $quotations
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

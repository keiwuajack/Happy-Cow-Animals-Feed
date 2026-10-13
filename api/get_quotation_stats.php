<?php
require_once '../config/pdo.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'converted' => 0,
        'total_value' => 0
    ];
    
    // Total quotations
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM quotations");
    $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Pending quotations
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM quotations WHERE status = 'pending'");
    $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Converted quotations
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM quotations WHERE status = 'converted'");
    $stats['converted'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total value
    $stmt = $pdo->query("SELECT SUM(total) as total_value FROM quotations WHERE status = 'pending'");
    $stats['total_value'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

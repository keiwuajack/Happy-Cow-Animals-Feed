<?php
require_once '../config/pdo.php';
header('Content-Type: application/json');
security_require_roles(['admin', 'manager', 'cashier']);
security_require_same_origin();
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$closingCash = filter_var($data['closing_cash'] ?? null, FILTER_VALIDATE_FLOAT);
if ($closingCash === false || $closingCash < 0) { echo json_encode(['success' => false, 'message' => 'Closing cash must be a valid non-negative amount']); exit(); }
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT * FROM cashier_shifts WHERE user_id = ? AND status = 'open' FOR UPDATE"); $stmt->execute([$_SESSION['user_id']]); $shift = $stmt->fetch();
    if (!$shift) throw new Exception('No open shift found');
    $sales = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total ELSE 0 END), 0) cash_sales, COALESCE(SUM(CASE WHEN payment_method = 'mpesa' THEN total ELSE 0 END), 0) mpesa_sales, COALESCE(SUM(CASE WHEN payment_method = 'card' THEN total ELSE 0 END), 0) card_sales, COALESCE(SUM(total), 0) total_sales     FROM sales WHERE user_id = ? AND (shift_id = ? OR (shift_id IS NULL AND created_at >= ?))");
        $sales->execute([$_SESSION['user_id'], $shift['id'], $shift['opened_at']]); $totals = $sales->fetch();
        $expectedCash = (float) $shift['opening_cash'] + (float) $totals['cash_sales']; $variance = round($closingCash - $expectedCash, 2);
        $closeNote = trim($data['notes'] ?? '');
        if ($variance != 0 && $closeNote === '') throw new Exception('Your count does not match the system. Add a note explaining the difference before closing.');
    $update = $pdo->prepare("UPDATE cashier_shifts SET closing_cash = ?, expected_cash = ?, cash_sales = ?, mpesa_sales = ?, card_sales = ?, total_sales = ?, variance = ?, status = 'closed', closed_at = NOW(), notes = ? WHERE id = ?");
    $update->execute([$closingCash, $expectedCash, $totals['cash_sales'], $totals['mpesa_sales'], $totals['card_sales'], $totals['total_sales'], $variance, $closeNote ?: null, $shift['id']]);
    $pdo->commit(); echo json_encode(['success' => true, 'message' => 'Shift closed', 'expected_cash' => $expectedCash, 'variance' => $variance]);
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
?>

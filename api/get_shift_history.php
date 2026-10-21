<?php
require_once '../config/pdo.php';
require_once '../includes/shift_totals.php';
header('Content-Type: application/json');
security_require_roles(['admin', 'manager', 'cashier']);
$sql = "SELECT s.id, s.user_id, u.full_name cashier, s.opening_cash, s.closing_cash, s.expected_cash, s.cash_sales, s.mpesa_sales, s.card_sales, s.total_sales, s.variance, s.status, s.opened_at, s.closed_at, s.notes
        FROM cashier_shifts s JOIN users u ON u.id = s.user_id";
$params = [];
if (($_SESSION['role'] ?? '') === 'cashier') { $sql .= " WHERE s.user_id = ?"; $params[] = $_SESSION['user_id']; }
$stmt = $pdo->prepare($sql . " ORDER BY s.id DESC LIMIT 100");
$stmt->execute($params);
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($shifts as &$shift) {
    if ($shift['status'] === 'open') {
        $totals = cashier_shift_payment_totals($pdo, $shift);
        $shift = array_merge($shift, $totals);
        $shift['expected_cash'] = round((float) $shift['opening_cash'] + $totals['cash_sales'], 2);
    }
}
unset($shift);
echo json_encode(['success' => true, 'shifts' => $shifts]);
?>

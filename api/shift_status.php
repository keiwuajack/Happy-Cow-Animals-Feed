<?php
require_once '../config/pdo.php';
header('Content-Type: application/json');
security_require_roles(['admin', 'manager', 'cashier']);
$stmt = $pdo->prepare("SELECT * FROM cashier_shifts WHERE user_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$shift = $stmt->fetch();
$prev = $pdo->query("SELECT closing_cash FROM cashier_shifts WHERE status = 'closed' AND closing_cash IS NOT NULL ORDER BY closed_at DESC, id DESC LIMIT 1")->fetch();
echo json_encode(['success' => true, 'shift' => $shift ?: null, 'previous_closing' => $prev ? (float) $prev['closing_cash'] : null]);
?>

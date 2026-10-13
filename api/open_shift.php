<?php
require_once '../config/pdo.php';
header('Content-Type: application/json');
security_require_roles(['admin', 'manager', 'cashier']);
security_require_same_origin();
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$openingCash = filter_var($data['opening_cash'] ?? null, FILTER_VALIDATE_FLOAT);
if ($openingCash === false || $openingCash < 0) { echo json_encode(['success' => false, 'message' => 'Opening cash must be a valid non-negative amount']); exit(); }
try {
    $check = $pdo->prepare("SELECT id FROM cashier_shifts WHERE user_id = ? AND status = 'open'"); $check->execute([$_SESSION['user_id']]);
    if ($check->fetch()) throw new Exception('You already have an open shift');
    $pdo->exec("ALTER TABLE cashier_shifts ADD COLUMN IF NOT EXISTS expected_opening DECIMAL(10,2) NULL, ADD COLUMN IF NOT EXISTS opening_variance DECIMAL(10,2) NULL, ADD COLUMN IF NOT EXISTS opening_note TEXT NULL");
    $prev = $pdo->query("SELECT closing_cash FROM cashier_shifts WHERE status = 'closed' AND closing_cash IS NOT NULL ORDER BY closed_at DESC, id DESC LIMIT 1")->fetch();
    $expectedOpening = $prev ? (float) $prev['closing_cash'] : null;
    $openingVariance = $expectedOpening === null ? null : round($openingCash - $expectedOpening, 2);
    $note = trim($data['note'] ?? '') ?: null;
    if ($openingVariance !== null && $openingVariance != 0 && !$note) throw new Exception('Opening cash differs from the previous closing cash by ' . number_format($openingVariance, 2) . '. Add a note explaining the difference.');
    $stmt = $pdo->prepare("INSERT INTO cashier_shifts (user_id, opening_cash, expected_opening, opening_variance, opening_note, status) VALUES (?, ?, ?, ?, ?, 'open')"); $stmt->execute([$_SESSION['user_id'], $openingCash, $expectedOpening, $openingVariance, $note]);
    echo json_encode(['success' => true, 'message' => 'Shift opened', 'shift_id' => $pdo->lastInsertId()]);
} catch (Throwable $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
?>

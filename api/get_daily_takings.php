<?php
require_once '../config/pdo.php';

header('Content-Type: application/json');

$dateFrom = $_GET['start_date'] ?? '';
$dateTo = $_GET['end_date'] ?? '';
$validDate = static function ($value) {
    if (!is_string($value)) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $date : null;
};
$fromDate = $validDate($dateFrom);
$toDate = $validDate($dateTo);

if (!$fromDate || !$toDate || $toDate < $fromDate) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Choose a valid date range']);
    exit();
}

$dateToExclusive = $toDate->modify('+1 day')->format('Y-m-d');
$sql = "SELECT collection_date AS date,
            SUM(cash_received) AS cash_received,
            SUM(mpesa_received) AS mpesa_received,
            SUM(card_bank_received) AS card_bank_received,
            SUM(credit_received) AS credit_received
        FROM (
            SELECT DATE(created_at) AS collection_date,
                SUM(CASE WHEN payment_method = 'cash' THEN LEAST(COALESCE(amount_paid, 0), total) ELSE 0 END) AS cash_received,
                SUM(CASE WHEN payment_method = 'mpesa' THEN LEAST(COALESCE(amount_paid, 0), total) ELSE 0 END) AS mpesa_received,
                SUM(CASE WHEN payment_method = 'card' THEN LEAST(COALESCE(amount_paid, 0), total) ELSE 0 END) AS card_bank_received,
                SUM(CASE WHEN payment_method = 'credit' THEN LEAST(COALESCE(amount_paid, 0), total) ELSE 0 END) AS credit_received
            FROM sales
            WHERE created_at >= ? AND created_at < ?
            GROUP BY DATE(created_at)

            UNION ALL

            SELECT DATE(payment_date) AS collection_date,
                SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END) AS cash_received,
                SUM(CASE WHEN payment_method = 'mpesa' THEN amount ELSE 0 END) AS mpesa_received,
                SUM(CASE WHEN payment_method IN ('card', 'bank') THEN amount ELSE 0 END) AS card_bank_received,
                0 AS credit_received
            FROM credit_payments
            WHERE payment_date >= ? AND payment_date < ?
            GROUP BY DATE(payment_date)
        ) AS collections
        GROUP BY collection_date
        ORDER BY collection_date DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dateFrom, $dateToExclusive, $dateFrom, $dateToExclusive]);
    $rows = $stmt->fetchAll();
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load daily payment totals']);
    exit();
}
$totals = [
    'cash_received' => 0.0,
    'mpesa_received' => 0.0,
    'card_bank_received' => 0.0,
    'credit_received' => 0.0,
    'total_received' => 0.0
];

foreach ($rows as &$row) {
    foreach (['cash_received', 'mpesa_received', 'card_bank_received', 'credit_received'] as $field) {
        $row[$field] = (float) $row[$field];
        $totals[$field] = round($totals[$field] + $row[$field], 2);
    }
    $row['total_received'] = round($row['cash_received'] + $row['mpesa_received']
        + $row['card_bank_received'] + $row['credit_received'], 2);
    $row['date'] = (string) $row['date'];
}
unset($row);
$totals['total_received'] = round($totals['cash_received'] + $totals['mpesa_received']
    + $totals['card_bank_received'] + $totals['credit_received'], 2);

echo json_encode(['success' => true, 'rows' => $rows, 'totals' => $totals]);

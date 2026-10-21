<?php

/**
 * Return payments received by a cashier during a shift.
 *
 * Sales store the amount actually paid, while later payments against credit
 * sales are recorded separately in credit_payments. Card, bank, and credit
 * tender are grouped as other payments for the shift summary.
 */
function cashier_shift_payment_totals(PDO $pdo, array $shift): array
{
    $closedAt = $shift['closed_at'] ?? null;
    $sales = $pdo->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN LEAST(COALESCE(amount_paid, 0), total) ELSE 0 END), 0) AS cash_sales,
            COALESCE(SUM(CASE WHEN payment_method = 'mpesa' THEN LEAST(COALESCE(amount_paid, 0), total) ELSE 0 END), 0) AS mpesa_sales,
            COALESCE(SUM(CASE WHEN payment_method IN ('card', 'credit') THEN LEAST(COALESCE(amount_paid, 0), total) ELSE 0 END), 0) AS card_sales
         FROM sales
         WHERE user_id = ?
           AND (shift_id = ? OR (shift_id IS NULL AND created_at >= ? AND created_at < COALESCE(?, NOW())))"
    );
    $sales->execute([$shift['user_id'], $shift['id'], $shift['opened_at'], $closedAt]);
    $saleTotals = $sales->fetch(PDO::FETCH_ASSOC) ?: [];

    $creditPayments = $pdo->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END), 0) AS cash_sales,
            COALESCE(SUM(CASE WHEN payment_method = 'mpesa' THEN amount ELSE 0 END), 0) AS mpesa_sales,
            COALESCE(SUM(CASE WHEN payment_method IN ('card', 'bank') THEN amount ELSE 0 END), 0) AS card_sales
         FROM credit_payments
         WHERE recorded_by = ? AND payment_date >= ? AND payment_date < COALESCE(?, NOW())"
    );
    $creditPayments->execute([$shift['user_id'], $shift['opened_at'], $closedAt]);
    $creditTotals = $creditPayments->fetch(PDO::FETCH_ASSOC) ?: [];

    $cash = round((float) ($saleTotals['cash_sales'] ?? 0) + (float) ($creditTotals['cash_sales'] ?? 0), 2);
    $mpesa = round((float) ($saleTotals['mpesa_sales'] ?? 0) + (float) ($creditTotals['mpesa_sales'] ?? 0), 2);
    $other = round((float) ($saleTotals['card_sales'] ?? 0) + (float) ($creditTotals['card_sales'] ?? 0), 2);

    return [
        'cash_sales' => $cash,
        'mpesa_sales' => $mpesa,
        'card_sales' => $other,
        'total_sales' => round($cash + $mpesa + $other, 2),
    ];
}

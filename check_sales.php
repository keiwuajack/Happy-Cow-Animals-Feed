<?php
require_once 'config/database.php';

try {
    $result = query('SELECT id, invoice_number, total, created_at FROM sales ORDER BY id DESC LIMIT 5');
    
    if ($result->num_rows == 0) {
        echo "No sales found in database.\n";
        echo "You need to make a sale first using the POS system at: http://localhost/POS/pos.php\n";
    } else {
        echo "Recent Sales:\n";
        echo str_repeat('-', 80) . "\n";
        while ($sale = $result->fetch_assoc()) {
            echo sprintf("ID: %-5s | Invoice: %-15s | Amount: KSh %-10s | Date: %s\n",
                $sale['id'],
                $sale['invoice_number'],
                number_format($sale['total'], 2),
                $sale['created_at']
            );
            $first_id = $first_id ?? $sale['id'];
        }
        echo str_repeat('-', 80) . "\n";
        echo "\nTo view a receipt, visit: http://localhost/POS/receipt.php?id=[ID]\n";
        if (isset($first_id)) {
            echo "Example: http://localhost/POS/receipt.php?id=" . $first_id . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

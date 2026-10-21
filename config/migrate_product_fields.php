<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

require_once __DIR__ . '/pdo.php';

$tableExists = $pdo->query("SHOW TABLES LIKE 'products'")->fetchColumn();
if (!$tableExists) {
    fwrite(STDERR, "Products table not found. Install the database schema first.\n");
    exit(1);
}

$columns = $pdo->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_COLUMN);
$requiredColumns = [
    'barcode' => 'VARCHAR(50) NULL AFTER sku',
    'expiry_date' => 'DATE NULL AFTER description',
    'image' => 'VARCHAR(255) NULL AFTER expiry_date',
];

foreach ($requiredColumns as $column => $definition) {
    if (in_array($column, $columns, true)) {
        echo "Column {$column} already exists.\n";
        continue;
    }

    $pdo->exec("ALTER TABLE products ADD COLUMN `{$column}` {$definition}");
    echo "Added column {$column}.\n";
}

echo "Product fields are ready.\n";

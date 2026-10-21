<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

require_once __DIR__ . '/pdo.php';

$tableExists = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
if (!$tableExists) {
    fwrite(STDERR, "Users table not found. Install the database schema first.\n");
    exit(1);
}

$columns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
if (in_array('permissions_json', $columns, true)) {
    echo "User permissions column already exists.\n";
    exit(0);
}

$pdo->exec('ALTER TABLE users ADD COLUMN permissions_json TEXT NULL AFTER role');
echo "Added user permissions column.\n";

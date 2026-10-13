<?php
require_once 'config/database.php';

echo "SALES TABLE STRUCTURE:\n";
echo str_repeat('=', 60) . "\n";
$result = query('SHOW COLUMNS FROM sales');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}

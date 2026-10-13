<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'agrovet_pos');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Sample expenses for the month
$expenses = [
    ['category' => 'Rent', 'amount' => 15000, 'description' => 'Monthly shop rental'],
    ['category' => 'Utilities', 'amount' => 5000, 'description' => 'Water and electricity'],
    ['category' => 'Staff Salaries', 'amount' => 35000, 'description' => 'Cashier salaries'],
    ['category' => 'Internet', 'amount' => 2000, 'description' => 'Internet and phone bill'],
    ['category' => 'Transport', 'amount' => 8000, 'description' => 'Delivery transportation costs'],
    ['category' => 'Insurance', 'amount' => 6000, 'description' => 'Shop insurance premium'],
    ['category' => 'Marketing', 'amount' => 3000, 'description' => 'Radio and billboard ads'],
    ['category' => 'Maintenance', 'amount' => 4000, 'description' => 'Equipment and shop maintenance'],
];

$added = 0;
$errors = 0;

foreach ($expenses as $expense) {
    $date = date('Y-m-d');
    $sql = "INSERT INTO expenses (date, category, amount, description) 
            VALUES ('$date', '" . $conn->real_escape_string($expense['category']) . "', " . $expense['amount'] . ", '" . $conn->real_escape_string($expense['description']) . "')";
    
    if ($conn->query($sql) === TRUE) {
        $added++;
        echo "✓ Added: " . $expense['category'] . " - KSh " . number_format($expense['amount'], 2) . "<br>";
    } else {
        $errors++;
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

$total = array_sum(array_column($expenses, 'amount'));
echo "<br><strong>Summary: $added expenses added (Total: KSh " . number_format($total, 2) . "), $errors errors</strong><br>";
$conn->close();
?>

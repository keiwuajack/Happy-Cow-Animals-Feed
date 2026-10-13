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

// Tax rates configuration
$tax_rates = [
    ['name' => 'Standard VAT', 'rate' => 16, 'is_default' => 1],
    ['name' => 'Zero Rated', 'rate' => 0, 'is_default' => 0],
    ['name' => 'Agricultural Inputs', 'rate' => 8, 'is_default' => 0],
];

echo "<h3>Configuring Tax Rates</h3>";
$added = 0;

foreach ($tax_rates as $tax) {
    $sql = "INSERT INTO tax_rates (name, rate, is_default, is_active) 
            VALUES ('" . $conn->real_escape_string($tax['name']) . "', " . $tax['rate'] . ", " . $tax['is_default'] . ", 1)";
    
    if ($conn->query($sql) === TRUE) {
        $added++;
        echo "✓ Added Tax Rate: " . $tax['name'] . " - " . $tax['rate'] . "%<br>";
    } else {
        // Check if already exists
        if (strpos($conn->error, 'Duplicate') !== false) {
            echo "! Tax rate already exists: " . $tax['name'] . "<br>";
        } else {
            echo "✗ Error: " . $conn->error . "<br>";
        }
    }
}

echo "<br><strong>Tax Rates: $added added</strong><br>";
$conn->close();
?>

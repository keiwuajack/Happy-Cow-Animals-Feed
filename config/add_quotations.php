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

// Get customers and products
$customers = $conn->query("SELECT id FROM customers LIMIT 8");
$products = $conn->query("SELECT id, selling_price FROM products LIMIT 10");

$customer_ids = [];
$product_data = [];

while ($row = $customers->fetch_assoc()) {
    $customer_ids[] = $row['id'];
}

while ($row = $products->fetch_assoc()) {
    $product_data[] = $row;
}

// Sample quotations
echo "<h3>Creating Sample Quotations</h3>";
$quotation_num = 2000;
$quotations_created = 0;

for ($i = 0; $i < 5; $i++) {
    $customer_id = $customer_ids[rand(0, count($customer_ids) - 1)];
    $num_items = rand(2, 5);
    $subtotal = 0;
    $quote_items = [];

    // Generate random items (bulk quantities)
    for ($j = 0; $j < $num_items; $j++) {
        $product = $product_data[rand(0, count($product_data) - 1)];
        $quantity = rand(20, 100); // Bulk quantities
        $item_total = $product['selling_price'] * $quantity * 0.9; // 10% bulk discount
        $subtotal += $item_total;
        
        $quote_items[] = [
            'product_id' => $product['id'],
            'quantity' => $quantity,
            'unit_price' => $product['selling_price'] * 0.9,
            'total' => $item_total
        ];
    }

    $tax = $subtotal * 0.16;
    $total = $subtotal + $tax;
    $quotation_num++;
    $valid_until = date('Y-m-d', strtotime('+14 days'));

    // Insert quotation
    $quote_sql = "INSERT INTO quotations (quote_number, customer_id, user_id, subtotal, discount, tax, total, valid_until, status) 
                  VALUES ('QT-$quotation_num', $customer_id, 1, $subtotal, 0, $tax, $total, '$valid_until', 'pending')";
    
    if ($conn->query($quote_sql) === TRUE) {
        $quote_id = $conn->insert_id;
        $quotations_created++;

        // Insert quotation items
        foreach ($quote_items as $item) {
            $item_sql = "INSERT INTO quotation_items (quotation_id, product_id, quantity, unit_price, total) 
                         VALUES ($quote_id, " . $item['product_id'] . ", " . $item['quantity'] . ", " . $item['unit_price'] . ", " . $item['total'] . ")";
            $conn->query($item_sql);
        }

        echo "✓ Quotation QT-$quotation_num - Customer ID: $customer_id - Items: $num_items - Total: KSh " . number_format($total, 2) . " - Valid Until: $valid_until<br>";
    }
}

echo "<br><strong>Quotations: $quotations_created created</strong><br>";
$conn->close();
?>

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

// First, update existing customers with credit limits and mark as credit customers
$credit_customers = [
    ['id' => 1, 'limit' => 50000, 'type' => 'dealer'],
    ['id' => 2, 'limit' => 75000, 'type' => 'dealer'],
    ['id' => 3, 'limit' => 100000, 'type' => 'farmer'],
    ['id' => 4, 'limit' => 60000, 'type' => 'dealer'],
    ['id' => 5, 'limit' => 80000, 'type' => 'dealer'],
];

echo "<h3>Updating Credit Customers</h3>";
foreach ($credit_customers as $customer) {
    $sql = "UPDATE customers SET credit_limit = " . $customer['limit'] . ", customer_type = '" . $customer['type'] . "' WHERE id = " . $customer['id'];
    if ($conn->query($sql) === TRUE) {
        echo "✓ Customer ID " . $customer['id'] . " - Credit Limit: KSh " . number_format($customer['limit'], 2) . "<br>";
    } else {
        echo "✗ Error updating customer: " . $conn->error . "<br>";
    }
}

// Get customer and product IDs for credit sales
$customers = $conn->query("SELECT id FROM customers WHERE id IN (1,2,3,4,5)");
$products = $conn->query("SELECT id, selling_price FROM products LIMIT 15");

$customer_ids = [];
$product_data = [];

while ($row = $customers->fetch_assoc()) {
    $customer_ids[] = $row['id'];
}

while ($row = $products->fetch_assoc()) {
    $product_data[] = $row;
}

// Create 5 credit sales
echo "<h3>Creating Credit Sales</h3>";
$invoice_num = 5000;
$sales_created = 0;

for ($i = 0; $i < 5; $i++) {
    $customer_id = $customer_ids[$i % count($customer_ids)];
    $num_items = rand(2, 4);
    $subtotal = 0;
    $sale_items = [];

    // Generate random items
    for ($j = 0; $j < $num_items; $j++) {
        $product = $product_data[rand(0, count($product_data) - 1)];
        $quantity = rand(2, 8);
        $item_total = $product['selling_price'] * $quantity;
        $subtotal += $item_total;
        
        $sale_items[] = [
            'product_id' => $product['id'],
            'quantity' => $quantity,
            'unit_price' => $product['selling_price'],
            'total' => $item_total
        ];
    }

    $tax = $subtotal * 0.16;
    $total = $subtotal + $tax;
    $amount_paid = $total * 0.3; // Customer pays 30% upfront
    $invoice_num++;

    // Insert credit sale
    $sale_sql = "INSERT INTO sales (invoice_number, customer_id, user_id, subtotal, tax, discount, total, amount_paid, payment_method, payment_status) 
                 VALUES ('INV-$invoice_num', $customer_id, 1, $subtotal, $tax, 0, $total, $amount_paid, 'credit', 'partial')";
    
    if ($conn->query($sale_sql) === TRUE) {
        $sale_id = $conn->insert_id;
        $sales_created++;

        // Insert sale items
        foreach ($sale_items as $item) {
            $item_sql = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal) 
                         VALUES ($sale_id, " . $item['product_id'] . ", " . $item['quantity'] . ", " . $item['unit_price'] . ", " . $item['total'] . ")";
            $conn->query($item_sql);
        }

        // Record credit payment (partial)
        $credit_sql = "INSERT INTO credit_payments (customer_id, sale_id, amount, payment_method, recorded_by) 
                       VALUES ($customer_id, $sale_id, $amount_paid, 'cash', 1)";
        $conn->query($credit_sql);

        $balance = $total - $amount_paid;
        echo "✓ Credit Sale #INV-$invoice_num - Customer ID: $customer_id - Total: KSh " . number_format($total, 2) . " | Paid: KSh " . number_format($amount_paid, 2) . " | Balance: KSh " . number_format($balance, 2) . "<br>";
    }
}

echo "<br><strong>Credit Sales: $sales_created created with partial payments</strong><br>";
$conn->close();
?>

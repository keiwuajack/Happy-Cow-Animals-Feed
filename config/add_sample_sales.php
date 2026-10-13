<?php
// Sample sales transactions
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'agrovet_pos');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Get customer and product IDs
$customers = $conn->query("SELECT id FROM customers LIMIT 10");
$products = $conn->query("SELECT id, selling_price FROM products");

$customer_ids = [];
$product_data = [];

while ($row = $customers->fetch_assoc()) {
    $customer_ids[] = $row['id'];
}

while ($row = $products->fetch_assoc()) {
    $product_data[] = $row;
}

if (empty($customer_ids) || empty($product_data)) {
    die("No customers or products found!");
}

// Create 10 sample sales today
$sales_added = 0;
$items_added = 0;
$invoice_num = 1000;

for ($i = 0; $i < 10; $i++) {
    $customer_id = $customer_ids[rand(0, count($customer_ids) - 1)];
    $num_items = rand(1, 4);
    $subtotal = 0;
    $sale_items = [];

    // Generate random items for this sale
    for ($j = 0; $j < $num_items; $j++) {
        $product = $product_data[rand(0, count($product_data) - 1)];
        $quantity = rand(1, 5);
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
    $invoice_num++;

    // Insert sale
    $sale_sql = "INSERT INTO sales (invoice_number, customer_id, user_id, subtotal, tax, total, amount_paid, payment_method, payment_status) 
                 VALUES ('INV-$invoice_num', $customer_id, 1, $subtotal, $tax, $total, $total, 'cash', 'paid')";
    
    if ($conn->query($sale_sql) === TRUE) {
        $sale_id = $conn->insert_id;
        $sales_added++;

        // Insert sale items
        foreach ($sale_items as $item) {
            $item_sql = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal) 
                         VALUES ($sale_id, " . $item['product_id'] . ", " . $item['quantity'] . ", " . $item['unit_price'] . ", " . $item['total'] . ")";
            
            if ($conn->query($item_sql) === TRUE) {
                $items_added++;
                // Update product stock
                $update_sql = "UPDATE products SET quantity = quantity - " . $item['quantity'] . " WHERE id = " . $item['product_id'];
                $conn->query($update_sql);
            }
        }
        
        echo "✓ Sale #$sale_id created with " . count($sale_items) . " items (Total: KSh " . number_format($total, 2) . ")<br>";
    } else {
        echo "✗ Error creating sale: " . $conn->error . "<br>";
    }
}

echo "<br><strong>Summary: $sales_added sales created with $items_added total items</strong><br>";
$conn->close();
?>

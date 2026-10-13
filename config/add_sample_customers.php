<?php
// Sample customers for the agrovet business
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'agrovet_pos');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Sample customers
$customers = [
    ['name' => 'John Kipchoge Farm', 'email' => 'john.kipchoge@farm.com', 'phone' => '0712345678', 'address' => 'Eldoret, Uasin Gishu'],
    ['name' => 'Mary Wanjiru Trading', 'email' => 'mary.wanjiru@trading.com', 'phone' => '0723456789', 'address' => 'Nairobi, Karen'],
    ['name' => 'Samuel Koech Agro', 'email' => 'samuel.koech@agro.com', 'phone' => '0734567890', 'address' => 'Kisii, Gusii'],
    ['name' => 'Grace Muthoni Nursery', 'email' => 'grace.muthoni@nursery.com', 'phone' => '0745678901', 'address' => 'Nakuru, Menengai'],
    ['name' => 'Peter Ochieng Poultry', 'email' => 'peter.ochieng@poultry.com', 'phone' => '0756789012', 'address' => 'Kisumu, Nyanza'],
    ['name' => 'Alice Kiplagat Dairy', 'email' => 'alice.kiplagat@dairy.com', 'phone' => '0767890123', 'address' => 'Kericho, Bomet'],
    ['name' => 'David Karanja Trading', 'email' => 'david.karanja@trading.com', 'phone' => '0778901234', 'address' => 'Muranga, Central'],
    ['name' => 'Jane Kipchoge Vegetables', 'email' => 'jane.kipchoge@veg.com', 'phone' => '0789012345', 'address' => 'Kiambu, Gatundu'],
    ['name' => 'Joseph Kiplagat Farm Store', 'email' => 'joseph.kiplagat@farm.com', 'phone' => '0790123456', 'address' => 'Nandi, Kapsabet'],
    ['name' => 'Ruth Wambui Supplies', 'email' => 'ruth.wambui@supplies.com', 'phone' => '0701234567', 'address' => 'Machakos, Athi River'],
    ['name' => 'Moses Kipchoge Cooperative', 'email' => 'moses.kipchoge@coop.com', 'phone' => '0712345679', 'address' => 'Laikipia, Nyahururu'],
    ['name' => 'Victoria Kiplagat Boutique', 'email' => 'victoria.kiplagat@boutique.com', 'phone' => '0723456780', 'address' => 'Rongai, Kajiado'],
    ['name' => 'Christopher Koech Farms', 'email' => 'christopher.koech@farms.com', 'phone' => '0734567891', 'address' => 'Mombasa, Digo Road'],
    ['name' => 'Patricia Ochieng Market', 'email' => 'patricia.ochieng@market.com', 'phone' => '0745678902', 'address' => 'Nakuru Market'],
    ['name' => 'Henry Kipchoge Estate', 'email' => 'henry.kipchoge@estate.com', 'phone' => '0756789013', 'address' => 'Arusha, Tanzania'],
];

$added = 0;
$errors = 0;

foreach ($customers as $customer) {
    $sql = "INSERT INTO customers (name, email, phone, address, customer_type) 
            VALUES ('" . $conn->real_escape_string($customer['name']) . "', '" . $conn->real_escape_string($customer['email']) . "', '" . $conn->real_escape_string($customer['phone']) . "', '" . $conn->real_escape_string($customer['address']) . "', 'farmer')";
    
    if ($conn->query($sql) === TRUE) {
        $added++;
        echo "✓ Added: " . $customer['name'] . "<br>";
    } else {
        $errors++;
        echo "✗ Error adding " . $customer['name'] . ": " . $conn->error . "<br>";
    }
}

echo "<br><strong>Summary: $added customers added, $errors errors</strong><br>";
$conn->close();
?>

<?php
// Sample suppliers for the agrovet business
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'agrovet_pos');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Sample suppliers
$suppliers = [
    ['name' => 'Kenya Seeds Company', 'contact' => 'John Mwangi', 'email' => 'supply@kenyaseeds.com', 'phone' => '0716789012', 'address' => 'Nairobi, Karen'],
    ['name' => 'East Africa Chemicals Ltd', 'contact' => 'Sarah Kipchoge', 'email' => 'info@eachems.com', 'phone' => '0727890123', 'address' => 'Nairobi, Westlands'],
    ['name' => 'Farmers Choice Feeds', 'contact' => 'Peter Ochieng', 'email' => 'sales@farmerschoice.com', 'phone' => '0738901234', 'address' => 'Eldoret, Uasin Gishu'],
    ['name' => 'Agro Solutions Limited', 'contact' => 'Grace Muthoni', 'email' => 'contact@agrosol.com', 'phone' => '0749012345', 'address' => 'Nakuru, Menengai'],
    ['name' => 'Vet Pharma Kenya', 'contact' => 'David Karanja', 'email' => 'supply@vetpharma.co.ke', 'phone' => '0750123456', 'address' => 'Nairobi, Industrial Area'],
    ['name' => 'International Chemicals', 'contact' => 'Mary Kiplagat', 'email' => 'intl@chemkenya.com', 'phone' => '0761234567', 'address' => 'Mombasa, Port'],
    ['name' => 'Agricultural Tools Distributors', 'contact' => 'Joseph Kipchoge', 'email' => 'tools@agridist.com', 'phone' => '0772345678', 'address' => 'Kiambu, Thika'],
    ['name' => 'Irrigation Systems Africa', 'contact' => 'Ruth Wambui', 'email' => 'info@irriafrica.com', 'phone' => '0783456789', 'address' => 'Nairobi, Ngong'],
];

$added = 0;
$errors = 0;

foreach ($suppliers as $supplier) {
    $sql = "INSERT INTO suppliers (name, contact_person, email, phone, address) 
            VALUES ('" . $conn->real_escape_string($supplier['name']) . "', '" . $conn->real_escape_string($supplier['contact']) . "', '" . $conn->real_escape_string($supplier['email']) . "', '" . $conn->real_escape_string($supplier['phone']) . "', '" . $conn->real_escape_string($supplier['address']) . "')";
    
    if ($conn->query($sql) === TRUE) {
        $added++;
        echo "✓ Added: " . $supplier['name'] . "<br>";
    } else {
        $errors++;
        echo "✗ Error adding " . $supplier['name'] . ": " . $conn->error . "<br>";
    }
}

echo "<br><strong>Summary: $added suppliers added, $errors errors</strong><br>";
$conn->close();
?>

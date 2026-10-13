<?php
// Sample data insertion script
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'agrovet_pos');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Sample products data
$products = [
    // Seeds & Planting (Category 1)
    ['Maize Seeds - DH04', 1, 'SEED001', 'High yield maize seeds, 2kg pack', 'kg', 450, 650, 50, 10],
    ['Hybrid Tomato Seeds', 1, 'SEED002', 'Disease resistant tomato seeds', 'pcs', 250, 350, 30, 5],
    ['Cabbage Seeds', 1, 'SEED003', 'Premium cabbage seeds', 'pcs', 180, 280, 25, 5],
    ['Bean Seeds - Red Haricot', 1, 'SEED004', 'Quality bean seeds, 1kg', 'kg', 200, 300, 40, 10],
    
    // Fertilizers (Category 2)
    ['DAP Fertilizer 50kg', 2, 'FERT001', 'Di-Ammonium Phosphate fertilizer', 'bag', 3500, 4200, 20, 5],
    ['CAN Fertilizer 50kg', 2, 'FERT002', 'Calcium Ammonium Nitrate', 'bag', 2800, 3500, 25, 5],
    ['NPK 17:17:17 50kg', 2, 'FERT003', 'Balanced NPK fertilizer', 'bag', 3200, 4000, 15, 5],
    ['Organic Compost 25kg', 2, 'FERT004', 'Well decomposed organic manure', 'bag', 400, 600, 50, 10],
    
    // Pesticides (Category 3)
    ['Duduthrin 1L', 3, 'PEST001', 'Broad spectrum insecticide', 'ltr', 850, 1200, 20, 5],
    ['Malathion 500ml', 3, 'PEST002', 'Contact insecticide', 'ltr', 450, 650, 30, 5],
    ['Roundup 1L', 3, 'PEST003', 'Glyphosate herbicide', 'ltr', 950, 1350, 25, 5],
    ['Fungicide - Milraz', 3, 'PEST004', 'Fungal disease control', 'kg', 600, 850, 15, 5],
    
    // Animal Feed (Category 4)
    ['Dairy Meal 70kg', 4, 'FEED001', 'High protein dairy cattle feed', 'bag', 2200, 2800, 30, 5],
    ['Layers Mash 70kg', 4, 'FEED002', 'Complete feed for laying hens', 'bag', 2400, 3000, 25, 5],
    ['Pig Grower 70kg', 4, 'FEED003', 'Pig fattening feed', 'bag', 2300, 2900, 20, 5],
    ['Calf Starter 50kg', 4, 'FEED004', 'Young calf feed supplement', 'bag', 3500, 4200, 15, 5],
    
    // Veterinary Supplies (Category 5)
    ['Multivitamins 1L', 5, 'VET001', 'Animal multivitamin supplement', 'ltr', 800, 1200, 20, 5],
    ['Dewormer - Albendazole', 5, 'VET002', 'Broad spectrum dewormer', 'ltr', 1200, 1800, 15, 5],
    ['Antibiotics - Penstrep', 5, 'VET003', 'Penicillin & Streptomycin injection', 'pcs', 450, 700, 25, 5],
    ['Tick Spray 1L', 5, 'VET004', 'Acaricide for tick control', 'ltr', 650, 950, 30, 5],
    
    // Farm Tools (Category 6)
    ['Jembe (Hoe)', 6, 'TOOL001', 'Heavy duty farming hoe', 'pcs', 450, 650, 40, 10],
    ['Panga (Machete)', 6, 'TOOL002', 'Sharp farming machete', 'pcs', 350, 550, 35, 10],
    ['Watering Can 10L', 6, 'TOOL003', 'Plastic watering can', 'pcs', 280, 450, 30, 10],
    ['Garden Fork', 6, 'TOOL004', 'Steel garden fork', 'pcs', 550, 800, 20, 5],
    
    // Irrigation (Category 7)
    ['Drip Irrigation Kit', 7, 'IRR001', 'Complete drip irrigation system', 'pcs', 3500, 5000, 10, 3],
    ['PVC Pipes 1/2 inch', 7, 'IRR002', '6 meters length', 'pcs', 250, 400, 50, 10],
    ['Sprinkler Head', 7, 'IRR003', 'Adjustable sprinkler', 'pcs', 180, 300, 40, 10],
    ['Water Timer', 7, 'IRR004', 'Automatic irrigation timer', 'pcs', 1200, 1800, 15, 5],
];

// Insert products
$inserted = 0;
foreach ($products as $product) {
    $sql = "INSERT INTO products (name, category_id, sku, description, unit, cost_price, selling_price, quantity, reorder_level) 
            VALUES ('{$product[0]}', {$product[1]}, '{$product[2]}', '{$product[3]}', '{$product[4]}', {$product[5]}, {$product[6]}, {$product[7]}, {$product[8]})";
    
    if ($conn->query($sql)) {
        $inserted++;
    }
}

// Sample customers
$customers = [
    ['John Kamau', '0712345678', 'john@farm.com', 'farmer', 'Kiambu, Kenya', '10 acres', 50000],
    ['Mary Wanjiku', '0723456789', 'mary@email.com', 'farmer', 'Nakuru, Kenya', '5 acres', 30000],
    ['Peter Ochieng', '0734567890', 'peter@farm.com', 'dealer', 'Kisumu, Kenya', 'N/A', 100000],
    ['Grace Akinyi', '0745678901', 'grace@email.com', 'farmer', 'Eldoret, Kenya', '15 acres', 70000],
    ['David Mwangi', '0756789012', 'david@farm.com', 'individual', 'Nairobi, Kenya', '2 acres', 20000],
];

$customerCount = 0;
foreach ($customers as $customer) {
    $sql = "INSERT INTO customers (name, phone, email, customer_type, address, farm_size, credit_limit) 
            VALUES ('{$customer[0]}', '{$customer[1]}', '{$customer[2]}', '{$customer[3]}', '{$customer[4]}', '{$customer[5]}', {$customer[6]})";
    
    if ($conn->query($sql)) {
        $customerCount++;
    }
}

// Sample suppliers
$suppliers = [
    ['Agro Kenya Ltd', 'James Kariuki', '0722111222', 'info@agrokenya.com', 'Industrial Area, Nairobi'],
    ['Farm Inputs Suppliers', 'Sarah Njeri', '0733222333', 'sales@farminputs.com', 'Nakuru Town'],
    ['Vet Supplies Co', 'Dr. John Omondi', '0744333444', 'vet@supplies.com', 'Eldoret'],
    ['Seeds & More Ltd', 'Alice Wambui', '0755444555', 'seeds@more.com', 'Meru'],
];

$supplierCount = 0;
foreach ($suppliers as $supplier) {
    $sql = "INSERT INTO suppliers (name, contact_person, phone, email, address) 
            VALUES ('{$supplier[0]}', '{$supplier[1]}', '{$supplier[2]}', '{$supplier[3]}', '{$supplier[4]}')";
    
    if ($conn->query($sql)) {
        $supplierCount++;
    }
}

echo "<h2>Sample Data Inserted Successfully!</h2>";
echo "<p>✅ Products inserted: $inserted</p>";
echo "<p>✅ Customers inserted: $customerCount</p>";
echo "<p>✅ Suppliers inserted: $supplierCount</p>";
echo "<br><a href='../login.php' style='padding: 10px 20px; background: #2ecc71; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";

$conn->close();
?>

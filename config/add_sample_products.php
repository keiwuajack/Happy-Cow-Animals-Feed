<?php
// Sample products for the agrovet business
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'agrovet_pos');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Sample products with category mapping
$products = [
    // Animal Feed
    ['name' => 'Chicken Layers Mash', 'category_id' => 1, 'sku' => 'CHKN-LAY-001', 'cost' => 2800, 'price' => 3200, 'stock' => 50, 'unit' => 'Bags', 'desc' => 'High quality feed for laying hens'],
    ['name' => 'Broiler Starter Feed', 'category_id' => 1, 'sku' => 'BROI-STR-001', 'cost' => 3200, 'price' => 3800, 'stock' => 40, 'unit' => 'Bags', 'desc' => 'Specialized feed for broiler chicks'],
    ['name' => 'Dairy Concentrate', 'category_id' => 1, 'sku' => 'DAIR-CON-001', 'cost' => 4500, 'price' => 5200, 'stock' => 30, 'unit' => 'Bags', 'desc' => 'Protein-rich feed for dairy cows'],
    ['name' => 'Goat Pellets', 'category_id' => 1, 'sku' => 'GOAT-PEL-001', 'cost' => 2200, 'price' => 2800, 'stock' => 35, 'unit' => 'Bags', 'desc' => 'Complete nutrition for goats'],

    // Fertilizers
    ['name' => 'DAP Fertilizer', 'category_id' => 3, 'sku' => 'FERT-DAP-001', 'cost' => 3500, 'price' => 4200, 'stock' => 100, 'unit' => 'Bags', 'desc' => 'Diammonium Phosphate for crops'],
    ['name' => 'NPK 17-17-17', 'category_id' => 3, 'sku' => 'FERT-NPK-001', 'cost' => 2800, 'price' => 3400, 'stock' => 80, 'unit' => 'Bags', 'desc' => 'Balanced fertilizer for general crops'],
    ['name' => 'Urea Fertilizer', 'category_id' => 3, 'sku' => 'FERT-URE-001', 'cost' => 3000, 'price' => 3600, 'stock' => 90, 'unit' => 'Bags', 'desc' => 'Nitrogen-rich fertilizer'],
    ['name' => 'Calcium Nitrate', 'category_id' => 3, 'sku' => 'FERT-CAL-001', 'cost' => 4000, 'price' => 4800, 'stock' => 50, 'unit' => 'Bags', 'desc' => 'For tomatoes and vegetables'],

    // Seeds & Planting
    ['name' => 'Maize Seed (Hybrid)', 'category_id' => 6, 'sku' => 'SEED-MAZ-001', 'cost' => 150, 'price' => 200, 'stock' => 500, 'unit' => 'Pieces', 'desc' => 'High-yield hybrid maize variety'],
    ['name' => 'Tomato Seed', 'category_id' => 6, 'sku' => 'SEED-TOM-001', 'cost' => 80, 'price' => 120, 'stock' => 300, 'unit' => 'Pieces', 'desc' => 'Quality tomato seeds'],
    ['name' => 'Cabbage Seed', 'category_id' => 6, 'sku' => 'SEED-CAB-001', 'cost' => 120, 'price' => 180, 'stock' => 250, 'unit' => 'Pieces', 'desc' => 'Cabbage planting seeds'],
    ['name' => 'Onion Bulbs', 'category_id' => 6, 'sku' => 'SEED-ONI-001', 'cost' => 50, 'price' => 80, 'stock' => 400, 'unit' => 'Pieces', 'desc' => 'Quality onion bulbs for planting'],

    // Pesticides
    ['name' => 'Mancozeb 80% WP', 'category_id' => 5, 'sku' => 'PEST-MAN-001', 'cost' => 800, 'price' => 1200, 'stock' => 60, 'unit' => 'Kilograms', 'desc' => 'Fungicide for crop protection'],
    ['name' => 'Cypermethrin 25% EC', 'category_id' => 5, 'sku' => 'PEST-CYP-001', 'cost' => 1200, 'price' => 1800, 'stock' => 40, 'unit' => 'Liters', 'desc' => 'Insecticide for general use'],
    ['name' => 'Glyphosate 41% SL', 'category_id' => 5, 'sku' => 'PEST-GLY-001', 'cost' => 600, 'price' => 1000, 'stock' => 50, 'unit' => 'Liters', 'desc' => 'Herbicide for weed control'],
    ['name' => 'Paraquat 20% SL', 'category_id' => 5, 'sku' => 'PEST-PAR-001', 'cost' => 700, 'price' => 1100, 'stock' => 45, 'unit' => 'Liters', 'desc' => 'Fast-acting herbicide'],

    // Veterinary Supplies
    ['name' => 'Amoxicillin 250mg', 'category_id' => 7, 'sku' => 'VET-AMO-001', 'cost' => 20, 'price' => 35, 'stock' => 200, 'unit' => 'Pieces', 'desc' => 'Antibiotic tablet for livestock'],
    ['name' => 'Oxytetracycline 200mg', 'category_id' => 7, 'sku' => 'VET-OXY-001', 'cost' => 25, 'price' => 45, 'stock' => 150, 'unit' => 'Pieces', 'desc' => 'Broad-spectrum antibiotic'],
    ['name' => 'Vitamins A,D,E Premix', 'category_id' => 7, 'sku' => 'VET-VIT-001', 'cost' => 2500, 'price' => 3500, 'stock' => 30, 'unit' => 'Kilograms', 'desc' => 'Vitamin supplement for animals'],
    ['name' => 'Fenbendazole Powder', 'category_id' => 7, 'sku' => 'VET-FEN-001', 'cost' => 1500, 'price' => 2200, 'stock' => 25, 'unit' => 'Kilograms', 'desc' => 'Dewormer for livestock'],

    // Farm Tools
    ['name' => 'Garden Hoe', 'category_id' => 2, 'sku' => 'TOOL-HOE-001', 'cost' => 500, 'price' => 750, 'stock' => 40, 'unit' => 'Pieces', 'desc' => 'Durable garden hoe for weeding'],
    ['name' => 'Digging Fork', 'category_id' => 2, 'sku' => 'TOOL-FOR-001', 'cost' => 600, 'price' => 900, 'stock' => 35, 'unit' => 'Pieces', 'desc' => 'Heavy-duty digging fork'],
    ['name' => 'Pruning Shears', 'category_id' => 2, 'sku' => 'TOOL-PRU-001', 'cost' => 800, 'price' => 1200, 'stock' => 50, 'unit' => 'Pieces', 'desc' => 'Professional pruning shears'],
    ['name' => 'Watering Can (10L)', 'category_id' => 2, 'sku' => 'TOOL-WAT-001', 'cost' => 400, 'price' => 600, 'stock' => 60, 'unit' => 'Pieces', 'desc' => '10-liter capacity watering can'],

    // Irrigation
    ['name' => 'Drip Tape (500m)', 'category_id' => 4, 'sku' => 'IRRI-TAP-001', 'cost' => 3000, 'price' => 4500, 'stock' => 20, 'unit' => 'Rolls', 'desc' => 'Premium drip irrigation tape'],
    ['name' => 'Sprinkler Head', 'category_id' => 4, 'sku' => 'IRRI-SPR-001', 'cost' => 150, 'price' => 250, 'stock' => 200, 'unit' => 'Pieces', 'desc' => 'Adjustable sprinkler head'],
    ['name' => 'Water Pump (1HP)', 'category_id' => 4, 'sku' => 'IRRI-PUM-001', 'cost' => 8000, 'price' => 12000, 'stock' => 10, 'unit' => 'Pieces', 'desc' => '1 horsepower water pump'],
    ['name' => 'Irrigation Pipe (50mm)', 'category_id' => 4, 'sku' => 'IRRI-PIP-001', 'cost' => 250, 'price' => 350, 'stock' => 100, 'unit' => 'Meters', 'desc' => '50mm diameter pipe for irrigation'],
];

$added = 0;
$errors = 0;

foreach ($products as $product) {
    $sql = "INSERT INTO products (name, category_id, sku, description, cost_price, selling_price, quantity, unit, reorder_level) 
            VALUES ('" . $conn->real_escape_string($product['name']) . "', " . $product['category_id'] . ", '" . $conn->real_escape_string($product['sku']) . "', '" . $conn->real_escape_string($product['desc']) . "', " . $product['cost'] . ", " . $product['price'] . ", " . $product['stock'] . ", '" . $conn->real_escape_string($product['unit']) . "', 10)";
    
    if ($conn->query($sql) === TRUE) {
        $added++;
        echo "✓ Added: " . $product['name'] . "<br>";
    } else {
        $errors++;
        echo "✗ Error adding " . $product['name'] . ": " . $conn->error . "<br>";
    }
}

echo "<br><strong>Summary: $added products added, $errors errors</strong><br>";
$conn->close();
?>

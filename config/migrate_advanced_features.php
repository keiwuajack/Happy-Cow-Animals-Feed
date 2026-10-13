<?php
/**
 * Database Migration - Advanced Features
 * Adds all tables for 15 advanced features
 */

// Database connection
$host = 'localhost';
$dbname = 'agrovet_pos';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Starting advanced features migration...\n";
    
    // 1. SMS Settings & Log
    $pdo->exec("CREATE TABLE IF NOT EXISTS sms_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        api_key VARCHAR(255),
        username VARCHAR(100),
        sender_id VARCHAR(20) DEFAULT 'VISIONPOS',
        enable_receipt_sms BOOLEAN DEFAULT FALSE,
        enable_low_stock_sms BOOLEAN DEFAULT FALSE,
        enable_credit_reminder_sms BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS sms_log (
        id INT PRIMARY KEY AUTO_INCREMENT,
        recipient VARCHAR(20),
        message TEXT,
        status ENUM('sent', 'failed') DEFAULT 'sent',
        response TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (sent_at), INDEX (status)
    )");
    
    // 2. Multi-Store Management
    $pdo->exec("CREATE TABLE IF NOT EXISTS stores (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        location VARCHAR(255),
        phone VARCHAR(20),
        email VARCHAR(100),
        manager_id INT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS store_inventory (
        id INT PRIMARY KEY AUTO_INCREMENT,
        store_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY store_product (store_id, product_id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS store_transfers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        from_store_id INT NOT NULL,
        to_store_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        transferred_by INT NOT NULL,
        status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (from_store_id) REFERENCES stores(id),
        FOREIGN KEY (to_store_id) REFERENCES stores(id),
        FOREIGN KEY (product_id) REFERENCES products(id),
        FOREIGN KEY (transferred_by) REFERENCES users(id)
    )");
    
    // 3. Employee Commission
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS commission_rate DECIMAL(5,2) DEFAULT 0");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS commissions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        sale_id INT NOT NULL,
        commission_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'paid') DEFAULT 'pending',
        paid_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
        INDEX (user_id), INDEX (status)
    )");
    
    // 4. Quotations
    $pdo->exec("CREATE TABLE IF NOT EXISTS quotations (
        id INT PRIMARY KEY AUTO_INCREMENT,
        quote_number VARCHAR(50) UNIQUE NOT NULL,
        customer_id INT,
        user_id INT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        discount DECIMAL(10,2) DEFAULT 0,
        tax DECIMAL(10,2) DEFAULT 0,
        total DECIMAL(10,2) NOT NULL,
        valid_until DATE,
        status ENUM('pending', 'accepted', 'rejected', 'converted') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        converted_sale_id INT NULL,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (converted_sale_id) REFERENCES sales(id) ON DELETE SET NULL,
        INDEX (quote_number), INDEX (status)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS quotation_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        quotation_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");
    
    // 5. Credit Sales
    $pdo->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS credit_limit DECIMAL(10,2) DEFAULT 0");
    $pdo->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS credit_balance DECIMAL(10,2) DEFAULT 0");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS credit_payments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT NOT NULL,
        sale_id INT,
        amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('cash', 'mpesa', 'bank', 'card') DEFAULT 'cash',
        reference_number VARCHAR(100),
        notes TEXT,
        recorded_by INT NOT NULL,
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL,
        FOREIGN KEY (recorded_by) REFERENCES users(id)
    )");
    
    // 6. Product Variants
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_variants (
        id INT PRIMARY KEY AUTO_INCREMENT,
        product_id INT NOT NULL,
        variant_name VARCHAR(100) NOT NULL,
        sku VARCHAR(50) UNIQUE,
        barcode VARCHAR(50),
        price_adjustment DECIMAL(10,2) DEFAULT 0,
        stock_quantity INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX (product_id), INDEX (sku), INDEX (barcode)
    )");
    
    // 7. Purchase Orders
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_orders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        po_number VARCHAR(50) UNIQUE NOT NULL,
        supplier_id INT NOT NULL,
        store_id INT,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('draft', 'sent', 'partial', 'received', 'cancelled') DEFAULT 'draft',
        expected_date DATE,
        received_date DATE NULL,
        created_by INT NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX (po_number), INDEX (status)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_order_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        po_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity_ordered INT NOT NULL,
        quantity_received INT DEFAULT 0,
        unit_cost DECIMAL(10,2) NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");
    
    // 8. Cashier Shifts
    $pdo->exec("CREATE TABLE IF NOT EXISTS cashier_shifts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        store_id INT,
        opening_cash DECIMAL(10,2) NOT NULL,
        closing_cash DECIMAL(10,2),
        expected_cash DECIMAL(10,2),
        cash_sales DECIMAL(10,2) DEFAULT 0,
        mpesa_sales DECIMAL(10,2) DEFAULT 0,
        card_sales DECIMAL(10,2) DEFAULT 0,
        total_sales DECIMAL(10,2) DEFAULT 0,
        variance DECIMAL(10,2) DEFAULT 0,
        status ENUM('open', 'closed') DEFAULT 'open',
        opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        closed_at TIMESTAMP NULL,
        notes TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL,
        INDEX (user_id), INDEX (status)
    )");
    
    // 9. Product Bundles
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_bundles (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(150) NOT NULL,
        description TEXT,
        bundle_price DECIMAL(10,2) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS bundle_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        bundle_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        FOREIGN KEY (bundle_id) REFERENCES product_bundles(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");
    
    // 10. Returns & Refunds
    $pdo->exec("CREATE TABLE IF NOT EXISTS sales_returns (
        id INT PRIMARY KEY AUTO_INCREMENT,
        return_number VARCHAR(50) UNIQUE NOT NULL,
        sale_id INT NOT NULL,
        customer_id INT,
        total_refund DECIMAL(10,2) NOT NULL,
        refund_method ENUM('cash', 'mpesa', 'credit') DEFAULT 'cash',
        reason TEXT,
        processed_by INT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sale_id) REFERENCES sales(id),
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
        FOREIGN KEY (processed_by) REFERENCES users(id),
        INDEX (return_number), INDEX (status)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS return_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        return_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        restore_stock BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (return_id) REFERENCES sales_returns(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");
    
    // 11. Tax Management
    $pdo->exec("CREATE TABLE IF NOT EXISTS tax_rates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        rate DECIMAL(5,2) NOT NULL,
        is_default BOOLEAN DEFAULT FALSE,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS tax_rate_id INT NULL");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_tax_exempt BOOLEAN DEFAULT FALSE");
    
    // Add store_id to existing tables
    $pdo->exec("ALTER TABLE sales ADD COLUMN IF NOT EXISTS store_id INT NULL");
    $pdo->exec("ALTER TABLE sales ADD COLUMN IF NOT EXISTS shift_id INT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS store_id INT NULL");
    
    // Insert default data
    $pdo->exec("INSERT IGNORE INTO stores (id, name, location, is_active) VALUES (1, 'Main Store', 'Main Branch', TRUE)");
    $pdo->exec("INSERT IGNORE INTO sms_settings (id) VALUES (1)");
    $pdo->exec("INSERT IGNORE INTO tax_rates (name, rate, is_default, is_active) VALUES ('Standard VAT', 16.00, TRUE, TRUE)");
    
    echo "✓ All advanced feature tables created successfully!\n";
    echo "✓ Migration completed!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    die();
}

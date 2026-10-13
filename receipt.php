<?php
session_start();
require_once 'config/database.php';
$biz = require __DIR__ . '/config/business.php';
$e = fn($v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get sale details
$sql = "SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
        u.full_name as served_by
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.id = $sale_id";

$result = query($sql);
$sale = $result->fetch_assoc();

if (!$sale) {
    die('Sale not found');
}

// Get sale items
$items_sql = "SELECT * FROM sale_items WHERE sale_id = $sale_id";
$items_result = query($items_sql);
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $sale['invoice_number']; ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #333;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .business-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .business-tagline {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .business-contact {
            font-size: 12px;
            margin: 3px 0;
        }
        
        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            text-align: center;
        }
        
        .receipt-info {
            margin-bottom: 15px;
            font-size: 12px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        
        .items-table {
            width: 100%;
            margin: 15px 0;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
        }
        
        .items-header {
            font-weight: bold;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
            margin-bottom: 8px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 5px;
            font-size: 11px;
        }
        
        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 5px;
            margin: 5px 0;
            font-size: 11px;
        }
        
        .item-name {
            font-weight: bold;
        }
        
        .totals {
            margin: 15px 0;
            font-size: 13px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        
        .total-row.grand-total {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            padding: 8px 0;
            margin-top: 10px;
        }
        
        .payment-info {
            margin: 15px 0;
            border-top: 1px dashed #ccc;
            padding-top: 10px;
            font-size: 12px;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px dashed #333;
            font-size: 11px;
        }
        
        .thank-you {
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .print-button {
            display: block;
            margin: 20px auto;
            padding: 12px 30px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }
        
        .print-button:hover {
            background: #27ae60;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .receipt-container {
                box-shadow: none;
                max-width: 100%;
                padding: 10px;
            }
            
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="business-name" style="color: #263238; font-weight: 900;"><?php echo $e($biz['name']); ?></div>
            <div class="business-tagline"><?php echo $e($biz['tagline']); ?></div>
            <div class="business-contact">Tel: <?php echo $e($biz['phone']); ?></div>
            <div class="business-contact">Email: <?php echo $e($biz['email']); ?></div>
            <div class="business-contact">Location: <?php echo $e($biz['address']); ?></div>
        </div>
        
        <!-- Receipt Title -->
        <div class="receipt-title">SALES RECEIPT</div>
        
        <!-- Receipt Info -->
        <div class="receipt-info">
            <div class="info-row">
                <span>Invoice #:</span>
                <strong><?php echo $sale['invoice_number']; ?></strong>
            </div>
            <div class="info-row">
                <span>Date:</span>
                <span><?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></span>
            </div>
            <div class="info-row">
                <span>Customer:</span>
                <span><?php echo $sale['customer_name'] ?: 'Walk-in Customer'; ?></span>
            </div>
            <?php if ($sale['customer_phone']): ?>
            <div class="info-row">
                <span>Phone:</span>
                <span><?php echo $sale['customer_phone']; ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span>Served By:</span>
                <span><?php echo $sale['served_by']; ?></span>
            </div>
        </div>
        
        <!-- Items -->
        <div class="items-table">
            <div class="items-header">
                <div>Item</div>
                <div style="text-align: right;">Qty</div>
                <div style="text-align: right;">Price</div>
                <div style="text-align: right;">Total</div>
            </div>
            
            <?php foreach ($items as $item): ?>
            <div class="item-row">
                <div class="item-name"><?php echo $item['product_name']; ?></div>
                <div style="text-align: right;"><?php echo $item['quantity']; ?></div>
                <div style="text-align: right;"><?php echo number_format($item['unit_price'], 2); ?></div>
                <div style="text-align: right;"><?php echo number_format($item['subtotal'], 2); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Totals -->
        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>KSh <?php echo number_format($sale['subtotal'], 2); ?></span>
            </div>
            <?php if ($sale['discount'] > 0): ?>
            <div class="total-row">
                <span>Discount:</span>
                <span>- KSh <?php echo number_format($sale['discount'], 2); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($sale['tax'] > 0): ?>
            <div class="total-row">
                <span>Tax:</span>
                <span>KSh <?php echo number_format($sale['tax'], 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>KSh <?php echo number_format($sale['total'], 2); ?></span>
            </div>
        </div>
        
        <!-- Payment Info -->
        <div class="payment-info">
            <div class="total-row">
                <span>Payment Method:</span>
                <strong><?php echo strtoupper($sale['payment_method']); ?></strong>
            </div>
            <div class="total-row">
                <span>Amount Paid:</span>
                <span>KSh <?php echo number_format($sale['amount_paid'], 2); ?></span>
            </div>
            <?php 
            $change = $sale['amount_paid'] - $sale['total'];
            if ($change > 0): 
            ?>
            <div class="total-row">
                <span>Change:</span>
                <span>KSh <?php echo number_format($change, 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="total-row">
                <span>Status:</span>
                <span style="text-transform: uppercase;"><?php echo $sale['payment_status']; ?></span>
            </div>
        </div>
        
        <div class="receipt-qr" style="text-align:center;margin:12px 0">
            <div id="receiptQr" style="display:inline-block;width:120px;height:120px"></div>
            <div style="font-size:10px;margin-top:4px">Scan to verify receipt</div>
        </div>
        <script src="assets/js/qrcode.js"></script>
        <script>
        (function () {
            var qr = qrcode(0, 'M');
            qr.addData(<?php echo json_encode('Receipt: ' . $sale['invoice_number'] . "\nDate: " . date('d/m/Y H:i', strtotime($sale['created_at'])) . "\nTotal: KSh " . number_format($sale['total'], 2) . "\n" . $biz['name']); ?>);
            qr.make();
            document.getElementById('receiptQr').innerHTML = qr.createSvgTag({cellSize: 4, margin: 0, scalable: true});
        })();
        </script>
        <!-- Footer -->
        <div class="receipt-footer">
            <div class="thank-you">THANK YOU FOR YOUR BUSINESS!</div>
            <div>We appreciate your patronage</div>
            <div style="margin-top: 10px;"><?php echo $e($biz['receipt_note']); ?></div>
            <div style="margin-top: 5px;">For inquiries: <?php echo $e($biz['phone']); ?></div>
            <div style="margin-top: 15px; font-size: 10px;">
                Powered by Vision Data Company<br>
                <?php echo date('Y'); ?> © All Rights Reserved
            </div>
        </div>
    </div>
    
    <button class="print-button" onclick="window.print()">
        🖨️ Print Receipt
    </button>
    
    <script>
        // Auto-print option (commented out by default)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>

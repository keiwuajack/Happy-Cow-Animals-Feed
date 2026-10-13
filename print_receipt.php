<?php
session_start();
require_once 'config/database.php';
$biz = require __DIR__ . '/config/business.php';
$e = fn($v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die('Invalid request');
}

$saleId = intval($_GET['id']);

// Get sale details
$sql = "SELECT s.*, c.name as customer_name, c.phone as customer_phone, 
        u.full_name as cashier_name
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.id = $saleId";

$result = query($sql);
$sale = $result->fetch_assoc();

if (!$sale) {
    die('Sale not found');
}

// Get sale items
$sql = "SELECT * FROM sale_items WHERE sale_id = $saleId";
$result = query($sql);
$items = [];

while ($row = $result->fetch_assoc()) {
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
            padding: 20px;
            max-width: 80mm;
            margin: 0 auto;
        }
        
        .receipt {
            border: 1px solid #000;
            padding: 10px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 12px;
            margin: 2px 0;
        }
        
        .info-section {
            margin-bottom: 15px;
            font-size: 12px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        
        .items-table {
            width: 100%;
            margin-bottom: 15px;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 10px 0;
        }
        
        .item-row {
            margin: 5px 0;
            font-size: 12px;
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
        }
        
        .summary {
            font-size: 12px;
            margin-bottom: 15px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        
        .summary-row.total {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 10px;
        }
        
        .footer {
            text-align: center;
            font-size: 11px;
            border-top: 2px dashed #000;
            padding-top: 10px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .receipt {
                border: none;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-btn {
            padding: 10px 20px;
            background-color: #2ecc71;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .print-btn:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Print Receipt</button>
    
    <div class="receipt">
        <div class="header">
            <h1><?php echo $e($biz['name']); ?></h1>
            <p><?php echo $e($biz['tagline']); ?></p>
            <p>Phone: <?php echo $e($biz['phone']); ?></p>
            <p>Email: <?php echo $e($biz['email']); ?></p>
        </div>
        
        <div class="info-section">
            <div class="info-row">
                <span>Invoice #:</span>
                <strong><?php echo $sale['invoice_number']; ?></strong>
            </div>
            <div class="info-row">
                <span>Date:</span>
                <span><?php echo date('d/m/Y h:i A', strtotime($sale['created_at'])); ?></span>
            </div>
            <div class="info-row">
                <span>Customer:</span>
                <span><?php echo $sale['customer_name'] ?: 'Walk-in'; ?></span>
            </div>
            <?php if ($sale['customer_phone']): ?>
            <div class="info-row">
                <span>Phone:</span>
                <span><?php echo $sale['customer_phone']; ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span>Cashier:</span>
                <span><?php echo $sale['cashier_name']; ?></span>
            </div>
        </div>
        
        <div class="items-table">
            <div class="item-header">
                <span>ITEM</span>
                <span>QTY</span>
                <span>PRICE</span>
                <span>TOTAL</span>
            </div>
            
            <?php foreach ($items as $item): ?>
            <div class="item-row">
                <div style="font-weight: bold; margin-bottom: 3px;">
                    <?php echo $item['product_name']; ?>
                </div>
                <div class="item-details">
                    <span></span>
                    <span><?php echo $item['quantity']; ?></span>
                    <span>KSh <?php echo number_format($item['unit_price'], 2); ?></span>
                    <span>KSh <?php echo number_format($item['subtotal'], 2); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>KSh <?php echo number_format($sale['subtotal'], 2); ?></span>
            </div>
            <?php if ($sale['discount'] > 0): ?>
            <div class="summary-row">
                <span>Discount:</span>
                <span>- KSh <?php echo number_format($sale['discount'], 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-row">
                <span>Tax (16%):</span>
                <span>KSh <?php echo number_format($sale['tax'], 2); ?></span>
            </div>
            <div class="summary-row total">
                <span>TOTAL:</span>
                <span>KSh <?php echo number_format($sale['total'], 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Amount Paid:</span>
                <span>KSh <?php echo number_format($sale['amount_paid'], 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Change:</span>
                <span>KSh <?php echo number_format($sale['amount_paid'] - $sale['total'], 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Payment Method:</span>
                <span><?php echo strtoupper($sale['payment_method']); ?></span>
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
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Quality Products for Better Yields</p>
            <p>*** Customer Copy ***</p><p style="font-size:10px">Powered by Vision Data Company</p>
        </div>
    </div>
    
    <script>
        // Auto print when page loads
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>

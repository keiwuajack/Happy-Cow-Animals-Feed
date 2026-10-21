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

if (strtolower((string) ($_SESSION['role'] ?? '')) === 'cashier') {
    $cashierId = (int) $_SESSION['user_id'];
    $sql .= " AND s.user_id = {$cashierId}";
}

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
    <title>Receipt - <?php echo $e($sale['invoice_number']); ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <style>
        :root { color-scheme: light; font-family: Arial, Helvetica, sans-serif; color: #17231d; background: #edf1ee; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; padding: 34px 16px; background: #edf1ee; }
        .print-toolbar { width: min(100%, 560px); margin: 0 auto 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; color: #526258; font-size: 13px; }
        .print-toolbar p { margin: 0; }
        .print-btn { display: inline-flex; align-items: center; justify-content: center; gap: 9px; min-height: 42px; padding: 0 18px; border: 0; border-radius: 10px; background: #176b45; color: #fff; box-shadow: 0 5px 14px rgba(23, 107, 69, .18); cursor: pointer; font: 700 14px Arial, sans-serif; }
        .print-btn:hover { background: #105638; }
        .receipt { width: 80mm; max-width: 100%; margin: 0 auto; padding: 7mm 5mm; background: #fff; box-shadow: 0 16px 42px rgba(26, 47, 35, .14); color: #17231d; font-family: Arial, Helvetica, sans-serif; font-size: 10px; line-height: 1.42; }
        .header { padding-bottom: 12px; border-bottom: 1px dashed #89968d; text-align: center; }
        .header h1 { margin: 0 0 4px; color: #155b3b; font-size: 19px; line-height: 1.15; letter-spacing: -.35px; overflow-wrap: anywhere; }
        .header p { margin: 2px 0; color: #526258; font-size: 9px; overflow-wrap: anywhere; }
        .header .receipt-tagline { margin-bottom: 8px; color: #26392e; font-size: 10px; font-weight: 700; }
        .receipt-kicker { margin: 10px 0 2px; color: #155b3b; font-size: 8px; font-weight: 800; letter-spacing: 1.5px; }
        .info-section { display: grid; gap: 5px; margin: 12px 0; }
        .info-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
        .info-row span:first-child { flex: 0 0 auto; color: #647168; }
        .info-row span:last-child, .info-row strong { text-align: right; overflow-wrap: anywhere; }
        .items-table { margin: 12px 0; padding: 9px 0; border-top: 1px dashed #89968d; border-bottom: 1px dashed #89968d; }
        .item-header, .item-details { display: grid; grid-template-columns: minmax(0, 1fr) 26px 55px 58px; gap: 4px; align-items: start; }
        .item-header { padding-bottom: 6px; color: #526258; font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: .35px; }
        .item-header span:not(:first-child), .item-details span:not(:first-child) { text-align: right; }
        .item-row { padding: 6px 0; border-top: 1px solid #edf0ed; font-size: 9px; break-inside: avoid; }
        .item-row:first-of-type { border-top: 0; }
        .item-name { margin-bottom: 3px; font-weight: 700; overflow-wrap: anywhere; }
        .item-details { color: #526258; font-variant-numeric: tabular-nums; }
        .summary { display: grid; gap: 5px; margin: 12px 0; font-variant-numeric: tabular-nums; }
        .summary-row { display: flex; justify-content: space-between; gap: 12px; }
        .summary-row span:first-child { color: #526258; }
        .summary-row span:last-child { text-align: right; }
        .summary-row.total { margin-top: 3px; padding: 8px 0; border-top: 1px solid #9aa79e; border-bottom: 1px solid #9aa79e; color: #155b3b; font-size: 14px; font-weight: 800; }
        .summary-row.total span:first-child { color: inherit; }
        .receipt-qr { margin: 12px 0; text-align: center; }
        .receipt-qr #receiptQr { width: 25mm !important; height: 25mm !important; }
        .receipt-qr svg { width: 25mm; height: 25mm; }
        .receipt-qr-label { margin-top: 4px; color: #647168; font-size: 8px; }
        .footer { padding-top: 10px; border-top: 1px dashed #89968d; text-align: center; }
        .footer p { margin: 3px 0; color: #526258; font-size: 9px; }
        .footer p:first-child { color: #155b3b; font-size: 11px; font-weight: 800; }
        .footer .receipt-copy { margin-top: 8px; color: #26392e; font-weight: 700; }
        .footer .receipt-powered { color: #7a867e; font-size: 8px; }
        @page { margin: 0; }
        @media (max-width: 420px) { body { padding: 18px 8px; } .print-toolbar { align-items: flex-start; flex-direction: column; } .receipt { padding: 6mm 4mm; } }
        @media print {
            html, body { width: 100%; min-height: 0; margin: 0 !important; padding: 0 !important; background: #fff !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .receipt { width: 80mm; max-width: 80mm; margin: 0 auto; padding: 3mm 4mm; box-shadow: none; }
            .no-print { display: none !important; }
            .receipt-qr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar no-print">
        <p>Receipt preview · <?php echo $e($sale['invoice_number']); ?></p>
        <button class="print-btn" type="button" onclick="window.print()">Print receipt</button>
    </div>
    
    <div class="receipt">
        <div class="header">
            <h1><?php echo $e($biz['name']); ?></h1>
            <p class="receipt-tagline"><?php echo $e($biz['tagline']); ?></p>
            <p>Phone: <?php echo $e($biz['phone']); ?></p>
            <p>Email: <?php echo $e($biz['email']); ?></p>
            <p>Location: <?php echo $e($biz['address']); ?></p>
        </div>
        
        <p class="receipt-kicker">SALE DETAILS</p>
        <div class="info-section">
            <div class="info-row">
                <span>Invoice #:</span>
                <strong><?php echo $e($sale['invoice_number']); ?></strong>
            </div>
            <div class="info-row">
                <span>Date:</span>
                <span><?php echo $e(date('d/m/Y h:i A', strtotime($sale['created_at']))); ?></span>
            </div>
            <div class="info-row">
                <span>Customer:</span>
                <span><?php echo $e($sale['customer_name'] ?: 'Walk-in'); ?></span>
            </div>
            <?php if ($sale['customer_phone']): ?>
            <div class="info-row">
                <span>Phone:</span>
                <span><?php echo $e($sale['customer_phone']); ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span>Cashier:</span>
                <span><?php echo $e($sale['cashier_name'] ?: 'Not recorded'); ?></span>
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
                <div class="item-name">
                    <?php echo $e($item['product_name']); ?>
                </div>
                <div class="item-details">
                    <span></span>
                    <span><?php echo $e($item['quantity']); ?></span>
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
                <span>Amount received:</span>
                <span>KSh <?php echo number_format($sale['amount_paid'], 2); ?></span>
            </div>
            <?php if ((float)$sale['amount_paid'] < (float)$sale['total']): ?>
            <div class="summary-row">
                <span>Balance due:</span>
                <span>KSh <?php echo number_format((float)$sale['total'] - (float)$sale['amount_paid'], 2); ?></span>
            </div>
            <?php elseif ((float)$sale['amount_paid'] > (float)$sale['total']): ?>
            <div class="summary-row">
                <span>Change:</span>
                <span>KSh <?php echo number_format((float)$sale['amount_paid'] - (float)$sale['total'], 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-row">
                <span>Payment Method:</span>
                <span><?php echo $e(strtoupper($sale['payment_method'])); ?></span>
            </div>
        </div>
        
        <div class="receipt-qr">
            <div id="receiptQr" style="display:inline-block"></div>
            <div class="receipt-qr-label">Scan to verify receipt</div>
        </div>
        <script src="assets/js/qrcode.js"></script>
        <script>
        (function () {
            var qr = qrcode(0, 'M');
            qr.addData(<?php echo json_encode('Receipt: ' . $sale['invoice_number'] . "\nDate: " . date('d/m/Y H:i', strtotime($sale['created_at'])) . "\nTotal: KSh " . number_format($sale['total'], 2) . "\n" . $biz['name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
            qr.make();
            document.getElementById('receiptQr').innerHTML = qr.createSvgTag({cellSize: 4, margin: 0, scalable: true});
        })();
        </script>
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Quality Products for Better Yields</p>
            <p class="receipt-copy">Customer copy</p>
            <p class="receipt-powered">Powered by Vision Data Company</p>
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

<?php
require_once '../config/pdo.php';
$biz = require __DIR__ . '/../config/business.php';
$e = fn($v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die('Unauthorized');
}

$quoteId = $_GET['id'];

try {
    // Get quotation details
    $stmt = $pdo->prepare("
        SELECT q.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
               c.address as customer_address
        FROM quotations q
        LEFT JOIN customers c ON q.customer_id = c.id
        WHERE q.id = ?
    ");
    $stmt->execute([$quoteId]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quote) {
        die('Quotation not found');
    }
    
    // Get quotation items
    $stmt = $pdo->prepare("
        SELECT qi.*, p.name as product_name, p.unit
        FROM quotation_items qi
        JOIN products p ON qi.product_id = p.id
        WHERE qi.quotation_id = ?
    ");
    $stmt->execute([$quoteId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quotation - <?php echo $quote['quote_number']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2ecc71;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #2ecc71;
            margin-bottom: 5px;
        }
        .company-tagline {
            font-size: 14px;
            color: #666;
            font-style: italic;
        }
        .quote-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0;
        }
        .info-section h3 {
            color: #2ecc71;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .info-row {
            margin: 5px 0;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #2ecc71;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 13px;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 20px;
            float: right;
            width: 300px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        .total-row.grand {
            font-size: 18px;
            font-weight: bold;
            color: #2ecc71;
            border-top: 2px solid #2ecc71;
            margin-top: 10px;
            padding-top: 10px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            font-size: 12px;
            color: #666;
            clear: both;
        }
        .terms {
            margin-top: 30px;
            font-size: 12px;
        }
        .terms h4 {
            color: #2ecc71;
            margin-bottom: 10px;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        @media print {
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    
    <div class="header">
        <div class="company-name"><?php echo $e(strtoupper($biz['name'] . ' ' . $biz['tagline'])); ?></div>
        <div class="company-tagline">Your Trusted Agricultural Partner</div>
        <div style="margin-top: 10px; font-size: 12px;">
            Tel: <?php echo $e($biz['phone']); ?> | Email: <?php echo $e($biz['email']); ?> | <?php echo $e($biz['address']); ?>
        </div>
    </div>
    
    <h2 style="text-align: center; color: #2ecc71;">QUOTATION</h2>
    
    <div class="quote-info">
        <div class="info-section">
            <h3>Quote Details</h3>
            <div class="info-row"><strong>Quote Number:</strong> <?php echo $quote['quote_number']; ?></div>
            <div class="info-row"><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($quote['created_at'])); ?></div>
            <div class="info-row"><strong>Valid Until:</strong> <?php echo $quote['valid_until'] ? date('d/m/Y', strtotime($quote['valid_until'])) : 'N/A'; ?></div>
            <div class="info-row"><strong>Status:</strong> <span style="text-transform: uppercase; color: #2ecc71;"><?php echo $quote['status']; ?></span></div>
        </div>
        
        <div class="info-section">
            <h3>Customer Information</h3>
            <div class="info-row"><strong>Name:</strong> <?php echo $quote['customer_name'] ?? 'Walk-in Customer'; ?></div>
            <?php if ($quote['customer_phone']): ?>
            <div class="info-row"><strong>Phone:</strong> <?php echo $quote['customer_phone']; ?></div>
            <?php endif; ?>
            <?php if ($quote['customer_email']): ?>
            <div class="info-row"><strong>Email:</strong> <?php echo $quote['customer_email']; ?></div>
            <?php endif; ?>
            <?php if ($quote['customer_address']): ?>
            <div class="info-row"><strong>Address:</strong> <?php echo $quote['customer_address']; ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $index => $item): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo $item['product_name']; ?></td>
                <td class="text-right"><?php echo $item['quantity']; ?> <?php echo $item['unit'] ?? ''; ?></td>
                <td class="text-right">KSh <?php echo number_format($item['unit_price'], 2); ?></td>
                <td class="text-right"><strong>KSh <?php echo number_format($item['total'], 2); ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>KSh <?php echo number_format($quote['subtotal'], 2); ?></span>
        </div>
        <div class="total-row">
            <span>Discount:</span>
            <span>- KSh <?php echo number_format($quote['discount'], 2); ?></span>
        </div>
        <div class="total-row grand">
            <span>TOTAL:</span>
            <span>KSh <?php echo number_format($quote['total'], 2); ?></span>
        </div>
    </div>
    
    <?php if ($quote['notes']): ?>
    <div style="clear: both; margin-top: 30px;">
        <h4 style="color: #2ecc71;">Notes:</h4>
        <p style="font-size: 13px;"><?php echo nl2br(htmlspecialchars($quote['notes'])); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="terms">
        <h4>Terms & Conditions:</h4>
        <ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
            <li>This quotation is valid until <?php echo $quote['valid_until'] ? date('d/m/Y', strtotime($quote['valid_until'])) : '30 days from issue date'; ?></li>
            <li>Prices are subject to change without notice</li>
            <li>Payment terms: As agreed</li>
            <li>Delivery charges may apply</li>
        </ul>
    </div>
    
    <div class="footer">
        <p style="text-align: center; margin: 0;">
            <strong><?php echo $e(strtoupper($biz['name'] . ' ' . $biz['tagline'])); ?></strong><br>
            Tel: <?php echo $e($biz['phone']); ?> | Email: <?php echo $e($biz['email']); ?><br>
            Thank you for your business!<br><span style="font-size:10px">Powered by Vision Data Company</span>
        </p>
    </div>
</body>
</html>

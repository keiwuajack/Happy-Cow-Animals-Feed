<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$returnId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$return = null;
$items = [];
if ($returnId > 0) {
    $result = query("SELECT r.*, s.invoice_number, c.name AS customer_name, u.full_name AS processed_by_name
        FROM sales_returns r
        JOIN sales s ON s.id = r.sale_id
        LEFT JOIN customers c ON c.id = r.customer_id
        JOIN users u ON u.id = r.processed_by
        WHERE r.id = $returnId");
    $return = $result ? $result->fetch_assoc() : null;
    if ($return) {
        $itemsResult = query("SELECT ri.*, p.name AS product_name
            FROM return_items ri
            LEFT JOIN products p ON p.id = ri.product_id
            WHERE ri.return_id = $returnId");
        while ($item = $itemsResult->fetch_assoc()) {
            $items[] = $item;
        }
    }
}
if (!$return) {
    http_response_code(404);
    exit('Return not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Details - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261011-3">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            <main class="page-content">
                <div class="page-header">
                    <h1>Return Details</h1>
                    <a class="btn" href="returns.php">Back to Returns</a>
                </div>
                <div class="content-box">
                    <div class="sale-details-header">
                        <h4><?php echo htmlspecialchars($return['return_number']); ?></h4>
                        <p>Sale: <?php echo htmlspecialchars($return['invoice_number']); ?></p>
                        <p>Customer: <?php echo htmlspecialchars($return['customer_name'] ?: 'Walk-in'); ?></p>
                        <p>Status: <?php echo htmlspecialchars(ucfirst($return['status'])); ?></p>
                        <p>Processed by: <?php echo htmlspecialchars($return['processed_by_name']); ?></p>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead><tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name'] ?: 'Deleted product'); ?></td>
                                    <td><?php echo intval($item['quantity']); ?></td>
                                    <td>KSh <?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td>KSh <?php echo number_format($item['subtotal'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="sale-summary">
                        <div class="summary-row total"><span>Total Refund</span><span>KSh <?php echo number_format($return['total_refund'], 2); ?></span></div>
                        <p>Refund method: <?php echo htmlspecialchars(ucfirst($return['refund_method'])); ?></p>
                        <p>Reason: <?php echo htmlspecialchars($return['reason']); ?></p>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>

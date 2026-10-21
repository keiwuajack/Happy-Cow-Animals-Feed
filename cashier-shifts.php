<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cashier Shifts - Happy Cow</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/shifts.css?v=20261021-1">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            <main class="page-content shifts-page">
                <div class="page-header">
                    <div class="shifts-heading-copy">
                        <p class="eyebrow">CASH CONTROL</p>
                        <h1><i class="fas fa-clock" aria-hidden="true"></i> Cashier Shifts</h1>
                        <p>Track cash and M-Pesa separately, then reconcile the drawer when the shift ends.</p>
                    </div>
                </div>

                <section id="shiftPanel" class="content-box shift-panel" aria-live="polite">
                    <div class="shift-loading"><i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> Loading your shift...</div>
                </section>

                <section class="content-box shift-history-card" aria-labelledby="shiftHistoryTitle">
                    <div class="shift-history-heading">
                        <div>
                            <h2 id="shiftHistoryTitle">Shift history</h2>
                            <p>Review cash, M-Pesa, other payments, and the total received for each shift.</p>
                        </div>
                        <span class="shift-history-count" id="shiftCount">Recent shifts</span>
                    </div>
                    <div class="shift-table-wrap" data-collapsible-list="always" data-list-title="Shift history">
                        <table class="shift-history-table">
                            <thead>
                                <tr>
                                    <th>Cashier</th>
                                    <th>Opened</th>
                                    <th>Opening cash</th>
                                    <th>Closed</th>
                                    <th>Closing cash</th>
                                    <th>Expected</th>
                                    <th>Variance</th>
                                    <th>Cash paid</th>
                                    <th>M-Pesa paid</th>
                                    <th>Other paid</th>
                                    <th>Total received</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody id="shiftHistory">
                                <tr><td colspan="13" class="shift-empty-cell"><i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i><strong>Loading shift history</strong></td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script src="assets/js/cashier-shifts.js?v=20261021-1"></script>
</body>
</html>

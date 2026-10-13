<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261011-3">
    <link rel="stylesheet" href="assets/css/reports.css?v=20261011-6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="page-content rpt-page">
                <div class="rpt-hero">
                    <div>
                        <h1><i class="fas fa-chart-pie"></i> Reports &amp; Analytics</h1>
                        <p>See how your shop is performing, then export it as a PDF.</p>
                    </div>
                    <button class="rpt-export" id="exportPdfBtn" type="button">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>

                <div class="rpt-toolbar">
                    <div class="rpt-tabs" role="tablist">
                        <button class="tab-btn active" data-tab="sales"><i class="fas fa-chart-line"></i> Sales</button>
                        <button class="tab-btn" data-tab="best-sellers"><i class="fas fa-trophy"></i> Best Sellers</button>
                        <button class="tab-btn" data-tab="slow-moving"><i class="fas fa-hourglass-half"></i> Slow Moving</button>
                        <button class="tab-btn" data-tab="profit-loss"><i class="fas fa-coins"></i> Profit &amp; Loss</button>
                        <button class="tab-btn" data-tab="customers"><i class="fas fa-users"></i> Top Customers</button>
                    </div>

                    <div class="rpt-filters">
                        <div class="rpt-quick">
                            <button class="rpt-chip" id="todayBtn" type="button">Today</button>
                            <button class="rpt-chip" id="weekBtn" type="button">7 days</button>
                            <button class="rpt-chip" id="monthBtn" type="button">This month</button>
                        </div>
                        <div class="rpt-dates">
                            <label><span>From</span><input type="date" id="reportDateFrom"></label>
                            <label><span>To</span><input type="date" id="reportDateTo"></label>
                            <button class="rpt-generate" id="generateReport" type="button">
                                <i class="fas fa-rotate"></i> Generate
                            </button>
                        </div>
                    </div>
                </div>

                <div class="reports-container">
                    <div class="report-tab active" id="sales-tab">
                        <div id="salesSummary" class="rpt-stats"></div>
                        <div class="rpt-card" style="margin-bottom:14px">
                            <div class="rpt-card-head"><h3><i class="fas fa-calendar-day"></i> Profit per day</h3></div>
                            <div class="table-responsive">
                                <table id="dailyTable" class="rpt-table">
                                    <thead>
                                        <tr><th>Date</th><th class="num">Sales</th><th class="num">Revenue</th><th class="num">Cost of goods</th><th class="num">Profit</th></tr>
                                    </thead>
                                    <tbody><tr><td colspan="5" class="rpt-empty">Loading report...</td></tr></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="rpt-card">
                            <div class="rpt-card-head"><h3><i class="fas fa-receipt"></i> Sales transactions</h3></div>
                            <div class="table-responsive">
                                <table id="salesTable" class="rpt-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th><th>Date</th><th>Customer</th><th>Items</th>
                                            <th class="num">Subtotal</th><th class="num">Discount</th><th class="num">Total</th><th class="num">Profit</th>
                                            <th>Payment</th><th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="10" class="rpt-empty">Loading report...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="report-tab" id="best-sellers-tab">
                        <div class="rpt-card">
                            <div class="rpt-card-head"><h3><i class="fas fa-trophy"></i> Best selling products</h3></div>
                            <div class="table-responsive">
                                <table id="bestSellersTable" class="rpt-table">
                                    <thead>
                                        <tr>
                                            <th>Rank</th><th>Product</th><th>Category</th>
                                            <th class="num">Units sold</th><th class="num">Revenue</th><th class="num">Avg. price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="6" class="rpt-empty">Loading report...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="report-tab" id="slow-moving-tab">
                        <div class="rpt-card">
                            <div class="rpt-card-head"><h3><i class="fas fa-hourglass-half"></i> Slow moving stock</h3></div>
                            <div class="table-responsive">
                                <table id="slowMovingTable" class="rpt-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th><th>Category</th><th class="num">In stock</th>
                                            <th class="num">Units sold</th><th>Last sale</th><th>Days since sale</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="6" class="rpt-empty">Loading report...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="report-tab" id="profit-loss-tab">
                        <div id="profitLossSummary"></div>
                    </div>

                    <div class="report-tab" id="customers-tab">
                        <div class="rpt-card">
                            <div class="rpt-card-head"><h3><i class="fas fa-users"></i> Top customers</h3></div>
                            <div class="table-responsive">
                                <table id="customersTable" class="rpt-table">
                                    <thead>
                                        <tr>
                                            <th>Rank</th><th>Customer</th><th>Phone</th>
                                            <th class="num">Purchases</th><th class="num">Total spent</th><th>Loyalty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="6" class="rpt-empty">Loading report...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js?v=20261012-1"></script>
    <script src="assets/js/reports.js?v=20261011-5"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</body>
</html>

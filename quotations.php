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
    <title>Quotations - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/quotations.css?v=20261018-2">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="page-content">
                <div class="page-header quotation-page-header">
                    <div>
                        <p class="quotation-page-eyebrow">Sales documents</p>
                        <h1><i class="fas fa-file-invoice"></i> Quotations</h1>
                        <p class="quotation-page-description">Prepare customer offers and track them through to a sale.</p>
                    </div>
                    <button class="btn btn-primary quotation-new-button" id="newQuoteBtn">
                        <i class="fas fa-plus"></i> New Quotation
                    </button>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #3498db;">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="totalQuotes">0</h3>
                            <p>Total Quotes</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f39c12;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="pendingQuotes">0</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #2ecc71;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="convertedQuotes">0</h3>
                            <p>Converted</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #9b59b6;">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="quoteValue">KSh 0</h3>
                            <p>Total Value</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quotations Table -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Quotations List</h3>
                        <div class="filter-group" style="display: flex; gap: 10px;">
                            <select id="statusFilter" class="form-control" style="width: 150px;">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="accepted">Accepted</option>
                                <option value="rejected">Rejected</option>
                                <option value="converted">Converted</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="quotationsTable">
                                <thead>
                                    <tr>
                                        <th>Quote #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Valid Until</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="7" style="text-align: center;">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Quotation Modal -->
    <div id="quoteModal" class="modal quotation-modal" aria-hidden="true">
        <div class="modal-content quotation-dialog" role="dialog" aria-modal="true" aria-labelledby="quoteDialogTitle">
            <div class="quotation-dialog-header">
                <div class="quotation-title-icon"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i></div>
                <div class="quotation-title-copy">
                    <p class="quotation-dialog-eyebrow">Sales document</p>
                    <h2 id="quoteDialogTitle"><span id="modalTitle">New Quotation</span></h2>
                    <p>Build a clear offer for your customer.</p>
                </div>
                <button type="button" class="close quotation-close" aria-label="Close quotation form">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            <form id="quoteForm" class="quotation-form">
                <section class="quotation-section">
                    <div class="quotation-section-heading">
                        <span class="quotation-step">01</span>
                        <div><h3>Customer and validity</h3><p>Choose who this quotation is for and when it expires.</p></div>
                    </div>
                    <div class="quotation-fields-grid">
                        <div class="form-group quotation-field">
                            <label for="quoteCustomer">Customer <span class="quotation-required">Required</span></label>
                            <div class="quotation-input-wrap"><i class="fas fa-user" aria-hidden="true"></i><select id="quoteCustomer" class="form-control" required>
                                <option value="">Select a customer</option>
                            </select></div>
                        </div>
                        <div class="form-group quotation-field">
                            <label for="validUntil">Valid until <span class="quotation-required">Required</span></label>
                            <div class="quotation-input-wrap"><i class="fas fa-calendar-alt" aria-hidden="true"></i><input type="date" id="validUntil" class="form-control" required></div>
                        </div>
                    </div>
                </section>

                <section class="quotation-section quotation-items-section">
                    <div class="quotation-section-heading">
                        <span class="quotation-step">02</span>
                        <div><h3>Products</h3><p>Search your inventory, then set the quantity and quoted price.</p></div>
                    </div>
                    <div class="quotation-product-search">
                        <label for="productSearch">Add a product</label>
                        <div class="quotation-search-wrap">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" id="productSearch" class="form-control" placeholder="Search by product name or SKU" autocomplete="off">
                            <span class="quotation-search-hint">Type at least 2 characters</span>
                        </div>
                        <div id="productDropdown" class="quote-product-dropdown" role="listbox" aria-label="Matching products" hidden></div>
                    </div>

                    <div class="quotation-items-card">
                        <div class="quotation-items-heading"><div><h4>Quotation items</h4><span id="quoteItemCount">0 items</span></div><span class="quotation-currency"><i class="fas fa-coins" aria-hidden="true"></i> KSh</span></div>
                        <div class="table-responsive quotation-table-wrap">
                            <table id="quoteItemsTable" class="quotation-items-table">
                                <thead><tr><th>Product</th><th>Quantity</th><th>Unit price</th><th>Line total</th><th><span class="sr-only">Action</span></th></tr></thead>
                                <tbody id="quoteItems">
                                    <tr><td colspan="5"><div class="quotation-empty-state"><span><i class="fas fa-box-open" aria-hidden="true"></i></span><strong>No products added yet</strong><small>Search for a product above to start this quotation.</small></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="quotation-section quotation-section-bottom">
                    <div class="quotation-section-heading">
                        <span class="quotation-step">03</span>
                        <div><h3>Notes and total</h3><p>Add any details the customer should see.</p></div>
                    </div>
                    <div class="quotation-bottom-grid">
                        <div class="form-group quotation-notes-field">
                            <label for="quoteNotes">Notes <span class="quotation-optional">Optional</span></label>
                            <textarea id="quoteNotes" class="form-control" rows="5" placeholder="Add delivery details, payment terms, or other notes"></textarea>
                        </div>
                        <aside class="quotation-summary-card" aria-label="Quotation totals">
                            <div class="quotation-summary-heading"><div><i class="fas fa-receipt" aria-hidden="true"></i><h4>Summary</h4></div><span>Amounts in KSh</span></div>
                            <div class="quotation-summary-row"><span>Subtotal</span><strong id="quoteSubtotal">KSh 0.00</strong></div>
                            <div class="quotation-discount-row">
                                <label for="quoteDiscount">Discount</label>
                                <div class="quotation-discount-input"><span>KSh</span><input type="number" id="quoteDiscount" class="form-control" value="0" min="0" step="0.01" inputmode="decimal"></div>
                            </div>
                            <div class="quotation-total-row"><span>Total</span><strong id="quoteTotal">KSh 0.00</strong></div>
                        </aside>
                    </div>
                </section>

                <div class="quotation-form-actions">
                    <button type="button" class="btn quotation-cancel-button" onclick="closeQuoteModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary quotation-save-button"><i class="fas fa-save" aria-hidden="true"></i> Save Quotation</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script src="assets/js/quotations.js?v=20261018-1"></script>
</body>
</html>

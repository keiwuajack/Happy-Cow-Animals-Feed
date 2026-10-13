<?php
session_start();
require_once 'config/database.php';
require_once 'config/settings_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$needsShift = false;
$expectedOpening = null;
$shiftTable = query("SHOW TABLES LIKE 'cashier_shifts'");
if ($shiftTable && $shiftTable->num_rows > 0) {
    $uid = (int) $_SESSION['user_id'];
    $open = query("SELECT id FROM cashier_shifts WHERE user_id = $uid AND status = 'open' LIMIT 1");
    $needsShift = !($open && $open->fetch_assoc());
    if ($needsShift) {
        $prev = query("SELECT closing_cash FROM cashier_shifts WHERE status = 'closed' ORDER BY closed_at DESC, id DESC LIMIT 1");
        $row = $prev ? $prev->fetch_assoc() : null;
        $expectedOpening = $row ? (float) $row['closing_cash'] : null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261011-3">
    <link rel="stylesheet" href="assets/css/pos.css?v=20261012-5">
    <link rel="stylesheet" href="assets/css/shifts.css?v=20261012-1">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css?v=20260903-2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <?php if ($needsShift): ?>
            <div id="shiftLock" class="shift-lock">
                <div class="shift-card">
                    <div class="shift-icon"><i class="fas fa-cash-register"></i></div>
                    <h2>Start your shift</h2>
                    <p class="shift-sub">Selling is locked until you count the drawer and open your shift.</p>
                    <?php if ($expectedOpening !== null): ?>
                    <div class="shift-hint"><i class="fas fa-circle-info"></i><span>Previous shift closed with <strong>KSh <?php echo number_format($expectedOpening, 2); ?></strong></span></div>
                    <?php endif; ?>
                    <form id="posOpenShiftForm">
                        <div class="shift-field big"><label>Counted opening cash (KSh)</label><input id="posOpeningCash" type="number" min="0" step="0.01" placeholder="0.00" required autofocus></div>
                        <div class="shift-field"><label>Note (required if different from previous)</label><input id="posOpeningNote" type="text" placeholder="Explain any difference"></div>
                        <button class="shift-btn"><i class="fas fa-play"></i> Open shift &amp; start selling</button>
                    </form>
                </div>
            </div>
            <script>
            document.getElementById('posOpenShiftForm').addEventListener('submit', function (e) {
                e.preventDefault();
                fetch('api/open_shift.php', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({opening_cash: document.getElementById('posOpeningCash').value, note: document.getElementById('posOpeningNote').value})})
                    .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message); })
                    .catch(() => alert('Could not open shift'));
            });
            </script>
            <?php endif; ?>

            <div class="pos-container">
                <div class="pos-left">
                    <div class="search-section">
                        <input type="text" id="productSearch" placeholder="Search products by name or SKU..." class="search-input">
                        <select id="categoryFilter" class="category-filter">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    
                    <div class="products-grid" id="productsGrid">
                        <!-- Products will be loaded here -->
                    </div>
                </div>
                
                <div class="pos-right">
                    <div class="cart-header">
                        <div class="cart-title">
                            <h2><i class="fas fa-shopping-cart"></i> Current Sale</h2>
                            <span class="cart-item-count" id="cartItemCount" aria-live="polite">0 items</span>
                        </div>
                        <button class="btn btn-danger btn-sm" id="clearCart">
                            <i class="fas fa-trash"></i> Clear
                        </button>
                    </div>
                    
                    <div class="customer-section">
                        <select id="customerSelect" class="customer-select">
                            <option value="">Walk-in Customer</option>
                        </select>
                        <button class="btn btn-sm" id="addCustomerBtn">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    
                    <div class="cart-items" id="cartItems">
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <p>Cart is empty</p>
                        </div>
                    </div>
                    
                    <div class="cart-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="subtotal">KSh 0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Discount:</span>
                            <input type="number" id="discount" value="0" min="0" step="0.01" class="discount-input">
                        </div>
                        <div class="summary-row">
                            <span>Tax (<?php echo rtrim(rtrim(number_format(hc_tax_rate(), 2), '0'), '.'); ?>%):</span>
                            <span id="tax">KSh 0.00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span id="total">KSh 0.00</span>
                        </div>
                    </div>
                    
                    <div class="payment-section">
                        <select id="cartPaymentMethod" class="payment-method">
                            <option value="cash">Cash</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="card">Card</option>
                            <option value="credit">Credit</option>
                        </select>
                        
                        <button class="btn btn-primary btn-block btn-lg" id="checkoutBtn" disabled>
                            <i class="fas fa-check"></i> Complete Sale
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="mobile-cart-toggle" type="button" onclick="toggleMobileCart()" aria-label="Open current sale">
        <i class="fas fa-shopping-cart"></i>
        <span class="mobile-cart-badge" id="mobileCartBadge">0</span>
    </button>
    
    <!-- Payment Modal -->
    <div id="paymentModal" class="modal payment-modal">
        <div class="modal-content checkout-modal" role="dialog" aria-modal="true" aria-labelledby="paymentModalTitle">
            <div class="modal-header">
                <div>
                    <h3 id="paymentModalTitle">Complete payment</h3>
                    <p>Choose a payment method and confirm the amount received.</p>
                </div>
                <button type="button" class="close" aria-label="Close payment window">&times;</button>
            </div>
            <div class="modal-body">
                <div class="checkout-total">
                    <span>Total due</span>
                    <strong id="modalTotal">KSh 0.00</strong>
                </div>
                
                <div class="form-group checkout-method-group">
                    <label>Payment method</label>
                    <div class="checkout-methods">
                        <button type="button" class="payment-method-btn" data-method="cash" aria-pressed="true">
                            <i class="fas fa-money-bill-wave" aria-hidden="true"></i><span>Cash</span>
                        </button>
                        <button type="button" class="payment-method-btn" data-method="mpesa" aria-pressed="false">
                            <i class="fas fa-mobile-alt" aria-hidden="true"></i><span>M-Pesa</span>
                        </button>
                        <button type="button" class="payment-method-btn" data-method="card" aria-pressed="false">
                            <i class="fas fa-credit-card" aria-hidden="true"></i><span>Card</span>
                        </button>
                    </div>
                    <input type="hidden" id="paymentMethod" value="cash">
                </div>
                
                <div id="mpesaDetails" class="form-group mpesa-details" style="display:none;">
                    <label for="mpesaCode">M-Pesa transaction code</label>
                    <input type="text" id="mpesaCode" placeholder="e.g. QFT5XHWK2L" autocomplete="off">
                </div>
                
                <div class="checkout-payment-row">
                    <div class="form-group">
                        <label for="amountPaid">Amount received</label>
                        <div class="checkout-input-wrap">
                            <span>KSh</span>
                            <input type="number" id="amountPaid" step="0.01" min="0" required inputmode="decimal">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="changeAmount">Change due</label>
                        <input type="text" id="changeAmount" readonly aria-live="polite">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="saleNotes">Sale note <span>(optional)</span></label>
                    <textarea id="saleNotes" rows="2" placeholder="Add a note for this sale..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cancelPayment">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmPayment">
                    <i class="fas fa-check-circle" aria-hidden="true"></i> Confirm sale
                </button>
            </div>
        </div>
    </div>
    
    <script>window.TAX_RATE = <?php echo hc_tax_rate(); ?>;</script>
<script src="assets/js/main.js?v=20261012-1"></script>
    <script src="assets/js/barcode-scanner.js?v=20261011-2"></script>
    <script src="assets/js/pos.js?v=20261012-2"></script>
    <script src="assets/js/mpesa-pos.js?v=20261012-2"></script>
</body>
</html>


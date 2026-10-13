// Enhanced POS JavaScript with M-Pesa Integration
let mpesaCheckoutRequestId = null;
let mpesaPaymentTimer = null;

// Add M-Pesa STK Push functionality
async function initiateMpesaPayment() {
    const total = parseFloat(document.getElementById('total').textContent.replace(/[^0-9.]/g, ''));
    const customer = getSelectedCustomer();
    const phone = await appPrompt('Enter customer M-Pesa phone number (e.g., 0722000000):', { okText: 'Send request' });
    
    if (!phone) {
        showNotification('Phone number is required for M-Pesa payment', 'error');
        return;
    }
    
    showNotification('Sending STK Push to ' + phone + '...', 'info');
    
    const invoiceNumber = 'INV' + Date.now();
    
    fetch('api/initiate_mpesa_payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            phone: phone,
            amount: total,
            account_reference: invoiceNumber,
            description: 'Payment for ' + invoiceNumber
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mpesaCheckoutRequestId = data.checkoutRequestId;
            showNotification('✅ STK Push sent! Check phone for M-Pesa prompt.', 'success');
            
            // Start polling for payment status
            checkMpesaPaymentStatus(data.checkoutRequestId);
        } else {
            showNotification('❌ Failed to send M-Pesa request: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error initiating M-Pesa payment', 'error');
    });
}

// Check M-Pesa payment status
function checkMpesaPaymentStatus(checkoutRequestId) {
    let attempts = 0;
    const maxAttempts = 30; // Check for 30 seconds
    
    mpesaPaymentTimer = setInterval(() => {
        attempts++;
        
        fetch('api/get_mpesa_transactions.php?checkout_request=' + checkoutRequestId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.transactions.length > 0) {
                    const txn = data.transactions[0];
                    
                    if (txn.status === 'completed') {
                        clearInterval(mpesaPaymentTimer);
                        showNotification('✅ Payment received! Receipt: ' + txn.mpesa_receipt_number, 'success');
                        
                        // Auto-fill payment details
                        document.getElementById('mpesaCode').value = txn.mpesa_receipt_number;
                        document.getElementById('amountPaid').value = txn.amount;
                        
                        // Auto-complete sale after 2 seconds
                        setTimeout(() => {
                            confirmPayment();
                        }, 2000);
                    } else if (txn.status === 'failed') {
                        clearInterval(mpesaPaymentTimer);
                        showNotification('❌ Payment failed or cancelled', 'error');
                    }
                }
                
                if (attempts >= maxAttempts) {
                    clearInterval(mpesaPaymentTimer);
                    showNotification('⏱️ Payment timeout. Please check manually.', 'warning');
                }
            })
            .catch(error => console.error('Error checking payment:', error));
    }, 2000); // Check every 2 seconds
}

// Send WhatsApp receipt
function sendWhatsAppReceipt(saleId) {
    const customer = getSelectedCustomer();
    
    if (!customer || !customer.whatsapp_number || !customer.receive_whatsapp) {
        return; // Skip if no WhatsApp number or customer opted out
    }
    
    fetch('api/send_receipt_whatsapp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sale_id: saleId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('📱 Receipt sent via WhatsApp', 'success');
        }
    })
    .catch(error => console.error('Error sending WhatsApp:', error));
}

// Mobile cart toggle
function toggleMobileCart() {
    const cart = document.querySelector('.pos-right');
    cart.classList.toggle('mobile-cart-open');
}

// Update payment modal when opened
function openPaymentModal() {
    if (cart.length === 0) {
        showNotification('Cart is empty', 'error');
        return;
    }
    
    const modal = document.getElementById('paymentModal');
    const total = parseFloat(document.getElementById('total').textContent.replace(/[^0-9.]/g, ''));
    
    document.getElementById('modalTotal').textContent = formatCurrency(total);
    document.getElementById('amountPaid').value = total.toFixed(2);
    updatePaymentChange();
    document.getElementById('saleNotes').value = '';
    document.getElementById('paymentMethod').value = 'cash';
    document.querySelectorAll('.payment-method-btn').forEach(button => {
        const isCash = button.dataset.method === 'cash';
        button.classList.toggle('active', isCash);
        button.setAttribute('aria-pressed', isCash ? 'true' : 'false');
    });
    document.getElementById('mpesaDetails').style.display = 'none';
    
    // Reset M-Pesa fields
    if (document.getElementById('mpesaCode')) {
        document.getElementById('mpesaCode').value = '';
    }

    modal.classList.add('show');
    document.getElementById('amountPaid').focus();
}

// Enhanced payment method selection
document.addEventListener('DOMContentLoaded', function() {
    // Add M-Pesa quick pay button
    const mpesaBtn = document.querySelector('.payment-method-btn[data-method="mpesa"]');
    if (mpesaBtn) {
        mpesaBtn.addEventListener('dblclick', function() {
            if (cart.length > 0) {
                initiateMpesaPayment();
            }
        });
    }
});

// Get selected customer details
function getSelectedCustomer() {
    const customerId = document.getElementById('customerSelect').value;
    if (!customerId) return null;
    
    return customers.find(c => c.id == customerId);
}

// Mobile menu toggle
function toggleMobileMenu() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');
    
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('active');
}

// Add touch swipe for mobile cart
let touchStartY = 0;
let touchEndY = 0;

document.addEventListener('DOMContentLoaded', function() {
    const posRight = document.querySelector('.pos-right');
    
    if (posRight) {
        posRight.addEventListener('touchstart', e => {
            touchStartY = e.changedTouches[0].screenY;
        }, false);
        
        posRight.addEventListener('touchend', e => {
            touchEndY = e.changedTouches[0].screenY;
            handleSwipe();
        }, false);
    }
});

function handleSwipe() {
    if (touchEndY - touchStartY > 50) {
        // Swipe down - close cart
        document.querySelector('.pos-right')?.classList.remove('mobile-cart-open');
    }
}

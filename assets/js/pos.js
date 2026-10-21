// POS JavaScript
let cart = [];
let products = [];
let customers = [];
let barcodeScanner = null;

document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    loadCategories();
    loadCustomers();
    
    // Initialize barcode scanner
    initBarcodeScanner();
    
    // Event listeners
    document.getElementById('productSearch').addEventListener('input', filterProducts);
    document.getElementById('categoryFilter').addEventListener('change', filterProducts);
    document.getElementById('clearCart').addEventListener('click', clearCart);
    document.getElementById('checkoutBtn').addEventListener('click', openPaymentModal);
    document.getElementById('discount').addEventListener('input', function() {
        syncDiscountInputs(this);
    });
    document.getElementById('checkoutDiscount').addEventListener('input', function() {
        syncDiscountInputs(this);
    });
    document.getElementById('addCustomerBtn').addEventListener('click', () => {
        window.location.href = 'customers.php?action=add&return=pos';
    });
    
    // Payment modal
    const modal = document.getElementById('paymentModal');
    const closeBtn = modal.querySelector('.close');
    const cancelBtn = document.getElementById('cancelPayment');
    const confirmBtn = document.getElementById('confirmPayment');
    const amountPaidInput = document.getElementById('amountPaid');
    
    closeBtn.onclick = () => modal.classList.remove('show');
    cancelBtn.onclick = () => modal.classList.remove('show');
    confirmBtn.onclick = confirmPayment;
    
    // Payment method selection
    document.querySelectorAll('.payment-method-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.payment-method-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.payment-method-btn').forEach(b => {
                b.setAttribute('aria-pressed', b === this ? 'true' : 'false');
            });
            document.getElementById('paymentMethod').value = this.dataset.method;
            
            // Show/hide M-Pesa details
            const mpesaDetails = document.getElementById('mpesaDetails');
            if (this.dataset.method === 'mpesa') {
                mpesaDetails.style.display = 'block';
            } else {
                mpesaDetails.style.display = 'none';
            }
        });
    });
    
    // Set default payment method
    const defaultPaymentButton = document.querySelector('.payment-method-btn[data-method="cash"]');
    defaultPaymentButton.classList.add('active');
    defaultPaymentButton.setAttribute('aria-pressed', 'true');
    
    amountPaidInput.addEventListener('input', updatePaymentChange);
    
    window.onclick = (e) => {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    };
});

function updatePaymentChange() {
    const total = parseFloat(document.getElementById('total').textContent.replace(/[^0-9.]/g, '')) || 0;
    const paid = parseFloat(document.getElementById('amountPaid').value) || 0;
    const change = paid - total;
    document.getElementById('changeAmount').value = change >= 0 ? formatCurrency(change) : 'Insufficient';
}

function loadProducts() {
    fetch('api/get_products.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                products = data.products;
                displayProducts(products);
            }
        })
        .catch(error => console.error('Error loading products:', error));
}

function loadCategories() {
    fetch('api/get_categories.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('categoryFilter');
                data.categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading categories:', error));
}

function loadCustomers() {
    fetch('api/get_customers.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                customers = data.customers;
                const select = document.getElementById('customerSelect');
                data.customers.forEach(customer => {
                    const option = document.createElement('option');
                    option.value = customer.id;
                    option.textContent = `${customer.name} - ${customer.phone || 'N/A'}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading customers:', error));
}

function displayProducts(productsToDisplay) {
    const grid = document.getElementById('productsGrid');
    grid.innerHTML = '';

    const visibleProducts = Array.isArray(productsToDisplay) ? productsToDisplay : [];
    const count = document.getElementById('catalogCount');
    if (count) {
        count.textContent = `${visibleProducts.length} ${visibleProducts.length === 1 ? 'product' : 'products'}`;
    }

    if (visibleProducts.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'products-empty';
        empty.innerHTML = '<i class="fas fa-box-open" aria-hidden="true"></i><strong>No products found</strong><span>Try another search or category.</span>';
        grid.appendChild(empty);
        return;
    }

    visibleProducts.forEach(product => {
        const isLowStock = product.quantity <= product.reorder_level;
        const card = document.createElement('button');
        card.type = 'button';
        card.className = 'product-card';
        card.setAttribute('aria-label', `Add ${product.name} to sale`);
        card.addEventListener('click', () => addToCart(product));

        const visual = document.createElement('span');
        visual.className = 'product-visual';
        if (product.image) {
            const image = document.createElement('img');
            image.className = 'product-photo';
            image.src = encodeURI(product.image);
            image.alt = '';
            image.loading = 'lazy';
            image.onerror = () => {
                image.remove();
                visual.classList.add('has-fallback');
                const fallback = document.createElement('i');
                fallback.className = 'fas fa-seedling';
                fallback.setAttribute('aria-hidden', 'true');
                visual.appendChild(fallback);
            };
            visual.appendChild(image);
        } else {
            visual.classList.add('has-fallback');
            const fallback = document.createElement('i');
            fallback.className = 'fas fa-seedling';
            fallback.setAttribute('aria-hidden', 'true');
            visual.appendChild(fallback);
        }

        const details = document.createElement('span');
        details.className = 'product-details';
        const category = document.createElement('span');
        category.className = 'product-category';
        category.textContent = product.category_name || 'Feed & supplies';
        const name = document.createElement('span');
        name.className = 'product-name';
        name.textContent = product.name;
        details.append(category, name);

        const footer = document.createElement('span');
        footer.className = 'product-card-footer';
        const price = document.createElement('span');
        price.className = 'product-price';
        price.textContent = formatCurrency(product.selling_price);
        const stock = document.createElement('span');
        stock.className = `product-stock${isLowStock ? ' low' : ''}`;
        stock.textContent = `${product.quantity} ${product.unit || 'in stock'}`;
        footer.append(price, stock);

        const addHint = document.createElement('span');
        addHint.className = 'product-add-hint';
        addHint.innerHTML = '<i class="fas fa-plus" aria-hidden="true"></i><span>Add</span>';
        card.append(visual, details, footer, addHint);
        grid.appendChild(card);
    });
}

function filterProducts() {
    const searchTerm = document.getElementById('productSearch').value.toLowerCase();
    const categoryId = document.getElementById('categoryFilter').value;
    
    let filtered = products;
    
    if (searchTerm) {
        filtered = filtered.filter(p => 
            p.name.toLowerCase().includes(searchTerm) || 
            (p.sku && p.sku.toLowerCase().includes(searchTerm)) ||
            (p.barcode && p.barcode.toLowerCase().includes(searchTerm))
        );
    }
    
    if (categoryId) {
        filtered = filtered.filter(p => p.category_id == categoryId);
    }
    
    displayProducts(filtered);
}

function addToCart(product) {
    if (product.quantity <= 0) {
        showAlert('Product is out of stock', 'error');
        return;
    }
    
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
        if (existingItem.quantity < product.quantity) {
            existingItem.quantity++;
        } else {
            showAlert('Not enough stock available', 'error');
            return;
        }
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.selling_price),
            quantity: 1,
            maxStock: product.quantity
        });
    }
    
    updateCart();
}

function updateCart() {
    const cartItemsDiv = document.getElementById('cartItems');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const mobileCartBadge = document.getElementById('mobileCartBadge');
    const cartItemCount = document.getElementById('cartItemCount');
    const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);

    if (mobileCartBadge) {
        mobileCartBadge.textContent = itemCount;
    }
    if (cartItemCount) {
        cartItemCount.textContent = `${itemCount} ${itemCount === 1 ? 'item' : 'items'}`;
    }
    
    if (cart.length === 0) {
        document.getElementById('discount').value = '0';
        document.getElementById('checkoutDiscount').value = '0';
        cartItemsDiv.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Cart is empty</p>
            </div>
        `;
        checkoutBtn.disabled = true;
    } else {
        cartItemsDiv.innerHTML = '';
        cart.forEach((item, index) => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'cart-item';
            const details = document.createElement('div');
            details.className = 'item-details';
            const name = document.createElement('div');
            name.className = 'item-name';
            name.textContent = item.name;
            const price = document.createElement('div');
            price.className = 'item-price';
            price.textContent = `${formatCurrency(item.price)} each`;
            details.append(name, price);

            const controls = document.createElement('div');
            controls.className = 'item-controls';
            const quantityControls = document.createElement('div');
            quantityControls.className = 'qty-controls';

            const decreaseButton = document.createElement('button');
            decreaseButton.type = 'button';
            decreaseButton.className = 'qty-btn';
            decreaseButton.setAttribute('aria-label', `Decrease quantity of ${item.name}`);
            decreaseButton.innerHTML = '<i class="fas fa-minus" aria-hidden="true"></i>';
            decreaseButton.disabled = item.quantity <= 1;
            decreaseButton.addEventListener('click', () => decreaseQty(index));

            const quantityInput = document.createElement('input');
            quantityInput.type = 'number';
            quantityInput.className = 'qty-input';
            quantityInput.value = item.quantity;
            quantityInput.min = 1;
            quantityInput.max = item.maxStock;
            quantityInput.setAttribute('aria-label', `Quantity of ${item.name}`);
            quantityInput.addEventListener('change', () => updateQty(index, quantityInput.value));

            const increaseButton = document.createElement('button');
            increaseButton.type = 'button';
            increaseButton.className = 'qty-btn';
            increaseButton.setAttribute('aria-label', `Increase quantity of ${item.name}`);
            increaseButton.innerHTML = '<i class="fas fa-plus" aria-hidden="true"></i>';
            increaseButton.disabled = item.quantity >= item.maxStock;
            increaseButton.addEventListener('click', () => increaseQty(index));

            quantityControls.append(decreaseButton, quantityInput, increaseButton);

            const itemTotal = document.createElement('div');
            itemTotal.className = 'item-total';
            itemTotal.textContent = formatCurrency(item.price * item.quantity);

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'remove-item';
            removeButton.setAttribute('aria-label', `Remove ${item.name} from cart`);
            removeButton.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
            removeButton.addEventListener('click', () => removeFromCart(index));

            controls.append(quantityControls, itemTotal, removeButton);
            itemDiv.append(details, controls);
            cartItemsDiv.appendChild(itemDiv);
        });
        checkoutBtn.disabled = false;
    }
    
    updateCartSummary();
}

function increaseQty(index) {
    if (cart[index].quantity < cart[index].maxStock) {
        cart[index].quantity++;
        updateCart();
    } else {
        showAlert('Not enough stock available', 'error');
    }
}

function decreaseQty(index) {
    if (cart[index].quantity > 1) {
        cart[index].quantity--;
        updateCart();
    }
}

function updateQty(index, value) {
    const qty = parseInt(value);
    if (qty > 0 && qty <= cart[index].maxStock) {
        cart[index].quantity = qty;
        updateCart();
    } else {
        showAlert('Invalid quantity', 'error');
        updateCart();
    }
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCart();
}

async function clearCart() {
    if (cart.length > 0) {
        if (await appConfirm('Are you sure you want to clear the cart?', { danger: true, okText: 'Clear cart' })) {
            cart = [];
            updateCart();
        }
    }
}

function updateCartSummary() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discountInput = document.getElementById('discount');
    const requestedDiscount = Number(discountInput.value) || 0;
    const discount = Math.min(Math.max(requestedDiscount, 0), subtotal);
    const taxRate = (typeof window.TAX_RATE === 'number' ? window.TAX_RATE : 16) / 100;
    const discountedSubtotal = Math.max(0, subtotal - discount);
    const tax = Math.round(discountedSubtotal * taxRate * 100) / 100;
    const total = discountedSubtotal + tax;
    const discountError = document.getElementById('discountError');

    discountInput.max = subtotal.toFixed(2);
    document.getElementById('checkoutDiscount').max = subtotal.toFixed(2);
    discountError.hidden = requestedDiscount <= subtotal && requestedDiscount >= 0;
    
    document.getElementById('subtotal').textContent = formatCurrency(subtotal);
    document.getElementById('tax').textContent = formatCurrency(tax);
    document.getElementById('total').textContent = formatCurrency(total);
    document.getElementById('paymentSubtotal').textContent = formatCurrency(subtotal);
    document.getElementById('paymentTax').textContent = formatCurrency(tax);
    document.getElementById('modalTotal').textContent = formatCurrency(total);

    const paymentModal = document.getElementById('paymentModal');
    if (paymentModal.classList.contains('show') && document.getElementById('amountPaid').value.trim() !== '') {
        updatePaymentChange();
    }
}

function syncDiscountInputs(sourceInput) {
    const otherInput = sourceInput.id === 'discount'
        ? document.getElementById('checkoutDiscount')
        : document.getElementById('discount');
    otherInput.value = sourceInput.value;
    updateCartSummary();
}

function openPaymentModal() {
    updateCartSummary();
    document.getElementById('amountPaid').value = '';
    document.getElementById('changeAmount').value = '';
    document.getElementById('saleNotes').value = '';
    document.getElementById('mpesaCode').value = '';
    
    // Reset payment method to cash
    document.querySelectorAll('.payment-method-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector('.payment-method-btn[data-method="cash"]').classList.add('active');
    document.getElementById('paymentMethod').value = 'cash';
    document.getElementById('mpesaDetails').style.display = 'none';
    
    document.getElementById('paymentModal').classList.add('show');
    document.getElementById('amountPaid').focus();
}

function confirmPayment() {
    const total = parseFloat(document.getElementById('total').textContent.replace(/[^0-9.]/g, ''));
    const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
    const paymentMethod = document.getElementById('paymentMethod').value;
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discount = Number(document.getElementById('discount').value) || 0;

    if (discount < 0 || discount > subtotal) {
        showAlert('Discount cannot be greater than the sale subtotal', 'error');
        document.getElementById('checkoutDiscount').focus();
        return;
    }
    
    if (amountPaid < total) {
        showAlert('Amount paid is less than total', 'error');
        return;
    }
    
    if (paymentMethod === 'mpesa' && !document.getElementById('mpesaCode').value) {
        showAlert('Please enter M-Pesa transaction code', 'error');
        return;
    }
    
    const paymentDetails = {};
    if (paymentMethod === 'mpesa') {
        paymentDetails.mpesa_code = document.getElementById('mpesaCode').value;
    }
    
    const saleData = {
        customer_id: document.getElementById('customerSelect').value || null,
        payment_method: paymentMethod,
        payment_details: paymentMethod === 'mpesa' ? JSON.stringify(paymentDetails) : null,
        items: cart,
        subtotal: parseFloat(document.getElementById('subtotal').textContent.replace(/[^0-9.]/g, '')),
        discount: discount,
        tax: parseFloat(document.getElementById('tax').textContent.replace(/[^0-9.]/g, '')),
        total: total,
        amount_paid: amountPaid,
        notes: document.getElementById('saleNotes').value
    };
    
    fetch('api/create_sale.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(saleData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let message = 'Sale completed successfully!';
            if (data.points_earned && data.points_earned > 0) {
                message += ` Customer earned ${data.points_earned} loyalty points!`;
            }
            showAlert(message, 'success');
            document.getElementById('paymentModal').classList.remove('show');
            
            // Clear cart
            cart = [];
            updateCart();
            document.getElementById('discount').value = '0';
            document.getElementById('checkoutDiscount').value = '0';
            document.getElementById('customerSelect').value = '';
            
            // Reload products to update stock
            loadProducts();

            sendSaleReceipts(data.sale_id);

            // Show receipt options
            appConfirm('Sale completed! Would you like to print the receipt?', { okText: 'Print receipt', cancelText: 'No thanks', icon: 'check' })
                .then(yes => { if (yes) printReceipt(data.sale_id); });
        } else {
            showAlert(data.message || 'Error completing sale', 'error');
            if (data.requires_shift) {
                appConfirm('No open cashier shift. Open the shift page now?', { okText: 'Open shift' })
                    .then(yes => { if (yes) window.location.href = 'cashier-shifts.php'; });
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error completing sale', 'error');
    });
}

function sendSaleReceipts(saleId) {
    fetch('api/send_sale_receipts.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({sale_id: saleId})
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success || !data.notifications) return;
        const attempted = Object.entries(data.notifications).filter(([, result]) => !result.skipped);
        if (!attempted.length) return;
        const sent = attempted.filter(([, result]) => result.success).map(([channel]) => channel === 'email' ? 'email' : 'WhatsApp');
        const failed = attempted.filter(([, result]) => !result.success);
        if (failed.length) {
            const reason = failed.map(([channel, result]) => `${channel === 'email' ? 'Email' : 'WhatsApp'}: ${result.message || 'delivery failed'}`).join('; ');
            showAlert(sent.length ? `Sent ${sent.join(' and ')}. ${reason}` : `Sale saved, but receipt delivery failed. ${reason}`, 'warning');
        } else {
            showAlert(`Receipt sent by ${sent.join(' and ')}.`, 'success');
        }
    })
    .catch(() => showAlert('Sale saved. Could not reach the local receipt messaging service.', 'warning'));
}

function printReceipt(saleId) {
    window.open(`receipt.php?id=${saleId}`, '_blank');
}

// Barcode Scanner Integration
function initBarcodeScanner() {
    if (typeof BarcodeScanner !== 'undefined') {
        barcodeScanner = new BarcodeScanner(handleBarcodeScanned);
        console.log('Barcode scanner initialized');
        
        // Add visual indicator
        const indicator = document.createElement('div');
        indicator.id = 'scannerIndicator';
        indicator.innerHTML = '<i class="fas fa-barcode"></i> Scanner Ready';
        indicator.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: var(--primary-color); color: white; padding: 10px 15px; border-radius: 5px; font-size: 12px; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2);';
        document.body.appendChild(indicator);
    }
}

function handleBarcodeScanned(barcode) {
    console.log('Looking for barcode:', barcode);
    
    // Search for product by barcode or SKU
    const product = products.find(p => 
        p.barcode === barcode || 
        p.sku === barcode ||
        p.barcode === barcode.trim() ||
        p.sku === barcode.trim()
    );
    
    if (product) {
        addToCart(product);
        showAlert(`${product.name} added to cart`, 'success');
        
        // Visual feedback
        flashScreen('#2ecc71');
    } else {
        showAlert('Product not found for barcode: ' + barcode, 'error');
        flashScreen('#e74c3c');
    }
}

function flashScreen(color) {
    const flash = document.createElement('div');
    flash.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: ${color};
        opacity: 0.3;
        z-index: 9999;
        pointer-events: none;
        animation: fadeOut 0.3s;
    `;
    document.body.appendChild(flash);
    setTimeout(() => flash.remove(), 300);
}

// Add CSS animation for flash
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from { opacity: 0.3; }
        to { opacity: 0; }
    }
`;
document.head.appendChild(style);

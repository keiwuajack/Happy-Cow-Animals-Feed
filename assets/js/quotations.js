// Quotations Management JavaScript

let quoteItems = [];
let products = [];
let customers = [];
let editingQuoteId = null;

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[character]);
}

document.addEventListener('DOMContentLoaded', function() {
    loadQuotations();
    loadCustomers();
    loadProducts();
    loadStats();
    
    document.getElementById('newQuoteBtn').addEventListener('click', openQuoteModal);
    document.getElementById('quoteForm').addEventListener('submit', saveQuotation);
    document.getElementById('statusFilter').addEventListener('change', loadQuotations);
    document.getElementById('quoteDiscount').addEventListener('input', updateQuoteSummary);
    document.getElementById('productSearch').addEventListener('input', filterProductsForQuote);
    document.getElementById('productDropdown').addEventListener('click', function(event) {
        const option = event.target.closest('[data-product-id]');
        if (option) addProductToQuote(option.dataset.productId);
    });
    
    // Modal close
    document.querySelector('#quoteModal .close').addEventListener('click', closeQuoteModal);
    document.getElementById('quoteModal').addEventListener('click', function(event) {
        if (event.target === this) closeQuoteModal();
    });
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && document.getElementById('quoteModal').classList.contains('show')) {
            closeQuoteModal();
        }
    });
});

function loadQuotations() {
    const status = document.getElementById('statusFilter').value;
    const url = status ? `api/get_quotations.php?status=${status}` : 'api/get_quotations.php';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayQuotations(data.quotations);
            }
        })
        .catch(error => console.error('Error:', error));
}

function displayQuotations(quotations) {
    const tbody = document.querySelector('#quotationsTable tbody');
    
    if (quotations.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No quotations found</td></tr>';
        return;
    }
    
    tbody.innerHTML = quotations.map(quote => `
        <tr>
            <td><strong>${quote.quote_number}</strong></td>
            <td>${quote.customer_name || 'Walk-in Customer'}</td>
            <td>${new Date(quote.created_at).toLocaleDateString()}</td>
            <td>${quote.valid_until ? new Date(quote.valid_until).toLocaleDateString() : 'N/A'}</td>
            <td><strong>KSh ${parseFloat(quote.total).toFixed(2)}</strong></td>
            <td><span class="badge badge-${getStatusColor(quote.status)}">${quote.status.toUpperCase()}</span></td>
            <td>
                <button class="btn btn-sm" onclick="viewQuote(${quote.id})" title="View">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm" onclick="sendQuotationEmail(${quote.id})" title="Email quotation to customer">
                    <i class="fas fa-envelope"></i>
                </button>
                ${quote.status === 'pending' ? `
                    <button class="btn btn-sm btn-primary" onclick="convertToSale(${quote.id})" title="Convert to Sale">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                    <button class="btn btn-sm" onclick="editQuote(${quote.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                ` : ''}
                <button class="btn btn-sm" onclick="downloadQuotePDF(${quote.id})" title="Download PDF">
                    <i class="fas fa-file-pdf"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function sendQuotationEmail(quotationId) {
    const button = document.querySelector(`#quotationsTable button[onclick="sendQuotationEmail(${quotationId})"]`);
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>';
    }
    try {
        const response = await fetch('api/send_quotation_email.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({quotation_id: quotationId})
        });
        const data = await response.json();
        showAlert(data.message || (data.success ? 'Quotation emailed.' : 'Could not email quotation.'), data.success ? 'success' : 'error');
    } catch (error) {
        showAlert('Could not connect to the email service. Check the PC internet connection and SMTP settings.', 'error');
    } finally {
        if (button) {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-envelope"></i>';
        }
    }
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'accepted': 'success',
        'rejected': 'danger',
        'converted': 'info'
    };
    return colors[status] || 'secondary';
}

function loadCustomers() {
    fetch('api/get_customers.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                customers = data.customers;
                const select = document.getElementById('quoteCustomer');
                select.innerHTML = '<option value="">Select Customer</option>';
                data.customers.forEach(customer => {
                    const option = document.createElement('option');
                    option.value = customer.id;
                    option.textContent = `${customer.name} - ${customer.phone}`;
                    select.appendChild(option);
                });
            }
        });
}

function loadProducts() {
    fetch('api/get_products.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                products = data.products;
            }
        });
}

function loadStats() {
    fetch('api/get_quotation_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalQuotes').textContent = data.stats.total || 0;
                document.getElementById('pendingQuotes').textContent = data.stats.pending || 0;
                document.getElementById('convertedQuotes').textContent = data.stats.converted || 0;
                document.getElementById('quoteValue').textContent = 'KSh ' + parseFloat(data.stats.total_value || 0).toFixed(2);
            }
        });
}

function openQuoteModal() {
    editingQuoteId = null;
    quoteItems = [];
    document.getElementById('quoteForm').reset();
    document.getElementById('modalTitle').textContent = 'New Quotation';
    
    // Set valid until to 30 days from now
    const validUntil = new Date();
    validUntil.setDate(validUntil.getDate() + 30);
    document.getElementById('validUntil').valueAsDate = validUntil;
    
    updateQuoteItemsDisplay();
    updateQuoteSummary();
    const modal = document.getElementById('quoteModal');
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('show');
    document.getElementById('quoteCustomer').focus();
}

function closeQuoteModal() {
    const modal = document.getElementById('quoteModal');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.getElementById('productDropdown').hidden = true;
}

function filterProductsForQuote() {
    const search = document.getElementById('productSearch').value.trim().toLowerCase();
    const dropdown = document.getElementById('productDropdown');
    if (search.length < 2) {
        dropdown.hidden = true;
        return;
    }
    
    const filtered = products.filter(p => 
        String(p.name || '').toLowerCase().includes(search) ||
        (p.sku && String(p.sku).toLowerCase().includes(search))
    );
    
    showProductDropdown(filtered);
}

function showProductDropdown(matches) {
    const dropdown = document.getElementById('productDropdown');
    dropdown.innerHTML = matches.length ? matches.map(product => `
        <button type="button" class="quote-product-option" role="option" data-product-id="${Number(product.id)}">
            <span class="quote-product-option-main">
                <span class="quote-product-option-name">${escapeHtml(product.name)}</span>
                <span class="quote-product-option-meta">${product.sku ? `SKU ${escapeHtml(product.sku)} - ` : ''}Stock: ${Number(product.quantity) || 0}</span>
            </span>
            <span class="quote-product-option-price">KSh ${parseFloat(product.selling_price || 0).toFixed(2)}</span>
        </button>
    `).join('') : '<div class="quote-product-empty">No matching products found</div>';
    dropdown.hidden = false;
}

function addProductToQuote(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;
    
    // Check if already added
    const existing = quoteItems.find(item => item.product_id == productId);
    if (existing) {
        existing.quantity++;
        existing.total = existing.quantity * existing.unit_price;
    } else {
        quoteItems.push({
            product_id: product.id,
            product_name: product.name,
            quantity: 1,
            unit_price: parseFloat(product.selling_price),
            total: parseFloat(product.selling_price)
        });
    }
    
    updateQuoteItemsDisplay();
    updateQuoteSummary();
    document.getElementById('productSearch').value = '';
    document.getElementById('productDropdown').hidden = true;
    document.getElementById('productSearch').focus();
}

function updateQuoteItemsDisplay() {
    const tbody = document.getElementById('quoteItems');
    const itemCount = document.getElementById('quoteItemCount');
    itemCount.textContent = `${quoteItems.length} ${quoteItems.length === 1 ? 'item' : 'items'}`;
    
    if (quoteItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5"><div class="quotation-empty-state"><span><i class="fas fa-box-open" aria-hidden="true"></i></span><strong>No products added yet</strong><small>Search for a product above to start this quotation.</small></div></td></tr>';
        return;
    }
    
    tbody.innerHTML = quoteItems.map((item, index) => `
        <tr>
            <td>${escapeHtml(item.product_name)}</td>
            <td><input class="quotation-item-input quantity-input" type="number" value="${item.quantity}" min="1" step="1" aria-label="Quantity for ${escapeHtml(item.product_name)}" onchange="updateQuoteItemQuantity(${index}, this.value)"></td>
            <td><input class="quotation-item-input" type="number" value="${item.unit_price}" min="0" step="0.01" aria-label="Unit price for ${escapeHtml(item.product_name)}" onchange="updateQuoteItemPrice(${index}, this.value)"></td>
            <td class="quotation-item-total">KSh ${Number(item.total).toFixed(2)}</td>
            <td><button type="button" class="quotation-remove-item" onclick="removeQuoteItem(${index})" aria-label="Remove ${escapeHtml(item.product_name)}"><i class="fas fa-trash" aria-hidden="true"></i></button></td>
        </tr>
    `).join('');
}

function updateQuoteItemQuantity(index, quantity) {
    quoteItems[index].quantity = parseInt(quantity) || 1;
    quoteItems[index].total = quoteItems[index].quantity * quoteItems[index].unit_price;
    updateQuoteItemsDisplay();
    updateQuoteSummary();
}

function updateQuoteItemPrice(index, price) {
    quoteItems[index].unit_price = parseFloat(price) || 0;
    quoteItems[index].total = quoteItems[index].quantity * quoteItems[index].unit_price;
    updateQuoteItemsDisplay();
    updateQuoteSummary();
}

function removeQuoteItem(index) {
    quoteItems.splice(index, 1);
    updateQuoteItemsDisplay();
    updateQuoteSummary();
}

function updateQuoteSummary() {
    const subtotal = quoteItems.reduce((sum, item) => sum + item.total, 0);
    const discount = parseFloat(document.getElementById('quoteDiscount').value) || 0;
    const total = subtotal - discount;
    
    document.getElementById('quoteSubtotal').textContent = 'KSh ' + subtotal.toFixed(2);
    document.getElementById('quoteTotal').textContent = 'KSh ' + total.toFixed(2);
}

function saveQuotation(e) {
    e.preventDefault();
    
    if (quoteItems.length === 0) {
        showAlert('Please add at least one item', 'error');
        return;
    }

    const subtotal = quoteItems.reduce((sum, item) => sum + item.total, 0);
    const discount = parseFloat(document.getElementById('quoteDiscount').value) || 0;
    if (discount < 0 || discount > subtotal) {
        showAlert('Discount cannot be greater than the quotation subtotal', 'error');
        return;
    }
    
    const data = {
        customer_id: document.getElementById('quoteCustomer').value || null,
        valid_until: document.getElementById('validUntil').value,
        notes: document.getElementById('quoteNotes').value,
        discount: parseFloat(document.getElementById('quoteDiscount').value) || 0,
        items: quoteItems
    };
    
    const submitButton = document.querySelector('.quotation-save-button');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Saving...';

    fetch('api/create_quotation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Quotation created successfully!', 'success');
            closeQuoteModal();
            loadQuotations();
            loadStats();
        } else {
            showAlert(data.message || 'Error creating quotation', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error creating quotation', 'error');
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-save" aria-hidden="true"></i> Save Quotation';
    });
}

async function convertToSale(quoteId) {
    if (!(await appConfirm('Convert this quotation to a sale?'))) return;
    
    fetch('api/convert_quotation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({quotation_id: quoteId})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Quotation converted to sale successfully!', 'success');
            loadQuotations();
            loadStats();
            
            appConfirm('View the sale receipt?', { okText: 'View receipt', icon: 'receipt' })
                .then(yes => { if (yes) window.open(`receipt.php?id=${data.sale_id}`, '_blank'); });
        } else {
            showAlert(data.message || 'Error converting quotation', 'error');
        }
    });
}

function downloadQuotePDF(quoteId) {
    window.open(`api/quotation_pdf.php?id=${quoteId}`, '_blank');
}

function viewQuote(quoteId) {
    window.open(`api/quotation_pdf.php?id=${quoteId}`, '_blank');
}

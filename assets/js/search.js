// Global Search Functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('globalSearch');
    const searchResults = document.createElement('div');
    searchResults.className = 'search-results';
    searchResults.style.display = 'none';
    
    if (searchInput) {
        searchInput.parentNode.appendChild(searchResults);
        
        let searchTimeout;
        
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
        
        searchInput.addEventListener('blur', function() {
            setTimeout(() => {
                searchResults.style.display = 'none';
            }, 200);
        });
        
        searchInput.addEventListener('focus', function() {
            if (searchInput.value.trim().length >= 2) {
                searchResults.style.display = 'block';
            }
        });
    }
    
    function performSearch(query) {
        fetch(`api/global_search.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                displayResults(data);
            })
            .catch(error => {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="search-error">Search failed</div>';
                searchResults.style.display = 'block';
            });
    }
    
    function displayResults(data) {
        if (!data.products?.length && !data.customers?.length && !data.sales?.length) {
            searchResults.innerHTML = '<div class="search-no-results">No results found</div>';
            searchResults.style.display = 'block';
            return;
        }
        
        let html = '';
        
        // Products
        if (data.products?.length > 0) {
            html += '<div class="search-section">';
            html += '<div class="search-section-title"><i class="fas fa-box"></i> Products</div>';
            data.products.forEach(product => {
                html += `
                    <a href="products.php" class="search-item" onclick="highlightProduct(${product.id})">
                        <div class="search-item-icon"><i class="fas fa-box"></i></div>
                        <div class="search-item-content">
                            <div class="search-item-title">${product.name}</div>
                            <div class="search-item-subtitle">Stock: ${product.stock} | KSh ${parseFloat(product.price).toFixed(2)}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        // Customers
        if (data.customers?.length > 0) {
            html += '<div class="search-section">';
            html += '<div class="search-section-title"><i class="fas fa-users"></i> Customers</div>';
            data.customers.forEach(customer => {
                html += `
                    <a href="customers.php" class="search-item" onclick="highlightCustomer(${customer.id})">
                        <div class="search-item-icon"><i class="fas fa-user"></i></div>
                        <div class="search-item-content">
                            <div class="search-item-title">${customer.name}</div>
                            <div class="search-item-subtitle">${customer.phone} | ${customer.email || 'No email'}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        // Sales
        if (data.sales?.length > 0) {
            html += '<div class="search-section">';
            html += '<div class="search-section-title"><i class="fas fa-receipt"></i> Sales</div>';
            data.sales.forEach(sale => {
                html += `
                    <a href="sales.php" class="search-item" onclick="highlightSale(${sale.id})">
                        <div class="search-item-icon"><i class="fas fa-receipt"></i></div>
                        <div class="search-item-content">
                            <div class="search-item-title">Invoice #${sale.invoice_number}</div>
                            <div class="search-item-subtitle">${sale.customer_name} | KSh ${parseFloat(sale.grand_total).toFixed(2)} | ${new Date(sale.created_at).toLocaleDateString()}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        searchResults.innerHTML = html;
        searchResults.style.display = 'block';
    }
});

// Helper functions to highlight search results on target pages
function highlightProduct(id) {
    localStorage.setItem('highlightProduct', id);
}

function highlightCustomer(id) {
    localStorage.setItem('highlightCustomer', id);
}

function highlightSale(id) {
    localStorage.setItem('highlightSale', id);
}

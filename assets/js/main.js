// Main JavaScript file

const nativeFetch = window.fetch.bind(window);
window.fetch = function(input, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const tokenElement = document.querySelector('meta[name="csrf-token"]');

    if (method !== 'GET' && method !== 'HEAD' && tokenElement) {
        options.headers = new Headers(options.headers || {});
        options.headers.set('X-CSRF-Token', tokenElement.content);
    }

    return nativeFetch(input, options);
};

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Set active navigation item
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href === currentPage) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
    
    // User dropdown menu
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userMenuToggle && userDropdown) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
            userMenuToggle.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenuToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('show');
                userMenuToggle.classList.remove('active');
            }
        });
    }
    
    // Initialize welcome message and datetime
    updateDateTime();
    setInterval(updateDateTime, 1000); // Update every second
    
    // Load notification count
    loadNotificationCount();
    // Refresh notification count every 2 minutes
    setInterval(loadNotificationCount, 120000);
});

// Update current date and time
function updateDateTime() {
    const now = new Date();
    
    // Update date
    const dateElement = document.getElementById('currentDate');
    if (dateElement) {
        const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
        dateElement.textContent = now.toLocaleDateString('en-US', options);
    }
    
    // Update time
    const timeElement = document.getElementById('currentTime');
    if (timeElement) {
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const seconds = now.getSeconds().toString().padStart(2, '0');
        timeElement.textContent = `${hours}:${minutes}:${seconds}`;
    }
}

// Load notification count for header badge
function loadNotificationCount() {
    fetch('api/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const criticalWarningCount = data.counts.critical + data.counts.warning;
                
                // Update header badge only
                const headerBadge = document.getElementById('headerNotifBadge');
                if (headerBadge) {
                    if (criticalWarningCount > 0) {
                        headerBadge.textContent = criticalWarningCount > 99 ? '99+' : criticalWarningCount;
                        headerBadge.style.display = 'inline-block';
                    } else {
                        headerBadge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => console.error('Error loading notification count:', error));
}

// Utility functions
function formatCurrency(amount) {
    return 'KSh ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Floating dialogs and toasts (replace the browser's native alert/confirm/prompt)
(function injectDialogStyles() {
    const css = `
    .hc-toasts{position:fixed;top:20px;right:20px;z-index:100000;display:flex;flex-direction:column;gap:10px;max-width:380px;pointer-events:none}
    .hc-toast{pointer-events:auto;display:flex;align-items:flex-start;gap:12px;padding:14px 16px;background:#fff;border-radius:12px;border-left:5px solid #27ae60;box-shadow:0 10px 30px rgba(0,0,0,.18);font-size:14px;color:#2c3e50;animation:hcIn .25s ease}
    .hc-toast i{font-size:20px;margin-top:1px;color:#27ae60}
    .hc-toast.error{border-left-color:#e74c3c}.hc-toast.error i{color:#e74c3c}
    .hc-toast.warning{border-left-color:#f39c12}.hc-toast.warning i{color:#f39c12}
    .hc-toast.info{border-left-color:#3498db}.hc-toast.info i{color:#3498db}
    .hc-toast.out{opacity:0;transform:translateX(30px);transition:all .25s}
    .hc-overlay{position:fixed;inset:0;z-index:100001;background:rgba(20,30,40,.5);display:flex;align-items:center;justify-content:center;padding:16px;animation:hcFade .15s ease}
    .hc-dialog{background:#fff;border-radius:16px;width:100%;max-width:420px;padding:26px;box-shadow:0 20px 50px rgba(0,0,0,.3);animation:hcPop .2s ease;text-align:center}
    .hc-dialog .hc-icon{width:58px;height:58px;margin:0 auto 14px;border-radius:50%;background:#e8f6ee;color:#27ae60;display:flex;align-items:center;justify-content:center;font-size:26px}
    .hc-dialog.danger .hc-icon{background:#fdecea;color:#e74c3c}
    .hc-dialog p{margin:0 0 20px;font-size:16px;line-height:1.5;color:#2c3e50}
    .hc-dialog input{width:100%;padding:11px 12px;margin-bottom:18px;border:1px solid #dfe4e8;border-radius:8px;font-size:15px}
    .hc-actions{display:flex;gap:10px}
    .hc-actions button{flex:1;padding:12px;border:0;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer}
    .hc-cancel{background:#eef1f3;color:#4a5560}
    .hc-ok{background:#27ae60;color:#fff}.hc-dialog.danger .hc-ok{background:#e74c3c}
    @keyframes hcIn{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:none}}
    @keyframes hcFade{from{opacity:0}to{opacity:1}}
    @keyframes hcPop{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:none}}
    @media(max-width:600px){.hc-toasts{left:12px;right:12px;max-width:none}}`;
    const style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);
})();

function hcEscape(text) {
    const d = document.createElement('div');
    d.textContent = String(text);
    return d.innerHTML;
}

function showAlert(message, type = 'success') {
    let box = document.querySelector('.hc-toasts');
    if (!box) {
        box = document.createElement('div');
        box.className = 'hc-toasts';
        document.body.appendChild(box);
    }
    const icons = { success: 'check-circle', error: 'exclamation-circle', danger: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };
    const kind = type === 'danger' ? 'error' : type;
    const toast = document.createElement('div');
    toast.className = `hc-toast ${kind}`;
    toast.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}"></i><div>${hcEscape(message)}</div>`;
    box.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('out');
        setTimeout(() => toast.remove(), 260);
    }, kind === 'error' ? 5000 : 3500);
}

function hcDialog(message, { prompt = false, danger = false, okText = 'OK', cancelText = 'Cancel', icon = 'question' } = {}) {
    return new Promise(resolve => {
        const overlay = document.createElement('div');
        overlay.className = 'hc-overlay';
        overlay.innerHTML = `
            <div class="hc-dialog${danger ? ' danger' : ''}" role="dialog" aria-modal="true">
                <div class="hc-icon"><i class="fas fa-${danger ? 'exclamation-triangle' : icon}"></i></div>
                <p>${hcEscape(message)}</p>
                ${prompt ? '<input type="text" class="hc-input">' : ''}
                <div class="hc-actions">
                    <button type="button" class="hc-cancel">${cancelText}</button>
                    <button type="button" class="hc-ok">${okText}</button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
        const input = overlay.querySelector('.hc-input');
        const okBtn = overlay.querySelector('.hc-ok');
        const finish = value => {
            document.removeEventListener('keydown', onKey, true);
            overlay.remove();
            resolve(value);
        };
        const onKey = e => {
            if (e.key === 'Escape') { e.preventDefault(); finish(prompt ? null : false); }
            if (e.key === 'Enter') { e.preventDefault(); finish(prompt ? input.value : true); }
        };
        document.addEventListener('keydown', onKey, true);
        okBtn.onclick = () => finish(prompt ? input.value : true);
        overlay.querySelector('.hc-cancel').onclick = () => finish(prompt ? null : false);
        (input || okBtn).focus();
    });
}

function showNotification(message, type = 'info') {
    showAlert(message, type);
}

function appConfirm(message, options = {}) {
    return hcDialog(message, options);
}

function appPrompt(message, options = {}) {
    return hcDialog(message, { ...options, prompt: true, icon: 'pen' });
}

// Any remaining native alert() becomes a floating toast
window.alert = function (message) {
    const text = String(message);
    showAlert(text, /error|fail|invalid|cannot|unable|not /i.test(text) ? 'error' : 'info');
};

// AJAX helper function
function ajax(url, data, method = 'POST') {
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    }).then(response => response.json());
}

// Global Search Functionality
function initializeGlobalSearch() {
    const searchInput = document.getElementById('globalSearch');
    if (!searchInput) return;
    
    const searchResults = document.createElement('div');
    searchResults.className = 'search-results';
    searchResults.style.display = 'none';
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
            performSearch(query, searchResults);
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

function performSearch(query, searchResults) {
    fetch(`api/global_search.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) throw new Error(data.error);
            displaySearchResults(data, searchResults);
        })
        .catch(error => {
            console.error('Search error:', error);
            searchResults.innerHTML = '<div class="search-error">Search failed</div>';
            searchResults.style.display = 'block';
        });
}

function displaySearchResults(data, searchResults) {
    const esc = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
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
                <a href="products.php" class="search-item">
                    <div class="search-item-icon"><i class="fas fa-box"></i></div>
                    <div class="search-item-content">
                        <div class="search-item-title">                        ${esc(product.name)}</div>
                                                <div class="search-item-subtitle">Stock: ${esc(product.stock)} | KSh ${parseFloat(product.price).toFixed(2)}</div>
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
                <a href="customers.php" class="search-item">
                    <div class="search-item-icon"><i class="fas fa-user"></i></div>
                    <div class="search-item-content">
                        <div class="search-item-title">                        ${esc(customer.name)}</div>
                                                <div class="search-item-subtitle">${esc(customer.phone)}${customer.email ? ' | ' + esc(customer.email) : ''}</div>
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
            const date = new Date(sale.created_at);
            html += `
                <a href="sales.php" class="search-item">
                    <div class="search-item-icon"><i class="fas fa-receipt"></i></div>
                    <div class="search-item-content">
                        <div class="search-item-title">                        Invoice #${esc(sale.invoice_number)}</div>
                                                <div class="search-item-subtitle">${esc(sale.customer_name || 'Walk-in Customer')} | KSh ${parseFloat(sale.grand_total).toFixed(2)} | ${date.toLocaleDateString()}</div>
                    </div>
                </a>
            `;
        });
        html += '</div>';
    }
    
    searchResults.innerHTML = html;
    searchResults.style.display = 'block';
}

// Initialize search when DOM is ready
document.addEventListener('DOMContentLoaded', initializeGlobalSearch);

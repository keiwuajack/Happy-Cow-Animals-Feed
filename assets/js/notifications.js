// Notifications Management
let allNotifications = [];
let currentFilter = 'all';

document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    // Auto-refresh every 2 minutes
    setInterval(loadNotifications, 120000);
});

function loadNotifications() {
    fetch('api/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allNotifications = data.notifications;
                displayStats(data.counts, data.total);
                displayNotifications(allNotifications);
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function displayStats(counts, total) {
    const statsHtml = `
        <div class="stat-card critical">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3>${counts.critical}</h3>
                <p>Critical Alerts</p>
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stat-content">
                <h3>${counts.warning}</h3>
                <p>Warnings</p>
            </div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="stat-content">
                <h3>${counts.info}</h3>
                <p>Information</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #f0f0f0; color: var(--dark-color);">
                <i class="fas fa-bell"></i>
            </div>
            <div class="stat-content">
                <h3>${total}</h3>
                <p>Total Notifications</p>
            </div>
        </div>
    `;
    
    document.getElementById('statsSummary').innerHTML = statsHtml;
    
    // Update header notification badge
    updateNotificationBadge(counts.critical + counts.warning);
}

function displayNotifications(notifications) {
    const grid = document.getElementById('notificationsGrid');
    
    if (notifications.length === 0) {
        grid.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>No Notifications</h3>
                <p>Everything looks good! No alerts at this time.</p>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = notifications.map(notif => {
        const icon = getNotificationIcon(notif.type);
        const metaInfo = getMetaInfo(notif);
        
        return `
            <div class="notification-card ${notif.severity}">
                <div class="notification-icon">
                    <i class="fas fa-${icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notif.title}</div>
                    <div class="notification-message">${notif.message}</div>
                    <div class="notification-meta">
                        ${metaInfo}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function getNotificationIcon(type) {
    const icons = {
        'low_stock': 'box-open',
        'out_of_stock': 'times-circle',
        'expired': 'calendar-times',
        'expiring_soon': 'clock'
    };
    return icons[type] || 'bell';
}

function getMetaInfo(notif) {
    let meta = [];
    
    if (notif.category) {
        meta.push(`<span><i class="fas fa-tag"></i> ${notif.category}</span>`);
    }
    
    if (notif.quantity !== undefined) {
        meta.push(`<span><i class="fas fa-cubes"></i> ${notif.quantity} ${notif.unit}</span>`);
    }
    
    if (notif.days_left !== undefined) {
        meta.push(`<span><i class="fas fa-calendar"></i> ${notif.days_left} days left</span>`);
    }
    
    if (notif.expiry_date) {
        meta.push(`<span><i class="fas fa-calendar-alt"></i> Expires: ${formatDate(notif.expiry_date)}</span>`);
    }
    
    return meta.join('');
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function filterNotifications(filter) {
    currentFilter = filter;
    
    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');
    
    let filtered = allNotifications;
    
    if (filter === 'critical' || filter === 'warning' || filter === 'info') {
        filtered = allNotifications.filter(n => n.severity === filter);
    } else if (filter !== 'all') {
        filtered = allNotifications.filter(n => n.type === filter);
    }
    
    displayNotifications(filtered);
}

function updateNotificationBadge(count) {
    // Update badge in header if it exists
    let badge = document.querySelector('.notification-badge');
    if (!badge && count > 0) {
        // Create badge if it doesn't exist
        const bellIcon = document.querySelector('.header a[href="notifications.php"] i');
        if (bellIcon) {
            badge = document.createElement('span');
            badge.className = 'notification-badge';
            bellIcon.parentElement.style.position = 'relative';
            bellIcon.parentElement.appendChild(badge);
        }
    }
    
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

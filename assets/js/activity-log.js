// Activity Log Management
let activities = [];
let filteredActivities = [];
let currentPage = 1;
let itemsPerPage = 50;

document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    loadActivities();
    loadStats();
    
    // Set default dates (last 7 days)
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 7);
    
    document.getElementById('startDate').valueAsDate = startDate;
    document.getElementById('endDate').valueAsDate = endDate;
    
    // Event listeners
    document.getElementById('applyFilters').addEventListener('click', applyFilters);
    document.getElementById('resetFilters').addEventListener('click', resetFilters);
    document.getElementById('searchLog').addEventListener('input', applyFilters);
    document.getElementById('clearLogsBtn').addEventListener('click', clearOldLogs);
});

function loadUsers() {
    fetch('api/get_users.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('filterUser');
                data.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.full_name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading users:', error));
}

function loadActivities() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    let url = 'api/get_activity_log.php';
    if (startDate && endDate) {
        url += `?start_date=${startDate}&end_date=${endDate}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                activities = data.activities;
                filteredActivities = activities;
                displayActivities();
            }
        })
        .catch(error => console.error('Error loading activities:', error));
}

function loadStats() {
    fetch('api/get_activity_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalLogs').textContent = data.total_logs.toLocaleString();
                document.getElementById('todayLogs').textContent = data.today_logs.toLocaleString();
                document.getElementById('activeUsers').textContent = data.active_users;
                document.getElementById('criticalActions').textContent = data.critical_actions.toLocaleString();
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

function applyFilters() {
    const searchTerm = document.getElementById('searchLog').value.toLowerCase();
    const filterUser = document.getElementById('filterUser').value;
    const filterModule = document.getElementById('filterModule').value;
    
    filteredActivities = activities.filter(activity => {
        const matchesSearch = !searchTerm || 
            activity.action.toLowerCase().includes(searchTerm) ||
            activity.description.toLowerCase().includes(searchTerm) ||
            activity.user_name.toLowerCase().includes(searchTerm);
        
        const matchesUser = !filterUser || activity.user_id == filterUser;
        const matchesModule = !filterModule || activity.module === filterModule;
        
        return matchesSearch && matchesUser && matchesModule;
    });
    
    currentPage = 1;
    displayActivities();
}

function resetFilters() {
    document.getElementById('filterUser').value = '';
    document.getElementById('filterModule').value = '';
    document.getElementById('searchLog').value = '';
    
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 7);
    
    document.getElementById('startDate').valueAsDate = startDate;
    document.getElementById('endDate').valueAsDate = endDate;
    
    loadActivities();
}

function displayActivities() {
    const tbody = document.querySelector('#activityTable tbody');
    tbody.innerHTML = '';
    
    if (filteredActivities.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No activities found</td></tr>';
        updatePagination();
        return;
    }
    
    // Calculate pagination
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredActivities.length);
    const pageActivities = filteredActivities.slice(startIndex, endIndex);
    
    pageActivities.forEach(activity => {
        const row = document.createElement('tr');
        
        // Determine action badge color
        let actionClass = 'badge-info';
        if (activity.action.includes('Delete')) actionClass = 'badge-danger';
        else if (activity.action.includes('Add') || activity.action.includes('Create')) actionClass = 'badge-success';
        else if (activity.action.includes('Update') || activity.action.includes('Edit')) actionClass = 'badge-warning';
        
        row.innerHTML = `
            <td>${formatDateTime(activity.created_at)}</td>
            <td><strong>${activity.user_name}</strong></td>
            <td><span class="badge ${actionClass}">${activity.action}</span></td>
            <td><span class="badge badge-primary">${activity.module}</span></td>
            <td>${activity.description || '-'}</td>
            <td><small>${activity.ip_address || '-'}</small></td>
        `;
        tbody.appendChild(row);
    });
    
    updatePagination();
}

function updatePagination() {
    const totalPages = Math.ceil(filteredActivities.length / itemsPerPage);
    const pagination = document.getElementById('pagination');
    
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    let html = `<span style="color: #666;">Page ${currentPage} of ${totalPages} (${filteredActivities.length} records)</span>`;
    
    html += '<div style="display: flex; gap: 5px;">';
    
    // Previous button
    html += `<button class="btn btn-sm" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
        <i class="fas fa-chevron-left"></i>
    </button>`;
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            html += `<button class="btn btn-sm ${i === currentPage ? 'btn-primary' : ''}" onclick="changePage(${i})">${i}</button>`;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            html += '<span style="padding: 0 5px;">...</span>';
        }
    }
    
    // Next button
    html += `<button class="btn btn-sm" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">
        <i class="fas fa-chevron-right"></i>
    </button>`;
    
    html += '</div>';
    pagination.innerHTML = html;
}

function changePage(page) {
    currentPage = page;
    displayActivities();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function clearOldLogs() {
    if (!(await appConfirm('This will delete activity logs older than 90 days. Continue?', {danger: true}))) {
        return;
    }
    
    fetch('api/clear_old_logs.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(`${data.deleted} old logs deleted successfully`, 'success');
            loadActivities();
            loadStats();
        } else {
            showAlert(data.message || 'Error clearing logs', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error clearing logs', 'error');
    });
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

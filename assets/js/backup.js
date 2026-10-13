// Database Backup Management

document.addEventListener('DOMContentLoaded', function() {
    loadBackupStats();
    loadBackupFiles();
    
    // Event listeners
    document.getElementById('createBackupBtn').addEventListener('click', createBackup);
    document.getElementById('restoreBackupBtn').addEventListener('click', () => {
        document.getElementById('restoreFile').click();
    });
    document.getElementById('restoreFile').addEventListener('change', restoreBackup);
    document.getElementById('autoBackupBtn').addEventListener('click', openAutoBackupModal);
    document.getElementById('cleanupBtn').addEventListener('click', cleanupOldBackups);
    document.getElementById('autoBackupForm').addEventListener('submit', saveAutoBackupSettings);
    
    // Modal controls
    const modal = document.getElementById('autoBackupModal');
    const closeBtn = modal.querySelector('.close');
    const cancelBtn = document.getElementById('cancelAutoBackup');
    
    closeBtn.onclick = () => modal.classList.remove('show');
    cancelBtn.onclick = () => modal.classList.remove('show');
    
    window.onclick = (e) => {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    };
});

function loadBackupStats() {
    fetch('api/get_backup_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('dbSize').textContent = data.db_size;
                document.getElementById('totalBackups').textContent = data.total_backups;
                document.getElementById('lastBackup').textContent = data.last_backup;
                document.getElementById('backupSize').textContent = data.backup_size;
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

function loadBackupFiles() {
    fetch('api/get_backup_files.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayBackupFiles(data.files);
            }
        })
        .catch(error => console.error('Error loading backup files:', error));
}

function displayBackupFiles(files) {
    const tbody = document.querySelector('#backupsTable tbody');
    tbody.innerHTML = '';
    
    if (files.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No backup files found</td></tr>';
        return;
    }
    
    files.forEach(file => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><i class="fas fa-file-archive"></i> <strong>${file.name}</strong></td>
            <td>${file.size}</td>
            <td>${file.date}</td>
            <td>
                <button class="btn-icon" onclick="downloadBackup('${file.name}')" title="Download">
                    <i class="fas fa-download"></i>
                </button>
                <button class="btn-icon text-danger" onclick="deleteBackup('${file.name}')" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

async function createBackup() {
    if (!(await appConfirm('Create a new database backup?'))) {
        return;
    }
    
    const btn = document.getElementById('createBackupBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    
    fetch('api/create_backup.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            loadBackupStats();
            loadBackupFiles();
            
            // Trigger download
            if (data.filename) {
                window.location.href = `api/download_backup.php?file=${data.filename}`;
            }
        } else {
            showAlert(data.message || 'Error creating backup', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error creating backup', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download"></i> Create Backup Now';
    });
}

async function restoreBackup(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    if (!(await appConfirm('WARNING: This will overwrite all current data! Are you sure you want to restore from this backup?', {danger: true}))) {
        e.target.value = '';
        return;
    }
    
    const formData = new FormData();
    formData.append('backup_file', file);
    
    showAlert('Restoring backup... Please wait.', 'info');
    
    fetch('api/restore_backup.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message + ' Page will reload...', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert(data.message || 'Error restoring backup', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error restoring backup', 'error');
    })
    .finally(() => {
        e.target.value = '';
    });
}

function downloadBackup(filename) {
    window.location.href = `api/download_backup.php?file=${filename}`;
}

async function deleteBackup(filename) {
    if (!(await appConfirm(`Delete backup file: ${filename}?`, {danger: true}))) {
        return;
    }
    
    fetch('api/delete_backup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ filename: filename })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            loadBackupStats();
            loadBackupFiles();
        } else {
            showAlert(data.message || 'Error deleting backup', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting backup', 'error');
    });
}

async function cleanupOldBackups() {
    if (!(await appConfirm('Delete all backups older than 30 days?', {danger: true}))) {
        return;
    }
    
    fetch('api/cleanup_backups.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(`${data.deleted} old backup(s) deleted`, 'success');
            loadBackupStats();
            loadBackupFiles();
        } else {
            showAlert(data.message || 'Error cleaning up backups', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error cleaning up backups', 'error');
    });
}

function openAutoBackupModal() {
    // Load current settings
    fetch('api/get_auto_backup_settings.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.settings) {
                document.getElementById('autoBackupEnabled').value = data.settings.enabled || '0';
                document.getElementById('backupFrequency').value = data.settings.frequency || 'daily';
                document.getElementById('keepBackups').value = data.settings.keep_backups || 7;
            }
        });
    
    document.getElementById('autoBackupModal').classList.add('show');
}

function saveAutoBackupSettings(e) {
    e.preventDefault();
    
    const settings = {
        enabled: document.getElementById('autoBackupEnabled').value,
        frequency: document.getElementById('backupFrequency').value,
        keep_backups: document.getElementById('keepBackups').value
    };
    
    fetch('api/save_auto_backup_settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            document.getElementById('autoBackupModal').classList.remove('show');
        } else {
            showAlert(data.message || 'Error saving settings', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving settings', 'error');
    });
}

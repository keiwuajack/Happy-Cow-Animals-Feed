// Users Management
let users = [];

document.addEventListener('DOMContentLoaded', function() {
    loadUsers();

    document.getElementById('addUserBtn').addEventListener('click', openAddModal);
    document.getElementById('userForm').addEventListener('submit', saveUser);
    document.getElementById('usersSearch').addEventListener('input', filterUsers);
    document.getElementById('roleFilter').addEventListener('change', filterUsers);
    document.getElementById('statusFilter').addEventListener('change', filterUsers);
    document.getElementById('cancelUser').addEventListener('click', closeUserModal);
    document.querySelector('#userModal .close').addEventListener('click', closeUserModal);
    document.getElementById('togglePasswordVisibility').addEventListener('click', togglePasswordVisibility);
    document.getElementById('role').addEventListener('change', function() {
        const custom = document.getElementById('customPermissions').checked;
        updatePermissionEditor(custom, custom ? getSelectedPermissions() : getRolePermissions());
    });
    document.getElementById('customPermissions').addEventListener('change', function() {
        updatePermissionEditor(this.checked, getRolePermissions());
    });

    document.getElementById('userModal').addEventListener('click', function(event) {
        if (event.target === this) closeUserModal();
    });
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && document.getElementById('userModal').classList.contains('show')) {
            closeUserModal();
        }
    });
});

function loadUsers() {
    fetch('api/get_users.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                users = data.users || [];
                updateUserStats();
                filterUsers();
            }
        })
        .catch(error => console.error('Error loading users:', error));
}

function updateUserStats() {
    document.getElementById('userTotal').textContent = users.length;
    document.getElementById('userActive').textContent = users.filter(user => String(user.status).toLowerCase() === 'active').length;
    document.getElementById('userAdmins').textContent = users.filter(user => String(user.role).toLowerCase() === 'admin').length;
    document.getElementById('userCashiers').textContent = users.filter(user => String(user.role).toLowerCase() === 'cashier').length;
}

function getRolePermissions() {
    const role = document.getElementById('role').value;
    const permissions = window.rolePermissionDefaults?.[role] || [];
    return permissions.includes('*')
        ? Array.from(document.querySelectorAll('.user-permission')).map(input => input.value)
        : permissions;
}

function getSelectedPermissions() {
    return Array.from(document.querySelectorAll('.user-permission:checked')).map(input => input.value);
}

function updatePermissionEditor(customEnabled, selectedPermissions = []) {
    const role = document.getElementById('role').value;
    const isAdmin = role === 'admin';
    const toggle = document.getElementById('customPermissions');
    const grid = document.getElementById('permissionGrid');
    const adminNote = document.getElementById('adminPermissionsNote');

    toggle.disabled = isAdmin;
    toggle.checked = !isAdmin && customEnabled;
    grid.hidden = isAdmin || !toggle.checked;
    adminNote.hidden = !isAdmin;

    const selected = new Set(selectedPermissions);
    document.querySelectorAll('.user-permission').forEach(input => {
        input.checked = isAdmin || selected.has(input.value);
    });
}

function filterUsers() {
    const search = document.getElementById('usersSearch').value.trim().toLowerCase();
    const role = document.getElementById('roleFilter').value.toLowerCase();
    const status = document.getElementById('statusFilter').value.toLowerCase();
    const filtered = users.filter(user => {
        const searchable = [user.username, user.full_name, user.email, user.phone]
            .some(value => String(value || '').toLowerCase().includes(search));
        return searchable && (!role || String(user.role).toLowerCase() === role)
            && (!status || String(user.status).toLowerCase() === status);
    });

    displayUsers(filtered);
}

function displayUsers(usersToDisplay) {
    const tbody = document.querySelector('#usersTable tbody');
    if (!usersToDisplay.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="users-table-empty">No users match these filters.</td></tr>';
        return;
    }

    tbody.innerHTML = usersToDisplay.map(user => {
        const userId = Number(user.id) || 0;
        const role = String(user.role || '').toLowerCase();
        const status = String(user.status || '').toLowerCase();
        const roleClass = ['admin', 'manager', 'cashier'].includes(role) ? `users-role-${role}` : 'users-role-other';
        const statusClass = ['active', 'inactive'].includes(status) ? `users-status-${status}` : 'users-status-other';
        const hasCustomPermissions = role !== 'admin' && user.permissions_json !== null && user.permissions_json !== '';
        const protectAdmin = String(user.username || '').toLowerCase() !== 'admin';

        return `
            <tr>
                <td><strong>${escapeHtml(user.username || '—')}</strong></td>
                <td>${escapeHtml(user.full_name || '—')}</td>
                <td>${escapeHtml(user.email || '—')}</td>
                <td>${escapeHtml(user.phone || '—')}</td>
                <td><span class="users-role-badge ${roleClass}">${escapeHtml(role || 'staff')}</span>${hasCustomPermissions ? '<small class="users-custom-access-badge">Custom access</small>' : ''}</td>
                <td><span class="users-status-badge ${statusClass}">${escapeHtml(status || 'unknown')}</span></td>
                <td>${formatDate(user.created_at)}</td>
                <td class="users-actions-cell">
                    <button class="btn-icon" onclick="editUser(${userId})" title="Edit user" aria-label="Edit ${escapeHtml(user.full_name || user.username || 'user')}">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${protectAdmin ? `
                        <button class="btn-icon text-danger" onclick="deleteUser(${userId})" title="Deactivate user" aria-label="Deactivate ${escapeHtml(user.full_name || user.username || 'user')}">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : ''}
                </td>
            </tr>
        `;
    }).join('');
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[character]));
}

function formatDate(value) {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '—' : date.toLocaleDateString();
}

function openUserModal() {
    const modal = document.getElementById('userModal');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    requestAnimationFrame(() => document.getElementById('username').focus());
}

function closeUserModal() {
    const modal = document.getElementById('userModal');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

function openAddModal() {
    document.getElementById('userForm').reset();
    document.getElementById('modalTitle').textContent = 'Add User';
    document.getElementById('userModalDescription').textContent = 'Create an account for a team member.';
    document.querySelector('.users-title-icon i').className = 'fas fa-user-plus';
    document.getElementById('userId').value = '';
    document.getElementById('password').required = true;
    document.getElementById('password').placeholder = 'Create a secure password';
    document.getElementById('passwordRequiredLabel').textContent = 'Required';
    document.getElementById('passwordRequiredLabel').classList.remove('users-optional');
    document.getElementById('passwordHint').textContent = 'Use a strong password for this account.';
    updatePermissionEditor(false, getRolePermissions());
    resetPasswordVisibility();
    openUserModal();
}

function editUser(id) {
    const user = users.find(item => Number(item.id) === Number(id));
    if (!user) return;

    document.getElementById('userForm').reset();
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('userModalDescription').textContent = 'Update this staff member’s details and access.';
    document.querySelector('.users-title-icon i').className = 'fas fa-user-pen';
    document.getElementById('userId').value = user.id;
    document.getElementById('username').value = user.username || '';
    document.getElementById('fullName').value = user.full_name || '';
    document.getElementById('email').value = user.email || '';
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('password').placeholder = 'Leave blank to keep current password';
    document.getElementById('passwordRequiredLabel').textContent = 'Optional';
    document.getElementById('passwordRequiredLabel').classList.add('users-optional');
    document.getElementById('passwordHint').textContent = 'Leave blank to keep the current password, or enter a new one.';
    document.getElementById('role').value = user.role || 'cashier';
    document.getElementById('status').value = user.status || 'active';
    let customPermissions = null;
    try {
        customPermissions = user.permissions_json === null || user.permissions_json === ''
            ? null
            : JSON.parse(user.permissions_json);
    } catch (error) {
        customPermissions = null;
    }
    const hasCustomPermissions = Array.isArray(customPermissions) && user.role !== 'admin';
    updatePermissionEditor(hasCustomPermissions, hasCustomPermissions ? customPermissions : getRolePermissions());
    resetPasswordVisibility();
    openUserModal();
}

function togglePasswordVisibility() {
    const password = document.getElementById('password');
    const button = document.getElementById('togglePasswordVisibility');
    const showing = password.type === 'text';
    password.type = showing ? 'password' : 'text';
    button.setAttribute('aria-pressed', String(!showing));
    button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    button.innerHTML = `<i class="fas fa-eye${showing ? '' : '-slash'}"></i>`;
}

function resetPasswordVisibility() {
    document.getElementById('password').type = 'password';
    const button = document.getElementById('togglePasswordVisibility');
    button.setAttribute('aria-pressed', 'false');
    button.setAttribute('aria-label', 'Show password');
    button.innerHTML = '<i class="fas fa-eye"></i>';
}

function saveUser(event) {
    event.preventDefault();

    const userData = {
        id: document.getElementById('userId').value,
        username: document.getElementById('username').value.trim(),
        full_name: document.getElementById('fullName').value.trim(),
        email: document.getElementById('email').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        password: document.getElementById('password').value,
        role: document.getElementById('role').value,
        status: document.getElementById('status').value,
        permissions: document.getElementById('role').value !== 'admin'
            && document.getElementById('customPermissions').checked
            ? getSelectedPermissions()
            : null
    };

    const saveButton = document.getElementById('saveUserBtn');
    const originalContent = saveButton.innerHTML;
    saveButton.disabled = true;
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    fetch('api/save_user.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeUserModal();
            loadUsers();
        } else {
            showAlert(data.message || 'Error saving user', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving user', 'error');
    })
    .finally(() => {
        saveButton.disabled = false;
        saveButton.innerHTML = originalContent;
    });
}

async function deleteUser(id) {
    if (!(await appConfirm('Deactivate this account? The user will lose sign-in access, while their sales and activity records are kept.', {danger: true}))) return;

    fetch('api/delete_user.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: Number(id)})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            loadUsers();
        } else {
            showAlert(data.message || 'Error deleting user', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting user', 'error');
    });
}

// Users Management JavaScript
let users = [];

document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    
    // Event listeners
    document.getElementById('addUserBtn').addEventListener('click', openAddModal);
    document.getElementById('userForm').addEventListener('submit', saveUser);
    
    // Modal controls
    const modal = document.getElementById('userModal');
    const closeBtn = modal.querySelector('.close');
    const cancelBtn = document.getElementById('cancelUser');
    
    closeBtn.onclick = () => modal.classList.remove('show');
    cancelBtn.onclick = () => modal.classList.remove('show');
    
    window.onclick = (e) => {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    };
});

function loadUsers() {
    fetch('api/get_users.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                users = data.users;
                displayUsers(users);
            }
        })
        .catch(error => console.error('Error loading users:', error));
}

function displayUsers(usersToDisplay) {
    const tbody = document.querySelector('#usersTable tbody');
    tbody.innerHTML = '';
    
    if (usersToDisplay.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No users found</td></tr>';
        return;
    }
    
    usersToDisplay.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${user.username}</strong></td>
            <td>${user.full_name}</td>
            <td>${user.email || 'N/A'}</td>
            <td>${user.phone || 'N/A'}</td>
            <td><span class="badge badge-info">${user.role}</span></td>
            <td><span class="badge badge-${user.status}">${user.status}</span></td>
            <td>${new Date(user.created_at).toLocaleDateString()}</td>
            <td>
                <button class="btn-icon" onclick="editUser(${user.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                ${user.username !== 'admin' ? `
                    <button class="btn-icon text-danger" onclick="deleteUser(${user.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                ` : ''}
            </td>
        `;
        tbody.appendChild(row);
    });
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('password').required = true;
    document.getElementById('userModal').classList.add('show');
}

function editUser(id) {
    const user = users.find(u => u.id == id);
    if (!user) return;
    
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('userId').value = user.id;
    document.getElementById('username').value = user.username;
    document.getElementById('fullName').value = user.full_name;
    document.getElementById('email').value = user.email || '';
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('role').value = user.role;
    document.getElementById('status').value = user.status;
    
    document.getElementById('userModal').classList.add('show');
}

function saveUser(e) {
    e.preventDefault();
    
    const userData = {
        id: document.getElementById('userId').value,
        username: document.getElementById('username').value,
        full_name: document.getElementById('fullName').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        password: document.getElementById('password').value,
        role: document.getElementById('role').value,
        status: document.getElementById('status').value
    };
    
    fetch('api/save_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            document.getElementById('userModal').classList.remove('show');
            loadUsers();
        } else {
            showAlert(data.message || 'Error saving user', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving user', 'error');
    });
}

async function deleteUser(id) {
    if (!(await appConfirm('Are you sure you want to delete this user?', {danger: true}))) {
        return;
    }
    
    fetch('api/delete_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
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

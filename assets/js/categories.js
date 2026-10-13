// Categories Management JavaScript
let categories = [];

document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    
    // Event listeners
    document.getElementById('addCategoryBtn').addEventListener('click', openAddModal);
    document.getElementById('categoryForm').addEventListener('submit', saveCategory);
    
    // Modal controls
    const modal = document.getElementById('categoryModal');
    const closeBtn = modal.querySelector('.close');
    const cancelBtn = document.getElementById('cancelCategory');
    
    closeBtn.onclick = () => modal.classList.remove('show');
    cancelBtn.onclick = () => modal.classList.remove('show');
    
    window.onclick = (e) => {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    };
});

function loadCategories() {
    fetch('api/get_categories.php?all=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                categories = data.categories;
                displayCategories(categories);
            }
        })
        .catch(error => console.error('Error loading categories:', error));
}

function displayCategories(categoriesToDisplay) {
    const grid = document.getElementById('categoriesGrid');
    grid.innerHTML = '';
    
    if (categoriesToDisplay.length === 0) {
        grid.innerHTML = '<p style="text-align: center; padding: 40px;">No categories found</p>';
        return;
    }
    
    categoriesToDisplay.forEach(category => {
        const card = document.createElement('div');
        card.className = 'category-card';
        card.innerHTML = `
            <div class="category-header">
                <h3>${category.name}</h3>
                <span class="badge badge-${category.status}">${category.status}</span>
            </div>
            <p class="category-description">${category.description || 'No description'}</p>
            <div class="category-actions">
                <button class="btn-icon" onclick="editCategory(${category.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon text-danger" onclick="deleteCategory(${category.id})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        grid.appendChild(card);
    });
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryModal').classList.add('show');
}

function editCategory(id) {
    const category = categories.find(c => c.id == id);
    if (!category) return;
    
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryName').value = category.name;
    document.getElementById('categoryDescription').value = category.description || '';
    document.getElementById('categoryStatus').value = category.status;
    
    document.getElementById('categoryModal').classList.add('show');
}

function saveCategory(e) {
    e.preventDefault();
    
    const categoryData = {
        id: document.getElementById('categoryId').value,
        name: document.getElementById('categoryName').value,
        description: document.getElementById('categoryDescription').value,
        status: document.getElementById('categoryStatus').value
    };
    
    fetch('api/save_category.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(categoryData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            document.getElementById('categoryModal').classList.remove('show');
            loadCategories();
        } else {
            showAlert(data.message || 'Error saving category', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving category', 'error');
    });
}

async function deleteCategory(id) {
    if (!(await appConfirm('Are you sure you want to delete this category?', {danger: true}))) {
        return;
    }
    
    fetch('api/delete_category.php', {
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
            loadCategories();
        } else {
            showAlert(data.message || 'Error deleting category', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting category', 'error');
    });
}

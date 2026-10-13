// Expenses Management
let expenses = [];

document.addEventListener('DOMContentLoaded', function() {
    loadExpenses();
    loadSummary();
    
    // Set today's date as default
    document.getElementById('expenseDate').valueAsDate = new Date();
    
    // Event listeners
    document.getElementById('addExpenseBtn').addEventListener('click', openAddModal);
    document.getElementById('searchExpense').addEventListener('input', filterExpenses);
    document.getElementById('filterCategory').addEventListener('change', filterExpenses);
    document.getElementById('startDate').addEventListener('change', filterExpenses);
    document.getElementById('endDate').addEventListener('change', filterExpenses);
    document.getElementById('expenseForm').addEventListener('submit', saveExpense);
    
    // Modal controls
    const modal = document.getElementById('expenseModal');
    const closeBtn = modal.querySelector('.close');
    const cancelBtn = document.getElementById('cancelExpense');
    
    closeBtn.onclick = () => modal.classList.remove('show');
    cancelBtn.onclick = () => modal.classList.remove('show');
    
    window.onclick = (e) => {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    };
});

function loadExpenses() {
    fetch('api/get_expenses.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                expenses = data.expenses;
                displayExpenses(expenses);
            }
        })
        .catch(error => console.error('Error loading expenses:', error));
}

function loadSummary() {
    fetch('api/get_expense_summary.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('todayExpenses').textContent = formatCurrency(data.today);
                document.getElementById('weekExpenses').textContent = formatCurrency(data.week);
                document.getElementById('monthExpenses').textContent = formatCurrency(data.month);
                document.getElementById('totalExpenses').textContent = formatCurrency(data.total);
            }
        })
        .catch(error => console.error('Error loading summary:', error));
}

function displayExpenses(expensesToDisplay) {
    const tbody = document.querySelector('#expensesTable tbody');
    tbody.innerHTML = '';
    
    if (expensesToDisplay.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No expenses found</td></tr>';
        return;
    }
    
    expensesToDisplay.forEach(expense => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${formatDate(expense.expense_date)}</td>
            <td><span class="badge badge-info">${expense.category}</span></td>
            <td>${expense.description}</td>
            <td><strong>${formatCurrency(expense.amount)}</strong></td>
            <td>${expense.payment_method}</td>
            <td>${expense.receipt_number || '-'}</td>
            <td>${expense.added_by_name}</td>
            <td>
                <button class="btn-icon" onclick="editExpense(${expense.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon text-danger" onclick="deleteExpense(${expense.id})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function filterExpenses() {
    const searchTerm = document.getElementById('searchExpense').value.toLowerCase();
    const category = document.getElementById('filterCategory').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    let filtered = expenses;
    
    if (searchTerm) {
        filtered = filtered.filter(e => 
            e.description.toLowerCase().includes(searchTerm) ||
            e.receipt_number?.toLowerCase().includes(searchTerm)
        );
    }
    
    if (category) {
        filtered = filtered.filter(e => e.category === category);
    }
    
    if (startDate) {
        filtered = filtered.filter(e => e.expense_date >= startDate);
    }
    
    if (endDate) {
        filtered = filtered.filter(e => e.expense_date <= endDate);
    }
    
    displayExpenses(filtered);
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Expense';
    document.getElementById('expenseForm').reset();
    document.getElementById('expenseId').value = '';
    document.getElementById('expenseDate').valueAsDate = new Date();
    document.getElementById('expenseModal').classList.add('show');
}

function editExpense(id) {
    const expense = expenses.find(e => e.id == id);
    if (!expense) return;
    
    document.getElementById('modalTitle').textContent = 'Edit Expense';
    document.getElementById('expenseId').value = expense.id;
    document.getElementById('category').value = expense.category;
    document.getElementById('amount').value = expense.amount;
    document.getElementById('description').value = expense.description;
    document.getElementById('expenseDate').value = expense.expense_date;
    document.getElementById('paymentMethod').value = expense.payment_method;
    document.getElementById('receiptNumber').value = expense.receipt_number || '';
    
    document.getElementById('expenseModal').classList.add('show');
}

function saveExpense(e) {
    e.preventDefault();
    
    const expenseData = {
        id: document.getElementById('expenseId').value,
        category: document.getElementById('category').value,
        amount: document.getElementById('amount').value,
        description: document.getElementById('description').value,
        expense_date: document.getElementById('expenseDate').value,
        payment_method: document.getElementById('paymentMethod').value,
        receipt_number: document.getElementById('receiptNumber').value
    };
    
    fetch('api/save_expense.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(expenseData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            document.getElementById('expenseModal').classList.remove('show');
            loadExpenses();
            loadSummary();
        } else {
            showAlert(data.message || 'Error saving expense', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving expense', 'error');
    });
}

async function deleteExpense(id) {
    if (!(await appConfirm('Are you sure you want to delete this expense?', {danger: true}))) {
        return;
    }
    
    fetch('api/delete_expense.php', {
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
            loadExpenses();
            loadSummary();
        } else {
            showAlert(data.message || 'Error deleting expense', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting expense', 'error');
    });
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

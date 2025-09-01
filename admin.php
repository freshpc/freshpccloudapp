<?php
// admin.php v4 | Admin dashboard | est lines: ~200 | Author: franklos
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login');
    exit;
}

require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FreshPC Cloud</title>
    <link rel="icon" type="image/x-icon" href="<?php echo ADMIN_FAVICON; ?>">
    <style>
        <?php echo generateDynamicCSS(); ?>
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .header {
            background: var(--header-bg-color);
            color: var(--header-text-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            display: flex;
            align-items: center;
        }
        
        .header .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .main-content {
            padding: 2rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            padding: 1rem 1.5rem;
            background: var(--header-bg-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }
        
        .modal-header {
            padding: 1rem 1.5rem;
            background: var(--header-bg-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px 10px 0 0;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            opacity: 0.8;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .alert {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            display: none;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--btn-primary);
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .table {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>
            <?php if (defined('SITE_LOGO') && file_exists(SITE_LOGO)): ?>
                <img src="<?php echo SITE_LOGO; ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <span class="site-title">FreshPC Cloud Admin</span>
        </h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
            <a href="/api/auth/logout" class="btn btn-secondary btn-sm">Logout</a>
        </div>
    </header>

    <main class="main-content">
        <div id="alert" class="alert"></div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="totalClients">0</div>
                <div class="stat-label">Total Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalUsers">0</div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalTasks">0</div>
                <div class="stat-label">Total Tasks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="pendingTasks">0</div>
                <div class="stat-label">Pending Tasks</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Clients Management -->
            <div class="card">
                <div class="card-header">
                    <h3>Clients</h3>
                    <button class="btn btn-success btn-sm" onclick="openClientModal()">Add Client</button>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>City</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="clientsTable">
                            <!-- Clients loaded via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Users Management -->
            <div class="card">
                <div class="card-header">
                    <h3>Users</h3>
                    <button class="btn btn-success btn-sm" onclick="openUserModal()">Add User</button>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTable">
                            <!-- Users loaded via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tasks Management -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Tasks</h3>
                    <button class="btn btn-success btn-sm" onclick="openTaskModal()">Add Task</button>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Client</th>
                                <th>Assigned To</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tasksTable">
                            <!-- Tasks loaded via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Client Modal -->
    <div id="clientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="clientModalTitle">Add Client</h3>
                <span class="close" onclick="closeClientModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="clientForm">
                    <input type="hidden" id="clientId" name="id">
                    <div class="form-group">
                        <label for="clientName">Name:</label>
                        <input type="text" id="clientName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="clientEmail">Email:</label>
                        <input type="email" id="clientEmail" name="email">
                    </div>
                    <div class="form-group">
                        <label for="clientPhone">Phone:</label>
                        <input type="tel" id="clientPhone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="clientCity">City:</label>
                        <input type="text" id="clientCity" name="address_city">
                    </div>
                    <div class="form-group">
                        <label for="clientPostcode">Postcode:</label>
                        <input type="text" id="clientPostcode" name="address_postcode">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Client</button>
                </form>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="userModalTitle">Add User</h3>
                <span class="close" onclick="closeUserModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="userId" name="id">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="userEmail">Email:</label>
                        <input type="email" id="userEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="fullName">Full Name:</label>
                        <input type="text" id="fullName" name="full_name">
                    </div>
                    <div class="form-group">
                        <label for="userRole">Role:</label>
                        <select id="userRole" name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Task Modal -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="taskModalTitle">Add Task</h3>
                <span class="close" onclick="closeTaskModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="taskForm">
                    <input type="hidden" id="taskId" name="id">
                    <div class="form-group">
                        <label for="taskTitle">Title:</label>
                        <input type="text" id="taskTitle" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="taskDescription">Description:</label>
                        <textarea id="taskDescription" name="description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="taskClient">Client:</label>
                        <select id="taskClient" name="client_id" required>
                            <option value="">Select Client</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="taskAssignedUser">Assign To:</label>
                        <select id="taskAssignedUser" name="assigned_user_id">
                            <option value="">Select User</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="taskPriority">Priority:</label>
                        <select id="taskPriority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="taskScheduledDate">Scheduled Date:</label>
                        <input type="datetime-local" id="taskScheduledDate" name="scheduled_date">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Task</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Load dashboard data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
        });

        // Generic API function
        async function apiCall(url, method = 'GET', data = null) {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                }
            };
            
            if (data) {
                options.body = JSON.stringify(data);
            }
            
            const response = await fetch(url, options);
            return await response.json();
        }

        // Show alert message
        function showAlert(message, type = 'success') {
            const alertDiv = document.getElementById('alert');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            alertDiv.style.display = 'block';
            setTimeout(() => {
                alertDiv.style.display = 'none';
            }, 5000);
        }

        // Load all dashboard data
        async function loadDashboardData() {
            try {
                const [clients, users, tasks] = await Promise.all([
                    apiCall('/api/clients'),
                    apiCall('/api/users'),
                    apiCall('/api/tasks')
                ]);
                
                // Update statistics
                document.getElementById('totalClients').textContent = clients.length;
                document.getElementById('totalUsers').textContent = users.length;
                document.getElementById('totalTasks').textContent = tasks.length;
                document.getElementById('pendingTasks').textContent = tasks.filter(t => t.status === 'pending').length;
                
                // Load tables
                loadClientsTable(clients);
                loadUsersTable(users);
                loadTasksTable(tasks);
                
                // Populate select dropdowns
                populateClientSelect(clients);
                populateUserSelect(users);
                
            } catch (error) {
                showAlert('Error loading dashboard data: ' + error.message, 'error');
            }
        }

        // Load clients table
        function loadClientsTable(clients) {
            const tbody = document.getElementById('clientsTable');
            tbody.innerHTML = '';
            
            clients.slice(0, 10).forEach(client => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td>${client.name || ''}</td>
                    <td>${client.email || ''}</td>
                    <td>${client.address_city || ''}</td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editClient(${client.id})">Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteClient(${client.id})">Delete</button>
                    </td>
                `;
            });
        }

        // Load users table
        function loadUsersTable(users) {
            const tbody = document.getElementById('usersTable');
            tbody.innerHTML = '';
            
            users.slice(0, 10).forEach(user => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td>${user.username || ''}</td>
                    <td>${user.email || ''}</td>
                    <td>${user.role || ''}</td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editUser(${user.id})">Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})">Delete</button>
                    </td>
                `;
            });
        }

        // Load tasks table
        function loadTasksTable(tasks) {
            const tbody = document.getElementById('tasksTable');
            tbody.innerHTML = '';
            
            tasks.slice(0, 10).forEach(task => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td>${task.title || ''}</td>
                    <td>${task.client_name || ''}</td>
                    <td>${task.assigned_to_name || ''}</td>
                    <td>${task.priority || ''}</td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editTask(${task.id})">Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteTask(${task.id})">Delete</button>
                    </td>
                `;
            });
        }

        // Populate client select dropdown
        function populateClientSelect(clients) {
            const select = document.getElementById('taskClient');
            select.innerHTML = '<option value="">Select Client</option>';
            clients.forEach(client => {
                select.innerHTML += `<option value="${client.id}">${client.name}</option>`;
            });
        }

        // Populate user select dropdown
        function populateUserSelect(users) {
            const select = document.getElementById('taskAssignedUser');
            select.innerHTML = '<option value="">Select User</option>';
            users.forEach(user => {
                select.innerHTML += `<option value="${user.id}">${user.full_name || user.username}</option>`;
            });
        }

        // Client Modal Functions
        function openClientModal(clientData = null) {
            const modal = document.getElementById('clientModal');
            const form = document.getElementById('clientForm');
            const title = document.getElementById('clientModalTitle');
            
            if (clientData) {
                title.textContent = 'Edit Client';
                document.getElementById('clientId').value = clientData.id;
                document.getElementById('clientName').value = clientData.name || '';
                document.getElementById('clientEmail').value = clientData.email || '';
                document.getElementById('clientPhone').value = clientData.phone || '';
                document.getElementById('clientCity').value = clientData.address_city || '';
                document.getElementById('clientPostcode').value = clientData.address_postcode || '';
            } else {
                title.textContent = 'Add Client';
                form.reset();
            }
            
            modal.style.display = 'block';
        }

        function closeClientModal() {
            document.getElementById('clientModal').style.display = 'none';
        }

        // User Modal Functions
        function openUserModal(userData = null) {
            const modal = document.getElementById('userModal');
            const form = document.getElementById('userForm');
            const title = document.getElementById('userModalTitle');
            
            if (userData) {
                title.textContent = 'Edit User';
                document.getElementById('userId').value = userData.id;
                document.getElementById('username').value = userData.username || '';
                document.getElementById('userEmail').value = userData.email || '';
                document.getElementById('fullName').value = userData.full_name || '';
                document.getElementById('userRole').value = userData.role || 'user';
            } else {
                title.textContent = 'Add User';
                form.reset();
            }
            
            modal.style.display = 'block';
        }

        function closeUserModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        // Task Modal Functions
        function openTaskModal(taskData = null) {
            const modal = document.getElementById('taskModal');
            const form = document.getElementById('taskForm');
            const title = document.getElementById('taskModalTitle');
            
            if (taskData) {
                title.textContent = 'Edit Task';
                document.getElementById('taskId').value = taskData.id;
                document.getElementById('taskTitle').value = taskData.title || '';
                document.getElementById('taskDescription').value = taskData.description || '';
                document.getElementById('taskClient').value = taskData.client_id || '';
                document.getElementById('taskAssignedUser').value = taskData.assigned_user_id || '';
                document.getElementById('taskPriority').value = taskData.priority || 'medium';
                document.getElementById('taskScheduledDate').value = taskData.scheduled_date || '';
            } else {
                title.textContent = 'Add Task';
                form.reset();
            }
            
            modal.style.display = 'block';
        }

        function closeTaskModal() {
            document.getElementById('taskModal').style.display = 'none';
        }

        // Form submission handlers
        document.getElementById('clientForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                if (data.id) {
                    await apiCall(`/api/clients/${data.id}`, 'POST', data);
                } else {
                    await apiCall('/api/clients', 'POST', data);
                }
                showAlert('Client saved successfully!');
                closeClientModal();
                loadDashboardData();
            } catch (error) {
                showAlert('Error saving client: ' + error.message, 'error');
            }
        });

        document.getElementById('userForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                if (data.id) {
                    await apiCall(`/api/users/${data.id}`, 'POST', data);
                } else {
                    await apiCall('/api/users', 'POST', data);
                }
                showAlert('User saved successfully!');
                closeUserModal();
                loadDashboardData();
            } catch (error) {
                showAlert('Error saving user: ' + error.message, 'error');
            }
        });

        document.getElementById('taskForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                if (data.id) {
                    await apiCall(`/api/tasks/${data.id}`, 'POST', data);
                } else {
                    await apiCall('/api/tasks', 'POST', data);
                }
                showAlert('Task saved successfully!');
                closeTaskModal();
                loadDashboardData();
            } catch (error) {
                showAlert('Error saving task: ' + error.message, 'error');
            }
        });

        // Edit functions
        async function editClient(id) {
            try {
                const clients = await apiCall('/api/clients');
                const client = clients.find(c => c.id == id);
                if (client) {
                    openClientModal(client);
                }
            } catch (error) {
                showAlert('Error loading client data: ' + error.message, 'error');
            }
        }

        async function editUser(id) {
            try {
                const users = await apiCall('/api/users');
                const user = users.find(u => u.id == id);
                if (user) {
                    openUserModal(user);
                }
            } catch (error) {
                showAlert('Error loading user data: ' + error.message, 'error');
            }
        }

        async function editTask(id) {
            try {
                const tasks = await apiCall('/api/tasks');
                const task = tasks.find(t => t.id == id);
                if (task) {
                    openTaskModal(task);
                }
            } catch (error) {
                showAlert('Error loading task data: ' + error.message, 'error');
            }
        }

        // Delete functions
        async function deleteClient(id) {
            if (confirm('Are you sure you want to delete this client?')) {
                try {
                    await apiCall(`/api/clients/${id}`, 'POST', {delete: true});
                    showAlert('Client deleted successfully!');
                    loadDashboardData();
                } catch (error) {
                    showAlert('Error deleting client: ' + error.message, 'error');
                }
            }
        }

        async function deleteUser(id) {
            if (confirm('Are you sure you want to delete this user?')) {
                try {
                    await apiCall(`/api/users/${id}`, 'DELETE');
                    showAlert('User deleted successfully!');
                    loadDashboardData();
                } catch (error) {
                    showAlert('Error deleting user: ' + error.message, 'error');
                }
            }
        }

        async function deleteTask(id) {
            if (confirm('Are you sure you want to delete this task?')) {
                try {
                    await apiCall(`/api/tasks/${id}`, 'DELETE');
                    showAlert('Task deleted successfully!');
                    loadDashboardData();
                } catch (error) {
                    showAlert('Error deleting task: ' + error.message, 'error');
                }
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = ['clientModal', 'userModal', 'taskModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
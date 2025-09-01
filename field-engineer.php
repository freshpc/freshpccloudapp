<?php
// field-engineer.php v4 | Field engineer dashboard | est lines: ~150 | Author: franklos
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
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
    <title>Field Engineer Dashboard - FreshPC Cloud</title>
    <link rel="icon" type="image/x-icon" href="<?php echo FIELD_FAVICON; ?>">
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
            grid-template-columns: 1fr;
            gap: 2rem;
            max-width: 1200px;
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
        
        .task-row {
            cursor: pointer;
        }
        
        .task-row:hover {
            background-color: #f0f8ff !important;
        }
        
        .priority-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .priority-high {
            background-color: #fee;
            color: #c53030;
        }
        
        .priority-medium {
            background-color: #fef5e7;
            color: #d69e2e;
        }
        
        .priority-low {
            background-color: #f0fff4;
            color: #38a169;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fef5e7;
            color: #d69e2e;
        }
        
        .status-in-progress {
            background-color: #e6fffa;
            color: #319795;
        }
        
        .status-completed {
            background-color: #f0fff4;
            color: #38a169;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--btn-primary), var(--btn-info));
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .welcome-card h2 {
            margin-bottom: 1rem;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
        
        .task-details {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }
        
        .no-tasks {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-tasks .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ccc;
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
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
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
            <span class="site-title">FreshPC Cloud - Field Engineer</span>
        </h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? $_SESSION['full_name'] ?? 'Engineer'); ?></span>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="/admin" class="btn btn-info btn-sm">Admin Panel</a>
            <?php endif; ?>
            <a href="/api/auth/logout" class="btn btn-secondary btn-sm">Logout</a>
        </div>
    </header>

    <main class="main-content">
        <div class="welcome-card">
            <h2>Welcome to Your Field Engineering Dashboard</h2>
            <p>Manage your tasks, view client information, and update work progress efficiently</p>
        </div>
        
        <!-- Quick Statistics -->
        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-number" id="myTasksCount">0</div>
                <div class="stat-label">My Tasks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="pendingTasksCount">0</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="inProgressTasksCount">0</div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="completedTasksCount">0</div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- My Tasks -->
            <div class="card">
                <div class="card-header">
                    <h3>My Tasks</h3>
                    <div>
                        <select id="statusFilter" onchange="filterTasks()" class="btn btn-secondary">
                            <option value="">All Tasks</option>
                            <option value="pending">Pending</option>
                            <option value="in-progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div id="tasksContainer">
                        <!-- Tasks loaded via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        let allTasks = [];
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        
        // Load dashboard data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadTasks();
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
            
            try {
                const response = await fetch(url, options);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return await response.json();
            } catch (error) {
                console.error('API call failed:', error);
                throw error;
            }
        }

        // Load tasks
        async function loadTasks() {
            try {
                const tasks = await apiCall('/api/tasks');
                allTasks = tasks.filter(task => task.assigned_user_id == currentUserId);
                
                // Update statistics
                updateStatistics(allTasks);
                
                // Display tasks
                displayTasks(allTasks);
                
            } catch (error) {
                console.error('Error loading tasks:', error);
                document.getElementById('tasksContainer').innerHTML = `
                    <div class="no-tasks">
                        <div class="icon">⚠️</div>
                        <h3>Error loading tasks</h3>
                        <p>Please try refreshing the page</p>
                    </div>
                `;
            }
        }

        // Update statistics
        function updateStatistics(tasks) {
            document.getElementById('myTasksCount').textContent = tasks.length;
            document.getElementById('pendingTasksCount').textContent = tasks.filter(t => t.status === 'pending' || !t.status).length;
            document.getElementById('inProgressTasksCount').textContent = tasks.filter(t => t.status === 'in-progress').length;
            document.getElementById('completedTasksCount').textContent = tasks.filter(t => t.status === 'completed').length;
        }

        // Display tasks
        function displayTasks(tasks) {
            const container = document.getElementById('tasksContainer');
            
            if (tasks.length === 0) {
                container.innerHTML = `
                    <div class="no-tasks">
                        <div class="icon">📋</div>
                        <h3>No tasks found</h3>
                        <p>You don't have any tasks assigned to you yet</p>
                    </div>
                `;
                return;
            }

            const tableHtml = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Client</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Scheduled</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tasks.map(task => createTaskRow(task)).join('')}
                    </tbody>
                </table>
            `;
            
            container.innerHTML = tableHtml;
        }

        // Create task row HTML
        function createTaskRow(task) {
            const priority = task.priority || 'medium';
            const status = task.status || 'pending';
            const scheduledDate = task.scheduled_date ? new Date(task.scheduled_date).toLocaleDateString() : 'Not scheduled';
            
            return `
                <tr class="task-row" onclick="toggleTaskDetails(${task.id})">
                    <td>
                        <strong>${task.title || 'Untitled Task'}</strong>
                        ${task.description ? `<br><small style="color: #666;">${task.description.substring(0, 100)}${task.description.length > 100 ? '...' : ''}</small>` : ''}
                    </td>
                    <td>${task.client_name || 'Unknown Client'}</td>
                    <td><span class="priority-badge priority-${priority}">${priority.charAt(0).toUpperCase() + priority.slice(1)}</span></td>
                    <td><span class="status-badge status-${status}">${status.replace('-', ' ').toUpperCase()}</span></td>
                    <td>${scheduledDate}</td>
                    <td>
                        <select onchange="updateTaskStatus(${task.id}, this.value)" onclick="event.stopPropagation()">
                            <option value="pending" ${status === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="in-progress" ${status === 'in-progress' ? 'selected' : ''}>In Progress</option>
                            <option value="completed" ${status === 'completed' ? 'selected' : ''}>Completed</option>
                        </select>
                    </td>
                </tr>
                <tr id="details-${task.id}" style="display: none;">
                    <td colspan="6">
                        <div class="task-details">
                            <h4>Task Details</h4>
                            <p><strong>Description:</strong> ${task.description || 'No description provided'}</p>
                            <p><strong>Client:</strong> ${task.client_name || 'Unknown Client'}</p>
                            <p><strong>Full Address:</strong> ${task.full_address || 'No address provided'}</p>
                            <p><strong>Priority:</strong> ${priority.charAt(0).toUpperCase() + priority.slice(1)}</p>
                            <p><strong>Scheduled:</strong> ${task.scheduled_date ? new Date(task.scheduled_date).toLocaleString() : 'Not scheduled'}</p>
                            <p><strong>Created:</strong> ${task.created_at ? new Date(task.created_at).toLocaleString() : 'Unknown'}</p>
                        </div>
                    </td>
                </tr>
            `;
        }

        // Toggle task details
        function toggleTaskDetails(taskId) {
            const detailsRow = document.getElementById(`details-${taskId}`);
            if (detailsRow) {
                detailsRow.style.display = detailsRow.style.display === 'none' ? 'table-row' : 'none';
            }
        }

        // Update task status
        async function updateTaskStatus(taskId, newStatus) {
            try {
                const task = allTasks.find(t => t.id === taskId);
                if (!task) return;
                
                const updatedTask = {
                    ...task,
                    status: newStatus
                };
                
                await apiCall(`/api/tasks/${taskId}`, 'POST', updatedTask);
                
                // Reload tasks to reflect changes
                await loadTasks();
                
                // Show success message (you could add a toast notification here)
                console.log(`Task ${taskId} status updated to ${newStatus}`);
                
            } catch (error) {
                console.error('Error updating task status:', error);
                // Reset the dropdown to the previous value
                loadTasks(); // Reload to reset the dropdown
            }
        }

        // Filter tasks
        function filterTasks() {
            const filterValue = document.getElementById('statusFilter').value;
            let filteredTasks = allTasks;
            
            if (filterValue) {
                filteredTasks = allTasks.filter(task => {
                    const taskStatus = task.status || 'pending';
                    return taskStatus === filterValue;
                });
            }
            
            displayTasks(filteredTasks);
        }
    </script>
</body>
</html>
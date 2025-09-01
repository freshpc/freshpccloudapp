<?php
// create_database.php - Simple database setup script for testing
// This creates the basic tables needed for the FreshPC Cloud application

require_once 'config.php';

try {
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        full_name VARCHAR(100),
        role VARCHAR(10) DEFAULT 'user',
        activated BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create clients table
    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        address_street VARCHAR(200),
        address_city VARCHAR(50),
        address_postcode VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create tasks table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        client_id INTEGER,
        assigned_user_id INTEGER,
        priority VARCHAR(10) DEFAULT 'medium',
        status VARCHAR(20) DEFAULT 'pending',
        scheduled_date DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
        FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // Create default admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@freshpccloud.nl', $adminPassword, 'Administrator', 'admin']);
    
    // Create test engineer user
    $engineerPassword = password_hash('engineer123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['engineer', 'engineer@freshpccloud.nl', $engineerPassword, 'Test Engineer', 'user']);
    
    // Create sample client
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO clients (name, email, phone, address_city, address_postcode) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Test Client BV', 'client@example.com', '+31 20 123 4567', 'Amsterdam', '1012 AB']);
    
    // Create sample task
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO tasks (title, description, client_id, assigned_user_id, priority, scheduled_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['POS System Maintenance', 'Regular maintenance of POS terminals at client location', 1, 2, 'medium', '2025-09-01 10:00:00']);
    
    echo "<h2>✅ Database Setup Complete!</h2>";
    echo "<p><strong>Default Admin Login:</strong></p>";
    echo "<ul>";
    echo "<li>Username: admin</li>";
    echo "<li>Email: admin@freshpccloud.nl</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
    echo "<p><strong>Test Engineer Login:</strong></p>";
    echo "<ul>";
    echo "<li>Username: engineer</li>";
    echo "<li>Email: engineer@freshpccloud.nl</li>";
    echo "<li>Password: engineer123</li>";
    echo "</ul>";
    echo "<p><a href='/login.php'>Go to Login Page</a> | <a href='/'>Back to Home</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Database Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Make sure your database is running and the config.php settings are correct.</p>";
}
?>
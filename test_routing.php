<?php
// Simple test file to check if routing works
echo "<h1>Test Routing - This is test_routing.php</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>File exists and PHP is executing correctly!</p>";

// Check if config.php loads
if (file_exists('config.php')) {
    echo "<p>✅ config.php exists</p>";
    require_once 'config.php';
    echo "<p>✅ Config loaded - Company: " . (defined('COMPANY_NAME') ? COMPANY_NAME : 'NOT DEFINED') . "</p>";
} else {
    echo "<p>❌ config.php missing</p>";
}

// Check other key files
$files = ['admin.php', 'field-engineer.php', 'login.php', 'index.php'];
foreach($files as $file) {
    if (file_exists($file)) {
        echo "<p>✅ $file exists</p>";
    } else {
        echo "<p>❌ $file missing</p>";
    }
}
?>
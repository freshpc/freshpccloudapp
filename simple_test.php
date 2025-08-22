<?php
// Simple test to see what's happening
echo "PHP is working<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "index.php exists: " . (file_exists(__DIR__ . '/index.php') ? 'YES' : 'NO') . "<br>";
echo "index.php readable: " . (is_readable(__DIR__ . '/index.php') ? 'YES' : 'NO') . "<br>";
echo "config.php exists: " . (file_exists(__DIR__ . '/config.php') ? 'YES' : 'NO') . "<br>";

// Test config loading
try {
    require_once __DIR__ . '/config.php';
    echo "config.php loaded successfully<br>";
    echo "DB_TYPE: " . DB_TYPE . "<br>";
} catch (Exception $e) {
    echo "❌ Error loading config.php: " . $e->getMessage() . "<br>";
}

// Test if index.php can be included
try {
    ob_start();
    include __DIR__ . '/index.php';
    $output = ob_get_clean();
    echo "index.php included successfully (output length: " . strlen($output) . ")<br>";
} catch (Exception $e) {
    echo "❌ Error including index.php: " . $e->getMessage() . "<br>";
} catch (ParseError $e) {
    echo "❌ Parse error in index.php: " . $e->getMessage() . "<br>";
}
?>
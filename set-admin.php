<?php
require __DIR__.'/config.php';
$pdo = new PDO("mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES=>false
]);
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password=? WHERE email='admin@freshpccloud.nl' OR username='admin' LIMIT 1")->execute([$hash]);
echo "OK\n";

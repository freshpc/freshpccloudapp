<?php
// api/auth/status.php v7 | Returns session status as JSON | line est: ~24 | Author: franklos

require_once '../../config.php'; // Adjust path if needed
session_start();

header('Content-Type: application/json');

// v7: Improved output, includes more user/session info, robust fallback
$response = [
    'authenticated' => false,
    'user' => null,
    'session_id' => session_id(),
    'timestamp' => date('Y-m-d H:i:s'),
];

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $response['authenticated'] = true;
    $response['user'] = [
        'id' => $_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
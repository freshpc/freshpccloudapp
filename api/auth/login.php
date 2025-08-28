<?php
// api/auth/login.php v10 | est lines: ~36
// Login with email or username, returns user ID and details on success

require_once '../../config.php';
session_start();

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$identifier = $data['email'] ?? $data['username'] ?? '';
$password = $data['password'] ?? '';

if (!$identifier || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing credentials']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT id, username, email, first_name, last_name, role, password_hash, activated FROM users 
         WHERE username = ? OR email = ? LIMIT 1"
    );
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $ex->getMessage()]);
    exit;
}

if ($user && $user['activated'] && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    echo json_encode([
        'success' => true,
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => $user['role']
    ]);
    exit;
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials or not activated']);
    exit;
}
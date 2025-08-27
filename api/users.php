<?php
// api/users.php v3 | User listing, add, edit, delete | est lines: ~45 | Author: franklos
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}
require_once '../config.php';

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

// List all users
if (preg_match('#^/api/users/?$#', $uri) && $method === 'GET') {
    $stmt = $pdo->query("SELECT id, username, email, full_name, first_name, last_name, role, activated FROM users ORDER BY id DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Add new user
if (preg_match('#^/api/users/?$#', $uri) && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Basic validation
    if (empty($data['username']) || empty($data['email'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Missing required fields']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, role, activated) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([
        $data['username'], $data['email'], $data['full_name'] ?? '', $data['role'] ?? 'user'
    ]);
    echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId()]);
    exit;
}

// Edit user
if (preg_match('#^/api/users/(\d+)$#', $uri, $match) && $method === 'POST') {
    $id = intval($match[1]);
    $data = json_decode(file_get_contents('php://input'), true);

    // Basic validation
    if (empty($data['username']) || empty($data['email'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Missing required fields']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, full_name=?, role=?, activated=? WHERE id=?");
    $stmt->execute([
        $data['username'], $data['email'], $data['full_name'] ?? '', $data['role'] ?? 'user', $data['activated'] ?? 1, $id
    ]);
    echo json_encode(['success'=>true]);
    exit;
}

// Delete user
if (preg_match('#^/api/users/(\d+)$#', $uri, $match) && $method === 'DELETE') {
    $id = intval($match[1]);
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);
    echo json_encode(['success'=>true]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
exit;
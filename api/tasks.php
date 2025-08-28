<?php
// api/tasks.php
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

// List all tasks (with joined client and user info)
if (preg_match('#^/api/tasks/?$#', $uri) && $method === 'GET') {
    $stmt = $pdo->query("SELECT t.*, c.name AS client_name, u.full_name AS assigned_to_name,
        CONCAT_WS(' ', c.address_street, c.address_postcode, c.address_city) AS full_address
        FROM tasks t
        LEFT JOIN clients c ON t.client_id = c.id
        LEFT JOIN users u ON t.assigned_user_id = u.id
        ORDER BY t.id DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Add new task
if (preg_match('#^/api/tasks/?$#', $uri) && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("INSERT INTO tasks (title, description, client_id, assigned_user_id, priority, scheduled_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['title'], $data['description'], $data['client_id'], $data['assigned_user_id'], $data['priority'], $data['scheduled_date']
    ]);
    echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId()]);
    exit;
}

// Edit task
if (preg_match('#^/api/tasks/(\d+)$#', $uri, $match) && $method === 'POST') {
    $id = intval($match[1]);
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=?, client_id=?, assigned_user_id=?, priority=?, scheduled_date=? WHERE id=?");
    $stmt->execute([
        $data['title'], $data['description'], $data['client_id'], $data['assigned_user_id'], $data['priority'], $data['scheduled_date'], $id
    ]);
    echo json_encode(['success'=>true]);
    exit;
}

// Delete task
if (preg_match('#^/api/tasks/(\d+)$#', $uri, $match) && $method === 'DELETE') {
    $id = intval($match[1]);
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id=?");
    $stmt->execute([$id]);
    echo json_encode(['success'=>true]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
exit;
<?php
// api/clients.php v3 | Client listing, add, edit, delete (POST fallback) | est lines: ~60 | Author: franklos
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

// List all clients
if (preg_match('#^/api/clients/?$#', $uri) && $method === 'GET') {
    $stmt = $pdo->query("SELECT id, name, email, phone, address_city, address_postcode, created_at FROM clients ORDER BY id DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Add new client
if (preg_match('#^/api/clients/?$#', $uri) && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Basic validation
    if (empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Missing required field: name']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO clients (name, email, phone, address_city, address_postcode, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $data['name'], $data['email'] ?? '', $data['phone'] ?? '',
        $data['address_city'] ?? '', $data['address_postcode'] ?? ''
    ]);
    echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId()]);
    exit;
}

// Edit or Delete client via POST
if (preg_match('#^/api/clients/(\d+)$#', $uri, $match) && $method === 'POST') {
    $id = intval($match[1]);
    $data = json_decode(file_get_contents('php://input'), true);

    // DELETE via POST
    if (!empty($data['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM clients WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['success'=>true]);
        exit;
    }

    // Otherwise, edit
    if (empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Missing required field: name']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE clients SET name=?, email=?, phone=?, address_city=?, address_postcode=? WHERE id=?");
    $stmt->execute([
        $data['name'], $data['email'] ?? '', $data['phone'] ?? '',
        $data['address_city'] ?? '', $data['address_postcode'] ?? '', $id
    ]);
    echo json_encode(['success'=>true]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
exit;
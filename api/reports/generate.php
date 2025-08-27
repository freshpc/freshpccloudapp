<?php // ver# 2
// lines: est 51
require_once '../../config.php';
require_once '../../tcpdf/tcpdf.php';

// Allow GET or POST for PDF generation
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get task_id from URL
    $task_id = $_GET['task_id'] ?? null;
    if (!$task_id) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'Missing task_id']);
        exit;
    }

    // Fetch history record from DB
    $stmt = $pdo->prepare("SELECT id, task_id, engineer_id, action, from_status, to_status, notes, created_at FROM task_history WHERE id = ?");
    $stmt->execute([$task_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'History record not found']);
        exit;
    }

    // Generate PDF
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 14);
    $pdf->Write(0, "Task History Report\n\n");
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Write(0, 
        "Action: {$row['action']}\n" .
        "From Status: {$row['from_status']}\n" .
        "To Status: {$row['to_status']}\n" .
        "Notes: {$row['notes']}\n" .
        "Date: {$row['created_at']}\n"
    );

    // Output PDF directly in browser
    header('Content-Type: application/pdf');
    $pdf->Output("Task_History_{$task_id}.pdf", 'I');
    exit;
}

if ($method === 'POST') {
    // ... your previous POST handler here ...
    // (Keep your original POST code if you still need it)
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'POST handler not implemented in this version.']);
    exit;
}

// For other methods, return 405
header('Content-Type: application/json');
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;
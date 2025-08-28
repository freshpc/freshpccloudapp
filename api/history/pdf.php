<?php // ver# 4
// lines: est 38
require_once '../../config.php';
header('Content-Type: text/html');

$stmt = $pdo->query("SELECT id, task_id, engineer_id, action, from_status, to_status, notes, created_at FROM task_history ORDER BY id DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<div class="history-list">';
foreach ($rows as $row) {
    $task_id = intval($row['task_id']);
    $action = htmlspecialchars($row['action']);
    $from = htmlspecialchars($row['from_status'] ?? '');
    $to = htmlspecialchars($row['to_status'] ?? '');
    $notes = htmlspecialchars($row['notes']);
    $date = htmlspecialchars($row['created_at']);

    echo '<div class="history-card">';
    echo "<div><strong>Action:</strong> $action</div>";
    echo "<div><strong>From → To:</strong> $from → $to</div>";
    echo "<div><strong>Notes:</strong> $notes</div>";
    echo "<div><strong>Date:</strong> $date</div>";
    // FIX: Use task_id, not history_id
    echo "<div><a href='/api/reports/generate.php?task_id=$task_id' target='_blank'>Create PDF</a></div>";
    echo '</div>';
}
echo '</div>';
?>
<?php
session_start();
include '../config/database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$response = ['success' => true, 'unread_count' => 0, 'notifications' => []];

try {
    // Get unread messages count
    $count_query = "SELECT COUNT(*) as unread_count FROM contact_messages WHERE is_read = 0";
    $count_result = $conn->query($count_query);
    $unread_count = $count_result->fetch_assoc()['unread_count'] ?? 0;

    $response['unread_count'] = $unread_count;

    // Get latest unread messages (limit to 10 for performance)
    $notif_query = "SELECT id, name, message, created_at FROM contact_messages 
                   WHERE is_read = 0 
                   ORDER BY created_at DESC 
                   LIMIT 10";
    $notif_result = $conn->query($notif_query);

    if ($notif_result !== false) {
        while ($row = $notif_result->fetch_assoc()) {
            $response['notifications'][] = [
                'id' => $row['id'],
                'name' => htmlspecialchars($row['name']),
                'message' => htmlspecialchars(substr($row['message'], 0, 50)) . '...',
                'time' => date("d M, h:i A", strtotime($row['created_at']))
            ];
        }
    }

} catch (Exception $e) {
    error_log("Notification fetch error: " . $e->getMessage());
    $response['success'] = false;
}

echo json_encode($response);
?>
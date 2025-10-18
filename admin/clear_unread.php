<?php
session_start();
include '../config/database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$response = ['success' => false];

try {
    $update_query = "UPDATE contact_messages SET is_read = 1 WHERE is_read = 0";
    if ($conn->query($update_query)) {
        // Get the new unread count after clearing
        $count_query = "SELECT COUNT(*) as unread_count FROM contact_messages WHERE is_read = 0";
        $count_result = $conn->query($count_query);
        $unread_count = $count_result->fetch_assoc()['unread_count'] ?? 0;

        $response['success'] = true;
        $response['message'] = 'All messages marked as read';
        $response['new_unread_count'] = $unread_count;
    }
} catch (Exception $e) {
    error_log("Clear unread error: " . $e->getMessage());
    $response['message'] = 'Database error';
}

echo json_encode($response);
?>
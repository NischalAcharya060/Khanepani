<?php
session_start();
include '../config/database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$response = ['success' => false];

if (isset($_POST['message_id']) && is_numeric($_POST['message_id'])) {
    $message_id = intval($_POST['message_id']);

    try {
        // Mark the specific message as read
        $stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $message_id);

        if ($stmt->execute()) {
            // Get the new unread count
            $count_query = "SELECT COUNT(*) as unread_count FROM contact_messages WHERE is_read = 0";
            $count_result = $conn->query($count_query);
            $unread_count = $count_result->fetch_assoc()['unread_count'] ?? 0;

            $response['success'] = true;
            $response['new_unread_count'] = $unread_count;
            $response['message'] = 'Message marked as read';
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Mark message read error: " . $e->getMessage());
        $response['message'] = 'Database error';
    }
} else {
    $response['message'] = 'Invalid message ID';
}

echo json_encode($response);
?>
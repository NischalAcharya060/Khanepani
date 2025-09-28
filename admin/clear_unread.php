<?php
include '../config/db.php';
session_start();

// Ensure only admin can clear
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Update the correct column (change is_read → read_status if needed)
$sql = "UPDATE contact_messages SET is_read = 1 WHERE is_read = 0";
if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
?>

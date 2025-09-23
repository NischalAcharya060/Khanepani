<?php
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $type = htmlspecialchars(strip_tags(trim($_POST['type'])));
    $subject = htmlspecialchars(strip_tags(trim($_POST['subject'] ?? 'New Message')));
    $name = htmlspecialchars(strip_tags(trim($_POST['name'])));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(strip_tags(trim($_POST['message'])));
    $complaint_ref = isset($_POST['complaint_ref']) ? htmlspecialchars(strip_tags(trim($_POST['complaint_ref']))) : null;
    $complaint_details = isset($_POST['complaint_details']) ? htmlspecialchars(strip_tags(trim($_POST['complaint_details']))) : null;

    // Validate required fields
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($name) || empty($message) || empty($type)) {
        echo "<script>alert('Please fill all required fields correctly.'); window.location='contact.php';</script>";
        exit;
    }

    // Save message to database
    $stmt = $conn->prepare("
        INSERT INTO contact_messages 
        (type, subject, name, email, message, complaint_ref, complaint_details) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssss", $type, $subject, $name, $email, $message, $complaint_ref, $complaint_details);

    if ($stmt->execute()) {
        echo "<script>alert('Thank you! Your message has been saved successfully.'); window.location='contact.php';</script>";
    } else {
        echo "<script>alert('Sorry, your message could not be saved. Please try again later.'); window.location='contact.php';</script>";
    }
}
?>

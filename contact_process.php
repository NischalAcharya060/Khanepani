<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = htmlspecialchars(strip_tags(trim($_POST['name'])));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars(strip_tags(trim($_POST['subject'] ?? 'New Contact Message')));
    $message = htmlspecialchars(strip_tags(trim($_POST['message'])));

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please enter a valid email address.'); window.location='contact.php';</script>";
        exit;
    }

    // Prepare email headers
    $to = "info@salakpurkhanepani.com";
    $headers = "From: $name <$email>\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Prepare email body
    $body = "You have received a new message from your website contact form.\n\n";
    $body .= "Name: $name\n";
    $body .= "Email: $email\n";
    $body .= "Subject: $subject\n\n";
    $body .= "Message:\n$message\n";

    // Send email
    if (mail($to, $subject, $body, $headers)) {
        echo "<script>alert('Thank you! Your message has been sent successfully.'); window.location='contact.php';</script>";
    } else {
        echo "<script>alert('Sorry, your message could not be sent. Please try again later.'); window.location='contact.php';</script>";
    }
}
?>

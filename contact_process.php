<?php
include 'config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Language handling (same as in contact.php)
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','np'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$langFile = __DIR__ . '/lang/' . $_SESSION['lang'] . '.php';
if (file_exists($langFile)) {
    include $langFile;
} else {
    include __DIR__ . '/lang/en.php';
}

// reCAPTCHA Secret Key (replace with your key)
$secretKey = "6Lex7dcrAAAAAHVJIRLgG5EZqnZy7_6HroDQ2rC8";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ✅ Verify reCAPTCHA
    if (empty($_POST['g-recaptcha-response'])) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => "⚠️ " . $lang['captcha_required']
        ];
        header("Location: contact.php");
        exit;
    }

    $captcha = $_POST['g-recaptcha-response'];
    $verifyResponse = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$captcha}"
    );
    $responseData = json_decode($verifyResponse);

    if (!$responseData->success) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => "❌ " . $lang['captcha_failed']
        ];
        header("Location: contact.php");
        exit;
    }

    // ✅ Sanitize inputs
    $type = htmlspecialchars(strip_tags(trim($_POST['type'])));
    $subject = htmlspecialchars(strip_tags(trim($_POST['subject'] ?? $lang['default_subject'])));
    $name = htmlspecialchars(strip_tags(trim($_POST['name'])));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(strip_tags(trim($_POST['message'])));
    $complaint_ref = isset($_POST['complaint_ref']) ? htmlspecialchars(strip_tags(trim($_POST['complaint_ref']))) : null;
    $complaint_details = isset($_POST['complaint_details']) ? htmlspecialchars(strip_tags(trim($_POST['complaint_details']))) : null;

    // ✅ Validate required fields
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($name) || empty($message) || empty($type)) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => "⚠️ " . $lang['fill_required_fields']
        ];
        header("Location: contact.php");
        exit;
    }

    // ✅ Save message to database
    $stmt = $conn->prepare("
        INSERT INTO contact_messages 
        (type, subject, name, email, message, complaint_ref, complaint_details) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssss", $type, $subject, $name, $email, $message, $complaint_ref, $complaint_details);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'text' => "✅ " . $lang['message_saved']
        ];
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => "❌ " . $lang['message_failed']
        ];
    }

    header("Location: contact.php");
    exit;
}
?>

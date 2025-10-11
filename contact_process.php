<?php
include 'config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
$secretKey = "6Lex7dcrAAAAAHVJIRLgG5EZqnZy7_6HroDQ2rC8";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['g-recaptcha-response'])) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => "⚠️ " . ($lang['captcha_required'] ?? 'CAPTCHA verification is required.')
        ];
        header("Location: contact.php");
        exit;
    }
    $captcha = $_POST['g-recaptcha-response'];
    $verifyResponse = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$captcha}"
    );
    $responseData = json_decode($verifyResponse);
    if (!$responseData || !$responseData->success) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => "❌ " . ($lang['captcha_failed'] ?? 'CAPTCHA verification failed.')
        ];
        header("Location: contact.php");
        exit;
    }
    $type = htmlspecialchars(strip_tags(trim($_POST['type'] ?? '')));
    $subject = htmlspecialchars(strip_tags(trim($_POST['subject'] ?? ($lang['default_subject'] ?? 'Contact Message'))));
    $name = htmlspecialchars(strip_tags(trim($_POST['name'] ?? '')));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(strip_tags(trim($_POST['phone'] ?? '')));
    if (empty($phone)) { $phone = ''; }
    $message = htmlspecialchars(strip_tags(trim($_POST['message'] ?? '')));

    // 🚩 FIX: Ensure complaint fields are strings/empty strings for bind_param if they are NOT NULL in DB.
    // If they are NULL in the DB, this logic is safer than passing a PHP NULL.
    $complaint_ref_raw = trim($_POST['complaint_ref'] ?? '');
    $complaint_details_raw = trim($_POST['complaint_details'] ?? '');

    $complaint_ref = !empty($complaint_ref_raw) ? htmlspecialchars(strip_tags($complaint_ref_raw)) : '';
    $complaint_details = !empty($complaint_details_raw) ? htmlspecialchars(strip_tags($complaint_details_raw)) : '';

    // Check if we need to pass PHP NULL instead of empty string (if DB column is defined as NULL)
    // Based on your table structure provided earlier, these fields are nullable.
    if (empty($complaint_ref)) { $complaint_ref = NULL; }
    if (empty($complaint_details)) { $complaint_details = NULL; }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($name) || empty($message) || empty($type) || empty($subject)) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => "⚠️ " . ($lang['fill_required_fields'] ?? 'Please fill out all required fields correctly.')
        ];
        header("Location: contact.php");
        exit;
    }

    $sql = "
        INSERT INTO contact_messages 
        (type, subject, name, email, phone, message, complaint_ref, complaint_details, is_read) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
    ";
    if (!$conn) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => "❌ " . ($lang['db_connect_error'] ?? 'Database connection failed.')];
        header("Location: contact.php");
        exit;
    }
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // We use 's' for complaint fields, even if NULL, as they are Varchar/Text
        // The ssssssss marker is correct for 8 string/null variables.
        $stmt->bind_param("ssssssss",
            $type,
            $subject,
            $name,
            $email,
            $phone,
            $message,
            $complaint_ref,    // Will be PHP NULL or a string
            $complaint_details // Will be PHP NULL or a string
        );
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'text' => "✅ " . ($lang['message_saved'] ?? 'Your message has been sent successfully!')
            ];
        } else {
            // 🛑 CRITICAL STEP: Display the actual MySQL error for debugging
            error_log("MySQL INSERT Error: " . $stmt->error);
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'text' => "❌ " . ($lang['message_failed'] ?? 'Message failed to save. Error: ' . $stmt->error)
            ];
        }
        $stmt->close();
    } else {
        error_log("MySQL Prepare Error: " . $conn->error);
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => "❌ " . ($lang['db_prepare_error'] ?? 'Database preparation failed. Error: ' . $conn->error)
        ];
    }
    $conn->close();
    header("Location: contact.php");
    exit;
}
?>
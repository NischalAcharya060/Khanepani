<?php
session_start();
include '../config/db.php';
include '../config/lang.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Get message ID from query
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: messages.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch message
$stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$message = $result->fetch_assoc();

if (!$message) {
    header("Location: messages.php");
    exit();
}

include '../config/Nepali_Calendar.php';
$cal = new Nepali_Calendar();

function format_nepali_date($date_str, $cal) {
    $timestamp = strtotime($date_str);
    $year  = (int)date('Y', $timestamp);
    $month = (int)date('m', $timestamp);
    $day   = (int)date('d', $timestamp);
    $hour  = (int)date('h', $timestamp); // 12-hour
    $minute = (int)date('i', $timestamp);
    $ampm  = date('A', $timestamp);

    if ( ($_SESSION['lang'] ?? 'en') === 'np' ) {
        $nepDate = $cal->eng_to_nep($year, $month, $day);
        $np_numbers = ['0'=>'०','1'=>'१','2'=>'२','3'=>'३','4'=>'४','5'=>'५','6'=>'६','7'=>'७','8'=>'८','9'=>'९'];

        $yearNep  = strtr($nepDate['year'], $np_numbers);
        $monthNep = strtr($nepDate['month'], $np_numbers);
        $dayNep   = strtr($nepDate['date'], $np_numbers);
        $hourNep  = strtr(sprintf("%02d", $hour), $np_numbers);
        $minNep   = strtr(sprintf("%02d", $minute), $np_numbers);

        return $dayNep . '-' . $monthNep . '-' . $yearNep . ', ' . $hourNep . ':' . $minNep . ' ' . $ampm;
    } else {
        return date("d M Y, h:i A", $timestamp);
    }
}

// Mark as read
$update = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
$update->bind_param("i", $id);
$update->execute();

// Get unread count for notification
$countResult = $conn->query("SELECT COUNT(*) AS unread_count FROM contact_messages WHERE is_read = 0");
$unread = $countResult->fetch_assoc()['unread_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['view'] ?> - <?= $lang['logo'] ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background: #f4f6f9; margin: 0; }
        .main-content { padding: 40px 20px; max-width: 900px; margin: auto; }

        .message-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            padding: 30px 35px;
            animation: fadeInUp 0.4s ease;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .message-header h2 { color: #007bff; margin: 0; font-size: 28px; }
        .message-header a {
            text-decoration: none;
            color: #fff;
            background: #007bff;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .message-header a:hover { background: #0056b3; }

        .message-field { margin-bottom: 18px; }
        .message-label { font-weight: 600; color: #555; display: block; margin-bottom: 6px; font-size: 14px; }
        .message-value {
            background: #f4f6f9;
            padding: 12px 14px;
            border-radius: 8px;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 15px;
            color: #333;
        }
        .message-value.email { color: #007bff; font-weight: 500; }

        /* Notification */
        .notification {
            position: relative;
            margin-right: 20px;
            cursor: pointer;
        }
        .notification i {
            font-size: 22px;
            color: #333;
        }
        .notification .badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background: red;
            color: white;
            font-size: 12px;
            padding: 3px 6px;
            border-radius: 50%;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <div class="message-card">
        <div class="message-header">
            <h2>📬 <?= $lang['view'] ?> <?= $lang['message'] ?></h2>
            <a href="messages.php">« <?= $lang['back'] ?></a>
        </div>

        <div class="message-field">
            <span class="message-label"><?= $lang['name'] ?></span>
            <div class="message-value"><?= htmlspecialchars($message['name']) ?></div>
        </div>

        <div class="message-field">
            <span class="message-label"><?= $lang['email'] ?></span>
            <div class="message-value email"><?= htmlspecialchars($message['email']) ?></div>
        </div>

        <div class="message-field">
            <span class="message-label"><?= $lang['subject'] ?></span>
            <div class="message-value"><?= htmlspecialchars($message['subject']) ?></div>
        </div>

        <div class="message-field">
            <span class="message-label"><?= $lang['message'] ?></span>
            <div class="message-value"><?= nl2br(htmlspecialchars($message['message'])) ?></div>
        </div>

        <div class="message-field">
            <span class="message-label"><?= $lang['date'] ?></span>
            <div class="message-value"><?= format_nepali_date($message['created_at'], $cal) ?></div>
        </div>
    </div>
</main>

</body>
</html>

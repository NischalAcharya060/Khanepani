<?php
session_start();
include '../config/database/db.php';
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

include '../config/Nepali_calendar.php';
$cal = new Nepali_Calendar();

function format_nepali_date($date_str, $cal) {
    $timestamp = strtotime($date_str);
    $year  = (int)date('Y', $timestamp);
    $month = (int)date('m', $timestamp);
    $day   = (int)date('d', $timestamp);
    $hour  = (int)date('h', $timestamp); // 12-hour
    $minute = (int)date('i', $timestamp);
    $ampm  = date('A', $timestamp);

    // Get current language from session or default to English
    $current_lang = $_SESSION['lang'] ?? 'en';

    if ( $current_lang === 'np' ) {
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
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['view'] ?> <?= $lang['message'] ?> - <?= $lang['logo'] ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>

        .main-content {
            padding: 30px;
            max-width: 900px;
            margin: auto;
        }

        /* --- Back Button Styling --- */
        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            margin-bottom: 25px;
            background: var(--secondary-color);
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s, transform 0.1s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        .back-btn svg {
            width: 18px;
            height: 18px;
            margin-right: 8px;
        }

        /* --- Message Card --- */
        .message-card {
            background: var(--card-background);
            border-radius: 12px;
            box-shadow: var(--shadow-light);
            padding: 30px 40px;
        }

        .message-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
        }
        .message-header h2 {
            color: var(--primary-color);
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        .message-header h2 svg {
            margin-right: 10px;
            width: 28px;
            height: 28px;
        }

        /* --- Metadata Grid --- */
        .metadata-grid {
            display: grid;
            /* Default: Min width 200px, responsive to fill space */
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .message-field {
            background: var(--background-light);
            padding: 15px;
            border-radius: 8px;
        }

        .message-label {
            font-weight: 500;
            color: var(--secondary-color);
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .message-value {
            font-size: 16px;
            color: var(--text-dark);
            font-weight: 500;
            /* Ensure long content (like email) wraps */
            word-break: break-all;
        }
        .message-value.email { color: var(--primary-color); }

        /* --- Main Message Body --- */
        .message-body {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            background: #fff;
        }
        .message-body-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 18px;
            border-bottom: 1px dashed var(--border-color);
            padding-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .message-body-content {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 15px;
            line-height: 1.6;
            color: #495057;
        }

        /* --- Responsive Refinements --- */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }
            .message-card {
                padding: 25px 20px;
            }
            /* Stack fields vertically on mobile */
            .metadata-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .message-header h2 {
                font-size: 24px;
            }
            .message-header h2 svg {
                width: 24px;
                height: 24px;
            }
            .message-body-label {
                font-size: 16px;
            }
            .message-body-content {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .back-btn {
                width: 100%;
                justify-content: center;
            }
            .message-card {
                padding: 15px;
                border-radius: 8px;
            }
        }
    </style>
</head>
<body>

<?php
// Assuming this component exists and handles its own responsiveness (e.g., sidebar collapse)
include '../components/admin_header.php';
?>

<main class="main-content">

    <a href="messages.php" class="back-btn">
        <i data-feather="arrow-left"></i>
        <?= $lang['back'] ?? 'Back to Messages' ?>
    </a>

    <div class="message-card">
        <div class="message-header">
            <h2><i data-feather="mail"></i> <?= $lang['view'] ?> <?= $lang['message'] ?></h2>
        </div>

        <div class="metadata-grid">

            <div class="message-field">
                <span class="message-label"><i data-feather="user" style="width:14px; height:14px;"></i> <?= $lang['name'] ?></span>
                <div class="message-value"><?= htmlspecialchars($message['name']) ?></div>
            </div>

            <div class="message-field">
                <span class="message-label"><i data-feather="mail" style="width:14px; height:14px;"></i> <?= $lang['email'] ?></span>
                <div class="message-value email"><?= htmlspecialchars($message['email']) ?></div>
            </div>

            <div class="message-field">
                <span class="message-label"><i data-feather="phone" style="width:14px; height:14px;"></i> <?= $lang['phone'] ?></span>
                <div class="message-value phone"><?= htmlspecialchars($message['phone']) ?></div>
            </div>

            <div class="message-field">
                <span class="message-label"><i data-feather="clock" style="width:14px; height:14px;"></i> <?= $lang['date'] ?></span>
                <div class="message-value"><?= format_nepali_date($message['created_at'], $cal) ?></div>
            </div>

            <div class="message-field">
                <span class="message-label"><i data-feather="tag" style="width:14px; height:14px;"></i> <?= $lang['subject'] ?></span>
                <div class="message-value"><?= htmlspecialchars($message['subject']) ?></div>
            </div>

            <?php if (isset($message['type'])): ?>
                <div class="message-field">
                    <span class="message-label"><i data-feather="bookmark" style="width:14px; height:14px;"></i> <?= $lang['type'] ?? 'Type' ?></span>
                    <div class="message-value"><?= htmlspecialchars(ucfirst($message['type'])) ?></div>
                </div>
            <?php endif; ?>

        </div>

        <div class="message-body">
            <div class="message-body-label"><i data-feather="message-square"></i> <?= $lang['message'] ?>:</div>
            <div class="message-body-content"><?= nl2br(htmlspecialchars($message['message'])) ?></div>
        </div>
    </div>
</main>

<script>
    // Initialize Feather Icons
    feather.replace();
</script>

</body>
</html>
<?php
session_start();
include '../config/db.php';
include '../config/lang.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'], $_POST['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $id = intval($_POST['toggle_id']);
        $result = $conn->query("SELECT is_read FROM contact_messages WHERE id = $id");
        if ($result && $row = $result->fetch_assoc()) {
            $new_status = $row['is_read'] == 1 ? 0 : 1; // Toggle
            $stmt = $conn->prepare("UPDATE contact_messages SET is_read = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_status, $id);
            $stmt->execute();
        }
        header("Location: messages.php?page=" . $page);
        exit();
    } else {
        die("Invalid CSRF token.");
    }
}

//Nepali date
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

// Pagination settings
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Fetch messages with limit
$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT $offset, $limit");

// Get total messages for pagination
$total_result = $conn->query("SELECT COUNT(*) as total FROM contact_messages");
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['messages'] ?> - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        .notice-table th, .notice-table td {
            padding: 12px 8px;
        }
        .notice-table tr:hover {
            background: #f1f1f1;
        }
        .pagination {
            text-align: center;
            margin-top: 20px;
        }
        .pagination a {
            margin: 0 5px;
            text-decoration: none;
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #0056d6;
        }
        .pagination a.active {
            background-color: #0056d6;
            color: white;
            border-color: #0056d6;
            font-weight: bold;

        }
        .pagination a:hover {
            background-color: #0056d6;
            color: white;
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <h2>📬 <?= $lang['messages'] ?></h2>
    <p class="subtitle"><?= $lang['messages_subtitle'] ?? "View messages sent from the contact form." ?></p>

    <table class="notice-table">
        <thead>
        <tr>
            <th><?= $lang['sn'] ?></th>
            <th><?= $lang['name'] ?? "Name" ?></th>
            <th><?= $lang['email'] ?? "Email" ?></th>
            <th><?= $lang['subject'] ?? "Subject" ?></th>
            <th><?= $lang['message'] ?? "Message" ?></th>
            <th><?= $lang['status'] ?? "Status" ?></th>
            <th><?= $lang['date'] ?? "Date" ?></th>
            <th><?= $lang['actions'] ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if ($messages->num_rows > 0): ?>
            <?php $sn = $offset + 1; ?>
            <?php while ($msg = $messages->fetch_assoc()): ?>
                <tr>
                    <td><?= $sn++ ?></td>
                    <td><?= htmlspecialchars($msg['name']) ?></td>
                    <td><?= htmlspecialchars($msg['email']) ?></td>
                    <td><?= htmlspecialchars($msg['subject']) ?></td>
                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    </td>
                    <td>
                        <?php if ($msg['is_read'] == 1): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="toggle_id" value="<?= $msg['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <button type="submit" class="btn small" style="background:green; color:white;">
                                    <?= $lang['read'] ?? "✔ Read" ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="toggle_id" value="<?= $msg['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <button type="submit" class="btn small" style="background:red; color:white;">
                                    <?= $lang['unread'] ?? "✉ Unread" ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td><?= format_nepali_date($msg['created_at'], $cal) ?></td>
                    <td>
                        <a href="view_message.php?id=<?= $msg['id'] ?>" class="btn small"><?= $lang['view'] ?? "👁 View" ?></a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('<?= $lang['delete_confirm_message'] ?? "Delete this message?" ?>');">
                            <input type="hidden" name="delete_id" value="<?= $msg['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" class="btn small danger"><?= $lang['delete'] ?? "🗑 Delete" ?></button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align:center; padding:20px;"><?= $lang['no_messages'] ?? "No messages found." ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        <?php if($page > 1): ?>
            <a href="?page=<?= $page-1 ?>"><?= $lang['previous'] ?></a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        for($p=$start; $p<=$end; $p++):
            ?>
            <a href="?page=<?= $p ?>" class="<?= ($p==$page)?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>

        <?php if($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?>"><?= $lang['next'] ?></a>
        <?php endif; ?>
    </div>
</main>


</body>
</html>

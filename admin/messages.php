<?php
session_start();
include '../config/database/db.php';
include '../config/lang.php';
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'], $_POST['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $id = intval($_POST['toggle_id']);
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $result = $conn->query("SELECT is_read FROM contact_messages WHERE id = $id");
        if ($result && $row = $result->fetch_assoc()) {
            $new_status = $row['is_read'] == 1 ? 0 : 1;
            $stmt = $conn->prepare("UPDATE contact_messages SET is_read = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_status, $id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: messages.php?page=" . $page);
        exit();
    } else {
        die("Invalid CSRF token.");
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'], $_POST['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $id = intval($_POST['delete_id']);
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: messages.php?page=" . $page);
        exit();
    } else {
        die("Invalid CSRF token.");
    }
}
include '../config/Nepali_calendar.php';
$cal = new Nepali_Calendar();
function format_nepali_date($date_str, $cal) {
    $timestamp = strtotime($date_str);
    $year  = (int)date('Y', $timestamp);
    $month = (int)date('m', $timestamp);
    $day   = (int)date('d', $timestamp);
    $hour  = (int)date('h', $timestamp);
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
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT $offset, $limit");
$total_result = $conn->query("SELECT COUNT(*) as total FROM contact_messages");
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['messages'] ?> - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/dark-mode.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary-color: #007bff;
            --primary-dark: #0056b3;
            --secondary-color: #6c757d;
            --background-light: #f4f6f9;
            --card-background: #ffffff;
            --border-color: #e9ecef;
            --text-dark: #343a40;
            --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--background-light);
            color: var(--text-dark);
            line-height: 1.6;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        main {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
            min-height: calc(100vh - 80px);
        }

        .page-header {
            margin-bottom: 30px;
        }

        h2 {
            font-size: clamp(24px, 5vw, 32px);
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        h2 svg {
            width: clamp(24px, 6vw, 32px);
            height: clamp(24px, 6vw, 32px);
            flex-shrink: 0;
        }

        .subtitle {
            color: var(--secondary-color);
            font-size: clamp(14px, 3vw, 16px);
            line-height: 1.5;
        }

        .card {
            background: var(--card-background);
            padding: clamp(20px, 4vw, 30px);
            border-radius: 12px;
            box-shadow: var(--shadow-light);
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            margin: 20px 0;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
            background: var(--card-background);
        }

        th, td {
            padding: clamp(12px, 3vw, 16px);
            text-align: left;
            font-size: clamp(13px, 2.5vw, 15px);
            border-bottom: 1px solid var(--border-color);
            background: var(--card-background);
            color: var(--text-dark);
        }

        th {
            background: var(--primary-color);
            font-weight: 600;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: clamp(12px, 2vw, 13px);
            position: sticky;
            top: 0;
        }

        tbody tr:nth-child(even) {
            background: rgba(0, 0, 0, 0.02);
        }

        tbody tr:hover {
            background: rgba(0, 123, 255, 0.08);
            transition: background-color 0.2s ease;
        }

        .message-content {
            max-width: 300px;
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.4;
            color: var(--text-dark);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-read {
            background: var(--success-color);
            color: white;
        }

        .status-unread {
            background: var(--warning-color);
            color: #000;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border: 1px solid transparent;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            gap: 6px;
            min-height: 36px;
            background: var(--background-light);
            color: var(--text-dark);
            border-color: var(--border-color);
        }

        .btn.small {
            padding: 6px 12px;
            font-size: 13px;
            min-height: 32px;
        }

        .btn.success {
            background: var(--success-color);
            color: white;
            border-color: var(--success-color);
        }

        .btn.warning {
            background: var(--warning-color);
            color: #000;
            border-color: var(--warning-color);
        }

        .btn.danger {
            background: var(--danger-color);
            color: white;
            border-color: var(--danger-color);
        }

        .btn.info {
            background: var(--info-color);
            color: white;
            border-color: var(--info-color);
        }

        .btn.primary {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            filter: brightness(1.1);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Mobile Card View */
        .mobile-messages {
            display: none;
            gap: 16px;
            flex-direction: column;
        }

        .message-card {
            background: var(--card-background);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-light);
            border-left: 4px solid var(--primary-color);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .message-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 16px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .message-sender {
            flex: 1;
            min-width: 0;
        }

        .message-name {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 16px;
            margin-bottom: 4px;
        }

        .message-contact {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 14px;
            color: var(--secondary-color);
        }

        .message-email::before {
            content: "📧 ";
        }

        .message-phone::before {
            content: "📞 ";
        }

        .message-status {
            flex-shrink: 0;
        }

        .message-subject {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 12px;
            font-size: 15px;
            background: rgba(0, 123, 255, 0.1);
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid var(--primary-color);
        }

        .message-content-mobile {
            background: var(--background-light);
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            line-height: 1.5;
            font-size: 14px;
            color: var(--text-dark);
            border: 1px solid var(--border-color);
        }

        .message-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .message-date {
            color: var(--secondary-color);
            font-size: 13px;
        }

        .message-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 30px;
            padding: 0 10px;
        }

        .pagination a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: clamp(8px, 2vw, 10px) clamp(12px, 3vw, 16px);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: clamp(13px, 2.5vw, 14px);
            transition: all 0.2s ease;
            min-width: 44px;
            min-height: 44px;
            background: var(--card-background);
        }

        .pagination a.active {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-dark);
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
        }

        .pagination a:hover:not(.active) {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
            transform: translateY(-1px);
        }

        .pagination-info {
            text-align: center;
            color: var(--secondary-color);
            font-size: 14px;
            margin-top: 15px;
            width: 100%;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--secondary-color);
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            color: var(--border-color);
        }

        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--secondary-color);
        }

        .empty-state p {
            font-size: 14px;
            max-width: 300px;
            margin: 0 auto;
            color: var(--secondary-color);
        }

        /* Form Styles */
        form {
            display: inline;
        }

        /* Loading State */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Dark Mode Specific Fixes */
        body.dark-mode .message-content-mobile {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-dark);
        }

        body.dark-mode .message-subject {
            background: rgba(67, 97, 238, 0.15);
            color: var(--primary-light);
        }

        body.dark-mode tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }

        body.dark-mode tbody tr:hover {
            background: rgba(67, 97, 238, 0.1);
        }

        body.dark-mode .btn:not(.success):not(.warning):not(.danger):not(.info):not(.primary) {
            background: var(--background-light);
            color: var(--text-dark);
            border-color: var(--border-color);
        }

        body.dark-mode .btn:not(.success):not(.warning):not(.danger):not(.info):not(.primary):hover {
            background: var(--primary-color);
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            main {
                padding: 15px;
            }

            .card {
                padding: 20px 15px;
                border-radius: 10px;
            }

            .desktop-table {
                display: none;
            }

            .mobile-messages {
                display: flex;
            }

            .message-card {
                padding: 16px;
            }

            .message-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .message-contact {
                flex-direction: column;
                gap: 4px;
            }

            .message-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .message-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .pagination {
                gap: 6px;
            }

            .pagination a {
                padding: 10px 14px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 10px;
            }

            .page-header {
                margin-bottom: 20px;
            }

            h2 {
                font-size: 22px;
            }

            .card {
                padding: 15px 10px;
                border-radius: 8px;
            }

            .message-card {
                padding: 14px;
            }

            .message-actions {
                flex-direction: column;
                width: 100%;
            }

            .message-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .pagination {
                flex-direction: column;
                align-items: center;
            }

            .pagination a {
                width: 100%;
                max-width: 200px;
                justify-content: center;
            }
        }

        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
            }

            .btn:hover {
                transform: none;
            }
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Print Styles */
        @media print {
            body {
                background: white !important;
            }

            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .pagination {
                display: none;
            }

            .message-actions {
                display: none;
            }
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main>
    <div class="page-header">
        <h2>
            <i data-feather="mail"></i>
            <?= $lang['messages'] ?>
        </h2>
        <p class="subtitle"><?= $lang['messages_subtitle'] ?? "View messages sent from the contact form." ?></p>
    </div>

    <div class="card">
        <!-- Desktop Table View -->
        <div class="table-container desktop-table">
            <table>
                <thead>
                <tr>
                    <th><?= $lang['sn'] ?></th>
                    <th><?= $lang['name'] ?? "Name" ?></th>
                    <th><?= $lang['email'] ?? "Email" ?></th>
                    <th><?= $lang['phone'] ?? "Phone" ?></th>
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
                            <td><strong><?= htmlspecialchars($msg['name']) ?></strong></td>
                            <td><?= htmlspecialchars($msg['email'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($msg['phone'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($msg['subject']) ?></td>
                            <td class="message-content">
                                <?= htmlspecialchars($msg['message']) ?>
                            </td>
                            <td>
                                    <span class="status-badge <?= $msg['is_read'] == 1 ? 'status-read' : 'status-unread' ?>">
                                        <?= $msg['is_read'] == 1 ? ($lang['read'] ?? 'Read') : ($lang['unread'] ?? 'Unread') ?>
                                    </span>
                            </td>
                            <td><?= format_nepali_date($msg['created_at'], $cal) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST">
                                        <input type="hidden" name="toggle_id" value="<?= $msg['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="btn small <?= $msg['is_read'] == 1 ? 'warning' : 'success' ?>">
                                            <?= $msg['is_read'] == 1 ? ($lang['mark_unread'] ?? "Unread") : ($lang['mark_read'] ?? "Read") ?>
                                        </button>
                                    </form>
                                    <a href="view_message.php?id=<?= $msg['id'] ?>" class="btn small info">
                                        <i data-feather="eye"></i>
                                        <?= $lang['view'] ?? "View" ?>
                                    </a>
                                    <form method="POST" onsubmit="return confirm('<?= $lang['delete_confirm_message'] ?? "Are you sure you want to delete this message?" ?>');">
                                        <input type="hidden" name="delete_id" value="<?= $msg['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="btn small danger">
                                            <i data-feather="trash-2"></i>
                                            <?= $lang['delete'] ?? "Delete" ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i data-feather="inbox"></i>
                                <h3><?= $lang['no_messages'] ?? "No messages found" ?></h3>
                                <p><?= $lang['no_messages_desc'] ?? "There are no contact messages to display." ?></p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="mobile-messages">
            <?php if ($messages->num_rows > 0): ?>
                <?php
                // Reset pointer and recreate messages for mobile view
                $messages->data_seek(0);
                $sn = $offset + 1;
                ?>
                <?php while ($msg = $messages->fetch_assoc()): ?>
                    <div class="message-card">
                        <div class="message-header">
                            <div class="message-sender">
                                <div class="message-name"><?= htmlspecialchars($msg['name']) ?></div>
                                <div class="message-contact">
                                    <span class="message-email"><?= htmlspecialchars($msg['email'] ?? 'N/A') ?></span>
                                    <span class="message-phone"><?= htmlspecialchars($msg['phone'] ?? 'N/A') ?></span>
                                </div>
                            </div>
                            <div class="message-status">
                                <span class="status-badge <?= $msg['is_read'] == 1 ? 'status-read' : 'status-unread' ?>">
                                    <?= $msg['is_read'] == 1 ? ($lang['read'] ?? 'Read') : ($lang['unread'] ?? 'Unread') ?>
                                </span>
                            </div>
                        </div>

                        <div class="message-subject"><?= htmlspecialchars($msg['subject']) ?></div>

                        <div class="message-content-mobile">
                            <?= htmlspecialchars($msg['message']) ?>
                        </div>

                        <div class="message-footer">
                            <div class="message-date">
                                <?= format_nepali_date($msg['created_at'], $cal) ?>
                            </div>
                            <div class="message-actions">
                                <form method="POST">
                                    <input type="hidden" name="toggle_id" value="<?= $msg['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn small <?= $msg['is_read'] == 1 ? 'warning' : 'success' ?> icon-<?= $msg['is_read'] == 1 ? 'mail' : 'check' ?>">
                                        <?= $msg['is_read'] == 1 ? ($lang['mark_unread'] ?? "Unread") : ($lang['mark_read'] ?? "Read") ?>
                                    </button>
                                </form>
                                <a href="view_message.php?id=<?= $msg['id'] ?>" class="btn small info">
                                    <i data-feather="eye"></i>
                                    <?= $lang['view'] ?? "View" ?>
                                </a>
                                <form method="POST" onsubmit="return confirm('<?= $lang['delete_confirm_message'] ?? "Delete this message?" ?>');">
                                    <input type="hidden" name="delete_id" value="<?= $msg['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn small danger">
                                        <i data-feather="trash-2"></i>
                                        <?= $lang['delete'] ?? "Delete" ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i data-feather="inbox"></i>
                    <h3><?= $lang['no_messages'] ?? "No messages found" ?></h3>
                    <p><?= $lang['no_messages_desc'] ?? "There are no contact messages to display." ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>" aria-label="<?= $lang['previous'] ?? 'Previous page' ?>">
                        <?= $lang['previous'] ?? '« Previous' ?>
                    </a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);

                // Show first page if not in range
                if ($start > 1) {
                    echo '<a href="?page=1">1</a>';
                    if ($start > 2) echo '<span style="padding: 10px; color: var(--secondary-color);">...</span>';
                }

                for($p = $start; $p <= $end; $p++): ?>
                    <a href="?page=<?= $p ?>" class="<?= ($p == $page) ? 'active' : '' ?>" aria-label="Page <?= $p ?>" aria-current="<?= ($p == $page) ? 'page' : '' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php
                // Show last page if not in range
                if ($end < $total_pages) {
                    if ($end < $total_pages - 1) echo '<span style="padding: 10px; color: var(--secondary-color);">...</span>';
                    echo '<a href="?page=' . $total_pages . '">' . $total_pages . '</a>';
                }
                ?>

                <?php if($page < $total_pages): ?>
                    <a href="?page=<?= $page+1 ?>" aria-label="<?= $lang['next'] ?? 'Next page' ?>">
                        <?= $lang['next'] ?? 'Next »' ?>
                    </a>
                <?php endif; ?>

                <div class="pagination-info">
                    <?= sprintf($lang['page_info'] ?? 'Page %d of %d', $page, $total_pages) ?>
                    <?php if ($messages && $messages->num_rows > 0): ?>
                        • <?= sprintf($lang['showing'] ?? 'Showing %d messages', $messages->num_rows) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    // Initialize Feather icons
    feather.replace();

    // Add loading state for pagination links and form submissions
    document.addEventListener('DOMContentLoaded', function() {
        const paginationLinks = document.querySelectorAll('.pagination a');
        const forms = document.querySelectorAll('form');

        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const card = document.querySelector('.card');
                card.classList.add('loading');

                setTimeout(() => {
                    card.classList.remove('loading');
                }, 1000);
            });
        });

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const card = document.querySelector('.card');
                card.classList.add('loading');

                setTimeout(() => {
                    card.classList.remove('loading');
                }, 2000);
            });
        });

        // Handle window resize for better mobile experience
        function handleResize() {
            const card = document.querySelector('.card');
            if (window.innerWidth <= 768) {
                card.classList.add('mobile-view');
            } else {
                card.classList.remove('mobile-view');
            }
        }

        // Initial check
        handleResize();

        // Listen for resize events
        window.addEventListener('resize', handleResize);
    });
</script>
</body>
</html>
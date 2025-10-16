<?php
session_start();
include '../config/database/db.php';

include '../config/Nepali_calendar.php';
$cal = new Nepali_Calendar();

function format_nepali_date($date_str, $cal) {
    $timestamp = strtotime($date_str);
    $year  = (int)date('Y', $timestamp);
    $month = (int)date('m', $timestamp);
    $day   = (int)date('d', $timestamp);
    $hour  = (int)date('h', $timestamp); // 12-hour format
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

        $ampm_nep = ($ampm === 'AM' ? ($lang['am'] ?? 'पूर्वाह्न') : ($lang['pm'] ?? 'अपराह्न'));

        return $dayNep . '-' . $monthNep . '-' . $yearNep . ', ' . $hourNep . ':' . $minNep . ' ' . $ampm_nep;
    } else {
        return date("d M Y, h:i A", $timestamp);
    }
}

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Language handling
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // default
}
if (isset($_GET['lang'])) {
    $allowed_langs = ['en','np'];
    if (in_array($_GET['lang'], $allowed_langs)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
}
include '../lang/' . $_SESSION['lang'] . '.php';

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_notices.php");
    exit();
}

// Pagination settings
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Fetch notices with limit
$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT $offset, $limit");

// Get total notices for pagination
$total_result = $conn->query("SELECT COUNT(*) as total FROM notices");
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['manage_notices'] ?> - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Existing Styles */
        .notice-table th, .notice-table td { padding: 12px 8px; }
        .notice-table tr:hover { background: #f1f1f1; }
        .pagination { text-align: center; margin-top: 20px; }
        .pagination a { margin: 0 5px; text-decoration: none; padding: 6px 12px; border: 1px solid #ddd; border-radius: 4px; color: #0056d6; }
        .pagination a.active { background-color: #0056d6; color: white; border-color: #0056d6; }
        .pagination a:hover { background-color: #0056d6; color: white; }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .message i {
            margin-right: 10px;
            width: 20px;
            height: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* --- START Responsive Table CSS Fix --- */
        @media screen and (max-width: 768px) {

            .main-content {
                padding-left: 15px !important;
                padding-right: 15px !important;
            }

            /* Force table to not be like a table */
            .notice-table {
                border: 0;
                box-shadow: none;
                border-radius: 0;
                margin-top: 20px;
            }

            .notice-table thead {
                display: none; /* Hide table headers */
            }

            .notice-table tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .notice-table tr:hover {
                background: #ffffff; /* Keep card background white on hover */
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            .notice-table td {
                display: block;
                text-align: right !important;
                border-bottom: 1px solid #f0f0f0;
                position: relative;
                padding-left: 45% !important; /* Make room for the label */
                white-space: normal;
                word-break: break-word;
            }

            .notice-table tr:last-child td {
                border-bottom: 1px solid #f0f0f0; /* Ensure all rows have a separator */
            }

            .notice-table td:before {
                /* The data label/header from the original table */
                position: absolute;
                left: 12px;
                width: 40%;
                text-align: left;
                font-weight: 600;
                color: #34495e; /* A clear color for labels */
                text-transform: capitalize;
                content: attr(data-label);
            }

            /* S.N. and Title handling for a card header feel */
            .notice-table tr td:nth-child(1) { /* S.N. */
                text-align: left !important;
                font-weight: 700;
                background-color: #f8f9fa;
                border-top-left-radius: 8px;
                border-top-right-radius: 8px;
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            .notice-table tr td:nth-child(1):before {
                content: 'S.N.'; /* Use a fixed label for S.N. as it's a number */
                color: #212529;
            }

            .notice-table tr td:nth-child(2) { /* Title */
                font-weight: 500;
            }

            /* Actions column */
            .notice-table td:last-child {
                border-bottom: none;
                text-align: center !important;
                padding: 15px 10px !important;
                display: flex;
                flex-direction: column;
                gap: 8px; /* Space out the action buttons */
            }
            .notice-table td:last-child a.btn {
                flex-grow: 1;
                width: 100%; /* Make buttons full width */
            }

            /* Use data-label attribute for all columns */
            .notice-table tr td:nth-child(1) { /* S.N. */
                text-align: center !important;
                padding-left: 10px !important;
            }
            .notice-table tr td:nth-child(1):before { content: '<?= $lang['sn'] ?? 'S.N.' ?>'; position: initial; width: auto; }
            .notice-table tr td:nth-child(2):before { content: '<?= $lang['title'] ?? 'Title' ?>'; }
            .notice-table tr td:nth-child(3):before { content: '<?= $lang['date'] ?? 'Date' ?>'; }
            .notice-table tr td:nth-child(4):before { content: '<?= $lang['created_by'] ?? 'Created By' ?>'; }
            .notice-table tr td:nth-child(5):before { content: '<?= $lang['actions'] ?? 'Actions' ?>'; position: initial; width: auto; }
        }

        /* Further optimization for small phones */
        @media screen and (max-width: 480px) {
            .notice-table td {
                padding-left: 50% !important;
            }
            .notice-table td:before {
                width: 45%;
                font-size: 0.9em;
            }
            .notice-table td:last-child {
                flex-direction: column;
            }
        }
        /* --- END Responsive Table CSS Fix --- */
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <h2>📢 <?= $lang['manage_notices'] ?></h2>
    <p class="subtitle"><?= $lang['manage_notices_subtitle'] ?? 'Add, edit, view, or remove notices quickly and efficiently.' ?></p>

    <a href="add_notice.php" class="btn">➕ <?= $lang['add_notice'] ?? 'Add New Notice' ?></a>

    <?php
    // Display Success Message
    if (isset($_SESSION['success'])): ?>
        <div class='message success'>
            <i data-feather="check-circle"></i>
            <?= $_SESSION['success']; ?>
        </div>
        <?php unset($_SESSION['success']);
    endif;

    if (isset($_SESSION['error_message'])): ?>
        <div class='message error'>
            <i data-feather="alert-triangle"></i>
            <?= $_SESSION['error_message']; ?>
        </div>
        <?php unset($_SESSION['error_message']);
    endif;
    ?>

    <table class="notice-table">
        <thead>
        <tr>
            <th><?= $lang['sn'] ?? 'S.N.' ?></th>
            <th><?= $lang['title'] ?? 'Title' ?></th>
            <th><?= $lang['date'] ?? 'Date' ?></th>
            <th><?= $lang['created_by'] ?? 'Created By' ?></th>
            <th><?= $lang['actions'] ?? 'Actions' ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if ($notices->num_rows > 0): ?>
            <?php $sn = $offset + 1; ?>
            <?php while ($notice = $notices->fetch_assoc()): ?>
                <tr>
                    <td data-label="<?= $lang['sn'] ?? 'S.N.' ?>"><?= $sn++ ?></td>
                    <td data-label="<?= $lang['title'] ?? 'Title' ?>"><?= htmlspecialchars($notice['title']) ?></td>
                    <td data-label="<?= $lang['date'] ?? 'Date' ?>"><?= format_nepali_date($notice['created_at'], $cal) ?></td>
                    <td data-label="<?= $lang['created_by'] ?? 'Created By' ?>"><?= !empty($notice['created_by']) ? htmlspecialchars($notice['created_by']) : 'N.A.' ?></td>
                    <td data-label="<?= $lang['actions'] ?? 'Actions' ?>">
                        <a href="view_notice.php?id=<?= $notice['id'] ?>" class="btn small info">👁 <?= $lang['view'] ?? 'View' ?></a>
                        <a href="edit_notice.php?id=<?= $notice['id'] ?>" class="btn small">✏ <?= $lang['edit'] ?? 'Edit' ?></a>
                        <a href="manage_notices.php?delete=<?= $notice['id'] ?>" class="btn small danger"
                           onclick="return confirm('<?= $lang['delete_confirm'] ?? 'Are you sure you want to delete this notice?' ?>');">
                            🗑 <?= $lang['delete'] ?? 'Delete' ?>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align:center; padding:20px;"><?= $lang['no_notices'] ?? 'No notices found.' ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php if($page > 1): ?>
            <a href="?page=<?= $page-1 ?>"><?= $lang['previous'] ?? '« Previous' ?></a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        for($p = $start; $p <= $end; $p++): ?>
            <a href="?page=<?= $p ?>" class="<?= ($p == $page) ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>

        <?php if($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?>"><?= $lang['next'] ?? 'Next »' ?></a>
        <?php endif; ?>
    </div>
</main>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>

</body>
</html>
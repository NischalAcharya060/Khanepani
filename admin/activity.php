<?php
session_start();
include '../config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Language handling
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang'])) {
    $allowed_langs = ['en','np'];
    if (in_array($_GET['lang'], $allowed_langs)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
}
include '../lang/' . $_SESSION['lang'] . '.php';

// Pagination settings
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Fetch recent activities from multiple tables
$activities_query = "
    SELECT 'New notice: ' AS type_desc, title AS title, created_at AS time FROM notices
    UNION ALL
    SELECT CONCAT('New message (', type, '): ', message) AS type_desc, '' AS title, created_at AS time FROM contact_messages
    UNION ALL
    SELECT CONCAT('New gallery item: ', title) AS type_desc, '' AS title, created_at AS time FROM gallery
    UNION ALL
    SELECT CONCAT('New admin: ', username) AS type_desc, '' AS title, created_at AS time FROM admins
    ORDER BY time DESC
    LIMIT $offset, $limit
";

$activities = $conn->query($activities_query);

// Get total activities count for pagination
$total_result = $conn->query("
    SELECT COUNT(*) as total FROM (
        SELECT id FROM notices
        UNION ALL
        SELECT id FROM contact_messages
        UNION ALL
        SELECT id FROM gallery
        UNION ALL
        SELECT id FROM admins
    ) AS all_activities
");
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);

$username = $_SESSION['username'];

include '../config/Nepali_Calendar.php';
$cal = new Nepali_Calendar();

function format_nepali_date($date_str, $cal) {
    if (!$date_str) return '—'; // handle empty dates

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
        return date("M d, Y h:i A", $timestamp);
    }
}

?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['recent_activity'] ?? 'Recent Activity' ?> - <?= $lang['logo'] ?></title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        main {
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
        }

        h2 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 20px;
            font-size: 15px;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
        }

        th, td {
            padding: 12px 10px;
            text-align: left;
        }

        th {
            background: #f5f5f5;
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

        tbody tr:nth-child(odd) {
            background: #fafafa;
        }

        tbody tr:hover {
            background: #f1f7ff;
        }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a {
            display: inline-block;
            padding: 6px 12px;
            margin: 0 4px;
            border-radius: 12px;
            border: 1px solid #ddd;
            color: #007bff;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .pagination a:hover {
            background: #007bff;
            color: #fff;
        }

        .pagination a.active {
            background: #007bff;
            color: #fff;
            border-color: #0056b3;
        }

        /* Responsive table scroll */
        @media (max-width: 600px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            th {
                display: none;
            }
            td {
                padding: 10px;
                border-bottom: 1px solid #eee;
                position: relative;
            }
            td::before {
                content: attr(data-label);
                font-weight: 600;
                display: inline-block;
                width: 100px;
                color: #555;
            }
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main>
    <h2>🕒 <?= $lang['recent_activity'] ?? 'Recent Activity' ?></h2>
    <p class="subtitle"><?= $lang['activity_subtitle'] ?? 'Here are the latest activities in the system.' ?></p>

    <div class="card">
        <table>
            <thead>
            <tr>
                <th><?= $lang['sn'] ?? 'S.N.' ?></th>
                <th><?= $lang['activity_desc'] ?? 'Activity' ?></th>
                <th><?= $lang['time'] ?? 'Time' ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($activities && $activities->num_rows > 0): ?>
                <?php $sn = $offset + 1; ?>
                <?php while ($act = $activities->fetch_assoc()): ?>
                    <tr>
                        <td data-label="<?= $lang['sn'] ?? 'S.N.' ?>"><?= $sn++ ?></td>
                        <td data-label="<?= $lang['activity_desc'] ?? 'Activity' ?>"><?= htmlspecialchars($act['type_desc']) ?></td>
                        <td data-label="<?= $lang['time'] ?? 'Time' ?>">
                            <?= format_nepali_date($act['time'], $cal) ?>
                        </td>                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align:center; padding:20px;"><?= $lang['no_activity'] ?? 'No activities found.' ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php if($page>1): ?>
                <a href="?page=<?= $page-1 ?>"><?= $lang['previous'] ?? '« Previous' ?></a>
            <?php endif; ?>

            <?php
            $start = max(1, $page-2);
            $end = min($total_pages, $page+2);
            for($p=$start;$p<=$end;$p++): ?>
                <a href="?page=<?= $p ?>" class="<?= ($p==$page)?'active':'' ?>"><?= $p ?></a>
            <?php endfor; ?>

            <?php if($page<$total_pages): ?>
                <a href="?page=<?= $page+1 ?>"><?= $lang['next'] ?? 'Next »' ?></a>
            <?php endif; ?>
        </div>

    </div>
</main>

</body>
</html>

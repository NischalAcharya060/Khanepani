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
// Include language file
include '../lang/' . ($_SESSION['lang'] ?? 'en') . '.php';

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Fetch recent activities with author info
$activities_query = "
    SELECT CONCAT('".$lang['notice_label'].": ', IFNULL(title,'—')) AS type_desc, created_at AS time, IFNULL(created_by,'—') AS author, 'notice' AS source_type FROM notices
    UNION ALL
    SELECT CONCAT('".$lang['image'].": ', IFNULL(title,'—')) AS type_desc, created_at AS time, IFNULL(uploaded_by,'—') AS author, 'gallery' AS source_type FROM gallery
    UNION ALL
    SELECT CONCAT('".$lang['add_new_admin'].": ', IFNULL(username,'—')) AS type_desc, created_at AS time, IFNULL(added_by,'—') AS author, 'admin' AS source_type FROM admins
    ORDER BY time DESC
    LIMIT $offset, $limit
";

$activities = $conn->query($activities_query);

// Total activities count for pagination
$total_result = $conn->query("
    SELECT COUNT(*) as total FROM (
        SELECT id FROM notices
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
    if (!$date_str) return '—';

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
        return date("M d, Y h:i A", $timestamp);
    }
}

function getActivityIcon($source_type) {
    switch ($source_type) {
        case 'notice': return 'file-text';
        case 'message': return 'inbox';
        case 'gallery': return 'image';
        case 'admin': return 'user-plus';
        default: return 'activity';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['recent_activity'] ?? 'Recent Activity' ?> - <?= $lang['logo'] ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
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
        }
        body { font-family: 'Roboto', sans-serif; background-color: var(--background-light); color: var(--text-dark); }
        main { padding: 30px; max-width: 1000px; margin: 0 auto; }
        h2 { font-size: 28px; font-weight: 700; margin-bottom: 5px; color: var(--primary-color); display: flex; align-items: center; }
        h2 svg { margin-right: 10px; width: 28px; height: 28px; }
        .subtitle { color: var(--secondary-color); margin-bottom: 30px; font-size: 16px; }
        .card { background: var(--card-background); padding: 30px; border-radius: 12px; box-shadow: var(--shadow-light); overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 15px; border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; font-size: 15px; }
        th { background: var(--primary-color); font-weight: 600; color: #fff; text-transform: uppercase; letter-spacing: 0.5px; font-size: 13px; }
        tbody tr:nth-child(even) { background: #fcfcfc; }
        tbody tr:hover { background: #e9f0ff; }
        .activity-cell { display: flex; align-items: center; gap: 10px; font-weight: 500; }
        .activity-cell svg { color: var(--secondary-color); width: 18px; height: 18px; flex-shrink: 0; }
        .time-cell { color: #555; font-size: 14px; }
        .pagination { text-align: center; margin-top: 30px; }
        .pagination a { display: inline-flex; align-items: center; padding: 8px 15px; margin: 0 4px; border-radius: 6px; border: 1px solid var(--border-color); color: var(--primary-color); text-decoration: none; font-weight: 500; transition: all 0.2s ease; }
        .pagination a.active { background: var(--primary-color); color: #fff; border-color: var(--primary-dark); box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3); }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main>
    <h2><i data-feather="activity"></i> <?= $lang['recent_activity'] ?? 'Recent Activity' ?></h2>
    <p class="subtitle"><?= $lang['activity_subtitle'] ?? 'Here are the latest activities in the system.' ?></p>

    <div class="card">
        <table>
            <thead>
            <tr>
                <th><?= $lang['sn'] ?? 'S.N.' ?></th>
                <th><?= $lang['activity_desc'] ?? 'Activity' ?></th>
                <th><?= $lang['author'] ?? 'Author' ?></th>
                <th><?= $lang['time'] ?? 'Time' ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($activities && $activities->num_rows > 0): ?>
                <?php $sn = $offset + 1; ?>
                <?php while ($act = $activities->fetch_assoc()): ?>
                    <tr>
                        <td><?= $sn++ ?></td>
                        <td class="activity-cell">
                            <i data-feather="<?= getActivityIcon($act['source_type']) ?>"></i>
                            <?= htmlspecialchars($act['type_desc'] ?: '—') ?>
                        </td>
                        <td><?= htmlspecialchars($act['author'] ?: '—') ?></td>
                        <td class="time-cell"><?= $act['time'] ? format_nepali_date($act['time'], $cal) : '—' ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding:30px; color:var(--secondary-color);">
                        <i data-feather="info" style="width:20px; height:20px;"></i>
                        <?= $lang['no_activity'] ?? 'No activities found.' ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

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

<script> feather.replace(); </script>
</body>
</html>

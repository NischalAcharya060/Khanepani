<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];

include '../config/database/db.php';

$allowed_langs = ['en','np'];
$lang_code = in_array($_GET['lang'] ?? '', $allowed_langs) ? $_GET['lang'] : ($_SESSION['lang'] ?? 'en');
$_SESSION['lang'] = $lang_code;

include "../lang/{$lang_code}.php";

function fetchCount($conn, $table) {
    if (!$conn) return 0;
    $res = $conn->query("SELECT COUNT(*) AS c FROM {$table}");
    return $res ? $res->fetch_assoc()['c'] : 0;
}

$total_notices = fetchCount($conn, 'notices');
$total_gallery = fetchCount($conn, 'gallery');
$total_messages = fetchCount($conn, 'contact_messages');
$total_admins = fetchCount($conn, 'admins');
$total_active_admins = $conn->query("SELECT COUNT(*) AS c FROM admins WHERE status='active'")->fetch_assoc()['c'] ?? 0;


function fetchRecent($conn, $table, $columns=['*'], $limit=5){
    if (!$conn) return [];
    $cols = implode(',', $columns);
    $res = $conn->query("SELECT {$cols} FROM {$table} ORDER BY created_at DESC LIMIT {$limit}");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

$recent_notices = fetchRecent($conn, 'notices', ['title','created_at']);
$recent_gallery = fetchRecent($conn, 'gallery', ['title','created_at']);
$recent_admins = fetchRecent($conn, 'admins', ['username','created_at']);
$recent_messages = fetchRecent($conn, 'contact_messages', ['name','created_at']);

$activities = [];
foreach($recent_notices as $n) $activities[] = ['type'=>'Notice','desc'=>$n['title'],'time'=>$n['created_at']];
foreach($recent_gallery as $g) $activities[] = ['type'=>'Gallery','desc'=>$g['title'],'time'=>$g['created_at']];
foreach($recent_admins as $a) $activities[] = ['type'=>'Admin','desc'=>htmlspecialchars($a['username']).' registered','time'=>$a['created_at']];
foreach($recent_messages as $m) $activities[] = ['type'=>'Message','desc'=>'Message from '.htmlspecialchars($m['name']),'time'=>$m['created_at']];

usort($activities, fn($a,$b) => strtotime($b['time']) - strtotime($a['time']));
$activities = array_slice($activities,0,10);

function timeAgo($datetime){
    $time = strtotime($datetime);
    $diff = time() - $time;
    if($diff < 60) return $diff.' second'.($diff!=1?'s':'').' ago';
    $diff = round($diff/60);
    if($diff < 60) return $diff.' minute'.($diff!=1?'s':'').' ago';
    $diff = round($diff/60);
    if($diff < 24) return $diff.' hour'.($diff!=1?'s':'').' ago';
    $diff = round($diff/24);
    if($diff < 7) return $diff.' day'.($diff!=1?'s':'').' ago';
    return date('d M Y', $time);
}

$notices_per_month = [];
$current_year = date('Y');
for($i=1;$i<=12;$i++){
    $res = $conn->query("SELECT COUNT(*) AS c FROM notices WHERE MONTH(created_at)={$i} AND YEAR(created_at)={$current_year}");
    $notices_per_month[$i] = $res ? $res->fetch_assoc()['c'] : 0;
}

$gallery_per_month = [];
for($i=1;$i<=12;$i++){
    $res = $conn->query("SELECT COUNT(*) AS c FROM gallery WHERE MONTH(created_at)={$i} AND YEAR(created_at)={$current_year}");
    $gallery_per_month[$i] = $res ? $res->fetch_assoc()['c'] : 0;
}

$messages_count = ['general'=>0,'complaint'=>0,'suggestion'=>0];
$res = $conn->query("SELECT type, COUNT(*) AS c FROM contact_messages GROUP BY type");
if ($res) {
    while($row = $res->fetch_assoc()){
        if (isset($messages_count[$row['type']])) {
            $messages_count[$row['type']] = $row['c'];
        }
    }
}


include '../config/Nepali_calendar.php';

$cal = new Nepali_Calendar();

$months_labels = [];
for($m=1; $m<=12; $m++){
    $eng_year = date('Y');
    $eng_month = $m;
    $eng_day = 1;

    $nep_date = $cal->eng_to_nep($eng_year, $eng_month, $eng_day);

    if($_SESSION['lang']=='np'){
        $months_labels[] = $nep_date['nmonth'];
    } else {
        $months_labels[] = date('F', mktime(0,0,0,$m,1,$eng_year));
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['dashboard'] ?? 'Dashboard' ?> - <?= $lang['logo'] ?? 'CMS' ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>
        :root{
            --sidebar-mobile-width: 240px;
            --sidebar-expanded-width: 240px;
            --primary-color: #007bff;
            --primary-dark: #0056b3;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --secondary-color: #6c757d;
            --text-dark:#212529;
            --card-background:#ffffff;
            --background-light:#f4f6f9;
            --border-color: #e9ecef;
            --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 8px 20px rgba(0, 0, 0, 0.15);
            --active-admin-color: #6f42c1;
            --chart-gallery-color: #17a2b8;
        }
        body{
            font-family:'Roboto',sans-serif;
            background:var(--background-light);
            color:var(--text-dark);
            margin:0;
            overflow-x: hidden;
        }

        .dashboard-wrapper {
            transition: transform 0.3s ease, padding-left 0.3s ease;
            position: relative;
            z-index: 10;
            padding-left: var(--sidebar-expanded-width);
            min-height: 100vh;
        }

        .sidebar-collapsed-state .dashboard-wrapper {
            padding-left: 70px;
        }

        @media (max-width: 900px) {
            .dashboard-wrapper {
                padding-left: 0;
                width: 100%;
            }
            body.mobile-sidebar-open .dashboard-wrapper {
                transform: translateX(var(--sidebar-mobile-width));
            }
        }
        @media (min-width: 901px) {
            body.mobile-sidebar-open .dashboard-wrapper {
                transform: translateX(0);
            }
        }

        .dashboard{
            padding:30px;
            max-width:1200px;
            margin:auto;
        }
        .dashboard h2{
            font-size:32px;
            font-weight:900;
            margin-bottom:8px;
            color:var(--text-dark);
        }
        .dashboard h2 .username-highlight {
            color:var(--primary-color);
            margin-left: 5px;
        }
        .subtitle{
            color:var(--secondary-color);
            margin-bottom:30px;
            font-size:16px;
        }

        .stats{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
            gap:20px;
            margin-bottom:40px;
        }

        @media (max-width: 1100px) and (min-width: 769px) {
            .stats{
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        .stat-card{
            background:var(--card-background);
            padding:25px;
            border-radius:12px;
            box-shadow:var(--shadow-light);
            display:flex;
            justify-content:space-between;
            align-items:center;
            transition:transform 0.3s ease,box-shadow 0.3s ease;
            position:relative;
            overflow:hidden;
        }
        .stat-card:hover{
            transform:translateY(-5px);
            box-shadow:var(--shadow-hover);
        }
        .stat-card::before{
            content:'';
            position:absolute;
            top:0;bottom:0;left:0;
            width:8px;
            transition: width 0.3s ease;
        }
        .stat-card:hover::before {
            width: 100%;
            opacity: 0.1;
        }

        .stat-card .icon-box{
            background:rgba(0,0,0,0.05);
            padding:12px;
            border-radius:50%;
            height:50px;width:50px;
            display:flex;align-items:center;justify-content:center;
            margin-left:15px;
        }
        .stat-card .icon-box svg{width:24px;height:24px;stroke-width:2;}

        .stat-card.notices::before{background:var(--info-color);}
        .stat-card.notices .icon-box{color:var(--info-color);}

        .stat-card.gallery::before{background:var(--success-color);}
        .stat-card.gallery .icon-box{color:var(--success-color);}

        .stat-card.messages::before{background:var(--warning-color);}
        .stat-card.messages .icon-box{color:var(--warning-color);}

        .stat-card.admins::before{background:var(--danger-color);}
        .stat-card.admins .icon-box{color:var(--danger-color);}

        .stat-card.active-admins::before{background:var(--active-admin-color);}
        .stat-card.active-admins .icon-box{color:var(--active-admin-color);}

        .stat-card h3{font-size:14px;margin-bottom:5px;color:var(--secondary-color);font-weight:600;text-transform:uppercase;}
        .stat-card p{font-size:36px;font-weight:900;margin:0;color:var(--text-dark);}

        .charts-and-activity{
            display:grid;
            grid-template-columns:3fr 1fr;
            gap:30px;
            margin-bottom:40px;
        }

        .charts-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .chart-card{
            background:var(--card-background);
            border-radius:12px;
            padding:25px;
            box-shadow:var(--shadow-light);
            transition:box-shadow 0.3s ease;
        }
        .chart-card:hover{box-shadow:var(--shadow-hover);}
        .chart-card h3{
            font-size:18px;
            font-weight:700;
            margin-bottom:20px;
            border-bottom:1px solid var(--border-color);
            padding-bottom:10px;
            color:var(--primary-color);
            display:flex;
            align-items:center;
        }
        .chart-card h3 svg {
            margin-right: 8px;
        }
        .chart-card canvas{
            height:300px !important;
        }

        .activity-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .activity-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.12);
        }

        .activity-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .activity-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .activity-count {
            font-size: 14px;
            color: #6b7280;
        }

        .activity-feed {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .activity-feed li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        .activity-feed li:last-child {
            border-bottom: none;
        }
        .activity-desc {
            color: #374151;
            word-break: break-word;
            padding-right: 10px;
        }
        .activity-time {
            color: #9ca3af;
            font-size: 13px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .no-activity {
            text-align: center;
            color: #9ca3af;
            padding: 15px 0;
        }

        .view-all-btn-container {
            margin-top: 15px;
            text-align: right;
        }
        .view-all-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            font-weight: 500;
            color: #4C7AFF;
            text-decoration: none;
            transition: color 0.2s;
        }
        .view-all-btn:hover {
            color: #3b61d3;
        }


        .quick-actions-card {
            background: var(--card-background);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow-light);
        }
        .quick-actions-card h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--primary-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }
        .action-list{
            list-style: none; padding: 0; margin: 0;
            display:flex; flex-wrap: wrap; gap: 15px;
        }
        .action-list a{
            display:flex;
            align-items:center;
            padding:12px;
            margin-bottom: 8px;
            background:var(--background-light);
            border-radius:8px;
            text-decoration:none;
            color:var(--text-dark);
            font-weight:500;
            transition:background 0.3s,transform 0.1s;
            border: 1px solid var(--border-color);
            flex-basis: calc(33.33% - 15px);
            min-width: 200px;
        }
        .action-list a:hover{
            background:rgba(0, 123, 255, 0.1);
            color:var(--primary-color);
            transform:translateX(5px);
            border-color: var(--primary-color);
        }
        .action-list a svg{margin-right:12px;width:18px;height:18px;}


        @media (max-width: 900px) {
            .charts-and-activity{
                grid-template-columns:1fr;
            }
            .charts-row-2 {
                grid-template-columns: 1fr;
            }
            .dashboard{
                max-width: 95%;
            }
        }
        @media (max-width: 768px) {
            .dashboard{padding:20px;}

            .stats{
                grid-template-columns:repeat(auto-fit,minmax(min(100%, 150px),1fr));
                gap: 15px;
            }

            .activity-feed li {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
            .activity-desc {
                font-weight: 500;
            }
            .activity-time {
                font-size: 12px;
                margin-top: 2px;
            }
            .activity-feed li span:first-child {
                max-width: 100%;
            }

            .action-list a {
                flex-basis: 100%;
                min-width: unset;
            }
        }
        @media (max-width: 480px) {
            .dashboard h2{font-size:24px;}
            .subtitle{font-size:14px;}
            .stats{
                grid-template-columns: 1fr;
            }
        }

        #mobileOptimizationBanner {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 500px;
            background-color: var(--warning-color);
            color: var(--text-dark);
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1000;
            font-size: 14px;
        }

        #mobileOptimizationBanner p {
            margin: 0;
            line-height: 1.4;
            padding-right: 15px;
        }

        #dismissBannerBtn {
            background: none;
            border: none;
            font-size: 20px;
            font-weight: bold;
            color: var(--text-dark);
            cursor: pointer;
            padding: 0 5px;
            line-height: 1;
            transition: color 0.2s;
        }

        #dismissBannerBtn:hover {
            color: var(--danger-color);
        }

        @media (min-width: 901px) {
            #mobileOptimizationBanner {
                display: none !important;
            }
        }
    </style>
</head>
<body <?php echo 'class="sidebar-expanded-state"' ?>>

<?php include '../components/admin_header.php'; ?>

<div id="mobileOptimizationBanner" style="display:none;">
    <p>
        <strong><?= $lang['mobile_warning_title'] ?? 'Mobile View Alert' ?>:</strong>
        <?= $lang['mobile_warning_text'] ?? 'For the best experience, especially with detailed charts and tables, please use this dashboard on a desktop or laptop device.' ?>
    </p>
    <button id="dismissBannerBtn" aria-label="<?= $lang['dismiss'] ?? 'Dismiss' ?>">&times;</button>
</div>

<div class="dashboard-wrapper">
    <div class="dashboard">
        <h2><?= $lang['welcome_back'] ?? 'Welcome back' ?>, <span class="username-highlight"><?= htmlspecialchars($username) ?></span> 👋</h2>
        <p class="subtitle"><?= $lang['dashboard_subtitle'] ?? 'Here are your system stats and insights.' ?></p>

        <div class="stats">
            <div class="stat-card notices">
                <div><h3><?= $lang['notices'] ?></h3><p><?= $total_notices ?></p></div>
                <div class="icon-box"><i data-feather="bell"></i></div>
            </div>
            <div class="stat-card gallery">
                <div><h3><?= $lang['gallery'] ?? 'Gallery Items' ?></h3><p><?= $total_gallery ?></p></div>
                <div class="icon-box"><i data-feather="image"></i></div>
            </div>
            <div class="stat-card messages">
                <div><h3><?= $lang['messages'] ?></h3><p><?= $total_messages ?></p></div>
                <div class="icon-box"><i data-feather="inbox"></i></div>
            </div>
            <div class="stat-card admins">
                <div><h3><?= $lang['admins'] ?? 'Admins' ?></h3><p><?= $total_admins ?></p></div>
                <div class="icon-box"><i data-feather="users"></i></div>
            </div>
            <div class="stat-card active-admins">
                <div><h3><?= $lang['active_admins'] ?? 'Active Admins' ?></h3><p><?= $total_active_admins ?></p></div>
                <div class="icon-box"><i data-feather="user-check"></i></div>
            </div>
        </div>

        <div class="charts-and-activity">
            <div class="chart-card">
                <h3><i data-feather="bar-chart-2"></i> <?= $lang['notices'] ?> (<?= $lang['per_month'] ?? 'Per Month' ?>)</h3>
                <canvas id="noticesChart"></canvas>
            </div>

            <div class="activity-card">
                <div class="activity-header">
                    <h3><i data-feather="clock"></i> <?= $lang['recent_activity'] ?></h3>
                    <span class="activity-count"><?= count($activities) ?> <?= $lang['activities'] ?? 'activities' ?></span>
                </div>

                <ul class="activity-feed">
                    <?php foreach(array_slice($activities, 0, 5) as $act): ?>
                        <li>
                            <div class="activity-desc"><?= htmlspecialchars($act['desc']) ?></div>
                            <div class="activity-time"><?= timeAgo($act['time']) ?></div>
                        </li>
                    <?php endforeach; ?>
                    <?php if(count($activities) === 0): ?>
                        <li class="no-activity"><?= $lang['no_activity'] ?? 'No recent activity.' ?></li>
                    <?php endif; ?>
                </ul>

                <div class="view-all-btn-container">
                    <a href="activity.php" class="view-all-btn">
                        <?= $lang['view_all'] ?> <i data-feather="arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="charts-row-2">
            <div class="chart-card">
                <h3><i data-feather="trending-up"></i> <?= $lang['gallery'] ?> (<?= $lang['monthly_uploads'] ?? 'Monthly Uploads' ?>)</h3>
                <canvas id="galleryChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><i data-feather="pie-chart"></i> <?= $lang['messages'] ?> (<?= $lang['by_type'] ?? 'By Type' ?>)</h3>
                <canvas id="messagesChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script>
    feather.replace();

    const noticesChart = new Chart(document.getElementById('noticesChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($months_labels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: '<?= $lang['notices'] ?>',
                data: [<?= implode(',', $notices_per_month) ?>],
                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                borderColor: 'var(--primary-color)',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            aspectRatio: window.innerWidth <= 900 ? 1.5 : 2.5,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Roboto' } } },
                y: { beginAtZero: true, grid: { color: 'var(--border-color)' } }
            }
        }
    });

    const galleryChart = new Chart(document.getElementById('galleryChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($months_labels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: '<?= $lang['gallery_uploads'] ?? 'Gallery Uploads' ?>',
                data: [<?= implode(',', $gallery_per_month) ?>],
                backgroundColor: 'rgba(23, 162, 184, 0.2)',
                borderColor: 'var(--chart-gallery-color)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'var(--chart-gallery-color)',
                pointBorderColor: '#fff',
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            aspectRatio: window.innerWidth <= 900 ? 1 : 1.5,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Roboto' } } },
                y: { beginAtZero: true, grid: { color: 'var(--border-color)' } }
            }
        }
    });

    const messagesChart = new Chart(document.getElementById('messagesChart'), {
        type: 'doughnut',
        data: {
            labels: ['<?= $lang['general'] ?? 'General' ?>','<?= $lang['complaint'] ?? 'Complaint' ?>','<?= $lang['suggestion'] ?? 'Suggestion' ?>'],
            datasets: [{
                data: [<?= $messages_count['general'] ?>, <?= $messages_count['complaint'] ?>, <?= $messages_count['suggestion'] ?>],
                backgroundColor: ['var(--success-color)','var(--danger-color)','var(--warning-color)'],
                hoverBackgroundColor: ['#157347','#bb2124','#d39e00'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            aspectRatio: window.innerWidth <= 900 ? 1 : 1.5,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 15, font: { family: 'Roboto' } } },
                tooltip: { callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) { label += ': '; }
                            if (context.parsed !== null) {
                                label += context.parsed;
                            }
                            return label;
                        }
                    } }
            }
        }
    });
</script>

<script>
    const banner = document.getElementById('mobileOptimizationBanner');
    const dismissBtn = document.getElementById('dismissBannerBtn');
    const mobileBreakpoint = 900;

    const isDismissed = localStorage.getItem('dashboardMobileBannerDismissed') === 'true';

    function checkScreenSize() {
        if (window.innerWidth <= mobileBreakpoint && !isDismissed) {
            banner.style.display = 'flex';
        } else {
            banner.style.display = 'none';
        }
    }

    dismissBtn.addEventListener('click', () => {
        localStorage.setItem('dashboardMobileBannerDismissed', 'true');
        banner.style.display = 'none';
    });

    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
</script>

</body>
</html>
<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];

// Include DB connection (Assuming this path is correct)
include '../config/db.php';

// --- Language handling ---
$allowed_langs = ['en','np'];
$lang_code = in_array($_GET['lang'] ?? '', $allowed_langs) ? $_GET['lang'] : ($_SESSION['lang'] ?? 'en');
$_SESSION['lang'] = $lang_code;

// Load language file (Assuming this path is correct)
include "../lang/{$lang_code}.php";

// --- Fetch stats safely ---
function fetchCount($conn, $table) {
    // Check if connection is valid before querying
    if (!$conn) return 0;
    $res = $conn->query("SELECT COUNT(*) AS c FROM {$table}");
    return $res ? $res->fetch_assoc()['c'] : 0;
}

$total_notices = fetchCount($conn, 'notices');
$total_gallery = fetchCount($conn, 'gallery');
$total_messages = fetchCount($conn, 'contact_messages');
$total_admins = fetchCount($conn, 'admins');
$total_active_admins = $conn->query("SELECT COUNT(*) AS c FROM admins WHERE status='active'")->fetch_assoc()['c'] ?? 0;


// --- Fetch recent records ---
function fetchRecent($conn, $table, $columns=['*'], $limit=5){
    if (!$conn) return [];
    $cols = implode(',', $columns);
    // Note: Assuming 'created_at' column exists for sorting
    $res = $conn->query("SELECT {$cols} FROM {$table} ORDER BY created_at DESC LIMIT {$limit}");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

$recent_notices = fetchRecent($conn, 'notices', ['title','created_at']);
$recent_gallery = fetchRecent($conn, 'gallery', ['title','created_at']);
$recent_admins = fetchRecent($conn, 'admins', ['username','created_at']);
$recent_messages = fetchRecent($conn, 'contact_messages', ['name','created_at']);

// --- Merge activities ---
$activities = [];
foreach($recent_notices as $n) $activities[] = ['type'=>'Notice','desc'=>$n['title'],'time'=>$n['created_at']];
foreach($recent_gallery as $g) $activities[] = ['type'=>'Gallery','desc'=>$g['title'],'time'=>$g['created_at']];
foreach($recent_admins as $a) $activities[] = ['type'=>'Admin','desc'=>htmlspecialchars($a['username']).' registered','time'=>$a['created_at']];
foreach($recent_messages as $m) $activities[] = ['type'=>'Message','desc'=>'Message from '.htmlspecialchars($m['name']),'time'=>$m['created_at']];

// Sort by latest
usort($activities, fn($a,$b) => strtotime($b['time']) - strtotime($a['time']));
$activities = array_slice($activities,0,10);

// --- Helper: Time ago ---
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

// --- Notices per month (Bar Chart Data) ---
$notices_per_month = [];
$current_year = date('Y');
for($i=1;$i<=12;$i++){
    $res = $conn->query("SELECT COUNT(*) AS c FROM notices WHERE MONTH(created_at)={$i} AND YEAR(created_at)={$current_year}");
    $notices_per_month[$i] = $res ? $res->fetch_assoc()['c'] : 0;
}

// --- NEW CHART DATA: Gallery Uploads per month (Line Chart Data) ---
$gallery_per_month = [];
for($i=1;$i<=12;$i++){
    $res = $conn->query("SELECT COUNT(*) AS c FROM gallery WHERE MONTH(created_at)={$i} AND YEAR(created_at)={$current_year}");
    $gallery_per_month[$i] = $res ? $res->fetch_assoc()['c'] : 0;
}

// --- Messages count by type (Doughnut Chart Data) ---
$messages_count = ['general'=>0,'complaint'=>0,'suggestion'=>0];
$res = $conn->query("SELECT type, COUNT(*) AS c FROM contact_messages GROUP BY type");
if ($res) {
    while($row = $res->fetch_assoc()){
        // Ensure the type is one of the expected keys
        if (isset($messages_count[$row['type']])) {
            $messages_count[$row['type']] = $row['c'];
        }
    }
}


// Include Nepali Date (Assuming this path is correct)
include '../config/Nepali_Calendar.php';

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
            /* NEW COLORS */
            --active-admin-color: #6f42c1; /* Purple */
            --chart-gallery-color: #17a2b8; /* Info/Teal */
        }
        body{
            font-family:'Roboto',sans-serif;
            background:var(--background-light);
            color:var(--text-dark);
            margin:0;
            overflow-x: hidden;
        }

        /* --- SLIDING EFFECT FIX FOR MOBILE --- */
        .dashboard-wrapper {
            transition: transform 0.3s ease, padding-left 0.3s ease;
            position: relative;
            z-index: 10;
            padding-left: var(--sidebar-expanded-width); /* Default desktop offset */
            min-height: 100vh;
        }

        /* Desktop: Collapsed State */
        .sidebar-collapsed-state .dashboard-wrapper {
            padding-left: 70px; /* Offset for collapsed sidebar */
        }

        /* Mobile View (max-width: 900px) */
        @media (max-width: 900px) {
            .dashboard-wrapper {
                padding-left: 0; /* Important: Clear desktop offset on mobile */
                width: 100%; /* Ensure it spans full width */
            }
            body.mobile-sidebar-open .dashboard-wrapper {
                /* Shifts the entire content wrapper to the right */
                transform: translateX(var(--sidebar-mobile-width));
            }
        }
        /* Desktop View (min-width: 901px) */
        @media (min-width: 901px) {
            body.mobile-sidebar-open .dashboard-wrapper {
                /* Prevents accidental transform on desktop */
                transform: translateX(0);
            }
        }
        /* --- END SLIDING FIX --- */

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

        /* --- Stat Cards --- */
        .stats{
            display:grid;
            /* Adjust grid to fit 5 items elegantly, responsive minimum 200px */
            grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
            gap:20px;
            margin-bottom:40px;
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

        /* New Stat Card */
        .stat-card.active-admins::before{background:var(--active-admin-color);}
        .stat-card.active-admins .icon-box{color:var(--active-admin-color);}

        .stat-card h3{font-size:14px;margin-bottom:5px;color:var(--secondary-color);font-weight:600;text-transform:uppercase;}
        .stat-card p{font-size:36px;font-weight:900;margin:0;color:var(--text-dark);}

        /* --- Layout Grid (Charts & Activity) --- */
        .charts-and-activity{
            display:grid;
            grid-template-columns:3fr 1fr;
            gap:30px;
            margin-bottom:40px;
        }

        /* New Chart Row (Gallery & Messages) */
        .charts-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        /* --- Chart Card --- */
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

        /* --- Activity Feed --- */
        .activity-card{
            background:var(--card-background);
            border-radius:12px;
            padding:25px;
            box-shadow:var(--shadow-light);
            display:flex;
            flex-direction:column;
        }
        .activity-card h3{
            font-size:18px;
            font-weight:700;
            margin-bottom:20px;
            color:var(--primary-color);
            display:flex;
            align-items:center;
            border-bottom:none;
        }
        .activity-card h3 svg {
            margin-right: 8px;
        }

        .activity-feed{list-style:none;padding:0;margin:0; flex-grow: 1;}
        .activity-feed li{
            padding:12px 0;
            border-bottom:1px dashed var(--border-color);
            font-size:15px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            transition: background 0.1s;
        }
        .activity-feed li:last-child{border-bottom:none;}
        .activity-feed li:hover{
            background: rgba(0, 123, 255, 0.05);
            padding-left: 5px;
        }
        .activity-feed li span:first-child{
            font-weight:500;
            color:var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 10px;
        }
        .activity-feed li span:last-child{
            font-size:13px;
            color:var(--secondary-color);
            flex-shrink: 0;
        }

        .view-all-btn-container {
            text-align:center;
            margin-top:20px;
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
        }
        .view-all-btn{
            display:inline-flex;
            align-items:center;
            padding:10px 20px;
            background:var(--primary-color);
            color:#fff;
            border-radius:8px;
            text-decoration:none;
            font-weight:600;
            transition:background 0.3s;
        }
        .view-all-btn:hover{
            background:var(--primary-dark);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.4);
        }
        .view-all-btn svg {
            width: 16px;
            height: 16px;
            margin-left: 8px;
        }

        /* --- Quick Actions Card styling --- */
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
            /* Flex layout for multi-column quick actions */
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
            flex-basis: calc(33.33% - 15px); /* Three items per row */
            min-width: 200px;
        }
        .action-list a:hover{
            background:rgba(0, 123, 255, 0.1);
            color:var(--primary-color);
            transform:translateX(5px);
            border-color: var(--primary-color);
        }
        .action-list a svg{margin-right:12px;width:18px;height:18px;}


        /* Responsive */
        @media (max-width: 900px) {
            .charts-and-activity{grid-template-columns:1fr;}
            .charts-row-2 {grid-template-columns: 1fr;}
            .dashboard{max-width: 95%;}
            .action-list a {
                flex-basis: 100%; /* Single column on small screens */
            }
        }
        @media (max-width: 768px) {
            .dashboard{padding:20px;}
            .stats{grid-template-columns:1fr;}
            .stat-card p{font-size:32px;}
            .activity-feed li span:first-child {
                max-width: 60%;
            }
        }
    </style>
</head>
<body <?php echo 'class="sidebar-expanded-state"' ?>>

<?php include '../components/admin_header.php'; ?>

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
                <h3><i data-feather="clock"></i> <?= $lang['recent_activity'] ?></h3>
                <ul class="activity-feed">
                    <?php foreach(array_slice($activities,0,5) as $act): ?>
                        <li>
                            <span><?= htmlspecialchars($act['desc']) ?></span>
                            <span><?= timeAgo($act['time']) ?></span>
                        </li>
                    <?php endforeach; ?>
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

    // --- CHART 1: Notices Per Month (Bar Chart) ---
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
            aspectRatio: 2.5,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Roboto' } } },
                y: { beginAtZero: true, grid: { color: 'var(--border-color)' } }
            }
        }
    });

    // --- CHART 2: Gallery Uploads Per Month (Line Chart) ---
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
            aspectRatio: 1.5,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Roboto' } } },
                y: { beginAtZero: true, grid: { color: 'var(--border-color)' } }
            }
        }
    });

    // --- CHART 3: Messages By Type (Doughnut Chart) ---
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
            aspectRatio: 1.5,
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

</body>
</html>
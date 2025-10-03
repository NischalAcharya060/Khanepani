<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];

// Include DB connection
include '../config/db.php';

// --- Language handling ---
$allowed_langs = ['en','np'];
$lang_code = in_array($_GET['lang'] ?? '', $allowed_langs) ? $_GET['lang'] : ($_SESSION['lang'] ?? 'en');
$_SESSION['lang'] = $lang_code;

// Load language file
include "../lang/{$lang_code}.php";

// --- Fetch stats safely ---
function fetchCount($conn, $table) {
    $res = $conn->query("SELECT COUNT(*) AS c FROM {$table}");
    return $res ? $res->fetch_assoc()['c'] : 0;
}

$total_notices = fetchCount($conn, 'notices');
$total_gallery = fetchCount($conn, 'gallery');
$total_messages = fetchCount($conn, 'contact_messages');
$total_admins = fetchCount($conn, 'admins');

// --- Fetch recent records ---
function fetchRecent($conn, $table, $columns=['*'], $limit=5){
    $cols = implode(',', $columns);
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
foreach($recent_admins as $a) $activities[] = ['type'=>'Admin','desc'=>$a['username'].' registered','time'=>$a['created_at']];
foreach($recent_messages as $m) $activities[] = ['type'=>'Message','desc'=>'Message from '.$m['name'],'time'=>$m['created_at']];

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

// --- Notices per month ---
$notices_per_month = [];
for($i=1;$i<=12;$i++){
    $res = $conn->query("SELECT COUNT(*) AS c FROM notices WHERE MONTH(created_at)={$i}");
    $notices_per_month[$i] = $res ? $res->fetch_assoc()['c'] : 0;
}

// --- Messages count by type (single query) ---
$messages_count = ['general'=>0,'complaint'=>0,'suggestion'=>0];
$res = $conn->query("SELECT type, COUNT(*) AS c FROM contact_messages GROUP BY type");
while($row = $res->fetch_assoc()){
    $messages_count[$row['type']] = $row['c'];
}
?>
<?php
// Include Nepali Date
include '../config/Nepali_Calendar.php';

$cal = new Nepali_Calendar();
?>
<?php
$months_labels = [];
for($m=1; $m<=12; $m++){
    $eng_year = date('Y');
    $eng_month = $m;
    $eng_day = 1;

    $nep_date = $cal->eng_to_nep($eng_year, $eng_month, $eng_day);

    if($_SESSION['lang']=='np'){
        $months_labels[] = $nep_date['nmonth']; // Nepali month name
    } else {
        $months_labels[] = date('F', mktime(0,0,0,$m,1,$eng_year)); // English month name
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['dashboard'] ?> - <?= $lang['logo'] ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root{
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
            --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        body{
            font-family:'Roboto',sans-serif;
            background:var(--background-light);
            color:var(--text-dark);
            margin:0;
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

        /* --- Stat Cards --- */
        .stats{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
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

        .stat-card h3{font-size:14px;margin-bottom:5px;color:var(--secondary-color);font-weight:600;text-transform:uppercase;}
        .stat-card p{font-size:36px;font-weight:900;margin:0;color:var(--text-dark);}

        /* --- Layout Grid (Charts & Activity) --- */
        .charts-and-activity{
            display:grid;
            grid-template-columns:3fr 1fr;
            gap:30px;
            margin-bottom:40px;
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

        /* Responsive */
        @media (max-width: 992px) {
            .charts-and-activity{grid-template-columns:1fr;}
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
<body>

<?php include '../components/admin_header.php'; ?>

<div class="dashboard">
    <h2><?= $lang['welcome_back'] ?? 'Welcome back' ?>, <span class="username-highlight"><?= htmlspecialchars($username) ?></span> 👋</h2>
    <p class="subtitle"><?= $lang['dashboard_subtitle'] ?? 'Here are your system stats and insights.' ?></p>

    <div class="stats">
        <div class="stat-card notices">
            <div><h3><?= $lang['notices'] ?></h3><p><?= $total_notices ?></p></div>
            <div class="icon-box"><i data-feather="bell"></i></div>
        </div>
        <div class="stat-card gallery">
            <div><h3><?= $lang['gallery'] ?? 'Gallery' ?></h3><p><?= $total_gallery ?></p></div>
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

    <div class="charts-and-activity" style="grid-template-columns: 2fr 1fr; max-width: 100%;">
        <div class="chart-card" style="min-height: 350px;">
            <h3><i data-feather="pie-chart"></i> <?= $lang['messages'] ?> (<?= $lang['by_type'] ?? 'By Type' ?>)</h3>
            <canvas id="messagesChart" style="max-height: 300px;"></canvas>
        </div>
        <div class="chart-card" style="min-height: 350px;">
            <h3><i data-feather="zap"></i> <?= $lang['quick_actions'] ?? 'Quick Actions' ?></h3>
            <div style="padding-top: 10px; font-size: 15px; color: var(--secondary-color);">
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;"><a href="add_notice.php" style="text-decoration: none; color: var(--primary-color); font-weight: 500;"><i data-feather="plus-circle" style="width: 16px; height: 16px; margin-right: 5px;"></i> <?= $lang['add_notice'] ?? 'Add Notice' ?></a></li>
                    <li style="margin-bottom: 10px;"><a href="manage_gallery.php" style="text-decoration: none; color: var(--primary-color); font-weight: 500;"><i data-feather="image" style="width: 16px; height: 16px; margin-right: 5px;"></i> <?= $lang['manage_gallery'] ?? 'Manage Gallery' ?></a></li>
                    <li style="margin-bottom: 10px;"><a href="messages.php" style="text-decoration: none; color: var(--primary-color); font-weight: 500;"><i data-feather="mail" style="width: 16px; height: 16px; margin-right: 5px;"></i> <?= $lang['view_messages'] ?? 'View Messages' ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    feather.replace();

    // Notices Chart
    new Chart(document.getElementById('noticesChart'), {
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
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Roboto' } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'var(--border-color)' }
                }
            }
        }
    });

    // Messages Chart
    new Chart(document.getElementById('messagesChart'), {
        type: 'doughnut',
        data: {
            labels: ['General','Complaint','Suggestion'],
            datasets: [{
                data: [
                    <?= $messages_count['general'] ?>,
                    <?= $messages_count['complaint'] ?>,
                    <?= $messages_count['suggestion'] ?>
                ],
                backgroundColor: [
                    'var(--success-color)',
                    'var(--danger-color)',
                    'var(--warning-color)'
                ],
                hoverBackgroundColor: [
                    '#157347',
                    '#bb2124',
                    '#d39e00'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            aspectRatio: 1.2,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15,
                        font: { family: 'Roboto' }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                label += context.parsed;
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>
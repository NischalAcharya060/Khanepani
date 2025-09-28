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
<!DOCTYPE html>
<html lang="<?= $lang_code ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['dashboard'] ?> - <?= $lang['logo'] ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --primary:#4e73df;--success:#1cc88a;--warning:#f6c23e;--danger:#e74a3b;--info:#36b9cc;
            --text-dark:#212529;--text-light:#f8f9fa;
            --card-bg:rgba(255,255,255,0.85);--background:#f4f6f9;
        }
        body{font-family:'Inter',sans-serif;background:var(--background);color:var(--text-dark);margin:0;transition:background 0.3s,color 0.3s;}
        .dashboard{padding:30px;max-width:1000px;margin:auto;}
        .dashboard h2{font-size:26px;font-weight:700;margin-bottom:8px;background:linear-gradient(90deg,var(--primary),var(--info));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
        .subtitle{color:#6c757d;margin-bottom:30px;}
        .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;margin-bottom:40px;}
        .stat-card{backdrop-filter:blur(10px);background:var(--card-bg);padding:22px;border-radius:16px;box-shadow:0 6px 20px rgba(0,0,0,0.08);transition:transform 0.25s ease,box-shadow 0.25s ease;}
        .stat-card:hover{transform:translateY(-6px);box-shadow:0 10px 28px rgba(0,0,0,0.15);}
        .stat-card h3{font-size:15px;margin-bottom:8px;color:#888;font-weight:600;}
        .stat-card p{font-size:32px;font-weight:700;margin:0;}
        .notices{border-left:6px solid var(--info);}
        .gallery{border-left:6px solid var(--success);}
        .messages{border-left:6px solid var(--warning);}
        .admins{border-left:6px solid var(--danger);}
        .charts{display:grid;grid-template-columns:2fr 1fr;gap:28px;margin-bottom:40px;}
        .chart-card{background:var(--card-bg);border-radius:16px;padding:22px;box-shadow:0 4px 14px rgba(0,0,0,0.08);transition:transform 0.2s ease;}
        .chart-card:hover{transform:translateY(-4px);}
        .chart-card h3{font-size:17px;font-weight:600;margin-bottom:18px;border-bottom:1px solid rgba(0,0,0,0.05);padding-bottom:8px;color:var(--primary);}
        .chart-card canvas{height:300px !important;}
        .activity-card{background:var(--card-bg);border-radius:16px;padding:20px;box-shadow:0 4px 14px rgba(0,0,0,0.08);}
        .activity-card h3{font-size:17px;margin-bottom:15px;font-weight:600;color:var(--primary);}
        .activity-feed{list-style:none;padding:0;margin:0;}
        .activity-feed li{padding:10px 0;border-bottom:1px dashed rgba(0,0,0,0.08);font-size:15px;}
        .activity-feed li span{float:right;font-size:12px;color:#999;}
        .view-all-btn{display:inline-block;padding:8px 16px;background:var(--primary);color:#fff;border-radius:12px;text-decoration:none;font-weight:600;transition:background 0.3s;}
        .view-all-btn:hover{background:#2e59d9;}
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="dashboard">
    <h2><?= $lang['welcome_back'] ?? 'Welcome back' ?>, <?= htmlspecialchars($username) ?> 👋</h2>
    <p class="subtitle"><?= $lang['dashboard_subtitle'] ?? 'Here are your system stats and insights.' ?></p>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card notices"><h3>📢 <?= $lang['notices'] ?></h3><p><?= $total_notices ?></p></div>
        <div class="stat-card gallery"><h3>🖼 <?= $lang['gallery'] ?? 'Gallery' ?></h3><p><?= $total_gallery ?></p></div>
        <div class="stat-card messages"><h3>📬 <?= $lang['messages'] ?></h3><p><?= $total_messages ?></p></div>
        <div class="stat-card admins"><h3>👤 <?= $lang['admins'] ?? 'Admins' ?></h3><p><?= $total_admins ?></p></div>
    </div>

    <!-- Charts -->
    <div class="charts">
        <div class="chart-card"><h3>📈 <?= $lang['notices'] ?> (<?= $lang['per_month'] ?? 'Per Month' ?>)</h3><canvas id="noticesChart"></canvas></div>
        <div class="chart-card"><h3>📊 <?= $lang['messages'] ?> (<?= $lang['by_type'] ?? 'By Type' ?>)</h3><canvas id="messagesChart"></canvas></div>
    </div>

    <!-- Activity Feed -->
    <div class="activity-card">
        <h3>🕒 <?= $lang['recent_activity'] ?></h3>
        <ul class="activity-feed">
            <?php foreach(array_slice($activities,0,5) as $act): ?>
                <li><?= htmlspecialchars($act['desc']) ?><span><?= timeAgo($act['time']) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <div style="text-align:center;margin-top:10px;">
            <a href="../admin/activity.php" class="view-all-btn"><?= $lang['view_all'] ?></a>
        </div>
    </div>
</div>

<script>
    // Notices Chart
    new Chart(document.getElementById('noticesChart'),{
        type:'bar',
        data:{
            labels:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            datasets:[{label:'<?= $lang['notices'] ?>',data:[
                    <?= implode(',', $notices_per_month) ?>
                ],backgroundColor:'#4e73df',borderRadius:6}]
        },
        options:{responsive:true,plugins:{legend:{display:false}}}
    });

    // Messages Chart
    new Chart(document.getElementById('messagesChart'),{
        type:'doughnut',
        data:{
            labels:['General','Complaint','Suggestion'],
            datasets:[{data:[
                    <?= $messages_count['general'] ?>,<?= $messages_count['complaint'] ?>,<?= $messages_count['suggestion'] ?>
                ],backgroundColor:['#1cc88a','#e74a3b','#f6c23e'],borderWidth:2}]
        },
        options:{responsive:true,plugins:{legend:{position:'bottom'}}}
    });
</script>

</body>
</html>

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

// Get today's counts
$today = date('Y-m-d');
$today_notices = fetchCount($conn, "notices WHERE DATE(created_at) = '{$today}'");
$today_gallery = fetchCount($conn, "gallery WHERE DATE(created_at) = '{$today}'");
$today_messages = fetchCount($conn, "contact_messages WHERE DATE(created_at) = '{$today}'");

function fetchRecent($conn, $table, $columns=['*'], $limit=5){
    if (!$conn) return [];
    $cols = implode(',', $columns);
    $res = $conn->query("SELECT {$cols} FROM {$table} ORDER BY created_at DESC LIMIT {$limit}");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

$recent_notices = fetchRecent($conn, 'notices', ['id', 'title','created_at']);
$recent_gallery = fetchRecent($conn, 'gallery', ['id', 'title','created_at']);
$recent_admins = fetchRecent($conn, 'admins', ['id', 'username','created_at']);
$recent_messages = fetchRecent($conn, 'contact_messages', ['id', 'name','created_at']);

$activities = [];
foreach($recent_notices as $n) $activities[] = ['type'=>'Notice','desc'=>$n['title'],'time'=>$n['created_at'], 'link'=>"notices.php?edit={$n['id']}"];
foreach($recent_gallery as $g) $activities[] = ['type'=>'Gallery','desc'=>$g['title'],'time'=>$g['created_at'], 'link'=>"gallery.php?edit={$g['id']}"];
foreach($recent_admins as $a) $activities[] = ['type'=>'Admin','desc'=>htmlspecialchars($a['username']).' registered','time'=>$a['created_at'], 'link'=>"admins.php?edit={$a['id']}"];
foreach($recent_messages as $m) $activities[] = ['type'=>'Message','desc'=>'Message from '.htmlspecialchars($m['name']),'time'=>$m['created_at'], 'link'=>"messages.php?view={$m['id']}"];

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

// Fetch system info
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$php_version = phpversion();
$mysql_version = $conn ? $conn->get_server_info() : 'Unknown';
$upload_max_filesize = ini_get('upload_max_filesize');
$memory_limit = ini_get('memory_limit');

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['dashboard'] ?? 'Dashboard' ?> - <?= $lang['logo'] ?? 'CMS' ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root{
            --sidebar-mobile-width: 240px;
            --sidebar-expanded-width: 240px;
            --primary-color: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a0ca3;
            --success-color: #4cc9a7;
            --warning-color: #f9c74f;
            --danger-color: #f94144;
            --info-color: #577590;
            --secondary-color: #6c757d;
            --text-dark:#212529;
            --text-light:#6c757d;
            --card-background:#ffffff;
            --background-light:#f8f9fa;
            --background-gradient: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            --border-color: #e9ecef;
            --shadow-light: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-medium: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --active-admin-color: #7209b7;
            --chart-gallery-color: #4895ef;
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        body{
            font-family: 'Inter', sans-serif;
            background: var(--background-gradient);
            color: var(--text-dark);
            margin: 0;
            overflow-x: hidden;
            line-height: 1.6;
        }

        .dashboard-wrapper {
            transition: var(--transition);
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
            padding: 30px;
            max-width: 1400px;
            margin: auto;
        }
        .dashboard h2{
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--text-dark);
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        /*.dashboard h2 .username-highlight {*/
        /*    color: var(--primary-color);*/
        /*    margin-left: 5px;*/
        /*    -webkit-text-fill-color: var(--primary-color);*/
        /*}*/
        .subtitle{
            color: var(--text-light);
            margin-bottom: 30px;
            font-size: 16px;
            font-weight: 500;
        }

        .stats{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        @media (max-width: 1100px) and (min-width: 769px) {
            .stats{
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        .stat-card{
            background: var(--card-background);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
        .stat-card:hover{
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }
        .stat-card::before{
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s ease;
        }
        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card .icon-box{
            background: rgba(0,0,0,0.05);
            padding: 12px;
            border-radius: 50%;
            height: 60px;
            width: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            transition: var(--transition);
        }
        .stat-card:hover .icon-box {
            transform: scale(1.1);
        }
        .stat-card .icon-box svg{width: 24px; height: 24px; stroke-width: 2;}

        .stat-card.notices::before{background: var(--info-color);}
        .stat-card.notices .icon-box{color: var(--info-color);}

        .stat-card.gallery::before{background: var(--success-color);}
        .stat-card.gallery .icon-box{color: var(--success-color);}

        .stat-card.messages::before{background: var(--warning-color);}
        .stat-card.messages .icon-box{color: var(--warning-color);}

        .stat-card.admins::before{background: var(--danger-color);}
        .stat-card.admins .icon-box{color: var(--danger-color);}

        .stat-card.active-admins::before{background: var(--active-admin-color);}
        .stat-card.active-admins .icon-box{color: var(--active-admin-color);}

        .stat-card h3{font-size: 14px; margin-bottom: 5px; color: var(--text-light); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;}
        .stat-card p{font-size: 36px; font-weight: 800; margin: 0; color: var(--text-dark);}
        .stat-card .today-count {
            font-size: 14px;
            color: var(--text-light);
            margin-top: 5px;
        }

        .charts-and-activity{
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .charts-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .chart-card{
            background: var(--card-background);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
        .chart-card:hover{box-shadow: var(--shadow-hover); transform: translateY(-5px);}
        .chart-card h3{
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        .chart-card h3 svg {
            margin-right: 8px;
        }
        .chart-card canvas{
            height: 300px !important;
        }

        .activity-card {
            background: var(--card-background);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
        .activity-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .activity-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .activity-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .activity-count {
            font-size: 14px;
            color: var(--text-light);
            background: var(--background-light);
            padding: 4px 10px;
            border-radius: 20px;
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
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
            transition: var(--transition);
        }
        .activity-feed li:hover {
            background: rgba(0, 0, 0, 0.02);
            padding-left: 10px;
            border-radius: 8px;
        }
        .activity-feed li:last-child {
            border-bottom: none;
        }
        .activity-desc {
            color: var(--text-dark);
            word-break: break-word;
            padding-right: 10px;
            font-weight: 500;
        }
        .activity-time {
            color: var(--text-light);
            font-size: 13px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .no-activity {
            text-align: center;
            color: var(--text-light);
            padding: 15px 0;
            font-style: italic;
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
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(67, 97, 238, 0.1);
        }
        .view-all-btn:hover {
            color: white;
            background: var(--primary-color);
            transform: translateX(5px);
        }

        .quick-actions-card {
            background: var(--card-background);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            transition: var(--transition);
        }
        .quick-actions-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
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
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .action-list a{
            display: flex;
            align-items: center;
            padding: 15px;
            background: var(--background-light);
            border-radius: 10px;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            transition: var(--transition);
            border: 1px solid var(--border-color);
            flex-basis: calc(33.33% - 15px);
            min-width: 200px;
            position: relative;
            overflow: hidden;
        }
        .action-list a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }
        .action-list a:hover::before {
            left: 100%;
        }
        .action-list a:hover{
            background: var(--primary-color);
            color: white;
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
            border-color: var(--primary-color);
        }
        .action-list a svg{
            margin-right: 12px;
            width: 18px;
            height: 18px;
            transition: var(--transition);
        }
        .action-list a:hover svg {
            transform: scale(1.2);
        }

        .system-info-card {
            background: var(--card-background);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            transition: var(--transition);
        }
        .system-info-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }
        .system-info-card h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--primary-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }
        .system-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        .system-info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed var(--border-color);
        }
        .system-info-item:last-child {
            border-bottom: none;
        }
        .system-info-label {
            font-weight: 500;
            color: var(--text-dark);
        }
        .system-info-value {
            color: var(--text-light);
            font-family: monospace;
            font-size: 14px;
        }

        /* Dark mode styles */
        body.dark-mode {
            --text-dark: #e9ecef;
            --text-light: #adb5bd;
            --card-background: #2d3748;
            --background-light: #1a202c;
            --background-gradient: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            --border-color: #4a5568;
        }

        @media (max-width: 900px) {
            .charts-and-activity{
                grid-template-columns: 1fr;
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
                grid-template-columns: repeat(auto-fit, minmax(min(100%, 150px), 1fr));
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

            .system-info-grid {
                grid-template-columns: 1fr;
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
            animation: slideInUp 0.5s ease;
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

        /* Animations */
        @keyframes slideInUp {
            from {
                transform: translateX(-50%) translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease;
        }

        .animate-delay-1 { animation-delay: 0.1s; }
        .animate-delay-2 { animation-delay: 0.2s; }
        .animate-delay-3 { animation-delay: 0.3s; }
        .animate-delay-4 { animation-delay: 0.4s; }
        .animate-delay-5 { animation-delay: 0.5s; }

        /* Loading animation for charts */
        .chart-loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 300px;
        }
        .chart-loading::after {
            content: '';
            width: 40px;
            height: 40px;
            border: 4px solid var(--border-color);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            <div class="stat-card notices animate-fade-in animate-delay-1">
                <div>
                    <h3><?= $lang['notices'] ?></h3>
                    <p><?= $total_notices ?></p>
                    <div class="today-count">+<?= $today_notices ?> <?= $lang['today'] ?? 'today' ?></div>
                </div>
                <div class="icon-box"><i data-feather="bell"></i></div>
            </div>
            <div class="stat-card gallery animate-fade-in animate-delay-2">
                <div>
                    <h3><?= $lang['gallery'] ?? 'Gallery Items' ?></h3>
                    <p><?= $total_gallery ?></p>
                    <div class="today-count">+<?= $today_gallery ?> <?= $lang['today'] ?? 'today' ?></div>
                </div>
                <div class="icon-box"><i data-feather="image"></i></div>
            </div>
            <div class="stat-card messages animate-fade-in animate-delay-3">
                <div>
                    <h3><?= $lang['messages'] ?? 'Messages' ?></h3>
                    <p><?= $total_messages ?></p>
                    <div class="today-count">+<?= $today_messages ?> <?= $lang['today'] ?? 'today' ?></div>
                </div>
                <div class="icon-box"><i data-feather="inbox"></i></div>
            </div>
            <div class="stat-card admins animate-fade-in animate-delay-4">
                <div>
                    <h3><?= $lang['admins'] ?? 'Admins' ?></h3>
                    <p><?= $total_admins ?></p>
                </div>
                <div class="icon-box"><i data-feather="users"></i></div>
            </div>
            <div class="stat-card active-admins animate-fade-in animate-delay-5">
                <div>
                    <h3><?= $lang['active_admins'] ?? 'Active Admins' ?></h3>
                    <p><?= $total_active_admins ?></p>
                </div>
                <div class="icon-box"><i data-feather="user-check"></i></div>
            </div>
        </div>

        <div class="charts-and-activity">
            <div class="chart-card animate-fade-in">
                <h3><i data-feather="bar-chart-2"></i> <?= $lang['notices'] ?> (<?= $lang['per_month'] ?? 'Per Month' ?>)</h3>
                <div class="chart-loading" id="noticesChartLoading"></div>
                <canvas id="noticesChart" style="display:none;"></canvas>
            </div>

            <div class="activity-card animate-fade-in">
                <div class="activity-header">
                    <h3><i data-feather="clock"></i> <?= $lang['recent_activity'] ?></h3>
                    <span class="activity-count"><?= count($activities) ?> <?= $lang['activities'] ?? 'activities' ?></span>
                </div>

                <ul class="activity-feed">
                    <?php foreach(array_slice($activities, 0, 5) as $act): ?>
                        <li>
                            <a href="<?= $act['link'] ?? '#' ?>" class="activity-desc"><?= htmlspecialchars($act['desc']) ?></a>
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
            <div class="chart-card animate-fade-in">
                <h3><i data-feather="trending-up"></i> <?= $lang['gallery'] ?> (<?= $lang['monthly_uploads'] ?? 'Monthly Uploads' ?>)</h3>
                <div class="chart-loading" id="galleryChartLoading"></div>
                <canvas id="galleryChart" style="display:none;"></canvas>
            </div>

            <div class="chart-card animate-fade-in">
                <h3><i data-feather="pie-chart"></i> <?= $lang['messages'] ?> (<?= $lang['by_type'] ?? 'By Type' ?>)</h3>
                <div class="chart-loading" id="messagesChartLoading"></div>
                <canvas id="messagesChart" style="display:none;"></canvas>
            </div>
        </div>

        <div class="charts-row-2">
            <div class="quick-actions-card animate-fade-in">
                <h3><i data-feather="zap"></i> <?= $lang['quick_actions'] ?? 'Quick Actions' ?></h3>
                <ul class="action-list">
                    <li><a href="add_notice.php"><i data-feather="plus"></i> <?= $lang['add_notice'] ?? 'Add Notice' ?></a></li>
                    <li><a href="gallery_add.php"><i data-feather="image"></i> <?= $lang['add_gallery_photo'] ?? 'Add Gallery Photo' ?></a></li>
                    <li><a href="add_admin.php"><i data-feather="user-plus"></i> <?= $lang['add_admin'] ?? 'Add Admin' ?></a></li>
                    <li><a href="messages.php"><i data-feather="mail"></i> <?= $lang['view_messages'] ?? 'View Messages' ?></a></li>
                    <li><a href="settings.php"><i data-feather="settings"></i> <?= $lang['settings'] ?? 'Settings' ?></a></li>
                    <li><a href="profile.php"><i data-feather="user"></i> <?= $lang['profile'] ?? 'Profile' ?></a></li>
                </ul>
            </div>

            <div class="system-info-card animate-fade-in">
                <h3><i data-feather="server"></i> <?= $lang['system_info'] ?? 'System Information' ?></h3>
                <div class="system-info-grid">
                    <div class="system-info-item">
                        <span class="system-info-label">PHP Version</span>
                        <span class="system-info-value"><?= $php_version ?></span>
                    </div>
                    <div class="system-info-item">
                        <span class="system-info-label">MySQL Version</span>
                        <span class="system-info-value"><?= $mysql_version ?></span>
                    </div>
                    <div class="system-info-item">
                        <span class="system-info-label">Server Software</span>
                        <span class="system-info-value"><?= $server_software ?></span>
                    </div>
                    <div class="system-info-item">
                        <span class="system-info-label">Upload Max Filesize</span>
                        <span class="system-info-value"><?= $upload_max_filesize ?></span>
                    </div>
                    <div class="system-info-item">
                        <span class="system-info-label">Memory Limit</span>
                        <span class="system-info-value"><?= $memory_limit ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    feather.replace();

    // Initialize charts after a slight delay to show loading animation
    setTimeout(() => {
        const noticesChart = new Chart(document.getElementById('noticesChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($months_labels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: '<?= $lang['notices'] ?>',
                    data: [<?= implode(',', $notices_per_month) ?>],
                    backgroundColor: 'rgba(67, 97, 238, 0.8)',
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
                    x: { grid: { display: false }, ticks: { font: { family: 'Inter' } } },
                    y: { beginAtZero: true, grid: { color: 'var(--border-color)' } }
                }
            }
        });

        document.getElementById('noticesChartLoading').style.display = 'none';
        document.getElementById('noticesChart').style.display = 'block';
    }, 1000);

    setTimeout(() => {
        const galleryChart = new Chart(document.getElementById('galleryChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($months_labels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: '<?= $lang['gallery_uploads'] ?? 'Gallery Uploads' ?>',
                    data: [<?= implode(',', $gallery_per_month) ?>],
                    backgroundColor: 'rgba(72, 149, 239, 0.2)',
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
                    x: { grid: { display: false }, ticks: { font: { family: 'Inter' } } },
                    y: { beginAtZero: true, grid: { color: 'var(--border-color)' } }
                }
            }
        });

        document.getElementById('galleryChartLoading').style.display = 'none';
        document.getElementById('galleryChart').style.display = 'block';
    }, 1500);

    setTimeout(() => {
        const messagesChart = new Chart(document.getElementById('messagesChart'), {
            type: 'doughnut',
            data: {
                labels: ['<?= $lang['general'] ?? 'General' ?>','<?= $lang['complaint'] ?? 'Complaint' ?>','<?= $lang['suggestion'] ?? 'Suggestion' ?>'],
                datasets: [{
                    data: [<?= $messages_count['general'] ?>, <?= $messages_count['complaint'] ?>, <?= $messages_count['suggestion'] ?>],
                    backgroundColor: ['var(--success-color)','var(--danger-color)','var(--warning-color)'],
                    hoverBackgroundColor: ['#3aa58a','#d63031','#e6b325'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                aspectRatio: window.innerWidth <= 900 ? 1 : 1.5,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 15, font: { family: 'Inter' } } },
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

        document.getElementById('messagesChartLoading').style.display = 'none';
        document.getElementById('messagesChart').style.display = 'block';
    }, 2000);

    // Mobile banner functionality
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

    // Add animation on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all cards for animation
    document.querySelectorAll('.stat-card, .chart-card, .activity-card, .quick-actions-card, .system-info-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
    });
</script>

</body>
</html>
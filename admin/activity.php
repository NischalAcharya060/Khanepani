<?php
session_start();
include '../config/database/db.php';

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

include '../config/Nepali_calendar.php';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
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
        }

        main {
            padding: 20px;
            max-width: 1200px;
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
            min-width: 600px;
        }

        th, td {
            padding: clamp(12px, 3vw, 16px);
            text-align: left;
            font-size: clamp(13px, 2.5vw, 15px);
            border-bottom: 1px solid var(--border-color);
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
            background: #f8f9fa;
        }

        tbody tr:hover {
            background: #e3f2fd;
            transition: background-color 0.2s ease;
        }

        .activity-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            min-width: 200px;
        }

        .activity-cell svg {
            color: var(--secondary-color);
            width: clamp(16px, 3vw, 18px);
            height: clamp(16px, 3vw, 18px);
            flex-shrink: 0;
        }

        .time-cell {
            color: #666;
            font-size: clamp(12px, 2.5vw, 14px);
            white-space: nowrap;
        }

        .author-cell {
            font-weight: 500;
            color: var(--text-dark);
        }

        /* Mobile Card View */
        .mobile-activities {
            display: none;
            gap: 16px;
            flex-direction: column;
        }

        .activity-card {
            background: var(--card-background);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-light);
            border-left: 4px solid var(--primary-color);
        }

        .activity-header {
            display: flex;
            align-items: flex-start;
            justify-content: between;
            margin-bottom: 12px;
            gap: 12px;
        }

        .activity-icon {
            background: var(--primary-color);
            color: white;
            border-radius: 8px;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-icon svg {
            width: 16px;
            height: 16px;
        }

        .activity-content {
            flex: 1;
            min-width: 0;
        }

        .activity-title {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 4px;
            word-wrap: break-word;
        }

        .activity-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 13px;
            color: var(--secondary-color);
        }

        .activity-author {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .activity-author::before {
            content: "👤";
            font-size: 12px;
        }

        .activity-time {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .activity-time::before {
            content: "🕒";
            font-size: 12px;
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

            .mobile-activities {
                display: flex;
            }

            .activity-card {
                padding: 16px;
            }

            .activity-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .activity-meta {
                flex-direction: column;
                gap: 6px;
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

            .activity-card {
                padding: 14px;
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

        @media (max-width: 360px) {
            .activity-meta {
                font-size: 12px;
            }

            .activity-title {
                font-size: 14px;
            }
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
            }

            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .pagination {
                display: none;
            }
        }

        /* Loading State */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
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
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main>
    <div class="page-header">
        <h2>
            <i data-feather="activity"></i>
            <?= $lang['recent_activity'] ?? 'Recent Activity' ?>
        </h2>
        <p class="subtitle"><?= $lang['activity_subtitle'] ?? 'Here are the latest activities in the system.' ?></p>
    </div>

    <div class="card">
        <!-- Desktop Table View -->
        <div class="table-container desktop-table">
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
                                <span><?= htmlspecialchars($act['type_desc'] ?: '—') ?></span>
                            </td>
                            <td class="author-cell"><?= htmlspecialchars($act['author'] ?: '—') ?></td>
                            <td class="time-cell"><?= $act['time'] ? format_nepali_date($act['time'], $cal) : '—' ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <i data-feather="info"></i>
                                <h3><?= $lang['no_activity'] ?? 'No activities found' ?></h3>
                                <p><?= $lang['no_activity_desc'] ?? 'There are no activities to display at the moment.' ?></p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="mobile-activities">
            <?php if ($activities && $activities->num_rows > 0): ?>
                <?php
                // Reset pointer and recreate activities for mobile view
                $activities->data_seek(0);
                $sn = $offset + 1;
                ?>
                <?php while ($act = $activities->fetch_assoc()): ?>
                    <div class="activity-card">
                        <div class="activity-header">
                            <div class="activity-icon">
                                <i data-feather="<?= getActivityIcon($act['source_type']) ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <?= htmlspecialchars($act['type_desc'] ?: '—') ?>
                                </div>
                                <div class="activity-meta">
                                    <span class="activity-author">
                                        <?= htmlspecialchars($act['author'] ?: '—') ?>
                                    </span>
                                    <span class="activity-time">
                                        <?= $act['time'] ? format_nepali_date($act['time'], $cal) : '—' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i data-feather="info"></i>
                    <h3><?= $lang['no_activity'] ?? 'No activities found' ?></h3>
                    <p><?= $lang['no_activity_desc'] ?? 'There are no activities to display at the moment.' ?></p>
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
                    <?php if ($activities && $activities->num_rows > 0): ?>
                        • <?= sprintf($lang['showing'] ?? 'Showing %d items', $activities->num_rows) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    // Initialize Feather icons
    feather.replace();

    // Add loading state for pagination links
    document.addEventListener('DOMContentLoaded', function() {
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Add loading state to the card
                const card = document.querySelector('.card');
                card.classList.add('loading');

                // Remove loading state after navigation (if user stays on page)
                setTimeout(() => {
                    card.classList.remove('loading');
                }, 1000);
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
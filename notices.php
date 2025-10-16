<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include 'config/database/db.php';
include 'config/lang.php';
include 'config/Nepali_calendar.php';

$cal = new Nepali_Calendar();

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

function format_date($date_str, $cal, $lang) {
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

        $dateNep = strtr($nepDate['year'].'-'.$nepDate['month'].'-'.$nepDate['date'], $np_numbers);
        $timeNep = strtr(sprintf("%02d:%02d", $hour, $minute), $np_numbers) . " " . $ampm;

        return $dateNep . '<br><span class="notice-time">' . $timeNep . '</span>'
                . '<span class="hidden-timestamp" data-timestamp="' . $timestamp . '"></span>';
    } else {
        $engDate = date("F j, Y", $timestamp) . '<br><span class="notice-time">' . date("h:i A", $timestamp) . '</span>';

        return $engDate . '<span class="hidden-timestamp" data-timestamp="' . $timestamp . '"></span>';
    }
}

$notice_type_options = [
        'all'           => $lang['filter_all'] ?? 'All Types',
        'General'       => $lang['type_general'] ?? 'General Notice',
        'Operational'   => $lang['type_operational'] ?? 'Operational Update',
        'Maintenance'   => $lang['type_maintenance'] ?? 'Maintenance Schedule',
        'Financial'     => $lang['type_financial'] ?? 'Financial Report',
];

// CORRECTED SQL QUERY: Fetch the actual `type` column from the database.
$sql = "SELECT id, title, created_at, type FROM notices ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$notices = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ($lang['all_notices'] ?? 'All Notices') ?> - <?= ($lang['logo'] ?? 'Water Supply') ?></title>

    <meta name="description" content="View all official notices, operational updates, maintenance schedules, and public announcements from Salakpur KhanePani Office.">
    <meta name="keywords" content="Notices, Announcements, Water Supply, Updates, Salakpur, KhanePani">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">

    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #004080;
            --accent-color: #007bff;
            --bg-light: #f5f7fa;
            --text-dark: #34495e;
            --text-muted: #7f8c8d;
            --shadow-light: 0 4px 10px rgba(0, 0, 0, 0.05);
            --shadow-medium: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            margin: 0;
            line-height: 1.6;
        }

        .notice-container {
            padding: 40px 20px;
            max-width: 1100px;
            margin: auto;
        }
        .notice-container h2 {
            text-align: left;
            margin-bottom: 30px;
            font-size: 38px;
            color: var(--secondary-color);
            border-bottom: 5px solid var(--primary-color);
            padding-bottom: 12px;
            font-weight: 700;
        }

        .controls-bar {
            display: grid;
            grid-template-columns: 1fr repeat(3, 180px) auto;
            gap: 20px;
            margin-bottom: 35px;
            align-items: center;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow-light);
        }

        .search-wrapper, .date-wrapper {
            position: relative;
        }

        .search-wrapper .fa-search, .date-wrapper .fa-calendar-alt {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
            pointer-events: none;
        }

        #search-input, .filter-select, .filter-date-input, #clear-filters {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e6ed;
            border-radius: 8px;
            font-size: 16px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
            background-color: #ffffff;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        #search-input, .filter-date-input {
            padding-left: 45px;
        }

        #search-input:focus, .filter-select:focus, .filter-date-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            outline: none;
        }

        .filter-select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2334495e' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 1em;
        }

        #clear-filters {
            padding: 12px 15px;
            background-color: #e74c3c;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
            white-space: nowrap;
        }
        #clear-filters:hover {
            background-color: #c0392b;
            box-shadow: 0 4px 12px rgba(192, 57, 43, 0.4);
        }

        .notice-list {
            list-style: none;
            padding: 0;
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--shadow-medium);
            overflow: hidden;
        }
        .notice-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid #ecf0f1;
            transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            color: var(--text-dark);
        }
        .hidden-timestamp {
            display: none !important;
        }
        .notice-item:last-child {
            border-bottom: none;
        }
        .notice-item:hover {
            background-color: #f8faff;
            color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.1);
        }

        .notice-info {
            flex-grow: 1;
            padding-right: 20px;
            display: flex;
            align-items: center;
        }
        .notice-icon {
            color: var(--accent-color);
            font-size: 20px;
            margin-right: 20px;
        }
        .notice-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            line-height: 1.4;
        }

        .notice-date-box {
            font-size: 15px;
            font-weight: 600;
            color: #2980b9;
            white-space: nowrap;
            text-align: center;
            padding: 10px 18px;
            background: #eaf3ff;
            border: 2px solid #d0e8ff;
            border-radius: 8px;
            min-width: 110px;
            line-height: 1.3;
        }
        .notice-date-box .notice-time {
            display: block;
            font-size: 12px;
            font-weight: 400;
            color: #5d7c9a;
            margin-top: 4px;
        }
        .notice-item:hover .notice-date-box {
            background: #cce5ff;
            border-color: var(--primary-color);
            color: var(--secondary-color);
        }

        .no-notices {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--shadow-medium);
            color: var(--text-muted);
            font-size: 20px;
            font-weight: 500;
            text-align: center;
            margin-top: 20px;
        }
        .no-notices i {
            font-size: 52px;
            margin-bottom: 25px;
            color: #bdc3c7;
        }

        @media (max-width: 900px) {
            .controls-bar {
                grid-template-columns: 1fr 1fr;
                padding: 15px;
            }
            .controls-bar > :nth-child(5) {
                grid-column: span 2;
            }
        }
        @media (max-width: 600px) {
            .notice-container { padding: 15px 10px; }
            .notice-container h2 { font-size: 32px; }
            .notice-item { padding: 15px; flex-wrap: wrap; }
            .notice-info { width: 100%; margin-bottom: 10px; }
            .notice-date-box { min-width: auto; padding: 6px 12px; }
            .controls-bar {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
    </style>
</head>
<body>

<?php
include 'components/header.php';
?>

<div class="notice-container">
    <h2><?= $lang['all_notices'] ?? 'Notices' ?></h2>

    <div class="controls-bar">
        <div class="search-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" id="search-input" placeholder="<?= $lang['search_notices'] ?? 'Search titles...' ?>">
        </div>

        <select id="type-filter" class="filter-select">
            <?php foreach ($notice_type_options as $value => $label): ?>
                <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>

        <div class="date-wrapper">
            <i class="fas fa-calendar-alt"></i>
            <input type="date" id="date-from" class="filter-date-input" placeholder="<?= $lang['date_from'] ?? 'Date From' ?>">
        </div>

        <div class="date-wrapper">
            <i class="fas fa-calendar-alt"></i>
            <input type="date" id="date-to" class="filter-date-input" placeholder="<?= $lang['date_to'] ?? 'Date To' ?>">
        </div>

        <button id="clear-filters" title="<?= $lang['clear_filters'] ?? 'Clear all search and filters' ?>">
            <i class="fas fa-eraser"></i> <?= $lang['clear_btn'] ?? 'Clear' ?>
        </button>
    </div>

    <ul class="notice-list" id="noticeList">
        <?php
        $hasInitialNotices = count($notices) > 0;

        if ($hasInitialNotices) {
            foreach($notices as $row){
                $displayDate = format_date($row['created_at'], $cal, $lang);
                // Use $row['type'] which is now correctly fetched from the DB
                $notice_type = $row['type'] ?? 'General';
                ?>
                <a
                        href="notice.php?id=<?= $row['id'] ?>"
                        class="notice-item"
                        data-type="<?= htmlspecialchars($notice_type) ?>"
                        data-title="<?= htmlspecialchars(strtolower($row['title'])) ?>"
                >
                    <div class="notice-info">
                        <i class="fas fa-bullhorn notice-icon"></i>
                        <p class="notice-title"><?= htmlspecialchars($row['title']) ?></p>
                    </div>
                    <div class="notice-date-box">
                        <?= $displayDate ?>
                    </div>
                </a>
                <?php
            }
        }

        if (!$hasInitialNotices) {
            $noNoticesText = $lang['no_notices'] ?? 'No notices have been published yet.';
            echo '<div class="no-notices" id="no-notices-message">'
                    . '<i class="fa-regular fa-bell"></i> '
                    . htmlspecialchars($noNoticesText, ENT_QUOTES, 'UTF-8')
                    . '</div>';
        }

        ?>
        <div class='no-notices' id='no-results-message' style="display: none;">
            <i class='fas fa-exclamation-circle'></i>
            <?= ($lang['no_results'] ?? 'No results found for your filters.') ?>
        </div>
    </ul>

</div>

<?php
include 'components/footer.php';
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const typeFilter = document.getElementById('type-filter');
        const dateFrom = document.getElementById('date-from');
        const dateTo = document.getElementById('date-to');
        const clearFiltersButton = document.getElementById('clear-filters');

        const noticeList = document.getElementById('noticeList');
        const noticeItems = noticeList.querySelectorAll('.notice-item');
        const noResultsMessage = document.getElementById('no-results-message');
        const noNoticesMessage = document.getElementById('no-notices-message');
        const hasInitialNotices = noticeItems.length > 0;

        function applyFilters() {
            const query = searchInput.value.toLowerCase().trim();
            const type = typeFilter.value;

            const dateFromTs = dateFrom.value ? new Date(dateFrom.value).setHours(0, 0, 0, 0) / 1000 : 0;
            const dateToTs   = dateTo.value   ? new Date(dateTo.value).setHours(23, 59, 59, 999) / 1000 : Infinity;

            let resultsFound = 0;

            noticeItems.forEach(item => {
                const itemTitle = item.dataset.title || '';
                const itemType = item.dataset.type || '';

                const itemTimestampElement = item.querySelector('.hidden-timestamp');
                const itemTimestamp = itemTimestampElement ? parseInt(itemTimestampElement.dataset.timestamp) : 0;

                const titleMatch = itemTitle.includes(query);
                const typeMatch = type === 'all' || itemType === type;
                const dateMatch = itemTimestamp >= dateFromTs && itemTimestamp <= dateToTs;

                if (titleMatch && typeMatch && dateMatch) {
                    item.style.display = 'flex';
                    resultsFound++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (hasInitialNotices) {
                if (noNoticesMessage) noNoticesMessage.style.display = 'none';

                if (resultsFound === 0) {
                    noResultsMessage.style.display = 'flex';
                } else {
                    noResultsMessage.style.display = 'none';
                }
            }
        }

        function clearFilters() {
            searchInput.value = '';
            typeFilter.value = 'all';
            dateFrom.value = '';
            dateTo.value = '';

            applyFilters();

            if (!hasInitialNotices && noNoticesMessage) {
                noNoticesMessage.style.display = 'flex';
                noResultsMessage.style.display = 'none';
            }
        }

        searchInput.addEventListener('input', applyFilters);
        typeFilter.addEventListener('change', applyFilters);
        dateFrom.addEventListener('change', applyFilters);
        dateTo.addEventListener('change', applyFilters);
        clearFiltersButton.addEventListener('click', clearFilters);

        applyFilters();
    });
</script>

</body>
</html>
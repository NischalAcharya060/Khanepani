<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include 'config/db.php';
include 'config/lang.php';
include 'config/nepali_calendar.php';
$cal = new Nepali_Calendar();

// Function to format date with time
function format_date($date_str, $cal) {
    $timestamp = strtotime($date_str);
    $year  = (int)date('Y', $timestamp);
    $month = (int)date('m', $timestamp);
    $day   = (int)date('d', $timestamp);
    $hour  = (int)date('h', $timestamp);
    $minute = (int)date('i', $timestamp);
    $ampm  = date('A', $timestamp);

    if ( (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en') === 'np' ) {
        $nepDate = $cal->eng_to_nep($year, $month, $day);
        $np_numbers = ['0'=>'०','1'=>'१','2'=>'२','3'=>'३','4'=>'४','5'=>'५','6'=>'६','7'=>'७','8'=>'८','9'=>'९'];

        $dateNep = strtr($nepDate['year'].'-'.$nepDate['month'].'-'.$nepDate['date'], $np_numbers);
        $timeNep = strtr(sprintf("%02d:%02d", $hour, $minute), $np_numbers) . " " . $ampm;

        return $dateNep . '<br><span class="notice-time">' . $timeNep . '</span>';
    } else {
        return date("F j, Y", $timestamp) . '<br><span class="notice-time">' . date("h:i A", $timestamp) . '</span>';
    }
}
$notice_type_options = [
        'General' => 'General Notice',
        'Operational' => 'Operational Update',
        'Maintenance' => 'Maintenance Schedule',
        'Financial' => 'Financial Report',
];
$current_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
?>
<!DOCTYPE html>
<html lang="<?= isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= (isset($lang['all_notices']) ? $lang['all_notices'] : 'All Notices') ?> - <?= (isset($lang['logo']) ? $lang['logo'] : 'Water Supply') ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f0f4f8;
            color: #2c3e50;
            margin: 0;
            line-height: 1.6;
        }

        .notice-container {
            padding: 40px 20px;
            max-width: 900px;
            margin: auto;
        }
        .notice-container h2 {
            text-align: left;
            margin-bottom: 25px;
            font-size: 36px;
            color: #004080;
            border-bottom: 4px solid #007bff;
            padding-bottom: 10px;
            font-weight: 700;
        }

        /* --- NEW STYLES FOR SEARCH BAR --- */
        .controls-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        #search-input {
            flex-grow: 1;
            padding: 12px 20px 12px 45px; /* Extra padding for icon */
            border: 1px solid #dcdfe6;
            border-radius: 10px;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); /* Subtle shadow */
            transition: all 0.3s ease;
            background-color: #ffffff;
            /* Integrated Search Icon using Font Awesome class and padding */
        }

        #search-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .search-wrapper {
            flex-grow: 1;
            position: relative;
        }

        .search-wrapper .fa-search {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 16px;
        }
        /* --- END NEW STYLES --- */


        .notice-list {
            list-style: none;
            padding: 0;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .notice-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 25px;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.3s ease-in-out;
            text-decoration: none;
            color: #2c3e50;
        }
        .notice-item:last-child {
            border-bottom: none;
        }

        .notice-item:hover {
            background-color: #eaf3ff;
            color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.1);
        }

        .notice-info {
            flex-grow: 1;
            padding-right: 20px;
            display: flex;
            align-items: center;
        }
        .notice-icon {
            color: #007bff;
            font-size: 18px;
            margin-right: 15px;
        }
        .notice-title {
            font-size: 17px;
            font-weight: 600;
            margin: 0;
            line-height: 1.4;
        }
        .notice-item:hover .notice-title {
            text-decoration: none;
        }

        .notice-source {
            display: none;
        }

        .notice-date-box {
            font-size: 14px;
            font-weight: 700;
            color: #34495e;
            white-space: nowrap;
            text-align: center;
            padding: 8px 15px;
            background: #f1f8ff;
            border: 1px solid #cce5ff;
            border-radius: 6px;
            min-width: 100px;
            line-height: 1.3;
        }
        .notice-date-box .notice-time {
            display: block;
            font-size: 12px;
            font-weight: 400;
            color: #6c757d;
            margin-top: 2px;
        }

        .notice-item:hover .notice-date-box {
            background: #cce5ff;
            color: #004080;
        }
        .notice-item:hover .notice-date-box .notice-time {
            color: #34495e;
        }


        .no-notices {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
            color: #7f8c8d;
            font-size: 20px;
            font-weight: 500;
            text-align: center;
            margin-top: 20px;
        }
        .no-notices i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #bdc3c7;
        }

        @media (max-width: 600px) {
            .notice-container { padding: 20px 10px; }
            .notice-container h2 { font-size: 30px; }
            .notice-item { padding: 15px; flex-wrap: wrap; }
            .notice-info { width: 100%; margin-bottom: 10px; }
            .notice-date-box { min-width: auto; padding: 6px 10px; }
            .search-wrapper, #search-input { width: 100%; }
        }
    </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<div class="notice-container">
    <h2><?= (isset($lang['all_notices']) ? $lang['all_notices'] : 'Notices') ?></h2>

    <div class="controls-bar">
        <div class="search-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" id="search-input" placeholder="<?= (isset($lang['search_notices']) ? $lang['search_notices'] : 'Search notices by title...') ?>">
        </div>
        <!-- If full type filtering were implemented, it would go here, e.g.:
        <select id="type-filter">...</select> -->
    </div>

    <ul class="notice-list" id="noticeList">
        <?php
        $sql = "SELECT id, title, created_at FROM notices ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);

        $hasNotices = false;

        if(mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_assoc($result)){
                $hasNotices = true;
                $displayDate = format_date($row['created_at'], $cal);
                ?>
                <a href="notice.php?id=<?= $row['id'] ?>" class="notice-item">
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

        if (!$hasNotices) {
            $noNoticesText = $lang['no_notices'] ?? 'No notices have been published yet.';
            echo '<div class="no-notices" id="no-notices-message">'
                    . '<i class="fa-regular fa-bell"></i> '
                    . htmlspecialchars($noNoticesText, ENT_QUOTES, 'UTF-8')
                    . '</div>';
        }

        if(!$hasNotices){ echo "<div class='no-notices' id='no-notices-message'><i class='fa-regular fa-bell'></i>" . (isset($lang['no_notices']) ? $lang['no_notices'] : 'No notices have been published yet.') . "</div>"; } ?> <div class='no-notices' id='no-results-message' style="display: none;"><i class='fas fa-exclamation-circle'></i><?= (isset($lang['no_results']) ? $lang['no_results'] : 'No results found for your search.') ?></div>
    </ul>

</div>

<?php include 'components/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const noticeList = document.getElementById('noticeList');
        const noticeItems = noticeList.querySelectorAll('.notice-item');
        const noResultsMessage = document.getElementById('no-results-message');
        const noNoticesMessage = document.getElementById('no-notices-message');

        /**
         * Filters the notice list based on the search input value (title).
         */
        function filterNotices() {
            const query = searchInput.value.toLowerCase().trim();
            let resultsFound = 0;

            noticeItems.forEach(item => {
                const titleElement = item.querySelector('.notice-title');
                if (!titleElement) return;

                const title = titleElement.textContent.toLowerCase();

                if (title.includes(query)) {
                    item.style.display = 'flex'; // Show the item
                    resultsFound++;
                } else {
                    item.style.display = 'none'; // Hide the item
                }
            });

            // Update the No Results message visibility
            if (noNoticesMessage) {
                // If there were no notices initially, this message is still relevant.
                // We keep it hidden during filtering.
                noNoticesMessage.style.display = 'none';
            }

            if (resultsFound === 0 && noticeItems.length > 0) {
                noResultsMessage.style.display = 'flex';
            } else {
                noResultsMessage.style.display = 'none';
            }

            // Re-show the original 'no notices' message if the list was truly empty initially
            if (noticeItems.length === 0 && noNoticesMessage) {
                noNoticesMessage.style.display = 'flex';
                noResultsMessage.style.display = 'none';
            }
        }

        // Attach event listener for live filtering
        searchInput.addEventListener('input', filterNotices);

        // Initial check to ensure all items are visible on load
        filterNotices();
    });
</script>

</body>
</html>

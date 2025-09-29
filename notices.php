<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Include database and language
include 'config/db.php';
include 'config/lang.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['all_notices'] ?> - <?= $lang['logo'] ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f5f7fa;
            color: #333;
            margin: 0;
            line-height: 1.6;
        }

        /* Section */
        .latest-notices {
            padding: 40px 20px;
            max-width: 1200px;
            margin: auto;
        }

        .latest-notices h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 28px;
            color: #222;
        }

        /* Controls */
        .notice-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-box input {
            padding: 10px 40px 10px 15px;
            width: 280px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
        }

        .search-box .clear-btn {
            position: absolute;
            right: 10px;
            font-size: 16px;
            color: #aaa;
            cursor: pointer;
            display: none;
            transition: 0.2s;
        }

        .search-box .clear-btn:hover { color: #333; }

        .notice-controls select {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
            background: #fff;
            cursor: pointer;
        }

        /* Grid layout */
        .notice-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .notice-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            overflow: hidden;
            text-decoration: none;
            color: #333;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .notice-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.1);
        }

        .notice-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .notice-content {
            padding: 15px;
        }

        .notice-content h3 {
            font-size: 18px;
            margin: 0 0 10px;
            color: #0056d6;
        }

        .notice-content p {
            font-size: 14px;
            color: #555;
            margin-bottom: 10px;
        }

        .notice-date {
            display: block;
            font-size: 12px;
            color: #999;
        }

        /* No Notices */
        .no-notices {
            grid-column: 1/-1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
            color: #7f8c8d;
            font-size: 20px;
            font-weight: 500;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .no-notices:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.12);
        }

        .no-notices i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #bdc3c7;
        }

        /* Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            justify-content: center;
            align-items: center;
            flex-direction: column;
            animation: fadeIn 0.3s;
        }

        .lightbox img {
            max-width: 90%;
            max-height: 80%;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
        }

        .lightbox-caption {
            margin-top: 15px;
            text-align: center;
            color: #fff;
            font-size: 16px;
        }

        .lightbox .close {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 32px;
            font-weight: bold;
            color: #fff;
            cursor: pointer;
            transition: color 0.3s;
        }

        .lightbox .close:hover { color: #ff6600; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<section class="latest-notices">
    <h2>📢 <?= $lang['all_notices'] ?></h2>

    <div class="notice-controls">
        <div class="search-box">
            <input type="text" id="noticeSearch" placeholder="<?= $lang['search_placeholder'] ?>">
            <span class="clear-btn" id="clearSearch">&times;</span>
        </div>
        <select id="noticeFilter">
            <option value="newest"><?= $lang['newest_first'] ?></option>
            <option value="oldest"><?= $lang['oldest_first'] ?></option>
        </select>
    </div>

    <div class="notice-wrapper" id="noticeWrapper">
        <?php
        $sql = "SELECT * FROM notices ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);

        $hasNotices = false;

        if(mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_assoc($result)){
                $hasNotices = true;
                ?>
                <a href="notice.php?id=<?= $row['id'] ?>" class="notice-card" data-date="<?= $row['created_at'] ?>">
                    <?php if(!empty($row['image'])): ?>
                        <img src="../assets/uploads/<?= $row['image'] ?>" alt="<?= $row['title'] ?>" class="clickable">
                    <?php endif; ?>
                    <div class="notice-content">
                        <h3><?= $row['title'] ?></h3>
                        <p><?= substr($row['content'],0,200) ?>...</p>
                        <span class="notice-date"><?= date("F j, Y, h:i A", strtotime($row['created_at'])) ?></span>
                    </div>
                </a>
                <?php
            }
        }

        if(!$hasNotices){
            echo "<div class='no-notices'><i class='fa-regular fa-bell'></i>{$lang['no_notices']}</div>";
        }
        ?>
    </div>

    <div id="noSearchResult" class="no-notices" style="display:none;">
        <i class='fa-regular fa-magnifying-glass'></i>
        <?= $lang['no_search_results'] ?>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<!-- Lightbox -->
<div id="lightbox" class="lightbox">
    <span class="close">&times;</span>
    <img id="lightbox-img">
    <div class="lightbox-caption" id="lightbox-caption"></div>
</div>

<script>
    // Search and Filter
    const searchInput = document.getElementById('noticeSearch');
    const clearSearchBtn = document.getElementById('clearSearch');
    const noticeWrapper = document.getElementById('noticeWrapper');
    const noSearchResult = document.getElementById('noSearchResult');
    const filterSelect = document.getElementById('noticeFilter');

    function filterNotices() {
        const filter = searchInput.value.toLowerCase();
        const notices = Array.from(noticeWrapper.getElementsByClassName('notice-card'));
        let found = false;

        notices.forEach(notice => {
            let title = notice.querySelector('h3').innerText.toLowerCase();
            let content = notice.querySelector('p').innerText.toLowerCase();
            if(title.includes(filter) || content.includes(filter)){
                notice.style.display = '';
                found = true;
            } else {
                notice.style.display = 'none';
            }
        });

        clearSearchBtn.style.display = searchInput.value ? 'block' : 'none';
        noSearchResult.style.display = found ? 'none' : 'flex';
    }

    searchInput.addEventListener('keyup', filterNotices);
    clearSearchBtn.addEventListener('click', () => {
        searchInput.value = '';
        filterNotices();
    });

    filterSelect.addEventListener('change', () => {
        const notices = Array.from(noticeWrapper.getElementsByClassName('notice-card'));
        notices.sort((a,b)=>{
            const dateA = new Date(a.dataset.date);
            const dateB = new Date(b.dataset.date);
            return filterSelect.value==='newest' ? dateB-dateA : dateA-dateB;
        });
        notices.forEach(n=>noticeWrapper.appendChild(n));
    });

    // Lightbox
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const closeBtn = document.querySelector('.lightbox .close');

    document.querySelectorAll('.clickable').forEach(img=>{
        img.addEventListener('click',()=>{
            lightbox.style.display='flex';
            lightboxImg.src=img.src;
            lightboxCaption.innerText=img.alt;
        });
    });

    closeBtn.addEventListener('click',()=>lightbox.style.display='none');
    lightbox.addEventListener('click',e=>{ if(e.target===lightbox) lightbox.style.display='none'; });
</script>
</body>
</html>

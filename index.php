<?php
session_start();
include 'config/db.php';
include 'config/Nepali_calendar.php';

// If no language in session, default to English
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// If user switches language (via ?lang=en or ?lang=np)
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] === 'np' ? 'np' : 'en';
    $_SESSION['lang'] = $lang;
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

include "lang/" . $_SESSION['lang'] . ".php";

$cal = new Nepali_Calendar();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="developer" content="Developed by Nischal Acharya">
    <title><?= $lang['logo'] ?? 'Khane Pani Office' ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<style>
    /* Hero Carousel */
    .hero {
        position: relative;
        overflow: hidden;
        height: 700px;
        border-radius: 10px;
        margin-bottom: 40px;
    }
    .carousel {
        position: relative;
        height: 100%;
    }
    .slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 1s ease-in-out;
    }
    .slide.active {
        opacity: 1;
        z-index: 1;
    }
    .slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 10px;
    }
    .caption {
        position: absolute;
        bottom: 40px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.6);
        color: #fff;
        padding: 10px 20px;
        border-radius: 5px;
        font-size: 20px;
    }

    /* Carousel Buttons */
    .carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0,0,0,0.5);
        color: #fff;
        border: none;
        font-size: 28px;
        padding: 10px 15px;
        cursor: pointer;
        border-radius: 50%;
        z-index: 2;
    }
    .prev { left: 20px; }
    .next { right: 20px; }

    /* Lightbox */
    .lightbox {
        display: none;
        position: fixed;
        z-index: 9999;
        padding-top: 60px;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.9);
    }
    .lightbox-content {
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 80%;
        border-radius: 10px;
    }
    .lightbox-caption {
        text-align: center;
        color: #fff;
        margin-top: 15px;
        font-size: 18px;
    }
    .close {
        position: absolute;
        top: 30px;
        right: 40px;
        color: #fff;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
    }
    .close:hover {
        color: #ff6600;
    }
    .clickable {
        cursor: pointer;
        transition: transform 0.3s;
    }
    /* Section Wrapper */
    .latest-notices {
        margin: 50px auto;
        padding: 20px;
        max-width: 1200px;
    }

    .latest-notices h2 {
        font-size: 28px;
        font-weight: bold;
        color: #0a2a66;
        text-align: center;
        margin-bottom: 30px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        position: relative;
    }

    .latest-notices h2::after {
        content: "";
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #ff3366, #0056d6);
        border-radius: 2px;
    }

    /* Notices Grid */
    .notice-grid {
        display: grid;
        grid-template-columns: 1fr 1px 1fr;
        gap: 40px;
        align-items: start;
    }

    /* Column Layout */
    .notice-column {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    /* Notice Item */
    .notice-item {
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    .notice-meta {
        font-size: 14px;
        color: #555;
        margin-bottom: 5px;
    }

    .notice-source {
        font-weight: bold;
        color: #0056d6;
        margin-right: 10px;
    }

    .notice-date {
        color: #777;
    }

    .notice-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .notice-title a {
        color: #0a2a66;
        text-decoration: none;
    }

    .notice-title a:hover {
        text-decoration: none;
    }

    .read-more {
        display: inline-block;
        margin-top: 5px;
        color: #0056d6;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: color 0.2s ease-in-out;
    }

    .read-more:hover {
        color: #003d99;
    }

    /* Separator */
    .notice-separator {
        background: #e0e0e0;
        width: 1px;
    }

    .see-all {
        text-align: right;
        margin-top: 25px;
        padding-right: 10px;
    }

    .see-all a {
        font-size: 16px;
        font-weight: 600;
        color: #0056d6;
        text-decoration: none;
        transition: color 0.2s ease-in-out;
    }

    .see-all a:hover {
        text-decoration: none;
        color: #003d99;
    }

    /* Responsive */
    @media (max-width: 900px) {
        .notice-grid {
            grid-template-columns: 1fr;
        }

        .notice-separator {
            display: none;
        }

        .read-more {
            font-size: 13px;
            text-align: right;
            display: block;
        }

        .see-all {
            text-align: center;
        }
    }
</style>
<body>

<?php include 'components/header.php'; ?>

<!-- Hero Carousel -->
<section class="hero">
    <div class="carousel">
        <div class="slide active">
            <img src="assets/images/hero2.jpg" alt="Kanepani building" class="clickable">
            <div class="caption"><?= $lang['hero_caption1'] ?? 'Our Water Supply Container' ?></div>
        </div>
        <div class="slide">
            <img src="assets/images/hero.jpg" alt="Serving the Community" class="clickable">
            <div class="caption"><?= $lang['hero_caption2'] ?? 'Serving the Community' ?></div>
        </div>
        <div class="slide">
            <img src="assets/images/hero1.jpg" alt="Clean & Safe Drinking Water" class="clickable">
            <div class="caption"><?= $lang['hero_caption3'] ?? 'Clean & Safe Drinking Water' ?></div>
        </div>
        <button class="carousel-btn prev">&#10094;</button>
        <button class="carousel-btn next">&#10095;</button>
    </div>
</section>

<!-- Lightbox Container -->
<div id="lightbox" class="lightbox">
    <span class="close">&times;</span>
    <img class="lightbox-content" id="lightbox-img">
    <div class="lightbox-caption" id="lightbox-caption"></div>
</div>

<!-- Latest Notices -->
<section class="latest-notices container">
    <h2>📢 <?= $lang['latest_notices'] ?? 'Latest Notices' ?></h2>

    <?php
    $sql = "SELECT * FROM notices ORDER BY created_at DESC LIMIT 6";
    $result = mysqli_query($conn, $sql);

    $notices = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notices[] = $row;
    }

    function format_date($date_str, $cal) {
        $timestamp = strtotime($date_str);
        $year  = (int)date('Y', $timestamp);
        $month = (int)date('m', $timestamp);
        $day   = (int)date('d', $timestamp);
        $hour  = (int)date('h', $timestamp); // 12-hour format
        $minute = (int)date('i', $timestamp);
        $ampm  = date('A', $timestamp);

        // Check language
        if ( ($_SESSION['lang'] ?? 'en') === 'np' ) {
            $nepDate = $cal->eng_to_nep($year, $month, $day);
            $np_numbers = ['0'=>'०','1'=>'१','2'=>'२','3'=>'३','4'=>'४','5'=>'५','6'=>'६','7'=>'७','8'=>'८','9'=>'९'];

            $dateNep = strtr($nepDate['year'].'-'.$nepDate['month'].'-'.$nepDate['date'], $np_numbers);
            $timeNep = strtr(sprintf("%02d:%02d", $hour, $minute), $np_numbers) . " " . $ampm;

            return 'मिति: ' . $dateNep . ', ' . 'समय: ' . $timeNep;
        } else {
            return date("F j, Y, h:i A", $timestamp);
        }
    }

    if (count($notices) > 0) {
        $leftNotices = array_slice($notices, 0, 3);
        $rightNotices = array_slice($notices, 3, 3);
        ?>
        <div class="notice-grid">
            <div class="notice-column">
                <?php foreach ($leftNotices as $row): ?>
                    <div class="notice-item">
                        <div class="notice-meta">
                            <span class="notice-source"><?= $lang['notice_label'] ?? 'Notice' ?></span>
                            <span class="notice-date"><?= format_date($row['created_at'], $cal) ?></span>
                        </div>
                        <h3 class="notice-title">
                            <a href="notice.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?></a>
                        </h3>
                        <a href="notice.php?id=<?= $row['id'] ?>" class="read-more"><?= $lang['read_more'] ?? 'Read more →' ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="notice-separator"></div>
            <div class="notice-column">
                <?php foreach ($rightNotices as $row): ?>
                    <div class="notice-item">
                        <div class="notice-meta">
                            <span class="notice-source"><?= $lang['notice_label'] ?? 'Notice' ?></span>
                            <span class="notice-date"><?= format_date($row['created_at'], $cal) ?></span>
                        </div>
                        <h3 class="notice-title">
                            <a href="notice.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?></a>
                        </h3>
                        <a href="notice.php?id=<?= $row['id'] ?>" class="read-more"><?= $lang['read_more'] ?? 'Read more →' ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="see-all">
            <a href="notices.php"><?= $lang['see_all_notices'] ?? 'See all notices →' ?></a>
        </div>
        <?php
    } else {
        echo "<p class='no-notices'>".$lang['user_no_notices'] ?? 'No latest notices at the moment.'."</p>";
    }
    ?>
</section>

<?php include 'components/footer.php'; ?>

<script>
    // Hero Carousel
    const slides = document.querySelectorAll('.slide');
    let currentSlide = 0;

    const showSlide = index => {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
    };

    document.querySelector('.next').addEventListener('click', () => {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    });

    document.querySelector('.prev').addEventListener('click', () => {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
    });

    // Auto slide every 10 seconds
    setInterval(() => {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }, 10000);

    // Swipe support for mobile
    let startX = 0;
    const carousel = document.querySelector('.carousel');
    carousel.addEventListener('touchstart', e => startX = e.touches[0].clientX);
    carousel.addEventListener('touchend', e => {
        let diffX = e.changedTouches[0].clientX - startX;
        if(diffX > 50) currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        if(diffX < -50) currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    });

    // Lightbox for clickable images
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const closeBtn = document.querySelector('.lightbox .close');

    document.querySelectorAll('.clickable').forEach(img => {
        img.addEventListener('click', (e) => {
            e.preventDefault(); // prevent following card link
            lightbox.style.display = 'flex';
            lightboxImg.src = img.src;
            lightboxCaption.innerText = img.alt;
        });
    });

    closeBtn.addEventListener('click', () => lightbox.style.display = 'none');
    lightbox.addEventListener('click', e => {
        if(e.target === lightbox) lightbox.style.display = 'none';
    });

    // Hamburger menu toggle
    const hamburger = document.getElementById('hamburger');
    const mainNav = document.getElementById('main-nav');

    hamburger.addEventListener('click', () => {
        mainNav.classList.toggle('nav-active');
    });

</script>
</body>
</html>

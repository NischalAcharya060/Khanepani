<?php
session_start();
include 'config/database/db.php';
include 'config/Nepali_calendar.php';

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] === 'np' ? 'np' : 'en';
    $_SESSION['lang'] = $lang;
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

include "lang/" . $_SESSION['lang'] . ".php";

$cal = new Nepali_Calendar();

function format_date($date_str, $cal, $lang) {
    $timestamp = strtotime($date_str);

    if ( ($_SESSION['lang'] ?? 'en') === 'np' ) {
        $year  = (int)date('Y', $timestamp);
        $month = (int)date('m', $timestamp);
        $day   = (int)date('d', $timestamp);
        $hour  = (int)date('h', $timestamp);
        $minute = (int)date('i', $timestamp);
        $ampm = date('A', $timestamp);

        $nepDate = $cal->eng_to_nep($year, $month, $day);
        $np_numbers = ['0'=>'०','1'=>'१','2'=>'२','3'=>'३','4'=>'४','5'=>'५','6'=>'६','7'=>'७','8'=>'८','9'=>'९'];

        $np_month_name = $lang['month_' . $nepDate['month']] ?? $nepDate['month'];

        $dateNep = strtr($nepDate['date'] . ' ' . $np_month_name . ', ' . $nepDate['year'], $np_numbers);
        $timeNep = strtr(sprintf("%02d:%02d", $hour, $minute), $np_numbers) . " " . $ampm;

        return 'मिति: ' . $dateNep . ' (' . $timeNep . ')';
    } else {
        return date("F j, Y, h:i A", $timestamp);
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="developer" content="Developed by Nischal Acharya">

    <title><?= $lang['logo'] ?? 'Salakpur KhanePani Office' ?></title>
    <meta name="description" content="Official website of Salakpur KhanePani Office — providing reliable water services, online payments, notices, and updates for the local community.">
    <meta name="keywords" content="Salakpur, KhanePani, Water Supply, Office, Nepal, Online Payment, Drinking Water">
    <meta name="author" content="Nischal Acharya">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://khanepani-86760.wasmer.app/">

    <meta name="msapplication-TileColor" content="#2b5797">
    <meta name="theme-color" content="#ffffff">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" />

    <link rel="icon" type="image/png" sizes="96x96" href="assets/images/favicon/favicon-96x96.png">
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon/favicon.svg">
    <link rel="shortcut icon" href="assets/images/favicon/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/favicon/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-title" content="Salakpur KhanePani Office">
    <link rel="manifest" href="assets/images/favicon/site.webmanifest">
</head>
<style>
    :root {
        --primary-color: #0056d6;
        --secondary-color: #0a2a66;
        --accent-color: #ff3366;
        --text-dark: #333;
        --text-light: #555;
        --bg-light: #f9f9f9;
        --shadow-subtle: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .hero {
        position: relative;
        overflow: hidden;
        aspect-ratio: 16 / 7;
        max-height: 700px;
        border-radius: 10px;
        margin-bottom: 30px;
        box-shadow: var(--shadow-subtle);
    }
    .carousel {
        position: relative;
        width: 100%;
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
        display: block;
    }
    .slide a {
        display: block;
        width: 100%;
        height: 100%;
        text-decoration: none;
        position: relative;
    }
    .caption {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
        color: #fff;
        padding: 60px 20px 20px;
        font-size: 1.2rem;
        text-align: center;
        border-radius: 0 0 10px 10px;
    }

    .carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        border: 2px solid rgba(255, 255, 255, 0.8);
        font-size: 24px;
        padding: 10px 18px;
        cursor: pointer;
        border-radius: 50%;
        z-index: 2;
        transition: all 0.3s ease;
    }
    .carousel-btn:hover {
        background: rgba(255, 255, 255, 0.4);
        border-color: #fff;
    }
    .prev { left: 20px; }
    .next { right: 20px; }

    .carousel-indicators {
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 2;
        display: flex;
        gap: 8px;
    }
    .indicator-dot {
        width: 10px;
        height: 10px;
        background-color: rgba(255, 255, 255, 0.5);
        border-radius: 50%;
        cursor: pointer;
        transition: background-color 0.3s, transform 0.3s;
    }
    .indicator-dot.active {
        background-color: var(--primary-color);
        transform: scale(1.2);
        border: 2px solid #fff;
    }

    .quick-links {
        margin-bottom: 50px;
    }
    .quick-links-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    .quick-link-card {
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: var(--shadow-subtle);
        border: 1px solid #eee;
        text-decoration: none;
        color: var(--secondary-color);
    }
    .quick-link-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-color: var(--primary-color);
    }
    .quick-link-card i {
        font-size: 3rem;
        color: var(--primary-color);
        margin-bottom: 15px;
        display: block;
    }
    .quick-link-card h3 {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0;
    }

    .latest-notices {
        margin: 50px auto;
        padding: 20px 0;
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

    .notice-grid {
        display: grid;
        grid-template-columns: 1fr 1px 1fr;
        gap: 40px;
        align-items: start;
    }

    .notice-column {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

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

<section class="hero">
    <div class="carousel">
        <div class="slide active" data-index="0">
            <a href="assets/images/hero2.jpg" data-fancybox="hero-gallery" data-caption="<?= $lang['hero_caption1'] ?? 'Our Water Supply Container' ?>">
                <img src="assets/images/hero2.jpg" alt="<?= $lang['hero_caption1'] ?? 'Our Water Supply Container' ?>" class="clickable">
                <div class="caption"><?= $lang['hero_caption1'] ?? 'Our Water Supply Container' ?></div>
            </a>
        </div>
        <div class="slide" data-index="1">
            <a href="assets/images/hero.jpg" data-fancybox="hero-gallery" data-caption="<?= $lang['hero_caption2'] ?? 'Serving the Community' ?>">
                <img src="assets/images/hero.jpg" alt="<?= $lang['hero_caption2'] ?? 'Serving the Community' ?>" class="clickable">
                <div class="caption"><?= $lang['hero_caption2'] ?? 'Serving the Community' ?></div>
            </a>
        </div>
        <div class="slide" data-index="2">
            <a href="assets/images/hero1.jpg" data-fancybox="hero-gallery" data-caption="<?= $lang['hero_caption3'] ?? 'Clean & Safe Drinking Water' ?>">
                <img src="assets/images/hero1.jpg" alt="<?= $lang['hero_caption3'] ?? 'Clean & Safe Drinking Water' ?>" class="clickable">
                <div class="caption"><?= $lang['hero_caption3'] ?? 'Clean & Safe Drinking Water' ?></div>
            </a>
        </div>
        <button class="carousel-btn prev" aria-label="Previous Slide">&#10094;</button>
        <button class="carousel-btn next" aria-label="Next Slide">&#10095;</button>

        <div class="carousel-indicators">
        </div>
    </div>
</section>

<section class="quick-links container">
    <div class="quick-links-grid">
        <a href="#" class="quick-link-card">
            <i class="fas fa-money-bill-wave"></i>
            <h3><?= $lang['pay_bill'] ?? 'Pay Bill Online' ?></h3>
        </a>
        <a href="#" class="quick-link-card">
            <i class="fas fa-file-invoice"></i>
            <h3><?= $lang['check_status'] ?? 'Check Bill/Account Status' ?></h3>
        </a>
        <a href="#" class="quick-link-card">
            <i class="fas fa-faucet-drip"></i>
            <h3><?= $lang['report_outage'] ?? 'Report Leak/Outage' ?></h3>
        </a>
        <a href="contact.php" class="quick-link-card">
            <i class="fas fa-headset"></i>
            <h3><?= $lang['contact_us'] ?? 'Contact & Support' ?></h3>
        </a>
    </div>
</section>

<section class="latest-notices container">
    <h2>📢 <?= $lang['latest_notices'] ?? 'Latest Notices' ?></h2>

    <?php
    $sql = "SELECT * FROM notices ORDER BY created_at DESC LIMIT 6";
    $result = mysqli_query($conn, $sql);

    $notices = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notices[] = $row;
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
                            <span class="notice-date"><?= format_date($row['created_at'], $cal, $lang) ?></span>
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
                            <span class="notice-date"><?= format_date($row['created_at'], $cal, $lang) ?></span>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>

<script>
    const carousel = document.querySelector('.carousel');
    const slides = document.querySelectorAll('.slide');
    const indicatorsContainer = document.querySelector('.carousel-indicators');
    const totalSlides = slides.length;
    let currentSlide = 0;

    for (let i = 0; i < totalSlides; i++) {
        const dot = document.createElement('div');
        dot.classList.add('indicator-dot');
        dot.dataset.index = i;
        indicatorsContainer.appendChild(dot);
    }
    const indicatorDots = document.querySelectorAll('.indicator-dot');

    const showSlide = index => {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        indicatorDots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    };

    showSlide(currentSlide);

    document.querySelector('.next').addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    });

    document.querySelector('.prev').addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
        showSlide(currentSlide);
    });

    indicatorDots.forEach(dot => {
        dot.addEventListener('click', () => {
            currentSlide = parseInt(dot.dataset.index);
            showSlide(currentSlide);
        });
    });

    setInterval(() => {
        currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    }, 10000);

    let startX = 0;
    carousel.addEventListener('touchstart', e => startX = e.touches[0].clientX);
    carousel.addEventListener('touchend', e => {
        let diffX = e.changedTouches[0].clientX - startX;
        if(diffX > 50) currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
        if(diffX < -50) currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    });

    $(document).ready(function() {
        $('[data-fancybox="hero-gallery"]').fancybox({
            buttons : [
                'zoom',
                'slideShow',
                'fullScreen',
                'thumbs',
                'close'
            ],
            loop: true,
        });
    });

    const hamburger = document.getElementById('hamburger');
    const mainNav = document.getElementById('main-nav');

    if (hamburger && mainNav) {
        hamburger.addEventListener('click', () => {
            mainNav.classList.toggle('nav-active');
        });
    }

</script>
</body>
</html>
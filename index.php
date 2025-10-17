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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
        --secondary: #0f172a;
        --accent: #f59e0b;
        --success: #10b981;
        --danger: #ef4444;
        --light: #f8fafc;
        --dark: #1e293b;
        --gray: #64748b;
        --border: #e2e8f0;
        --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        --gradient: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        line-height: 1.6;
        color: var(--dark);
        background: var(--light);
        overflow-x: hidden;
    }

    .container {
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .hero {
        position: relative;
        overflow: hidden;
        aspect-ratio: 16 / 7;
        max-height: 700px;
        border-radius: 20px;
        margin: 30px auto;
        box-shadow: var(--shadow-lg);
        background: var(--secondary);
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
        transform: scale(1.1);
        transition: all 1.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .slide.active {
        opacity: 1;
        transform: scale(1);
        z-index: 1;
    }

    .slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 20px;
        display: block;
    }

    .slide a {
        display: block;
        width: 100%;
        height: 100%;
        text-decoration: none;
        position: relative;
    }

    .slide::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.2) 100%);
        z-index: 1;
        border-radius: 20px;
    }

    .caption {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        color: #fff;
        padding: 80px 40px 30px;
        font-size: 1.5rem;
        font-weight: 600;
        text-align: center;
        z-index: 2;
        border-radius: 0 0 20px 20px;
        transform: translateY(20px);
        opacity: 0;
        transition: all 0.8s ease 0.3s;
    }

    .slide.active .caption {
        transform: translateY(0);
        opacity: 1;
    }

    .carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.95);
        color: var(--primary);
        border: none;
        font-size: 24px;
        width: 60px;
        height: 60px;
        cursor: pointer;
        border-radius: 50%;
        z-index: 3;
        transition: all 0.3s ease;
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .carousel-btn:hover {
        background: white;
        transform: translateY(-50%) scale(1.1);
        box-shadow: var(--shadow-lg);
    }

    .prev { left: 30px; }
    .next { right: 30px; }

    .carousel-indicators {
        position: absolute;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 3;
        display: flex;
        gap: 12px;
    }

    .indicator-dot {
        width: 14px;
        height: 14px;
        background-color: rgba(255, 255, 255, 0.6);
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .indicator-dot.active {
        background-color: var(--accent);
        transform: scale(1.3);
        border-color: white;
    }

    .quick-links {
        margin: 80px auto;
    }

    .section-title {
        text-align: center;
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--secondary);
        margin-bottom: 50px;
        position: relative;
    }

    .section-title::after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: var(--gradient);
        border-radius: 2px;
    }

    .quick-links-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
    }

    .quick-link-card {
        background: white;
        padding: 40px 30px;
        border-radius: 20px;
        text-align: center;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: var(--shadow);
        border: 2px solid transparent;
        text-decoration: none;
        color: var(--secondary);
        position: relative;
        overflow: hidden;
    }

    .quick-link-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: var(--gradient);
        opacity: 0.05;
        transition: left 0.6s ease;
    }

    .quick-link-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary);
    }

    .quick-link-card:hover::before {
        left: 0;
    }

    .quick-link-card i {
        font-size: 3.5rem;
        background: var(--gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 20px;
        display: block;
        transition: transform 0.3s ease;
    }

    .quick-link-card:hover i {
        transform: scale(1.1) rotate(5deg);
    }

    .quick-link-card h3 {
        font-size: 1.4rem;
        font-weight: 600;
        margin: 0;
        position: relative;
        z-index: 1;
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

    .floating-buttons {
        position: fixed;
        bottom: 30px;
        right: 30px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        z-index: 1000;
    }

    .floating-btn {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--gradient);
        color: white;
        border: none;
        cursor: pointer;
        box-shadow: var(--shadow-lg);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        opacity: 0;
        transform: scale(0);
    }

    .floating-btn.show {
        opacity: 1;
        transform: scale(1);
    }

    .floating-btn:hover {
        transform: scale(1.1) rotate(10deg);
    }

    @media (max-width: 1024px) {
        .notice-grid {
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .notice-separator {
            display: none;
        }

        .hero {
            aspect-ratio: 16 / 9;
        }

        .caption {
            font-size: 1.2rem;
            padding: 60px 20px 20px;
        }
    }

    @media (max-width: 768px) {
        .section-title {
            font-size: 2rem;
        }

        .quick-links-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .carousel-btn {
            width: 50px;
            height: 50px;
            font-size: 20px;
        }

        .prev { left: 15px; }
        .next { right: 15px; }

        .floating-buttons {
            bottom: 20px;
            right: 20px;
        }

        .floating-btn {
            width: 50px;
            height: 50px;
            font-size: 1rem;
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

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.8s ease-out;
    }
</style>
<body>

<?php include 'components/header.php'; ?>

<div class="floating-buttons">
    <button class="floating-btn" onclick="scrollToTop()" title="Scroll to Top">
        <i class="fas fa-arrow-up"></i>
    </button>
</div>

<section class="hero animate-fade-in-up">
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

<section class="quick-links container animate-fade-in-up">
    <h2 class="section-title"><?= $lang['quick_links'] ?? 'Quick Access' ?></h2>
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
        echo "<p class='no-notices'>".($lang['user_no_notices'] ?? 'No latest notices at the moment.')."</p>";
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
    let autoSlideInterval;

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

    const nextSlide = () => {
        currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    };

    const prevSlide = () => {
        currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
        showSlide(currentSlide);
    };

    showSlide(currentSlide);

    document.querySelector('.next').addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        nextSlide();
        resetAutoSlide();
    });

    document.querySelector('.prev').addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        prevSlide();
        resetAutoSlide();
    });

    indicatorDots.forEach(dot => {
        dot.addEventListener('click', () => {
            currentSlide = parseInt(dot.dataset.index);
            showSlide(currentSlide);
            resetAutoSlide();
        });
    });

    const startAutoSlide = () => {
        autoSlideInterval = setInterval(nextSlide, 8000);
    };

    const resetAutoSlide = () => {
        clearInterval(autoSlideInterval);
        startAutoSlide();
    };

    startAutoSlide();

    let startX = 0;
    carousel.addEventListener('touchstart', e => startX = e.touches[0].clientX);
    carousel.addEventListener('touchend', e => {
        let diffX = e.changedTouches[0].clientX - startX;
        if(diffX > 50) prevSlide();
        if(diffX < -50) nextSlide();
        resetAutoSlide();
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

    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    window.addEventListener('scroll', () => {
        const scrollBtn = document.querySelector('.floating-btn');
        if (window.scrollY > 500) {
            scrollBtn.classList.add('show');
        } else {
            scrollBtn.classList.remove('show');
        }
    });

    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.8s ease-out forwards';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.animate-fade-in-up').forEach(el => {
        observer.observe(el);
    });
</script>
</body>
</html>
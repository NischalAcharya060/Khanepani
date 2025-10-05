<?php
//// Start session if not already started
//if (session_status() === PHP_SESSION_NONE) {
//    session_start();
//}

// Language handling
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

if (isset($_GET['lang'])) {
    $langCode = $_GET['lang'];
    if (in_array($langCode, ['en', 'np'])) {
        $_SESSION['lang'] = $langCode;
    }
}

$langFile = __DIR__ . '/../lang/' . $_SESSION['lang'] . '.php';
if (file_exists($langFile)) {
    include $langFile;
} else {
    include __DIR__ . '/../lang/en.php';
}


?>

<!-- ✅ Add inside <head> -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Info Bar -->
<div class="info-bar">
    <section class="datetime-bar">
        <div class="container">
            <span id="live-datetime"></span>
        </div>
    </section>
    <div class="container info-bar-content">
        <span><i class="fa-solid fa-phone"></i> +977 1 4117356, 4117358</span>
        <span><i class="fa-solid fa-envelope"></i> info@salakpurkhanepani.com</span>
    </div>
</div>

<!-- Header -->
<header>
    <div class="container header-container">
        <!-- Logo -->
        <div class="logo">
            <a href="../index.php">
                <img src="../assets/images/logo.jpg" alt="Khane Pani Logo">
                <span class="logo-text"><?= $lang['logo'] ?></span>
            </a>
        </div>

        <!-- Hamburger (visible only on mobile) -->
        <div class="hamburger" id="hamburger">
            <i class="fa-solid fa-bars"></i>
        </div>

        <!-- Navigation -->
        <nav class="main-nav" id="main-nav">
            <a href="../index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
                <?= $lang['home'] ?? 'Home' ?>
            </a>
            <a href="../notices.php" class="<?= $current_page == 'notices.php' ? 'active' : '' ?>">
                <?= $lang['notices'] ?? 'Notices' ?>
            </a>
            <div class="dropdown">
                <a href="javascript:void(0)" class="dropbtn">
                    <?= $lang['about'] ?? 'About' ?>
                </a>
                <div class="dropdown-content">
                    <a href="../about_us.php"><?= $lang['about_us'] ?? 'About Us' ?></a>
                    <a href="../our_services.php"><?= $lang['our_services'] ?? 'Our Services' ?></a>
                </div>
            </div>
            <div class="dropdown">
                <a href="javascript:void(0)" class="dropbtn">
                    <?= $lang['resources'] ?? 'Resources' ?>
                </a>
                <div class="dropdown-content">
                    <a href="../gallery.php"><?= $lang['user_gallery'] ?? 'Gallery' ?></a>
                    <a href="../nepali_unicode.php"><?= $lang['nepali_unicode'] ?? 'Nepali Unicode' ?></a>
                </div>
            </div>

            <a href="../contact.php" class="<?= $current_page == 'contact.php' ? 'active' : '' ?>">
                <?= $lang['contact'] ?? 'Contact' ?>
            </a>

            <!-- Utilities move inside nav for mobile -->
            <div class="header-utilities">
                <a href="../admin/login.php" class="employee-btn">
                    <i class="fa-solid fa-user-shield"></i> <?= $lang['employee_portal'] ?? 'Employee Portal' ?>
                </a>

                <div class="lang-switcher">
                    <a href="?lang=en" class="lang-link <?= ($_SESSION['lang'] ?? 'en') == 'en' ? 'active-lang' : '' ?>">
                        <img src="../assets/images/gb.webp" alt="EN" class="flag-icon"> EN
                    </a>
                    <a href="?lang=np" class="lang-link <?= ($_SESSION['lang'] ?? 'en') == 'np' ? 'active-lang' : '' ?>">
                        <img src="../assets/images/np.png" alt="NP" class="flag-icon"> NP
                    </a>
                </div>
            </div>
        </nav>
    </div>
</header>

<style>
    /* =====================
       INFO BAR
    ===================== */
    .info-bar {
        background: linear-gradient(90deg, #004080, #0066cc);
        color: #ffffff;
        padding: 8px 0;
        font-size: 14px;
    }
    .info-bar .info-bar-content {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        flex-wrap: wrap;
    }
    @media (max-width: 600px) {
        .info-bar {
            font-size: 12px;
            text-align: center;
        }
        .info-bar .info-bar-content {
            justify-content: center;
        }
    }

    /* =====================
       HEADER
    ===================== */
    header {
        background: #fff;
        padding: 12px 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        position: sticky;
        top: 0;
        z-index: 999;
    }
    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Logo */
    .logo a {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #004080;
        font-weight: 700;
        font-size: 20px;
    }
    .logo a img {
        width: 55px;
        margin-right: 10px;
        border-radius: 8px;
    }

    /* =====================
       NAVIGATION
    ===================== */
    .main-nav {
        display: flex;
        gap: 25px;
        align-items: center;
    }
    .main-nav a {
        text-decoration: none;
        color: #004080;
        font-weight: 500;
        padding: 6px 0;
        position: relative;
    }
    .main-nav a::after {
        content: "";
        display: block;
        height: 2px;
        background: #ff6600;
        width: 0;
        transition: width 0.3s;
        position: absolute;
        bottom: -3px;
        left: 0;
    }
    .main-nav a:hover::after {
        width: 100%;
    }

    /* Dropdown */
    .dropdown {
        position: relative;
    }
    .dropbtn { cursor: pointer; }
    .dropdown-content {
        display: none;
        position: absolute;
        top: 120%;
        left: 0;
        background: #fff;
        min-width: 160px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        border-radius: 6px;
    }
    .dropdown-content a {
        display: block;
        padding: 10px 14px;
        color: #333;
    }
    .dropdown-content a:hover {
        background: #ff6600;
        color: #fff;
    }
    .dropdown:hover .dropdown-content {
        display: block;
    }

    /* =====================
       HAMBURGER
    ===================== */
    .hamburger {
        display: none;
        font-size: 26px;
        cursor: pointer;
        color: #004080;
    }

    /* =====================
       UTILITIES
    ===================== */
    .header-utilities {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    /* Fix Employee Portal Button */
    .employee-btn {
        display: inline-flex !important;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #007bff, #0056d6) !important;
        color: #fff !important;
        padding: 8px 14px !important;
        border-radius: 8px !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        text-decoration: none !important;
        border: none !important;
        box-shadow: none !important;
        position: relative;
    }

    /* Remove orange underline effect inside nav */
    .employee-btn::after {
        display: none !important;
    }

    /* Fix Language Switcher */
    .lang-link {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 4px 8px !important;
        border-radius: 20px !important;
        color: #444 !important;
        font-weight: 500 !important;
        text-decoration: none !important;
    }
    .lang-link:hover {
        background: #f0f0f0 !important;
        color: #1a1a1a !important;
    }
    .lang-link.active-lang {
        background: #0056d6 !important;
        color: #fff !important;
    }
    .flag-icon {
        width: 18px;
        height: 12px;
        object-fit: cover;
    }

    /* =====================
       RESPONSIVE
    ===================== */
    @media (max-width: 992px) {
        .main-nav {
            display: none;
            flex-direction: column;
            position: absolute;
            top: 70px;
            right: 20px;
            width: 250px;
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }
        .main-nav.show { display: flex; }
        .main-nav a { border-bottom: 1px solid #eee; padding: 10px 0; }
        .main-nav a:last-child { border-bottom: none; }

        /* dropdown inside mobile */
        .dropdown-content { display: none; position: static; box-shadow: none; background: #f9f9f9; }
        .dropdown-content.show-dropdown { display: block; }
        .dropbtn::after { content: " ▾"; font-size: 12px; }

        .hamburger { display: block; }

        /* utilities stack under nav */
        .header-utilities {
            flex-direction: column;
            align-items: flex-start;
            margin-top: 10px;
            gap: 10px;
        }
    }
    @media (max-width: 480px) {
        .logo a span { font-size: 16px; }
        .lang-switcher { flex-wrap: wrap; }
        .lang-link { flex: 1; justify-content: center; }
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const hamburger = document.getElementById("hamburger");
        const nav = document.getElementById("main-nav");

        hamburger.addEventListener("click", function () {
            nav.classList.toggle("show");
            this.innerHTML = nav.classList.contains("show")
                ? '<i class="fa-solid fa-xmark"></i>'
                : '<i class="fa-solid fa-bars"></i>';
        });

        // Mobile dropdown toggle
        const dropdowns = document.querySelectorAll(".dropdown > .dropbtn");
        dropdowns.forEach((btn) => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                const dropdownContent = this.nextElementSibling;
                dropdownContent.classList.toggle("show-dropdown");
            });
        });

        const datetimeElement = document.getElementById('live-datetime');

        function updateLiveDateTime() {
            fetch('../utils/get_live_datetime.php')
                .then(response => response.text())
                .then(data => {
                    datetimeElement.textContent = data;
                })
                .catch(error => {
                    console.error('Error fetching live datetime:', error);
                    datetimeElement.textContent = 'Date/Time Error';
                });
        }

        updateLiveDateTime();
        setInterval(updateLiveDateTime, 1000);

    });
</script>

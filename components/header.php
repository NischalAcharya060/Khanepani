<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
include 'config/database/db.php';

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

// Fetch settings from database
$settings = [
        'email' => 'info@salakpurkhanepani.com',
        'phone' => '+977-1-4117356',
        'map_embed' => 'map_embed',
];

$sql = "SELECT email, phone, facebook_link, map_embed FROM settings WHERE id = 1 LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['site_title'] ?? 'Khane Pani' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="info-bar">
    <section class="datetime-bar">
        <div class="container">
            <span id="live-datetime"></span>
        </div>
    </section>
    <div class="container info-bar-content">
        <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($settings['phone']) ?></span>
        <span><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($settings['email']) ?></span>
    </div>
</div>

<header>
    <div class="container header-container">
        <div class="logo">
            <a href="../index.php">
                <img src="../assets/images/logo.jpg" alt="Khane Pani Logo">
                <span class="logo-text"><?= $lang['logo'] ?></span>
            </a>
        </div>

        <div class="hamburger" id="hamburger" aria-label="Toggle navigation">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </div>

        <nav class="main-nav" id="main-nav" aria-label="Main navigation">
            <a href="../index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <?= $lang['home'] ?? 'Home' ?>
            </a>
            <a href="../notices.php" class="nav-link <?= $current_page == 'notices.php' ? 'active' : '' ?>">
                <?= $lang['notices'] ?? 'Notices' ?>
            </a>

            <div class="dropdown" id="about-dropdown">
                <button class="dropbtn nav-link" aria-expanded="false">
                    <?= $lang['about'] ?? 'About' ?> <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </button>
                <div class="dropdown-content">
                    <a href="../about_us.php"><i class="fa-solid fa-info-circle"></i> <?= $lang['about_us'] ?? 'About Us' ?></a>
                    <a href="../our_services.php"><i class="fa-solid fa-faucet-drip"></i> <?= $lang['our_services'] ?? 'Our Services' ?></a>
                </div>
            </div>

            <div class="dropdown" id="resources-dropdown">
                <button class="dropbtn nav-link" aria-expanded="false">
                    <?= $lang['resources'] ?? 'Resources' ?> <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </button>
                <div class="dropdown-content">
                    <a href="../gallery.php"><i class="fa-solid fa-images"></i> <?= $lang['user_gallery'] ?? 'Gallery' ?></a>
                    <a href="../nepali_unicode.php"><i class="fa-solid fa-keyboard"></i> <?= $lang['nepali_unicode'] ?? 'Nepali Unicode' ?></a>
                </div>
            </div>

            <a href="../contact.php" class="nav-link <?= $current_page == 'contact.php' ? 'active' : '' ?>">
                <?= $lang['contact'] ?? 'Contact' ?>
            </a>

            <div class="header-utilities">
                <a href="../admin/login.php" class="employee-btn">
                    <i class="fa-solid fa-user-shield"></i> <?= $lang['employee_portal'] ?? 'Employee Portal' ?>
                </a>

                <div class="lang-switcher">
                    <a href="?lang=en" class="lang-link <?= ($_SESSION['lang'] ?? 'en') == 'en' ? 'active-lang' : '' ?>" aria-label="Switch to English">
                        <img src="../assets/images/gb.webp" alt="EN" class="flag-icon"> EN
                    </a>
                    <a href="?lang=np" class="lang-link <?= ($_SESSION['lang'] ?? 'en') == 'np' ? 'active-lang' : '' ?>" aria-label="Switch to Nepali">
                        <img src="../assets/images/np.png" alt="NP" class="flag-icon"> NP
                    </a>
                </div>
            </div>
        </nav>
    </div>
</header>

<style>
    /* =====================
       VARIABLES
    ===================== */
    :root {
        --primary-color: #004080;
        --secondary-color: #ff6600;
        --accent-color: #0056d6;
        --light-color: #ffffff;
        --dark-color: #333333;
        --gray-light: #f5f5f5;
        --gray-medium: #e0e0e0;
        --shadow: 0 2px 8px rgba(0,0,0,0.08);
        --shadow-heavy: 0 6px 15px rgba(0,0,0,0.15);
        --border-radius: 8px;
        --transition: all 0.3s ease;
    }

    /* =====================
       INFO BAR
    ===================== */
    .info-bar {
        background: linear-gradient(90deg, var(--primary-color), #0066cc);
        color: var(--light-color);
        padding: 3px 0;
        font-size: 14px;
    }

    .datetime-bar {
        background: rgba(0, 0, 0, 0.1);
        padding: 2px 0;
        text-align: center;
    }

    .info-bar-content {
        display: flex;
        justify-content: flex-end;
        gap: 20px;
        flex-wrap: wrap;
        padding: 5px 0;
    }

    .info-bar-content span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* =====================
       HEADER
    ===================== */
    header {
        background: var(--light-color);
        padding: 12px 0;
        box-shadow: var(--shadow);
        position: sticky;
        top: 0;
        z-index: 1000;
        transition: var(--transition);
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
    }

    /* Logo */
    .logo a {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: var(--primary-color);
        font-weight: 700;
        font-size: 20px;
        transition: var(--transition);
    }

    .logo a:hover {
        transform: translateY(-2px);
    }

    .logo a img {
        width: 55px;
        height: 55px;
        margin-right: 12px;
        border-radius: var(--border-radius);
        object-fit: cover;
    }

    /* =====================
       NAVIGATION
    ===================== */
    .main-nav {
        display: flex;
        gap: 25px;
        align-items: center;
        transition: var(--transition);
    }

    .nav-link {
        text-decoration: none;
        color: var(--primary-color);
        font-weight: 500;
        padding: 8px 0;
        position: relative;
        transition: var(--transition);
        display: flex;
        align-items: center;
    }

    .nav-link.active {
        color: var(--secondary-color);
        font-weight: 600;
    }

    .nav-link::after {
        content: "";
        display: block;
        height: 2px;
        background: var(--secondary-color);
        width: 0;
        transition: var(--transition);
        position: absolute;
        bottom: -3px;
        left: 0;
    }

    .nav-link:hover::after,
    .nav-link.active::after {
        width: 100%;
    }

    /* =====================
       DROPDOWN
    ===================== */
    .dropdown {
        position: relative;
    }

    .dropbtn {
        cursor: pointer;
        display: flex;
        align-items: center;
        background: none;
        border: none;
        font: inherit;
        color: inherit;
        padding: 8px 0;
    }

    .dropdown-arrow {
        font-size: 10px;
        margin-left: 5px;
        transition: transform 0.3s ease;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background: var(--light-color);
        min-width: 200px;
        box-shadow: var(--shadow-heavy);
        border-radius: var(--border-radius);
        z-index: 10;
        padding: 10px 0;
        opacity: 0;
        transform: translateY(-10px);
        transition: opacity 0.3s, transform 0.3s;
    }

    .dropdown-content a {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        color: var(--dark-color);
        white-space: nowrap;
        text-decoration: none;
        transition: var(--transition);
    }

    .dropdown-content a i {
        margin-right: 10px;
        width: 16px;
        text-align: center;
    }

    .dropdown-content a:hover {
        background: var(--secondary-color);
        color: var(--light-color);
    }

    /* Desktop Hover Activation */
    @media (min-width: 993px) {
        .dropdown:hover .dropdown-content {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown:hover .dropdown-arrow {
            transform: rotate(180deg);
        }
    }

    /* =====================
       HAMBURGER
    ===================== */
    .hamburger {
        display: none;
        flex-direction: column;
        justify-content: space-between;
        width: 24px;
        height: 18px;
        cursor: pointer;
        z-index: 1001;
    }

    .hamburger-line {
        display: block;
        height: 2px;
        width: 100%;
        background-color: var(--primary-color);
        border-radius: 1px;
        transition: var(--transition);
    }

    .hamburger.active .hamburger-line:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }

    .hamburger.active .hamburger-line:nth-child(2) {
        opacity: 0;
    }

    .hamburger.active .hamburger-line:nth-child(3) {
        transform: rotate(-45deg) translate(7px, -6px);
    }

    /* =====================
       UTILITIES
    ===================== */
    .header-utilities {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .employee-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, var(--accent-color), #007bff);
        color: var(--light-color);
        padding: 8px 16px;
        border-radius: var(--border-radius);
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: var(--transition);
        box-shadow: 0 2px 5px rgba(0, 86, 214, 0.3);
    }

    .employee-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 86, 214, 0.4);
    }

    .lang-switcher {
        display: flex;
        gap: 5px;
        background: var(--gray-light);
        border-radius: 20px;
        padding: 2px;
    }

    .lang-link {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: 20px;
        color: var(--dark-color);
        font-weight: 500;
        text-decoration: none;
        transition: var(--transition);
    }

    .lang-link:hover {
        background: var(--gray-medium);
    }

    .lang-link.active-lang {
        background: var(--accent-color);
        color: var(--light-color);
    }

    .flag-icon {
        width: 18px;
        height: 12px;
        object-fit: cover;
        border-radius: 1px;
    }

    /* =====================
       RESPONSIVE DESIGN
    ===================== */
    @media (max-width: 992px) {
        .hamburger {
            display: flex;
        }

        .main-nav {
            display: none;
            flex-direction: column;
            position: fixed;
            top: 0;
            right: 0;
            width: 280px;
            height: 100vh;
            background: var(--light-color);
            padding: 80px 20px 20px;
            box-shadow: var(--shadow-heavy);
            overflow-y: auto;
            gap: 0;
        }

        .main-nav.show {
            display: flex;
        }

        .nav-link {
            width: 100%;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-medium);
        }

        .dropdown {
            width: 100%;
        }

        .dropbtn {
            width: 100%;
            justify-content: space-between;
            padding: 12px 0;
        }

        .dropdown-content {
            display: none;
            position: static;
            top: unset;
            left: unset;
            min-width: 100%;
            box-shadow: none;
            background: var(--gray-light);
            border-radius: 0;
            padding: 5px 0 5px 15px;
            opacity: 1;
            transform: none;
            transition: none;
        }

        .dropdown-content.show-dropdown {
            display: block;
        }

        .header-utilities {
            flex-direction: column;
            align-items: flex-start;
            margin-top: 20px;
            gap: 15px;
            width: 100%;
        }

        .employee-btn {
            width: 100%;
            justify-content: center;
        }

        .lang-switcher {
            width: 100%;
            justify-content: center;
        }

        .info-bar-content {
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .logo a span {
            font-size: 16px;
        }

        .logo a img {
            width: 45px;
            height: 45px;
            margin-right: 8px;
        }

        .main-nav {
            width: 100%;
        }

        .info-bar {
            font-size: 12px;
        }
    }

    /* =====================
       ACCESSIBILITY
    ===================== */
    @media (prefers-reduced-motion: reduce) {
        * {
            transition: none !important;
            animation: none !important;
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

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const hamburger = document.getElementById("hamburger");
        const nav = document.getElementById("main-nav");
        const body = document.body;

        // Hamburger/Navigation Toggle
        hamburger.addEventListener("click", function () {
            const isExpanded = nav.classList.toggle("show");
            hamburger.classList.toggle("active", isExpanded);
            hamburger.setAttribute("aria-expanded", isExpanded);

            // Prevent body scroll when menu is open
            body.style.overflow = isExpanded ? "hidden" : "";

            // Close all dropdowns when the main nav is closed
            if (!isExpanded) {
                document.querySelectorAll(".dropdown-content").forEach(content => {
                    content.classList.remove("show-dropdown");
                });
            }
        });

        // Mobile dropdown toggle
        const dropdowns = document.querySelectorAll(".dropdown > .dropbtn");
        dropdowns.forEach((btn) => {
            btn.addEventListener("click", function (e) {
                if (window.innerWidth <= 992) {
                    e.preventDefault();
                    e.stopPropagation();

                    const dropdownContent = this.nextElementSibling;
                    const isExpanded = this.getAttribute("aria-expanded") === "true";

                    // Close all other dropdowns
                    document.querySelectorAll(".dropdown-content").forEach(content => {
                        if (content !== dropdownContent) {
                            content.classList.remove("show-dropdown");
                        }
                    });

                    document.querySelectorAll(".dropbtn").forEach(button => {
                        if (button !== this) {
                            button.setAttribute("aria-expanded", "false");
                        }
                    });

                    // Toggle the current dropdown
                    dropdownContent.classList.toggle("show-dropdown");
                    this.setAttribute("aria-expanded", !isExpanded);
                }
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener("click", function(e) {
            if (window.innerWidth <= 992 && !e.target.closest(".dropdown") && !e.target.closest(".hamburger")) {
                document.querySelectorAll(".dropdown-content").forEach(content => {
                    content.classList.remove("show-dropdown");
                });

                document.querySelectorAll(".dropbtn").forEach(button => {
                    button.setAttribute("aria-expanded", "false");
                });
            }
        });

        // Live Datetime Update
        const datetimeElement = document.getElementById('live-datetime');

        function updateLiveDateTime() {
            if (!datetimeElement) return;

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

        // Close menu when clicking on a link (for single page applications)
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 992) {
                    nav.classList.remove('show');
                    hamburger.classList.remove('active');
                    body.style.overflow = '';
                }
            });
        });
    });
</script>
</body>
</html>
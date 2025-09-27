<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

<!-- Info Bar -->
<div class="info-bar">
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

        <!-- Navigation -->
        <nav class="main-nav" id="main-nav">
            <a href="../index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
                <?= $lang['home'] ?? 'Home' ?>
            </a>
            <a href="../notices.php" class="<?= $current_page == 'notices.php' ? 'active' : '' ?>">
                <?= $lang['user_notices'] ?? 'Notices' ?>
            </a>

            <div class="dropdown">
                <a href="javascript:void(0)" class="dropbtn">
                    <?= $lang['resources'] ?? 'Resources' ?> ▾
                </a>
                <div class="dropdown-content">
                    <a href="../gallery.php"><?= $lang['user_gallery'] ?? 'Gallery' ?></a>
                    <a href="../nepali_unicode.php"><?= $lang['nepali_unicode'] ?? 'Nepali Unicode' ?></a>
                </div>
            </div>

            <a href="../contact.php" class="<?= $current_page == 'contact.php' ? 'active' : '' ?>">
                <?= $lang['contact'] ?? 'Contact' ?>
            </a>
        </nav>

        <!-- Right Utilities -->
        <div class="header-utilities">
            <!-- Employee Portal -->
            <a href="../admin/login.php" class="employee-btn">
                <i class="fa-solid fa-user-shield"></i> <?= $lang['employee_portal'] ?? 'Employee Portal' ?>
            </a>

            <!-- Language Switcher -->
            <div class="lang-switcher">
                <a href="?lang=en" class="lang-link <?= ($_SESSION['lang'] ?? 'en') == 'en' ? 'active-lang' : '' ?>" title="English">
                    <img src="../assets/images/gb.webp" alt="EN" class="flag-icon">
                    <span>EN</span>
                </a>
                <a href="?lang=np" class="lang-link <?= ($_SESSION['lang'] ?? 'en') == 'np' ? 'active-lang' : '' ?>" title="नेपाली">
                    <img src="../assets/images/np.png" alt="NP" class="flag-icon">
                    <span>NP</span>
                </a>
            </div>

            <!-- Mobile Menu -->
            <div class="hamburger" id="hamburger">
                <i class="fa-solid fa-bars"></i>
            </div>
        </div>
    </div>
</header>

<style>
    /* Dropdown */
    .dropdown {
        position: relative;
    }
    .dropbtn {
        background: none;
        border: none;
        font: inherit;
        color: #333;
        font-weight: 500;
        cursor: pointer;
        padding: 8px 6px;
    }
    .dropdown-content {
        display: none;
        position: absolute;
        background: #fff;
        min-width: 160px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        border-radius: 8px;
        overflow: hidden;
    }
    .dropdown-content a {
        display: block;
        padding: 10px 14px;
        text-decoration: none;
        color: #333;
        font-size: 14px;
    }
    .dropdown-content a:hover {
        background: #f5f5f5;
    }
    .dropdown:hover .dropdown-content {
        display: block;
    }

    /* Utilities */
    .header-utilities {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    /* Employee Portal Button */
    .employee-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #007bff, #0056d6);
        color: #fff;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: background 0.3s ease, transform 0.2s ease;
    }
    .employee-btn:hover {
        background: linear-gradient(135deg, #0056d6, #003da8);
        transform: translateY(-2px);
    }

    /* Language Switcher */
    .lang-switcher {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .lang-link {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 20px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        color: #444;
        transition: background 0.3s ease, color 0.3s ease;
    }
    .lang-link:hover {
        background: #f0f0f0;
        color: #1a1a1a;
    }
    .lang-link.active-lang {
        background: #0056d6;
        color: #fff;
    }
    .flag-icon {
        width: 18px;
        height: 12px;
        object-fit: cover;
    }

    /* Hamburger */
    .hamburger {
        font-size: 22px;
        cursor: pointer;
        display: none; /* hidden on desktop */
    }
</style>
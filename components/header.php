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
    <div class="container">
        <div class="info-bar-content">
            <span>📞 +977 1 4117356, 4117358</span>
            <span>✉ info@salakpurkhanepani.com</span>
        </div>
    </div>
</div>

<!-- Header -->
<header>
    <div class="container header-container">
        <!-- Logo -->
        <div class="logo">
            <a href="../index.php">
                <img src="../assets/images/logo.jpg" alt="Khane Pani Logo">
                <span><?= $lang['logo'] ?></span>
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
                </div>
            </div>

            <a href="../contact.php" class="<?= $current_page == 'contact.php' ? 'active' : '' ?>">
                <?= $lang['contact'] ?? 'Contact' ?>
            </a>
        </nav>

        <!-- Language Switcher -->
        <div class="lang-switcher">
            <a href="?lang=en" class="lang-link <?= ($_SESSION['lang'] ?? 'en') == 'en' ? 'active-lang' : '' ?>" title="English" aria-label="Switch to English">
                <img src="../assets/images/gb.webp" alt="EN" class="flag-icon">
                <span class="lang-text">EN</span>
            </a>
            <a href="?lang=np" class="lang-link <?= ($_SESSION['lang'] ?? 'en') == 'np' ? 'active-lang' : '' ?>" title="नेपाली" aria-label="Switch to Nepali">
                <img src="../assets/images/np.png" alt="NP" class="flag-icon">
                <span class="lang-text">NP</span>
            </a>
        </div>

        <div class="hamburger" id="hamburger">&#9776;</div>
    </div>
</header>

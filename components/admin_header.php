<?php
$allowed_langs = ['en', 'np'];
$default_lang = 'en';

function isMobile() {
    return preg_match('/(android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile)/i', $_SERVER['HTTP_USER_AGENT']);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/database/db.php';

$generic_db_error = "An unrecoverable database error occurred. Please contact support.";

// Check database connection immediately
if (!isset($conn) || $conn->connect_error) {
    error_log("FATAL DB ERROR (Header Connection): " . ($conn->connect_error ?? "Unknown connection issue"));
    die($generic_db_error);
}

// --- Language Handling ---
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_langs)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$current_lang = $_SESSION['lang'] = $_SESSION['lang'] ?? $default_lang;
if (!in_array($current_lang, $allowed_langs)) {
    $current_lang = $_SESSION['lang'] = $default_lang;
}

include '../lang/' . $current_lang . '.php';

// Ensure user is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// --- Notification Fetch ---
$notif_query = "SELECT id, name, message, created_at FROM contact_messages WHERE is_read = 0 ORDER BY created_at DESC";
$notif_result = $conn->query($notif_query);
$unread_messages = [];

if ($notif_result !== false) {
    while ($row = $notif_result->fetch_assoc()) {
        $unread_messages[] = $row;
    }
}
$unread_count = count($unread_messages);

$admin_id = $_SESSION['admin'];
$username = $_SESSION['username'] ?? 'Admin';

$profile_pic_path = '../assets/profile/default.png';

$current_role_id = $_SESSION['role_id'] ?? 0;

if ($admin_id !== 'master') {
    $admin_id_int = intval($admin_id);
    $stmt = $conn->prepare("SELECT username, profile_pic, role_id FROM admins WHERE id = ?");

    if ($stmt !== false) {
        $stmt->bind_param("i", $admin_id_int);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $admin_data = $result->fetch_assoc();
            $stmt->close();

            if ($admin_data) {
                $username = $admin_data['username'] ?: 'Admin';
                $db_profile_pic = $admin_data['profile_pic'] ?? '';

                $current_role_id = $admin_data['role_id'] ?? 0;
                $_SESSION['role_id'] = $current_role_id;

                $uploaded_path_check = '../assets/uploads/profile/' . $db_profile_pic;

                if (!empty($db_profile_pic) && $db_profile_pic !== 'default.png' && file_exists($uploaded_path_check)) {
                    $profile_pic_path = $uploaded_path_check;
                }
            }
        }
    }
} else {
    $current_role_id = 1;
    $_SESSION['role_id'] = 1;
}

$current_page = basename($_SERVER['PHP_SELF']);

$sidebar_state = $_SESSION['sidebar_state'] ?? 'expanded';

$current_admin_id = $_SESSION['admin'] ?? '';
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<header class="admin-header">
    <div class="logo">
        <img src="../assets/images/logo.jpg" alt="Logo">
        <h1><?= $lang['logo'] ?></h1>
    </div>
    <div class="user-info">
        <div class="lang-switcher">
            <a href="?lang=en" class="lang-link <?= ($current_lang == 'en') ? 'active-lang' : '' ?>" title="English">
                <img src="../assets/images/gb.webp" alt="EN" class="flag-icon">
                <span>EN</span>
            </a>
            <a href="?lang=np" class="lang-link <?= ($current_lang == 'np') ? 'active-lang' : '' ?>" title="नेपाली">
                <img src="../assets/images/np.png" alt="NP" class="flag-icon">
                <span>NP</span>
            </a>
        </div>

        <div class="notification" id="notifBell">
            🔔
            <?php if ($unread_count > 0): ?>
                <span class="notif-badge"><?= $unread_count ?></span>
            <?php endif; ?>
        </div>

        <div class="profile-menu">
            <div class="profile-trigger" onclick="toggleProfileMenu()">
                <img src="<?= htmlspecialchars($profile_pic_path, ENT_QUOTES, 'UTF-8') ?>"
                     alt="Profile" class="profile-pic">
                <span><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
                <i class="arrow">&#9662;</i>
            </div>

            <div class="profile-dropdown" id="profileDropdown">
                <a href="../admin/profile.php">👤 <?= htmlspecialchars($lang['my_profile'] ?? 'My Profile', ENT_QUOTES, 'UTF-8') ?></a>
                <a href="../admin/logout.php" class="logout-link">🚪 <?= htmlspecialchars($lang['logout'] ?? 'Logout', ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </div>

        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    </div>
</header>

<div id="notifModal" class="notif-modal">
    <div class="notif-modal-content">
        <div class="notif-header">
            <h3><?= $lang['unread_messages'] ?? 'Unread Messages' ?></h3>
            <button class="close-btn" id="closeNotif">&times;</button>
        </div>

        <?php if ($unread_count > 0): ?>
            <button class="clear-btn" id="clearUnread">
                <i class="fa fa-check-double"></i> <?= $lang['clear_all'] ?? 'Clear All' ?>
            </button>
            <ul>
                <?php foreach ($unread_messages as $msg): ?>
                    <li onclick="window.location.href='view_message.php?id=<?= $msg['id'] ?>'">
                        <div class="msg-left">
                            <strong><?= htmlspecialchars($msg['name']) ?></strong>
                            <span class="time"><?= date("d M, h:i A", strtotime($msg['created_at'])) ?></span>
                        </div>
                        <div class="msg-right">
                            <?= htmlspecialchars(substr($msg['message'], 0, 50)) ?>...
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="no-messages"><?= $lang['no_unread'] ?? 'No unread messages.' ?></p>
        <?php endif; ?>
    </div>
</div>

<aside class="sidebar <?= $sidebar_state ?>" id="sidebar">
    <br>
    <div class="sidebar-top">
        <button class="collapse-toggle" onclick="toggleSidebarCollapse()">⮜</button>
    </div>
    <ul>
        <li><a href="../admin/dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">🏠 <span class="link-text"><?= $lang['dashboard'] ?></span></a></li>

        <li class="sidebar-group-separator"></li>

        <?php //= in_array($current_page, ['manage_notices.php', 'manage_gallery.php']) ? 'active' : '' ?><?php //= $lang['management'] ?? 'Management' ?><li>
            <a href="../admin/manage_notices.php" class="<?= $current_page == 'manage_notices.php' ? 'active' : '' ?>">
                <span class="sub-icon">📢</span> <span class="link-text"><?= $lang['manage_notices'] ?></span>
            </a>
        </li>
        <li class="sidebar-group-separator"></li>
        <li>
            <a href="../admin/manage_gallery.php" class="<?= $current_page == 'manage_gallery.php' ? 'active' : '' ?>">
                <span class="sub-icon">📸</span> <span class="link-text"><?= $lang['manage_gallery'] ?></span>
            </a>
        </li>

        <li class="sidebar-group-separator"></li>

        <?php
        if ($current_admin_id === 'master' || in_array($current_role_id, [1, 2])):
            ?>
            <li>
                <a href="../admin/manage_admins.php" class="<?= $current_page == 'manage_admins.php' ? 'active' : '' ?>">
                    👥 <span class="link-text"><?= $lang['manage_admins'] ?></span>
                </a>
            </li>
            <li class="sidebar-group-separator"></li>
        <?php endif; ?>
        <li><a href="../admin/messages.php" class="<?= $current_page == 'messages.php' ? 'active' : '' ?>">📬 <span class="link-text"><?= $lang['messages'] ?></span></a></li>
        <li class="sidebar-group-separator"></li>
        <li><a href="../admin/activity.php" class="<?= $current_page == 'activity.php' ? 'active' : '' ?>">🕒 <span class="link-text"><?= $lang['recent_activity'] ?></span></a></li>
        <li class="sidebar-group-separator"></li>
        <li><a href="../admin/settings.php" class="<?= $current_page == 'settings.php' ? 'active' : '' ?>">⚙ <span class="link-text"><?= $lang['settings'] ?></span></a></li>
        <li class="sidebar-group-separator"></li>
    </ul>
</aside>

<style>
    /* ================================
       VARIABLES & CORE SLIDING SETUP
    ================================ */
    :root {
        --sidebar-mobile-width: 240px;
        --sidebar-collapsed-width: 60px;
        --sidebar-expanded-width: 240px;
    }

    /* ------------------------------------------------------------------
       MOBILE SLIDING EFFECT: Main content shifts when sidebar is active
       ------------------------------------------------------------------ */
    body.mobile-sidebar-open {
        overflow-x: hidden;
    }

    /* This class must be applied to the main content container (e.g., `<main class="dashboard-wrapper">`)
       outside this header/sidebar code for the dashboard content to shift. */
    body.mobile-sidebar-open .dashboard-wrapper {
        transform: translateX(var(--sidebar-mobile-width));
        /* Prevents content from being pushed twice on mobile */
        padding-left: 0 !important;
    }

    /* ------------------------------------------------------------------
       NEW: Desktop Sidebar Shift for Main Content
       This ensures the main content (dashboard-wrapper) shifts for desktop
       sidebar state changes.
       ------------------------------------------------------------------ */
    @media (min-width: 901px) {
        /* Default state: Expanded sidebar */
        .dashboard-wrapper {
            transition: padding-left 0.3s ease-in-out;
            padding-left: var(--sidebar-expanded-width); /* Default desktop padding */
        }
        /* Collapsed state */
        .sidebar-collapsed-state .dashboard-wrapper {
            padding-left: var(--sidebar-collapsed-width);
        }
    }


    /* ================================
       HEADER
    ================================ */
    .admin-header {
        background: linear-gradient(90deg, #004080, #0066cc);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 25px;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        border-bottom: 2px solid rgba(255,255,255,0.15);
    }

    .admin-header .logo {
        display: flex;
        align-items: center;
        gap: 15px;
        /* NEW: Prevent text wrapping */
        white-space: nowrap;
    }

    .admin-header .logo img {
        height: 55px;
        border-radius: 10px;
        border: 2px solid rgba(255,255,255,0.7);
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        transition: transform 0.3s;
    }

    /* NEW: Adjust logo size and hide H1 on smaller mobile screens */
    @media (max-width: 480px) {
        .admin-header .logo img {
            height: 40px;
        }
        .admin-header .logo h1 {
            display: none;
        }
        .admin-header {
            padding: 10px 15px;
        }
    }

    .admin-header .logo img:hover {
        transform: scale(1.05) rotate(2deg);
    }

    .admin-header .user-info {
        display: flex;
        align-items: center;
        /* NEW: Use flex-wrap to prevent overflow on small screens */
        flex-wrap: nowrap;
        gap: 18px;
        font-weight: 500;
    }

    .lang-switcher {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px;
        font-weight: 600;
    }

    /* NEW: Hide language text on small screens to save space */
    @media (max-width: 600px) {
        .lang-switcher span {
            display: none;
        }
        .lang-link {
            padding: 6px 8px; /* Reduce padding when text is hidden */
            gap: 0;
        }
    }

    .lang-link {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        cursor: pointer;
        color: white;
    }

    .lang-switcher a:hover,
    .lang-switcher a.active-lang {
        background: rgba(255,255,255,0.2);
    }

    .flag-icon {
        width: 22px;
        height: 14px;
        border-radius: 3px;
        object-fit: cover;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }

    .menu-toggle {
        display: none;
        background: #ff6600;
        border: none;
        color: white;
        font-size: 22px;
        padding: 6px 14px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .menu-toggle:hover {
        background: #ff8533;
        transform: scale(1.1);
    }

    /* Notification Bell */
    .notification {
        position: relative;
        font-size: 22px;
        cursor: pointer;
        /* NEW: Ensure visibility on smaller screens */
        min-width: 22px;
    }

    .notif-badge {
        position: absolute;
        top: -6px;
        right: -10px;
        background: #dc3545;
        color: #fff;
        font-size: 12px;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 50%;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }

    /* ================================
       Profile Menu
    ================================ */
    .profile-menu {
        position: relative;
        display: flex;
        align-items: center;
    }

    .profile-trigger {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        padding: 6px 12px;
        border-radius: 6px;
        transition: background 0.3s ease;
    }

    /* NEW: Hide username on small screens */
    @media (max-width: 768px) {
        .profile-trigger span {
            display: none;
        }
        .profile-trigger .arrow {
            /* Keep arrow near the profile pic */
            margin-left: 0;
        }
    }

    .profile-trigger:hover {
        background: rgba(255,255,255,0.15);
    }

    .profile-pic {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #fff;
    }

    .profile-dropdown {
        position: absolute;
        top: 110%;
        right: 0;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.15);
        display: none;
        flex-direction: column;
        min-width: 180px;
        z-index: 999;
        overflow: hidden;
        animation: fadeIn 0.25s ease;
    }

    /* NEW: Adjust dropdown position for very small screens */
    @media (max-width: 400px) {
        .profile-dropdown {
            /* Push to the left edge to fit on screen */
            right: -20px;
            min-width: 150px;
        }
    }

    .profile-dropdown a {
        padding: 12px 16px;
        color: #333;
        text-decoration: none;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s;
    }

    .profile-dropdown a:hover {
        background: #f5f5f5;
    }

    .logout-link {
        color: #d9534f !important;
        font-weight: bold;
    }

    /* ================================
       SIDEBAR
    ================================ */
    .sidebar {
        width: var(--sidebar-expanded-width);
        height: 100vh;
        position: fixed;
        top: 70px;
        left: 0;
        background: rgba(0, 38, 77, 0.95);
        color: white;
        padding-top: 0;
        box-shadow: 2px 0 12px rgba(0,0,0,0.3);
        backdrop-filter: blur(12px);
        transition: width 0.3s ease-in-out, transform 0.3s ease;
        overflow-y: auto;
        z-index: 990;
    }

    /* Default desktop state */
    @media (min-width: 901px) {
        .sidebar.expanded {
            width: var(--sidebar-expanded-width);
        }

        /* Sidebar collapsed desktop state */
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .menu-toggle {
            /* Hide mobile toggle on desktop */
            display: none;
        }

        /* Ensure sidebar top is aligned correctly on desktop */
        .sidebar-top {
            justify-content: flex-end;
        }
    }


    /* Default mobile state (off screen) */
    @media (max-width: 900px) {
        /* Sidebar starts off-screen */
        .sidebar {
            transform: translateX(-100%);
            width: var(--sidebar-mobile-width);
            top: 66px; /* Adjust top to account for potentially smaller header on mobile */
            height: calc(100vh - 66px); /* Fill the rest of the viewport */
        }
        /* Mobile Active State */
        .sidebar.active {
            transform: translateX(0);
        }
        .menu-toggle {
            display: block; /* Show mobile toggle */
        }
        .sidebar-top {
            justify-content: flex-end;
        }
        .sidebar.active .collapse-toggle {
            display: none; /* Hide desktop toggle on mobile active state */
        }
        /* Mobile sidebar must always be expanded for usability */
        .sidebar.collapsed {
            width: var(--sidebar-mobile-width);
            transform: translateX(-100%);
        }
    }


    .sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar ul li {
        margin: 8px 0;
    }

    .sidebar ul li a {
        color: white;
        text-decoration: none;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        font-weight: 500;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
        border-radius: 6px;
        position: relative;
    }

    .sidebar ul li a.active,
    .sidebar ul li a:hover {
        background: linear-gradient(90deg, #004080, #0059b3);
        padding-left: 28px;
        border-left: 4px solid #ff6600;
        box-shadow: inset 3px 0 10px rgba(0,0,0,0.2);
    }

    .sidebar ul li a .link-text {
        margin-left: 10px;
        white-space: nowrap;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .sidebar.collapsed .link-text {
        opacity: 0;
        visibility: hidden;
    }

    /* Sidebar top (button placement) */
    .sidebar-top {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    /* Toggle button */
    .collapse-toggle {
        background: #ff6600;
        border: none;
        color: white;
        font-size: 16px;
        padding: 6px 10px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .collapse-toggle:hover {
        background: #ff8533;
        transform: scale(1.1);
    }
    .sidebar.collapsed .collapse-toggle {
        transform: rotate(180deg);
    }

    /* --------------------------------
       NEW: SIDEBAR GROUP SEPARATOR
       -------------------------------- */
    .sidebar ul li.sidebar-group-separator {
        height: 1px;
        margin: 15px 15px;
        background: rgba(255, 255, 255, 0.25); /* Stronger visibility */
        border-radius: 1px;
        list-style: none;
        /* Reset default list item margin */
        padding: 0;
    }

    /* Adjust separator when collapsed */
    .sidebar.collapsed .sidebar-group-separator {
        margin: 15px 5px;
        /* Full width of the collapsed sidebar */
        width: calc(100% - 10px);
    }

    /* --------------------------------
       SIDEBAR DROPDOWN STYLES (NEW)
       -------------------------------- */
    .sidebar-dropdown {
        position: relative;
    }

    .dropdown-toggle {
        justify-content: space-between;
        /* Ensures chevron is on the right */
        transition: background 0.3s ease;
    }

    /* Remove extra padding on hover for dropdown link */
    .dropdown-toggle:hover {
        padding-left: 28px;
    }

    .dropdown-arrow {
        margin-left: auto;
        font-size: 10px;
        transition: transform 0.3s ease;
    }

    /* Dropdown Content */
    .dropdown-content {
        list-style: none;
        padding: 0;
        margin: 0;
        max-height: 0; /* Starts hidden */
        overflow: hidden;
        transition: max-height 0.3s ease-in-out;
    }

    /* Styling for nested links */
    .dropdown-content li a {
        padding-left: 45px; /* Indent sub-links */
        font-size: 14px;
        border-left: none; /* Remove main border */
    }

    /* Sub-link hover/active state */
    .dropdown-content li a.active,
    .dropdown-content li a:hover {
        background: rgba(255, 255, 255, 0.1);
        padding-left: 55px; /* Indent on hover/active */
        border-left: 2px solid #ff6600; /* Add a sub-link border */
    }

    .dropdown-content li a .sub-icon {
        margin-right: 10px;
    }

    /* DROPDOWN OPEN STATES (JS controlled via 'open') */
    .sidebar-dropdown.open .dropdown-content {
        max-height: 500px; /* Large enough value to show all content */
    }
    .sidebar-dropdown.open .dropdown-arrow {
        transform: rotate(180deg);
    }

    /* Sidebar Collapsed State Overrides */
    .sidebar.collapsed .sidebar-dropdown .dropdown-toggle {
        /* Make entire collapsed toggle the hover trigger */
        position: relative;
        z-index: 20;
    }

    /* Hide text/arrow in collapsed state */
    .sidebar.collapsed .dropdown-toggle .link-text,
    .sidebar.collapsed .dropdown-toggle .dropdown-arrow {
        opacity: 0;
        visibility: hidden;
    }

    .sidebar.collapsed .dropdown-content {
        /* Hide content completely in collapsed mode */
        max-height: 0 !important;
        overflow: hidden;
    }

    /* Active styling for the dropdown link when child is active */
    .sidebar-dropdown .dropdown-toggle.active {
        /* Ensure background and border are applied */
        background: linear-gradient(90deg, #004080, #0059b3);
        border-left: 4px solid #ff6600;
        box-shadow: inset 3px 0 10px rgba(0,0,0,0.2);
    }

    /* Re-show link-text and arrow for active state when collapsed */
    .sidebar.collapsed .sidebar-dropdown .dropdown-toggle.active .link-text,
    .sidebar.collapsed .sidebar-dropdown .dropdown-toggle.active .dropdown-arrow {
        opacity: 1;
        visibility: visible;
    }

    /* ================================
    NOTIFICATION MODAL (Responsive updates)
    ================================ */
    .notif-modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(20, 20, 20, 0.6);
        backdrop-filter: blur(6px);
        transition: all 0.3s ease-in-out;
    }

    .notif-modal-content {
        background: #fff;
        margin: 80px auto;
        padding: 0;
        border-radius: 20px;
        /* UPDATED: Use max-width and percentage for responsive sizing */
        width: 90%;
        max-width: 400px;
        max-height: 85%; /* Increased max height */
        overflow-y: auto;
        box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        font-family: 'Roboto', sans-serif;
        animation: slideDown 0.35s ease;
        border-top: 6px solid #004080;
    }

    .notif-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 24px;
        background: linear-gradient(90deg, #004080, #0066cc);
        border-radius: 16px 16px 0 0;
        color: white;
    }

    .notif-modal-content li {
        /* UPDATED: Use flex-wrap to stack content on small screens */
        flex-direction: row;
        flex-wrap: wrap; /* Allows wrapping on smaller list items */
        padding: 12px 14px;
        margin-bottom: 10px;
        background: #f7f9fc;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        transition: all 0.25s ease;
    }

    @media (max-width: 380px) {
        .notif-modal-content li {
            flex-direction: column;
            align-items: flex-start;
        }
        .msg-right {
            max-width: 100%; /* Take full width when stacked */
            margin-top: 8px;
        }
    }


    /* ... other notification styles remain the same ... */

    @keyframes slideDown {
        from { transform: translateY(-40px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* Removed redundant @media screen and (max-width: 500px) block,
       as max-width: 400px; max-height: 85%; covers mobile responsiveness. */
</style>

<script>
    // Sidebar toggle (mobile)
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const body = document.body;

        // 1. Toggle the sidebar 'active' class (to make it visible/hide it)
        sidebar.classList.toggle('active');

        // 2. Toggle the body class to trigger the dashboard content slide
        body.classList.toggle('mobile-sidebar-open');

        // Ensure desktop collapse class is off when mobile sidebar is active
        if (sidebar.classList.contains('active')) {
            // UPDATED: Only remove 'collapsed' if it's there AND we are on a smaller screen (optional but safer)
            if (window.innerWidth <= 900) {
                sidebar.classList.remove('collapsed');
                body.classList.remove('sidebar-collapsed-state');
            }
        }
    }

    function toggleSidebarCollapse() {
        const sidebar = document.getElementById('sidebar');
        const body = document.body;

        // Prevent collapse/expand on mobile
        if (window.innerWidth <= 900 && !sidebar.classList.contains('active')) {
            // Only allow toggle if it's the desktop collapse button click
            return;
        }


        sidebar.classList.toggle('collapsed');
        body.classList.toggle('sidebar-collapsed-state');

        let state = sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded';

        // Save state to session
        fetch('../admin/save_sidebar_state.php', {
            method: 'POST',
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "state=" + state
        });
    }

    // Notification modal
    const notifBell = document.getElementById('notifBell');
    const notifModal = document.getElementById('notifModal');
    const closeBtn = document.getElementById('closeNotif');

    notifBell.addEventListener('click', () => {
        notifModal.style.display = 'block';
    });

    closeBtn.addEventListener('click', () => {
        notifModal.style.display = 'none';
    });

    window.addEventListener('click', (e) => {
        if (e.target === notifModal) {
            notifModal.style.display = 'none';
        }
    });

    // NOTE: Changed alert() to console.log as per platform constraints.
    document.getElementById("clearUnread")?.addEventListener("click", function() {
        fetch("../admin/clear_unread.php", { method: "POST" })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    console.log("✅ All unread messages cleared!");
                    location.reload();
                } else {
                    console.log("❌ Failed to clear messages.");
                }
            })
            .catch(error => {
                console.log("❌ An error occurred while communicating with the server.");
            });
    });

    function toggleProfileMenu() {
        const dropdown = document.getElementById("profileDropdown");
        dropdown.style.display = dropdown.style.display === "flex" ? "none" : "flex";
    }

    window.addEventListener("click", function(e) {
        const trigger = document.querySelector(".profile-trigger");
        const dropdown = document.getElementById("profileDropdown");
        if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = "none";
        }
    });

    // ==================================
    // NEW: Sidebar Dropdown Logic
    // ==================================
    document.querySelectorAll('.sidebar-dropdown .dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parentLi = this.closest('.sidebar-dropdown');

            // Do nothing if sidebar is collapsed on desktop
            if (window.innerWidth > 900 && document.getElementById('sidebar').classList.contains('collapsed')) {
                return;
            }

            // Close other open dropdowns
            document.querySelectorAll('.sidebar-dropdown').forEach(item => {
                if (item !== parentLi && item.classList.contains('open')) {
                    item.classList.remove('open');
                }
            });

            // Toggle current dropdown
            parentLi.classList.toggle('open');
        });
    });
</script>
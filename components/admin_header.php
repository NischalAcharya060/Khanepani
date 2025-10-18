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

// --- Dark Mode Handling ---
if (isset($_GET['dark_mode'])) {
    $_SESSION['dark_mode'] = ($_GET['dark_mode'] === 'on');
}

$is_dark_mode = $_SESSION['dark_mode'] ?? false;

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

<body class="<?= $is_dark_mode ? 'dark-mode' : '' ?>">
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

        <!-- DARK MODE TOGGLE BUTTON -->
        <div class="dark-mode-toggle">
            <a href="?dark_mode=<?= $is_dark_mode ? 'off' : 'on' ?>" class="dark-mode-btn" title="<?= $is_dark_mode ? 'Switch to Light Mode' : 'Switch to Dark Mode' ?>">
                <span class="dark-mode-icon">
                    <?= $is_dark_mode ? '🌙' : '☀️' ?>
                </span>
                <span class="dark-mode-text">
                    <?= $is_dark_mode ? 'Light' : 'Dark' ?>
                </span>
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
                <div class="profile-avatar">
                    <img src="<?= htmlspecialchars($profile_pic_path, ENT_QUOTES, 'UTF-8') ?>"
                         alt="Profile" class="profile-pic">
                    <div class="online-indicator"></div>
                </div>
                <div class="profile-info">
                    <span class="username"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="user-role">
                        <?= $current_admin_id === 'master' ? 'Master Admin' : ($current_role_id == 1 ? 'Super Admin' : 'Admin') ?>
                    </span>
                </div>
                <i class="dropdown-arrow" data-feather="chevron-down"></i>
            </div>

            <div class="profile-dropdown" id="profileDropdown">
                <div class="dropdown-header">
                    <div class="dropdown-avatar">
                        <img src="<?= htmlspecialchars($profile_pic_path, ENT_QUOTES, 'UTF-8') ?>" alt="Profile">
                    </div>
                    <div class="dropdown-user-info">
                        <strong><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= $current_admin_id === 'master' ? 'Master Admin' : ($current_role_id == 1 ? 'Super Admin' : 'Admin') ?></span>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="../admin/profile.php" class="dropdown-item">
                    <i data-feather="user"></i>
                    <span><?= htmlspecialchars($lang['my_profile'] ?? 'My Profile', ENT_QUOTES, 'UTF-8') ?></span>
                </a>
                <a href="../admin/settings.php" class="dropdown-item">
                    <i data-feather="settings"></i>
                    <span><?= htmlspecialchars($lang['settings'] ?? 'Settings', ENT_QUOTES, 'UTF-8') ?></span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="../admin/logout.php" class="dropdown-item logout-link">
                    <i data-feather="log-out"></i>
                    <span><?= htmlspecialchars($lang['logout'] ?? 'Logout', ENT_QUOTES, 'UTF-8') ?></span>
                </a>
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
    :root {
        --sidebar-mobile-width: 240px;
        --sidebar-collapsed-width: 60px;
        --sidebar-expanded-width: 240px;
        --bg-primary: #ffffff;
        --bg-secondary: #f8f9fa;
        --text-primary: #333333;
        --text-secondary: #666666;
        --border-color: #e0e0e0;
        --header-bg: linear-gradient(90deg, #004080, #0066cc);
        --sidebar-bg: rgba(0, 38, 77, 0.95);
        --card-bg: #ffffff;
        --shadow-color: rgba(0, 0, 0, 0.1);
    }

    body.dark-mode {
        --bg-primary: #1a1a1a;
        --bg-secondary: #2d2d2d;
        --text-primary: #ffffff;
        --text-secondary: #cccccc;
        --border-color: #404040;
        --header-bg: linear-gradient(90deg, #002b4d, #004080);
        --sidebar-bg: rgba(0, 20, 40, 0.95);
        --card-bg: #2d2d2d;
        --shadow-color: rgba(0, 0, 0, 0.3);
    }

    body.dark-mode {
        background-color: var(--bg-primary);
        color: var(--text-primary);
    }

    body.dark-mode .admin-header {
        background: var(--header-bg);
    }

    body.dark-mode .sidebar {
        background: var(--sidebar-bg);
    }

    body.dark-mode .profile-dropdown {
        background: var(--card-bg);
        border-color: var(--border-color);
    }

    body.dark-mode .dropdown-item {
        color: var(--text-primary);
    }

    body.dark-mode .dropdown-item:hover {
        background: var(--bg-secondary);
    }

    body.dark-mode .notif-modal-content {
        background: var(--card-bg);
        color: var(--text-primary);
    }

    body.dark-mode .notif-modal-content li {
        background: var(--bg-secondary);
        border-color: var(--border-color);
    }

    body.dark-mode .msg-left strong {
        color: var(--text-primary);
    }

    body.dark-mode .time {
        color: var(--text-secondary);
    }

    body.dark-mode .msg-right {
        color: var(--text-secondary);
    }

    body.dark-mode .no-messages {
        background: var(--bg-secondary);
        color: var(--text-secondary);
        border-color: var(--border-color);
    }

    body.dark-mode .dropdown-divider {
        background: var(--border-color);
    }

    body.dark-mode .dropdown-item i {
        color: var(--text-secondary);
    }

    body.dark-mode .dropdown-item:hover i {
        color: var(--text-primary);
    }

    .dark-mode-toggle {
        display: flex;
        align-items: center;
    }

    .dark-mode-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 20px;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.15);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        white-space: nowrap;
    }

    .dark-mode-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        color: white;
    }

    .dark-mode-btn:active {
        transform: translateY(0);
    }

    .dark-mode-icon {
        font-size: 18px;
        transition: transform 0.3s ease;
    }

    .dark-mode-btn:hover .dark-mode-icon {
        transform: rotate(20deg);
    }

    :root {
        --sidebar-mobile-width: 240px;
        --sidebar-collapsed-width: 60px;
        --sidebar-expanded-width: 240px;
    }

    body.mobile-sidebar-open {
        overflow-x: hidden;
    }

    body.mobile-sidebar-open .dashboard-wrapper {
        transform: translateX(var(--sidebar-mobile-width));
        padding-left: 0 !important;
    }

    @media (min-width: 901px) {
        .dashboard-wrapper {
            transition: padding-left 0.3s ease-in-out;
            padding-left: var(--sidebar-expanded-width);
        }
        .sidebar-collapsed-state .dashboard-wrapper {
            padding-left: var(--sidebar-collapsed-width);
        }
    }

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
        white-space: nowrap;
    }

    .admin-header .logo img {
        height: 55px;
        border-radius: 10px;
        border: 2px solid rgba(255,255,255,0.7);
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        transition: transform 0.3s;
    }

    .admin-header .logo img:hover {
        transform: scale(1.05) rotate(2deg);
    }

    .admin-header .user-info {
        display: flex;
        align-items: center;
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

    .notification {
        position: relative;
        font-size: 22px;
        cursor: pointer;
        min-width: 22px;
        transition: all 0.3s ease;
        padding: 8px;
        border-radius: 8px;
    }

    .notification:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: scale(1.1);
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

    .profile-menu {
        position: relative;
    }

    .profile-trigger {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: rgba(255,255,255,0.05);
    }

    .profile-trigger:hover {
        background: rgba(255,255,255,0.1);
        transform: translateY(-1px);
    }

    .profile-avatar {
        position: relative;
    }

    .profile-pic {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255,255,255,0.8);
        transition: all 0.3s ease;
    }

    .online-indicator {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 10px;
        height: 10px;
        background: #4cc9a7;
        border: 2px solid white;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    .profile-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .username {
        font-weight: 600;
        font-size: 14px;
        color: white;
    }

    .user-role {
        font-size: 11px;
        opacity: 0.8;
        font-weight: 500;
        color: rgba(255,255,255,0.9);
    }

    .dropdown-arrow {
        width: 16px;
        height: 16px;
        color: white;
        transition: all 0.3s ease;
    }

    .profile-trigger:hover .dropdown-arrow {
        transform: rotate(180deg);
    }

    .profile-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        display: none;
        flex-direction: column;
        min-width: 240px;
        z-index: 1001;
        overflow: hidden;
        animation: dropdownSlide 0.3s ease;
        border: 1px solid rgba(0,0,0,0.1);
        margin-top: 8px;
    }

    @keyframes dropdownSlide {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .dropdown-header {
        padding: 20px;
        background: linear-gradient(135deg, #4361ee, #3a0ca3);
        color: white;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .dropdown-avatar img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: 3px solid rgba(255,255,255,0.3);
        object-fit: cover;
    }

    .dropdown-user-info {
        display: flex;
        flex-direction: column;
    }

    .dropdown-user-info strong {
        font-size: 16px;
        font-weight: 700;
    }

    .dropdown-user-info span {
        font-size: 12px;
        opacity: 0.9;
    }

    .dropdown-divider {
        height: 1px;
        background: rgba(0,0,0,0.1);
        margin: 8px 0;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 20px;
        color: #1a202c;
        text-decoration: none;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .dropdown-item:hover {
        background: #f8fafc;
        padding-left: 24px;
    }

    .dropdown-item i {
        width: 18px;
        height: 18px;
        stroke-width: 2;
        color: #6c757d;
    }

    .dropdown-item:hover i {
        color: #4361ee;
    }

    .logout-link {
        color: #f94144 !important;
        font-weight: 600;
    }

    .logout-link:hover {
        background: #fff0f0 !important;
    }

    .dark-mode .profile-dropdown {
        background: #1e293b;
        border-color: #475569;
    }

    .dark-mode .dropdown-item {
        color: #e2e8f0;
    }

    .dark-mode .dropdown-item:hover {
        background: #334155;
    }

    .dark-mode .dropdown-divider {
        background: #475569;
    }

    .dark-mode .dropdown-item i {
        color: #94a3b8;
    }

    .dark-mode .dropdown-item:hover i {
        color: #4895ef;
    }

    .dark-mode .logout-link:hover {
        background: #2d1b1b !important;
    }

    .sidebar {
        width: var(--sidebar-expanded-width);
        max-height: 100vh;
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

    @media (min-width: 901px) {
        .sidebar.expanded {
            width: var(--sidebar-expanded-width);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .menu-toggle {
            display: none;
        }

        .sidebar-top {
            justify-content: flex-end;
        }
    }

    @media (max-width: 900px) {
        .sidebar {
            transform: translateX(-100%);
            width: var(--sidebar-mobile-width);
            top: 66px;
            max-height: calc(100vh - 66px);
        }
        .sidebar.active {
            transform: translateX(0);
        }
        .menu-toggle {
            display: block;
        }
        .sidebar-top {
            justify-content: flex-end;
        }
        .sidebar.active .collapse-toggle {
            display: none;
        }
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

    .sidebar-top {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

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

    .sidebar ul li.sidebar-group-separator {
        height: 1px;
        margin: 15px 15px;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 1px;
        list-style: none;
        padding: 0;
    }

    .sidebar.collapsed .sidebar-group-separator {
        margin: 15px 5px;
        width: calc(100% - 10px);
    }

    .notif-modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(8px);
        transition: all 0.3s ease-in-out;
        opacity: 0;
    }

    .notif-modal.show {
        display: block;
        animation: fadeIn 0.3s ease-out forwards;
    }

    .notif-modal-content {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        margin: 80px auto;
        padding: 0;
        border-radius: 20px;
        width: 90%;
        max-width: 480px;
        max-height: 75vh;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        font-family: 'Inter', sans-serif;
        animation: modalSlideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 1px solid rgba(255, 255, 255, 0.2);
        position: relative;
    }

    .notif-modal-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #004080, #0066cc, #004080);
        background-size: 200% 100%;
        animation: shimmer 3s linear infinite;
    }

    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    @keyframes modalSlideIn {
        from {
            transform: translateY(-50px) scale(0.9);
            opacity: 0;
        }
        to {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }

    .notif-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        background: linear-gradient(135deg, #004080 0%, #0066cc 100%);
        color: white;
        position: relative;
    }

    .notif-header h3 {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .notif-header h3::before {
        content: '📬';
        font-size: 1.2em;
    }

    .close-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        font-size: 24px;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .close-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg) scale(1.1);
    }

    .clear-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 15px 24px;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        font-size: 0.9rem;
    }

    .clear-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }

    .clear-btn:active {
        transform: translateY(0);
    }

    .clear-btn.loading {
        pointer-events: none;
        opacity: 0.7;
    }

    .clear-btn.loading::after {
        content: '';
        width: 16px;
        height: 16px;
        border: 2px solid transparent;
        border-top: 2px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .notif-modal-content ul {
        list-style: none;
        padding: 0 20px 20px;
        margin: 0;
        max-height: 400px;
        overflow-y: auto;
    }

    .notif-modal-content ul::-webkit-scrollbar {
        width: 6px;
    }

    .notif-modal-content ul::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .notif-modal-content ul::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 10px;
    }

    .notif-modal-content ul::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    .notif-modal-content li {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 16px;
        margin-bottom: 12px;
        background: white;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }

    .notif-modal-content li::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(135deg, #004080, #0066cc);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .notif-modal-content li:hover::before {
        opacity: 1;
    }

    .notif-modal-content li:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        border-color: #cbd5e1;
    }

    .msg-left {
        flex: 1;
        min-width: 0;
    }

    .msg-left strong {
        display: block;
        color: #1e293b;
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .time {
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 500;
        display: block;
    }

    .msg-right {
        color: #475569;
        font-size: 0.85rem;
        line-height: 1.4;
        margin-left: 15px;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .no-messages {
        text-align: center;
        padding: 40px 20px;
        color: #64748b;
        font-style: italic;
        background: #f8fafc;
        border-radius: 12px;
        margin: 20px;
        border: 2px dashed #e2e8f0;
    }

    .no-messages::before {
        content: '💌';
        font-size: 2rem;
        display: block;
        margin-bottom: 10px;
        opacity: 0.5;
    }

    .notif-badge {
        position: absolute;
        top: -6px;
        right: -10px;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 7px;
        border-radius: 50%;
        box-shadow: 0 3px 10px rgba(220, 53, 69, 0.4);
        animation: badgePulse 2s infinite;
        min-width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @keyframes badgePulse {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 3px 10px rgba(220, 53, 69, 0.4);
        }
        50% {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.6);
        }
    }

    .notification {
        position: relative;
        font-size: 22px;
        cursor: pointer;
        min-width: 22px;
        transition: all 0.3s ease;
        padding: 8px;
        border-radius: 8px;
    }

    .notification:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: scale(1.1);
    }

    .notification:active {
        transform: scale(0.95);
    }

    @media (max-width: 900px) {
        .admin-header {
            padding: 12px 20px;
        }

        .admin-header .logo h1 {
            font-size: 18px;
        }

        .user-info {
            gap: 12px;
        }

        .dark-mode-btn {
            padding: 6px 10px;
        }

        .lang-switcher {
            gap: 4px;
        }

        .lang-link {
            padding: 5px 8px;
        }

        .flag-icon {
            width: 22px;
            height: 14px;
        }

        .notification {
            font-size: 18px;
            padding: 6px;
        }

        .notif-badge {
            font-size: 10px;
            padding: 1px 4px;
            top: -4px;
            right: -8px;
        }

        .profile-info {
            display: none;
        }

        .profile-trigger {
            padding: 4px 8px;
        }

        .profile-pic {
            width: 32px;
            height: 32px;
        }

        .dropdown-arrow {
            width: 14px;
            height: 14px;
        }

        .profile-dropdown {
            min-width: 200px;
            right: -10px;
        }

        .sidebar {
            top: 60px;
            max-height: calc(100vh - 60px);
        }

        .sidebar ul li a {
            padding: 12px 16px;
            font-size: 14px;
        }

        .sidebar-top {
            padding: 8px;
        }

        .collapse-toggle {
            padding: 4px 8px;
            font-size: 14px;
        }
    }

    @media (max-width: 768px) {
        .admin-header {
            padding: 10px 15px;
        }

        .admin-header .logo h1 {
            font-size: 16px;
        }

        .admin-header .logo img {
            height: 35px;
        }

        .user-info {
            gap: 10px;
        }

        .dark-mode-text {
            display: none;
        }

        .dark-mode-btn {
            padding: 6px 8px;
        }

        .lang-switcher span {
            display: none;
        }

        .lang-link {
            padding: 4px 6px;
        }

        .flag-icon {
            width: 18px;
            height: 12px;
        }

        .notification {
            font-size: 18px;
            padding: 6px;
        }

        .notif-badge {
            font-size: 10px;
            padding: 1px 4px;
            top: -4px;
            right: -8px;
        }

        .profile-info {
            display: none;
        }

        .profile-trigger {
            padding: 4px 8px;
        }

        .profile-pic {
            width: 32px;
            height: 32px;
        }

        .dropdown-arrow {
            width: 14px;
            height: 14px;
        }

        .profile-dropdown {
            min-width: 200px;
            right: -10px;
        }

        .sidebar {
            width: 220px;
            top: 55px;
            max-height: calc(100vh - 55px);
        }

        .sidebar ul li a {
            padding: 10px 14px;
        }

        .link-text {
            font-size: 13px;
        }

        .sidebar-group-separator {
            margin: 12px 10px;
        }
    }

    @media (max-width: 600px) {
        .admin-header {
            padding: 8px 12px;
        }

        .admin-header .logo h1 {
            display: none;
        }

        .admin-header .logo img {
            height: 30px;
        }

        .user-info {
            gap: 8px;
        }

        .dark-mode-toggle {
            margin-right: 0;
        }

        .dark-mode-btn {
            padding: 4px 6px;
        }

        .dark-mode-icon {
            font-size: 14px;
        }

        .lang-switcher {
            gap: 2px;
        }

        .lang-link {
            padding: 3px 4px;
        }

        .flag-icon {
            width: 16px;
            height: 10px;
        }

        .notification {
            font-size: 16px;
            padding: 4px;
        }

        .notif-badge {
            font-size: 9px;
            padding: 1px 3px;
            min-width: 16px;
            height: 16px;
        }

        .profile-trigger {
            gap: 6px;
            padding: 3px 6px;
        }

        .profile-pic {
            width: 28px;
            height: 28px;
        }

        .dropdown-arrow {
            width: 12px;
            height: 12px;
        }

        .profile-dropdown {
            min-width: 180px;
            right: -15px;
        }

        .dropdown-header {
            padding: 16px;
        }

        .dropdown-item {
            padding: 10px 16px;
        }

        .sidebar {
            width: 200px;
            top: 50px;
            max-height: calc(100vh - 50px);
        }

        .sidebar ul li a {
            padding: 8px 12px;
            font-size: 13px;
        }

        .sub-icon {
            font-size: 14px;
        }
    }

    @media (max-width: 480px) {
        .admin-header {
            padding: 6px 10px;
        }

        .admin-header .logo img {
            height: 25px;
        }

        .user-info {
            gap: 6px;
        }

        .lang-switcher {
            display: none;
        }

        .dark-mode-btn {
            padding: 3px 4px;
        }

        .dark-mode-icon {
            font-size: 12px;
        }

        .notification {
            font-size: 14px;
            padding: 3px;
        }

        .notif-badge {
            font-size: 8px;
            padding: 0px 2px;
            top: -3px;
            right: -6px;
            min-width: 14px;
            height: 14px;
        }

        .profile-trigger {
            gap: 4px;
            padding: 2px 4px;
        }

        .profile-pic {
            width: 24px;
            height: 24px;
        }

        .dropdown-arrow {
            width: 10px;
            height: 10px;
        }

        .menu-toggle {
            font-size: 18px;
            padding: 4px 8px;
        }

        .notif-modal-content {
            margin: 40px auto;
            width: 95%;
            max-height: 80vh;
        }

        .notif-header {
            padding: 12px 16px;
        }

        .notif-header h3 {
            font-size: 1rem;
        }

        .close-btn {
            width: 30px;
            height: 30px;
            font-size: 20px;
        }

        .clear-btn {
            margin: 10px 16px;
            padding: 8px 12px;
            font-size: 0.8rem;
        }

        .notif-modal-content ul {
            padding: 0 12px 12px;
        }

        .notif-modal-content li {
            padding: 12px;
            flex-direction: column;
            align-items: flex-start;
        }

        .msg-right {
            margin-left: 0;
            margin-top: 8px;
            max-width: 100%;
        }

        .no-messages {
            padding: 20px 15px;
            margin: 15px;
        }
    }

    @media (max-width: 360px) {
        .admin-header {
            padding: 4px 8px;
        }

        .user-info {
            gap: 4px;
        }

        .dark-mode-toggle {
            display: none;
        }

        .notification {
            font-size: 12px;
            padding: 2px;
        }

        .profile-trigger {
            padding: 1px 3px;
        }

        .profile-pic {
            width: 22px;
            height: 22px;
        }
    }

    @media (hover: none) and (pointer: coarse) {
        .dark-mode-btn:hover,
        .lang-link:hover,
        .notification:hover,
        .profile-trigger:hover,
        .menu-toggle:hover,
        .collapse-toggle:hover {
            transform: none;
        }

        .dark-mode-btn:active,
        .lang-link:active,
        .notification:active,
        .profile-trigger:active,
        .menu-toggle:active,
        .collapse-toggle:active {
            transform: scale(0.95);
        }

        .dark-mode-btn,
        .lang-link,
        .notification,
        .menu-toggle,
        .collapse-toggle {
            min-height: 44px;
            min-width: 44px;
        }

        .profile-trigger {
            min-height: 44px;
        }
    }

    @media (max-height: 500px) and (orientation: landscape) {
        .admin-header {
            padding: 6px 15px;
        }

        .admin-header .logo img {
            height: 25px;
        }

        .sidebar {
            top: 45px;
            max-height: calc(100vh - 45px);
        }

        .sidebar ul li a {
            padding: 8px 16px;
        }

        .notif-modal-content {
            margin: 20px auto;
            max-height: 85vh;
        }
    }

    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
        .admin-header .logo img {
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
        }

        .flag-icon {
            image-rendering: -webkit-optimize-contrast;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .admin-header .logo img,
        .dark-mode-btn,
        .lang-link,
        .notification,
        .profile-trigger,
        .menu-toggle,
        .collapse-toggle,
        .sidebar ul li a,
        .dropdown-item,
        .notif-modal-content,
        .notif-modal-content li {
            transition: none;
            animation: none;
        }

        .dark-mode-btn:hover .dark-mode-icon,
        .profile-trigger:hover .dropdown-arrow {
            transform: none;
        }

        .notif-badge {
            animation: none;
        }
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>

<script src="js/theme-manager.js"></script>

<script>
    // Apply dark mode class on page load
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($is_dark_mode): ?>
        document.body.classList.add('dark-mode');
        <?php endif; ?>

        // Initialize sidebar state
        initializeSidebar();
    });

    // Initialize sidebar based on screen size and saved state
    function initializeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const body = document.body;

        if (window.innerWidth <= 900) {
            // Mobile: always start with sidebar hidden
            sidebar.classList.remove('active');
            body.classList.remove('mobile-sidebar-open');
            sidebar.classList.remove('collapsed'); // Mobile should never be collapsed
        } else {
            // Desktop: apply saved state
            const savedState = '<?= $sidebar_state ?>'; // expanded or collapsed
            if (savedState === 'collapsed') {
                sidebar.classList.add('collapsed');
                body.classList.add('sidebar-collapsed-state');
            } else {
                sidebar.classList.remove('collapsed');
                body.classList.remove('sidebar-collapsed-state');
            }
            sidebar.classList.remove('active'); // Remove mobile active state on desktop
        }
    }

    // SIMPLIFIED Sidebar toggle (mobile)
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const body = document.body;

        if (window.innerWidth <= 900) {
            // Mobile behavior
            sidebar.classList.toggle('active');
            body.classList.toggle('mobile-sidebar-open');

            // Add/remove overlay
            if (sidebar.classList.contains('active')) {
                createMobileOverlay();
            } else {
                removeMobileOverlay();
            }
        } else {
            // Desktop: toggle collapse/expand
            toggleSidebarCollapse();
        }
    }

    // Create mobile overlay
    function createMobileOverlay() {
        if (document.getElementById('mobile-overlay')) return;

        const overlay = document.createElement('div');
        overlay.id = 'mobile-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 980;
            backdrop-filter: blur(2px);
        `;
        overlay.onclick = closeMobileSidebar;
        document.body.appendChild(overlay);
    }

    // Remove mobile overlay
    function removeMobileOverlay() {
        const overlay = document.getElementById('mobile-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    // Close mobile sidebar
    function closeMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const body = document.body;

        sidebar.classList.remove('active');
        body.classList.remove('mobile-sidebar-open');
        removeMobileOverlay();
    }

    // Desktop sidebar collapse/expand
    function toggleSidebarCollapse() {
        if (window.innerWidth <= 900) return; // Don't allow collapse on mobile

        const sidebar = document.getElementById('sidebar');
        const body = document.body;

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

    // Handle window resize
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('sidebar');

        if (window.innerWidth > 900) {
            // Desktop: remove mobile states
            sidebar.classList.remove('active');
            document.body.classList.remove('mobile-sidebar-open');
            removeMobileOverlay();

            // Ensure proper desktop state
            initializeSidebar();
        } else {
            // Mobile: ensure collapsed state is removed
            sidebar.classList.remove('collapsed');
            document.body.classList.remove('sidebar-collapsed-state');
        }
    });

    // Enhanced Notification Modal
    const notifBell = document.getElementById('notifBell');
    const notifModal = document.getElementById('notifModal');
    const closeBtn = document.getElementById('closeNotif');
    const clearBtn = document.getElementById('clearUnread');

    function openNotificationModal() {
        notifModal.classList.add('show');
        document.body.style.overflow = 'hidden';

        // Add entrance animation to list items
        const listItems = notifModal.querySelectorAll('li');
        listItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
            item.style.animation = 'slideInRight 0.5s ease-out forwards';
        });
    }

    function closeNotificationModal() {
        notifModal.classList.remove('show');
        document.body.style.overflow = '';

        // Reset animations
        const listItems = notifModal.querySelectorAll('li');
        listItems.forEach(item => {
            item.style.animation = '';
        });
    }

    if (notifBell) notifBell.addEventListener('click', openNotificationModal);
    if (closeBtn) closeBtn.addEventListener('click', closeNotificationModal);

    window.addEventListener('click', (e) => {
        if (e.target === notifModal) {
            closeNotificationModal();
        }
    });

    // Enhanced clear unread functionality
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            const btn = this;
            const originalText = btn.innerHTML;

            // Add loading state
            btn.classList.add('loading');
            btn.innerHTML = 'Clearing...';

            fetch("../admin/clear_unread.php", { method: "POST" })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Success animation
                        btn.innerHTML = '✅ Cleared!';
                        btn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';

                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        // Error state
                        btn.innerHTML = '❌ Failed';
                        btn.style.background = 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)';

                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.style.background = '';
                            btn.classList.remove('loading');
                        }, 2000);
                    }
                })
                .catch(error => {
                    btn.innerHTML = '❌ Error';
                    btn.style.background = 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)';

                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.style.background = '';
                        btn.classList.remove('loading');
                    }, 2000);
                });
        });
    }

    // Keyboard support
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && notifModal && notifModal.classList.contains('show')) {
            closeNotificationModal();
        }
    });

    // Enhanced list item click with ripple effect
    document.querySelectorAll('.notif-modal-content li').forEach(item => {
        item.addEventListener('click', function(e) {
            // Create ripple effect
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(0, 100, 255, 0.3);
                transform: scale(0);
                animation: ripple 0.6s linear;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                pointer-events: none;
            `;

            this.appendChild(ripple);

            // Navigate after animation
            setTimeout(() => {
                const messageId = this.getAttribute('onclick')?.match(/id=(\d+)/)?.[1];
                if (messageId) {
                    window.location.href = `view_message.php?id=${messageId}`;
                }
            }, 300);
        });
    });

    // Add CSS for ripple animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    `;
    document.head.appendChild(style);

    function toggleProfileMenu() {
        const dropdown = document.getElementById("profileDropdown");
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === "flex" ? "none" : "flex";
        }
    }

    window.addEventListener("click", function(e) {
        const trigger = document.querySelector(".profile-trigger");
        const dropdown = document.getElementById("profileDropdown");
        if (trigger && dropdown && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = "none";
        }
    });
</script>
<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/db.php';

// Language handling
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // default
}
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
include '../lang/' . $_SESSION['lang'] . '.php';

// Get current filename for active sidebar
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch unread messages
$notif_result = $conn->query("SELECT * FROM contact_messages WHERE is_read = 0 ORDER BY created_at DESC");
$unread_messages = [];
if ($notif_result) {
    while ($row = $notif_result->fetch_assoc()) {
        $unread_messages[] = $row;
    }
}
$unread_count = count($unread_messages);
$username = $_SESSION['username'] ?? 'Admin';

// Sidebar state from session (default collapsed)
$sidebar_state = $_SESSION['sidebar_state'] ?? 'collapsed';
?>

<!-- Admin Header -->
<header class="admin-header">
    <div class="logo">
        <img src="../assets/images/logo.jpg" alt="Logo">
        <h1><?= $lang['logo'] ?></h1>

    </div>
    <div class="user-info">
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

        <!-- Notification Bell -->
        <div class="notification" id="notifBell">
            🔔
            <?php if (!empty($unread_count) && $unread_count > 0): ?>
                <span class="notif-badge"><?= $unread_count ?></span>
            <?php endif; ?>
        </div>

        <span>👤 <?= htmlspecialchars($username) ?></span>
        <a href="../admin/logout.php" class="logout-btn"><?= $lang['logout'] ?></a>
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    </div>
</header>

<!-- Notification Modal -->
<div id="notifModal" class="notif-modal">
    <div class="notif-modal-content">
        <span class="close-btn" id="closeNotif">&times;</span>
        <h3><?= $lang['unread_messages'] ?? 'Unread Messages' ?></h3>
        <?php if ($unread_count > 0): ?>
            <ul>
                <?php foreach ($unread_messages as $msg): ?>
                    <li onclick="window.location.href='view_message.php?id=<?= $msg['id'] ?>'">
                        <strong><?= htmlspecialchars($msg['name']) ?></strong>:
                        <?= htmlspecialchars(substr($msg['message'], 0, 50)) ?>...
                        <span class="time"><?= date("d M, h:i A", strtotime($msg['created_at'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><?= $lang['no_unread'] ?? 'No unread messages.' ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Sidebar -->
<aside class="sidebar <?= $sidebar_state ?>" id="sidebar">
    <br>
    <div class="sidebar-top">
        <button class="collapse-toggle" onclick="toggleSidebarCollapse()">⮜</button>
    </div>
    <ul>
        <li><a href="../admin/dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">🏠 <span class="link-text"><?= $lang['dashboard'] ?></span></a></li>
        <li><a href="../admin/manage_notices.php" class="<?= $current_page == 'manage_notices.php' ? 'active' : '' ?>">📢 <span class="link-text"><?= $lang['manage_notices'] ?></span></a></li>
        <li><a href="../admin/manage_gallery.php" class="<?= $current_page == 'manage_gallery.php' ? 'active' : '' ?>">🖼 <span class="link-text"><?= $lang['manage_gallery'] ?></span></a></li>
        <li><a href="../admin/messages.php" class="<?= $current_page == 'messages.php' ? 'active' : '' ?>">📬 <span class="link-text"><?= $lang['messages'] ?></span></a></li>
        <li><a href="../admin/manage_admin.php" class="<?= $current_page == 'manage_admin.php' ? 'active' : '' ?>">👥 <span class="link-text"><?= $lang['manage_admin'] ?></span></a></li>
        <li><a href="../admin/activity.php" class="<?= $current_page == 'activity.php' ? 'active' : '' ?>">🕒 <span class="link-text"><?= $lang['recent_activity'] ?></span></a></li>
        <li><a href="../admin/settings.php" class="<?= $current_page == 'settings.php' ? 'active' : '' ?>">⚙ <span class="link-text"><?= $lang['settings'] ?></span></a></li>
    </ul>
</aside>

<style>
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
        gap: 18px;
        font-weight: 500;
    }

    .lang-switcher {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px;
    }

    .lang-link {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px; /* pill shape */
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;

        box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .flag-icon {
        width: 22px;
        height: 14px;
        border-radius: 3px;
        object-fit: cover;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }

    /* Logout button */
    .logout-btn {
        background: linear-gradient(135deg, #ff4d4d, #cc0000);
        color: white;
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .logout-btn:hover {
        background: linear-gradient(135deg, #e60000, #990000);
        transform: translateY(-2px) scale(1.05);
    }

    /* Menu toggle (mobile) */
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
       SIDEBAR
    ================================ */
    .sidebar {
        width: 240px;
        height: 100vh;
        position: fixed;
        top: 70px;
        left: 0;
        background: rgba(0, 38, 77, 0.95);
        color: white;
        padding-top: 0;
        box-shadow: 2px 0 12px rgba(0,0,0,0.3);
        backdrop-filter: blur(12px);
        transition: width 0.3s ease-in-out;
        overflow: hidden;
    }

    /* Sidebar collapsed */
    .sidebar.collapsed {
        width: 70px;
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

    /* ================================
    NOTIFICATION MODAL (Modern Style)
 ================================ */
    .notif-modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 15, 15, 0.6);
        backdrop-filter: blur(6px);
        animation: fadeIn 0.3s ease;
    }

    /* Modal Content */
    .notif-modal-content {
        background: rgba(255, 255, 255, 0.95);
        margin: 100px auto;
        padding: 20px 25px;
        border-radius: 16px;
        width: 380px;
        max-height: 75%;
        overflow-y: auto;
        box-shadow: 0 8px 30px rgba(0,0,0,0.25);
        animation: slideDown 0.35s ease;
        font-family: "Segoe UI", Roboto, sans-serif;
    }

    /* Header inside modal */
    .notif-modal-content h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 20px;
        font-weight: 600;
        color: #003366;
        border-bottom: 2px solid #eee;
        padding-bottom: 8px;
    }

    /* Close button */
    .close-btn {
        color: #555;
        float: right;
        font-size: 22px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .close-btn:hover {
        color: #e60000;
        transform: rotate(90deg);
    }

    /* List styling */
    .notif-modal-content ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .notif-modal-content li {
        padding: 12px 14px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: all 0.25s ease;
        border-radius: 8px;
    }
    .notif-modal-content li:hover {
        background: #f7f9fc;
        transform: translateX(4px);
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }

    /* Time text */
    .notif-modal-content .time {
        display: block;
        font-size: 12px;
        color: #888;
        margin-top: 4px;
        font-style: italic;
    }

    /* Animations */
    @keyframes slideDown {
        from { transform: translateY(-40px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* ================================
       RESPONSIVE
    ================================ */
    @media (max-width: 900px) {
        .menu-toggle { display: block; }
        .sidebar { transform: translateX(-100%); }
        .sidebar.active { transform: translateX(0); }
    }

    /* ===== Language Switcher ===== */
    .lang-switcher {
        margin-right: 15px;
        font-weight: 600;
    }
    .lang-switcher a {
        color: white;
        text-decoration: none;
        margin: 0 3px;
        padding: 4px 6px;
        border-radius: 4px;
        transition: background 0.3s;
    }
    .lang-switcher a:hover,
    .lang-switcher a.active-lang {
        background: rgba(255,255,255,0.2);
    }
</style>

<script>
    // Sidebar toggle (mobile)
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }

    function toggleSidebarCollapse() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
        let state = sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded';

        // Save state to session
        fetch('../admin/save_sidebar_state.php', {
            method: 'POST',
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "state=" + state
        });

        // Add blink effect when expanding
        if (state === "expanded") {
            sidebar.classList.add("blink");
            setTimeout(() => sidebar.classList.remove("blink"), 2000);
        }
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
</script>

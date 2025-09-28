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
<?php
function isMobile() {
    return preg_match('/(android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile)/i', $_SERVER['HTTP_USER_AGENT']);
}

if (isMobile()) {
    header("Location: ../mobile-block.php");
    exit();
}
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
    /* Modal Overlay */
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

    /* Modal Content */
    .notif-modal-content {
        background: #fff;
        margin: 80px auto;
        padding: 0;
        border-radius: 20px;
        width: 400px;
        max-height: 75%;
        overflow-y: auto;
        box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        font-family: 'Poppins', sans-serif;
        animation: slideDown 0.35s ease;
        border-top: 6px solid #004080;
    }

    /* Header */
    .notif-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 24px;
        background: linear-gradient(90deg, #004080, #0066cc);
        border-radius: 16px 16px 0 0;
        color: white;
    }

    .notif-header h3 {
        font-size: 18px;
        font-weight: 600;
    }

    .close-btn {
        background: transparent;
        border: none;
        color: white;
        font-size: 22px;
        cursor: pointer;
        transition: transform 0.3s ease, color 0.3s ease;
    }

    .close-btn:hover {
        color: #ff4d4d;
        transform: rotate(90deg);
    }

    /* Clear Button */
    .clear-btn {
        background: #ff6600;
        color: #fff;
        border: none;
        padding: 8px 16px;
        margin: 12px 24px 8px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .clear-btn:hover {
        background: #e65c00;
        transform: translateY(-2px);
    }

    /* List Items */
    .notif-modal-content ul {
        list-style: none;
        padding: 0 24px 18px 24px;
        margin: 0;
    }

    .notif-modal-content li {
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

    .notif-modal-content li:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    /* Message layout */
    .msg-left {
        display: flex;
        flex-direction: column;
    }

    .msg-left strong {
        font-weight: 600;
        color: #004080;
    }

    .msg-left .time {
        font-size: 12px;
        color: #888;
        margin-top: 4px;
        font-style: italic;
    }

    .msg-right {
        max-width: 60%;
        font-size: 14px;
        color: #333;
    }

    /* No messages text */
    .no-messages {
        text-align: center;
        padding: 20px;
        font-style: italic;
        color: #666;
    }

    /* Animations */
    @keyframes slideDown {
        from { transform: translateY(-40px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    /* Scrollbar styling */
    .notif-modal-content::-webkit-scrollbar {
        width: 6px;
    }

    .notif-modal-content::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.2);
        border-radius: 3px;
    }

    .notif-modal-content::-webkit-scrollbar-track {
        background: transparent;
    }

    /* Responsive */
    @media screen and (max-width: 500px) {
        .notif-modal-content {
            width: 90%;
            margin: 60px auto;
        }
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

<script>
    document.getElementById("clearUnread")?.addEventListener("click", function() {
        if (confirm("Are you sure you want to clear all unread messages?")) {
            fetch("../admin/clear_unread.php", { method: "POST" })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("✅ All unread messages cleared!");
                        location.reload(); // reload to update modal
                    } else {
                        alert("❌ Failed to clear messages.");
                    }
                });
        }
    });
</script>

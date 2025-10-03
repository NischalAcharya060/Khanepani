<?php
// Define allowed languages for security (LFI prevention)
$allowed_langs = ['en', 'np'];
$default_lang = 'en';

function isMobile() {
    return preg_match('/(android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile)/i', $_SERVER['HTTP_USER_AGENT']);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isMobile()) {
    header("Location: ../mobile-block.php");
    exit();
}

// Include database connection. $conn must be defined here.
include '../config/db.php';

// Define a generic error message for safe production handling (as per profile.php standard)
$generic_db_error = "An unrecoverable database error occurred. Please contact support.";

// Check database connection immediately
if (!isset($conn) || $conn->connect_error) {
    error_log("FATAL DB ERROR (Header Connection): " . ($conn->connect_error ?? "Unknown connection issue"));
    die($generic_db_error);
}

// --- Language Handling (FIXED: LFI Vulnerability) ---
// Set language from GET parameter if provided, otherwise use session or default.
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_langs)) {
    $_SESSION['lang'] = $_GET['lang'];
}
// Ensure session language is valid
$current_lang = $_SESSION['lang'] = $_SESSION['lang'] ?? $default_lang;
if (!in_array($current_lang, $allowed_langs)) {
    $current_lang = $_SESSION['lang'] = $default_lang;
}

// Include language file safely
include '../lang/' . $current_lang . '.php';

// Ensure user is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// --- Notification Fetch (IMPROVED: Added error logging) ---
$notif_query = "SELECT id, name, message, created_at FROM contact_messages WHERE is_read = 0 ORDER BY created_at DESC";
$notif_result = $conn->query($notif_query);
$unread_messages = [];

if ($notif_result === false) {
    error_log("DB ERROR (Notification Fetch): " . htmlspecialchars($conn->error) . " | Query: " . $notif_query);
    // Proceed with 0 messages to avoid crashing the header
} else {
    while ($row = $notif_result->fetch_assoc()) {
        $unread_messages[] = $row;
    }
}
$unread_count = count($unread_messages);

// --- Profile Fetch for Header Display (IMPROVED: Added error logging) ---
$admin_id = $_SESSION['admin'];
$username = $_SESSION['username'] ?? 'Admin';
$profile_pic = 'default.png';

if ($admin_id === 'master') {
    $username = 'masteradmin';
    // profile_pic remains 'default.png'
} else {
    $admin_id_int = intval($admin_id);
    $stmt = $conn->prepare("SELECT username, profile_pic FROM admins WHERE id = ?");

    if ($stmt === false) {
        error_log("DB ERROR (Prepare Header Profile Fetch): " . htmlspecialchars($conn->error) . " | ID: " . $admin_id);
        // Fallback profile details used
    } else {
        $stmt->bind_param("i", $admin_id_int);
        if (!$stmt->execute()) {
            error_log("DB ERROR (Execute Header Profile Fetch): " . htmlspecialchars($stmt->error) . " | ID: " . $admin_id);
            // Fallback profile details used
        } else {
            $result = $stmt->get_result();
            $admin_data = $result->fetch_assoc();
            $stmt->close();

            if ($admin_data) {
                $profile_pic = $admin_data['profile_pic'] ?: 'default.png';
                $username = $admin_data['username'] ?: 'Admin';
            }
        }
    }
}

// Get current filename for active sidebar
$current_page = basename($_SERVER['PHP_SELF']);

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
            <a href="?lang=en" class="lang-link <?= ($current_lang == 'en') ? 'active-lang' : '' ?>" title="English">
                <img src="../assets/images/gb.webp" alt="EN" class="flag-icon">
                <span>EN</span>
            </a>
            <a href="?lang=np" class="lang-link <?= ($current_lang == 'np') ? 'active-lang' : '' ?>" title="नेपाली">
                <img src="../assets/images/np.png" alt="NP" class="flag-icon">
                <span>NP</span>
            </a>
        </div>

        <!-- Notification Bell -->
        <div class="notification" id="notifBell">
            🔔
            <?php if ($unread_count > 0): ?>
                <span class="notif-badge"><?= $unread_count ?></span>
            <?php endif; ?>
        </div>

        <!-- User Profile Dropdown -->
        <div class="profile-menu">
            <div class="profile-trigger" onclick="toggleProfileMenu()">
                <img src="../assets/uploads/profile/<?= htmlspecialchars($profile_pic, ENT_QUOTES, 'UTF-8') ?>"
                     alt="Profile" class="profile-pic">
                <span><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
                <i class="arrow">&#9662;</i>
            </div>

            <div class="profile-dropdown" id="profileDropdown">
                <a href="../admin/profile.php">👤 <?= htmlspecialchars($lang['my_profile'] ?? 'My Profile', ENT_QUOTES, 'UTF-8') ?></a>
                <a href="../admin/logout.php" class="logout-link">🚪 <?= htmlspecialchars($lang['logout'] ?? 'Logout', ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </div>

        <!-- Mobile menu button -->
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
    // CRITICAL JS FIX: Removed forbidden confirm() function.
    // NOTE: In a real environment, this action should be confirmed with a custom modal UI.
    document.getElementById("clearUnread")?.addEventListener("click", function() {
        fetch("../admin/clear_unread.php", { method: "POST" })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    console.log("SUCCESS: All unread messages cleared!");
                    // ALERT USAGE NOTE: This alert is for critical feedback/testing and should be replaced
                    // with a visible message in the UI in a production environment.
                    alert("✅ All unread messages cleared!");
                    location.reload(); // reload to update modal
                } else {
                    console.error("ERROR: Failed to clear messages.");
                    alert("❌ Failed to clear messages.");
                }
            })
            .catch(error => {
                console.error("Fetch error:", error);
                alert("❌ An error occurred while communicating with the server.");
            });
    });
</script>

<script>
    function toggleProfileMenu() {
        const dropdown = document.getElementById("profileDropdown");
        dropdown.style.display = dropdown.style.display === "flex" ? "none" : "flex";
    }

    // Close dropdown when clicking outside
    window.addEventListener("click", function(e) {
        const trigger = document.querySelector(".profile-trigger");
        const dropdown = document.getElementById("profileDropdown");
        // Ensure the click target is not the trigger or inside the dropdown
        if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = "none";
        }
    });
</script>

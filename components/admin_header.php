<?php
include '../config/db.php';

// Count unread messages
$notif_result = $conn->query("SELECT COUNT(*) AS unread FROM contact_messages WHERE is_read = 0");
$notif_row = $notif_result->fetch_assoc();
$unread_count = $notif_row['unread'];
?>
<!-- Header -->
<header class="admin-header">
    <div class="logo">
        <img src="../assets/images/logo.jpg" alt="Logo">
        <h1>सलकपुर खानेपानी</h1>
    </div>
    <div class="user-info">
        <!-- 🔔 Notification Bell -->
        <div class="notification">
            <a href="../admin/messages.php" class="notif-link">
                🔔
                <?php if ($unread_count > 0): ?>
                    <span class="notif-badge"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>
        </div>

        <span>👤 <?= htmlspecialchars($username) ?></span>
        <a href="../admin/logout.php" class="logout-btn">Logout</a>
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    </div>
</header>

<style>
    .notification {
        position: relative;
        display: inline-block;
        margin-right: 15px;
        font-size: 20px;
    }

    .notif-link {
        text-decoration: none;
        color: #333;
        position: relative;
    }

    .notif-link:hover {
        color: #007bff;
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
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

</style>
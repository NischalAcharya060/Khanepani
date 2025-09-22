<?php
session_start();
include '../config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Get notice ID
if (!isset($_GET['id'])) {
    header("Location: manage_notices.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch notice
$stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$notice = $result->fetch_assoc();

if (!$notice) {
    header("Location: manage_notices.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Notice - <?= htmlspecialchars($notice['title']) ?> - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<!-- Header -->
<header class="admin-header">
    <div class="logo">
        <img src="../assets/images/logo.jpg" alt="Logo">
        <h1>सलकपुर खानेपानी</h1>
    </div>
    <div class="user-info">
        <span>👤 <?= htmlspecialchars($username) ?></span>
        <a href="../admin/logout.php" class="logout-btn">Logout</a>
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    </div>
</header>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <ul>
        <li><a href="dashboard.php">🏠 Dashboard</a></li>
        <li><a href="manage_notices.php" class="active">📢 Manage Notices</a></li>
        <li><a href="manage_gallery.php">🖼 Manage Gallery</a></li>
        <li><a href="messages.php">📬 Messages</a></li>
        <li><a href="manage_admin.php">👥 Manage Admin</a></li>
        <li><a href="settings.php">⚙ Settings</a></li>
    </ul>
</aside>

<!-- Main Content -->
<main class="main-content">
    <h2>📄 <?= htmlspecialchars($notice['title']) ?></h2>
    <p class="subtitle">Created on <?= date("d M Y", strtotime($notice['created_at'])) ?></p>

    <div class="notice-content" style="margin-top:20px; line-height:1.6; font-size:16px;">
        <?= nl2br(htmlspecialchars($notice['content'])) ?>
    </div>

    <?php if(!empty($notice['file'])): ?>
        <div class="notice-file" style="margin-top:20px;">
            <strong>Attached File:</strong>
            <?php
            $fileExt = strtolower(pathinfo($notice['file'], PATHINFO_EXTENSION));
            $filePath = "../assets/uploads/" . $notice['file'];

            // If image, display preview
            if(in_array($fileExt, ['jpg','jpeg','png','gif'])): ?>
                <div style="margin-top:10px;">
                    <img src="<?= $filePath ?>" alt="Notice File" style="max-width:400px; border:1px solid #ccc; border-radius:6px;">
                </div>
            <?php else: ?>
                <p><a href="<?= $filePath ?>" target="_blank"><?= htmlspecialchars($notice['file']) ?></a></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <a href="manage_notices.php" class="btn" style="margin-top:20px;">⬅ Back to Notices</a>
</main>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>

</body>
</html>

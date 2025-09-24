<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <ul>
        <li><a href="dashboard.php" class="active">🏠 Dashboard</a></li>
        <li><a href="manage_notices.php">📢 Manage Notices</a></li>
        <li><a href="manage_gallery.php">🖼 Manage Gallery</a></li>
        <li><a href="messages.php">📬 Messages</a></li>
        <li><a href="manage_admin.php">👥 Manage Admin</a></li>
        <li><a href="settings.php">⚙ Settings</a></li>
    </ul>
</aside>

<!-- Main Content -->
<main class="main-content">
    <h2>Welcome back, <?= htmlspecialchars($username) ?> 👋</h2>
    <p class="subtitle">Manage your office efficiently with quick access to tools below.</p>

    <div class="cards">
        <div class="card">
            <h3>📢 Notices</h3>
            <p>Create, publish, and manage office notices.</p>
            <a href="manage_notices.php" class="btn">Manage</a>
        </div>

        <div class="card">
            <h3>📬 Messages</h3>
            <p>Check and reply to messages sent by citizens.</p>
            <a href="messages.php" class="btn">View</a>
        </div>

        <div class="card">
            <h3>⚙ Settings</h3>
            <p>Change password, update profile, and system settings.</p>
            <a href="settings.php" class="btn">Settings</a>
        </div>
    </div>
</main>

<script>
    // Sidebar toggle for mobile view
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>

</body>
</html>

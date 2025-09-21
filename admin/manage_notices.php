<?php
session_start();
include '../config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_notices.php");
    exit();
}

// Fetch all notices
$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC");

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Notices - सलकपुर खानेपानी</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

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

<aside class="sidebar" id="sidebar">
    <ul>
        <li><a href="dashboard.php">🏠 Dashboard</a></li>
        <li><a href="manage_notices.php" class="active">📢 Manage Notices</a></li>
        <li><a href="messages.php">📬 Messages</a></li>
        <li><a href="settings.php">⚙ Settings</a></li>
    </ul>
</aside>

<main class="main-content">
    <h2>📢 Manage Notices</h2>
    <p class="subtitle">Add, edit, view, or remove notices quickly and efficiently.</p>

    <a href="add_notice.php" class="btn">➕ Add New Notice</a>

    <table class="notice-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($notices->num_rows > 0): ?>
            <?php while ($notice = $notices->fetch_assoc()): ?>
                <tr>
                    <td><?= $notice['id'] ?></td>
                    <td><?= htmlspecialchars($notice['title']) ?></td>
                    <td><?= date("d M Y", strtotime($notice['created_at'])) ?></td>
                    <td>
                        <a href="view_notice.php?id=<?= $notice['id'] ?>" class="btn small">👁 View</a>
                        <a href="edit_notice.php?id=<?= $notice['id'] ?>" class="btn small">✏ Edit</a>
                        <a href="manage_notices.php?delete=<?= $notice['id'] ?>" class="btn small danger" onclick="return confirm('Are you sure you want to delete this notice?');">🗑 Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" style="text-align:center; padding:20px;">No notices found.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</main>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>

</body>
</html>

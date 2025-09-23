<?php
session_start();
include '../config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Get message ID from query
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: messages.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch message
$stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$message = $result->fetch_assoc();

if (!$message) {
    header("Location: messages.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Message - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background: #f4f6f9; margin: 0; }
        .main-content { padding: 40px 20px; max-width: 900px; margin: auto; }

        .message-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            padding: 30px 35px;
            animation: fadeInUp 0.4s ease;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .message-header h2 { color: #007bff; margin: 0; font-size: 28px; }
        .message-header a {
            text-decoration: none;
            color: #fff;
            background: #007bff;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .message-header a:hover { background: #0056b3; }

        .message-field { margin-bottom: 18px; }
        .message-label { font-weight: 600; color: #555; display: block; margin-bottom: 6px; font-size: 14px; }
        .message-value {
            background: #f4f6f9;
            padding: 12px 14px;
            border-radius: 8px;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 15px;
            color: #333;
        }

        .message-value.email { color: #007bff; font-weight: 500; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
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
        <li><a href="manage_notices.php">📢 Manage Notices</a></li>
        <li><a href="manage_gallery.php">🖼 Manage Gallery</a></li>
        <li><a href="messages.php" class="active">📬 Messages</a></li>
        <li><a href="manage_admin.php">👥 Manage Admin</a></li>
        <li><a href="settings.php">⚙ Settings</a></li>
    </ul>
</aside>

<main class="main-content">
    <div class="message-card">
        <div class="message-header">
            <h2>📬 Message Details</h2>
            <a href="messages.php">« Back</a>
        </div>

        <div class="message-field">
            <span class="message-label">Name</span>
            <div class="message-value"><?= htmlspecialchars($message['name']) ?></div>
        </div>

        <div class="message-field">
            <span class="message-label">Email</span>
            <div class="message-value email"><?= htmlspecialchars($message['email']) ?></div>
        </div>

        <div class="message-field">
            <span class="message-label">Subject</span>
            <div class="message-value"><?= htmlspecialchars($message['subject']) ?></div>
        </div>

        <div class="message-field">
            <span class="message-label">Message</span>
            <div class="message-value"><?= nl2br(htmlspecialchars($message['message'])) ?></div>
        </div>

        <div class="message-field">
            <span class="message-label">Sent At</span>
            <div class="message-value"><?= date("d M Y, h:i A", strtotime($message['created_at'])) ?></div>
        </div>
    </div>
</main>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>

</body>
</html>

<?php
session_start();
include '../config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $file_path = null;

    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload_dir = '../assets/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $file_name = time() . '_' . basename($_FILES['file']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = $file_name;
        } else {
            $error = "Failed to upload file.";
        }
    }

    if ($title && $content) {
        $stmt = $conn->prepare("INSERT INTO notices (title, content, file, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $title, $content, $file_path);
        $stmt->execute();

        header("Location: manage_notices.php");
        exit();
    } else {
        $error = "Please fill in all required fields!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Notice - Admin - सलकपुर खानेपानी</title>
    <link rel="stylesheet" href="../css/admin.css">
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
    </div>
</header>

<aside class="sidebar" id="sidebar">
    <ul>
        <li><a href="dashboard.php">🏠 Dashboard</a></li>
        <li><a href="manage_notices.php" class="active">📢 Manage Notices</a></li>
        <li><a href="gallery_add.php">🖼 Add Gallery</a></li>
        <li><a href="messages.php">📬 Messages</a></li>
        <li><a href="add_admin.php">👤 Add Admin</a></li>
        <li><a href="settings.php">⚙ Settings</a></li>
    </ul>
</aside>

<main class="main-content">
    <h2>➕ Add New Notice</h2>
    <?php if(isset($error)): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>
    <form method="POST" class="notice-form" enctype="multipart/form-data">
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" required>

        <label for="content">Content:</label>
        <textarea name="content" id="content" rows="8" required></textarea>

        <label for="file">Upload Image or File (optional):</label>
        <input type="file" name="file" id="file" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">

        <div style="margin-top: 15px;">
            <button type="submit" class="btn">Add Notice</button>
            <a href="manage_notices.php" class="btn" style="background:#888;">Back</a>
        </div>
    </form>
</main>

</body>
</html>

<?php
session_start();
include '../config/db.php';

// ✅ Restrict access (only logged-in admin)
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $imageName = $_FILES['image']['name'];
    $imageTmp = $_FILES['image']['tmp_name'];
    $targetDir = "../assets/uploads/";
    $targetFile = $targetDir . basename($imageName);

    // Allow only image types
    $allowedTypes = ['jpg','jpeg','png','gif'];
    $fileExt = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    if (in_array($fileExt, $allowedTypes)) {
        if (move_uploaded_file($imageTmp, $targetFile)) {
            $sql = "INSERT INTO gallery (title, image, created_at) VALUES ('$title', '$imageName', NOW())";
            if (mysqli_query($conn, $sql)) {
                $success = "✅ Image uploaded successfully!";
            } else {
                $error = "❌ Database error: " . mysqli_error($conn);
            }
        } else {
            $error = "❌ Error uploading file.";
        }
    } else {
        $error = "❌ Only JPG, JPEG, PNG, GIF allowed.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Gallery Image - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
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
        <li><a href="manage_notices.php">📢 Manage Notices</a></li>
        <li><a href="gallery_add.php" class="active">🖼 Add Gallery</a></li>
        <li><a href="messages.php">📬 Messages</a></li>
        <li><a href="manage_admin.php">👥 Manage Admin</a></li>
        <li><a href="settings.php">⚙ Settings</a></li>
    </ul>
</aside>

<!-- Main Content -->
<main class="main-content">
    <h2>🖼 Add New Image to Gallery</h2>
    <p class="subtitle">Upload photos to display in the public gallery.</p>

    <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="POST" enctype="multipart/form-data" class="gallery-form">
        <div class="input-group">
            <label>Image Title (optional)</label>
            <input type="text" name="title" placeholder="Enter image title">
        </div>

        <div class="input-group">
            <label>Select Image</label>
            <input type="file" name="image" id="imageInput" accept="image/*" required>
        </div>

        <!-- Image Preview -->
        <div class="input-group" id="previewContainer" style="display:none;">
            <label>Preview:</label>
            <img id="imagePreview" src="" alt="Image Preview" style="max-width:100%; border-radius:8px; margin-top:10px;">
        </div>

        <button type="submit" class="btn-submit">📤 Upload</button>
    </form>
</main>

<script>
    // Live Image Preview
    const imageInput = document.getElementById('imageInput');
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');

    imageInput.addEventListener('change', () => {
        const file = imageInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => {
                imagePreview.src = e.target.result;
                previewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = 'none';
        }
    });
</script>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>

</body>
</html>

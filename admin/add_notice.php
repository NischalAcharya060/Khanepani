<?php
session_start();
include '../config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Include language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
include "../lang/" . $_SESSION['lang'] . ".php";

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
            $error = $lang['file_upload_failed'] ?? "Failed to upload file.";
        }
    }

    if ($title && $content) {
        $stmt = $conn->prepare("INSERT INTO notices (title, content, file, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $title, $content, $file_path);
        $stmt->execute();

        header("Location: manage_notices.php");
        exit();
    } else {
        $error = $lang['fill_required'] ?? "Please fill in all required fields!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['add_notice'] ?> - Admin - <?= $lang['logo'] ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <h2>➕ <?= $lang['add_notice'] ?></h2>
    <?php if(isset($error)): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>
    <form method="POST" class="notice-form" enctype="multipart/form-data">
        <label for="title"><?= $lang['notice_title'] ?>:</label>
        <input type="text" name="title" id="title" required>

        <label for="content"><?= $lang['notice_description'] ?>:</label>
        <textarea name="content" id="content" rows="8" required></textarea>

        <label for="file"><?= $lang['notice_file_optional'] ?? 'Upload Image or File (optional):' ?></label>
        <input type="file" name="file" id="file" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">

        <div style="margin-top: 15px;">
            <button type="submit" class="btn"><?= $lang['add_notice'] ?></button>
            <a href="manage_notices.php" class="btn" style="background:#888;"><?= $lang['back'] ?? 'Back' ?></a>
        </div>
    </form>
</main>

</body>
</html>

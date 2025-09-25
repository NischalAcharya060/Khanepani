<?php
session_start();
include '../config/db.php';
include '../config/lang.php';

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $file_path = $notice['file']; // Keep old file if not replaced

    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload_dir = '../assets/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $file_name = time() . '_' . basename($_FILES['file']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            // Delete old file if exists
            if ($notice['file'] && file_exists($upload_dir . $notice['file'])) {
                unlink($upload_dir . $notice['file']);
            }
            $file_path = $file_name;
        } else {
            $error = $lang['file_upload_failed'] ?? "Failed to upload file.";
        }
    }

    if ($title && $content) {
        $stmt = $conn->prepare("UPDATE notices SET title = ?, content = ?, file = ? WHERE id = ?");
        $stmt->bind_param("sssi", $title, $content, $file_path, $id);
        $stmt->execute();

        $_SESSION['success'] = $lang['notice_updated'] ?? "Notice Updated Successfully";
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
    <title><?= $lang['edit_notice'] ?? 'Edit Notice' ?> - Admin - <?= $lang['logo'] ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <h2>✏ <?= $lang['edit_notice'] ?? 'Edit Notice' ?></h2>
    <?php if(isset($error)): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>
    <form method="POST" class="notice-form" enctype="multipart/form-data">
        <label for="title"><?= $lang['notice_title'] ?? 'Title' ?>:</label>
        <input type="text" name="title" id="title" value="<?= htmlspecialchars($notice['title']) ?>" required>

        <label for="content"><?= $lang['notice_description'] ?? 'Description' ?>:</label>
        <textarea name="content" id="content" rows="8" required><?= htmlspecialchars($notice['content']) ?></textarea>

        <?php if($notice['file']): ?>
            <p><?= $lang['notice_file'] ?? 'Current File' ?>:
                <a href="../assets/uploads/<?= htmlspecialchars($notice['file']) ?>" target="_blank"><?= htmlspecialchars($notice['file']) ?></a>
            </p>
        <?php endif; ?>

        <label for="file"><?= $lang['replace_file'] ?? 'Replace File/Image (optional)' ?>:</label>
        <input type="file" name="file" id="file" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">

        <div style="margin-top: 15px;">
            <button type="submit" class="btn"><?= $lang['update'] ?? 'Update' ?> <?= $lang['notice'] ?? 'Notice' ?></button>
            <a href="manage_notices.php" class="btn" style="background:#888;"><?= $lang['cancel'] ?? 'Cancel' ?></a>
        </div>
    </form>
</main>

</body>
</html>

<?php
session_start();
include '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$msg = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_title = trim($_POST['site_title']);
    $contact_email = trim($_POST['contact_email']);
    $contact_phone = trim($_POST['contact_phone']);

    // Save settings to database (you can create a 'settings' table with columns site_title, contact_email, contact_phone)
    $stmt = $conn->prepare("UPDATE settings SET site_title=?, contact_email=?, contact_phone=? WHERE id=1");
    $stmt->bind_param("sss", $site_title, $contact_email, $contact_phone);

    if ($stmt->execute()) {
        $msg = "✅ Settings updated successfully!";
    } else {
        $msg = "❌ Failed to update settings!";
    }
}

// Fetch current settings
$result = mysqli_query($conn, "SELECT * FROM settings WHERE id=1");
$settings = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .main-content { padding: 30px; }
        h2 { font-size: 26px; margin-bottom: 15px; color: #333; }
        label { display: block; margin: 12px 0 5px; font-weight: 500; }
        input[type=text], input[type=email] {
            width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; box-sizing: border-box;
        }
        button {
            background: #007bff; color: #fff; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; margin-top: 15px;
        }
        button:hover { background: #0069d9; }

        .message { margin-bottom: 15px; padding: 12px; border-radius: 8px; font-size: 14px; }
        .success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <h2>⚙ Admin Settings</h2>

    <?php if($msg): ?>
        <div class="message <?= strpos($msg, '✅') === 0 ? 'success' : 'error' ?>"><?= $msg ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="site_title">Site Title</label>
        <input type="text" name="site_title" id="site_title" value="<?= htmlspecialchars($settings['site_title']) ?>" required>

        <label for="contact_email">Contact Email</label>
        <input type="email" name="contact_email" id="contact_email" value="<?= htmlspecialchars($settings['contact_email']) ?>" required>

        <label for="contact_phone">Contact Phone</label>
        <input type="text" name="contact_phone" id="contact_phone" value="<?= htmlspecialchars($settings['contact_phone']) ?>" required>

        <button type="submit">Update Settings</button>
    </form>
</main>

</body>
</html>

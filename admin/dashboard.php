<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];

// Language handling
if (isset($_GET['lang'])) {
    $lang_code = $_GET['lang'];
    $_SESSION['lang'] = $lang_code;
} elseif (isset($_SESSION['lang'])) {
    $lang_code = $_SESSION['lang'];
} else {
    $lang_code = 'en'; // default
}

// Load language file
if ($lang_code === 'np') {
    include '../lang/np.php';
} else {
    include '../lang/en.php';
}
?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['dashboard'] ?> - <?= $lang['logo'] ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Language switcher */
        .lang-switch { text-align: right; margin: 10px 20px; }
        .lang-switch a { text-decoration: none; padding: 6px 12px; border-radius: 5px; background: #eee; color: #333; margin-left: 5px; }
        .lang-switch a.active { background: #007bff; color: #fff; }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<!-- Main Content -->
<main class="main-content">
    <h2><?= $lang['welcome_back'] ?? 'Welcome back' ?>, <?= htmlspecialchars($username) ?> 👋</h2>
    <p class="subtitle"><?= $lang['dashboard_subtitle'] ?? 'Manage your office efficiently with quick access to tools below.' ?></p>

    <div class="cards">
        <div class="card">
            <h3>📢 <?= $lang['notices'] ?></h3>
            <p><?= $lang['notices_desc'] ?? 'Create, publish, and manage office notices.' ?></p>
            <a href="manage_notices.php" class="btn"><?= $lang['manage'] ?? 'Manage' ?></a>
        </div>

        <div class="card">
            <h3>📬 <?= $lang['messages'] ?></h3>
            <p><?= $lang['messages_desc'] ?? 'Check and reply to messages sent by citizens.' ?></p>
            <a href="messages.php" class="btn"><?= $lang['view'] ?? 'View' ?></a>
        </div>

        <div class="card">
            <h3>⚙ <?= $lang['settings'] ?></h3>
            <p><?= $lang['settings_desc'] ?? 'Change password, update profile, and system settings.' ?></p>
            <a href="settings.php" class="btn"><?= $lang['settings'] ?></a>
        </div>
    </div>
</main>

</body>
</html>

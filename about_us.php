<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database & language
include 'config/db.php';
include 'config/lang.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['about_us'] ?> - <?= $lang['logo'] ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<style>
    .about-container {
        max-width: 1200px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .section-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .section-header h1 {
        font-size: 2.2rem;
        font-weight: 700;
        color: #222;
    }

    .section-header .subtitle {
        font-size: 1rem;
        color: #666;
        margin-top: 8px;
    }


</style>
<body>

<?php include 'components/header.php'; ?>

<section class="about-container">
    <div class="section-header">
        <h1><i class="fa-solid fa-building"></i> <?= $lang['about_us'] ?></h1>
        <p class="subtitle"><?= $lang['about_us_desc'] ?? 'Learn more about our mission, vision, and services.' ?></p>
    </div>

</section>

<?php include 'components/footer.php'; ?>

</body>
</html>

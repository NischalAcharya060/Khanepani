<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database & language
include 'config/db.php';
include 'config/lang.php';
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['our_services'] ?> - <?= $lang['logo'] ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<style>
    .services-container {
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

<section class="services-container">
    <div class="section-header">
        <h1><i class="fa-solid fa-hand-holding-water"></i> <?= $lang['our_services'] ?></h1>
        <p class="subtitle"><?= $lang['our_services_desc'] ?? 'Discover the services we provide to support the community.' ?></p>
    </div>

</section>

<?php include 'components/footer.php'; ?>

</body>
</html>

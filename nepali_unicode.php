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
    <title><?= $lang['nepali_unicode'] ?> - <?= $lang['logo'] ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<style>
    body {
        font-family: 'Roboto', sans-serif;
        background: #f9fafb;
        color: #333;
        margin: 0;
        padding: 0;
    }

    .unicode-container {
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

    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 24px;
    }

    .card {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        display: flex;
        flex-direction: column;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .card img {
        width: 100%;
        height: 180px;
        object-fit: cover;
    }

    .card-content {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        flex: 1;
    }

    .card-content.only-text {
        text-align: center;
        justify-content: center;
    }

    .card h3 {
        font-size: 1.2rem;
        font-weight: 600;
        color: #111;
    }

    .card p {
        font-size: 0.95rem;
        line-height: 1.5;
        color: #555;
    }

    .download-btn {
        margin-top: auto;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #007bff, #0056d6);
        color: #fff;
        padding: 10px 16px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.95rem;
        transition: background 0.3s ease;
    }

    .download-btn:hover {
        background: linear-gradient(135deg, #0056d6, #003da8);
    }

    .card:hover {
        transform: translateY(-6px);
        box-shadow: 0 8px 22px rgba(0,0,0,0.12);
    }

</style>
<body>

<?php include 'components/header.php'; ?>

<section class="unicode-container">
    <div class="section-header">
        <h1><i class="fa-solid fa-language"></i> <?= $lang['nepali_unicode'] ?></h1>
        <p class="subtitle"><?= $lang['nepali_unicode_desc'] ?? 'Download Nepali Unicode tools, fonts, and typing guides in one place.' ?></p>
    </div>

    <div class="cards">
        <div class="card">
            <img src="assets/images/nepali-unicode-romanized.jpg" alt="Romanized Unicode">
            <div class="card-content">
                <h3><?= $lang['nepali_unicode_romanized'] ?></h3>
                <p><?= $lang['nepali_unicode_romanized_desc'] ?? 'Type Nepali text using Romanized script that converts to Unicode.' ?></p>
                <a href="assets/files/nepali_romanised.zip" download class="download-btn">
                    <i class="fa-solid fa-download"></i> <?= $lang['user_download'] ?>
                </a>
            </div>
        </div>

        <div class="card">
            <img src="assets/images/nepali-unicode-traditional.jpg" alt="Traditional Unicode">
            <div class="card-content">
                <h3><?= $lang['nepali_unicode_traditional'] ?></h3>
                <p><?= $lang['nepali_unicode_traditional_desc'] ?? 'Traditional Nepali Unicode layout for typing.' ?></p>
                <a href="assets/files/nepali_Traditional.zip" download class="download-btn">
                    <i class="fa-solid fa-download"></i> <?= $lang['user_download'] ?>
                </a>
            </div>
        </div>

        <div class="card">
            <img src="assets/images/kalimati-font-keyboard.jpg" alt="Kalimati Font">
            <div class="card-content">
                <h3><?= $lang['kalimati_font'] ?></h3>
                <p><?= $lang['kalimati_font_desc'] ?? 'Nepali Kalimati font for typing and display.' ?></p>
                <a href="assets/files/Kalimati.ttf" download class="download-btn">
                    <i class="fa-solid fa-download"></i> <?= $lang['user_download'] ?>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-content only-text">
                <h3><?= $lang['typing_guide'] ?></h3>
                <p><?= $lang['typing_guide_desc'] ?? 'Download the Nepali Unicode typing guide sheet.' ?></p>
                <a href="assets/files/Nepali_Unicode_Type_Guide.pdf" download class="download-btn">
                    <i class="fa-solid fa-file-pdf"></i> <?= $lang['user_download'] ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'components/footer.php'; ?>

</body>
</html>

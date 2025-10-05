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
    :root {
        --primary-color: #0077b6;
        --primary-hover: #005f93;
        --background-light: #f4f7f9;
        --card-background: #ffffff;
        --text-dark: #2c3e50;
        --text-medium: #7f8c8d;
        --shadow-light: rgba(0, 0, 0, 0.05);
        --shadow-medium: rgba(0, 0, 0, 0.1);
        --border-radius-large: 18px;
    }

    body {
        font-family: 'Roboto', sans-serif;
        background: var(--background-light);
        color: var(--text-dark);
        margin: 0;
        padding: 0;
        line-height: 1.6;
    }

    .unicode-container {
        max-width: 1280px;
        margin: 80px auto 100px auto;
        padding: 0 30px;
    }

    .section-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .section-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--text-dark);
        letter-spacing: -0.5px;
        margin-bottom: 10px;
    }

    .section-header h1 i {
        color: var(--primary-color);
        margin-right: 10px;
    }

    .section-header .subtitle {
        font-size: 1.1rem;
        color: var(--text-medium);
        max-width: 700px;
        margin: 0 auto;
    }

    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
    }

    .card {
        background: var(--card-background);
        border-radius: var(--border-radius-large);
        overflow: hidden;
        box-shadow: 0 6px 20px var(--shadow-light);
        display: flex;
        flex-direction: column;
        transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        border: 1px solid #eaf0f4;
    }

    .card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-top-left-radius: var(--border-radius-large);
        border-top-right-radius: var(--border-radius-large);
    }

    .card-content {
        padding: 25px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        flex: 1;
    }

    .card-content.only-text {
        text-align: center;
        justify-content: center;
        padding: 40px 25px;
    }

    .card h3 {
        font-size: 1.35rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-top: 0;
    }

    .card p {
        font-size: 1rem;
        line-height: 1.6;
        color: var(--text-medium);
    }

    .download-btn {
        margin-top: auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: linear-gradient(45deg, var(--primary-color) 0%, #00a8e8 100%);
        color: #fff;
        padding: 12px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 500;
        font-size: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(0, 119, 182, 0.3);
    }

    .download-btn:hover {
        background: linear-gradient(45deg, #005f93 0%, #008cc9 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 119, 182, 0.4);
    }

    .card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px var(--shadow-medium);
    }

    @media (max-width: 768px) {
        .unicode-container {
            margin: 40px auto;
            padding: 0 20px;
        }

        .section-header h1 {
            font-size: 2rem;
        }
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

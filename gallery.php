<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database and language
include 'config/database/db.php';

// Language handling
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','np'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Include language file
$langFile = __DIR__ . '/lang/' . $_SESSION['lang'] . '.php';
if (file_exists($langFile)) {
    include $langFile;
} else {
    include __DIR__ . '/lang/en.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['photo_gallery'] ?> - <?= $lang['logo'] ?></title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.6;
        }

        .gallery {
            max-width: 1280px;
            margin: 60px auto;
            padding: 0 30px;
        }

        .gallery h2 {
            text-align: center;
            margin-bottom: 50px;
            font-size: 40px;
            font-weight: 900;
            color: #1a237e;
            letter-spacing: 1px;
            position: relative;
        }

        .gallery h2::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: #ff6f00;
            margin: 10px auto 0;
            border-radius: 2px;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .album-card {
            position: relative;
            cursor: pointer;
            transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94), box-shadow 0.3s ease;
            border-radius: 12px;
            display: inline-block;
            overflow: visible;
        }

        .album-card:hover {
            transform: translateY(-10px) scale(1.02);
            z-index: 10;
        }

        .album-card.pile::before,
        .album-card.pile::after {
            content: "";
            position: absolute;
            top: 8px;
            left: 8px;
            right: -8px;
            bottom: -8px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            z-index: 0;
            transform: rotate(-2deg);
            transition: all 0.3s ease;
        }
        .album-card.pile::after {
            top: 16px;
            left: 16px;
            right: -16px;
            bottom: -16px;
            transform: rotate(3deg);
            opacity: 0.8;
        }

        .album-card:hover.pile::before {
            transform: rotate(-4deg) translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        .album-card:hover.pile::after {
            transform: rotate(5deg) translateY(-2px);
        }

        .album-pile {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            background: #fff;
            z-index: 1;
        }

        .album-card:hover .album-pile {
            box-shadow: 0 15px 35px rgba(0,0,0,0.25);
        }

        .album-image {
            height: 250px;
            overflow: hidden;
            border-radius: 12px 12px 0 0;
        }
        .album-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }
        .album-card:hover .album-image img {
            transform: scale(1.1);
        }

        .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to top, rgba(0,0,0,0.85), rgba(0,0,0,0.4), transparent);
            color: #fff;
            padding: 15px;
            border-radius: 0 0 12px 12px;
            z-index: 2;
            box-sizing: border-box;
        }

        .overlay .title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        }
        .overlay .count {
            font-size: 14px;
            font-weight: 500;
            opacity: 0.95;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .overlay .count::before {
            font-family: "Font Awesome 6 Free";
            content: "\f03e";
            font-weight: 900;
        }

        .no-data {
            grid-column: 1/-1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 20px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            color: #7f8c8d;
            font-size: 22px;
            font-weight: 500;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .no-data:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .no-data i {
            font-size: 64px;
            margin-bottom: 25px;
            color: #95a5a6;
            transition: color 0.3s ease;
        }

        .no-data:hover i {
            color: #34495e;
        }

        @media (max-width: 768px) {
            .gallery {
                margin: 30px auto;
                padding: 0 15px;
            }

            .gallery h2 {
                font-size: 32px;
                margin-bottom: 30px;
            }

            .album-image {
                height: 200px;
            }
        }
    </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<section class="gallery container">
    <h2><?= $lang['photo_gallery'] ?></h2>
    <div class="gallery-grid">
        <?php
        // Fetch albums - latest first
        $albums_sql = "SELECT id, name FROM albums ORDER BY id DESC";
        $albums_result = mysqli_query($conn, $albums_sql);

        $hasAlbums = false; // flag to check if any album has images

        if(mysqli_num_rows($albums_result) > 0) {
            while($album = mysqli_fetch_assoc($albums_result)) {
                $album_id = $album['id'];
                $album_name = $album['name'];

                // Get images in this album
                $images_sql = "SELECT image FROM gallery WHERE album_id = $album_id ORDER BY created_at DESC";
                $images_result = mysqli_query($conn, $images_sql);
                $images = [];
                while($img = mysqli_fetch_assoc($images_result)) {
                    $images[] = $img['image'];
                }

                if(count($images) > 0) {
                    $hasAlbums = true;
                    $coverImage = "assets/uploads/".$images[0];
                    $pileClass = (count($images) > 1) ? "pile" : "";

                    echo "<div class='album-card $pileClass' onclick=\"location.href='album.php?id=$album_id'\">";
                    echo "  <div class='album-pile'>";
                    echo "      <div class='album-image'>";
                    echo "          <img src='$coverImage' alt='$album_name'>";
                    echo "      </div>";
                    echo "      <div class='overlay'>";
                    echo "          <div class='title'>$album_name</div>";
                    echo "          <div class='count'>".count($images)." images</div>";
                    echo "      </div>";
                    echo "  </div>";
                    echo "</div>";
                }
            }
        }

        // Show message if no albums with images
        if(!$hasAlbums) {
            echo "<div class='no-data'>
                <i class='fa-regular fa-images'></i>
                {$lang['no_albums_found']}
              </div>";
        }
        ?>
    </div>
</section>

<?php include 'components/footer.php'; ?>

</body>
</html>

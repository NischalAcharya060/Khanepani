<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config/db.php';

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','np'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$langFile = __DIR__ . '/lang/' . $_SESSION['lang'] . '.php';
if (file_exists($langFile)) {
    include $langFile;
} else {
    include __DIR__ . '/lang/en.php';
}

if(!isset($_GET['id'])) {
    header("Location: gallery.php");
    exit();
}

$album_id = intval($_GET['id']);

$album_sql = "SELECT name FROM albums WHERE id = $album_id";
$album_result = mysqli_query($conn, $album_sql);
$album = mysqli_fetch_assoc($album_result);
$album_name = $album ? $album['name'] : ($lang['user_album'] ?? 'Album');

$images_sql = "SELECT * FROM gallery WHERE album_id = $album_id ORDER BY created_at DESC";
$images_result = mysqli_query($conn, $images_sql);

?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($album_name) ?> - <?= htmlspecialchars($lang['logo'] ?? 'Gallery') ?></title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" />

    <style>
        :root {
            --primary-color: #5b5ffb;
            --secondary-color: #f72585;
            --text-color: #343a40;
            --background-color: #f8f9fa;
            --card-background: #ffffff;
            --shadow-light: 0 4px 12px rgba(0,0,0,0.06);
            --shadow-hover: 0 15px 30px rgba(0,0,0,0.15);
        }

        .album-container {
            max-width: 1400px;
            margin: 100px auto 80px;
            padding: 0 25px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 40px;
            padding: 10px 22px;
            background: var(--primary-color);
            color: #fff;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        .back-link i {
            font-size: 14px;
        }
        .back-link:hover {
            background: #4747e0;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
            transform: translateY(-2px);
        }

        h2 {
            text-align: center;
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 70px;
            color: var(--text-color);
            position: relative;
            letter-spacing: -1px;
        }
        h2::after {
            content: "";
            display: block;
            width: 80px;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            margin: 18px auto 0;
            border-radius: 3px;
        }

        .album-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 35px;
        }

        .album-item {
            position: relative;
            border-radius: 18px;
            overflow: hidden;
            background: var(--card-background);
            box-shadow: var(--shadow-light);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
        .album-item:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
        }
        .album-item a {
            display: block;
            text-decoration: none;
        }

        .album-item img {
            width: 100%;
            height: 280px;
            object-fit: cover;
            display: block;
            transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), filter 0.4s ease;
            border-radius: 18px;
            filter: brightness(0.98);
        }
        .album-item:hover img {
            transform: scale(1.05);
            filter: brightness(1.05);
        }

        .album-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px 20px;
            background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0));
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            opacity: 1;
            min-height: 50px;
            display: flex;
            align-items: flex-end;
            transition: background 0.4s ease;
        }
        .album-item:hover .album-caption {
            background: linear-gradient(to top, rgba(0,0,0,0.9), rgba(0,0,0,0));
        }

        .no-images {
            text-align: center;
            color: #7f8c8d;
            font-size: 18px;
            padding: 40px 0;
            grid-column: 1 / -1;
        }

        @media (max-width: 992px) {
            .album-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }
        @media (max-width: 768px) {
            h2 { font-size: 34px; margin-bottom: 50px; }
            .album-item img { height: 230px; }
            .back-link { margin-bottom: 30px; }
        }
        @media (max-width: 480px) {
            .album-item img { height: 200px; }
            h2 { font-size: 28px; }
            .album-container { padding: 0 15px; }
        }
    </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<section class="album-container">
    <a href="gallery.php" class="back-link">
        <i class="fas fa-arrow-left"></i>
        <?= htmlspecialchars($lang['back_to_albums'] ?? 'Back to Albums') ?>
    </a>
    <h2><?= htmlspecialchars($album_name); ?></h2>
    <div class="album-grid">
        <?php
        if(mysqli_num_rows($images_result) > 0) {
            while($row = mysqli_fetch_assoc($images_result)) {
                $imgPath = "assets/uploads/".$row['image'];
                $title = !empty($row['title']) ? htmlspecialchars($row['title']) : htmlspecialchars($album_name);

                echo "<div class='album-item'>";
                echo "  <a href='$imgPath' data-fancybox='album-gallery' data-caption='$title'>";
                echo "      <img src='$imgPath' alt='$title' loading='lazy'>";
                echo "      <div class='album-caption'>$title</div>";
                echo "  </a>";
                echo "</div>";
            }
        } else {
            echo "<p class='no-images'>No images found in this album.</p>";
        }
        ?>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>

<script>
    $(document).ready(function() {
        $('[data-fancybox="album-gallery"]').fancybox({
            buttons : [
                'zoom',
                'slideShow',
                'fullScreen',
                'download',
                'thumbs',
                'close'
            ],
            loop: true
        });
    });
</script>

</body>
</html>
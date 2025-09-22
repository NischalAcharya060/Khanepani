<?php include 'config/db.php'; ?>
<?php
if(!isset($_GET['id'])) {
    header("Location: gallery.php");
    exit();
}
$album_id = intval($_GET['id']);

// Get album name
$album_sql = "SELECT name FROM albums WHERE id = $album_id";
$album_result = mysqli_query($conn, $album_sql);
$album = mysqli_fetch_assoc($album_result);
$album_name = $album ? $album['name'] : "Album";

// Get all images in this album
$images_sql = "SELECT * FROM gallery WHERE album_id = $album_id ORDER BY created_at DESC";
$images_result = mysqli_query($conn, $images_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $album_name; ?> - Gallery</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- PhotoSwipe CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe-lightbox.css">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
            color: #2c3e50;
        }

        .album-container {
            max-width: 1300px;
            margin: 80px auto;
            padding: 0 20px;
        }

        /* Back link */
        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            padding: 8px 16px;
            background: #3498db;
            color: #fff;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            background: #1d6fa5;
            transform: translateY(-2px);
        }

        /* Heading */
        h2 {
            text-align: center;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 60px;
            color: #34495e;
            position: relative;
        }
        h2::after {
            content: "";
            display: block;
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #8e44ad);
            margin: 15px auto 0;
            border-radius: 2px;
        }

        /* Grid */
        .album-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 28px;
        }

        /* Image Card */
        .album-item {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 12px 25px rgba(0,0,0,0.08);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            background: #fff;
        }
        .album-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .album-item img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
            transition: transform 0.6s ease, filter 0.6s ease;
            border-radius: 15px 15px 0 0;
            filter: brightness(0.95);
        }
        .album-item:hover img {
            transform: scale(1.1);
            filter: brightness(1);
        }

        /* Image Overlay Title */
        .album-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 14px 16px;
            background: linear-gradient(to top, rgba(0,0,0,0.65), rgba(0,0,0,0));
            color: #fff;
            font-size: 15px;
            font-weight: 500;
            opacity: 0;
            transition: opacity 0.4s ease;
            border-radius: 0 0 15px 15px;
        }
        .album-item:hover .album-caption {
            opacity: 1;
        }

        /* Responsive tweaks */
        @media (max-width: 768px) {
            h2 { font-size: 28px; }
            .album-item img { height: 200px; }
        }
        @media (max-width: 480px) {
            .album-item img { height: 180px; }
        }
    </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<section class="album-container">
    <a href="gallery.php" class="back-link">« Back to Albums</a>
    <h2><?php echo $album_name; ?></h2>
    <div class="album-grid">
        <?php
        if(mysqli_num_rows($images_result) > 0) {
            while($row = mysqli_fetch_assoc($images_result)) {
                $imgPath = "assets/uploads/".$row['image'];
                $title = !empty($row['title']) ? $row['title'] : $album_name;

                // Use approximate image dimensions for PhotoSwipe
                $width = 1200;
                $height = 800;

                echo "<div class='album-item'>";
                echo "  <a href='$imgPath' data-pswp-width='$width' data-pswp-height='$height' data-pswp-title='$title'>";
                echo "      <img src='$imgPath' alt='$title'>";
                echo "      <div class='album-caption'>$title</div>";
                echo "  </a>";
                echo "</div>";
            }
        } else {
            echo "<p style='text-align:center; color:#7f8c8d; font-size:16px;'>No images found in this album.</p>";
        }
        ?>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<!-- Initialize PhotoSwipe -->
<script type="module">
    import PhotoSwipeLightbox from 'https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe-lightbox.esm.js';

    const lightbox = new PhotoSwipeLightbox({
        gallery: '.album-grid',
        children: 'a',
        pswpModule: () => import('https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe.esm.js')
    });

    lightbox.init();
</script>

</body>
</html>

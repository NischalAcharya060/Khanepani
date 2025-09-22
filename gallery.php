<?php include 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gallery - Khane Pani Office</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 0;
        }

        .gallery {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
        }
        .gallery h2 {
            text-align: center;
            margin-bottom: 40px;
            font-size: 36px;
            font-weight: 700;
            color: #2c3e50;
        }

        /* Grid layout */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        /* Album Card */
        .album-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            cursor: pointer;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .album-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.12);
        }

        /* Album Image */
        .album-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        .album-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .album-card:hover .album-image img {
            transform: scale(1.08);
        }

        /* Overlay */
        .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
            color: #fff;
            padding: 15px;
        }
        .overlay .title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .overlay .count {
            font-size: 14px;
            opacity: 0.85;
        }
    </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<section class="gallery container">
    <h2>Photo Gallery</h2>
    <div class="gallery-grid">
        <?php
        // Fetch albums - latest first
        $albums_sql = "SELECT id, name FROM albums ORDER BY id DESC";
        $albums_result = mysqli_query($conn, $albums_sql);

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
                $coverImage = "assets/uploads/".$images[0];
                echo "<div class='album-card' onclick=\"location.href='album.php?id=$album_id'\">";
                echo "  <div class='album-image'>";
                echo "      <img src='$coverImage' alt='$album_name'>";
                echo "      <div class='overlay'>";
                echo "          <div class='title'>$album_name</div>";
                echo "          <div class='count'>".count($images)." images</div>";
                echo "      </div>";
                echo "  </div>";
                echo "</div>";
            }
        }
        ?>
    </div>
</section>

<?php include 'components/footer.php'; ?>

</body>
</html>

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
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 25px;
        }

        /* Album Card */
        .album-card {
            position: relative;
            cursor: pointer;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            border-radius: 10px;
            z-index: 1; /* keep card above the pile layers */
        }
        .album-card:hover {
            transform: translateY(-8px) scale(1.02);
        }

        /* Album Pile */
        .album-pile {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 18px rgba(0,0,0,0.15);
            background: #fff;
            z-index: 2;
        }

        /* Pile layers */
        .album-pile::before,
        .album-pile::after {
            content: "";
            position: absolute;
            top: 10px;
            left: 10px;
            right: -10px;
            bottom: -10px;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 6px 14px rgba(0,0,0,0.08);
            z-index: -1;
            transform: rotate(-3deg);
        }
        .album-pile::after {
            top: 18px;
            left: 18px;
            right: -18px;
            bottom: -18px;
            transform: rotate(4deg);
            opacity: 0.9;
        }

        /* Album Image */
        .album-image {
            height: 220px;
            overflow: hidden;
            border-radius: 10px;
        }
        .album-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }
        .album-card:hover .album-image img {
            transform: scale(1.08);
        }

        /* Overlay for title & count */
        .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
            color: #fff;
            padding: 12px;
            border-radius: 0 0 10px 10px;
        }
        .overlay .title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .overlay .count {
            font-size: 13px;
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
        ?>
    </div>
</section>

<?php include 'components/footer.php'; ?>

</body>
</html>

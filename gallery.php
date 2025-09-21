<?php include 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gallery - Khane Pani Office</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .gallery-item img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .gallery-item:hover img {
            transform: scale(1.05);
        }
        .gallery-item .caption {
            position: absolute;
            bottom: 0;
            width: 100%;
            text-align: center;
            background: rgba(0,0,0,0.6);
            color: #fff;
            padding: 8px 0;
            font-size: 16px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .gallery-item:hover .caption {
            opacity: 1;
        }

        /* Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.85);
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 20px;
        }
        .lightbox-content {
            max-width: 90%;
            max-height: 80%;
            border-radius: 10px;
        }
        .lightbox-caption {
            margin-top: 12px;
            color: #ddd;
            text-align: center;
            font-size: 14px;
        }
        .lightbox .close {
            position: absolute;
            top: 25px;
            right: 40px;
            font-size: 40px;
            color: #fff;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<!-- Gallery Section -->
<section class="gallery container">
    <h2>Photo Gallery</h2>
    <div class="gallery-grid">
        <?php
        $sql = "SELECT * FROM gallery ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);

        if(mysqli_num_rows($result) > 0){
            while ($row = mysqli_fetch_assoc($result)) {
                $imagePath = "../assets/uploads/".$row['image'];
                $title = !empty($row['title']) ? $row['title'] : "Image";
                echo "<div class='gallery-item'>";
                echo "<img src='$imagePath' alt='$title' class='clickable'>";
                echo "<div class='caption'>$title</div>";
                echo "</div>";
            }
        } else {
            echo "<p>No images found.</p>";
        }
        ?>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<!-- Lightbox -->
<div id="lightbox" class="lightbox">
    <span class="close">&times;</span>
    <img class="lightbox-content" id="lightbox-img">
    <div class="lightbox-caption" id="lightbox-caption"></div>
</div>

<script>
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const closeBtn = document.querySelector('.lightbox .close');

    document.querySelectorAll('.clickable').forEach(img => {
        img.addEventListener('click', () => {
            lightbox.style.display = 'flex';
            lightboxImg.src = img.src;
            lightboxCaption.innerText = img.alt;
        });
    });

    closeBtn.addEventListener('click', () => lightbox.style.display = 'none');
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) lightbox.style.display = 'none';
    });
</script>

</body>
</html>

<?php include 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gallery - Khane Pani Office</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'components/header.php'; ?>

<!-- Gallery Section -->
<section class="gallery container">
    <h2>Photo Gallery</h2>
    <div class="gallery-grid">
        <?php
        // Fetch images from gallery table (or you can adjust table name)
        $sql = "SELECT * FROM gallery ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);

        if(mysqli_num_rows($result) > 0){
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<div class='gallery-item'>";
                echo "<img src='uploads/".$row['image']."' alt='".$row['title']."' class='clickable'>";
                if(!empty($row['title'])){
                    echo "<div class='caption'>".$row['title']."</div>";
                }
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
    // Lightbox JS (same as homepage/notices)
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const closeBtn = document.querySelector('.lightbox .close');

    document.querySelectorAll('.clickable').forEach(img => {
        img.addEventListener('click', () => {
            lightbox.style.display = 'block';
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

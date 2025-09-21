<?php include 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Khane Pani Office</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'components/header.php'; ?>

<!-- Hero Carousel -->
<section class="hero">
    <div class="carousel">
        <div class="slide active">
            <img src="assets/images/hero2.jpg" alt="Kanepani building" class="clickable">
            <div class="caption">Our Water Supply Container</div>
        </div>
        <div class="slide">
            <img src="assets/images/hero.jpg" alt="Serving the Community" class="clickable">
            <div class="caption">Serving the Community</div>
        </div>
        <div class="slide">
            <img src="assets/images/hero1.jpg" alt="Clean & Safe Drinking Water" class="clickable">
            <div class="caption">Clean & Safe Drinking Water</div>
        </div>
        <button class="carousel-btn prev">&#10094;</button>
        <button class="carousel-btn next">&#10095;</button>
    </div>
</section>

<!-- Lightbox Container -->
<div id="lightbox" class="lightbox">
    <span class="close">&times;</span>
    <img class="lightbox-content" id="lightbox-img">
    <div class="lightbox-caption" id="lightbox-caption"></div>
</div>

<!-- Latest Notices -->
<section class="latest-notices container">
    <h2>Latest Notices</h2>
    <div class="notice-wrapper">
        <?php
        $sql = "SELECT * FROM notices ORDER BY created_at DESC LIMIT 5";
        $result = mysqli_query($conn, $sql);

        if(mysqli_num_rows($result) > 0){
            while ($row = mysqli_fetch_assoc($result)) {
                ?>
                <a href="notice.php?id=<?= $row['id'] ?>" class="notice-card">
                    <?php if(!empty($row['image'])): ?>
                        <img src="uploads/<?= $row['image'] ?>" alt="<?= $row['title'] ?>" class="clickable">
                    <?php endif; ?>
                    <div class="notice-content">
                        <h3><?= $row['title'] ?></h3>
                        <p><?= substr($row['content'],0,150) ?>...</p>
                    </div>
                </a>
                <?php
            }
        } else {
            echo "<p class='no-notices'>No notices found.</p>";
        }
        ?>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<script>
    // Hero Carousel
    const slides = document.querySelectorAll('.slide');
    let currentSlide = 0;

    const showSlide = index => {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
    };

    document.querySelector('.next').addEventListener('click', () => {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    });

    document.querySelector('.prev').addEventListener('click', () => {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
    });

    // Auto slide every 10 seconds
    setInterval(() => {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }, 10000);

    // Swipe support for mobile
    let startX = 0;
    const carousel = document.querySelector('.carousel');
    carousel.addEventListener('touchstart', e => startX = e.touches[0].clientX);
    carousel.addEventListener('touchend', e => {
        let diffX = e.changedTouches[0].clientX - startX;
        if(diffX > 50) currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        if(diffX < -50) currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    });

    // Lightbox for clickable images
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const closeBtn = document.querySelector('.lightbox .close');

    document.querySelectorAll('.clickable').forEach(img => {
        img.addEventListener('click', (e) => {
            e.preventDefault(); // prevent following card link
            lightbox.style.display = 'flex';
            lightboxImg.src = img.src;
            lightboxCaption.innerText = img.alt;
        });
    });

    closeBtn.addEventListener('click', () => lightbox.style.display = 'none');
    lightbox.addEventListener('click', e => {
        if(e.target === lightbox) lightbox.style.display = 'none';
    });

    // Hamburger menu toggle
    const hamburger = document.getElementById('hamburger');
    const mainNav = document.getElementById('main-nav');

    hamburger.addEventListener('click', () => {
        mainNav.classList.toggle('nav-active');
    });

</script>
</body>
</html>

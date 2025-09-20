<?php include 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Khane Pani Office</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- Top Info Bar -->
<div class="top-bar">
    <div class="container">
        <div class="contact-info">
            <span>📞 +977 1 4117356, 4117358</span>
            <span>✉ info@salakpurkhanepani.com</span>
        </div>
    </div>
</div>

<!-- Header -->
<header>
    <div class="container header-container">
        <div class="logo">
            <img src="assets/images/logo.jpg" alt="Khane Pani Logo">
            <span>सलकपुर खानेपानी / Salakpur KhanePani</span>
        </div>
        <nav class="main-nav">
            <a href="index.php">Home</a>
            <a href="notices.php">Notices</a>
            <a href="gallery.php">Gallery</a>
            <a href="contact.php">Contact</a>
        </nav>
    </div>
</header>

<!-- Hero Carousel -->
<section class="hero">
    <div class="carousel">
        <div class="slide active">
            <img src="assets/images/hero2.jpg" alt="Kanepani building">
            <div class="caption">Our Water Supply Office</div>
        </div>
        <div class="slide">
            <img src="assets/images/hero.jpg" alt="Group image">
            <div class="caption">Serving the Community</div>
        </div>
        <div class="slide">
            <img src="assets/images/hero1.jpg" alt="Group image2">
            <div class="caption">Clean & Safe Drinking Water</div>
        </div>
        <button class="carousel-btn prev">&#10094;</button>
        <button class="carousel-btn next">&#10095;</button>
    </div>
</section>

<!-- Latest Notices -->
<section class="latest-notices container">
    <h2>Latest Notices</h2>
    <div class="notice-wrapper">
        <?php
        $sql = "SELECT * FROM notices ORDER BY created_at DESC LIMIT 5";
        $result = mysqli_query($conn, $sql);

        if(mysqli_num_rows($result) > 0){
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<div class='notice'>";
                echo "<h3>".$row['title']."</h3>";
                echo "<p>".substr($row['content'],0,150)."...</p>";
                if (!empty($row['file'])) {
                    echo "<a class='download-btn' href='uploads/".$row['file']."' download>Download File</a>";
                }
                echo "<a class='read-more' href='notice.php?id=".$row['id']."'>Read More</a>";
                echo "</div>";
            }
        } else {
            echo "<p>No notices found.</p>";
        }
        ?>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> सलकपुर खानेपानी / Salakpur KhanePani. All rights reserved.</p>
    </div>
</footer>

<script>
    // Hero Carousel JS
    const slides = document.querySelectorAll('.slide');
    let currentSlide = 0;

    const showSlide = (index) => {
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

    // Auto slide every 8 seconds
    setInterval(() => {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }, 8000);

    // Optional: Swipe for mobile
    let startX = 0;
    document.querySelector('.carousel').addEventListener('touchstart', e => startX = e.touches[0].clientX);
    document.querySelector('.carousel').addEventListener('touchend', e => {
        let diffX = e.changedTouches[0].clientX - startX;
        if(diffX > 50) currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        if(diffX < -50) currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    });
</script>

</body>
</html>

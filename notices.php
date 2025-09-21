<?php include 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notices - Khane Pani Office</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'components/header.php'; ?>

<!-- Notices Section -->
<section class="latest-notices container">
    <h2>All Notices</h2>

    <!-- Search Bar -->
    <div class="notice-search">
        <input type="text" id="noticeSearch" placeholder="Search notices...">
    </div>

    <div class="notice-wrapper" id="noticeWrapper">
        <?php
        $sql = "SELECT * FROM notices ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);

        if(mysqli_num_rows($result) > 0){
            while ($row = mysqli_fetch_assoc($result)) {
                ?>
                <a href="notice.php?id=<?= $row['id'] ?>" class="notice-card">
                    <?php if(!empty($row['image'])): ?>
                        <img src="../assets/uploads/<?= $row['image'] ?>" alt="<?= $row['title'] ?>" class="clickable">
                    <?php endif; ?>
                    <div class="notice-content">
                        <h3><?= $row['title'] ?></h3>
                        <p><?= substr($row['content'], 0, 200) ?>...</p>
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

<!-- Lightbox for notice images -->
<div id="lightbox" class="lightbox">
    <span class="close">&times;</span>
    <img class="lightbox-content" id="lightbox-img">
    <div class="lightbox-caption" id="lightbox-caption"></div>
</div>

<!-- JS -->
<script>
    // Search Functionality
    const searchInput = document.getElementById('noticeSearch');
    const noticeWrapper = document.getElementById('noticeWrapper');
    searchInput.addEventListener('keyup', () => {
        const filter = searchInput.value.toLowerCase();
        const notices = noticeWrapper.getElementsByClassName('notice-card');
        Array.from(notices).forEach(notice => {
            let title = notice.querySelector('h3').innerText.toLowerCase();
            let content = notice.querySelector('p').innerText.toLowerCase();
            notice.style.display = (title.includes(filter) || content.includes(filter)) ? '' : 'none';
        });
    });

    // Lightbox
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
    lightbox.addEventListener('click', e => {
        if (e.target === lightbox) lightbox.style.display = 'none';
    });
</script>

</body>
</html>

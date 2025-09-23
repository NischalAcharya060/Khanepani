<?php
include 'config/db.php';

if(!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']); // sanitize input
$sql = "SELECT * FROM notices WHERE id = $id";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) === 0){
    echo "<p style='text-align:center; margin-top:50px; font-size:18px;'>Notice not found.</p>";
    exit();
}

$notice = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $notice['title'] ?> - Salkpur Khanepani</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }

        .notice-detail {
            max-width: 900px;
            margin: 50px auto;
            padding: 30px 25px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .notice-detail h2 {
            font-size: 30px;
            margin-bottom: 15px;
            color: #004080;
            font-weight: 700;
        }

        .notice-meta {
            font-size: 14px;
            color: #777;
            margin-bottom: 20px;
        }

        .notice-meta i {
            margin-right: 6px;
        }

        .notice-detail img.notice-image {
            max-width: 100%;
            border-radius: 12px;
            margin: 20px 0;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .notice-detail img.notice-image:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .notice-detail p {
            font-size: 16px;
            line-height: 1.7;
            color: #444;
            margin-top: 15px;
        }

        .action-btn {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .back-btn {
            background: #007bff;
            color: #fff;
            margin-right: 10px;
        }
        .back-btn:hover {
            background: #054d8f;
            color: #fff;
            font-weight: bold;
        }

        .download-btn {
            background: #28a745;
            color: #fff;
        }
        .download-btn:hover { background: #1e7e34; }

        .preview-btn {
            background: #ffc107;
            color: #222;
            margin-left: 10px;
        }
        .preview-btn:hover { background: #e0a800; }

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

        .lightbox .close {
            position: absolute;
            top: 20px;
            right: 35px;
            font-size: 40px;
            color: #fff;
            cursor: pointer;
            font-weight: bold;
            transition: color 0.3s;
        }
        .lightbox .close:hover { color: #ff6600; }

        .lightbox-caption {
            margin-top: 12px;
            color: #ddd;
            text-align: center;
            font-size: 14px;
        }

        #lightbox-img {
            border-radius: 12px;
            max-width: 90%;
            max-height: 90%;
            display: none;
        }

        #previewFrame {
            display: none;
            border: none;
            border-radius: 12px;
            width: 90%;
            height: 90%;
        }

        @media (max-width: 600px) {
            .notice-detail { padding: 20px 15px; }
            .notice-detail h2 { font-size: 24px; }
            .action-btn { font-size: 14px; padding: 10px 16px; }
        }
    </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<section class="notice-detail container">
    <a href="notices.php" class="action-btn back-btn">⬅ Back to Home</a>

    <h2><?= $notice['title'] ?></h2>
    <div class="notice-meta">
        <i class="fa-regular fa-calendar"></i> <?= date("F d, Y, h:i A", strtotime($notice['created_at'])) ?>
    </div>

    <?php if(!empty($notice['image'])): ?>
        <img src="../assets/uploads/<?= $notice['image'] ?>" alt="<?= $notice['title'] ?>" class="clickable notice-image">
    <?php endif; ?>

    <p><?= nl2br($notice['content']) ?></p>

    <?php if(!empty($notice['file'])):
        $filePath = "../assets/uploads/".$notice['file'];
        $fileExt = strtolower(pathinfo($notice['file'], PATHINFO_EXTENSION));
        ?>
        <div>
            <a href="<?= $filePath ?>" download class="action-btn download-btn">⬇ Download File</a>
            <button type="button"
                    class="action-btn preview-btn"
                    onclick="openPreview('<?= $filePath ?>', '<?= $fileExt ?>')">
                👁 View File
            </button>
        </div>
    <?php endif; ?>
</section>

<!-- Lightbox -->
<div id="lightbox" class="lightbox">
    <span class="close">&times;</span>
    <img id="lightbox-img">
    <iframe id="previewFrame"></iframe>
    <div class="lightbox-caption" id="lightbox-caption"></div>
</div>

<?php include 'components/footer.php'; ?>

<script>
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const previewFrame = document.getElementById('previewFrame');
    const closeBtn = document.querySelector('.lightbox .close');
    const caption = document.getElementById('lightbox-caption');

    function openPreview(filePath, fileExt) {
        lightbox.style.display = 'flex';
        caption.innerText = "";

        if (['jpg','jpeg','png','gif'].includes(fileExt)) {
            previewFrame.style.display = 'none';
            lightboxImg.style.display = 'block';
            lightboxImg.src = filePath;

            lightboxImg.onload = function() {
                const maxWidth = window.innerWidth * 0.9;
                const maxHeight = window.innerHeight * 0.9;
                let width = lightboxImg.naturalWidth;
                let height = lightboxImg.naturalHeight;
                const scale = Math.min(maxWidth/width, maxHeight/height, 1);
                lightboxImg.style.width = width * scale + "px";
                lightboxImg.style.height = height * scale + "px";
            };
            caption.innerText = "Image Preview";
        } else if(fileExt === 'pdf') {
            lightboxImg.style.display = 'none';
            previewFrame.style.display = 'block';
            previewFrame.src = filePath;
            caption.innerText = "PDF Preview";
        } else if (['doc','docx','xls','xlsx','ppt','pptx'].includes(fileExt)) {
            lightboxImg.style.display = 'none';
            previewFrame.style.display = 'block';
            previewFrame.src = "https://docs.google.com/gview?url=" + encodeURIComponent(window.location.origin + "/" + filePath) + "&embedded=true";
            caption.innerText = "Document Preview";
        } else {
            alert("Preview not supported for this file type. Please download.");
            lightbox.style.display = 'none';
        }
    }

    closeBtn.addEventListener('click', () => {
        lightbox.style.display = 'none';
        lightboxImg.src = "";
        previewFrame.src = "";
    });

    lightbox.addEventListener('click', e => {
        if(e.target === lightbox) {
            lightbox.style.display = 'none';
            lightboxImg.src = "";
            previewFrame.src = "";
        }
    });
</script>

</body>
</html>

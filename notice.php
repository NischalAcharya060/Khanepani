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
    echo "<p style='text-align:center; margin-top:50px;'>Notice not found.</p>";
    exit();
}

$notice = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $notice['title'] ?> - Khane Pani Office</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .notice-detail {
            max-width: 900px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .notice-detail h2 {
            font-size: 28px;
            margin-bottom: 15px;
            color: #222;
        }

        .notice-meta {
            font-size: 14px;
            color: #777;
            margin-bottom: 20px;
        }

        .notice-detail img.notice-image {
            max-width: 100%;
            border-radius: 10px;
            margin: 20px 0;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .notice-detail img.notice-image:hover {
            transform: scale(1.02);
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
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            transition: background 0.3s;
        }

        .back-btn {
            background: #007bff;
            color: #fff;
        }
        .back-btn:hover { background: #0056b3; }

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

        .lightbox-content {
            width: 90%;
            height: 80%;
            border-radius: 10px;
            background: #fff;
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

        .lightbox-caption {
            margin-top: 12px;
            color: #ddd;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<section class="notice-detail container">
    <a href="index.php" class="action-btn back-btn">⬅ Back to Home</a>

    <h2><?= $notice['title'] ?></h2>
    <div class="notice-meta">📅 Posted on: <?= date("F d, Y", strtotime($notice['created_at'])) ?></div>

    <?php if(!empty($notice['image'])): ?>
        <img src="../assets/uploads/<?= $notice['image'] ?>"
             alt="<?= $notice['title'] ?>"
             class="clickable notice-image">
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
    <iframe class="lightbox-content" id="previewFrame"></iframe>
    <div class="lightbox-caption" id="lightbox-caption"></div>
</div>

<?php include 'components/footer.php'; ?>

<script>
    const lightbox = document.getElementById('lightbox');
    const previewFrame = document.getElementById('previewFrame');
    const closeBtn = document.querySelector('.lightbox .close');

    function openPreview(filePath, fileExt) {
        let src = "";

        if (fileExt === 'pdf') {
            src = filePath;
        } else if (['jpg','jpeg','png','gif'].includes(fileExt)) {
            src = filePath;
        } else if (['doc','docx','xls','xlsx','ppt','pptx'].includes(fileExt)) {
            src = "https://docs.google.com/gview?url=" + encodeURIComponent(window.location.origin + "/" + filePath) + "&embedded=true";
        } else {
            alert("Preview not supported for this file type. Please download.");
            return;
        }

        previewFrame.src = src;
        lightbox.style.display = 'flex';
    }

    closeBtn.addEventListener('click', () => {
        lightbox.style.display = 'none';
        previewFrame.src = ""; // clear iframe
    });

    lightbox.addEventListener('click', e => {
        if(e.target === lightbox) {
            lightbox.style.display = 'none';
            previewFrame.src = "";
        }
    });
</script>

</body>
</html>

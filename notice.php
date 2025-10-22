<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config/database/db.php';
include 'config/lang.php';
include 'config/Nepali_calendar.php';
$cal = new Nepali_Calendar();

function format_date($date_str, $cal) {
    $timestamp = strtotime($date_str);
    $year  = (int)date('Y', $timestamp);
    $month = (int)date('m', $timestamp);
    $day   = (int)date('d', $timestamp);
    $hour  = (int)date('h', $timestamp);
    $minute = (int)date('i', $timestamp);
    $ampm  = date('A', $timestamp);

    if ( ($_SESSION['lang'] ?? 'en') === 'np' ) {
        $nepDate = $cal->eng_to_nep($year, $month, $day);
        $np_numbers = ['0'=>'०','1'=>'१','2'=>'२','3'=>'३','4'=>'४','5'=>'५','6'=>'६','7'=>'७','8'=>'८','9'=>'९'];

        $dateNep = strtr($nepDate['year'].'-'.$nepDate['month'].'-'.$nepDate['date'], $np_numbers);
        $timeNep = strtr(sprintf("%02d:%02d", $hour, $minute), $np_numbers) . " " . $ampm;

        return 'मिति: ' . $dateNep . ', ' . 'समय: ' . $timeNep;
    } else {
        return date("F d, Y, h:i A", $timestamp);
    }
}

if(!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();


if(mysqli_num_rows($result) === 0){
    echo "<p style='text-align:center; margin-top:50px; font-size:18px;'>Notice not found.</p>";
    exit();
}

$notice = mysqli_fetch_assoc($result);
$displayDate = format_date($notice['created_at'], $cal);

$attached_files = $notice['file'] ? json_decode($notice['file'], true) : [];
if (!is_array($attached_files)) {
    $attached_files = [];
}

$image_files = [];
$other_attachments = [];
$image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

foreach ($attached_files as $file_name) {
    $fileExt = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $filePath = "assets/uploads/" . $file_name;

    if (file_exists($filePath)) {
        if (in_array($fileExt, $image_extensions)) {
            $image_files[] = $file_name;
        } else {
            $other_attachments[] = $file_name;
        }
    } else {
    }
}

$has_images = !empty($image_files);
$has_multiple_images = count($image_files) > 1;
$has_attachments_to_list = !empty($other_attachments);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($notice['title']) ?> - <?= $lang['logo'] ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary: #007bff;
            --primary-light: #e6f2ff;
            --primary-dark: #004d99;
            --secondary: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --text-dark: #212529;
            --text-light: #606b74;
            --bg-light: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #dee2e6;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: var(--bg-light);
            margin: 0;
            padding: 0;
        }

        .notice-detail {
            max-width: 900px;
            margin: 50px auto;
            padding: 45px 40px;
            background: var(--card-bg);
            border-radius: 18px;
            box-shadow: var(--shadow);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 6px 15px;
            margin-bottom: 25px;
            background: var(--primary-light);
            color: var(--primary-dark);
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease-in-out;
            border: 1px solid var(--primary);
        }
        .back-btn:hover {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
            border-color: var(--primary-dark);
        }
        .back-btn i { width: 14px; height: 14px; margin-right: 6px; }


        .notice-detail h2 {
            font-size: 38px;
            margin-bottom: 15px;
            color: var(--text-dark);
            font-weight: 900;
            line-height: 1.2;
        }

        .notice-meta {
            font-size: 16px;
            color: var(--text-light);
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px dashed var(--border-color);
            display: flex;
            align-items: center;
        }

        .notice-meta i {
            margin-right: 8px;
            color: var(--primary);
        }

        .notice-content {
            font-size: 18px;
            line-height: 1.8;
            color: var(--text-dark);
            padding-top: 0;
            margin-top: 30px;
        }
        .notice-content p {
            margin-top: 0;
        }

        .image-display-wrapper {
            margin: 0 auto 30px auto;
            max-width: 100%;
        }

        .single-image-display {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            cursor: zoom-in;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .single-image-display:hover {
            transform: scale(1.005);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .single-image-display img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .image-slider-container {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background: #000;
        }
        .image-slider {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }
        .slide {
            min-width: 100%;
            height: auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .slide img {
            max-width: 100%;
            height: auto;
            display: block;
            cursor: zoom-in;
        }
        .slider-nav button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.6);
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            z-index: 10;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.8;
            transition: opacity 0.2s, background 0.2s;
        }
        .slider-nav button:hover {
            opacity: 1;
            background: rgba(0, 0, 0, 0.8);
        }
        .slider-nav .prev { left: 15px; }
        .slider-nav .next { right: 15px; }

        .attachments-section {
            margin-top: 50px;
            padding: 25px 30px;
            border-radius: 12px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .attachments-section h3 {
            font-size: 22px;
            color: var(--primary-dark);
            margin: 0 0 20px 0;
            font-weight: 700;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .attachment-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            text-decoration: none;
            color: inherit;
        }
        .attachment-item:last-child {
            border-bottom: none;
        }
        .attachment-item:hover {
            background-color: var(--primary-light);
            border-radius: 4px;
            margin-left: -5px;
            margin-right: -5px;
            padding-left: 5px;
            padding-right: 5px;
        }

        .file-info-group {
            display: flex;
            align-items: center;
            width: 100%;
        }

        .file-icon-wrapper {
            background: var(--primary);
            border-radius: 6px;
            padding: 8px;
            margin-right: 15px;
            color: white;
            flex-shrink: 0;
        }
        .file-icon-wrapper i {
            width: 18px;
            height: 18px;
        }

        .file-name {
            font-size: 16px;
            color: var(--text-dark);
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lightbox {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(33, 37, 41, 0.98);
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 20px;
        }

        .lightbox .close {
            position: fixed;
            top: 20px;
            right: 35px;
            font-size: 40px;
            color: #fff;
            cursor: pointer;
            font-weight: 300;
            transition: color 0.3s;
        }
        .lightbox .close:hover { color: var(--primary); }

        .lightbox-caption {
            color: #fff;
            margin-top: 15px;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
        }

        #previewFrame {
            display: none;
            width: 90%;
            height: 80vh;
            border: none;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        #lightbox-img {
            display: none;
            max-width: 90%;
            max-height: 80vh;
            border-radius: 10px;
        }

        @media (max-width: 600px) {
            .notice-detail { margin: 20px auto; padding: 20px 15px; border-radius: 12px; }
            .notice-detail h2 { font-size: 28px; }
            .back-btn { margin-bottom: 15px; padding: 8px 15px; }
            .notice-meta { font-size: 14px; margin-bottom: 25px; padding-bottom: 15px; }
            .notice-content { font-size: 16px; line-height: 1.6; }
            .attachments-section { margin-top: 30px; padding: 20px 15px; }
        }
    </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<section class="notice-detail container">
    <a href="notices.php" class="back-btn">
        <i data-feather="arrow-left"></i>
        <?= $lang['back'] ?? 'Back to Notices' ?>
    </a>

    <h2><?= htmlspecialchars($notice['title']) ?></h2>
    <div class="notice-meta">
        <i data-feather="calendar"></i> <?= $displayDate ?>
    </div>

    <?php if($has_images): ?>
        <div class="image-display-wrapper">
            <?php if ($has_multiple_images): ?>
                <div class="image-slider-container">
                    <div class="image-slider" id="image-slider">
                        <?php foreach ($image_files as $f):
                            $filePath = "assets/uploads/" . $f;
                            ?>
                            <div class="slide">
                                <img src="<?= $filePath ?>"
                                     alt="<?= htmlspecialchars($notice['title']) ?>"
                                     onclick="openPreview('<?= htmlspecialchars($filePath) ?>', 'jpg', '<?= htmlspecialchars($filePath) ?>')"
                                     title="<?= $lang['click_to_zoom'] ?? 'Click to zoom/preview' ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="slider-nav">
                        <button class="prev" onclick="moveSlider(-1)">
                            <i data-feather="chevron-left" style="width:25px; height:25px; stroke-width:3;"></i>
                        </button>
                        <button class="next" onclick="moveSlider(1)">
                            <i data-feather="chevron-right" style="width:25px; height:25px; stroke-width:3;"></i>
                        </button>
                    </div>
                </div>
            <?php else:
                $f = $image_files[0];
                $filePath = "assets/uploads/" . $f;
                $fileExt = strtolower(pathinfo($f, PATHINFO_EXTENSION));

                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
                $base_uri = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']);

                $cleaned_base_uri = preg_replace('#/+#', '/', $base_uri);

                $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $cleaned_base_uri . $filePath;
                ?>
                <div class="single-image-display">
                    <img
                            src="<?= $filePath ?>"
                            alt="<?= htmlspecialchars($notice['title']) ?>"
                            onclick="openPreview('<?= htmlspecialchars($filePath) ?>', '<?= htmlspecialchars($fileExt) ?>', '<?= htmlspecialchars($url) ?>')"
                            title="<?= $lang['click_to_zoom'] ?? 'Click to zoom/preview' ?>"
                    >
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="notice-content">
        <p><?= nl2br(htmlspecialchars($notice['content'])) ?></p>
    </div>

    <?php if($has_attachments_to_list): ?>
        <div class="attachments-section">
            <h3><i data-feather="paperclip" style="width:20px; height:20px; margin-right: 8px;"></i><?= $lang['attachments'] ?? 'Attachments' ?></h3>
            <ul class="attachments-list">
                <?php foreach($other_attachments as $file_name):
                    $filePath = "assets/uploads/".$file_name;
                    $fileExt = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
                    $base_uri = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']);

                    $cleaned_base_uri = preg_replace('#/+#', '/', $base_uri);

                    $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $cleaned_base_uri . $filePath;

                    $icon = 'file';
                    $icon_color = 'var(--secondary)';
                    $display_name = htmlspecialchars(substr($file_name, strpos($file_name, '_', strpos($file_name, '_') + 1) + 1));

                    if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) { $icon = 'image'; $icon_color = 'var(--success)'; }
                    else if ($fileExt === 'pdf') { $icon = 'file-text'; $icon_color = '#dc3545'; }
                    else if (in_array($fileExt, ['doc', 'docx'])) { $icon = 'file-text'; $icon_color = 'var(--primary-dark)'; }
                    else if (in_array($fileExt, ['xls', 'xlsx', 'csv'])) { $icon = 'table'; $icon_color = '#1e7e34'; }
                    else if (in_array($fileExt, ['ppt', 'pptx'])) { $icon = 'monitor'; $icon_color = '#ff6a00'; }
                    ?>
                    <li class="attachment-item"
                        onclick="openPreview('<?= htmlspecialchars($filePath) ?>', '<?= htmlspecialchars($fileExt) ?>', '<?= htmlspecialchars($url) ?>')"
                        title="<?= $lang['click_to_view'] ?? 'Click to view or download' ?>"
                    >
                        <div class="file-info-group">
                            <div class="file-icon-wrapper" style="background-color: <?= $icon_color ?>;">
                                <i data-feather="<?= $icon ?>"></i>
                            </div>
                            <span class="file-name"><?= $display_name ?></span>
                        </div>
                        <i data-feather="chevron-right" style="width:18px; height:18px; color:var(--text-light)"></i>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</section>

<div id="lightbox" class="lightbox">
    <span class="close">&times;</span>
    <img id="lightbox-img">
    <iframe id="previewFrame"></iframe>
    <div class="lightbox-caption" id="lightbox-caption"></div>
</div>

<?php include 'components/footer.php'; ?>

<script>
    feather.replace();

    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const previewFrame = document.getElementById('previewFrame');
    const closeBtn = document.querySelector('.lightbox .close');
    const caption = document.getElementById('lightbox-caption');

    function openPreview(filePath, fileExt, fullUrl) {
        lightbox.style.display = 'flex';
        caption.innerText = "";

        const imageExtensions = ['jpg','jpeg','png','gif','webp'];
        const docExtensions = ['pdf','doc','docx','xls','xlsx','ppt','pptx','csv'];

        lightboxImg.style.display = 'none';
        previewFrame.style.display = 'none';
        previewFrame.src = "";
        lightboxImg.src = "";

        if (imageExtensions.includes(fileExt)) {
            lightboxImg.style.display = 'block';
            lightboxImg.src = filePath;
            caption.innerText = "<?= $lang['image_preview'] ?? 'Image Preview' ?>";
        }
        else if (docExtensions.includes(fileExt)) {
            previewFrame.style.display = 'block';

            if (fileExt === 'pdf') {
                previewFrame.src = filePath;
                caption.innerText = "<?= $lang['pdf_preview'] ?? 'PDF Document Preview' ?>";
            } else {
                previewFrame.src = "https://docs.google.com/gview?url=" + encodeURIComponent(fullUrl) + "&embedded=true";
                caption.innerText = "<?= $lang['file_preview'] ?? 'File Preview (Google Viewer)' ?>";
            }
        }
        else {
            const downloadConfirm = confirm("<?= $lang['preview_not_supported_download'] ?? 'Preview not available for this file type. Would you like to download it?' ?>");
            if (downloadConfirm) {
                window.location.href = filePath;
            }
            lightbox.style.display = 'none';
        }
    }

    function closeLightbox() {
        lightbox.style.display = 'none';
        lightboxImg.src = "";
        previewFrame.src = "";
    }

    closeBtn.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', e => {
        if(e.target === lightbox) {
            closeLightbox();
        }
    });

    <?php if ($has_multiple_images): ?>
    let currentSlide = 0;
    const slider = document.getElementById('image-slider');
    const slides = document.querySelectorAll('.slide');
    const totalSlides = slides.length;

    function updateSlider() {
        if (slider) {
            const offset = -currentSlide * 100;
            slider.style.transform = `translateX(${offset}%)`;
        }
    }

    function moveSlider(direction) {
        currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
        updateSlider();
    }
    <?php endif; ?>
</script>

</body>
</html>
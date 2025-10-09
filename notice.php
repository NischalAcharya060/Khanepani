<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database, language, Nepali calendar
include 'config/db.php';
include 'config/lang.php';
include 'config/nepali_calendar.php';
$cal = new Nepali_Calendar();

// Function to format date with time (kept unchanged)
function format_date($date_str, $cal) {
    $timestamp = strtotime($date_str);
    $year  = (int)date('Y', $timestamp);
    $month = (int)date('m', $timestamp);
    $day   = (int)date('d', $timestamp);
    $hour  = (int)date('h', $timestamp); // 12-hour format
    $minute = (int)date('i', $timestamp);
    $ampm  = date('A', $timestamp);

    if ( ($_SESSION['lang'] ?? 'en') === 'np' ) {
        $nepDate = $cal->eng_to_nep($year, $month, $day);
        $np_numbers = ['0'=>'०','1'=>'१','2'=>'२','3'=>'३','4'=>'४','5'=>'५','6'=>'६','7'=>'७','८','9'=>'९'];

        $dateNep = strtr($nepDate['year'].'-'.$nepDate['month'].'-'.$nepDate['date'], $np_numbers);
        $timeNep = strtr(sprintf("%02d:%02d", $hour, $minute), $np_numbers) . " " . $ampm;

        return 'मिति: ' . $dateNep . ', ' . 'समय: ' . $timeNep;
    } else {
        return date("F d, Y, h:i A", $timestamp);
    }
}
?>

<?php
if(!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

// Use prepared statement for security
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

// DECODE: Decode the JSON array of files from the 'file' column
$attached_files = $notice['file'] ? json_decode($notice['file'], true) : [];
if (!is_array($attached_files)) {
    $attached_files = [];
}

$has_files = !empty($attached_files);

// Logic to check and extract the FIRST image for INLINE display
$inline_image = null;
$image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
$other_attachments = [];

if ($has_files) {
    foreach ($attached_files as $file_name) {
        $fileExt = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($fileExt, $image_extensions) && $inline_image === null) {
            $inline_image = $file_name;
        } else {
            $other_attachments[] = $file_name;
        }
    }
}

// If there was no image, but there are other files, we list all files under attachments.
if ($inline_image === null) {
    $other_attachments = $attached_files;
} else {
    // If there is an inline image, we ensure it's still clickable in the attachments list
    // OR we can decide to omit it from the list if it's displayed prominently.
    // For simplicity and clarity, we'll keep it in the attachment list too.
    // The previous loop already separated the *first* image into $inline_image
    // and put all *remaining* files into $other_attachments. We'll stick with that.
}

$has_other_attachments = !empty($other_attachments);
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
        /* --- CSS Variables & Reset --- */
        :root {
            --primary: #007bff;
            --primary-dark: #0056b3;
            --secondary: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --text-dark: #343a40;
            --text-light: #6c757d;
            --bg-light: #f5f7fa;
            --card-bg: #ffffff;
            --border-color: #e9ecef;
            --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: var(--bg-light);
            margin: 0;
            padding: 0;
        }

        /* --- Notice Container --- */
        .notice-detail {
            max-width: 900px;
            margin: 50px auto;
            padding: 40px 30px;
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: var(--shadow);
            position: relative;
        }

        /* --- Header & Meta --- */
        .notice-detail h2 {
            font-size: 34px;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 900;
        }

        .notice-meta {
            font-size: 15px;
            color: var(--text-light);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
        }

        .notice-meta i {
            margin-right: 8px;
            color: var(--primary);
        }

        /* --- Back Button (Cleaned up, now top-right floating) --- */
        .back-btn {
            position: absolute;
            top: 30px;
            right: 30px;
            display: inline-flex;
            align-items: center;
            padding: 10px 18px;
            background: var(--secondary);
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .back-btn:hover { background: var(--secondary); opacity: 0.9; }
        .back-btn i { width: 16px; height: 16px; margin-right: 5px; }


        /* --- Content & Inline Image --- */
        .notice-content {
            font-size: 17px;
            line-height: 1.75;
            color: var(--text-dark);
            border-top: 1px solid var(--border-color);
            padding-top: 30px;
        }
        .notice-content p {
            margin-top: 0;
        }

        /* Inline Image Styling */
        .notice-inline-image {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            margin: 30px 0;
            cursor: zoom-in;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .notice-inline-image:hover {
            transform: scale(1.005);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        /* --- Attachments Section (Aesthetic List) --- */
        .attachments-section {
            margin-top: 40px;
            padding: 20px 25px;
            border-radius: 10px;
            background: #eef2f8; /* Very light background for distinction */
            border: 1px solid #dcdfe4;
        }
        .attachments-section h3 {
            font-size: 20px;
            color: var(--primary-dark);
            margin: 0 0 15px 0;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .attachment-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .attachment-item:last-child {
            border-bottom: none;
        }
        .attachment-item:hover {
            background-color: rgba(0, 123, 255, 0.05); /* Light hover effect */
        }

        .file-info-group {
            display: flex;
            align-items: center;
            width: 100%;
        }

        /* New File Icon Styling */
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

        /* --- Lightbox (Unchanged) --- */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.95);
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
            font-weight: bold;
            transition: color 0.3s;
        }
        .lightbox .close:hover { color: var(--warning); }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .notice-detail { margin: 20px auto; padding: 20px 15px; }
            .notice-detail h2 { font-size: 28px; }
            .back-btn { position: static; margin-bottom: 20px; display: block; width: 100%; text-align: center; }
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

    <div class="notice-content">
        <?php if($inline_image):
            $inline_filePath = "../assets/uploads/".$inline_image;
            $inline_fileExt = strtolower(pathinfo($inline_image, PATHINFO_EXTENSION));

            // Generate full URL for lightbox to open it immediately on click
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
            $base_uri = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']);
            $inline_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $base_uri . $inline_filePath;
            $inline_url = str_replace('//', '/', $inline_url);
            ?>
            <img
                    src="<?= $inline_filePath ?>"
                    alt="<?= htmlspecialchars($notice['title']) ?>"
                    class="notice-inline-image"
                    onclick="openPreview('<?= htmlspecialchars($inline_filePath) ?>', '<?= htmlspecialchars($inline_fileExt) ?>', '<?= htmlspecialchars($inline_url) ?>')"
                    title="<?= $lang['click_to_zoom'] ?? 'Click to zoom/preview' ?>"
            >
        <?php endif; ?>

        <p><?= nl2br(htmlspecialchars($notice['content'])) ?></p>
    </div>

    <?php if($has_files || $has_other_attachments): ?>
        <div class="attachments-section">
            <h3><i data-feather="paperclip" style="width:20px; height:20px; margin-right: 8px;"></i><?= $lang['attachments'] ?? 'Attachments' ?></h3>
            <ul class="attachments-list">
                <?php foreach($attached_files as $file_name):
                    $filePath = "../assets/uploads/".$file_name;
                    $fileExt = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                    // Safely determine the protocol and construct the full public URL
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
                    $base_uri = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']);
                    $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $base_uri . $filePath;
                    $url = str_replace('//', '/', $url);

                    // Determine the appropriate Feather Icon based on extension
                    $icon = 'file';
                    $icon_color = 'var(--primary)';
                    if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) { $icon = 'image'; $icon_color = 'var(--success)'; }
                    else if ($fileExt === 'pdf') { $icon = 'file-text'; $icon_color = '#dc3545'; } // Red for PDF
                    else if (in_array($fileExt, ['doc', 'docx'])) { $icon = 'file-text'; $icon_color = 'var(--primary-dark)'; }
                    else if (in_array($fileExt, ['xls', 'xlsx'])) { $icon = 'file-text'; $icon_color = '#1e7e34'; }

                    ?>
                    <li class="attachment-item"
                        onclick="openPreview('<?= htmlspecialchars($filePath) ?>', '<?= htmlspecialchars($fileExt) ?>', '<?= htmlspecialchars($url) ?>')"
                        title="<?= $lang['click_to_view'] ?? 'Click to view or download' ?>"
                    >
                        <div class="file-info-group">
                            <div class="file-icon-wrapper" style="background-color: <?= $icon_color ?>;">
                                <i data-feather="<?= $icon ?>"></i>
                            </div>
                            <span class="file-name"><?= htmlspecialchars(basename($file_name)) ?></span>
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
    // Initialize Feather Icons
    feather.replace();

    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const previewFrame = document.getElementById('previewFrame');
    const closeBtn = document.querySelector('.lightbox .close');
    const caption = document.getElementById('lightbox-caption');

    /**
     * Opens the lightbox to preview images or files.
     * @param {string} filePath - Local path to the file.
     * @param {string} fileExt - File extension.
     * @param {string} fullUrl - Full public URL of the file for Google Viewer.
     */
    function openPreview(filePath, fileExt, fullUrl) {
        lightbox.style.display = 'flex';
        caption.innerText = "";

        const imageExtensions = ['jpg','jpeg','png','gif'];
        const docExtensions = ['pdf','doc','docx','xls','xlsx','ppt','pptx'];

        if (imageExtensions.includes(fileExt)) {
            previewFrame.style.display = 'none';
            lightboxImg.style.display = 'block';
            lightboxImg.src = filePath;
            caption.innerText = "<?= $lang['image_preview'] ?? 'Image Preview' ?>";
        } else if(docExtensions.includes(fileExt)) {
            lightboxImg.style.display = 'none';
            previewFrame.style.display = 'block';

            let src = filePath;
            if (fileExt !== 'pdf') {
                // Use Google Docs Viewer for non-PDF documents
                src = "https://docs.google.com/gview?url=" + encodeURIComponent(fullUrl) + "&embedded=true";
            }
            previewFrame.src = src;

            caption.innerText = "<?= $lang['file_preview'] ?? 'File Preview (may require Google Viewer)' ?>";
        } else {
            // If preview isn't supported, prompt the user to download instead
            const downloadConfirm = confirm("<?= $lang['preview_not_supported_download'] ?? 'Preview not available for this file type. Would you like to download it?' ?>");
            if (downloadConfirm) {
                window.location.href = filePath; // Triggers download
            }
            lightbox.style.display = 'none';
        }
    }

    // Close handlers (optimized to clear resources)
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
</script>

</body>
</html>
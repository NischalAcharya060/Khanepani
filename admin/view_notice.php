<?php
session_start();
include '../config/database/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
include "../lang/" . $_SESSION['lang'] . ".php";

$username = $_SESSION['username'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_notices.php");
    exit();
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $stmt->close();
    header("Location: manage_notices.php");
    exit();
}

$notice = $result->fetch_assoc();
$stmt->close();

// Define the notice types using language strings for display
$notice_type_labels = [
        'General'       => $lang['type_general'] ?? 'General Notice',
        'Operational'   => $lang['type_operational'] ?? 'Operational Update',
        'Maintenance'   => $lang['type_maintenance'] ?? 'Maintenance Schedule',
        'Financial'     => $lang['type_financial'] ?? 'Financial Report',
];

// Determine the display label for the notice type
$notice_type_key = $notice['type'] ?? 'General';
$display_type = $notice_type_labels[$notice_type_key] ?? $notice_type_key;

// --- PRE-PROCESS FILES FOR SLIDER ---
$files = $notice['file'] ? json_decode($notice['file'], true) : [];
$image_files = [];
$other_files = [];

if (is_array($files)) {
    foreach ($files as $f) {
        $ext = pathinfo($f, PATHINFO_EXTENSION);
        $filePath = "../assets/uploads/" . $f;

        if (file_exists($filePath)) {
            $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp']);

            if ($isImage) {
                $image_files[] = $f;
            } else {
                $other_files[] = $f;
            }
        }
    }
}
$has_multiple_images = count($image_files) > 1;
// ------------------------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lang['view_notice'] ?? 'View Notice') ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        <?php include '../css/dark-mode.css'; ?>

        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .title-group h2 { font-size: 28px; color: var(--text-color-dark); margin: 0; font-weight: 700; }

        /* Card style similar to .notice-form */
        .notice-view-card {
            background: var(--card-bg);
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: var(--shadow-subtle);
            border: 1px solid var(--border-color);
        }

        /* Notice content styles */
        .notice-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-color-dark);
            margin-bottom: 10px;
        }
        .notice-meta {
            font-size: 14px;
            color: var(--text-color-light);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .notice-meta-date {
            color: var(--text-color-light);
            font-weight: 500;
        }
        /* Style for the Type tag */
        .notice-type-tag {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 13px;
            background-color: var(--type-color);
            color: #fff;
        }
        .notice-content {
            font-size: 16px;
            line-height: 1.7;
            color: var(--text-color-dark);
            white-space: pre-line;
            margin-bottom: 30px;
        }

        /* --- Slider/Image Styles (NEW) --- */
        .image-slider-container {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
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
            border-radius: 0;
        }
        .slider-nav button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            z-index: 10;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.8;
            transition: opacity 0.2s, background 0.2s;
        }
        .slider-nav button:hover {
            opacity: 1;
            background: rgba(0, 0, 0, 0.7);
        }
        .slider-nav .prev { left: 10px; }
        .slider-nav .next { right: 10px; }
        .single-image-display img {
            max-width: 100%;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
        }

        /* --- Other File Section Styles --- */
        .notice-file {
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }
        .notice-file-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-color-dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .file-download {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            background: var(--bg-light);
            color: var(--primary-color);
            border: 1px solid var(--border-color);
            transition: background 0.2s;
            font-weight: 500;
        }
        .file-download:hover {
            background: #e0f2f1;
            border-color: var(--primary-color);
        }
        .file-download i {
            width: 18px;
            height: 18px;
            margin-right: 8px;
        }

        /* Back Button Style */
        .back-btn { display: inline-flex; align-items: center; padding: 8px 15px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color-light); font-weight: 500; transition: all 0.2s ease; }
        .back-btn:hover { border-color: var(--secondary-color); color: var(--secondary-color); box-shadow: var(--shadow-subtle); }
        .back-btn i { width: 20px; height: 20px; margin-right: 8px; }

        @media (max-width: 600px) {
            .notice-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div class="title-group">
            <h2>👀 <?= htmlspecialchars($lang['view_notice'] ?? 'View Notice') ?></h2>
        </div>

        <a href="manage_notices.php" class="back-btn" style="background:var(--card-bg); color:var(--text-color-light); border: 1px solid var(--border-color);">
            <i data-feather="arrow-left"></i>
            <?= $lang['back'] ?? 'Back to Notices' ?>
        </a>
    </div>

    <div class="notice-view-card">
        <h3 class="notice-title"><?= htmlspecialchars($notice['title']) ?></h3>

        <div class="notice-meta">
            <span class="notice-meta-date">
                <?= $lang['posted_on'] ?? 'Posted on:' ?> <?= date('F j, Y, g:i a', strtotime($notice['created_at'])) ?>
            </span>
            <span class="notice-type-tag">
                <?= htmlspecialchars($display_type) ?>
            </span>
        </div>

        <div class="notice-content"><?= nl2br(htmlspecialchars($notice['content'])) ?></div>

        <?php if (!empty($image_files)): ?>
            <?php if ($has_multiple_images): ?>
                <div class="image-slider-container">
                    <div class="image-slider" id="image-slider">
                        <?php foreach ($image_files as $f):
                            $safeFile = htmlspecialchars($f);
                            $filePath = "../assets/uploads/" . $f;
                            ?>
                            <div class="slide">
                                <a href="<?= $filePath ?>" target="_blank" title="<?= $safeFile ?>">
                                    <img src="<?= $filePath ?>" alt="<?= $safeFile ?>">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="slider-nav">
                        <button class="prev" onclick="moveSlider(-1)">
                            <i data-feather="chevron-left" style="width:20px; height:20px; stroke-width:3;"></i>
                        </button>
                        <button class="next" onclick="moveSlider(1)">
                            <i data-feather="chevron-right" style="width:20px; height:20px; stroke-width:3;"></i>
                        </button>
                    </div>
                </div>
            <?php else:
                // Display single image normally
                $f = $image_files[0];
                $safeFile = htmlspecialchars($f);
                $filePath = "../assets/uploads/" . $f;
                ?>
                <div class="single-image-display">
                    <a href="<?= $filePath ?>" target="_blank" title="<?= $safeFile ?>">
                        <img src="<?= $filePath ?>" alt="<?= $safeFile ?>">
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($other_files)): ?>
            <div class="notice-file">
                <div class="notice-file-title">
                    <i data-feather="paperclip"></i>
                    <?= $lang['attachments'] ?? 'Attachments' ?>
                </div>

                <?php foreach ($other_files as $f):
                    $safeFile = htmlspecialchars($f);
                    $filePath = "../assets/uploads/" . $f;
                    $ext = pathinfo($f, PATHINFO_EXTENSION);
                    $isPdf = strtolower($ext) === 'pdf';
                    $displayFileName = htmlspecialchars(substr($f, strpos($f, '_', strpos($f, '_') + 1) + 1)); // Display original part of name
                    ?>
                    <a href="<?= $filePath ?>" target="_blank" class="file-download">
                        <?php if ($isPdf): ?>
                            <i data-feather="file-text"></i>
                        <?php else: ?>
                            <i data-feather="download"></i>
                        <?php endif; ?>
                        <?= $displayFileName ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<script>
    feather.replace();

    // Slider Script
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
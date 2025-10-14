<?php
session_start();
require_once __DIR__ . '/../config/db.php'; // safer path

// --- Language Handling ---
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // default
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'np'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Load language file
$langFile = __DIR__ . '/../lang/' . $_SESSION['lang'] . '.php';
if (file_exists($langFile)) {
    include $langFile;
} else {
    include __DIR__ . '/../lang/en.php';
}

// --- Check Admin ---
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$msg = "";

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $facebook_link = trim($_POST['facebook_link'] ?? '');
    $map_embed = trim($_POST['map'] ?? '');

    if (empty($email)) {
        $msg = $lang['email_required'] ?? "❌ Email is required!";
    } else {
        $stmt = $conn->prepare("UPDATE settings SET email=?, phone=?, facebook_link=?, map_embed=?, updated_at=NOW() WHERE id=1");
        if ($stmt === false) {
            $msg = ($lang['db_prepare_error'] ?? "❌ Database prepare error: ") . $conn->error;
        } else {
            $stmt->bind_param("ssss", $email, $phone, $facebook_link, $map_embed);
            if ($stmt->execute()) {
                $msg = ($stmt->affected_rows > 0)
                        ? ($lang['settings_updated'] ?? "✅ Settings updated successfully!")
                        : ($lang['no_changes'] ?? "⚠ No changes detected or row does not exist. (Ensure row with id=1 exists)");

                $settings['email'] = $email;
                $settings['phone'] = $phone;
                $settings['facebook_link'] = $facebook_link;
                $settings['map_embed'] = $map_embed;
            } else {
                $msg = ($lang['update_failed'] ?? "❌ Failed to update settings: ") . $stmt->error;
            }
        }
    }
}

// --- Fetch Current Settings ---
$settings = [
        'email' => 'contact@example.com',
        'phone' => '+977-123456789',
        'facebook_link' => 'https://facebook.com/khanepani',
        'map_embed' => ''
];

$result = mysqli_query($conn, "SELECT * FROM settings WHERE id=1");
if ($result && $fetched = mysqli_fetch_assoc($result)) {
    $settings = array_merge($settings, $fetched);
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - सलकपुर खानेपानी</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <style>
        .main-content {
            padding: 40px;
            max-width: 800px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e0e0e0;
        }
        h2 {
            font-size: 30px;
            margin-bottom: 20px;
            color: #1a202c;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        h2 span {
            margin-right: 15px;
            color: #007bff;
        }
        label {
            display: block;
            margin: 20px 0 8px;
            font-weight: 600;
            color: #4a5568;
        }
        input[type=text],
        input[type=email],
        textarea {
            width: 100%;
            padding: 14px 18px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            font-size: 16px;
            background-color: #f7f9fb;
            transition: 0.3s;
            resize: vertical;
        }
        input:focus, textarea:focus {
            border-color: #007bff;
            background-color: #ffffff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.15);
        }
        button[type=submit] {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #fff;
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 35px;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(0,123,255,0.3);
            transition: all 0.3s ease;
        }
        button[type=submit]:hover {
            background: linear-gradient(135deg, #0056b3, #007bff);
            box-shadow: 0 6px 20px rgba(0,123,255,0.4);
            transform: translateY(-2px);
        }
        .message {
            margin-bottom: 25px;
            padding: 18px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
        }
        .success { background-color: #e6ffed; color: #2f855a; border: 1px solid #a8dadc; }
        .error { background-color: #fff0f3; color: #c53030; border: 1px solid #f68c9f; }
        .map-preview-container {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background-color: #f7f9fb;
        }
        .map-iframe-wrapper {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .map-iframe-wrapper iframe {
            position: absolute;
            top:0;
            left:0;
            width:100%;
            height:100%;
            border:0;
        }

        /* --- Responsive Adjustments --- */
        @media (max-width: 850px) {
            .main-content {
                max-width: 95%; /* Use more width on tablets */
                margin: 20px auto;
                padding: 30px;
            }
        }

        @media (max-width: 600px) {
            .main-content {
                padding: 20px 15px; /* Reduce padding on mobile */
                margin: 10px auto;
                border-radius: 0; /* Optional: full width experience */
                box-shadow: none; /* Optional: remove shadow for cleaner look */
            }
            h2 {
                font-size: 24px; /* Smaller heading */
            }
            h2 span {
                margin-right: 10px; /* Smaller icon margin */
            }
            input[type=text],
            input[type=email],
            textarea {
                padding: 12px 15px; /* Smaller input padding */
                font-size: 15px;
            }
            label {
                font-size: 14px;
                margin: 15px 0 6px;
            }
            .message {
                font-size: 14px;
                padding: 15px;
            }
            button[type=submit] {
                width: 100%; /* Full width button on mobile */
                font-size: 15px;
                padding: 12px;
                margin-top: 25px;
            }
            .map-preview-container {
                padding: 10px; /* Smaller padding for map container */
            }
            /* Adjust map aspect ratio for better mobile fit if needed, e.g. 4:3 */
            .map-iframe-wrapper {
                padding-bottom: 75%; /* 4:3 aspect ratio (100 / 4 * 3 = 75) */
            }
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="main-content">
    <h2><span>⚙️</span> <?= $lang['basic_site_settings'] ?? 'Basic Site Settings' ?></h2>
    <p style="color:#718096;margin-bottom:30px;border-bottom:1px dashed #e2e8f0;padding-bottom:15px;">
        <?= $lang['admin_settings_desc'] ?? 'Configure the primary contact information and social media links for your website.' ?>
    </p>

    <?php if ($msg): ?>
        <div class="message <?= strpos($msg, '✅') === 0 ? 'success' : 'error' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="email">📧 <?= $lang['contact_email'] ?? 'Contact Email' ?></label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($settings['email']) ?>" required>

        <label for="phone">📞 <?= $lang['contact_phone'] ?? 'Contact Phone' ?></label>
        <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($settings['phone']) ?>" placeholder="<?= $lang['phone_placeholder'] ?? '+977-XXXXXXXXXX' ?>">

        <label for="facebook_link">📘 <?= $lang['facebook_link'] ?? 'Facebook Page Link' ?></label>
        <input type="text" name="facebook_link" id="facebook_link" placeholder="https://facebook.com/yourpage" value="<?= htmlspecialchars($settings['facebook_link']) ?>">

        <label for="map">🗺️ <?= $lang['map_embed'] ?? 'Google Maps Embed Link' ?></label>
        <textarea name="map" id="map" rows="4" placeholder="Paste your Google Maps iframe 'src' URL or the entire iframe code here..."><?= htmlspecialchars($settings['map_embed']) ?></textarea>

        <?php if (!empty($settings['map_embed'])): ?>
            <div class="map-preview-container">
                <p style="font-size:14px; color:#6c757d; margin-bottom:10px;">
                    <?= $lang['map_preview'] ?? 'Map Preview' ?>:
                </p>
                <div class="map-iframe-wrapper">
                    <iframe src="<?= htmlspecialchars($settings['map_embed'], ENT_QUOTES, 'UTF-8') ?>" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        <?php endif; ?>

        <button type="submit">💾 <?= $lang['save_settings'] ?? 'Save Settings' ?></button>
    </form>
</div>

</body>
</html>
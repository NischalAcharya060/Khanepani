<?php
session_start();
require_once __DIR__ . '/../config/database/db.php';

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

// System Information for Advanced Tab
$php_version = phpversion();
$mysql_version = mysqli_get_server_info($conn);
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$upload_max_filesize = ini_get('upload_max_filesize');
$memory_limit = ini_get('memory_limit');
$max_execution_time = ini_get('max_execution_time');

$msg = "";

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $facebook_link = trim($_POST['facebook_link'] ?? '');
    $map_embed = trim($_POST['map'] ?? '');
    $site_title = trim($_POST['site_title'] ?? '');
    $site_description = trim($_POST['site_description'] ?? '');

    // Simple sanitization for map_embed: extract only the src URL if full iframe is pasted
    if (preg_match('/src="([^"]+)"/i', $map_embed, $matches)) {
        $map_embed = $matches[1];
    }

    if (empty($email)) {
        $msg = $lang['email_required'] ?? "❌ Email is required!";
    } else {
        $stmt = $conn->prepare("UPDATE settings SET email=?, phone=?, facebook_link=?, map_embed=?, site_title=?, site_description=?, updated_at=NOW() WHERE id=1");
        if ($stmt === false) {
            $msg = ($lang['db_prepare_error'] ?? "❌ Database prepare error: ") . $conn->error;
        } else {
            $stmt->bind_param("ssssss", $email, $phone, $facebook_link, $map_embed, $site_title, $site_description);
            if ($stmt->execute()) {
                $msg = ($stmt->affected_rows > 0)
                        ? ($lang['settings_updated'] ?? "✅ Settings updated successfully!")
                        : ($lang['no_changes'] ?? "⚠ No changes detected or row does not exist. (Ensure row with id=1 exists)");

                // Update $settings array with new values to display on success
                $settings['email'] = $email;
                $settings['phone'] = $phone;
                $settings['facebook_link'] = $facebook_link;
                $settings['map_embed'] = $map_embed;
                $settings['site_title'] = $site_title;
                $settings['site_description'] = $site_description;
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
        'map_embed' => '',
        'site_title' => 'सलकपुर खानेपानी',
        'site_description' => 'Community Water Management System'
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
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a0ca3;
            --secondary-color: #4cc9a7;
            --accent-color: #7209b7;
            --text-dark: #1a202c;
            --text-light: #6c757d;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --success-color: #4cc9a7;
            --error-color: #f94144;
            --warning-color: #f9c74f;
            --shadow-light: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

            /* Sidebar Variables */
            --sidebar-expanded-width: 240px;
            --sidebar-collapsed-width: 70px;
        }

        .dark-mode {
            --text-dark: #e2e8f0;
            --text-light: #94a3b8;
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --border-color: #475569;
            --shadow-light: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: var(--bg-color);
            color: var(--text-dark);
            min-height: 100vh;
            transition: var(--transition);
        }

        /* Main Content Wrapper */
        .content-wrapper {
            transition: transform 0.3s ease, padding-left 0.3s ease;
            position: relative;
            z-index: 10;
            padding-left: var(--sidebar-expanded-width);
            min-height: 100vh;
        }

        .sidebar-collapsed-state .content-wrapper {
            padding-left: var(--sidebar-collapsed-width);
        }

        @media (max-width: 900px) {
            .content-wrapper {
                padding-left: 0;
                width: 100%;
            }
            body.mobile-sidebar-open .content-wrapper {
                transform: translateX(var(--sidebar-expanded-width));
            }
        }

        .main-content {
            padding: 40px;
            max-width: 1000px;
            margin: 40px auto;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border-color);
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .header-content h2 {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .header-content p {
            color: var(--text-light);
            font-size: 16px;
            font-weight: 500;
        }

        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .tab-button {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-radius: 8px;
            color: var(--text-light);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-button.active {
            background: var(--primary-color);
            color: white;
        }

        .tab-button:hover:not(.active) {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .form-section {
            margin-bottom: 35px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        label .icon {
            width: 18px;
            height: 18px;
            color: var(--primary-color);
        }

        input[type="text"],
        input[type="email"],
        input[type="url"],
        textarea,
        select {
            width: 100%;
            padding: 14px 16px;
            border-radius: 10px;
            border: 2px solid var(--border-color);
            font-size: 16px;
            background-color: var(--card-bg);
            color: var(--text-dark);
            transition: var(--transition);
            resize: vertical;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary-color);
            background-color: var(--card-bg);
            outline: none;
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.15);
        }

        textarea {
            min-height: 120px;
            font-family: 'Inter', sans-serif;
        }

        .input-with-preview {
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .input-with-preview input {
            flex: 1;
        }

        .preview-button {
            padding: 12px 16px;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
        }

        .preview-button:hover {
            background: #3aa58a;
            transform: translateY(-2px);
        }

        .map-preview-container {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background-color: rgba(0, 0, 0, 0.02);
        }

        .map-iframe-wrapper {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%;
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

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
        }

        button[type="submit"],
        .reset-button {
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        button[type="submit"] {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        button[type="submit"]:hover {
            background: linear-gradient(135deg, var(--primary-light), var(--secondary-color));
            box-shadow: 0 6px 20px rgba(76, 201, 167, 0.4);
            transform: translateY(-2px);
        }

        .reset-button {
            background: transparent;
            color: var(--text-light);
            border: 2px solid var(--border-color);
        }

        .reset-button:hover {
            background: var(--border-color);
            color: var(--text-dark);
        }

        .message {
            margin-bottom: 25px;
            padding: 18px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .success {
            background-color: rgba(76, 201, 167, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        .error {
            background-color: rgba(249, 65, 68, 0.1);
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }

        .setting-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-light);
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .setting-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        /* System Information Card Styles */
        .system-info-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-light);
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
        }

        .system-info-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .system-info-card h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .system-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .system-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: rgba(67, 97, 238, 0.05);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .system-info-item:hover {
            background: rgba(67, 97, 238, 0.1);
            transform: translateX(5px);
        }

        .system-info-label {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }

        .system-info-value {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 14px;
            background: rgba(67, 97, 238, 0.1);
            padding: 4px 10px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
        }

        /* Animation for system info card */
        .animate-fade-in {
            animation: fadeInUp 0.8s ease forwards;
        }

        /* Dark mode adjustments for system info */
        .dark-mode .system-info-item {
            background: rgba(67, 97, 238, 0.1);
            border-color: #475569;
        }

        .dark-mode .system-info-value {
            background: rgba(67, 97, 238, 0.2);
            color: var(--primary-light);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-content {
                max-width: 95%;
                margin: 30px auto;
                padding: 30px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
                margin: 20px auto;
                border-radius: 12px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .header-content h2 {
                font-size: 26px;
            }

            .settings-tabs {
                flex-wrap: wrap;
            }

            .tab-button {
                flex: 1;
                min-width: 120px;
                justify-content: center;
            }

            .form-actions {
                flex-direction: column;
            }

            button[type="submit"],
            .reset-button {
                width: 100%;
                justify-content: center;
            }

            .input-with-preview {
                flex-direction: column;
            }

            .map-iframe-wrapper {
                padding-bottom: 75%; /* Better mobile aspect ratio */
            }

            .system-info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .system-info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .system-info-value {
                align-self: flex-end;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                margin: 10px auto;
                padding: 15px;
                border-radius: 0;
                box-shadow: none;
            }

            .header-content h2 {
                font-size: 22px;
            }

            .section-title {
                font-size: 16px;
            }

            input[type="text"],
            input[type="email"],
            input[type="url"],
            textarea,
            select {
                padding: 12px 14px;
                font-size: 15px;
            }

            .system-info-card {
                padding: 20px 15px;
            }

            .system-info-item {
                padding: 10px 12px;
            }

            .system-info-label,
            .system-info-value {
                font-size: 13px;
            }
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Advanced Settings */
        .advanced-settings {
            background: rgba(67, 97, 238, 0.05);
            border-left: 4px solid var(--primary-color);
        }

        .copy-button {
            padding: 8px 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: var(--transition);
        }

        .copy-button:hover {
            background: var(--primary-light);
        }
    </style>
</head>
<body <?php echo 'class="sidebar-expanded-state"' ?>>

<?php include '../components/admin_header.php'; ?>

<div class="content-wrapper">
    <div class="main-content">
        <div class="page-header">
            <div class="header-content">
                <h2><?= $lang['site_settings'] ?? 'Site Settings' ?></h2>
                <p><?= $lang['admin_settings_desc'] ?? 'Configure your website settings and contact information' ?></p>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="message <?= strpos($msg, '✅') === 0 ? 'success' : 'error' ?>">
                <i data-feather="<?= strpos($msg, '✅') === 0 ? 'check-circle' : 'alert-circle' ?>"></i>
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div class="settings-tabs">
            <button class="tab-button active" data-tab="general">
                <i data-feather="settings"></i>
                <?= $lang['general_settings'] ?? 'General' ?>
            </button>
            <button class="tab-button" data-tab="contact">
                <i data-feather="mail"></i>
                <?= $lang['contact_info'] ?? 'Contact' ?>
            </button>
            <button class="tab-button" data-tab="social">
                <i data-feather="share-2"></i>
                <?= $lang['social_media'] ?? 'Social' ?>
            </button>
            <button class="tab-button" data-tab="advanced">
                <i data-feather="tool"></i>
                <?= $lang['advanced'] ?? 'Advanced' ?>
            </button>
        </div>

        <form method="POST" id="settingsForm">
            <!-- General Settings Tab -->
            <div class="tab-content active" id="general-tab">
                <div class="form-section">
                    <h3 class="section-title">
                        <i data-feather="globe"></i>
                        <?= $lang['site_info'] ?? 'Site Information' ?>
                    </h3>

                    <div class="form-group">
                        <label for="site_title">
                            <i data-feather="type"></i>
                            <?= $lang['site_title'] ?? 'Site Title' ?>
                        </label>
                        <input type="text" name="site_title" id="site_title"
                               value="<?= htmlspecialchars($settings['site_title']) ?>"
                               placeholder="<?= $lang['site_title_placeholder'] ?? 'Enter your site title' ?>">
                    </div>

                    <div class="form-group">
                        <label for="site_description">
                            <i data-feather="file-text"></i>
                            <?= $lang['site_description'] ?? 'Site Description' ?>
                        </label>
                        <textarea name="site_description" id="site_description"
                                  placeholder="<?= $lang['site_description_placeholder'] ?? 'Brief description of your website' ?>"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Contact Settings Tab -->
            <div class="tab-content" id="contact-tab">
                <div class="form-section">
                    <h3 class="section-title">
                        <i data-feather="phone"></i>
                        <?= $lang['contact_info'] ?? 'Contact Information' ?>
                    </h3>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email">
                                <i data-feather="mail"></i>
                                <?= $lang['contact_email'] ?? 'Contact Email' ?>
                            </label>
                            <input type="email" name="email" id="email"
                                   value="<?= htmlspecialchars($settings['email']) ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i data-feather="phone"></i>
                                <?= $lang['contact_phone'] ?? 'Contact Phone' ?>
                            </label>
                            <input type="text" name="phone" id="phone"
                                   value="<?= htmlspecialchars($settings['phone']) ?>"
                                   placeholder="<?= $lang['phone_placeholder'] ?? '+977-XXXXXXXXXX' ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="map">
                            <i data-feather="map-pin"></i>
                            <?= $lang['map_embed'] ?? 'Location Map' ?>
                        </label>
                        <div class="input-with-preview">
                            <textarea name="map" id="map" rows="3"
                                      placeholder="<?= $lang['map_placeholder'] ?? 'Paste Google Maps embed URL or iframe code...' ?>"><?= htmlspecialchars($settings['map_embed']) ?></textarea>
                            <button type="button" class="preview-button" onclick="previewMap()">
                                <i data-feather="eye"></i>
                                <?= $lang['preview'] ?? 'Preview' ?>
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($settings['map_embed'])): ?>
                        <div class="map-preview-container" id="mapPreview">
                            <p style="font-size:14px; color:var(--text-light); margin-bottom:10px;">
                                <i data-feather="map"></i>
                                <?= $lang['map_preview'] ?? 'Map Preview' ?>:
                            </p>
                            <div class="map-iframe-wrapper">
                                <iframe src="<?= htmlspecialchars($settings['map_embed'], ENT_QUOTES, 'UTF-8') ?>"
                                        allowfullscreen="" loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Social Media Tab -->
            <div class="tab-content" id="social-tab">
                <div class="form-section">
                    <h3 class="section-title">
                        <i data-feather="share-2"></i>
                        <?= $lang['social_media'] ?? 'Social Media Links' ?>
                    </h3>

                    <div class="form-group">
                        <label for="facebook_link">
                            <i data-feather="facebook"></i>
                            <?= $lang['facebook_link'] ?? 'Facebook Page' ?>
                        </label>
                        <input type="url" name="facebook_link" id="facebook_link"
                               value="<?= htmlspecialchars($settings['facebook_link']) ?>"
                               placeholder="https://facebook.com/yourpage">
                    </div>

                    <div class="setting-card">
                        <h4 style="margin-bottom: 15px; color: var(--text-dark);">
                            <i data-feather="info"></i>
                            <?= $lang['social_tips'] ?? 'Social Media Tips' ?>
                        </h4>
                        <p style="color: var(--text-light); font-size: 14px; line-height: 1.5;">
                            <?= $lang['social_tips_desc'] ?? 'Add your social media links to help visitors connect with your organization. More platforms can be added in the future.' ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Advanced Settings Tab -->
            <div class="tab-content" id="advanced-tab">
                <div class="form-section">
                    <h3 class="section-title">
                        <i data-feather="tool"></i>
                        <?= $lang['advanced_settings'] ?? 'Advanced Settings' ?>
                    </h3>

                    <!-- System Information Card -->
                    <div class="system-info-card animate-fade-in">
                        <h3><i data-feather="server"></i> <?= $lang['system_info'] ?? 'System Information' ?></h3>
                        <div class="system-info-grid">
                            <div class="system-info-item">
                                <span class="system-info-label">PHP Version</span>
                                <span class="system-info-value"><?= $php_version ?></span>
                            </div>
                            <div class="system-info-item">
                                <span class="system-info-label">MySQL Version</span>
                                <span class="system-info-value"><?= $mysql_version ?></span>
                            </div>
                            <div class="system-info-item">
                                <span class="system-info-label">Server Software</span>
                                <span class="system-info-value"><?= $server_software ?></span>
                            </div>
                            <div class="system-info-item">
                                <span class="system-info-label">Upload Max Filesize</span>
                                <span class="system-info-value"><?= $upload_max_filesize ?></span>
                            </div>
                            <div class="system-info-item">
                                <span class="system-info-label">Memory Limit</span>
                                <span class="system-info-value"><?= $memory_limit ?></span>
                            </div>
                            <div class="system-info-item">
                                <span class="system-info-label">Max Execution Time</span>
                                <span class="system-info-value"><?= $max_execution_time ?>s</span>
                            </div>
                        </div>
                    </div>

                    <div class="setting-card">
                        <h4 style="margin-bottom: 15px; color: var(--text-dark);">
                            <i data-feather="download"></i>
                            <?= $lang['export_settings'] ?? 'Export Settings' ?>
                        </h4>
                        <p style="color: var(--text-light); margin-bottom: 15px; font-size: 14px;">
                            <?= $lang['export_desc'] ?? 'Download your current settings as a backup file.' ?>
                        </p>
                        <button type="button" class="copy-button" onclick="exportSettings()">
                            <i data-feather="download"></i>
                            <?= $lang['export'] ?? 'Export Settings' ?>
                        </button>
                    </div>

                    <!-- Additional Advanced Options -->
                    <div class="setting-card">
                        <h4 style="margin-bottom: 15px; color: var(--text-dark);">
                            <i data-feather="shield"></i>
                            <?= $lang['security_settings'] ?? 'Security Settings' ?>
                        </h4>
                        <p style="color: var(--text-light); margin-bottom: 15px; font-size: 14px;">
                            <?= $lang['security_desc'] ?? 'Manage security-related settings and configurations.' ?>
                        </p>
                        <button type="button" class="copy-button" onclick="showSecurityModal()">
                            <i data-feather="settings"></i>
                            <?= $lang['configure_security'] ?? 'Configure Security' ?>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="reset-button" onclick="resetForm()">
                    <i data-feather="refresh-cw"></i>
                    <?= $lang['reset'] ?? 'Reset' ?>
                </button>
                <button type="submit" id="submitButton">
                    <i data-feather="save"></i>
                    <?= $lang['save_settings'] ?? 'Save Settings' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    feather.replace();

    // Tab Switching
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all tabs and contents
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });

    // Map Preview Function
    function previewMap() {
        const mapInput = document.getElementById('map');
        const mapPreview = document.getElementById('mapPreview');
        let mapUrl = mapInput.value.trim();

        // Extract src URL if full iframe is provided
        if (mapUrl.includes('<iframe')) {
            const match = mapUrl.match(/src="([^"]+)"/i);
            if (match) mapUrl = match[1];
        }

        if (mapUrl) {
            if (!mapPreview) {
                const previewContainer = document.createElement('div');
                previewContainer.className = 'map-preview-container';
                previewContainer.id = 'mapPreview';
                previewContainer.innerHTML = `
                    <p style="font-size:14px; color:var(--text-light); margin-bottom:10px;">
                        <i data-feather="map"></i>
                        <?= $lang['map_preview'] ?? 'Map Preview' ?>:
                    </p>
                    <div class="map-iframe-wrapper">
                        <iframe src="${mapUrl}" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                `;
                mapInput.parentNode.parentNode.appendChild(previewContainer);
            } else {
                const iframe = mapPreview.querySelector('iframe');
                iframe.src = mapUrl;
            }
            feather.replace();
        }
    }

    // Form Reset
    function resetForm() {
        if (confirm('<?= $lang['confirm_reset'] ?? 'Are you sure you want to reset all changes?' ?>')) {
            document.getElementById('settingsForm').reset();
        }
    }

    // Export Settings
    function exportSettings() {
        const settings = {
            site_title: document.getElementById('site_title').value,
            site_description: document.getElementById('site_description').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            facebook_link: document.getElementById('facebook_link').value,
            map_embed: document.getElementById('map').value,
            export_date: new Date().toISOString()
        };

        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(settings, null, 2));
        const downloadAnchor = document.createElement('a');
        downloadAnchor.setAttribute("href", dataStr);
        downloadAnchor.setAttribute("download", "settings_backup_" + new Date().toISOString().split('T')[0] + ".json");
        document.body.appendChild(downloadAnchor);
        downloadAnchor.click();
        document.body.removeChild(downloadAnchor);
    }

    // Security Settings Modal
    function showSecurityModal() {
        const securitySettings = {
            'Session Timeout': '30 minutes',
            'Password Policy': 'Strong (min 8 characters)',
            'Login Attempts': '5 attempts allowed',
            'IP Whitelist': 'Not configured',
            'Two-Factor Auth': 'Disabled'
        };

        let modalHTML = `
            <div class="modal-overlay" id="securityModal" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                backdrop-filter: blur(5px);
            ">
                <div class="modal-content" style="
                    background: var(--card-bg);
                    padding: 30px;
                    border-radius: var(--border-radius);
                    max-width: 500px;
                    width: 90%;
                    max-height: 80vh;
                    overflow-y: auto;
                    box-shadow: var(--shadow-hover);
                    border: 1px solid var(--border-color);
                ">
                    <div class="modal-header" style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 20px;
                        padding-bottom: 15px;
                        border-bottom: 1px solid var(--border-color);
                    ">
                        <h3 style="color: var(--primary-color); display: flex; align-items: center; gap: 10px;">
                            <i data-feather="shield"></i>
                            Security Settings
                        </h3>
                        <button onclick="closeSecurityModal()" style="
                            background: none;
                            border: none;
                            font-size: 24px;
                            cursor: pointer;
                            color: var(--text-light);
                        ">&times;</button>
                    </div>
                    <div class="security-settings-list">
        `;

        for (const [key, value] of Object.entries(securitySettings)) {
            modalHTML += `
                <div class="security-item" style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 12px 0;
                    border-bottom: 1px solid var(--border-color);
                ">
                    <span style="font-weight: 600; color: var(--text-dark);">${key}</span>
                    <span style="color: var(--primary-color); font-weight: 500;">${value}</span>
                </div>
            `;
        }

        modalHTML += `
                    </div>
                    <div class="modal-actions" style="
                        margin-top: 25px;
                        padding-top: 20px;
                        border-top: 1px solid var(--border-color);
                        display: flex;
                        gap: 15px;
                        justify-content: flex-end;
                    ">
                        <button onclick="closeSecurityModal()" style="
                            padding: 10px 20px;
                            background: var(--border-color);
                            color: var(--text-dark);
                            border: none;
                            border-radius: 8px;
                            cursor: pointer;
                            font-weight: 600;
                        ">Close</button>
                        <button style="
                            padding: 10px 20px;
                            background: var(--primary-color);
                            color: white;
                            border: none;
                            border-radius: 8px;
                            cursor: pointer;
                            font-weight: 600;
                        ">Configure</button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        feather.replace();
    }

    function closeSecurityModal() {
        const modal = document.getElementById('securityModal');
        if (modal) {
            modal.remove();
        }
    }

    // Form Submission Loading
    document.getElementById('settingsForm').addEventListener('submit', function() {
        const submitButton = document.getElementById('submitButton');
        submitButton.innerHTML = '<div class="loading-spinner"></div> Saving...';
        submitButton.disabled = true;
    });

    // Sidebar State Management
    function updateLayoutForSidebar() {
        const body = document.body;
        const mainContent = document.querySelector('.main-content');

        if (body.classList.contains('sidebar-collapsed-state')) {
            // Adjust layout for collapsed sidebar
            if (mainContent) {
                mainContent.style.maxWidth = '1100px';
            }
        } else {
            // Reset to normal layout for expanded sidebar
            if (mainContent) {
                mainContent.style.maxWidth = '1000px';
            }
        }
    }

    // Initialize sidebar state
    document.addEventListener('DOMContentLoaded', function() {
        updateLayoutForSidebar();

        // Listen for sidebar state changes
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                setTimeout(updateLayoutForSidebar, 300);
            });
        }

        // Also update on window resize
        window.addEventListener('resize', updateLayoutForSidebar);
    });

    // Add animation to cards
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all setting cards for animation
    document.querySelectorAll('.setting-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
</script>

</body>
</html>
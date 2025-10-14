<?php
// Define the project root path reliably.
// Assuming 500.php is in the project root.
$base_path = __DIR__;

session_start();
// --- Language Handling ---
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // default language
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','np'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Load language file - Using $base_path for absolute reliability
// Assuming structure is: /lang/en.php, /lang/np.php
$langFile = $base_path . '/lang/' . $_SESSION['lang'] . '.php';

if (file_exists($langFile)) {
    include $langFile;
} else {
    // Fallback: Use the base path for the English file
    include $base_path . '/lang/en.php';
}

// 🟢 UPDATED FOR 500 ERROR: Variables must be defined AFTER the language file is loaded.
$page_title = $lang['500_title'] ?? '500 - Server Error';
$error_heading = $lang['500_heading'] ?? 'Internal Server Error (500).';
$error_message = $lang['500_message'] ?? 'Something went wrong on the server. We are currently working hard to fix this. Please try refreshing or check back in a few minutes.';
$go_to_dashboard = $lang['go_to_dashboard'] ?? 'Go to Dashboard';
$go_to_home = $lang['back_home'] ?? 'Back to Home';

// Correct asset_base based on the assumption that 500.php is in the root.
$asset_base = './';
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= $asset_base ?>assets/images/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Changed primary color slightly to reflect a system/server issue */
            --primary-color: #f39c12; /* Orange for serious but recoverable error */
            --secondary-color: #d35400; /* Darker Orange */
            --text-color: #333;
            --white: #ffffff;
            --overlay-color: rgba(44, 62, 80, 0.85);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
            background-image: url('<?= $asset_base ?>assets/images/login_background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--overlay-color);
            z-index: 1;
        }

        .error-container {
            text-align: center;
            padding: 40px 30px;
            max-width: 500px;
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
            z-index: 10;
            animation: slideUp 0.6s ease-out forwards;
        }

        @keyframes slideUp {
            0% { opacity: 0; transform: translateY(50px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .error-code {
            font-size: 8em;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
            line-height: 1;
        }

        h1 {
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.8em;
        }

        p {
            color: #555;
            margin-bottom: 30px;
            font-size: 1em;
            line-height: 1.6;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s, transform 0.1s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background: var(--secondary-color);
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #495057;
        }

        .language-selector {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 20;
        }

        .language-selector select {
            padding: 8px 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            background-color: var(--white);
            color: var(--text-color);
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
        }

        @media (max-width: 600px) {
            .error-code {
                font-size: 6em;
            }
            .error-container {
                margin: 20px;
            }
        }
    </style>
</head>
<body>
<div class="language-selector">
    <select onchange="window.location.href='?lang=' + this.value">
        <option value="en" <?= ($_SESSION['lang'] === 'en') ? 'selected' : '' ?>>English</option>
        <option value="np" <?= ($_SESSION['lang'] === 'np') ? 'selected' : '' ?>>नेपाली</option>
    </select>
</div>

<div class="error-container">
    <div class="error-code">500</div>
    <h1><?= htmlspecialchars($error_heading) ?></h1>
    <p><?= htmlspecialchars($error_message) ?></p>

    <div class="actions">
        <!-- Conditional link for logged-in admins -->
        <?php if (isset($_SESSION['admin'])): ?>
            <a href="<?= $asset_base ?>admin/dashboard.php" class="btn">
                <i class="fas fa-tachometer-alt"></i> <?= htmlspecialchars($go_to_dashboard) ?>
            </a>
        <?php endif; ?>

        <!-- Link back to the home page -->
        <a href="<?= $asset_base ?>index.php" class="btn btn-secondary">
            <i class="fas fa-home"></i> <?= htmlspecialchars($go_to_home) ?>
        </a>
    </div>
</div>

</body>
</html>

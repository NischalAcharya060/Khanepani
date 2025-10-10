<?php
session_start();
require_once __DIR__ . '/../config/db.php'; // safer path

// --- Language Handling ---
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // default
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','np'])) {
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

    if (empty($email)) {
        $msg = $lang['email_required'] ?? "❌ Email is required!";
    } else {
        // NOTE: The 'updated_at' column is assumed to exist in the settings table.
        $stmt = $conn->prepare("UPDATE settings SET email=?, phone=?, facebook_link=?, updated_at=NOW() WHERE id=1");
        if ($stmt === false) {
            $msg = ($lang['db_prepare_error'] ?? "❌ Database prepare error: ") . $conn->error;
        } else {
            $stmt->bind_param("sss", $email, $phone, $facebook_link);
            if ($stmt->execute()) {
                $msg = ($stmt->affected_rows > 0) ? ($lang['settings_updated'] ?? "✅ Settings updated successfully!") : ($lang['no_changes'] ?? "⚠ No changes detected or row does not exist. (Ensure row with id=1 exists)");
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
        /* Modern Reset/Base */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9; /* Light, soft background */
        }

        /* Main Container Styling (The Card) */
        .main-content {
            padding: 40px;
            max-width: 800px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); /* Elevated, soft shadow */
            border: 1px solid #e0e0e0;
        }

        /* Header Styling */
        h2 {
            font-size: 30px;
            margin-bottom: 20px;
            color: #1a202c; /* Darker, professional text */
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        h2 span {
            margin-right: 15px;
            color: #007bff; /* Accent color for the icon */
            font-size: 1.2em;
        }


        /* Form Group & Label Styling */
        label {
            display: block;
            margin: 20px 0 8px;
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
            transition: color 0.3s;
        }
        label:hover {
            color: #007bff;
        }
        .icon {
            margin-right: 5px;
            font-size: 1.1em;
            vertical-align: middle;
        }

        /* Input Styling */
        input[type=text],
        input[type=email] {
            width: 100%;
            padding: 14px 18px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            box-sizing: border-box;
            transition: all 0.3s ease;
            font-size: 16px;
            background-color: #f7f9fb;
        }
        input:focus {
            border-color: #007bff;
            background-color: #ffffff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15); /* Ring shadow for better focus */
        }
        input::placeholder {
            color: #a0aec0;
        }

        /* Button Styling */
        button[type=submit] {
            background: linear-gradient(135deg, #007bff, #0056b3); /* Blue Gradient */
            color: #fff;
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 35px;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
        }
        button[type=submit]:hover {
            background: linear-gradient(135deg, #0056b3, #007bff);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
            transform: translateY(-2px);
        }
        button[type=submit]:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(0, 123, 255, 0.3);
        }

        /* Message Styling (Feedback) */
        .message {
            margin-bottom: 25px;
            padding: 18px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
        }
        .success {
            background-color: #e6ffed; /* Light green */
            color: #2f855a; /* Dark green text */
            border: 1px solid #a8dadc;
        }
        .error {
            background-color: #fff0f3; /* Light red/pink */
            color: #c53030; /* Dark red text */
            border: 1px solid #f68c9f;
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .main-content {
                margin: 20px;
                padding: 25px;
            }
            h2 {
                font-size: 24px;
            }
            input[type=text],
            input[type=email] {
                padding: 12px 15px;
            }
            button[type=submit] {
                width: 100%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="main-content">
    <h2><span aria-hidden="true">⚙️</span> <?= $lang['basic_site_settings'] ?? 'Basic Site Settings' ?></h2>
    <p style="color: #718096; margin-bottom: 30px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 15px;">
        <?= $lang['settings_desc'] ?? 'Configure the primary contact information and social media links for your public website.' ?>
    </p>

    <?php if ($msg): ?>
        <div class="message <?= strpos($msg, '✅') === 0 ? 'success' : 'error' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="email">
            <span class="icon" aria-hidden="true">📧</span> <?= $lang['contact_email'] ?? 'Contact Email' ?>
        </label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($settings['email']) ?>" required>

        <label for="phone">
            <span class="icon" aria-hidden="true">📞</span> <?= $lang['contact_phone'] ?? 'Contact Phone' ?>
        </label>
        <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($settings['phone']) ?>" placeholder="<?= $lang['phone_placeholder'] ?? '+977-XXXXXXXXXX' ?>">

        <label for="facebook_link">
            <span class="icon" aria-hidden="true">📘</span> <?= $lang['facebook_link'] ?? 'Facebook Page Link' ?>
        </label>
        <input type="text" name="facebook_link" id="facebook_link" placeholder="https://facebook.com/yourpage" value="<?= htmlspecialchars($settings['facebook_link']) ?>">

        <button type="submit">
            <span class="icon" aria-hidden="true">💾</span> <?= $lang['save_settings'] ?? 'Save Settings' ?>
        </button>
    </form>
</div>

</body>
</html>

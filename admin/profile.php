<?php
session_start();
include '../config/db.php';
include '../config/lang.php';

// Check database connection immediately
if (!isset($conn) || $conn->connect_error) {
    die("❌ Database connection failed. Check db.php config.");
}

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin'];
$is_master = false;
$error = null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// --- Admin Data Fetch ---
if ($admin_id === "master") {
    $is_master = true;
    $admin = [
            'id' => 0,
            'username' => 'masteradmin',
            'email' => 'master@admin.com',
            'status' => 'active',
            'profile_pic' => 'default.png',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => date('Y-m-d H:i:s'),
            'password' => '',
    ];
} else {
    $admin_id = intval($_SESSION['admin']);
    $stmt = $conn->prepare("SELECT id, username, email, status, profile_pic, created_at, last_login, password FROM admins WHERE id = ?");
    if ($stmt === false) die("❌ SQL Prepare failed: " . htmlspecialchars($conn->error));

    $stmt->bind_param("i", $admin_id);
    if (!$stmt->execute()) die("❌ SQL Execute failed: " . htmlspecialchars($stmt->error));

    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    if (!$admin) {
        die("❌ Admin with ID $admin_id not found.");
    }

    // --- Handle Form Submissions (Update Profile) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password'])) {
        $new_username = trim($_POST['username']);
        $profile_pic = $admin['profile_pic'] ?? 'default.png';

        if (empty($new_username)) {
            $error = "⚠️ Username cannot be empty.";
        }

        // Handle File Upload
        if (empty($error) && !empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === 0) {
            $upload_dir = '../assets/uploads/profile/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_tmp = $_FILES['profile_pic']['tmp_name'];
            $file_type = @mime_content_type($file_tmp);

            if (!in_array($file_type, $allowed_types)) {
                $error = "❌ Invalid file type. Only JPG, PNG, and GIF are allowed.";
            } else {
                $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                $file_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($file_tmp, $target_file)) {
                    if (!empty($admin['profile_pic']) && $admin['profile_pic'] !== 'default.png' && file_exists($upload_dir . $admin['profile_pic'])) {
                        unlink($upload_dir . $admin['profile_pic']);
                    }
                    $profile_pic = $file_name;
                } else {
                    $error = "❌ Failed to upload profile picture.";
                }
            }
        }

        if (empty($error)) {
            if ($new_username !== $admin['username'] || $profile_pic !== $admin['profile_pic']) {
                $stmt = $conn->prepare("UPDATE admins SET username = ?, profile_pic = ? WHERE id = ?");
                if ($stmt === false) die("❌ SQL Prepare (Update) failed: " . htmlspecialchars($conn->error));
                $stmt->bind_param("ssi", $new_username, $profile_pic, $admin['id']);
                if (!$stmt->execute()) die("❌ SQL Execute (Update) failed: " . htmlspecialchars($stmt->error));
                $stmt->close();

                $_SESSION['username'] = $new_username;
                $_SESSION['success'] = "✅ Profile updated successfully! Changes will reflect after refresh.";
                header("Location: profile.php");
                exit();
            } else {
                $error = "⚠️ No changes detected. Nothing updated.";
            }
        }
    }

    // --- Handle Form Submissions (Password Change) ---
    if (isset($_POST['change_password'])) {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if (password_verify($current_pass, $admin['password'])) {
            if ($new_pass === $confirm_pass && strlen($new_pass) >= 8) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                if ($stmt === false) die("❌ SQL Prepare (Password) failed: " . htmlspecialchars($conn->error));
                $stmt->bind_param("si", $hashed, $admin['id']);
                if (!$stmt->execute()) die("❌ SQL Execute (Password) failed: " . htmlspecialchars($stmt->error));
                $stmt->close();

                $_SESSION['success'] = "✅ Password changed successfully!";
                header("Location: profile.php");
                exit();
            } else {
                $error = "⚠️ New password and confirm password do not match or new password is too short (min 8 chars).";
            }
        } else {
            $error = "❌ Current password is incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - <?= htmlspecialchars($admin['username']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        /* ====================================================
           MODERN AESTHETIC CSS (HIGHLY REFINED)
           ==================================================== */
        :root {
            /* Color Palette */
            --color-primary: #1e88e5; /* Deep Blue - Action */
            --color-secondary: #00bcd4; /* Cyan - Accent */
            --color-text: #212529; /* Near Black */
            --color-text-light: #6c757d; /* Subtle Grey */
            --color-bg: #f5f8fa; /* Very Light Background */
            --color-card-bg: #ffffff;
            --color-hover-bg: #e6f3ff; /* Very Light Blue for Hover */

            /* Shadows and Borders */
            --shadow-card: 0 0.5rem 1rem rgba(0, 0, 0, 0.08); /* Smoother shadow */
            --shadow-hover: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.12); /* Deeper shadow on hover */
            --border-radius: 12px;
            --input-border: #dee2e6;
        }

        /* General Styling */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body {
            background: var(--color-bg);
            color: var(--color-text);
            min-height: 100vh;
        }

        /* Utility */
        .icon { width: 20px; height: 20px; margin-right: 12px; stroke-width: 2; color: var(--color-primary); }

        /* Layout Container */
        .container {
            max-width: 1100px;
            margin: 60px auto;
            padding: 30px;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 30px;
        }

        /* Card Styling */
        .card {
            background: var(--color-card-bg);
            border-radius: var(--border-radius);
            padding: 35px;
            box-shadow: var(--shadow-card);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.35s ease-in-out;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        /* Top Bar Accent (Used for form cards) */
        .card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px; /* Slightly thinner */
            background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
        }

        /* Profile Info Card Overrides */
        .card.profile-info {
            text-align: center;
            border: none; /* Remove subtle inner border */
            /* Use linear-gradient for a richer background */
            background: linear-gradient(135deg, var(--color-card-bg) 95%, #f1f2f6 100%);
            border-bottom: 5px solid var(--color-secondary);
            padding-top: 50px; /* More space above picture */
        }

        /* Hover Effect */
        .card:hover {
            transform: translateY(-5px); /* Stronger lift effect */
            box-shadow: var(--shadow-hover);
        }
        .card.profile-info:hover {
            transform: none;
            box-shadow: var(--shadow-card);
        }

        /* Headings */
        h2 {
            font-size: 32px; /* Larger main title */
            color: var(--color-primary); /* Primary color for main title */
            margin-bottom: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .card-title {
            font-size: 20px;
            margin: 0 0 25px 0;
            color: var(--color-text);
            font-weight: 700;
            display: flex;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--input-border); /* Lighter divider */
        }
        .card-title .icon {
            color: var(--color-secondary);
        }

        /* Profile Picture */
        .profile-info img {
            width: 160px; /* Larger image */
            height: 160px;
            border: 6px solid var(--color-primary); /* Thicker border */
            margin-bottom: 30px;
            box-shadow: 0 0 0 10px rgba(30, 136, 229, 0.15); /* Stronger ring effect */
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .profile-info img:hover {
            transform: scale(1.03) rotate(1deg);
        }

        /* Profile Detail List */
        .profile-detail-list {
            list-style: none;
            padding: 0;
            margin: 30px 0 0 0;
            text-align: left;
        }
        .profile-detail-list li {
            padding: 15px 10px; /* More vertical padding */
            font-size: 15px;
            border-bottom: 1px dashed var(--input-border); /* Dashed divider for subtlety */
            border-radius: 4px;
        }
        .profile-detail-list li:hover {
            background-color: var(--color-hover-bg);
        }
        .profile-detail-list li:last-child {
            border-bottom: none;
        }
        .profile-detail-list strong {
            font-weight: 700; /* Bolder label */
            color: var(--color-text);
            display: flex;
            align-items: center;
            /* Custom color for icons inside the list */
        }
        .profile-detail-list strong .icon {
            color: var(--color-primary);
            margin-right: 8px;
        }
        .profile-detail-list span {
            color: var(--color-text-light);
            font-weight: 500;
            font-size: 14px;
        }


        /* Forms Styling */
        form { gap: 25px; } /* Increased gap between form sections */
        .form-group { gap: 8px; }
        label {
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.2px;
        }
        label .icon { color: var(--color-text-light); }

        input[type="text"], input[type="password"], input[type="file"] {
            padding: 12px 16px; /* Optimized padding */
            border-radius: 8px;
            border: 1px solid var(--input-border); /* Thinner, lighter default border */
            font-size: 16px;
            background: var(--color-card-bg);
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px rgba(30, 136, 229, 0.1); /* Lighter shadow on focus */
        }
        input[type="file"] {
            padding: 12px 16px;
            cursor: pointer;
        }

        /* Button Styling */
        button {
            background: linear-gradient(45deg, var(--color-primary), #0077b6); /* Subtle gradient for depth */
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            letter-spacing: 1px; /* Clearer button text */
            box-shadow: 0 4px 12px rgba(30, 136, 229, 0.3);
            margin-top: 20px;
        }
        button:hover {
            background: linear-gradient(45deg, var(--color-secondary), #00a4b6);
            transform: translateY(-3px); /* Stronger hover effect */
            box-shadow: 0 8px 18px rgba(0, 188, 212, 0.5);
        }
        button .icon { margin-right: 8px; color: white; } /* Ensure icons are white */


        /* Message Styles (More distinct) */
        .message-box {
            padding: 18px;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 30px;
        }
        .error {
            background: #fef0f0;
            color: #c0392b;
            border-left: 5px solid #c0392b; /* Strong left bar */
        }
        .success {
            background: #e6f7ed;
            color: #27ae60;
            border-left: 5px solid #27ae60;
        }
        .message-box i { width: 20px; height: 20px; stroke-width: 2.5; margin-right: 10px; }

        /* Master Admin Warning */
        .master-warning {
            margin-top: 40px;
            font-weight: 700;
            color: #e67e22; /* Darker amber for contrast */
            padding: 20px;
            border: 3px dashed #f39c12;
            background-color: #fff9e6;
            border-radius: 10px;
        }
        .master-warning .icon { color: #f39c12; margin-right: 15px; }

        /* Responsive adjustments */
        @media(max-width:992px){
            .container {
                grid-template-columns: 1fr;
                gap: 30px;
                margin: 30px auto;
            }
            .profile-info {
                order: -1;
                padding-top: 35px;
            }
            .card {
                padding: 25px;
            }
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; // Ensure your header is included ?>

<div class="container">

    <div class="card profile-info">
        <h2><?= $lang['profile_overview'] ?></h2>

        <img src="../assets/uploads/profile/<?= htmlspecialchars($admin['profile_pic'] ?? 'default.png', ENT_QUOTES, 'UTF-8') ?>" alt="Profile Picture">

        <ul class="profile-detail-list">
            <li>
                <strong><i data-feather="user" class="icon" style="color: var(--color-text-light);"></i> <?= $lang['username'] ?> :-</strong>
                <span><?= htmlspecialchars($admin['username'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
            </li>
            <li>
                <strong><i data-feather="mail" class="icon" style="color: var(--color-text-light);"></i> Email :-</strong>
                <span><?= htmlspecialchars($admin['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
            </li>
            <li>
                <strong><i data-feather="activity" class="icon" style="color: var(--color-text-light);"></i> Status :-</strong>
                <span><?= htmlspecialchars(ucfirst($admin['status'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></span>
            </li>
            <li>
                <strong><i data-feather="calendar" class="icon" style="color: var(--color-text-light);"></i> <?= $lang['account_created'] ?> :-</strong>
                <span><?= htmlspecialchars(date('M d, Y', strtotime($admin['created_at'] ?? 'N/A')), ENT_QUOTES, 'UTF-8') ?></span>
            </li>
            <li>
                <strong><i data-feather="clock" class="icon" style="color: var(--color-text-light);"></i> <?= $lang['last_activity'] ?> :-</strong>
                <span><?= htmlspecialchars(date('H:i A, M d', strtotime($admin['last_login'] ?? 'N/A')), ENT_QUOTES, 'UTF-8') ?></span>
            </li>
        </ul>

        <?php if($is_master): ?>
            <p class="master-warning">
                <i data-feather="lock" class="icon" style="color: #ff9800;"></i>
                <?= $lang['master_admin_locked'] ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if(!$is_master): ?>
        <div class="card">
            <?php if($error): ?>
                <p class="message-box error">
                    <i data-feather="alert-triangle"></i> <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>
            <?php if($success): ?>
                <p class="message-box success">
                    <i data-feather="check-circle"></i> <?= htmlspecialchars($success) ?>
                </p>
            <?php endif; ?>

            <h3 class="card-title"><i data-feather="settings" class="icon"></i> <?= $lang['edit_account_details'] ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="username"><i data-feather="user-check" class="icon"></i> <?= $lang['username'] ?>:</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($admin['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="form-group">
                    <label for="profile_pic"><i data-feather="camera" class="icon"></i> <?= $lang['change_profile_picture'] ?>:</label>
                    <input type="file" id="profile_pic" name="profile_pic" accept=".jpg,.jpeg,.png,.gif">
                </div>

                <button type="submit"><i data-feather="save"></i> <?= $lang['save_changes'] ?></button>
            </form>

            <h3 class="card-title" style="margin-top: 40px;"><i data-feather="key" class="icon"></i> <?= $lang['update_security_credentials'] ?></h3>
            <form method="POST">
                <input type="hidden" name="change_password" value="1">

                <div class="form-group">
                    <label><i data-feather="lock" class="icon"></i> <?= $lang['current_password'] ?>:</label>
                    <input type="password" name="current_password" required placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label><i data-feather="unlock" class="icon"></i> <?= $lang['new_password'] ?>:</label>
                    <input type="password" name="new_password" required placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label><i data-feather="repeat" class="icon"></i> <?= $lang['confirm_password'] ?>:</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••">
                </div>

                <button type="submit"><i data-feather="rotate-ccw"></i> <?= $lang['change_password'] ?></button>
            </form>
        </div>
    <?php endif; ?>

</div>

<script>
    feather.replace();
</script>

</body>
</html>
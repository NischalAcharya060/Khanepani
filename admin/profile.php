<?php
session_start();
include '../config/db.php';
include '../config/lang.php'; // Assuming lang.php sets up the $lang array

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
    // Set default profile pic for non-master admins if DB value is missing or 'default.png'
    $admin['profile_pic'] = $admin['profile_pic'] ?? 'default.png';

    // --- Handle Form Submissions (Update Profile) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password']) && !isset($_POST['delete_account'])) {
        $new_username = trim($_POST['username']);
        $profile_pic = $admin['profile_pic']; // Start with existing value

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
                    // Check if old file is NOT the default and delete it
                    if ($admin['profile_pic'] !== 'default.png' && file_exists($upload_dir . $admin['profile_pic'])) {
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

    // --- Handle Form Submissions (Account Deletion) ---
    if (isset($_POST['delete_account'])) {
        if ($_POST['confirm_delete'] === 'yes') {
            // Delete the admin's profile picture if it's not the default
            $upload_dir = '../assets/uploads/profile/';
            if ($admin['profile_pic'] !== 'default.png' && file_exists($upload_dir . $admin['profile_pic'])) {
                unlink($upload_dir . $admin['profile_pic']);
            }

            $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
            if ($stmt === false) die("❌ SQL Prepare (Delete) failed: " . htmlspecialchars($conn->error));
            $stmt->bind_param("i", $admin['id']);
            if (!$stmt->execute()) die("❌ SQL Execute (Delete) failed: " . htmlspecialchars($stmt->error));
            $stmt->close();

            // Clear session and redirect after successful deletion
            session_destroy();
            header("Location: login.php?msg=account_deleted");
            exit();
        } else {
            $error = "⚠️ Account deletion was not explicitly confirmed.";
        }
    }
}

// ** FIX FOR PROFILE PICTURE PATH **
// Determine the final source path for the profile picture in the HTML
$final_profile_pic_src = '../assets/profile/default.png'; // Default path

if (!empty($admin['profile_pic']) && $admin['profile_pic'] !== 'default.png') {
    // If a custom picture exists, check the path for the uploaded folder
    $uploaded_path = '../assets/uploads/profile/' . $admin['profile_pic'];
    if (file_exists($uploaded_path)) {
        $final_profile_pic_src = $uploaded_path;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - <?= htmlspecialchars($admin['username']) ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        /* ... (CSS remains unchanged) ... */
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
            --color-danger: #e74c3c; /* Red for Danger */
            --color-danger-dark: #c0392b; /* Darker Red */

            /* Master Admin Palette */
            --color-master-bg: #2c3e50; /* Dark Slate/Navy */
            --color-master-accent: #f1c40f; /* Gold/Amber */
            --color-master-text: #ecf0f1;
            --color-master-text-subtle: #bdc3c7;


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
            grid-template-columns: 320px 1fr; /* Two-column grid for normal admin */
            gap: 30px;
        }

        /* Card Styling */
        .card {
            background: var(--color-card-bg);
            border-radius: var(--border-radius);
            padding: 55px;
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

        /* --- NORMAL ADMIN PROFILE OVERHAUL (Vertical, Clean) --- */
        .card.profile-info {
            text-align: center;
            border: none;
            /* Changed background to solid white for maximum cleanliness */
            background: var(--color-card-bg);
            border-bottom: 5px solid var(--color-secondary);
            padding-top: 50px;
            align-self: flex-start; /* Center the card vertically in its grid cell */
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
            font-size: 32px;
            color: var(--color-primary);
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
            border-bottom: 1px solid var(--input-border);
        }
        .card-title .icon {
            color: var(--color-secondary);
        }

        /* Profile Picture */
        .profile-info img {
            width: 140px; /* Slightly smaller for a tighter, centered look */
            height: 140px;
            border: 4px solid var(--color-primary); /* Slightly thinner border */
            margin-bottom: 25px;
            box-shadow: 0 0 0 8px rgba(30, 136, 229, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .profile-info img:hover {
            transform: scale(1.03) rotate(1deg);
        }

        /* Profile Detail List */
        .profile-detail-list {
            list-style: none;
            padding: 0;
            margin: 20px 0 0 0;
            text-align: left;
            /* Center the list content itself by limiting width */
            max-width: 280px;
            margin-left: auto;
            margin-right: auto;
        }
        .profile-detail-list li {
            padding: 12px 0; /* Reduced padding */
            font-size: 14px; /* Slightly smaller font for density */
            border-bottom: 1px solid var(--input-border); /* Solid, clean line */
            border-radius: 0;
        }
        .profile-detail-list li:hover {
            background-color: transparent; /* Remove hover background for simplicity */
        }
        .profile-detail-list li:last-child {
            border-bottom: none;
        }
        .profile-detail-list strong {
            font-weight: 700;
            color: var(--color-text);
            display: flex;
            align-items: center;
        }
        .profile-detail-list strong .icon {
            color: var(--color-primary);
            margin-right: 8px;
        }
        .profile-detail-list span {
            color: var(--color-text); /* Make value darker for better reading */
            font-weight: 500;
            font-size: 14px;
        }


        /* Forms Styling */
        form { display: flex; flex-direction: column; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        label {
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.2px;
        }
        label .icon { color: var(--color-text-light); }

        input[type="text"], input[type="password"], input[type="file"] {
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid var(--input-border);
            font-size: 16px;
            background: var(--color-card-bg);
            width: 100%;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px rgba(30, 136, 229, 0.1);
            outline: none;
        }
        input[type="file"] {
            padding: 12px 16px;
            cursor: pointer;
        }

        /* Button Styling */
        button {
            background: linear-gradient(45deg, var(--color-primary), #0077b6);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            letter-spacing: 1px;
            box-shadow: 0 4px 12px rgba(30, 136, 229, 0.3);
            margin-top: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        button:hover {
            background: linear-gradient(45deg, var(--color-secondary), #00a4b6);
            transform: translateY(-3px);
            box-shadow: 0 8px 18px rgba(0, 188, 212, 0.5);
        }
        button .icon { margin-right: 8px; color: white; }

        /* Danger Button Styling */
        .danger-button {
            background: linear-gradient(45deg, var(--color-danger), var(--color-danger-dark));
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }
        .danger-button:hover {
            background: linear-gradient(45deg, var(--color-danger-dark), #922b21);
            box-shadow: 0 8px 18px rgba(231, 76, 60, 0.5);
        }


        /* Message Styles (More distinct) */
        .message-box {
            padding: 18px;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
        }
        .error {
            background: #fef0f0;
            color: #c0392b;
            border-left: 5px solid #c0392b;
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
            color: #e67e22;
            padding: 20px;
            border: 3px dashed #f39c12;
            background-color: #fff9e6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .master-warning .icon { color: #f39c12; margin-right: 15px; }

        /* Danger Zone Card */
        .card.danger-zone {
            /* Override for a distinct, high-alert look */
            background-color: #fcebeb;
            border: 1px solid var(--color-danger);
            box-shadow: 0 0 15px rgba(231, 76, 60, 0.1);
            margin-top: 30px;
            padding: 25px;
        }
        .card.danger-zone:before {
            background: var(--color-danger);
        }
        .danger-zone .card-title {
            color: var(--color-danger-dark);
            border-bottom: 1px dashed var(--color-danger);
        }
        .danger-zone .card-title .icon {
            color: var(--color-danger);
        }
        .danger-zone p {
            color: var(--color-danger-dark);
            font-size: 14px;
            margin-bottom: 15px;
        }


        /* ====================================================
           MASTER ADMIN SPECIFIC STYLING (Layout Overrides)
           ==================================================== */

        /* Force Master Admin to use a single column layout */
        .container.master-admin-layout {
            grid-template-columns: 1fr;
        }

        /* 1. Master Card Background & Border */
        .card.master-admin {
            background: linear-gradient(135deg, var(--color-master-bg) 0%, #34495e 100%);
            color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border-bottom: 5px solid var(--color-master-accent);
            border: none;
        }

        /* Override profile info specifics for master card */
        .card.master-admin.profile-info {
            background: linear-gradient(135deg, var(--color-master-bg) 95%, #34495e 100%);
            border-bottom: 5px solid var(--color-master-accent);

            /* LANDSCAPE LAYOUT */
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            text-align: left;
            padding: 50px 80px;
            gap: 40px;
        }

        .card.master-admin h2 {
            color: var(--color-master-accent);
            margin-bottom: 5px;
        }
        .card.master-admin img {
            border: 6px solid var(--color-master-accent);
            box-shadow: 0 0 0 10px rgba(241, 196, 15, 0.3);
            margin-top: 10px;
            margin-bottom: 0;
            flex-shrink: 0;
            width: 160px; /* Larger master image */
            height: 160px;
        }
        .card.master-admin .profile-detail-list {
            margin: 20px 0 0 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 30px;
            max-width: 700px;
            margin-left: 0; /* Align left in landscape mode */
            margin-right: 0;
        }
        .card.master-admin .profile-detail-list li {
            padding: 10px 5px;
            border-bottom: none;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        .card.master-admin .profile-detail-list li:nth-child(even) {
            border-right: none;
        }
        .card.master-admin .profile-detail-list strong { color: var(--color-master-text); }
        .card.master-admin .profile-detail-list span { color: var(--color-master-text-subtle); }
        .card.master-admin .profile-detail-list strong .icon { color: var(--color-master-accent) !important; }

        /* Reposition the master warning message */
        .card.master-admin .master-warning {
            position: absolute;
            top: 20px;
            right: 20px;
            margin-top: 0;
            padding: 10px 20px;
            border: none;
            background-color: #f39c12;
            color: var(--color-master-bg);
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
        }
        .card.master-admin .master-warning .icon {
            color: var(--color-master-bg);
        }

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

            /* Responsive adjustment for Master Layout */
            .card.master-admin.profile-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 30px;
            }
            .card.master-admin .profile-detail-list {
                grid-template-columns: 1fr;
                gap: 10px;
                margin: 20px auto;
            }
            .card.master-admin .profile-detail-list li {
                border-right: none;
                border-bottom: 1px dashed rgba(255, 255, 255, 0.2);
            }
            .card.master-admin .master-warning {
                position: static;
                margin-top: 20px;
                margin-bottom: 0;
                color: #f39c12;
                background-color: #34495e;
            }
            .card.master-admin .master-warning .icon {
                color: var(--color-master-accent);
            }
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; // Ensure your header is included ?>

<div class="container <?= $is_master ? 'master-admin-layout' : '' ?>">

    <div class="card profile-info <?= $is_master ? 'master-admin' : '' ?>">

        <img src="<?= htmlspecialchars($final_profile_pic_src, ENT_QUOTES, 'UTF-8') ?>" alt="Profile Picture" style="border-radius: 50%; object-fit: cover;">

        <div>
            <h2><?= $lang['profile_overview'] ?></h2>
            <ul class="profile-detail-list">
                <li>
                    <strong><i data-feather="user" class="icon" style="color: var(--color-text-light);"></i> <?= $lang['username'] ?> :-</strong>
                    <span><?= htmlspecialchars($admin['username'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                </li>
                <li>
                    <strong><i data-feather="mail" class="icon" style="color: var(--color-text-light);"></i> <?= $lang['email'] ?> :-</strong>
                    <span><?= htmlspecialchars($admin['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                </li>
                <li>
                    <strong><i data-feather="activity" class="icon" style="color: var(--color-text-light);"></i> <?= $lang['status'] ?> :-</strong>
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
        </div>
        <?php if($is_master): ?>
            <p class="master-warning">
                <i data-feather="lock" class="icon"></i>
                <?= $lang['master_admin_locked'] ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if(!$is_master): ?>
    <div>
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

        <div class="card danger-zone">
            <h3 class="card-title"><i data-feather="alert-triangle" class="icon"></i> <?= $lang['danger_zone'] ?></h3>
            <p> <strong><?= $lang['warning_title'] ?></strong> <?= $lang['warning_message'] ?></p>

            <form id="delete-form" method="POST" onsubmit="return confirmAccountDeletion()">
                <input type="hidden" name="delete_account" value="1">
                <input type="hidden" id="confirm_delete_input" name="confirm_delete" value="no">
                <button type="submit" class="danger-button">
                    <i data-feather="trash-2"></i> <?= $lang['delete_account_button'] ?>
                </button>
            </form>
            <?php endif; ?>
        </div>

        <script>
            feather.replace();

            /**
             * Confirms permanent account deletion before submitting the form.
             * @returns {boolean} True if deletion is confirmed, false otherwise.
             */
            function confirmAccountDeletion() {
                return confirm("<?= $lang['confirm_delete_alert'] ?>");
            }
        </script>

</body>
</html>
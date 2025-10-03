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
unset($_SESSION['success']); // Clear success message after displaying

if ($admin_id === "master") {
    $is_master = true;
    // Master admin info (hardcoded)
    $admin = [
            'id' => 0,
            'username' => 'masteradmin',
            'email' => 'master@admin.com',
            'status' => 'active',
            'profile_pic' => 'default.png',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => date('Y-m-d H:i:s'),
            'password' => '', // Not needed
    ];
} else {
    // Normal admin fetch
    $admin_id = intval($_SESSION['admin']);

    $stmt = $conn->prepare("SELECT id, username, email, status, profile_pic, created_at, last_login, password FROM admins WHERE id = ?");

    if ($stmt === false) {
        // Prepare statement failed. This is often a SQL syntax error or connection issue.
        die("❌ SQL Prepare failed: " . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("i", $admin_id);

    if (!$stmt->execute()) {
        // Execute failed
        die("❌ SQL Execute failed: " . htmlspecialchars($stmt->error));
    }

    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    if (!$admin) {
        die("❌ Admin with ID $admin_id not found in database. Check the 'admins' table and your session data.");
    }
}

// Handle profile update (only for normal admin)
if (!$is_master && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password'])) {
    $new_username = trim($_POST['username']);
    $profile_pic = $admin['profile_pic'] ?? 'default.png';

    if (empty($new_username)) {
        $error = "⚠️ Username cannot be empty.";
    }

    // Handle File Upload
    if (empty($error) && !empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === 0) {
        $upload_dir = '../assets/uploads/profile/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        // FIX 2: Security check - Verify MIME type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $file_type = @mime_content_type($file_tmp);

        if (!in_array($file_type, $allowed_types)) {
            $error = "❌ Invalid file type. Only JPG, PNG, and GIF are allowed.";
        } else {
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            // Generate secure, unique filename
            $file_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                // Delete old pic if it exists and is not default
                if (!empty($admin['profile_pic']) && $admin['profile_pic'] !== 'default.png' && file_exists($upload_dir . $admin['profile_pic'])) {
                    unlink($upload_dir . $admin['profile_pic']);
                }
                $profile_pic = $file_name;
            } else {
                $error = "❌ Failed to upload profile picture.";
            }
        }
    }

    // FIX 3: Perform the update if valid username and no upload error
    if (empty($error)) {
        // Only update if username or profile picture has actually changed
        if ($new_username !== $admin['username'] || $profile_pic !== $admin['profile_pic']) {
            $stmt = $conn->prepare("UPDATE admins SET username = ?, profile_pic = ? WHERE id = ?");
            if ($stmt === false) {
                die("❌ SQL Prepare (Update) failed: " . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("ssi", $new_username, $profile_pic, $admin['id']);
            if (!$stmt->execute()) {
                die("❌ SQL Execute (Update) failed: " . htmlspecialchars($stmt->error));
            }
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

// Handle password change (only for normal admin)
if (!$is_master && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (password_verify($current_pass, $admin['password'])) {
        if ($new_pass === $confirm_pass && strlen($new_pass) >= 8) { // Added minimum length check
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            if ($stmt === false) {
                die("❌ SQL Prepare (Password) failed: " . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("si", $hashed, $admin['id']);
            if (!$stmt->execute()) {
                die("❌ SQL Execute (Password) failed: " . htmlspecialchars($stmt->error));
            }
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Modern Reset and Typography */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #e9ecef; color: #343a40; min-height: 100vh; }

        /* Layout Container */
        .container {
            max-width: 1000px;
            margin: 60px auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
        }

        /* Card Styling */
        .card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .card:hover {
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.15);
        }

        /* Profile Info */
        .profile-info {
            text-align: center;
            align-items: center;
            justify-content: center;
            grid-column: 1 / 2; /* Forces it to the left column */
        }
        .profile-info img {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid #4e73df;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .profile-info img:hover { transform: scale(1.08); }
        .profile-info p {
            margin: 10px 0;
            font-size: 15px;
            color: #6c757d;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px dashed #f1f1f1;
        }
        .profile-info strong {
            color: #343a40;
            font-weight: 600;
            font-size: 16px;
        }

        /* Headings */
        h2 {
            font-size: 32px;
            color: #4e73df;
            margin-bottom: 30px;
            font-weight: 800;
        }
        h3 {
            font-size: 22px;
            margin: 30px 0 20px;
            color: #343a40;
            padding-bottom: 5px;
            font-weight: 700;
            border-bottom: 2px solid #f1f1f1;
        }

        /* Forms */
        form { display: flex; flex-direction: column; gap: 15px; }
        label { font-weight: 600; font-size: 14px; color: #495057; }

        input[type="text"], input[type="password"], input[type="file"] {
            padding: 12px 18px;
            border-radius: 10px;
            border: 1px solid #ced4da;
            font-size: 15px;
            width: 100%;
            background: #f8f9fa;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input[type="text"]:focus, input[type="password"]:focus, input[type="file"]:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.2);
            background: #fff;
            outline: none;
        }

        /* Button Styling */
        button {
            background: linear-gradient(45deg, #4e73df, #6610f2);
            color: #fff;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.4);
        }
        button:hover {
            background: linear-gradient(45deg, #6610f2, #4e73df);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.6);
        }

        /* Message Styles */
        .error {
            background: #fef2f2;
            color: #b91c1c;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #fca5a5;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
        }
        .success {
            background: #ecfdf5;
            color: #065f46;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #34d399;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
        }

        /* Master Admin Warning */
        .master-warning {
            margin-top: 25px;
            font-weight: 700;
            color: #ff4d4f;
            padding: 10px;
            border: 1px dashed #ff4d4f;
            border-radius: 8px;
            background-color: #fffafa;
        }

        /* Responsive adjustments */
        @media(max-width:992px){
            .container {
                grid-template-columns: 1fr; /* Stack columns */
                gap: 30px;
                margin: 30px auto;
            }
            .profile-info {
                grid-column: 1 / 2;
            }
        }
        @media(max-width:576px){
            .card {
                padding: 30px 20px;
                border-radius: 12px;
            }
            h2 { font-size: 28px; }
            h3 { font-size: 20px; margin-top: 20px; }
            .profile-info img { width: 120px; height: 120px; }
            .profile-info p { font-size: 14px; }
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; // Assuming this provides the main navigation/header ?>

<div class="container">

    <!-- Profile Info Card -->
    <div class="card profile-info">
        <h2><span style="color:#6610f2;">👤</span> Profile Overview</h2>

        <img src="../assets/uploads/profile/<?= htmlspecialchars($admin['profile_pic'] ?? 'default.png', ENT_QUOTES, 'UTF-8') ?>" alt="Profile Picture">

        <p>
            <strong>Username</strong>
            <span><?= htmlspecialchars($admin['username'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
        </p>
        <p>
            <strong>Email</strong>
            <span><?= htmlspecialchars($admin['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
        </p>
        <p>
            <strong>Status</strong>
            <span><?= htmlspecialchars(ucfirst($admin['status'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></span>
        </p>
        <p>
            <strong>Account Created</strong>
            <span><?= htmlspecialchars(date('M d, Y', strtotime($admin['created_at'] ?? 'N/A')), ENT_QUOTES, 'UTF-8') ?></span>
        </p>
        <p style="border-bottom: none;">
            <strong>Last Activity</strong>
            <span><?= htmlspecialchars(date('H:i A, M d', strtotime($admin['last_login'] ?? 'N/A')), ENT_QUOTES, 'UTF-8') ?></span>
        </p>

        <?php if($is_master): ?>
            <p class="master-warning">
                ⚠️ Master Admin: Profile settings are locked for security.
            </p>
        <?php endif; ?>
    </div>

    <!-- Update / Password Card for non-master only -->
    <?php if(!$is_master): ?>
        <div class="card">
            <?php if($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <?php if($success): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <h3 style="margin-top: 0;">✏️ Edit Account Details</h3>
            <form method="POST" enctype="multipart/form-data">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($admin['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

                <label for="profile_pic">Change Profile Picture:</label>
                <input type="file" id="profile_pic" name="profile_pic" accept=".jpg,.jpeg,.png,.gif">

                <button type="submit" style="margin-top: 10px;">Save Profile Changes</button>
            </form>

            <h3>🔑 Update Security Credentials</h3>
            <form method="POST">
                <input type="hidden" name="change_password" value="1">

                <label>Current Password:</label>
                <input type="password" name="current_password" required placeholder="Enter your current password">

                <label>New Password (min 8 chars):</label>
                <input type="password" name="new_password" required placeholder="New password">

                <label>Confirm New Password:</label>
                <input type="password" name="confirm_password" required placeholder="Confirm new password">

                <button type="submit">Change Password</button>
            </form>
        </div>
    <?php endif; ?>

</div>

</body>
</html>

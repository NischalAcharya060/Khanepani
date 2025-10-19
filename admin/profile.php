<?php
session_start();
include '../config/database/db.php';
include '../config/lang.php';

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed.");
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
    if ($stmt === false) die("SQL error.");
    $stmt->bind_param("i", $admin_id);
    if (!$stmt->execute()) die("SQL error.");
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();
    if (!$admin) die("Admin not found.");
    $admin['profile_pic'] = $admin['profile_pic'] ?? 'default.png';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password']) && !isset($_POST['delete_account'])) {
        $new_username = trim($_POST['username']);
        $new_email = trim($_POST['email']);
        $profile_pic = $admin['profile_pic'];

        if (empty($new_username)) $error = "Username cannot be empty.";
        elseif (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) $error = "Please enter a valid email.";

        if (empty($error)) {
            $check_stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $check_stmt->bind_param("si", $new_email, $admin['id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) $error = "Email already registered.";
            $check_stmt->close();
        }

        if (empty($error) && !empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === 0) {
            $upload_dir = '../assets/uploads/profile/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_tmp = $_FILES['profile_pic']['tmp_name'];
            $file_type = @mime_content_type($file_tmp);
            if (!in_array($file_type, $allowed_types)) $error = "Invalid file type.";
            elseif ($_FILES['profile_pic']['size'] > 2 * 1024 * 1024) $error = "File too large (max 2MB).";
            else {
                $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                $file_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($file_tmp, $target_file)) {
                    if ($admin['profile_pic'] !== 'default.png' && file_exists($upload_dir . $admin['profile_pic'])) unlink($upload_dir . $admin['profile_pic']);
                    $profile_pic = $file_name;
                } else $error = "Upload failed.";
            }
        }

        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, profile_pic = ? WHERE id = ?");
            if ($stmt === false) die("SQL error.");
            $stmt->bind_param("sssi", $new_username, $new_email, $profile_pic, $admin['id']);
            if (!$stmt->execute()) die("SQL error.");
            $stmt->close();
            $_SESSION['username'] = $new_username;
            $_SESSION['success'] = "Profile updated!";
            header("Location: profile.php");
            exit();
        }
    }

    if (isset($_POST['change_password'])) {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        if (password_verify($current_pass, $admin['password'])) {
            if ($new_pass === $confirm_pass) {
                $strength = checkPasswordStrength($new_pass);
                if ($strength['score'] < 3) $error = "Weak password. " . $strength['feedback'];
                else {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    if ($stmt === false) die("SQL error.");
                    $stmt->bind_param("si", $hashed, $admin['id']);
                    if (!$stmt->execute()) die("SQL error.");
                    $stmt->close();
                    $_SESSION['success'] = "Password changed!";
                    header("Location: profile.php");
                    exit();
                }
            } else $error = "Passwords don't match.";
        } else $error = "Current password incorrect.";
    }

    if (isset($_POST['delete_account'])) {
        $confirm_text = $_POST['confirm_text'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        if ($confirm_text !== 'DELETE MY ACCOUNT') $error = "Type 'DELETE MY ACCOUNT' to confirm.";
        elseif (!password_verify($confirm_password, $admin['password'])) $error = "Password incorrect.";
        else {
            $upload_dir = '../assets/uploads/profile/';
            if ($admin['profile_pic'] !== 'default.png' && file_exists($upload_dir . $admin['profile_pic'])) unlink($upload_dir . $admin['profile_pic']);
            $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
            if ($stmt === false) die("SQL error.");
            $stmt->bind_param("i", $admin['id']);
            if (!$stmt->execute()) die("SQL error.");
            $stmt->close();
            session_destroy();
            header("Location: login.php?msg=account_deleted");
            exit();
        }
    }
}

function checkPasswordStrength($password) {
    $score = 0;
    $feedback = [];
    if (strlen($password) >= 8) $score++; else $feedback[] = "8+ characters";
    if (preg_match('/[A-Z]/', $password) && preg_match('/[a-z]/', $password)) $score++; else $feedback[] = "Upper & lowercase";
    if (preg_match('/[0-9]/', $password)) $score++; else $feedback[] = "Number";
    if (preg_match('/[^A-Za-z0-9]/', $password)) $score++; else $feedback[] = "Special character";
    return ['score' => $score, 'feedback' => implode(', ', $feedback)];
}

$final_profile_pic_src = '../assets/profile/default.png';
if (!empty($admin['profile_pic']) && $admin['profile_pic'] !== 'default.png') {
    $uploaded_path = '../assets/uploads/profile/' . $admin['profile_pic'];
    if (file_exists($uploaded_path)) $final_profile_pic_src = $uploaded_path;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - <?= htmlspecialchars($admin['username']) ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a0ca3;
            --secondary: #00bcd4;
            --success: #4cc9a7;
            --warning: #f9c74f;
            --danger: #f94144;
            --text: #212529;
            --text-light: #6c757d;
            --bg: #f5f8fa;
            --card-bg: #ffffff;
            --border: #e9ecef;
            --radius: 16px;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            --gradient: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        body.dark-mode {
            --text: #e9ecef;
            --text-light: #adb5bd;
            --bg: #1a202c;
            --card-bg: #2d3748;
            --border: #4a5568;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; transition: all 0.3s ease; }

        .container {
            max-width: 1200px;
            margin: 80px auto;
            padding: 40px;
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 40px;
            align-items: start;
        }

        .container.master-admin-layout { grid-template-columns: 1fr; }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient);
        }

        .card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15); }
        body.dark-mode .card:hover { box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); }

        .profile-card { text-align: center; padding-top: 50px; }
        .profile-card.master { background: linear-gradient(135deg, #2d3748, #4a5568); }

        .profile-image-container {
            position: relative;
            display: inline-block;
            margin-bottom: 30px;
        }

        .profile-image {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
            box-shadow: 0 0 0 12px rgba(67, 97, 238, 0.1);
            transition: all 0.4s ease;
            cursor: pointer;
        }

        body.dark-mode .profile-image { border-color: var(--primary-light); box-shadow: 0 0 0 12px rgba(72, 149, 239, 0.2); }

        .profile-image:hover { transform: scale(1.05) rotate(2deg); }

        .image-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }

        .profile-image-container:hover .image-overlay { opacity: 1; }

        .image-overlay i { color: white; font-size: 28px; }

        .profile-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 30px;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .profile-list { list-style: none; margin-top: 25px; }
        .profile-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }

        .profile-list li:last-child { border-bottom: none; }

        .profile-list strong {
            display: flex;
            align-items: center;
            font-weight: 600;
            color: var(--text);
        }

        .profile-list span { color: var(--text-light); font-weight: 500; }

        .icon { width: 20px; height: 20px; margin-right: 12px; stroke-width: 2.5; }

        .master-badge {
            background: linear-gradient(45deg, #f9c74f, #f8961e);
            color: #1a202c;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }

        form { display: flex; flex-direction: column; gap: 24px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }

        label {
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            color: var(--text);
        }

        input[type="text"], input[type="password"], input[type="email"] {
            padding: 16px 20px;
            border-radius: 12px;
            border: 2px solid var(--border);
            background: var(--card-bg);
            color: var(--text);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
            outline: none;
            background: var(--bg);
        }

        .password-meter {
            margin-top: 8px;
            height: 6px;
            background: var(--border);
            border-radius: 3px;
            overflow: hidden;
        }

        .meter-fill {
            height: 100%;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .meter-weak { background: var(--danger); width: 25%; }
        .meter-fair { background: var(--warning); width: 50%; }
        .meter-good { background: #f1c40f; width: 75%; }
        .meter-strong { background: var(--success); width: 100%; }

        .password-feedback {
            font-size: 12px;
            margin-top: 6px;
            color: var(--text-light);
            min-height: 18px;
        }

        .delete-section {
            background: rgba(248, 65, 68, 0.05);
            border: 2px solid var(--danger);
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }

        body.dark-mode .delete-section { background: rgba(248, 65, 68, 0.1); }

        .confirmation-step { margin-bottom: 20px; }
        .confirmation-step label { color: var(--danger); font-weight: 700; }

        .confirmation-step input {
            background: var(--card-bg);
            border: 2px solid rgba(248, 65, 68, 0.3);
        }

        .confirmation-step input:focus { border-color: var(--danger); }

        button {
            background: var(--gradient);
            color: white;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(67, 97, 238, 0.3);
        }

        .danger-btn {
            background: linear-gradient(45deg, var(--danger), #c0392b);
        }

        .danger-btn:hover { box-shadow: 0 12px 24px rgba(248, 65, 68, 0.3); }

        .danger-btn:disabled {
            background: var(--border);
            color: var(--text-light);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .message-box {
            padding: 20px;
            border-radius: 12px;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 5px solid;
        }

        .error { background: rgba(248, 65, 68, 0.1); color: var(--danger); border-left-color: var(--danger); }
        .success { background: rgba(76, 201, 167, 0.1); color: var(--success); border-left-color: var(--success); }

        body.dark-mode .error { background: rgba(248, 65, 68, 0.15); }
        body.dark-mode .success { background: rgba(76, 201, 167, 0.15); }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text);
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border);
        }

        @media (max-width: 968px) {
            .container { grid-template-columns: 1fr; gap: 30px; margin: 40px auto; padding: 20px; }
            .profile-card { order: -1; padding-top: 30px; }
            .card { padding: 30px; }
        }

        @media (max-width: 480px) {
            .container { margin: 20px auto; padding: 15px; }
            .card { padding: 20px; }
            .profile-image { width: 120px; height: 120px; }
        }
    </style>
</head>
<body>
<?php include '../components/admin_header.php'; ?>

<div class="container <?= $is_master ? 'master-admin-layout' : '' ?>">
    <div class="card profile-card <?= $is_master ? 'master' : '' ?>">
        <div class="profile-image-container">
            <img src="<?= htmlspecialchars($final_profile_pic_src) ?>" alt="Profile" class="profile-image" id="currentProfileImage">
            <?php if(!$is_master): ?>
                <div class="image-overlay" onclick="document.getElementById('profile_pic').click()">
                    <i data-feather="camera"></i>
                </div>
            <?php endif; ?>
        </div>

        <h2 class="profile-title"><?= $lang['profile_overview'] ?></h2>
        <ul class="profile-list">
            <li><strong><i data-feather="user" class="icon"></i> <?= $lang['username'] ?></strong> <span><?= htmlspecialchars($admin['username']) ?></span></li>
            <li><strong><i data-feather="mail" class="icon"></i> <?= $lang['email'] ?></strong> <span><?= htmlspecialchars($admin['email']) ?></span></li>
            <li><strong><i data-feather="activity" class="icon"></i> <?= $lang['status'] ?></strong> <span><?= ucfirst($admin['status']) ?></span></li>
            <li><strong><i data-feather="calendar" class="icon"></i> <?= $lang['account_created'] ?></strong> <span><?= date('M d, Y', strtotime($admin['created_at'])) ?></span></li>
            <li><strong><i data-feather="clock" class="icon"></i> <?= $lang['last_activity'] ?></strong> <span><?= date('H:i A, M d', strtotime($admin['last_login'])) ?></span></li>
        </ul>

        <?php if($is_master): ?>
            <div class="master-badge">
                <i data-feather="shield"></i> <?= $lang['master_admin_locked'] ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if(!$is_master): ?>
        <div>
            <div class="card">
                <?php if($error): ?>
                    <div class="message-box error"><i data-feather="alert-triangle"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if($success): ?>
                    <div class="message-box success"><i data-feather="check-circle"></i> <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <h3 class="section-title"><i data-feather="settings"></i> <?= $lang['edit_account_details'] ?></h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label><i data-feather="user-check" class="icon"></i> <?= $lang['username'] ?></label>
                        <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label><i data-feather="mail" class="icon"></i> <?= $lang['email'] ?></label>
                        <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label><i data-feather="camera" class="icon"></i> <?= $lang['change_profile_picture'] ?></label>
                        <input type="file" id="profile_pic" name="profile_pic" accept=".jpg,.jpeg,.png,.gif,.webp" onchange="previewImage(this)">
                    </div>

                    <button type="submit"><i data-feather="save"></i> <?= $lang['save_changes'] ?></button>
                </form>

                <h3 class="section-title" style="margin-top: 40px;"><i data-feather="key"></i> <?= $lang['update_security_credentials'] ?></h3>
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    <div class="form-group">
                        <label><i data-feather="lock" class="icon"></i> <?= $lang['current_password'] ?></label>
                        <input type="password" name="current_password" required placeholder="••••••••">
                    </div>

                    <div class="form-group">
                        <label><i data-feather="unlock" class="icon"></i> <?= $lang['new_password'] ?></label>
                        <input type="password" id="new_password" name="new_password" required placeholder="••••••••" onkeyup="checkPasswordStrength(this.value)">
                        <div class="password-meter"><div class="meter-fill" id="passwordStrengthFill"></div></div>
                        <div class="password-feedback" id="passwordFeedback"></div>
                    </div>

                    <div class="form-group">
                        <label><i data-feather="repeat" class="icon"></i> <?= $lang['confirm_password'] ?></label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••" onkeyup="checkPasswordMatch()">
                        <div class="password-feedback" id="passwordMatchFeedback"></div>
                    </div>

                    <button type="submit"><i data-feather="rotate-ccw"></i> <?= $lang['change_password'] ?></button>
                </form>
            </div>

            <div class="card">
                <h3 class="section-title"><i data-feather="alert-triangle"></i> <?= $lang['danger_zone'] ?></h3>
                <p style="color: var(--text-light); margin-bottom: 20px; line-height: 1.6;"><?= $lang['warning_message'] ?></p>

                <form method="POST" id="deleteForm">
                    <input type="hidden" name="delete_account" value="1">
                    <div class="delete-section">
                        <div class="confirmation-step">
                            <label><i data-feather="alert-circle" class="icon"></i> <?= $lang['delete_confirmation_label'] ?></label>
                            <input
                                    type="text"
                                    id="confirm_text"
                                    name="confirm_text"
                                    placeholder="<?= $lang['delete_confirmation_placeholder'] ?>"
                                    onkeyup="checkDeleteConfirmation()">
                        </div>
                        <div class="confirmation-step">
                            <label><i data-feather="lock" class="icon"></i> <?= $lang['delete_password_label'] ?></label>
                            <input
                                    type="password"
                                    id="confirm_password_delete"
                                    name="confirm_password"
                                    placeholder="<?= $lang['delete_password_placeholder'] ?>"
                                    onkeyup="checkDeleteConfirmation()">
                        </div>
                    </div>
                    <button
                            type="submit"
                            id="deleteButton"
                            class="danger-btn"
                            disabled>
                        <i data-feather="trash-2"></i> <?= $lang['delete_account_button'] ?>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    feather.replace();

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => document.getElementById('currentProfileImage').src = e.target.result;
            reader.readAsDataURL(input.files[0]);
        }
    }

    function checkPasswordStrength(password) {
        const fill = document.getElementById('passwordStrengthFill');
        const feedback = document.getElementById('passwordFeedback');
        let score = 0;
        let messages = [];
        if (password.length >= 8) score++; else messages.push("8+ characters");
        if (/[A-Z]/.test(password) && /[a-z]/.test(password)) score++; else messages.push("Upper & lowercase");
        if (/[0-9]/.test(password)) score++; else messages.push("Number");
        if (/[^A-Za-z0-9]/.test(password)) score++; else messages.push("Special character");

        fill.className = 'meter-fill';
        if (!password) { fill.style.width = '0%'; feedback.textContent = ''; return; }

        const classes = ['meter-weak', 'meter-fair', 'meter-good', 'meter-strong'];
        const texts = ['Weak', 'Fair', 'Good', 'Strong'];
        fill.classList.add(classes[score - 1]);
        feedback.textContent = texts[score - 1] + (score < 4 ? ' - ' + messages.join(', ') : ' password');
        feedback.style.color = ['#f94144', '#f9c74f', '#f1c40f', '#4cc9a7'][score - 1];
    }

    function checkPasswordMatch() {
        const pass = document.getElementById('new_password').value;
        const confirm = document.getElementById('confirm_password').value;
        const feedback = document.getElementById('passwordMatchFeedback');
        if (!confirm) feedback.textContent = '';
        else if (pass === confirm) {
            feedback.textContent = '✓ Passwords match';
            feedback.style.color = '#4cc9a7';
        } else {
            feedback.textContent = '✗ Passwords do not match';
            feedback.style.color = '#f94144';
        }
    }

    function checkDeleteConfirmation() {
        const text = document.getElementById('confirm_text').value;
        const pass = document.getElementById('confirm_password_delete').value;
        const btn = document.getElementById('deleteButton');
        const confirmed = text === 'DELETE MY ACCOUNT' && pass.length >= 1;
        btn.disabled = !confirmed;
        if (confirmed) btn.innerHTML = '<i data-feather="trash-2"></i> Confirm Permanent Deletion';
        else btn.innerHTML = '<i data-feather="trash-2"></i> <?= $lang['delete_account_button'] ?>';
        feather.replace();
    }

    document.getElementById('deleteForm')?.addEventListener('submit', e => {
        if (!confirm('FINAL WARNING: This will permanently delete your account. This action cannot be undone!')) e.preventDefault();
    });

    document.getElementById('currentProfileImage')?.addEventListener('click', () => {
        document.getElementById('profile_pic')?.click();
    });
</script>
</body>
</html>
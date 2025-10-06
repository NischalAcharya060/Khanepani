<?php
session_start();
include '../config/db.php';

// Master admin credentials
$master_username = "masteradmin";
$master_email = "master@admin.com";
$master_password = "admin@123";

// Insert master admin into database if not exists
$hashed_password = password_hash($master_password, PASSWORD_DEFAULT);
$stmt_check = $conn->prepare("SELECT id FROM admins WHERE username=?");
$stmt_check->bind_param("s", $master_username);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows === 0) {
    $stmt_insert = $conn->prepare("INSERT INTO admins (username, email, password, status, created_at, last_login) VALUES (?, ?, ?, 'active', NOW(), NOW())");
    $stmt_insert->bind_param("sss", $master_username, $master_email, $hashed_password);
    $stmt_insert->execute();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']); // username or email
    $password = $_POST['password'];

    // Master admin bypass
    if (($login === $master_username || $login === $master_email) && $password === $master_password) {
        $_SESSION['admin'] = "master";
        $_SESSION['username'] = $master_username;
        $_SESSION['is_master'] = true;
        header("Location: dashboard.php");
        exit();
    }

    // Fetch admin info
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        // 🚫 Check account status BEFORE password verify
        if ($admin['status'] === 'banned') {
            $error = "🚫 Your account has been permanently banned. Please contact the master admin.";
        } elseif ($admin['status'] === 'deactivated') {
            $error = "⚠️ Your account is currently deactivated. Contact the master admin to restore access.";
        } elseif ($admin['status'] === 'active') {
            // ✅ Only active users can proceed
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin'] = $admin['id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['is_master'] = false;

                // Update last login
                $stmt_update = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $stmt_update->bind_param("i", $admin['id']);
                $stmt_update->execute();

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "❌ Incorrect password!";
            }
        } else {
            $error = "⚠️ Unknown account status. Please contact support.";
        }
    } else {
        $error = "❌ Admin not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
    <style>
        .input-group { position: relative; margin-bottom: 20px; }
        .input-group .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .error {
            background: #f8d7da;
            padding: 10px;
            border-radius: 8px;
            color: #721c24;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="left">
        <h2>सलकपुर खानेपानी Login</h2>
        <p>Welcome! Please enter your credentials to access the admin dashboard.</p>

        <?php if(isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="input-group">
                <span class="icon">👤</span>
                <input type="text" name="login" placeholder="Username or Email" required>
            </div>

            <div class="input-group">
                <span class="icon">🔒</span>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <span class="toggle-password" onclick="togglePassword()">👁️</span>
            </div>

            <button type="submit" class="btn">Login Now</button>
        </form>

        <a href="../index.php" class="back-btn">⬅ Back to Home</a>
    </div>

    <div class="right">
        <img src="../assets/images/login_img.png" alt="Login Illustration">
    </div>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggle = document.querySelector('.toggle-password');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggle.textContent = '🙈';
        } else {
            passwordInput.type = 'password';
            toggle.textContent = '👁️';
        }
    }
</script>
</body>
</html>

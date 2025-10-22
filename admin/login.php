<?php
session_start();
include '../config/database/db.php';

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','np'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$langFile = __DIR__ . '/../lang/' . $_SESSION['lang'] . '.php';
if (file_exists($langFile)) {
    include $langFile;
} else {
    include __DIR__ . '/../lang/en.php';
}

$master_username = "masteradmin";
$master_email = "master@admin.com";
$master_password = "admin@123";

$hashed_password = password_hash($master_password, PASSWORD_DEFAULT);
$stmt_check = $conn->prepare("SELECT id FROM admins WHERE username=?");
$stmt_check->bind_param("s", $master_username);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows === 0) {
    $stmt_insert = $conn->prepare("INSERT INTO admins (username, email, password, status, role_id, created_at, last_login) VALUES (?, ?, ?, 'active', 1, NOW(), NOW())");
    $stmt_insert->bind_param("sss", $master_username, $master_email, $hashed_password);
    $stmt_insert->execute();
}

if (isset($_SESSION['admin']) && $_SESSION['admin'] !== 'master') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    $error = '';

    if (($login === $master_username || $login === $master_email) && $password === $master_password) {
        $stmt_master = $conn->prepare("
            SELECT a.id, a.username, r.role_name 
            FROM admins a 
            JOIN roles r ON a.role_id = r.id 
            WHERE a.username = ?
        ");
        $stmt_master->bind_param("s", $master_username);
        $stmt_master->execute();
        $result_master = $stmt_master->get_result();

        if ($result_master->num_rows === 1) {
            $master = $result_master->fetch_assoc();

            $_SESSION['admin'] = $master['id'];

            $_SESSION['username'] = $master['username'];
            $_SESSION['role_name'] = $master['role_name'];
            $_SESSION['is_master'] = true;

            $stmt_update = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $stmt_update->bind_param("i", $master['id']);
            $stmt_update->execute();

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "❌ Master Admin not configured correctly in the database.";
        }
    }


    $stmt = $conn->prepare("
        SELECT a.*, r.role_name 
        FROM admins a 
        JOIN roles r ON a.role_id = r.id 
        WHERE a.username = ? OR a.email = ?
    ");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        if ($admin['status'] === 'banned') {
            $error = $lang['error_banned'] ?? "🚫 Your account has been permanently banned. Please contact the master admin.";
        } elseif ($admin['status'] === 'inactive') {
            $error = $lang['error_deactivated'] ?? "⚠️ Your account is currently deactivated. Contact the master admin to restore access.";
        } elseif ($admin['status'] === 'active') {

            if (password_verify($password, $admin['password'])) {

                $_SESSION['admin'] = $admin['id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['role_name'] = $admin['role_name'];
                $_SESSION['is_master'] = (strtolower($admin['role_name']) === 'masteradmin');

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
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['admin_login_title'] ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2a6fdb;
            --secondary-color: #0d3b66;
            --text-color: #333;
            --white: #ffffff;
            --bg-light: #f4f7f9;
            --error-bg: #ffebeb;
            --error-text: #cc0000;
            --overlay-color: rgba(13, 59, 102, 0.75);
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
            background-image: url('../assets/images/login_background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            animation: backgroundPan 60s linear infinite;
        }

        @keyframes backgroundPan {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
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

        .container {
            display: flex;
            width: 90%;
            max-width: 1000px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            overflow: hidden;
            background: var(--white);
            z-index: 10;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 0.8s ease-out forwards;
        }

        @keyframes fadeIn {
            to { opacity: 1; transform: translateY(0); }
        }

        .left {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .right {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            transform: translateX(100%);
            opacity: 0;
            animation: slideInRight 1s ease-out 0.3s forwards;
        }

        @keyframes slideInRight {
            to { transform: translateX(0); opacity: 1; }
        }


        .right img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            opacity: 0.9;
            animation: pulse 4s infinite ease-in-out;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }

        h2 {
            color: var(--secondary-color);
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 2em;
            opacity: 0;
            transform: translateX(-20px);
            animation: slideInLeft 0.5s ease-out 0.8s forwards;
        }

        .left > p:not(.error) {
            color: #777;
            margin-bottom: 30px;
            font-size: 1em;
            opacity: 0;
            transform: translateX(-20px);
            animation: slideInLeft 0.5s ease-out 0.9s forwards;
        }

        @keyframes slideInLeft {
            to { opacity: 1; transform: translateX(0); }
        }

        .error {
            background: var(--error-bg);
            padding: 15px;
            border-radius: 8px;
            color: var(--error-text);
            margin-bottom: 25px;
            border-left: 5px solid var(--error-text);
            font-weight: 600;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        .login-form {
            display: flex;
            flex-direction: column;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
            opacity: 0;
            transform: translateY(10px);
        }

        .login-form .input-group:nth-child(1) {
            animation: fadeInUp 0.5s ease-out 1.1s forwards;
        }

        .login-form .input-group:nth-child(2) {
            animation: fadeInUp 0.5s ease-out 1.2s forwards;
        }

        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }


        .input-group .icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 1.1em;
            transition: color 0.3s;
        }

        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .input-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(42, 111, 219, 0.2);
            outline: none;
        }

        .input-group input:focus ~ .icon {
            color: var(--secondary-color);
        }


        .input-group .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }

        .input-group .toggle-password:hover {
            color: var(--secondary-color);
        }

        .btn {
            position: relative;
            overflow: hidden;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s, transform 0.1s, box-shadow 0.3s;
            letter-spacing: 1px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0;
            animation: fadeInUp 0.5s ease-out 1.3s forwards;
        }

        .btn:hover {
            background: var(--secondary-color);
            box-shadow: 0 5px 15px rgba(42, 111, 219, 0.4);
        }

        .btn:active {
            transform: scale(0.98);
        }


        .btn.loading {
            pointer-events: none;
            background: #4a8cd6;
            color: transparent !important;
            box-shadow: none;
        }

        .btn.loading .btn-text, .btn.loading .btn-icon {
            visibility: hidden;
            opacity: 0;
        }

        .btn .spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 25px;
            height: 25px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--white);
            border-radius: 50%;
            display: none;
            animation: spin 0.8s linear infinite;
        }

        .btn.loading .spinner {
            display: block;
        }

        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }


        .back-btn {
            display: inline-block;
            margin-top: 25px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s, transform 0.3s;
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInUp 0.5s ease-out 1.4s forwards;
        }

        .back-btn:hover {
            color: var(--secondary-color);
            transform: translateX(-5px);
        }

        .language-selector {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 20;
            transition: transform 0.3s ease-out;
            opacity: 0;
            transform: translateY(-20px);
            animation: dropdownFadeIn 0.5s ease-out 1.5s forwards;
        }

        @keyframes dropdownFadeIn {
            to { opacity: 1; transform: translateY(0); }
        }

        .language-selector select {
            padding: 8px 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            background-color: var(--white);
            color: var(--text-color);
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .language-selector select:hover {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(42, 111, 219, 0.1);
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                width: 95%;
                margin: 20px 0;
            }

            .left, .right {
                flex: none;
            }

            .left {
                padding: 30px 20px;
            }

            .right {
                display: none;
            }

            .language-selector {
                top: 10px;
                right: 10px;
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

<div class="container">
    <div class="left">
        <h2><?= $lang['login_title'] ?></h2>
        <p><?= $lang['login_subtitle'] ?></p>

        <?php if(isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" class="login-form" id="login-form">
            <div class="input-group">
                <span class="icon"><i class="fas fa-user"></i></span>
                <input type="text" name="login" placeholder="<?= $lang['placeholder_username_email'] ?>" required>
            </div>

            <div class="input-group">
                <span class="icon"><i class="fas fa-lock"></i></span>
                <input type="password" name="password" id="password" placeholder="<?= $lang['placeholder_password'] ?>" required>
                <span class="toggle-password" onclick="togglePassword()">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <button type="submit" class="btn" id="login-btn">
                <span class="btn-icon"><i class="fas fa-sign-in-alt"></i></span>
                <span class="btn-text"><?= $lang['login_button'] ?></span>
                <div class="spinner"></div>
            </button>
        </form>

        <a href="../index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> <?= $lang['back_home'] ?>
        </a>
    </div>

    <div class="right">
        <img src="../assets/images/login_img.png" alt="Municipality Administration Illustration">
    </div>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggle = document.querySelector('.toggle-password i');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggle.classList.remove('fa-eye');
            toggle.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggle.classList.remove('fa-eye-slash');
            toggle.classList.add('fa-eye');
        }
    }

    document.getElementById('login-form').addEventListener('submit', function(event) {
        const loginButton = document.getElementById('login-btn');

        const loginInput = this.elements['login'];
        const passwordInput = this.elements['password'];

        if (loginInput.value.trim() !== '' && passwordInput.value.trim() !== '') {
            loginButton.classList.add('loading');
        }
    });
</script>
</body>
</html>
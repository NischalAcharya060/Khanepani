<?php
session_start();
include '../config/db.php';

// Master admin credentials
$master_username = "masteradmin";
$master_password = "admin@123";

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Check master admin
    if ($username === $master_username && $password === $master_password) {
        $_SESSION['admin'] = "master";
        $_SESSION['username'] = $master_username;
        header("Location: dashboard.php");
        exit();
    }

    // Database users
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "❌ Incorrect password!";
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
<div class="container">
    <div class="left">
        <h2>सलकपुर खानेपानी Login</h2>
        <p>Welcome! Please enter your credentials to access the admin dashboard.</p>

        <?php if(isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="input-group">
                <span class="icon">👤</span>
                <input type="text" name="username" placeholder="Username" required>
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

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
    <title>Admin Login - Khane Pani Office</title>
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-container">
        <h2>🔑 Admin Login</h2>
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
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" name="login">Login</button>
        </form>
    </div>
</div>

</body>
</html>

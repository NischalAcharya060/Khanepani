<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include '../config/db.php';
$username = $_SESSION['username'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    // Validation
    if (empty($new_username) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if username exists
        $check = mysqli_query($conn, "SELECT * FROM admins WHERE username='$new_username'");
        if(mysqli_num_rows($check) > 0) {
            $error = "Username already exists.";
        } else {
            // Insert new admin
            $sql = "INSERT INTO admins (username, email, password, created_at) VALUES ('$new_username','$email','$hashed_password', NOW())";
            if(mysqli_query($conn, $sql)) {
                $success = "New admin added successfully!";
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Add Admin</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        /* Main content */
        .main-content {
            padding: 40px 30px;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Titles */
        .main-content h2 {
            font-size: 30px;
            color: #222;
            margin-bottom: 10px;
        }
        .main-content .subtitle {
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
        }

        /* Form */
        .form {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
        }

        /* Input groups */
        .input-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }
        .input-group label {
            margin-bottom: 6px;
            font-weight: 500;
            color: #444;
        }
        .input-group .required {
            color: #d9534f;
        }
        .input-group input {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
            transition: all 0.3s;
        }
        .input-group input:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
            outline: none;
        }

        /* Button */
        .btn {
            padding: 12px 20px;
            background: #007bff;
            color: #fff;
            font-size: 16px;
            font-weight: 500;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        /* Messages */
        .message {
            padding: 12px 18px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-size: 15px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }

        /* Responsive */
        @media (max-width: 650px) {
            .main-content {
                padding: 20px 15px;
            }
            .form {
                padding: 20px;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<?php include '../components/admin_header.php'; ?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <ul>
        <li><a href="dashboard.php">🏠 Dashboard</a></li>
        <li><a href="manage_notices.php">📢 Manage Notices</a></li>
        <li><a href="manage_gallery.php">🖼 Manage Gallery</a></li>
        <li><a href="messages.php">📬 Messages</a></li>
        <li><a href="manage_admin.php" class="active">👥 Manage Admin</a></li>
        <li><a href="settings.php">⚙ Settings</a></li>
    </ul>
</aside>

<main class="main-content">
    <h2>➕ Add New Admin</h2>
    <p class="subtitle">Create a new admin account to manage the dashboard.</p>

    <?php if(isset($success)) echo "<div class='message success'>$success</div>"; ?>
    <?php if(isset($error)) echo "<div class='message error'>$error</div>"; ?>

    <form method="POST" class="form">
        <div class="input-group">
            <label>Username <span class="required">*</span></label>
            <input type="text" name="username" required placeholder="Enter username">
        </div>

        <div class="input-group">
            <label>Email (optional)</label>
            <input type="email" name="email" placeholder="Enter email">
        </div>

        <div class="input-group">
            <label>Password <span class="required">*</span></label>
            <input type="password" name="password" required placeholder="Enter password">
        </div>

        <div class="input-group">
            <label>Confirm Password <span class="required">*</span></label>
            <input type="password" name="confirm_password" required placeholder="Confirm password">
        </div>

        <button type="submit" class="btn">Add Admin</button>
    </form>
</main>

<script>
    // Sidebar toggle for mobile view
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>

</body>
</html>

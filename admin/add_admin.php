<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include '../config/database/db.php';
include '../config/lang.php';
$username = $_SESSION['username'];

$roles = [];
$roleQuery = mysqli_query($conn, "SELECT id, role_name FROM roles ORDER BY id ASC");
if ($roleQuery && mysqli_num_rows($roleQuery) > 0) {
    while ($r = mysqli_fetch_assoc($roleQuery)) {
        $roles[] = $r;
    }
}

// Handle form submission
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role_id = intval($_POST['role_id'] ?? 0);

    // Validation
    if (empty($new_username) || empty($password) || empty($confirm_password) || $role_id === 0) {
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
            $added_by = mysqli_real_escape_string($conn, $username);
            $sql = "INSERT INTO admins (username, email, password, role_id, added_by, created_at) 
                    VALUES ('$new_username','$email','$hashed_password', $role_id, '$added_by', NOW())";

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
    <title><?= $lang['manage_admin'] ?? 'Add Admin' ?> - Dashboard</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .title-group {
            display: flex;
            flex-direction: column;
        }

        .main-content h2 {
            font-size: 28px;
            color: var(--text-color-dark);
            margin: 0;
            font-weight: 700;
        }
        .main-content .subtitle {
            color: var(--text-color-light);
            font-size: 16px;
            margin-top: 5px;
        }

        /* --- Back Button --- */
        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-color-light);
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .back-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            box-shadow: var(--shadow-subtle);
        }
        .back-btn i {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }


        /* --- Form Card --- */
        .form-card {
            background: var(--card-bg);
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: var(--shadow-subtle);
            border: 1px solid var(--border-color);
        }

        /* --- Input Groups --- */
        .input-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }
        .input-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color-light);
            font-size: 14px;
        }
        .input-group .required {
            color: var(--error-color);
            margin-left: 4px;
        }

        .input-group input, .input-group select {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-size: 16px;
            color: var(--text-color-dark);
            background-color: var(--bg-light);
            transition: all 0.3s;
        }
        .input-group input:focus, .input-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 122, 255, 0.2); /* Focus ring effect */
            background-color: var(--card-bg);
            outline: none;
        }

        /* --- Button --- */
        .btn {
            padding: 14px 25px;
            background: var(--primary-color);
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s, box-shadow 0.2s;
            margin-top: 15px;
        }
        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(76, 122, 255, 0.3);
        }
        .btn:active {
            transform: translateY(0);
            box-shadow: none;
        }

        /* --- Messages --- */
        .message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        .message i {
            margin-right: 10px;
            width: 20px;
            height: 20px;
        }
        .success {
            background-color: #d1fae5; /* Light green */
            color: var(--success-color);
            border: 1px solid #a7f3d0;
        }
        .error {
            background-color: #fee2e2; /* Light red */
            color: var(--error-color);
            border: 1px solid #fca5a5;
        }

        /* --- Responsive Layout for Smaller Screens --- */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }
            .form-card {
                padding: 20px;
            }
            .btn {
                width: 100%;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div class="title-group">
            <h2><?= $lang['add'] ?? 'Add' ?> <?= $lang['manage_admin'] ?? 'Admin' ?></h2>
            <p class="subtitle"><?= $lang['subtitle_add_admin'] ?? 'Create a new admin account to manage the dashboard.' ?></p>
        </div>

        <a href="manage_admins.php" class="back-btn">
            <i data-feather="arrow-left"></i>
            <?= $lang['back'] ?? 'Back to Admins' ?>
        </a>
    </div>

    <?php if(isset($success)): ?>
        <div class='message success'><i data-feather="check-circle"></i><?= $success ?></div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class='message error'><i data-feather="alert-triangle"></i><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="form-card">
        <div class="input-group">
            <label><?= $lang['username'] ?? 'Username' ?> <span class="required">*</span></label>
            <input type="text" name="username" required placeholder="<?= $lang['username_placeholder'] ?? 'Enter username' ?>"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div class="input-group">
            <label><?= $lang['email'] ?? 'Email' ?> (<?= $lang['optional'] ?? 'optional' ?>)</label>
            <input type="email" name="email" placeholder="<?= $lang['email_placeholder'] ?? 'Enter email' ?>"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="input-group">
            <label><?= $lang['role'] ?? 'Role' ?> <span class="required">*</span></label>
            <select name="role_id" required>
                <option value="">-- <?= $lang['select_role'] ?? 'Select Role' ?> --</option>
                <?php $selected_role_id = intval($_POST['role_id'] ?? 0); ?>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['id'] ?>"
                        <?= $selected_role_id === $role['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($role['role_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="input-group">
            <label><?= $lang['password'] ?? 'Password' ?> <span class="required">*</span></label>
            <input type="password" name="password" required placeholder="<?= $lang['password_placeholder'] ?? 'Enter password' ?>">
        </div>

        <div class="input-group">
            <label><?= $lang['confirm_password'] ?? 'Confirm Password' ?> <span class="required">*</span></label>
            <input type="password" name="confirm_password" required placeholder="<?= $lang['confirm_password_placeholder'] ?? 'Confirm password' ?>">
        </div>

        <button type="submit" class="btn"> <?= $lang['add_new_admin'] ?? 'Admin' ?></button>
    </form>
</main>

<script>
    feather.replace();
</script>

</body>
</html>
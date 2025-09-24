<?php
session_start();
include '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Handle ban/unban request
if (isset($_GET['ban'])) {
    $id = intval($_GET['ban']);
    $check = mysqli_query($conn, "SELECT username, status FROM admins WHERE id=$id");
    $row = mysqli_fetch_assoc($check);

    if ($row && $row['username'] !== 'masteradmin') {
        if ($row['status'] === 'active') {
            mysqli_query($conn, "UPDATE admins SET status='banned' WHERE id=$id");
            $_SESSION['msg'] = "🚫 Admin banned successfully.";
        } else {
            mysqli_query($conn, "UPDATE admins SET status='active' WHERE id=$id");
            $_SESSION['msg'] = "✅ Admin unbanned successfully.";
        }
    }
    header("Location: manage_admin.php");
    exit();
}

// Fetch all admins except masteradmin
$result = mysqli_query($conn, "SELECT * FROM admins WHERE username!='masteradmin' ORDER BY created_at DESC");
$hasAdmins = mysqli_num_rows($result) > 0;

// Handle ban / unban
if (isset($_GET['ban'])) {
    $id = intval($_GET['ban']);
    mysqli_query($conn, "UPDATE admins SET status='banned' WHERE id=$id AND username!='masteradmin'");
    $_SESSION['msg'] = "🚫 Admin banned successfully.";
    header("Location: manage_admin.php"); exit();
}

if (isset($_GET['unban'])) {
    $id = intval($_GET['unban']);
    mysqli_query($conn, "UPDATE admins SET status='active' WHERE id=$id AND username!='masteradmin'");
    $_SESSION['msg'] = "✅ Admin unbanned successfully.";
    header("Location: manage_admin.php"); exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Admin - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }

        .main-content { padding: 30px; }
        h2 { font-size: 26px; margin-bottom: 5px; display: inline-block; color: #333; }
        .subtitle { color: #666; margin-bottom: 20px; font-size: 14px; }

        .add-btn {
            float: right;
            background: linear-gradient(135deg, #28a745, #218838);
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }
        .add-btn:hover { opacity: 0.85; }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        table th, table td {
            padding: 14px 16px;
            text-align: left;
            font-size: 14px;
        }

        table th {
            background: #007bff;
            color: #fff;
            font-weight: 600;
        }

        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #f1f1f1; }

        .btn-ban {
            background: #ffc107;
            color: #000;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            transition: 0.3s;
        }
        .btn-ban:hover { background: #e0a800; color: #fff; }

        .btn-unban {
            background: #e20808;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            transition: 0.3s;
        }
        .btn-unban:hover { background: #ef051b; color: #000000; }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }

        .message {
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
        }
        .success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
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
    <h2>👥 Manage Admins</h2>
    <a href="add_admin.php" class="add-btn">➕ Add Admin</a>
    <div style="clear: both;"></div>
    <p class="subtitle">Ban/unban admin accounts (Master Admin excluded).</p>

    <?php if(isset($_SESSION['msg'])): ?>
        <div class="message success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
    <?php endif; ?>

    <?php if($hasAdmins): ?>
        <table>
            <thead>
            <tr>
                <th>S.N.</th>
                <th>👤 Username</th>
                <th>📧 Email</th>
                <th>📅 Created At</th>
                <th>Status</th>
                <th>🕒 Last Login</th>
                <th>⚡ Action</th>
            </tr>
            </thead>
            <tbody>
            <?php $sn = 1; ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $sn++ ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></td>
                    <td>
                        <?php if($row['status'] === 'active'): ?>
                            <span class="badge badge-success">✅ Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">🚫 Banned</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $row['last_login'] ? date("M d, Y h:i A", strtotime($row['last_login'])) : 'Never' ?>
                    </td>
                    <td>
                        <?php if($row['status'] === 'active'): ?>
                            <a href="manage_admin.php?ban=<?= $row['id'] ?>" class="btn-ban"
                               title="Ban Admin" onclick="return confirm('Ban this admin?')">🚫 Ban</a>
                        <?php else: ?>
                            <a href="manage_admin.php?unban=<?= $row['id'] ?>" class="btn-unban"
                               title="Unban Admin" onclick="return confirm('Unban this admin?')">✅ Unban</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="
        text-align: center;
        background: #fff3cd;
        color: #856404;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid #ffeeba;
        font-size: 16px;
        margin-top: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    ">
            <span style="font-size: 24px;">⚠</span>
            <span>No admins available.</span>
        </div>
    <?php endif; ?>
</main>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>
</body>
</html>

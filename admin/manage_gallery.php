<?php
session_start();
include '../config/db.php';

// ✅ Restrict access
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];

// ✅ Delete Image
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $query = mysqli_query($conn, "SELECT image FROM gallery WHERE id=$id");
    $row = mysqli_fetch_assoc($query);

    if ($row) {
        $imagePath = "../assets/uploads/" . $row['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        mysqli_query($conn, "DELETE FROM gallery WHERE id=$id");
        $_SESSION['msg'] = "🗑️ Image deleted successfully.";
    }
    header("Location: manage_gallery.php");
    exit();
}

// ✅ Pagination settings
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// ✅ Fetch images with album names using LIMIT
$result = mysqli_query($conn, "
    SELECT g.*, a.name AS album_name 
    FROM gallery g
    LEFT JOIN albums a ON g.album_id = a.id
    ORDER BY g.created_at DESC
    LIMIT $offset, $limit
");

// ✅ Get total images for pagination
$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM gallery");
$total_row = mysqli_fetch_assoc($total_result);
$total_pages = ceil($total_row['total'] / $limit);

$hasImages = mysqli_num_rows($result) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Gallery - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body { font-family: 'Roboto', sans-serif; background: #f4f6f9; margin: 0; }

        .main-content { padding: 40px; }
        h2 { font-size: 26px; margin-bottom: 5px; display: inline-block; color: #222; }
        .subtitle { color: #666; margin-bottom: 25px; font-size: 14px; }

        .add-btn {
            float: right;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #fff;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 10px rgba(0,123,255,0.25);
            transition: all 0.3s ease;
        }
        .add-btn:hover { background: linear-gradient(135deg, #0056b3, #004099); transform: translateY(-2px); }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            animation: fadeInUp 0.4s ease;
        }
        table th, table td { padding: 14px 16px; text-align: left; font-size: 14px; }
        table th { background: #007bff; color: #fff; font-weight: 600; }
        table tr:nth-child(even) { background: #f9f9f9; }
        table tr:hover { background: #f1f6ff; }

        .btn {
            display: inline-block;
            padding: 7px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            margin-right: 5px;
            transition: all 0.3s ease;
        }
        .btn-view { background: #17a2b8; color: #fff; box-shadow: 0 3px 8px rgba(23,162,184,0.3); }
        .btn-view:hover { background: #117a8b; transform: scale(1.05); }
        .btn-edit { background: #ffc107; color: #000; box-shadow: 0 3px 8px rgba(255,193,7,0.3); }
        .btn-edit:hover { background: #e0a800; transform: scale(1.05); }
        .btn-delete { background: #dc3545; color: #fff; box-shadow: 0 3px 8px rgba(220,53,69,0.3); }
        .btn-delete:hover { background: #b02a37; transform: scale(1.05); }

        .gallery-img {
            width: 90px;
            height: 65px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: transform 0.3s ease;
        }
        .gallery-img:hover { transform: scale(1.05); }

        .message {
            margin-bottom: 20px;
            padding: 14px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
        }
        .success { background: #d4edda; color: #155724; border-left: 5px solid #28a745; }
        .warning { background: #fff3cd; color: #856404; border-left: 5px solid #ffc107; }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }
        .pagination a {
            margin: 0 5px;
            text-decoration: none;
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #0056d6;
        }
        .pagination a.active {
            background-color: #0056d6;
            color: white;
            border-color: #0056d6;
        }
        .pagination a:hover {
            background-color: #0056d6;
            color: white;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<header class="admin-header">
    <div class="logo">
        <img src="../assets/images/logo.jpg" alt="Logo">
        <h1>सलकपुर खानेपानी</h1>
    </div>
    <div class="user-info">
        <span>👤 <?= htmlspecialchars($username) ?></span>
        <a href="../admin/logout.php" class="logout-btn">Logout</a>
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    </div>
</header>

<aside class="sidebar" id="sidebar">
    <ul>
        <li><a href="dashboard.php">🏠 Dashboard</a></li>
        <li><a href="manage_notices.php">📢 Manage Notices</a></li>
        <li><a href="manage_gallery.php" class="active">🖼 Manage Gallery</a></li>
        <li><a href="messages.php">📬 Messages</a></li>
        <li><a href="manage_admin.php">👥 Manage Admin</a></li>
        <li><a href="settings.php">⚙ Settings</a></li>
    </ul>
</aside>

<main class="main-content">
    <h2>🖼 Manage Gallery</h2>
    <a href="gallery_add.php" class="add-btn">➕ Add New Image</a>
    <div style="clear: both;"></div>
    <p class="subtitle">View, edit, or delete uploaded images.</p>

    <?php if(isset($_SESSION['msg'])): ?>
        <div class="message success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
    <?php endif; ?>

    <?php if($hasImages): ?>
        <table>
            <thead>
            <tr>
                <th>S.N.</th>
                <th>🖼 Image</th>
                <th>📄 Title</th>
                <th>📂 Album</th>
                <th>📅 Uploaded At</th>
                <th>⚡ Action</th>
            </tr>
            </thead>
            <tbody>
            <?php $sn = $offset + 1; ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $sn++ ?></td>
                    <td><img src="../assets/uploads/<?= htmlspecialchars($row['image']) ?>" class="gallery-img"></td>
                    <td><?= $row['title'] ? htmlspecialchars($row['title']) : '<em>No Title</em>' ?></td>
                    <td><?= $row['album_name'] ? htmlspecialchars($row['album_name']) : '<em>Uncategorized</em>' ?></td>
                    <td><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></td>
                    <td>
                        <a href="gallery_edit.php?id=<?= $row['id'] ?>" class="btn btn-edit">✏ Edit</a>
                        <a href="manage_gallery.php?delete=<?= $row['id'] ?>" class="btn btn-delete"
                           onclick="return confirm('⚠️ Are you sure you want to delete this image?')">🗑️ Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?= $page-1 ?>">« Previous</a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            for($p = $start; $p <= $end; $p++): ?>
                <a href="?page=<?= $p ?>" class="<?= ($p == $page) ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>

            <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?>">Next »</a>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="message warning" style="text-align:center; font-size:15px;">
            ⚠ No images in gallery yet.
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

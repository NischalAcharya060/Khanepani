<?php
session_start();
include '../config/db.php';

// Restrict access
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];

if (!isset($_GET['id'])) {
    header("Location: manage_gallery.php");
    exit();
}

$id = intval($_GET['id']);
$result = mysqli_query($conn, "SELECT * FROM gallery WHERE id = $id");
$image = mysqli_fetch_assoc($result);

if (!$image) {
    $_SESSION['msg'] = "⚠ Image not found.";
    header("Location: manage_gallery.php");
    exit();
}

// Fetch albums for dropdown
$albums = mysqli_query($conn, "SELECT * FROM albums ORDER BY name ASC");

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $album_id = intval($_POST['album_id']);

    $filename = $image['image']; // keep old
    if (!empty($_FILES['image']['name'])) {
        $filename = time() . "_" . basename($_FILES['image']['name']);
        $targetPath = "../assets/uploads/" . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            // delete old
            $oldPath = "../assets/uploads/" . $image['image'];
            if (file_exists($oldPath)) unlink($oldPath);
        }
    }

    mysqli_query($conn, "UPDATE gallery SET title='$title', album_id='$album_id', image='$filename' WHERE id=$id");
    $_SESSION['msg'] = "✅ Image updated successfully.";
    header("Location: manage_gallery.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Image - सलकपुर खानेपानी</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        /* Main Content */
        .main-content {
            margin-left: 240px;
            padding: 50px 40px;
            transition: 0.3s;
        }
        .sidebar.active ~ .main-content { margin-left: 0; }

        /* Form Card */
        .edit-form {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 35px 30px;
            border-radius: 15px;
            box-shadow: 0 12px 25px rgba(0,0,0,0.08);
            transition: 0.3s;
        }
        .edit-form h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 26px;
        }

        /* Inputs */
        .input-group { margin-bottom: 20px; }
        label { display: block; font-weight: 500; margin-bottom: 8px; color: #34495e; }
        input[type="text"], select, input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #dcdde1;
            font-size: 14px;
            transition: 0.3s;
        }
        input[type="text"]:focus, select:focus, input[type="file"]:focus {
            border-color: #28a745;
            outline: none;
        }

        /* Current Image Preview */
        .current-img { text-align: center; margin-bottom: 20px; }
        .current-img img {
            width: 200px;
            height: auto;
            border-radius: 12px;
            border: 1px solid #dcdde1;
            object-fit: cover;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 500;
            border: none;
            border-radius: 12px;
            background: #28a745;
            color: #fff;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-submit:hover { background: #218838; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; padding: 30px 20px; }
        }
        @media (max-width: 576px) {
            .sidebar { position: relative; width: 100%; height: auto; top: 0; }
            .main-content { margin-left: 0; padding: 20px 15px; }
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

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
    <form method="POST" enctype="multipart/form-data" class="edit-form">
        <h2>✏ Edit Image</h2>

        <div class="input-group">
            <label>📄 Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($image['title']) ?>" required>
        </div>

        <div class="input-group">
            <label>📂 Album</label>
            <select name="album_id">
                <option value="0">Uncategorized</option>
                <?php while($a = mysqli_fetch_assoc($albums)): ?>
                    <option value="<?= $a['id'] ?>" <?= $image['album_id'] == $a['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="current-img">
            <label>Current Image:</label><br>
            <img src="../assets/uploads/<?= htmlspecialchars($image['image']) ?>" alt="Current Image">
        </div>

        <div class="input-group">
            <label>Replace Image</label>
            <input type="file" name="image" accept="image/*">
        </div>

        <button type="submit" class="btn-submit">💾 Save Changes</button>
    </form>
</main>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>

</body>
</html>

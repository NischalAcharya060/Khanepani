<?php
session_start();
include '../config/db.php';
include '../config/lang.php';

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
    $fileError = false;

    if (!empty($_FILES['image']['name'])) {
        $imageTmp = $_FILES['image']['tmp_name'];
        $targetDir = "../assets/uploads/";

        // Sanitize file name and ensure uniqueness
        $uniqueFileName = uniqid() . '-' . time() . '-' . preg_replace("/[^a-zA-Z0-9\.]/", "", basename($_FILES['image']['name']));
        $targetPath = $targetDir . $uniqueFileName;

        $allowedTypes = ['jpg','jpeg','png','gif'];
        $fileExt = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowedTypes)) {
            if (move_uploaded_file($imageTmp, $targetPath)) {
                // delete old only if the old file is not the default placeholder or empty
                $oldFilename = $image['image'];
                $oldPath = $targetDir . $oldFilename;
                if (!empty($oldFilename) && file_exists($oldPath)) {
                    unlink($oldPath);
                }
                $filename = $uniqueFileName;
            } else {
                $fileError = $lang['file_upload_failed'] ?? "❌ Error uploading new file.";
            }
        } else {
            $fileError = $lang['allowed_types'] ?? "❌ Only JPG, JPEG, PNG, GIF allowed.";
        }
    }

    if (!$fileError) {
        $update_sql = "UPDATE gallery SET title='$title', album_id='$album_id', image='$filename' WHERE id=$id";
        if (mysqli_query($conn, $update_sql)) {
            $_SESSION['msg'] = $lang['notice_updated'] ?? "✅ Image updated successfully.";
            header("Location: manage_gallery.php");
            exit();
        } else {
            $error = $lang['db_error'] . ": " . mysqli_error($conn);
        }
    } else {
        $error = $fileError;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['edit_image'] ?? "Edit Image" ?> - <?= $lang['logo'] ?? "Salakpur KhanePani" ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        /* --- General Styling and Layout --- */
        :root {
            --primary-color: #007bff;
            --primary-dark: #0056b3;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --background-light: #f4f6f9;
            --card-background: #ffffff;
            --border-color: #e9ecef;
            --text-dark: #343a40;
            --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--background-light);
            color: var(--text-dark);
        }

        /* Ensure main-content padding is right when not using sidebar.css */
        .main-content {
            padding: 30px;
            max-width: 700px; /* Centered form max width */
            margin: 0 auto;
        }

        /* --- Back Button Styling --- */
        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            margin-bottom: 25px;
            background: var(--secondary-color);
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s, transform 0.1s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        .back-btn svg {
            width: 18px;
            height: 18px;
            margin-right: 8px;
        }

        /* --- Alerts/Messages --- */
        .alert-error {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        /* --- Form Card --- */
        .edit-form {
            background: var(--card-background);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-light);
            transition: box-shadow 0.3s ease;
        }
        .edit-form:hover {
            box-shadow: var(--shadow-hover);
        }

        .edit-form h2 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 25px;
            color: var(--primary-color);
            text-align: left;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .edit-form h2 svg {
            margin-right: 10px;
            width: 26px;
            height: 26px;
        }


        /* --- Form Elements --- */
        .input-group { margin-bottom: 20px; }
        .input-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--secondary-color);
        }

        input[type="text"], select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="text"]:focus, select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            outline: none;
        }

        input[type="file"] {
            width: 100%;
            padding: 10px 0;
            border: none;
            font-size: 16px;
        }

        /* --- Current Image Preview --- */
        .current-img {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            border: 1px dashed var(--border-color);
            border-radius: 8px;
            background: var(--background-light);
        }
        .current-img-label {
            font-size: 14px;
            font-weight: 500;
            color: var(--secondary-color);
            margin-bottom: 10px;
            display: block;
        }
        .current-img img {
            max-width: 100%;
            max-height: 200px;
            width: auto;
            height: auto;
            border-radius: 8px;
            object-fit: contain;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* --- Submit Button --- */
        .btn-submit {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 500;
            border: none;
            border-radius: 8px;
            background: var(--success-color); /* Used success color for update/save */
            color: #fff;
            cursor: pointer;
            transition: background 0.3s, transform 0.1s;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:hover {
            background: #218838;
            transform: translateY(-1px);
        }

        /* Responsive adjustments */
        @media (max-width: 700px) {
            .main-content {
                padding: 15px;
            }
            .edit-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="main-content">

    <!-- Back Button -->
    <a href="manage_gallery.php" class="back-btn">
        <i data-feather="arrow-left"></i>
        <?= $lang['back'] ?? 'Back to Gallery' ?>
    </a>

    <form method="POST" enctype="multipart/form-data" class="edit-form">
        <h2><i data-feather="edit"></i> <?= $lang['edit_image'] ?? "Edit Image" ?></h2>

        <?php if(isset($error)): ?>
            <p class="alert-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <div class="input-group">
            <label><i data-feather="tag" style="width:14px; height:14px;"></i> <?= $lang['image_title'] ?? "Title" ?></label>
            <input type="text" name="title" value="<?= htmlspecialchars($image['title']) ?>" placeholder="<?= $lang['image_title_placeholder'] ?? "Enter image title" ?>" required>
        </div>

        <div class="input-group">
            <label><i data-feather="folder" style="width:14px; height:14px;"></i> <?= $lang['image_album'] ?? "Album" ?></label>
            <select name="album_id">
                <option value="0"><?= $lang['uncategorized'] ?? "Uncategorized" ?></option>
                <?php while($a = mysqli_fetch_assoc($albums)): ?>
                    <option value="<?= $a['id'] ?>" <?= $image['album_id'] == $a['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="current-img">
            <label class="current-img-label">🖼 <?= $lang['view'] ?? "Current Image Preview" ?>:</label>
            <img src="../assets/uploads/<?= htmlspecialchars($image['image']) ?>" alt="Current Image">
        </div>

        <div class="input-group">
            <label><i data-feather="upload" style="width:14px; height:14px;"></i> <?= $lang['replace_file'] ?? "Replace Image (Optional)" ?></label>
            <input type="file" name="image" accept="image/*">
        </div>

        <button type="submit" class="btn-submit">
            <i data-feather="save"></i> <?= $lang['save'] ?? "Save Changes" ?>
        </button>
    </form>
</main>

<script>
    // Initialize Feather Icons
    feather.replace();
</script>

</body>
</html>
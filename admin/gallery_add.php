<?php
session_start();
include '../config/database/db.php';
include '../config/lang.php';

// ✅ Restrict access (only logged-in admin)
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];

// ✅ Handle Album Creation
if (isset($_POST['create_album'])) {
    $albumName = mysqli_real_escape_string($conn, $_POST['album_name']);
    $albumDesc = mysqli_real_escape_string($conn, $_POST['album_description']);

    $sql = "INSERT INTO albums (name, description) VALUES ('$albumName', '$albumDesc')";
    if (mysqli_query($conn, $sql)) {
        $success = $lang['album_created'] ?? "✅ Album created successfully!";
    } else {
        $error = $lang['db_error'] . " (Album): " . mysqli_error($conn);
    }
}

// ✅ Handle Image Upload
if (isset($_POST['upload_image'])) {
    $album_id = intval($_POST['album_id']); // chosen album
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $imageName = $_FILES['image']['name'];
    $imageTmp = $_FILES['image']['tmp_name'];
    $targetDir = "../assets/uploads/";

    // Check and Create Upload Directory
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            $error = $lang['dir_creation_failed'] ?? "❌ Failed to create upload directory! Check parent folder permissions.";
            goto after_upload_check;
        }
    }

    $uniqueFileName = uniqid() . '-' . time() . '-' . preg_replace("/[^a-zA-Z0-9\.]/", "", basename($imageName));
    $targetFile = $targetDir . $uniqueFileName;

    $allowedTypes = ['jpg','jpeg','png','gif'];
    $fileExt = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // ✅ Handle "Unsorted" Album creation/selection if no album chosen
    if ($album_id == 0) {
        $checkDefault = mysqli_query($conn, "SELECT id FROM albums WHERE name='Unsorted' LIMIT 1");
        if (mysqli_num_rows($checkDefault) > 0) {
            $defaultAlbum = mysqli_fetch_assoc($checkDefault);
            $album_id = $defaultAlbum['id'];
        } else {
            mysqli_query($conn, "INSERT INTO albums (name, description) VALUES ('Unsorted', 'Default album for uncategorized images')");
            $album_id = mysqli_insert_id($conn);
        }
    }

    // ✅ File validation and upload/DB insertion (CONSOLIDATED)
    if (in_array($fileExt, $allowedTypes)) {
        if (move_uploaded_file($imageTmp, $targetFile)) {
            $uploaded_by = mysqli_real_escape_string($conn, $username);
            $sql = "INSERT INTO gallery (album_id, title, image, uploaded_by, created_at) 
                    VALUES ('$album_id', '$title', '$uniqueFileName', '$uploaded_by', NOW())";
            if (mysqli_query($conn, $sql)) {
                $success = $lang['image_uploaded_success'] ?? "✅ Image uploaded successfully!";
            } else {
                // DB insert failed, delete the uploaded file to clean up
                @unlink($targetFile);
                $error = $lang['db_error'] . ": " . mysqli_error($conn);
            }
        } else {
            $error = $lang['file_upload_failed'] ?? "❌ Error uploading file. Check permissions on $targetDir.";
        }
    } else {
        $error = $lang['allowed_types'] ?? "❌ Only JPG, JPEG, PNG, GIF allowed.";
    }

    after_upload_check:
}

// ✅ Fetch Albums for dropdown
$albums_result = mysqli_query($conn, "SELECT * FROM albums ORDER BY created_at DESC");
$albums = [];
while ($row = mysqli_fetch_assoc($albums_result)) {
    $albums[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['add_image'] ?? "Add Image" ?> - <?= $lang['logo'] ?? "Salakpur KhanePani" ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css"> <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>

    <style>
        <?php include '../css/dark-mode.css'; ?>
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

        .main-content h2 {
            font-size: 28px;
            font-weight: 900; /* Made title bolder */
            margin-bottom: 5px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        .main-content h2 svg {
            margin-right: 10px;
        }

        .main-content .subtitle {
            font-size: 16px;
            color: var(--secondary-color);
            margin-bottom: 30px;
        }

        /* --- Alerts/Messages --- */
        .alert-success {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        .alert-error {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        /* --- Form Containers (Cards) --- */
        .form-section {
            margin-bottom: 30px;
        }

        .form-card {
            background: var(--card-background);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-light);
            transition: box-shadow 0.3s ease;
        }
        .form-card:hover {
            box-shadow: var(--shadow-hover);
        }

        .form-card h3 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .form-card h3 svg {
            margin-right: 10px;
            width: 24px;
            height: 24px;
        }

        /* --- Separator between forms --- */
        .separator {
            border: none;
            height: 1px;
            background: var(--border-color);
            margin: 30px 0;
        }

        /* --- Form Elements --- */
        .gallery-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .input-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--secondary-color);
        }

        .input-group input[type="text"],
        .input-group select,
        .input-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .input-group input:focus,
        .input-group select:focus,
        .input-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            outline: none;
        }

        .input-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .input-group input[type="file"] {
            padding: 10px 0;
            border: none;
        }

        /* --- Buttons --- */
        .btn-submit {
            padding: 12px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .btn-submit:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* --- Image Preview --- */
        #previewContainer {
            margin-top: 20px;
            border-top: 1px dashed var(--border-color);
            padding-top: 20px;
        }
        #imagePreview {
            max-width: 100%;
            max-height: 250px;
            width: auto;
            height: auto;
            object-fit: contain;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        /* --- Responsive Design --- */
        @media (max-width: 600px) {
            .main-content {
                padding: 15px;
            }
            .form-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="main-content">

    <a href="manage_gallery.php" class="back-btn">
        <i data-feather="arrow-left"></i>
        <?= $lang['back'] ?? 'Back to Gallery' ?>
    </a>

    <h2><i data-feather="image"></i> <?= $lang['add_image'] ?? "Add Image" ?></h2>
    <p class="subtitle"><?= $lang['gallery_subtitle'] ?? "Create albums and upload photos for different occasions." ?></p>

    <?php if (isset($success)) echo "<p class='alert-success'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p class='alert-error'>$error</p>"; ?>

    <section class="form-section">
        <div class="form-card">
            <h3><i data-feather="folder-plus"></i> <?= $lang['create_album'] ?? "Create New Album" ?></h3>
            <form method="POST" class="gallery-form">
                <div class="input-group">
                    <label><?= $lang['album_name'] ?? "Album Name" ?></label>
                    <input type="text" name="album_name" placeholder="<?= $lang['album_name_placeholder'] ?? "Enter album name" ?>" required>
                </div>
                <div class="input-group">
                    <label><?= $lang['album_description'] ?? "Description (optional)" ?></label>
                    <textarea name="album_description" placeholder="<?= $lang['album_description_placeholder'] ?? "Album description" ?>"></textarea>
                </div>
                <button type="submit" name="create_album" class="btn-submit">
                    <i data-feather="plus-circle"></i> <?= $lang['create_album_btn'] ?? "Create Album" ?>
                </button>
            </form>
        </div>
    </section>

    <hr class="separator">

    <section class="form-section">
        <div class="form-card">
            <h3><i data-feather="upload-cloud"></i> <?= $lang['upload_new_image'] ?? "Add New Image to Album" ?></h3>
            <form method="POST" enctype="multipart/form-data" class="gallery-form">
                <div class="input-group">
                    <label><?= $lang['select_album'] ?? "Select Album" ?></label>
                    <select name="album_id">
                        <option value="0">-- <?= $lang['no_album'] ?? "No Album (Save in Unsorted)" ?> --</option>
                        <?php foreach($albums as $row) { ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="input-group">
                    <label><?= $lang['image_title_optional'] ?? "Image Title (optional)" ?></label>
                    <input type="text" name="title" placeholder="<?= $lang['image_title_placeholder'] ?? "Enter image title" ?>">
                </div>

                <div class="input-group">
                    <label><?= $lang['select_image'] ?? "Select Image" ?></label>
                    <input type="file" name="image" id="imageInput" accept="image/*" required>
                </div>

                <div class="input-group" id="previewContainer" style="display:none;">
                    <label><?= $lang['preview'] ?? "Preview" ?>:</label>
                    <img id="imagePreview" src="" alt="<?= $lang['preview'] ?? "Preview" ?>">
                </div>

                <button type="submit" class="btn-submit" name="upload_image">
                    <i data-feather="upload"></i> <?= $lang['upload_image_btn'] ?? "Upload" ?>
                </button>
            </form>
        </div>
    </section>
</main>

<script>
    // Initialize Feather Icons
    feather.replace();

    // Live Image Preview
    const imageInput = document.getElementById('imageInput');
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');

    imageInput.addEventListener('change', () => {
        const file = imageInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => {
                imagePreview.src = e.target.result;
                previewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = 'none';
        }
    });
</script>

</body>
</html>
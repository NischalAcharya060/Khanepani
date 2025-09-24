<?php
session_start();
include '../config/db.php';

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
        $success = "✅ Album created successfully!";
    } else {
        $error = "❌ Database error (Album): " . mysqli_error($conn);
    }
}

// ✅ Handle Image Upload
if (isset($_POST['upload_image'])) {
    $album_id = intval($_POST['album_id']); // chosen album
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $imageName = $_FILES['image']['name'];
    $imageTmp = $_FILES['image']['tmp_name'];
    $targetDir = "../assets/uploads/";
    $targetFile = $targetDir . basename($imageName);

    $allowedTypes = ['jpg','jpeg','png','gif'];
    $fileExt = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // ✅ If no album selected, use/create "Unsorted"
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

    if (in_array($fileExt, $allowedTypes)) {
        if (move_uploaded_file($imageTmp, $targetFile)) {
            $sql = "INSERT INTO gallery (album_id, title, image, created_at) 
                    VALUES ('$album_id', '$title', '$imageName', NOW())";
            if (mysqli_query($conn, $sql)) {
                $success = "✅ Image uploaded successfully!";
            } else {
                $error = "❌ Database error: " . mysqli_error($conn);
            }
        } else {
            $error = "❌ Error uploading file.";
        }
    } else {
        $error = "❌ Only JPG, JPEG, PNG, GIF allowed.";
    }
}

// ✅ Fetch Albums for dropdown
$albums = mysqli_query($conn, "SELECT * FROM albums ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Gallery Image - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<!-- Main Content -->
<main class="main-content">
    <h2>➕ Add Image</h2>
    <p class="subtitle">Create albums and upload photos for different occasions.</p>

    <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <!-- Album Create Form -->
    <section>
        <h3>📂 Create New Album</h3>
        <form method="POST" class="gallery-form">
            <div class="input-group">
                <label>Album Name</label>
                <input type="text" name="album_name" placeholder="Enter album name" required>
            </div>
            <div class="input-group">
                <label>Description (optional)</label>
                <textarea name="album_description" placeholder="Album description"></textarea>
            </div>
            <button type="submit" name="create_album" class="btn-submit">➕ Create Album</button>
        </form>
    </section>

    <hr>

    <!-- Image Upload Form -->
    <section>
        <h3>🖼 Add New Image to Album</h3>
        <form method="POST" enctype="multipart/form-data" class="gallery-form">
            <div class="input-group">
                <label>Select Album</label>
                <select name="album_id">
                    <option value="0">-- No Album (Save in Unsorted) --</option>
                    <?php while($row = mysqli_fetch_assoc($albums)) { ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="input-group">
                <label>Image Title (optional)</label>
                <input type="text" name="title" placeholder="Enter image title">
            </div>

            <div class="input-group">
                <label>Select Image</label>
                <input type="file" name="image" id="imageInput" accept="image/*" required>
            </div>

            <!-- Image Preview -->
            <div class="input-group" id="previewContainer" style="display:none;">
                <label>Preview:</label>
                <img id="imagePreview" src="" alt="Image Preview" style="max-width:100%; border-radius:8px; margin-top:10px;">
            </div>

            <button type="submit" class="btn-submit" name="upload_image">📤 Upload</button>
        </form>
    </section>
</main>

<script>
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

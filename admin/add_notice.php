<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Include language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
include "../lang/" . $_SESSION['lang'] . ".php";
include '../config/db.php';

$username = $_SESSION['username'];

// Handle form submission
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $uploaded_files = [];

    // Handle multiple file uploads
    if (isset($_FILES['file']) && count($_FILES['file']['name']) > 0) {
        $upload_dir = '../assets/uploads/';
        $allowed_types = [
                'image/jpeg','image/png','image/gif',
                'application/pdf','application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        foreach ($_FILES['file']['name'] as $index => $filename) {
            if ($_FILES['file']['error'][$index] === 0) {
                $file_type = $_FILES['file']['type'][$index];
                if (!in_array($file_type, $allowed_types)) {
                    $error = $lang['invalid_file_type'] ?? "Invalid file type: $filename";
                    break;
                }

                $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
                $file_name_clean = preg_replace("/[^a-zA-Z0-9-.]/", "_", basename($filename));
                $file_name = time() . '_' . uniqid() . '_' . $file_name_clean;
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['file']['tmp_name'][$index], $target_file)) {
                    $uploaded_files[] = $file_name;
                } else {
                    $error = $lang['file_upload_failed'] ?? "Failed to upload $filename.";
                    break;
                }
            }
        }
    }

    if (!$error && $title) {
        $file_paths = !empty($uploaded_files) ? json_encode($uploaded_files) : null;

        $stmt = $conn->prepare("INSERT INTO notices (title, content, file, created_at, created_by) VALUES (?, ?, ?, NOW(), ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $title, $content, $file_paths, $username);
            if ($stmt->execute()) {
                header("Location: manage_notices.php");
                exit();
            } else {
                $error = $lang['database_insert_error'] ?? "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = $lang['database_prepare_error'] ?? "Database error (prepare): " . $conn->error;
        }
    } elseif (!$error) {
        $error = $lang['fill_required'] ?? "Please fill in all required fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['add_notice'] ?? 'Add Notice' ?> - Admin</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        /* --- keep your existing CSS --- */
        :root {
            --primary-color: #007bff;
            --primary-hover: #0056b3;
            --secondary-color: #6c757d;
            --secondary-hover: #5a6268;
            --text-color-dark: #212529;
            --text-color-light: #6c757d;
            --bg-light: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #dee2e6;
            --shadow-subtle: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
            --success-color: #28a745;
            --error-color: #dc3545;
        }
        body { background-color: var(--bg-light); }
        .main-content { padding: 40px 30px; max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .title-group h2 { font-size: 28px; color: var(--text-color-dark); margin: 0; font-weight: 700; }
        .back-btn { display: inline-flex; align-items: center; padding: 8px 15px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color-light); font-weight: 500; transition: all 0.2s ease; }
        .back-btn:hover { border-color: var(--secondary-color); color: var(--secondary-color); box-shadow: var(--shadow-subtle); }
        .back-btn i { width: 20px; height: 20px; margin-right: 8px; }
        .notice-form { background: var(--card-bg); padding: 30px 40px; border-radius: 12px; box-shadow: var(--shadow-subtle); border: 1px solid var(--border-color); }
        .notice-form label { display: block; margin-top: 15px; margin-bottom: 8px; font-weight: 600; color: var(--text-color-dark); font-size: 15px; }
        .notice-form input[type="text"], .notice-form textarea { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); font-size: 16px; color: var(--text-color-dark); background-color: var(--bg-light); transition: border-color 0.3s, box-shadow 0.3s; resize: vertical; }
        .notice-form input:focus, .notice-form textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25); background-color: var(--card-bg); outline: none; }
        .file-input-group { margin-top: 15px; border: 1px dashed var(--border-color); border-radius: 6px; padding: 15px; text-align: center; cursor: pointer; transition: border-color 0.2s, background-color 0.2s; }
        .file-input-group:hover { border-color: var(--primary-color); background-color: rgba(0, 123, 255, 0.05); }
        .file-input-group input[type="file"] { opacity: 0; width: 0.1px; height: 0.1px; position: absolute; z-index: -1; }
        .file-input-group label { display: flex; align-items: center; justify-content: center; margin: 0; cursor: pointer; color: var(--text-color-light); font-weight: 500; gap: 10px; }
        .button-group { margin-top: 30px; display: flex; gap: 15px; }
        .btn { flex: 1; padding: 12px 20px; font-size: 16px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; transition: background 0.3s, transform 0.2s, box-shadow 0.2s; text-align: center; text-decoration: none; }
        .btn-primary { background: var(--primary-color); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0, 123, 255, 0.2); }
        .btn-secondary { background: var(--secondary-color); color: #fff; }
        .btn-secondary:hover { background: var(--secondary-hover); transform: translateY(-1px); box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2); }
        .message { padding: 15px 20px; border-radius: 8px; font-size: 15px; font-weight: 500; display: flex; align-items: center; margin-bottom: 0; }
        .message i { margin-right: 10px; width: 20px; height: 20px; }
        .error { background-color: #f8d7da; color: var(--error-color); border: 1px solid #f5c6cb; }
        @media (max-width: 600px) { .main-content { padding: 20px 15px; } .notice-form { padding: 20px; } .button-group { flex-direction: column; } .page-header { flex-direction: column; align-items: flex-start; gap: 15px; } }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div class="title-group">
            <h2>➕ <?= $lang['add_notice'] ?? 'Add New Notice' ?></h2>
        </div>

        <a href="manage_notices.php" class="back-btn">
            <i data-feather="arrow-left"></i>
            <?= $lang['back'] ?? 'Back to Notices' ?>
        </a>
    </div>

    <?php if(isset($error)): ?>
        <div class='message error'><i data-feather="alert-triangle"></i><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="notice-form" enctype="multipart/form-data">
        <label for="title"><?= $lang['notice_title'] ?? 'Notice Title' ?> <span style="color:red">*</span></label>
        <input type="text" name="title" id="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">

        <label for="content"><?= $lang['notice_description'] ?? 'Notice Description' ?></label>
        <textarea name="content" id="content" rows="8"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>

        <label><?= $lang['notice_file_optional'] ?? 'Upload Images or Files (optional)' ?>:</label>
        <div class="file-input-group">
            <input type="file" name="file[]" id="file" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx" onchange="updateFileName(this)">
            <label for="file">
                <i data-feather="upload-cloud"></i>
                <span id="file-name-display"><?= $lang['click_to_upload'] ?? 'Click here to upload files' ?></span>
            </label>
        </div>

        <div class="button-group">
            <button type="submit" class="btn btn-primary">
                <i data-feather="save" style="width:18px; height:18px; margin-right:5px; vertical-align:middle;"></i>
                <?= $lang['add_notice'] ?? 'Publish Notice' ?>
            </button>
            <a href="manage_notices.php" class="btn btn-secondary">
                <?= $lang['cancel'] ?? 'Cancel' ?>
            </a>
        </div>
    </form>
</main>

<script>
    feather.replace();
    function updateFileName(input) {
        const fileNameDisplay = document.getElementById('file-name-display');
        if (input.files && input.files.length > 0) {
            let names = [];
            for (let i = 0; i < input.files.length; i++) {
                names.push(input.files[i].name);
            }
            fileNameDisplay.textContent = names.join(", ");
            fileNameDisplay.style.color = 'var(--text-color-dark)';
        } else {
            fileNameDisplay.textContent = '<?= $lang['click_to_upload'] ?? 'Click here to upload files' ?>';
            fileNameDisplay.style.color = 'var(--text-color-light)';
        }
    }
</script>

</body>
</html>
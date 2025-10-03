<?php
session_start();
include '../config/db.php';

// Redirect if not logged in
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

$username = $_SESSION['username'];

// Get notice ID
if (!isset($_GET['id'])) {
    header("Location: manage_notices.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch notice
$stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$notice = $result->fetch_assoc();
$stmt->close();

if (!$notice) {
    header("Location: manage_notices.php");
    exit();
}

// DECODE existing files from the 'file' JSON string
$existing_files = $notice['file'] ? json_decode($notice['file'], true) : [];
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    $upload_dir = '../assets/uploads/';
    $allowed_types = [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    // 1. Handle file deletions (from existing_files)
    $files_to_keep = [];
    $new_file_list = [];

    // Check which existing files were NOT marked for removal
    if (is_array($existing_files)) {
        foreach ($existing_files as $file_name) {
            // If the corresponding 'delete_file_NAME' checkbox is NOT set, keep the file
            if (!isset($_POST['delete_file_' . basename($file_name)]) || $_POST['delete_file_' . basename($file_name)] != '1') {
                $files_to_keep[] = $file_name;
            } else {
                // File is marked for deletion, physically remove it
                if (file_exists($upload_dir . $file_name)) {
                    unlink($upload_dir . $file_name);
                }
            }
        }
    }
    $new_file_list = $files_to_keep; // Start the new list with files we kept

    // 2. Handle new multiple file uploads
    if (isset($_FILES['file']) && count($_FILES['file']['name']) > 0) {
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        foreach ($_FILES['file']['name'] as $index => $filename) {
            if ($_FILES['file']['error'][$index] === 0) {
                $file_type = $_FILES['file']['type'][$index];
                if (!in_array($file_type, $allowed_types)) {
                    $error = $lang['invalid_file_type'] ?? "Invalid file type: $filename";
                    break;
                }

                $file_name_clean = preg_replace("/[^a-zA-Z0-9-.]/", "_", basename($filename));
                $file_name = time() . '_' . uniqid() . '_' . $file_name_clean; // Added uniqid() for better uniqueness
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['file']['tmp_name'][$index], $target_file)) {
                    $new_file_list[] = $file_name; // Add new file to the list
                } else {
                    $error = $lang['file_upload_failed'] ?? "Failed to upload $filename.";
                    break;
                }
            }
        }
    }

    if (!$error && $title && $content) {
        // Encode the final, merged file list back into JSON
        $final_file_path = !empty($new_file_list) ? json_encode($new_file_list) : null;

        $stmt = $conn->prepare("UPDATE notices SET title = ?, content = ?, file = ? WHERE id = ?");
        $stmt->bind_param("sssi", $title, $content, $final_file_path, $id);
        $stmt->execute();

        $_SESSION['success'] = $lang['notice_updated'] ?? "Notice Updated Successfully";
        header("Location: manage_notices.php");
        exit();
    } elseif (!$error) {
        $error = $lang['fill_required'] ?? "Please fill in all required fields!";
    }

    // If an error occurred during upload, reload notice data to refresh the form
    if ($error) {
        $stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notice = $result->fetch_assoc();
        $stmt->close();
        $existing_files = $notice['file'] ? json_decode($notice['file'], true) : [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['edit_notice'] ?? 'Edit Notice' ?> - Admin</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        /* --- Styles (Unchanged for consistency) --- */
        :root {
            --primary-color: #10b981;
            --primary-hover: #059669;
            --secondary-color: #6c757d;
            --secondary-hover: #5a6268;
            --danger-color: #ef4444;
            --text-color-dark: #1f2937;
            --text-color-light: #6b7280;
            --bg-light: #f9fafb;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
            --shadow-subtle: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
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
        .notice-form input:focus, .notice-form textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25); outline: none; }

        /* --- Current File Section (Updated for Multi-File Display) --- */
        .current-file-group {
            border: 1px solid var(--border-color);
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .current-file-group h4 {
            margin: 0 0 5px 0;
            color: var(--text-color-dark);
            font-size: 16px;
        }
        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            border-top: 1px solid var(--border-color);
            font-size: 15px;
        }
        .file-item:first-child { border-top: none; }
        .file-info {
            display: flex;
            align-items: center;
            flex-grow: 1;
        }
        .file-info i {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            color: var(--text-color-light);
        }
        .file-info a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.2s;
            max-width: 80%; /* Prevent link from pushing checkbox */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .file-info a:hover { text-decoration: underline; }

        .remove-file-check {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: var(--danger-color);
            cursor: pointer;
        }
        .remove-file-check input {
            margin-right: 5px;
            width: 16px;
            height: 16px;
            cursor: pointer;
            min-width: 16px;
        }

        /* --- File Input Enhancement --- */
        .file-input-group { margin-top: 15px; border: 1px dashed var(--border-color); border-radius: 6px; padding: 15px; text-align: center; cursor: pointer; transition: border-color 0.2s, background-color 0.2s; }
        .file-input-group:hover { border-color: var(--primary-color); background-color: rgba(16, 185, 129, 0.05); }
        .file-input-group input[type="file"] { opacity: 0; width: 0.1px; height: 0.1px; position: absolute; z-index: -1; }
        .file-input-group label { display: flex; align-items: center; justify-content: center; margin: 0; cursor: pointer; color: var(--text-color-light); font-weight: 500; gap: 10px; }

        /* --- Button Group (Unchanged for consistency) --- */
        .button-group { margin-top: 30px; display: flex; gap: 15px; }
        .btn { flex: 1; padding: 12px 20px; font-size: 16px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; transition: background 0.3s, transform 0.2s, box-shadow 0.2s; text-align: center; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-primary { background: var(--primary-color); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); }
        .btn-secondary { background: var(--secondary-color); color: #fff; }
        .btn-secondary:hover { background: var(--secondary-hover); transform: translateY(-1px); }
        .message { padding: 15px 20px; border-radius: 8px; font-size: 15px; font-weight: 500; display: flex; align-items: center; margin-bottom: 0; }
        .message i { margin-right: 10px; width: 20px; height: 20px; }
        .error { background-color: #f8d7da; color: var(--error-color); border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div class="title-group">
            <h2>✏ <?= $lang['edit_notice'] ?? 'Edit Notice' ?></h2>
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
        <label for="title"><?= $lang['notice_title'] ?? 'Notice Title' ?> <span style="color:var(--error-color)">*</span></label>
        <input type="text" name="title" id="title" value="<?= htmlspecialchars($notice['title']) ?>" required>

        <label for="content"><?= $lang['notice_description'] ?? 'Notice Description' ?> <span style="color:var(--error-color)">*</span></label>
        <textarea name="content" id="content" rows="8" required><?= htmlspecialchars($notice['content']) ?></textarea>

        <?php if (!empty($existing_files) && is_array($existing_files)): ?>
            <div class="current-file-group">
                <h4><i data-feather="archive" style="width:18px; height:18px; margin-right:5px; color:var(--text-color-dark);"></i> <?= $lang['current_files'] ?? 'Current Attached Files' ?> (<?= count($existing_files) ?>)</h4>
                <?php foreach($existing_files as $file_name): ?>
                    <div class="file-item">
                        <div class="file-info">
                            <i data-feather="file-text"></i>
                            <a href="../assets/uploads/<?= htmlspecialchars($file_name) ?>" target="_blank" title="<?= htmlspecialchars($file_name) ?>">
                                <?= htmlspecialchars(basename($file_name)) ?>
                            </a>
                        </div>
                        <label class="remove-file-check" for="delete_file_<?= basename($file_name) ?>">
                            <input type="checkbox" name="delete_file_<?= basename($file_name) ?>" id="delete_file_<?= basename($file_name) ?>" value="1">
                            <?= $lang['delete'] ?? 'Delete' ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <label><?= $lang['add_or_replace_files'] ?? 'Add or Replace Files/Images (optional)' ?>:</label>
        <div class="file-input-group">
            <input type="file" name="file[]" id="file" multiple
                   accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx" onchange="updateFileName(this)">
            <label for="file">
                <i data-feather="upload-cloud"></i>
                <span id="file-name-display"><?= $lang['click_to_upload'] ?? 'Click here to upload new file(s)' ?></span>
            </label>
        </div>

        <div class="button-group">
            <button type="submit" class="btn btn-primary">
                <i data-feather="refresh-cw" style="width:18px; height:18px; margin-right:5px; vertical-align:middle;"></i>
                <?= $lang['update'] ?? 'Update' ?> <?= $lang['notice'] ?? 'Notice' ?>
            </button>
            <a href="manage_notices.php" class="btn btn-secondary">
                <?= $lang['cancel'] ?? 'Cancel' ?>
            </a>
        </div>
    </form>
</main>

<script>
    // Initialize Feather Icons
    feather.replace();

    /**
     * Updates the file name display when a file is selected.
     * Shows the count if multiple files are selected.
     * @param {HTMLInputElement} input - The file input element.
     */
    function updateFileName(input) {
        const fileNameDisplay = document.getElementById('file-name-display');
        const fileCount = input.files ? input.files.length : 0;

        if (fileCount > 0) {
            if (fileCount === 1) {
                fileNameDisplay.textContent = input.files[0].name;
            } else {
                fileNameDisplay.textContent = `${fileCount} files selected`;
            }
            fileNameDisplay.style.color = 'var(--text-color-dark)';
        } else {
            // Restore default text if file is deselected
            fileNameDisplay.textContent = '<?= $lang['click_to_upload'] ?? 'Click here to upload new file(s)' ?>';
            fileNameDisplay.style.color = 'var(--text-color-light)';
        }
    }
</script>

</body>
</html>
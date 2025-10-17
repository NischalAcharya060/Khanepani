<?php
session_start();
include '../config/database/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
include "../lang/" . $_SESSION['lang'] . ".php";

$username = $_SESSION['username'];
$current_lang = $_SESSION['lang'] ?? 'en';

if (!isset($_GET['id'])) {
    header("Location: manage_notices.php");
    exit();
}

$id = intval($_GET['id']);

// Define the notice types using language strings
$notice_type_options = [
        'General'       => $lang['type_general'] ?? 'General Notice',
        'Operational'   => $lang['type_operational'] ?? 'Operational Update',
        'Maintenance'   => $lang['type_maintenance'] ?? 'Maintenance Schedule',
        'Financial'     => $lang['type_financial'] ?? 'Financial Report',
];

// Fetch existing notice data
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

$existing_files = $notice['file'] ? json_decode($notice['file'], true) : [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $type = trim($_POST['type'] ?? 'General'); // Capture the selected type

    $upload_dir = '../assets/uploads/';
    $allowed_types = [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    $files_to_keep = [];
    $new_file_list = [];

    // 1. Handle file deletions
    if (is_array($existing_files)) {
        foreach ($existing_files as $file_name) {
            if (!in_array($file_name, $_POST['delete_files'] ?? [])) {
                $files_to_keep[] = $file_name;
            } else {
                $file_path = $upload_dir . $file_name;
                if (file_exists($file_path)) unlink($file_path);
            }
        }
    }
    $new_file_list = $files_to_keep;

    // 2. Handle new file uploads
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
                $file_name = time() . '_' . uniqid() . '_' . $file_name_clean;
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['file']['tmp_name'][$index], $target_file)) {
                    $new_file_list[] = $file_name;
                } else {
                    $error = $lang['file_upload_failed'] ?? "Failed to upload $filename.";
                    break;
                }
            }
        }
    }

    if (!$error && $title && $content) {
        $final_file_path = !empty($new_file_list) ? json_encode($new_file_list) : null;

        // UPDATED: Added `type` column to the UPDATE query
        $stmt = $conn->prepare("UPDATE notices SET title = ?, content = ?, type = ?, file = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $title, $content, $type, $final_file_path, $id);

        if ($stmt->execute()) {
            $_SESSION['success'] = $lang['notice_updated'] ?? "Notice Updated Successfully";
            header("Location: manage_notices.php");
            exit();
        } else {
            $error = $lang['database_update_error'] ?? "Database error: " . $stmt->error;
        }
    } elseif (!$error) {
        $error = $lang['fill_required'] ?? "Please fill in all required fields!";
    }

    // Re-fetch data on error to display the last successful state + any new files kept
    if ($error) {
        $stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notice = $result->fetch_assoc();
        $stmt->close();
        $existing_files = $notice['file'] ? json_decode($notice['file'], true) : [];
        // Override notice content/title/type with POST values to keep user input visible
        $notice['title'] = $title;
        $notice['content'] = $content;
        $notice['type'] = $type;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['edit_notice'] ?? 'Edit Notice' ?> - Admin</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        <?php include '../css/dark-mode.css'; ?>
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .title-group h2 { font-size: 28px; color: var(--text-color-dark); margin: 0; font-weight: 700; }
        .back-btn { display: inline-flex; align-items: center; padding: 8px 15px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color-light); font-weight: 500; transition: all 0.2s ease; }
        .back-btn:hover { border-color: var(--secondary-color); color: var(--secondary-color); box-shadow: var(--shadow-subtle); }
        .back-btn i { width: 20px; height: 20px; margin-right: 8px; }
        .notice-form { background: var(--card-bg); padding: 30px 40px; border-radius: 12px; box-shadow: var(--shadow-subtle); border: 1px solid var(--border-color); }
        .notice-form label { display: block; margin-top: 15px; margin-bottom: 8px; font-weight: 600; color: var(--text-color-dark); font-size: 15px; }

        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-row > div {
            flex: 1;
        }

        .notice-form input[type="text"],
        .notice-form textarea,
        .notice-form select {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            font-size: 16px;
            color: var(--text-color-dark);
            background-color: var(--bg-light);
            transition: border-color 0.3s, box-shadow 0.3s;
            resize: vertical;
            box-sizing: border-box;
        }
        .notice-form input:focus, .notice-form textarea:focus, .notice-form select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
            outline: none;
        }
        .current-file-group { border: 1px solid var(--border-color); padding: 15px; border-radius: 6px; margin-top: 15px; display: flex; flex-direction: column; gap: 10px; }
        .current-file-group h4 { margin: 0 0 5px 0; color: var(--text-color-dark); font-size: 16px; }
        .file-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-top: 1px solid var(--border-color); font-size: 15px; }
        .file-item:first-child { border-top: none; }
        .file-info { display: flex; align-items: center; flex-grow: 1; }
        .file-info i { width: 18px; height: 18px; margin-right: 8px; color: var(--text-color-light); }
        .file-info a { color: var(--primary-color); text-decoration: none; transition: color 0.2s; max-width: 80%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .file-info a:hover { text-decoration: underline; }
        .remove-file-check { display: flex; align-items: center; font-size: 14px; color: var(--danger-color); cursor: pointer; }
        .remove-file-check input { margin-right: 5px; width: 16px; height: 16px; cursor: pointer; min-width: 16px; }
        .file-input-group { margin-top: 15px; border: 1px dashed var(--border-color); border-radius: 6px; padding: 15px; text-align: center; cursor: pointer; transition: border-color 0.2s, background-color 0.2s; }
        .file-input-group:hover { border-color: var(--primary-color); background-color: rgba(16, 185, 129, 0.05); }
        .file-input-group input[type="file"] { opacity: 0; width: 0.1px; height: 0.1px; position: absolute; z-index: -1; }
        .file-input-group label { display: flex; align-items: center; justify-content: center; margin: 0; cursor: pointer; color: var(--text-color-light); font-weight: 500; gap: 10px; }
        .button-group { margin-top: 30px; display: flex; gap: 15px; }
        .btn { flex: 1; padding: 12px 20px; font-size: 16px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; transition: background 0.3s, transform 0.2s, box-shadow 0.2s; text-align: center; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-primary { background: var(--primary-color); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); }
        .btn-secondary { background: var(--secondary-color); color: #fff; }
        .btn-secondary:hover { background: var(--secondary-hover); transform: translateY(-1px); }
        .message { padding: 15px 20px; border-radius: 8px; font-size: 15px; font-weight: 500; display: flex; align-items: center; margin-bottom: 0; }
        .message i { margin-right: 10px; width: 20px; height: 20px; }
        .error { background-color: #f8d7da; color: var(--error-color); border: 1px solid #f5c6cb; }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
        }
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

        <div class="form-row">
            <div class="form-group">
                <label for="title"><?= $lang['notice_title'] ?? 'Notice Title' ?> <span style="color:var(--error-color)">*</span></label>
                <input type="text" name="title" id="title" value="<?= htmlspecialchars($notice['title']) ?>" required>
            </div>

            <div class="form-group">
                <label for="type"><?= $lang['notice_type'] ?? 'Notice Type' ?> <span style="color:var(--error-color)">*</span></label>
                <select name="type" id="type" required>
                    <?php
                    $current_type = $notice['type'] ?? 'General';
                    foreach ($notice_type_options as $value => $label):
                        ?>
                        <option value="<?= $value ?>" <?= ($current_type === $value) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

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
                                <?= htmlspecialchars(substr($file_name, strpos($file_name, '_', strpos($file_name, '_') + 1) + 1)) ?>
                            </a>
                        </div>
                        <label class="remove-file-check">
                            <input type="checkbox" name="delete_files[]" value="<?= htmlspecialchars($file_name) ?>">
                            <?= $lang['delete'] ?? 'Delete' ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <label><?= $lang['add_or_replace_files'] ?? 'Add or Replace Files/Images (optional)' ?>:</label>
        <div class="file-input-group">
            <input type="file" name="file[]" id="file" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx" onchange="updateFileName(this)">
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
    feather.replace();

    // JS for language support in file input
    const currentLang = '<?= $current_lang ?>';

    const langStrings = {
        en: {
            upload_prompt: 'Click here to upload new file(s)',
            files_selected: 'files selected'
        },
        np: {
            upload_prompt: 'नयाँ फाइल(हरू) अपलोड गर्न यहाँ क्लिक गर्नुहोस्',
            files_selected: 'फाइलहरू चयन गरिए'
        }
    };

    function getLangString(key) {
        // Use 'upload_prompt' for the main message
        if (key === 'upload_prompt') {
            return langStrings[currentLang]['upload_prompt'] || langStrings['en']['upload_prompt'];
        }
        // Use 'files_selected' for the file count message
        if (key === 'files_selected') {
            return langStrings[currentLang]['files_selected'] || langStrings['en']['files_selected'];
        }
    }

    function updateFileName(input) {
        const fileNameDisplay = document.getElementById('file-name-display');
        const fileCount = input.files ? input.files.length : 0;

        if (fileCount > 0) {
            if (fileCount === 1) {
                fileNameDisplay.textContent = input.files[0].name;
            } else {
                fileNameDisplay.textContent = `${fileCount} ${getLangString('files_selected')}`;
            }
            fileNameDisplay.style.color = 'var(--text-color-dark)';
        } else {
            fileNameDisplay.textContent = getLangString('upload_prompt');
            fileNameDisplay.style.color = 'var(--text-color-light)';
        }
    }

    // Set initial display text
    document.addEventListener('DOMContentLoaded', function() {
        const fileNameDisplay = document.getElementById('file-name-display');
        fileNameDisplay.textContent = getLangString('upload_prompt');
    });
</script>
</body>
</html>
<?php
session_start();
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
include '../config/database/db.php';

$username = $_SESSION['username'];
$error = null;
$success = null;
$current_lang = $_SESSION['lang'] ?? 'en';

$notice_type_options = [
        'General'       => $lang['type_general'] ?? 'General Notice',
        'Operational'   => $lang['type_operational'] ?? 'Operational Update',
        'Maintenance'   => $lang['type_maintenance'] ?? 'Maintenance Schedule',
        'Financial'     => $lang['type_financial'] ?? 'Financial Report',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $type = trim($_POST['type'] ?? 'General');
    $uploaded_files = [];

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

        $stmt = $conn->prepare("INSERT INTO notices (title, content, type, file, created_at, created_by) VALUES (?, ?, ?, ?, NOW(), ?)");
        if ($stmt) {
            $stmt->bind_param("sssss", $title, $content, $type, $file_paths, $username);
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
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['add_notice'] ?? 'Add Notice' ?> - Admin</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        <?php include '../css/dark-mode.css'; ?>

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary-color);
        }
        .title-group h2 {
            font-size: 32px;
            color: var(--text-color-dark);
            margin: 0;
            font-weight: 700;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 18px;
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
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .back-btn i { width: 20px; height: 20px; margin-right: 8px; }

        .notice-form {
            background: var(--card-bg);
            padding: 35px 45px;
            border-radius: 12px;
            box-shadow: var(--shadow-subtle);
            border: 1px solid #e9ecef;
        }

        .form-group { margin-bottom: 20px; }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-row > div {
            flex: 1;
        }

        .notice-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color-dark);
            font-size: 16px;
        }
        .notice-form input[type="text"],
        .notice-form textarea,
        .notice-form select {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ced4da;
            font-size: 16px;
            color: var(--text-color-dark);
            background-color: var(--bg-light);
            transition: border-color 0.3s, box-shadow 0.3s;
            resize: vertical;
            box-sizing: border-box;
        }
        .notice-form input:focus,
        .notice-form textarea:focus,
        .notice-form select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
            background-color: var(--card-bg);
            outline: none;
        }

        .file-input-group {
            border: 2px dashed #b8c1c9;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s, background-color 0.3s;
            margin-top: 10px;
        }
        .file-input-group:hover {
            border-color: var(--primary-color);
            background-color: rgba(0, 123, 255, 0.05);
        }
        .file-input-group input[type="file"] {
            opacity: 0;
            width: 0.1px;
            height: 0.1px;
            position: absolute;
            z-index: -1;
        }
        .file-input-group label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0;
            cursor: pointer;
            color: var(--primary-color);
            font-weight: 600;
            gap: 10px;
        }
        #file-name-display {
            font-weight: 400;
            color: var(--text-color-light);
            margin-top: 5px;
        }

        .button-group {
            margin-top: 35px;
            display: flex;
            gap: 20px;
            justify-content: flex-end;
        }
        .btn {
            padding: 12px 25px;
            font-size: 17px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s, box-shadow 0.2s;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary { background: var(--primary-color); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 6px 15px rgba(0, 123, 255, 0.3); }
        .btn-secondary { background: #f1f3f5; color: var(--text-color-light); border: 1px solid #dee2e6; }
        .btn-secondary:hover { background: #e9ecef; color: var(--text-color-dark); transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }
        .message i { margin-right: 10px; width: 20px; height: 20px; }
        .error { background-color: #f8d7da; color: var(--error-color); border: 1px solid #f5c6cb; }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
        }
        @media (max-width: 600px) {
            .main-content { padding: 20px 15px; }
            .notice-form { padding: 25px 20px; }
            .button-group { flex-direction: column; gap: 10px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .title-group h2 { font-size: 28px; }
            .btn { width: 100%; justify-content: center; }
        }
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

        <div class="form-row">
            <div class="form-group">
                <label for="title"><?= $lang['notice_title'] ?? 'Notice Title' ?> <span style="color:red">*</span></label>
                <input type="text" name="title" id="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="type"><?= $lang['notice_type'] ?? 'Notice Type' ?> <span style="color:red">*</span></label>
                <select name="type" id="type" required>
                    <?php foreach ($notice_type_options as $value => $label): ?>
                        <option value="<?= $value ?>" <?= (($_POST['type'] ?? 'General') === $value) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="content"><?= $lang['notice_description'] ?? 'Notice Description' ?></label>
            <textarea name="content" id="content" rows="8"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label><?= $lang['notice_file_optional'] ?? 'Upload Images or Files (optional)' ?>:</label>
            <div class="file-input-group">
                <input type="file" name="file[]" id="file" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx" onchange="updateFileName(this)">
                <label for="file">
                    <i data-feather="upload-cloud" style="width:30px; height:30px;"></i>
                    <span style="font-size:18px;"><?= $lang['click_to_upload'] ?? 'Click here to upload files' ?></span>
                    <span id="file-name-display"></span>
                </label>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" class="btn btn-primary">
                <i data-feather="save"></i>
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

    const currentLang = '<?= $current_lang ?>';

    const langStrings = {
        en: {
            click_to_upload: 'Click here to upload files',
            files_selected: 'files selected'
        },
        np: {
            click_to_upload: 'फाइलहरू अपलोड गर्न यहाँ क्लिक गर्नुहोस्',
            files_selected: 'फाइलहरू चयन गरिए'
        }
    };

    function getLangString(key) {
        return langStrings[currentLang][key] || langStrings['en'][key];
    }

    function updateFileName(input) {
        const fileNameDisplay = document.getElementById('file-name-display');

        if (input.files && input.files.length > 0) {
            if (input.files.length === 1) {
                fileNameDisplay.textContent = input.files[0].name;
            } else {
                fileNameDisplay.textContent = `${input.files.length} ${getLangString('files_selected')}`;
            }
            fileNameDisplay.style.color = 'var(--text-color-dark)';
        } else {
            fileNameDisplay.textContent = '';
            document.querySelector('.file-input-group label span:first-of-type').textContent = getLangString('click_to_upload');
            fileNameDisplay.style.color = 'var(--text-color-light)';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('file-name-display').textContent = '';
        document.querySelector('.file-input-group label span:first-of-type').textContent = getLangString('click_to_upload');
    });
</script>

</body>
</html>
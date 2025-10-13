<?php
session_start();
include '../config/db.php';
include '../config/Nepali_Calendar.php';
$cal = new Nepali_Calendar();

function format_nepali_date($date_str, $cal) {
    if (!$date_str) return '—';
    $timestamp = strtotime($date_str);
    $year  = (int)date('Y', $timestamp);
    $month = (int)date('m', $timestamp);
    $day   = (int)date('d', $timestamp);
    $hour  = (int)date('h', $timestamp);
    $minute = (int)date('i', $timestamp);
    $ampm  = date('A', $timestamp);

    global $lang;

    if ( ($_SESSION['lang'] ?? 'en') === 'np' ) {
        $nepDate = $cal->eng_to_nep($year, $month, $day);
        $np_numbers = ['0'=>'०','1'=>'१','2'=>'२','3'=>'३','4'=>'४','5'=>'५','6'=>'६','7'=>'७','8'=>'८','9'=>'९'];

        $yearNep  = strtr($nepDate['year'], $np_numbers);
        $monthNep = strtr($nepDate['month'], $np_numbers);
        $dayNep   = strtr($nepDate['date'], $np_numbers);
        $hourNep  = strtr(sprintf("%02d", $hour), $np_numbers);
        $minNep   = strtr(sprintf("%02d", $minute), $np_numbers);

        $ampm_nep = ($ampm === 'AM' ? ($lang['am'] ?? 'पूर्वाह्न') : ($lang['pm'] ?? 'अपराह्न'));

        return $dayNep . '-' . $monthNep . '-' . $yearNep . ', ' . $hourNep . ':' . $minNep . ' ' . $ampm_nep;
    } else {
        return date("d M Y, h:i A", $timestamp);
    }
}

function nepali_date_time($date_str, $cal) {
    return format_nepali_date($date_str, $cal);
}


if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang'])) {
    $allowed_langs = ['en','np'];
    if (in_array($_GET['lang'], $allowed_langs)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
}
include '../lang/' . $_SESSION['lang'] . '.php';
$lang['am'] = $lang['am'] ?? 'AM';
$lang['pm'] = $lang['pm'] ?? 'PM';


if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("SELECT image FROM gallery WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $image_row = $result->fetch_assoc();
    $image_file = $image_row['image'] ?? null;
    $stmt->close();

    $safe_to_delete = $image_file && $image_file !== 'default.png';

    if ($safe_to_delete && file_exists('../assets/uploads/' . $image_file)) {
        unlink('../assets/uploads/' . $image_file);
    }

    $stmt = $conn->prepare("DELETE FROM gallery WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['msg'] = $lang['delete_success_image'] ?? "Image deleted successfully.";
    header("Location: manage_gallery.php");
    exit();
}

$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$sql_query = "
    SELECT 
        g.*, 
        a.name AS album_name  
    FROM 
        gallery g
    LEFT JOIN 
        albums a ON g.album_id = a.id
    ORDER BY 
        g.created_at DESC 
    LIMIT $offset, $limit
";

$images = $conn->query($sql_query);

$total_result = $conn->query("SELECT COUNT(*) as total FROM gallery");
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['manage_gallery'] ?? 'Manage Gallery' ?> - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        h2 {
            color: #1e3a8a;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .subtitle {
            color: #6c757d;
            margin-bottom: 25px;
            font-size: 1.05em;
        }

        .btn-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            font-size: 1em;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #1e3a8a;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            background-color: #1c50af;
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
        }

        .gallery-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            overflow: hidden;
        }

        .gallery-table th {
            padding: 18px 15px;
            text-align: left;
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: capitalize;
            font-size: 0.9em;
        }

        .gallery-table td {
            padding: 16px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
            font-size: 0.95em;
            color: #495057;
            background-color: #ffffff;
        }

        .gallery-table tbody tr:nth-child(even) {
            background-color: #fcfdff;
        }

        .gallery-table tbody tr:hover {
            background: #e6f3ff;
            box-shadow: inset 3px 0 0 0 #1e3a8a;
            cursor: default;
        }

        .gallery-table tr:last-child td {
            border-bottom: none;
        }

        .gallery-table td:nth-child(1), .gallery-table th:nth-child(1) { width: 5%; text-align: center; }
        .gallery-table td:nth-child(2), .gallery-table th:nth-child(2) { width: 10%; text-align: center; }
        .gallery-table td:nth-child(6), .gallery-table th:nth-child(6) { width: 15%; text-align: center; }

        .gallery-img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            cursor: zoom-in;
            vertical-align: middle;
        }
        .gallery-img:hover {
            transform: scale(1.05);
        }

        .action-group-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-edit, .btn-delete {
            padding: 8px 14px;
            font-size: 0.85em;
            border-radius: 6px;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
        }

        .btn-edit {
            background-color: #2563eb;
            color: white;
        }
        .btn-edit:hover {
            background-color: #1d4ed8;
        }

        .btn-delete {
            background-color: #ef4444;
            color: white;
        }
        .btn-delete:hover {
            background-color: #dc2626;
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }
        .message i {
            margin-right: 10px;
            width: 20px;
            height: 20px;
        }
        .success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 5px solid #059669;
        }
        .error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 5px solid #ef4444;
        }

        .pagination { text-align: center; margin-top: 25px; margin-bottom: 20px;}
        .pagination a { margin: 0 5px; text-decoration: none; padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px; color: #1e3a8a; font-weight: 600; transition: background-color 0.2s, color 0.2s; }
        .pagination a.active { background-color: #1e3a8a; color: white; border-color: #1e3a8a; }
        .pagination a:hover:not(.active) { background-color: #e0f2fe; color: #1e3a8a; }

    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <h2>📸 <?= $lang['manage_gallery'] ?? "Manage Gallery" ?></h2>
    <p class="subtitle"><?= $lang['subtitle_gallery'] ?? "View, edit, or delete uploaded images." ?></p>

    <div class="btn-container">
        <a href="gallery_add.php" class="btn">➕ <?= $lang['add_image'] ?? "Add New Image" ?></a>
    </div>

    <?php if(isset($_SESSION['msg'])): ?>
        <div class="message success">
            <i data-feather="check-circle"></i>
            <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
        </div>
    <?php endif; ?>

    <table class="gallery-table">
        <thead>
        <tr>
            <th><?= $lang['sn'] ?? "S.N." ?></th>
            <th><?= $lang['image'] ?? "Image" ?></th>
            <th><?= $lang['title'] ?? "Title" ?></th>
            <th><?= $lang['album'] ?? "Album" ?></th>
            <th><?= $lang['uploaded_at'] ?? "Uploaded At" ?></th>
            <th><?= $lang['uploaded_by'] ?? "Uploaded By" ?></th>
            <th><?= $lang['actions'] ?? "Actions" ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        if ($images && $images->num_rows > 0):
            ?>
            <?php $sn = $offset + 1; ?>
            <?php
            while ($image = $images->fetch_assoc()):
                $uploadedPath = "../assets/uploads/" . htmlspecialchars($image['image']);
                $imageSrc = file_exists($uploadedPath) ? $uploadedPath : "../assets/images/placeholder.png";
                ?>
                <tr>
                    <td><?= $sn++ ?></td>
                    <td style="text-align: center;"><img src="<?= $imageSrc ?>" class="gallery-img" alt="<?= htmlspecialchars($image['title'] ?? 'Image') ?>"></td>
                    <td><?= $image['title'] ? htmlspecialchars($image['title']) : '<em>' . ($lang['no_title'] ?? "No Title") . '</em>' ?></td>
                    <td><?= $image['album_name'] ? htmlspecialchars($image['album_name']) : '<em>' . ($lang['uncategorized'] ?? "Uncategorized") . '</em>' ?></td>
                    <td><?= nepali_date_time($image['created_at'], $cal) ?></td>
                    <td><?= $image['uploaded_by'] ? htmlspecialchars($image['uploaded_by']) : 'N.A' ?></td>
                    <td>
                        <div class="action-group-buttons">
                            <a href="gallery_edit.php?id=<?= $image['id'] ?>" class="btn btn-edit">✏ <?= $lang['edit'] ?? "Edit" ?></a>
                            <a href="manage_gallery.php?delete=<?= $image['id'] ?>" class="btn btn-delete"
                               onclick="return confirm('<?= $lang['delete_confirm_image'] ?? "Are you sure you want to delete this image?" ?>')">🗑 <?= $lang['delete'] ?? "Delete" ?></a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:20px; font-style: italic; color: #a0a0a0;"><?= $lang['no_images'] ?? 'No images found in the gallery.' ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php if($page > 1): ?>
            <a href="?page=<?= $page-1 ?>"><?= $lang['previous'] ?? '« Previous' ?></a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        for($p = $start; $p <= $end && $total_pages > 0; $p++): ?>
            <a href="?page=<?= $p ?>" class="<?= ($p == $page) ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>

        <?php if($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?>"><?= $lang['next'] ?? 'Next »' ?></a>
        <?php endif; ?>
    </div>
</main>

<script>
    feather.replace();
</script>

</body>
</html>
<?php
session_start();
include '../config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Language handling
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // default
}
if (isset($_GET['lang'])) {
    $allowed_langs = ['en','np'];
    if (in_array($_GET['lang'], $allowed_langs)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
}
include '../lang/' . $_SESSION['lang'] . '.php';

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_notices.php");
    exit();
}

// Pagination settings
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Fetch notices with limit
$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT $offset, $limit");

// Get total notices for pagination
$total_result = $conn->query("SELECT COUNT(*) as total FROM notices");
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['manage_notices'] ?> - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        .notice-table th, .notice-table td { padding: 12px 8px; }
        .notice-table tr:hover { background: #f1f1f1; }
        .pagination { text-align: center; margin-top: 20px; }
        .pagination a { margin: 0 5px; text-decoration: none; padding: 6px 12px; border: 1px solid #ddd; border-radius: 4px; color: #0056d6; }
        .pagination a.active { background-color: #0056d6; color: white; border-color: #0056d6; }
        .pagination a:hover { background-color: #0056d6; color: white; }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <h2>📢 <?= $lang['manage_notices'] ?></h2>
    <p class="subtitle"><?= $lang['manage_notices_subtitle'] ?? 'Add, edit, view, or remove notices quickly and efficiently.' ?></p>

    <a href="add_notice.php" class="btn">➕ <?= $lang['add_new_notice'] ?? 'Add New Notice' ?></a>

    <table class="notice-table">
        <thead>
        <tr>
            <th><?= $lang['sn'] ?? 'S.N.' ?></th>
            <th><?= $lang['title'] ?? 'Title' ?></th>
            <th><?= $lang['date'] ?? 'Date' ?></th>
            <th><?= $lang['actions'] ?? 'Actions' ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if ($notices->num_rows > 0): ?>
            <?php $sn = $offset + 1; ?>
            <?php while ($notice = $notices->fetch_assoc()): ?>
                <tr>
                    <td><?= $sn++ ?></td>
                    <td><?= htmlspecialchars($notice['title']) ?></td>
                    <td><?= date("d M Y", strtotime($notice['created_at'])) ?></td>
                    <td>
                        <a href="edit_notice.php?id=<?= $notice['id'] ?>" class="btn small">✏ <?= $lang['edit'] ?? 'Edit' ?></a>
                        <a href="manage_notices.php?delete=<?= $notice['id'] ?>" class="btn small danger" onclick="return confirm('<?= $lang['delete_confirm'] ?? 'Are you sure you want to delete this notice?' ?>');">🗑 <?= $lang['delete'] ?? 'Delete' ?></a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" style="text-align:center; padding:20px;"><?= $lang['no_notices'] ?? 'No notices found.' ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        <?php if($page > 1): ?>
            <a href="?page=<?= $page-1 ?>"><?= $lang['previous'] ?? '« Previous' ?></a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        for($p = $start; $p <= $end; $p++): ?>
            <a href="?page=<?= $p ?>" class="<?= ($p == $page) ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>

        <?php if($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?>"><?= $lang['next'] ?? 'Next »' ?></a>
        <?php endif; ?>
    </div>
</main>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>

</body>
</html>

<?php
session_start();
include '../config/db.php';
include '../config/lang.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin'];
$username = $_SESSION['username'] ?? '';

// --- Dual Action Handler ---

// Handle Activate/Deactivate toggle
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $check = mysqli_query($conn, "SELECT username, status FROM admins WHERE id=$id");
    $row = mysqli_fetch_assoc($check);

    if ($row) {
        if ($row['username'] === 'masteradmin') {
            $_SESSION['msg'] = "❌ Cannot modify the 'masteradmin' account.";
            $_SESSION['msg_type'] = 'error';
        } elseif ($id == $admin_id) {
            $_SESSION['msg'] = "⚠️ You cannot change your own status.";
            $_SESSION['msg_type'] = 'error';
        } else {
            if ($row['status'] === 'active') {
                mysqli_query($conn, "UPDATE admins SET status='deactivated' WHERE id=$id");
                $_SESSION['msg'] = "🟡 Admin account deactivated.";
                $_SESSION['msg_type'] = 'success';
            } elseif ($row['status'] === 'deactivated') {
                mysqli_query($conn, "UPDATE admins SET status='active' WHERE id=$id");
                $_SESSION['msg'] = "🟢 Admin account activated.";
                $_SESSION['msg_type'] = 'success';
            } elseif ($row['status'] === 'banned') {
                mysqli_query($conn, "UPDATE admins SET status='active' WHERE id=$id");
                $_SESSION['msg'] = "🔓 Admin account unbanned and reactivated.";
                $_SESSION['msg_type'] = 'success';
            }
        }
    }

    header("Location: manage_admin.php");
    exit();
}

// Handle Permanent Ban
if (isset($_GET['ban'])) {
    $id = intval($_GET['ban']);
    $check = mysqli_query($conn, "SELECT username, status FROM admins WHERE id=$id");
    $row = mysqli_fetch_assoc($check);

    if ($row) {
        if ($row['username'] === 'masteradmin') {
            $_SESSION['msg'] = "❌ Cannot ban the 'masteradmin' account.";
            $_SESSION['msg_type'] = 'error';
        } elseif ($id == $admin_id) {
            $_SESSION['msg'] = "⚠️ You cannot ban your own account.";
            $_SESSION['msg_type'] = 'error';
        } elseif ($row['status'] !== 'banned') {
            mysqli_query($conn, "UPDATE admins SET status='banned' WHERE id=$id");
            $_SESSION['msg'] = "🛑 Admin permanently banned.";
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = "⚠️ Admin is already banned.";
            $_SESSION['msg_type'] = 'error';
        }
    }

    header("Location: manage_admin.php");
    exit();
}

include '../config/Nepali_Calendar.php';
$cal = new Nepali_Calendar();

function format_nepali_date($date_str, $cal) {
    if (!$date_str || !strtotime($date_str)) return '—';
    $timestamp = strtotime($date_str);
    $year  = (int)date('Y', $timestamp);
    $month = (int)date('m', $timestamp);
    $day   = (int)date('d', $timestamp);
    $hour  = (int)date('h', $timestamp);
    $minute = (int)date('i', $timestamp);
    $ampm  = date('A', $timestamp);

    if (($_SESSION['lang'] ?? 'en') === 'np') {
        $nepDate = $cal->eng_to_nep($year, $month, $day);
        $np_numbers = ['0'=>'०','1'=>'१','2'=>'२','3'=>'३','4'=>'४','5'=>'५','6'=>'६','7'=>'७','8'=>'८','9'=>'९'];
        $yearNep  = strtr($nepDate['year'], $np_numbers);
        $monthNep = strtr($nepDate['month'], $np_numbers);
        $dayNep   = strtr($nepDate['date'], $np_numbers);
        $hourNep  = strtr(sprintf("%02d", $hour), $np_numbers);
        $minNep   = strtr(sprintf("%02d", $minute), $np_numbers);
        return $dayNep . '-' . $monthNep . '-' . $yearNep . ', ' . $hourNep . ':' . $minNep . ' ' . $ampm;
    } else {
        return date("M d, Y h:i A", $timestamp);
    }
}

// Fetch admins with roles
$result = mysqli_query($conn, "
    SELECT a.*, r.role_name 
    FROM admins a
    LEFT JOIN roles r ON a.role_id = r.id
    ORDER BY FIELD(a.username, 'masteradmin') DESC, a.created_at DESC
");
$hasAdmins = mysqli_num_rows($result) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['manage_admin'] ?> - <?= $lang['logo'] ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }
        .main-content { padding: 30px; }
        h2 { font-size: 26px; margin-bottom: 5px; display: inline-block; color: #333; }
        .subtitle { color: #666; margin-bottom: 20px; font-size: 14px; }

        .add-btn {
            float: right;
            background: linear-gradient(135deg, #28a745, #218838);
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }
        .add-btn:hover { opacity: 0.85; }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        table th, table td { padding: 14px 16px; text-align: left; font-size: 14px; }
        table th { background: #007bff; color: #fff; font-weight: 600; }
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #f1f1f1; }

        tr.banned td { opacity: 0.6; text-decoration: line-through; }

        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            transition: 0.3s;
            margin-right: 5px;
        }
        .btn-deactivate { background: #ffc107; color: #000; }
        .btn-deactivate:hover { background: #e0a800; color: #fff; }
        .btn-activate { background: #28a745; color: #fff; }
        .btn-activate:hover { background: #218838; }
        .btn-ban { background: #dc3545; color: #fff; }
        .btn-ban:hover { background: #c82333; }

        .badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #cce5ff; color: #004085; }

        .message { margin-bottom: 15px; padding: 12px; border-radius: 8px; font-size: 14px; }
        .success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <h2>👥 <?= $lang['manage_admin'] ?></h2>
    <a href="add_admin.php" class="add-btn">➕ <?= $lang['add'] ?> <?= $lang['add_new_admin'] ?></a>
    <div style="clear: both;"></div>
    <p class="subtitle"><?= $lang['subtitle'] ?? 'Manage admin accounts (Activate/Deactivate or Ban/Unban)' ?></p>

    <?php if(isset($_SESSION['msg'])): ?>
        <div class="message <?= $_SESSION['msg_type'] ?? 'success' ?>">
            <?= $_SESSION['msg']; unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
        </div>
    <?php endif; ?>

    <?php if($hasAdmins): ?>
        <table>
            <thead>
            <tr>
                <th><?= $lang['sn'] ?></th>
                <th>👤 <?= $lang['username'] ?? 'Username' ?></th>
                <th>📧 <?= $lang['email'] ?></th>
                <th>📅 <?= $lang['date'] ?? 'Created At' ?></th>
                <th><?= $lang['status'] ?></th>
                <th>🕒 <?= $lang['last_login'] ?? 'Last Login' ?></th>
                <th>🏷️ <?= $lang['role'] ?? 'Role' ?></th>
                <th>⚡ <?= $lang['actions'] ?></th>
            </tr>
            </thead>
            <tbody>
            <?php $sn = 1; while($row = mysqli_fetch_assoc($result)): ?>
                <tr class="<?= $row['status'] === 'banned' ? 'banned' : '' ?>">
                    <td><?= $sn++ ?></td>
                    <td><?= htmlspecialchars($row['username']) ?>
                        <?php if ($row['username'] === 'masteradmin'): ?>
                            <span class="badge badge-warning">⭐</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= format_nepali_date($row['created_at'], $cal) ?></td>
                    <td>
                        <?php
                        if($row['status'] === 'active'):
                            $status_text = 'Active';
                            $status_class = 'badge-success';
                        elseif($row['status'] === 'deactivated'):
                            $status_text = 'Deactivated';
                            $status_class = 'badge-info';
                        else:
                            $status_text = 'Banned';
                            $status_class = 'badge-danger';
                        endif;
                        ?>
                        <span class="badge <?= $status_class ?>">
                            <?= ($row['status'] === 'active' ? '✅ ' : ($row['status'] === 'banned' ? '🛑 ' : '🚫 ')) . $status_text ?>
                        </span>
                    </td>
                    <td><?= $row['last_login'] ? format_nepali_date($row['last_login'], $cal) : '—' ?></td>
                    <td><?= htmlspecialchars($row['role_name'] ?? '—') ?></td>
                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <?php if($row['status'] === 'active'): ?>
                            <a href="manage_admin.php?toggle=<?= $row['id'] ?>"
                               class="btn btn-deactivate"
                               onclick="return confirm('<?= $lang['confirm_deactivate'] ?>')">🚫 <?= $lang['deactivate'] ?></a>
                            <a href="manage_admin.php?ban=<?= $row['id'] ?>"
                               class="btn btn-ban"
                               onclick="return confirm('<?= $lang['confirm_ban'] ?>')">🛑 <?= $lang['ban'] ?></a>

                        <?php elseif($row['status'] === 'deactivated'): ?>
                            <a href="manage_admin.php?toggle=<?= $row['id'] ?>"
                               class="btn btn-activate"
                               onclick="return confirm('<?= $lang['confirm_activate'] ?>')">✅ <?= $lang['activate'] ?></a>
                            <a href="manage_admin.php?ban=<?= $row['id'] ?>"
                               class="btn btn-ban"
                               onclick="return confirm('<?= $lang['confirm_ban'] ?>')">🛑 <?= $lang['ban'] ?></a>

                        <?php elseif($row['status'] === 'banned'): ?>
                            <a href="manage_admin.php?toggle=<?= $row['id'] ?>"
                               class="btn btn-activate"
                               onclick="return confirm('<?= $lang['confirm_unban'] ?>')">🔓 <?= $lang['unban'] ?></a>

                        <?php elseif($row['id'] == $admin_id): ?>
                            <span class="badge badge-warning">⚙️ <?= $lang['you'] ?></span>

                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align:center;background:#fff3cd;color:#856404;padding:20px;border-radius:10px;border:1px solid #ffeeba;font-size:16px;margin-top:20px;">
            ⚠ No admins available.
        </div>
    <?php endif; ?>
</main>
</body>
</html>

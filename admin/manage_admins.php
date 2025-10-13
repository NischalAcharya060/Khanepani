<?php
session_start();
include '../config/db.php';
include '../config/Nepali_Calendar.php';
$cal = new Nepali_Calendar();

const ACTIVE_NOW_THRESHOLD = 100;

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
        return date("d M Y, h:i A", $timestamp);
    }
}

function format_time_ago($date_str) {
    if (!$date_str || strtotime($date_str) === false) return '—';

    global $lang;
    $timestamp = strtotime($date_str);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return $lang['just_now'] ?? 'just now';
    }

    $periods = array(
            31536000 => $lang['year'] ?? 'year',
            2592000  => $lang['month'] ?? 'month',
            604800   => $lang['week'] ?? 'week',
            86400    => $lang['day'] ?? 'day',
            3600     => $lang['hour'] ?? 'hour',
            60       => $lang['minute'] ?? 'minute',
            1        => $lang['second'] ?? 'second'
    );

    $lang_ago = $lang['ago'] ?? 'ago';

    foreach ($periods as $seconds => $name) {
        if ($diff >= $seconds) {
            $value = floor($diff / $seconds);
            $phrase = $name . ($value > 1 ? 's' : '');
            return "$value $phrase $lang_ago";
        }
    }

    return '—';
}

function is_admin_active_now(?string $last_login_str): bool {
    if (!$last_login_str) return false;
    $last_login_timestamp = strtotime($last_login_str);
    if ($last_login_timestamp === false) return false;
    return (time() - $last_login_timestamp) < ACTIVE_NOW_THRESHOLD;
}

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$current_admin_id = $_SESSION['admin'];

// --- FIX: Robust privilege check against database ---
// Fetch current role from DB using the logged-in ID for reliability.
$current_admin_role_check = $conn->prepare("SELECT r.role_name FROM admins a JOIN roles r ON a.role_id = r.id WHERE a.id = ?");
$current_admin_role_check->bind_param("i", $current_admin_id);
$current_admin_role_check->execute();
$result = $current_admin_role_check->get_result();
$current_admin_db_role_name = $result->fetch_assoc()['role_name'] ?? '';

// Use the reliable database role name for the rest of the page logic
$current_admin_role = $current_admin_db_role_name;
$is_master_admin_session = (strtolower($current_admin_role) === 'masteradmin');

// Update session for consistency across other pages if needed
$_SESSION['role_name'] = $current_admin_role;
// ---------------------------------------------------


if (!isset($_SESSION['lang'])) $_SESSION['lang'] = 'en';
if (isset($_GET['lang'])) {
    $allowed_langs = ['en','np'];
    if (in_array($_GET['lang'], $allowed_langs)) $_SESSION['lang'] = $_GET['lang'];
}
include '../lang/' . $_SESSION['lang'] . '.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => $lang['db_error'] ?? 'Database error.'];
    $admin_id = intval($_POST['id'] ?? 0);

    // Use the reliable $current_admin_role for permission checks
    if ($admin_id == $current_admin_id && strtolower($current_admin_role) === 'masteradmin') {
        $response['message'] = $lang['self_edit_forbidden'] ?? 'You cannot edit your own account.';
        echo json_encode($response);
        exit;
    }

    if (strtolower($current_admin_role) !== 'masteradmin') {
        $response['message'] = $lang['no_permission'] ?? 'You do not have permission.';
        echo json_encode($response);
        exit;
    }

    if ($admin_id <= 0) {
        $response['message'] = $lang['invalid_id'] ?? 'Invalid admin ID.';
        echo json_encode($response);
        exit;
    }

    // --- The rest of the POST logic remains unchanged ---
    $action = $_POST['action'];
    $update_successful = false;

    switch ($action) {
        case 'update_role':
            $role_id = intval($_POST['value'] ?? 0);
            $stmt = $conn->prepare("UPDATE admins SET role_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $role_id, $admin_id);
            $update_successful = $stmt->execute();
            if ($update_successful) {
                $role_name_to_return = $_POST['new_role_name'] ?? 'N/A';
                $response['new_role_name'] = htmlspecialchars($role_name_to_return);
            }
            break;

        case 'update_status':
            $status = $_POST['value'];
            if (in_array($status, ['active', 'inactive', 'banned'])) {
                $stmt = $conn->prepare("UPDATE admins SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $admin_id);
                $update_successful = $stmt->execute();
                $response['new_status'] = $status;
            }
            break;

        default:
            $response['message'] = $lang['invalid_action'] ?? 'Invalid action.';
            break;
    }

    if ($update_successful) {
        $response['success'] = true;
        $response['message'] = $lang['update_success'] ?? 'Update successful.';
    } else if (isset($stmt) && $stmt->errno) {
        $response['message'] = ($lang['db_query_error'] ?? 'Database query error:') . ' ' . $stmt->error;
    } else if (!$update_successful) {
        $response['message'] = $lang['update_failed'] ?? 'Update failed.';
    }

    echo json_encode($response);
    exit;
}
// --- The rest of the page rendering logic remains unchanged ---

$roles_result = $conn->query("SELECT id, role_name FROM roles ORDER BY id ASC");
$roles = [];
while ($row = $roles_result->fetch_assoc()) {
    $roles[$row['id']] = $row;
}

$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$query = "
    SELECT a.*, r.role_name, r.id AS role_id_fk
    FROM admins a 
    LEFT JOIN roles r ON a.role_id = r.id 
    ORDER BY a.created_at DESC 
    LIMIT $offset, $limit";
$admins = $conn->query($query);

$total_result = $conn->query("SELECT COUNT(*) as total FROM admins");
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);

$username = $_SESSION['username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['manage_admins'] ?? 'Manage Admins' ?> - सलकपुर खानेपानी</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
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

        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            overflow: hidden;
        }

        .admin-table th {
            padding: 18px 15px;
            text-align: left;
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: capitalize;
            font-size: 0.9em;
        }

        .admin-table td {
            padding: 16px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
            font-size: 0.95em;
            color: #495057;
            background-color: #ffffff;
        }

        .admin-table tbody tr:nth-child(even) {
            background-color: #fcfdff;
        }

        .admin-table tbody tr:hover {
            background: #e6f3ff;
            box-shadow: inset 3px 0 0 0 #1e3a8a;
            cursor: default;
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .admin-table td:nth-child(1), .admin-table th:nth-child(1) { text-align: center; width: 4%; }
        .admin-table td:nth-child(2), .admin-table th:nth-child(2) { text-align: center; width: 6%; }
        .admin-table td:nth-child(5), .admin-table th:nth-child(5) { width: 12%; }
        /* Adjusted width for last column based on master admin visibility */
        <?php if ($is_master_admin_session): ?>
        .admin-table td:nth-child(6), .admin-table th:nth-child(6) { text-align: center; width: 8%; } /* Status */
        .admin-table td:nth-child(9), .admin-table th:nth-child(9) { text-align: center; width: 18%; } /* Actions */
        <?php else: ?>
        .admin-table td:nth-child(6), .admin-table th:nth-child(6) { text-align: center; width: 10%; }
        <?php endif; ?>


        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #1e3a8a;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .profile-pic:hover {
            transform: scale(1.05);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.85em;
            font-weight: 700;
            display: inline-block;
            min-width: 80px;
            text-align: center;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: background-color 0.2s;
        }
        .status-active {
            background-color: #059669;
        }
        .status-inactive {
            background-color: #f97316;
            color: #ffffff;
        }
        .status-banned {
            background-color: #ef4444;
        }

        .active-now {
            font-weight: 700;
            color: #059669;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .active-now::before {
            content: '•';
            font-size: 1.5em;
            line-height: 0.5em;
            color: #059669;
        }

        .role-display {
            cursor: pointer;
            color: #1e3a8a;
            font-weight: 600;
            padding: 4px 6px;
            border-radius: 6px;
            transition: background-color 0.1s;
            display: inline-block;
        }
        .role-display:hover {
            background-color: #e0f2fe;
            color: #1c50af;
        }
        .role-select {
            padding: 7px;
            border-radius: 6px;
            border: 1px solid #1e3a8a;
            font-size: 0.95em;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .action-group-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn.action-button {
            padding: 8px 14px;
            font-size: 0.8em;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
        }

        .btn.action-button.danger {
            background-color: #ef4444;
            color: white;
        }
        .btn.action-button.success {
            background-color: #059669;
            color: white;
        }
        .btn.action-button.warning {
            background-color: #f97316;
            color: white;
        }

        .btn.small.disabled {
            background-color: #e9ecef !important;
            color: #6c757d !important;
            font-weight: 500;
            padding: 8px 14px;
            border: 1px solid #ced4da;
            cursor: default !important;
            border-radius: 8px;
            box-shadow: none;
        }

        .status-unactivated {
            color: #993300;
            font-weight: 500;
            font-style: italic;
            background-color: #fff8e1;
            padding: 3px 6px;
            border-radius: 4px;
            border: 1px dashed #ffd54f;
            display: inline-block;
        }

        #message-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            width: 350px;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 10px;
            display: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            animation: fadeInDown 0.5s ease-out;
        }
        .alert-success { background-color: #d1fae5; color: #065f46; border-left: 5px solid #059669; }
        .alert-danger { background-color: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .spinner {
            display: inline-block;
            font-size: 13px;
            color: #007bff;
            animation: spin 1s linear infinite;
            margin-left: 4px;
        }

        @keyframes spin {
            0% { opacity: 0.5; transform: rotate(0deg); }
            50% { opacity: 1; transform: rotate(180deg); }
            100% { opacity: 0.5; transform: rotate(360deg); }
        }

        .pagination { text-align: center; margin-top: 20px; }
        .pagination a { margin: 0 5px; text-decoration: none; padding: 6px 12px; border: 1px solid #ddd; border-radius: 4px; color: #0056d6; }
        .pagination a.active { background-color: #0056d6; color: white; border-color: #0056d6; }
        .pagination a:hover { background-color: #0056d6; color: white; }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="main-content">
    <h2>👥 <?= $lang['manage_admins'] ?? 'Manage Admins' ?></h2>
    <p class="subtitle"><?= $lang['manage_admins_subtitle'] ?? 'View, edit, or remove administrator accounts.' ?></p>
    <div id="message-container"></div>

    <a href="add_admin.php" class="btn">➕ <?= $lang['add_admin'] ?? 'Add New Admin' ?></a>

    <table class="admin-table">
        <thead>
        <tr>
            <th><?= $lang['sn'] ?? 'S.N.' ?></th>
            <th><?= $lang['profile'] ?? 'Profile' ?></th>
            <th><?= $lang['username'] ?? 'Username' ?></th>
            <th><?= $lang['email'] ?? 'Email' ?></th>
            <th><?= $lang['role'] ?? 'Role' ?></th>
            <th><?= $lang['status'] ?? 'Status' ?></th>
            <th><?= $lang['created_at'] ?? 'Created At' ?></th>
            <th><?= $lang['last_login'] ?? 'Last Login' ?></th>
            <th><?= $lang['added_by'] ?? 'Added By' ?></th>

            <?php if ($is_master_admin_session): ?>
                <th><?= $lang['actions'] ?? 'Actions' ?></th>
            <?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php if ($admins->num_rows > 0): ?>
            <?php $sn = $offset + 1; ?>
            <?php while ($admin = $admins->fetch_assoc()): ?>
                <?php $is_self = ($admin['id'] == $current_admin_id); ?>
                <?php $is_master = ($admin['role_id_fk'] == 1); ?>
                <tr>
                    <td><?= $sn++ ?></td>
                    <td>
                        <?php
                        $admin_profile_pic = $admin['profile_pic'] ?? '';
                        $uploadedPath = "../assets/uploads/profile/" . htmlspecialchars($admin_profile_pic);
                        $defaultPath = "../assets/profile/default.png";
                        if (!empty($admin_profile_pic) && file_exists($uploadedPath) && $admin_profile_pic !== 'default.png') {
                            echo '<img src="' . $uploadedPath . '" class="profile-pic" alt="Profile">';
                        } else {
                            echo '<img src="' . $defaultPath . '" class="profile-pic" alt="Default">';
                        }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($admin['username'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($admin['email'] ?? 'N/A') ?></td>

                    <td data-role-id="<?= $admin['role_id_fk'] ?>" data-admin-id="<?= $admin['id'] ?>" class="role-cell">
                        <?php if ($is_self && $is_master): ?>
                            <span class="role-static"><?= htmlspecialchars($admin['role_name'] ?? '—') ?></span> (<?= $lang['you'] ?? 'You' ?>)
                        <?php elseif ($current_admin_role === 'masteradmin'): ?>
                            <span class="role-display" id="role-display-<?= $admin['id'] ?>">
                                <?= htmlspecialchars($admin['role_name'] ?? '—') ?>
                            </span>
                        <?php else: ?>
                            <span class="role-static"><?= htmlspecialchars($admin['role_name'] ?? '—') ?>
                        <?php endif; ?>
                    </td>

                    <td class="status-cell">
                        <?php
                        $status_class = 'status-' . strtolower($admin['status'] ?? 'inactive');
                        $status_text = ucfirst($admin['status'] ?? 'Inactive');
                        ?>
                        <span id="badge-<?= $admin['id'] ?>" class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                    </td>

                    <td><?= format_nepali_date($admin['created_at'], $cal) ?></td>
                    <td>
                        <?php
                        $last_login_str = $admin['last_login'] ?? null;
                        $is_active_now = $is_self || is_admin_active_now($last_login_str);

                        if ($is_active_now) {
                            echo '<span class="active-now">' . ($lang['active_now'] ?? 'Active Now') . '</span>';
                        } else {
                            if (!empty($last_login_str)) {
                                $last_login_display = format_time_ago($last_login_str);
                                $title_text = date('Y-m-d H:i:s', strtotime($last_login_str));
                                echo '<span title="' . $title_text . '">' . $last_login_display . '</span>';
                            } else {
                                $not_activated_text = $lang['account_not_activated'] ?? 'Account Not Activated';
                                echo '<span class="status-unactivated" title="' . ($lang['account_never_logged_in'] ?? 'Account has never logged in.') . '">' . $not_activated_text . '</span>';
                            }
                        }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($admin['added_by'] ?? 'N/A') ?></td>

                    <?php if ($is_master_admin_session): ?>
                        <td class="action-buttons-cell">
                            <?php
                            $you_text = $lang['you'] ?? 'You';

                            if ($is_self && $is_master) {
                                echo '<span class="btn small disabled action-master">(' . htmlspecialchars($you_text) . ')</span>';
                            } else {
                                $initial_status = $admin['status'] ?? 'inactive';
                                echo '<div class="action-group-buttons" id="action-group-' . $admin['id'] . '" data-initial-status="' . $initial_status . '"></div>';
                            }
                            ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?= $is_master_admin_session ? 9 : 8 ?>" style="text-align:center; padding:20px;"><?= $lang['no_admins'] ?? 'No admins found.' ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php if($page > 1): ?>
            <a href="?page=<?= $page-1 ?>"><?= $lang['previous'] ?? '« Previous' ?></a>
        <?php endif; ?>
        <?php if($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?>"><?= $lang['next'] ?? 'Next »' ?></a>
        <?php endif; ?>
    </div>
</main>

<script>
    const roles = <?= json_encode($roles) ?>;
    const langJS = {
        update_success: '<?= $lang['update_success'] ?? 'Update successful.' ?>',
        update_failed: '<?= $lang['update_failed'] ?? 'Update failed.' ?>',
        ajax_failed: '<?= $lang['ajax_failed'] ?? 'AJAX connection failed.' ?>',
        confirm_status: '<?= $lang['confirm_status'] ?? 'Are you sure you want to set the status to' ?>',
        ban: '<?= $lang['ban'] ?? 'Ban' ?>',
        unban: '<?= $lang['unban'] ?? 'Unban' ?>',
        inactive: '<?= $lang['inactive'] ?? 'Inactive' ?>',
        active: '<?= $lang['active'] ?? 'Active' ?>',
    };

    function showMessage(type, message) {
        let container = $('#message-container');
        let alertClass = (type === 'success') ? 'alert-success' : 'alert-danger';
        container.html(`<div class="alert ${alertClass}">${message}</div>`);
        container.fadeIn().delay(3000).fadeOut();
    }

    function getActionButtonsHtml(adminId, currentStatus) {
        let html = '';
        const banButton = `<button class="btn action-button danger update-status-btn" data-id="${adminId}" data-new-status="banned">🚫 ${langJS.ban}</button>`;
        const unbanButton = `<button class="btn action-button success update-status-btn" data-id="${adminId}" data-new-status="active">✔ ${langJS.unban}</button>`;
        const inactiveButton = `<button class="btn action-button warning update-status-btn" data-id="${adminId}" data-new-status="inactive">- ${langJS.inactive}</button>`;
        const activeButton = `<button class="btn action-button success update-status-btn" data-id="${adminId}" data-new-status="active">+ ${langJS.active}</button>`;

        switch (currentStatus) {
            case 'active':
                html += banButton;
                html += inactiveButton;
                break;
            case 'inactive':
                html += banButton;
                html += activeButton;
                break;
            case 'banned':
                html += unbanButton;
                break;
            default:
                html += banButton;
                html += activeButton;
                break;
        }

        return html;
    }

    function rebindStatusButtons() {
        $('.update-status-btn').off('click').on('click', function() {
            const $button = $(this);
            const adminId = $button.data('id');
            const newStatus = $button.data('new-status');
            const $actionGroup = $button.closest('.action-group-buttons');
            const $badge = $('#badge-' + adminId);

            const originalStatusClassMatch = $badge.attr('class').match(/status-(active|inactive|banned)/);
            const originalStatus = originalStatusClassMatch ? originalStatusClassMatch[1] : 'inactive';

            if (!confirm(`${langJS.confirm_status} "${newStatus.toUpperCase()}"?`)) {
                return;
            }

            $.ajax({
                url: 'manage_admins.php',
                type: 'POST',
                data: {
                    action: 'update_status',
                    id: adminId,
                    value: newStatus
                },
                dataType: 'json',
                beforeSend: function() {
                    $actionGroup.find('.update-status-btn').attr('disabled', true).addClass('disabled');
                    $button.html('<span class="spinner">⏳</span>');
                },
                success: function(response) {
                    if (response.success) {
                        const updatedStatus = response.new_status;
                        let statusDisplayText = langJS[updatedStatus] || updatedStatus.charAt(0).toUpperCase() + updatedStatus.slice(1);

                        $badge.removeClass('status-active status-inactive status-banned')
                            .addClass('status-' + updatedStatus)
                            .text(statusDisplayText.charAt(0).toUpperCase() + statusDisplayText.slice(1));

                        $actionGroup.html(getActionButtonsHtml(adminId, updatedStatus));
                        rebindStatusButtons();

                        showMessage('success', response.message);
                    } else {
                        showMessage('error', response.message);
                        $actionGroup.html(getActionButtonsHtml(adminId, originalStatus));
                        rebindStatusButtons();
                    }
                },
                error: function() {
                    showMessage('error', langJS.ajax_failed);
                    $actionGroup.html(getActionButtonsHtml(adminId, originalStatus));
                    rebindStatusButtons();
                }
            });
        });
    }


    $(document).ready(function() {
        const currentUserRole = '<?php echo strtolower(trim($current_admin_role ?? "")); ?>';

        // Only initialize buttons if the current user is a master admin
        if (currentUserRole === 'masteradmin') {
            $('.action-group-buttons').each(function() {
                const $group = $(this);
                const adminId = $group.attr('id').replace('action-group-', '');
                const initialStatus = $group.data('initial-status');
                $group.html(getActionButtonsHtml(adminId, initialStatus));
            });
            rebindStatusButtons();
        }

        // Role editing logic (Only available for Master Admin)
        $('.role-display').on('click', function() {
            if (currentUserRole !== 'masteradmin') {
                showMessage('error', 'You do not have permission to change roles.');
                return;
            }

            const $displaySpan = $(this);
            const $roleCell = $displaySpan.parent();
            const adminId = $roleCell.data('admin-id');
            const currentRoleId = $roleCell.data('role-id');

            if ($roleCell.find('.role-select').length) return;

            if (parseInt(adminId) === parseInt('<?php echo $current_admin_id; ?>')) {
                showMessage('error', 'You cannot change your own role.');
                return;
            }

            let $select = $('<select class="role-select"></select>');
            $.each(roles, function(id, role) {
                const selected = (parseInt(id) === parseInt(currentRoleId)) ? 'selected' : '';
                $select.append(`<option value="${id}" ${selected}>${role.role_name}</option>`);
            });

            $displaySpan.hide();
            $roleCell.append($select);
            $select.focus();

            const $spinner = $('<span class="spinner ml-2">⏳</span>');
            $spinner.hide();
            $roleCell.append($spinner);

            $select.on('change', function() {
                const newRoleId = $(this).val();
                const newRoleName = $(this).find('option:selected').text();

                $spinner.show();

                $.ajax({
                    url: 'manage_admins.php',
                    type: 'POST',
                    data: {
                        action: 'update_role',
                        id: adminId,
                        value: newRoleId,
                        new_role_name: newRoleName
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showMessage('success', response.message);
                            $displaySpan.text(response.new_role_name);
                            $roleCell.data('role-id', newRoleId);
                        } else {
                            showMessage('error', response.message);
                        }
                    },
                    error: function() {
                        showMessage('error', langJS.ajax_failed);
                    },
                    complete: function() {
                        $spinner.hide();
                        $select.remove();
                        $displaySpan.show();
                    }
                });
            });

            $select.on('blur', function() {
                setTimeout(() => {
                    $select.remove();
                    $displaySpan.show();
                }, 100);
            });
        });
    });
</script>

</body>
</html>
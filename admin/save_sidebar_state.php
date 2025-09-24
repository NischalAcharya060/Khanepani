<?php
session_start();
if (isset($_POST['state'])) {
    $_SESSION['sidebar_state'] = $_POST['state'];
    echo "saved";
}

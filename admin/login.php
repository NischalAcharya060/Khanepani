<?php
session_start();
include '../config/db.php';

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM admin WHERE username='$username'";
    $result = $conn->query($sql);

    if($result->num_rows == 1){
        $admin = $result->fetch_assoc();
        if(password_verify($password, $admin['password'])){
            $_SESSION['admin'] = $admin['id'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "Admin not found!";
    }
}
?>

<form method="POST">
    <input type="text" name="username" placeholder="Username" required /><br>
    <input type="password" name="password" placeholder="Password" required /><br>
    <button type="submit" name="login">Login</button>
</form>

<?php if(isset($error)) echo $error; ?>

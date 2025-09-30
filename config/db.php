<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "khaneapani";

//$host = "sql313.infinityfree.com";
//$user = "if0_39987491";
//$pass = "hiukHPgcZJ1HCuT";
//$db   = "if0_39987491_khanepani";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

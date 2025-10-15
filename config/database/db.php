<?php
// LocalHost
  // $host = "localhost";
  // $port = 3306;
  // $user = "root";
  // $pass = "";
  // $db   = "khaneapani";

// Infinity Host
// $host = "sql313.infinityfree.com";
// $user = "if0_39987491";
// $pass = "hiukHPgcZJ1HCuT";
// $db   = "if0_39987491_khanepani";

// Wasmer Host
//$host = "db.fr-pari1.bengt.wasmernet.com";
//$port = 10272;
//$db   = "khanepani";
//$user = "da83b3f17a968000a4066df68a56";
//$pass = "068eda83-b3f1-7c82-8000-834f83407111";

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

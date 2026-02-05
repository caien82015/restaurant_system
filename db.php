
<?php
$host = "localhost";
$user = "root";
$pass = "820815";
$dbname = "restaurant_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if(!$conn){
    die("Database connection failed: " . mysqli_connect_error());
}
?>

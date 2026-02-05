<?php
include 'db.php';
$id = intval($_GET['id']);
$res = mysqli_query($conn, "SELECT * FROM booking WHERE id=$id");
$booking = mysqli_fetch_assoc($res);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $booking_time = mysqli_real_escape_string($conn, $_POST['booking_time']);
    $people = intval($_POST['people']);

    $sql = "UPDATE booking SET 
            customer_name='$customer_name', 
            phone='$phone', 
            booking_date='$booking_date', 
            booking_time='$booking_time', 
            people=$people 
            WHERE id=$id";
    mysqli_query($conn, $sql) or die("Error: " . mysqli_error($conn));
    header("Location: dashboard.php");
    exit;
}
?>
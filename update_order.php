<?php
include 'db.php';
$id = intval($_GET['id']);
$res = mysqli_query($conn, "SELECT * FROM orders WHERE id=$id");
$order = mysqli_fetch_assoc($res);

$booking_res = mysqli_query($conn, "SELECT id, customer_name FROM booking ORDER BY booking_date DESC");
$food_res = mysqli_query($conn, "SELECT id, food_name, price FROM food ORDER BY food_name ASC");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $booking_id = intval($_POST['booking_id']);
    $food_id = intval($_POST['food_id']);
    $quantity = intval($_POST['quantity']);

    $sql = "UPDATE orders SET booking_id=$booking_id, food_id=$food_id, quantity=$quantity WHERE id=$id";
    mysqli_query($conn, $sql) or die("Error: " . mysqli_error($conn));
    header("Location: dashboard.php");
    exit;
}
?>


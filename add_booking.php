<?php
include 'db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $booking_time = mysqli_real_escape_string($conn, $_POST['booking_time']);
    $people = intval($_POST['people']); 

    $sql = "INSERT INTO booking (customer_name, phone, booking_date, booking_time, people, created_at)
            VALUES ('$customer_name','$phone','$booking_date','$booking_time',$people, NOW())";

    mysqli_query($conn, $sql) or die("Error: " . mysqli_error($conn));
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
<h2>Add Booking</h2>
<form method="post">
    <div class="mb-3">
        <label>Customer Name</label>
        <input type="text" name="customer_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Date</label>
        <input type="date" name="booking_date" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Time</label>
        <input type="time" name="booking_time" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>People</label>
        <input type="number" name="people" class="form-control" required>
    </div>
    <button class="btn btn-primary">Add Booking</button>
    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
</form>
</body>
</html>

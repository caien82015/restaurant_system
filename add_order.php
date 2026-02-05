<?php
include 'db.php';

$booking_res = mysqli_query($conn, "SELECT id, customer_name FROM booking ORDER BY booking_date DESC");
$food_res = mysqli_query($conn, "SELECT id, food_name, price FROM food ORDER BY food_name ASC");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $booking_id = intval($_POST['booking_id']);
    $food_id = intval($_POST['food_id']);
    $quantity = intval($_POST['quantity']);

    $sql = "INSERT INTO orders (booking_id, food_id, quantity, order_time) 
            VALUES ($booking_id, $food_id, $quantity, NOW())";
    mysqli_query($conn, $sql) or die("Error: " . mysqli_error($conn));

    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
<h2>Add Order</h2>
<form method="post">
    <div class="mb-3">
        <label>Booking</label>
        <select name="booking_id" class="form-control" required>
            <option value="">Select Booking</option>
            <?php while($b = mysqli_fetch_assoc($booking_res)): ?>
                <option value="<?= $b['id'] ?>">ID <?= $b['id'] ?> - <?= $b['customer_name'] ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="mb-3">
        <label>Food</label>
        <select name="food_id" class="form-control" required>
            <option value="">Select Food</option>
            <?php while($f = mysqli_fetch_assoc($food_res)): ?>
                <option value="<?= $f['id'] ?>"><?= $f['food_name'] ?> - RM <?= number_format($f['price'],2) ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="mb-3">
        <label>Quantity</label>
        <input type="number" name="quantity" class="form-control" required>
    </div>
    <button class="btn btn-success">Add Order</button>
    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
</form>
</body>
</html>

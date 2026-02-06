<?php
include 'db.php';

$restaurant_name = "Lululu Restaurant";
$restaurant_address = "123 Main Street, City";
$restaurant_phone = "012-3456789";
$restaurant_hours = "10:00 - 22:00";

$food_res = mysqli_query($conn,"SELECT * FROM food ORDER BY food_name ASC");
$food_options = [];
while($row=mysqli_fetch_assoc($food_res)) $food_options[] = $row;

function generate_time_options($start="10:00",$end="22:00",$step=30){
    $times = [];
    $start_ts = strtotime($start);
    $end_ts = strtotime($end);
    for($t=$start_ts;$t<=$end_ts;$t+=60*$step){
        $times[] = date("H:i",$t);
    }
    return $times;
}
$time_options = generate_time_options();

if($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['save_booking'])){
    $customer_name = mysqli_real_escape_string($conn,$_POST['customer_name']);
    $phone = mysqli_real_escape_string($conn,$_POST['phone']);
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $people = intval($_POST['people']);

    $start_time = strtotime("10:00");
    $end_time = strtotime("22:00");
    $selected_time = strtotime($booking_time);
    if($selected_time < $start_time || $selected_time > $end_time){
        echo "<script>alert('请选择营业时间内的时间（10:00 - 22:00）'); window.history.back();</script>";
        exit;
    }

    mysqli_query($conn,"INSERT INTO booking (customer_name, phone, booking_date, booking_time, people, created_at)
        VALUES ('$customer_name','$phone','$booking_date','$booking_time',$people,NOW())") or die(mysqli_error($conn));

    $booking_id = mysqli_insert_id($conn);
    header("Location: orders.php?booking_id=$booking_id");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title><?= htmlspecialchars($restaurant_name) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>

body{
    font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    background:#f0f4f8; 
    padding-bottom:50px;
    color:#333;
}
.container{max-width:1200px; margin:auto;}

.menu-card{
    background:#ffffff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
    margin-bottom:20px;
    text-align:center;
    transition: transform 0.2s, box-shadow 0.2s;
}
.menu-card:hover{
    transform: translateY(-5px);
    box-shadow:0 8px 18px rgba(0,0,0,0.2);
}

.btn-primary{
    background-color:#007bff; border:none; border-radius:8px; padding:8px 16px;
    transition:0.2s;
}
.btn-primary:hover{background-color:#0056b3;}
.btn-success{
    background-color:#28a745; border:none; border-radius:8px; padding:6px 14px;
}
.btn-success:hover{background-color:#218838;}

input.form-control, select.form-control{
    border-radius:8px;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    border:1px solid #ccc;
}

h1,h2,h3{color:#007bff;}
</style>
</head>
<body class="container mt-5">

<h1><?= htmlspecialchars($restaurant_name) ?></h1>
<p><strong>地址:</strong> <?= htmlspecialchars($restaurant_address) ?> | <strong>电话:</strong> <?= htmlspecialchars($restaurant_phone) ?></p>
<p><strong>营业时间:</strong> <?= htmlspecialchars($restaurant_hours) ?></p>

<h2 class="mt-4">Menu</h2>
<div class="row">
<?php foreach($food_options as $f): ?>
<div class="col-md-3">
    <div class="menu-card">
        <h5><?= htmlspecialchars($f['food_name']) ?></h5>
        <p>Price: <?= $f['price'] ?></p>
    </div>
</div>
<?php endforeach; ?>
</div>

<h2 class="mt-4">Booking</h2>
<form method="post" class="row g-3">
    <div class="col-md-3">
        <input type="text" name="customer_name" class="form-control" placeholder="Customer Name" required>
    </div>
    <div class="col-md-3">
        <input type="text" name="phone" class="form-control" placeholder="Phone" required>
    </div>
    <div class="col-md-2">
        <input type="date" name="booking_date" class="form-control" required>
    </div>
    <div class="col-md-2">
        <select name="booking_time" class="form-control" required>
            <?php foreach($time_options as $t): ?>
            <option value="<?= $t ?>"><?= $t ?></option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted">营业时间: 10:00 - 22:00</small>
    </div>
    <div class="col-md-1">
        <input type="number" name="people" class="form-control" min="1" max="20" placeholder="人数" required>
    </div>
    <div class="col-md-1">
        <button type="submit" name="save_booking" class="btn btn-primary">Book</button>
    </div>
</form>

</body>
</html>

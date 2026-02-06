<?php
include 'db.php';

$restaurant_name = "Lululu Restaurant";
$restaurant_address = "123 Main Street, City";
$restaurant_phone = "012-3456789";

$booking_id = intval($_GET['booking_id'] ?? 0);
if(!$booking_id) die("Booking not found");

$booking_res = mysqli_query($conn,"SELECT * FROM booking WHERE id=$booking_id");
$booking = mysqli_fetch_assoc($booking_res);
if(!$booking) die("Booking not found");

$food_res = mysqli_query($conn,"SELECT * FROM food ORDER BY food_name ASC");
$food_options = [];
while($row=mysqli_fetch_assoc($food_res)) $food_options[] = $row;

if(isset($_POST['place_order'])){
    $food_id = intval($_POST['food_id']);
    mysqli_query($conn,"INSERT INTO orders (booking_id, food_id, quantity, order_time) VALUES ($booking_id,$food_id,1,NOW())") or die(mysqli_error($conn));
    header("Location: orders.php?booking_id=$booking_id");
    exit;
}

$orders_res = mysqli_query($conn,"
SELECT o.id, f.food_name, f.price
FROM orders o
JOIN food f ON o.food_id=f.id
WHERE o.booking_id=$booking_id
");
$orders = [];
$total_price = 0;
while($row=mysqli_fetch_assoc($orders_res)){
    $orders[] = $row;
    $total_price += $row['price'];
}

$receipt_note = "谢谢惠顾！欢迎下次光临~";  
?>

<!DOCTYPE html>
<html>
<head>
<title>Orders for <?= htmlspecialchars($booking['customer_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    font-family: 'Segoe UI', Arial, sans-serif;
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

#receipt{
    display:none;
    background:#ffffff;
    padding:25px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.2);
    max-width:480px;
    margin-top:20px;
    font-family:'Segoe UI', Arial, sans-serif;
}
#receipt h4{
    text-align:center;
    margin-bottom:20px;
    color:#007bff;
    font-size:1.6em;
    border-bottom:2px solid #007bff;
    padding-bottom:5px;
}
#receipt p{margin:4px 0; font-size:0.95em; color:#555;}
#receipt table{width:100%; border-collapse:collapse; margin-top:10px;}
#receipt th, #receipt td{padding:10px 8px; border-bottom:1px solid #ddd; text-align:left; font-size:0.95em;}
#receipt tr:nth-child(even){background:#f0f8ff;}
#receipt tr.total-row{font-weight:bold; font-size:1.1em; color:#007bff;}
#receipt .note{margin-top:15px; font-style:italic; color:#888; text-align:center;}

@media print{
    body *{visibility:hidden;}
    #receipt, #receipt *{visibility:visible;}
    #receipt{position:absolute; left:0; top:0; width:100%;}
}
</style>
</head>
<body class="container mt-5">

<h2>Booking: <?= htmlspecialchars($booking['customer_name']) ?> (<?= $booking['booking_date'] ?> <?= $booking['booking_time'] ?>)</h2>

<h3>Menu</h3>
<div class="row">
<?php foreach($food_options as $f): ?>
<div class="col-md-3">
    <div class="menu-card">
        <h5><?= htmlspecialchars($f['food_name']) ?></h5>
        <p>Price: <?= $f['price'] ?></p>
        <form method="post">
            <input type="hidden" name="food_id" value="<?= $f['id'] ?>">
            <button type="submit" name="place_order" class="btn btn-success btn-sm">Order</button>
        </form>
    </div>
</div>
<?php endforeach; ?>
</div>

<div class="mb-3">
    <a href="index.php" class="btn btn-primary">Back to Menu</a>
</div>

<h3>Orders</h3>
<?php if(count($orders) > 0): ?>
<button class="btn btn-primary mb-3" onclick="document.getElementById('receipt').style.display='block'">查看小票</button>

<div id="receipt">
<h4><?= htmlspecialchars($restaurant_name) ?> - Receipt</h4>
<p><strong>Customer:</strong> <?= htmlspecialchars($booking['customer_name']) ?></p>
<p><strong>Phone:</strong> <?= htmlspecialchars($booking['phone']) ?></p>
<p><strong>Date:</strong> <?= date("Y-m-d H:i", strtotime($booking['booking_date'].' '.$booking['booking_time'])) ?></p>
<p><strong>People:</strong> <?= $booking['people'] ?></p>
<p><strong>Address:</strong> <?= htmlspecialchars($restaurant_address) ?></p>
<p><strong>Phone:</strong> <?= htmlspecialchars($restaurant_phone) ?></p>

<table>
<tr><th>Food</th><th>Price</th></tr>
<?php foreach($orders as $o): ?>
<tr>
<td><?= htmlspecialchars($o['food_name']) ?></td>
<td><?= $o['price'] ?></td>
</tr>
<?php endforeach; ?>
<tr class="total-row">
<td>Total</td>
<td><?= $total_price ?></td>
</tr>
</table>

<p class="note"><?= htmlspecialchars($receipt_note) ?></p>
<button class="btn btn-success" onclick="window.print()">Print</button>
</div>
<?php else: ?>
<p>No orders yet.</p>
<?php endif; ?>

</body>
</html>

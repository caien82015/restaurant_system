<?php
include 'db.php';

$restaurant_name = "Delicious Restaurant";
$restaurant_address = "123 Main Street, City";
$restaurant_phone = "012-3456789";
$max_people_per_slot = 20;

$food_options_res = mysqli_query($conn,"SELECT id, food_name, price, image_path FROM food ORDER BY food_name ASC");
$food_options = [];
while($row = mysqli_fetch_assoc($food_options_res)) $food_options[] = $row;

if(isset($_POST['save_booking'])){
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $customer_name = mysqli_real_escape_string($conn,$_POST['customer_name']);
    $phone = mysqli_real_escape_string($conn,$_POST['phone']);
    $booking_date = mysqli_real_escape_string($conn,$_POST['booking_date']);
    $booking_time = mysqli_real_escape_string($conn,$_POST['booking_time']);
    $people = intval($_POST['people']);

    $res = mysqli_query($conn,"SELECT SUM(people) AS total FROM booking WHERE booking_date='$booking_date' AND booking_time='$booking_time' AND id<>$booking_id");
    $row = mysqli_fetch_assoc($res);
    $total_people = $row['total'] ?? 0;

    if($total_people + $people > $max_people_per_slot){
        $error = "该时间段人数已满或超过限制";
    } else {
        if($booking_id>0){
            mysqli_query($conn,"UPDATE booking SET customer_name='$customer_name', phone='$phone', booking_date='$booking_date', booking_time='$booking_time', people=$people WHERE id=$booking_id");
        } else {
            mysqli_query($conn,"INSERT INTO booking(customer_name, phone, booking_date, booking_time, people, created_at) VALUES ('$customer_name','$phone','$booking_date','$booking_time',$people,NOW())");
            $booking_id = mysqli_insert_id($conn);
        }
        header("Location: booking.php?booking_id=$booking_id");
        exit;
    }
}

if(isset($_POST['save_order'])){
    $booking_id = intval($_POST['booking_id']);
    mysqli_query($conn,"DELETE FROM orders WHERE booking_id=$booking_id"); // 清空原订单
    foreach($_POST['food_id'] as $i=>$fid){
        $qty = intval($_POST['quantity'][$i]);
        $fid = intval($fid);
        if($fid>0 && $qty>0){
            mysqli_query($conn,"INSERT INTO orders(booking_id, food_id, quantity, order_time) VALUES($booking_id,$fid,$qty,NOW())");
        }
    }
    header("Location: booking.php?booking_id=$booking_id");
    exit;
}

$booking_id = $_GET['booking_id'] ?? 0;
$booking = [];
$order = [];
$total_price = 0;
if($booking_id){
    $res = mysqli_query($conn,"SELECT * FROM booking WHERE id=$booking_id");
    $booking = mysqli_fetch_assoc($res);

    $res = mysqli_query($conn,"SELECT f.food_name,f.image_path,o.quantity,f.price FROM orders o LEFT JOIN food f ON o.food_id=f.id WHERE booking_id=$booking_id");
    while($row=mysqli_fetch_assoc($res)){
        $order[]=$row;
        $total_price += $row['quantity']*$row['price'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Booking & Order</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
body { font-family:Arial,sans-serif; background:#f8f9fa; }
.container { max-width:800px; }
.food-row { display:flex; gap:10px; margin-bottom:5px; align-items:center; }
.food-row select, .food-row input { flex:1; }
.food-row img { width:50px; height:50px; object-fit:cover; border-radius:5px; }
@media print { body * { visibility:hidden; } #printContent, #printContent * { visibility:visible; } #printContent { position:absolute; left:0; top:0; width:100%; font-size:14px; } table { width:100%; border-collapse:collapse; } th, td { border:1px solid #000; padding:5px; } th { background:#eee; } }
</style>
</head>
<body class="container mt-5">

<h2><?= $booking_id ? 'Edit Booking' : 'New Booking' ?></h2>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<form method="post">
<input type="hidden" name="booking_id" value="<?= $booking_id ?>">
<div class="mb-3"><label>Name</label><input type="text" name="customer_name" class="form-control" value="<?= $booking['customer_name'] ?? '' ?>" required></div>
<div class="mb-3"><label>Phone</label><input type="text" name="phone" class="form-control" value="<?= $booking['phone'] ?? '' ?>" required></div>
<div class="mb-3"><label>Date</label><input type="date" name="booking_date" class="form-control" value="<?= $booking['booking_date'] ?? '' ?>" required></div>
<div class="mb-3"><label>Time</label>
<select name="booking_time" class="form-control" required>
<?php for($h=10;$h<=21;$h++){ foreach(['00','30'] as $m){ $time_str = sprintf('%02d:%s',$h,$m); $sel = ($booking['booking_time']??'')==$time_str ? 'selected':''; echo "<option value='$time_str' $sel>$time_str</option>"; } } ?>
</select>
</div>
<div class="mb-3"><label>People</label><input type="number" name="people" class="form-control" min="1" max="<?= $max_people_per_slot ?>" value="<?= $booking['people'] ?? 1 ?>" required></div>
<button class="btn btn-primary" type="submit" name="save_booking"><?= $booking_id ? 'Update Booking' : 'Book Now' ?></button>
</form>

<?php if($booking_id): ?>
<hr>
<h2>Order Menu for <?= htmlspecialchars($booking['customer_name']) ?></h2>
<form method="post">
<input type="hidden" name="booking_id" value="<?= $booking_id ?>">
<div id="foodContainer">
<?php if($order): ?>
<?php foreach($order as $o): ?>
<div class="food-row">
<img src="<?= htmlspecialchars($o['image_path']) ?>" alt="<?= htmlspecialchars($o['food_name']) ?>">
<select name="food_id[]" class="form-control" required>
<?php foreach($food_options as $f): $sel = $f['food_name']==$o['food_name']?'selected':''; ?>
<option value="<?= $f['id'] ?>" <?= $sel ?>><?= htmlspecialchars($f['food_name']) ?> (<?= $f['price'] ?>)</option>
<?php endforeach; ?>
</select>
<input type="number" name="quantity[]" class="form-control" min="1" value="<?= $o['quantity'] ?>" required>
<button type="button" class="btn btn-danger btn-sm remove-food"><i class="bi bi-trash"></i></button>
</div>
<?php endforeach; ?>
<?php else: ?>
<div class="food-row">
<select name="food_id[]" class="form-control" required>
<?php foreach($food_options as $f): ?>
<option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['food_name']) ?> (<?= $f['price'] ?>)</option>
<?php endforeach; ?>
</select>
<input type="number" name="quantity[]" class="form-control" min="1" value="1" required>
<button type="button" class="btn btn-danger btn-sm remove-food"><i class="bi bi-trash"></i></button>
</div>
<?php endif; ?>
</div>
<button type="button" class="btn btn-secondary btn-sm mt-2" id="addFoodRow">Add Another Food</button>
<button class="btn btn-success mt-2" type="submit" name="save_order">Save Order</button>
</form>

<?php if($order): ?>
<hr>
<h3>Order Summary</h3>
<div id="printContent" style="padding:20px; border:1px dashed #000; width:350px;">
<div style="text-align:center;">
<h2 style="margin:0"><?= htmlspecialchars($restaurant_name) ?></h2>
<p style="margin:0; font-size:12px"><?= htmlspecialchars($restaurant_address) ?></p>
<p style="margin:0; font-size:12px">Tel: <?= htmlspecialchars($restaurant_phone) ?></p>
<hr style="border:1px dashed #000;">
</div>
<p style="font-size:12px">Customer: <?= htmlspecialchars($booking['customer_name']) ?></p>
<p style="font-size:12px">Date: <?= $booking['booking_date'] ?> Time: <?= $booking['booking_time'] ?></p>
<table style="width:100%; font-size:12px; border-collapse:collapse;">
<thead>
<tr><th>Food</th><th>Qty</th><th>Price</th></tr>
</thead>
<tbody>
<?php foreach($order as $o): ?>
<tr>
<td><img src="<?= htmlspecialchars($o['image_path']) ?>" style="width:30px;height:30px;object-fit:cover;border-radius:3px;"> <?= htmlspecialchars($o['food_name']) ?></td>
<td style="text-align:center"><?= $o['quantity'] ?></td>
<td style="text-align:right"><?= $o['price']*$o['quantity'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr><th colspan="2" style="text-align:right;">Total</th><th style="text-align:right;"><?= $total_price ?></th></tr>
</tfoot>
</table>
<hr style="border:1px dashed #000;">
<p style="text-align:center; font-size:12px">Thank you for your order!</p>
</div>
<button class="btn btn-primary mt-2" onclick="window.print()">Print Receipt</button>
<?php endif; endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const container = document.getElementById('foodContainer');
document.getElementById('addFoodRow').addEventListener('click', ()=>{
    const row = document.createElement('div'); row.className='food-row';
    row.innerHTML=`<select name="food_id[]" class="form-control" required><?php foreach($food_options as $f): ?><option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['food_name']) ?> (<?= $f['price'] ?>)</option><?php endforeach; ?></select><input type="number" name="quantity[]" class="form-control" min="1" value="1" required><button type="button" class="btn btn-danger btn-sm remove-food"><i class="bi bi-trash"></i></button>`;
    container.appendChild(row);
});
document.addEventListener('click', e=>{if(e.target.closest('.remove-food')) e.target.closest('.food-row').remove();});
</script>
</body>
</html>

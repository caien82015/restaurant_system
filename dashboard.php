<?php
include 'db.php';

$restaurant_name = "Delicious Restaurant";
$restaurant_address = "123 Main Street, City";
$restaurant_phone = "012-3456789";
$restaurant_hours = "Mon-Sun: 10:00 AM - 10:00 PM";
$max_people_per_slot = 20;

if(isset($_GET['action']) && $_GET['action']=='get_remaining'){
    $date = $_GET['date'];
    $time = $_GET['time'];
    $res = mysqli_query($conn, "SELECT SUM(people) AS total FROM booking WHERE booking_date='$date' AND booking_time='$time'");
    $row = mysqli_fetch_assoc($res);
    $total = $row['total'] ?? 0;
    $remaining = max(0, $max_people_per_slot - $total);
    echo json_encode(['remaining'=>$remaining]);
    exit;
}

if(isset($_POST['save_booking'])){
    $id = intval($_POST['booking_id'] ?? 0);
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $booking_time = mysqli_real_escape_string($conn, $_POST['booking_time']);
    $people = intval($_POST['people']);

    
    $result = mysqli_query($conn, "SELECT SUM(people) AS total FROM booking WHERE booking_date='$booking_date' AND booking_time='$booking_time' AND id<>$id");
    $row = mysqli_fetch_assoc($result);
    $total_people = $row['total'] ?? 0;

    if(($total_people + $people) > $max_people_per_slot){
        echo "<script>alert('该时间段人数已满或超过限制，无法预约！'); window.location='dashboard.php';</script>";
        exit;
    }

    if($id>0){
        mysqli_query($conn, "UPDATE booking SET customer_name='$customer_name', phone='$phone', booking_date='$booking_date', booking_time='$booking_time', people=$people WHERE id=$id") or die(mysqli_error($conn));
    }else{
        mysqli_query($conn, "INSERT INTO booking (customer_name, phone, booking_date, booking_time, people, created_at)
        VALUES ('$customer_name','$phone','$booking_date','$booking_time',$people,NOW())") or die(mysqli_error($conn));
    }
    header("Location: dashboard.php");
    exit;
}

if(isset($_POST['save_order'])){
    $booking_id = intval($_POST['booking_id']);
    $food_ids = $_POST['food_id']; 
    $quantities = $_POST['quantity']; 

    foreach($food_ids as $index => $food_id){
        $food_id_int = intval($food_id);
        $quantity_int = intval($quantities[$index]);
        if($food_id_int > 0 && $quantity_int > 0){
            mysqli_query($conn, "INSERT INTO orders (booking_id, food_id, quantity, order_time)
                VALUES ($booking_id,$food_id_int,$quantity_int,NOW())") or die(mysqli_error($conn));
        }
    }
    header("Location: dashboard.php");
    exit;
}

if(isset($_GET['delete_booking'])){
    $id = intval($_GET['delete_booking']);
    mysqli_query($conn,"DELETE FROM booking WHERE id=$id");
    mysqli_query($conn,"DELETE FROM orders WHERE booking_id=$id");
    header("Location: dashboard.php");
    exit;
}

$bookings_res = mysqli_query($conn, "SELECT * FROM booking ORDER BY created_at DESC");
$bookings = [];
while($row = mysqli_fetch_assoc($bookings_res)){
    $bookings[] = $row;
}

$orders_res = mysqli_query($conn, "
SELECT b.id AS booking_id, b.customer_name,
IFNULL(GROUP_CONCAT(f.food_name,' x', o.quantity SEPARATOR ', '), '') AS foods,
IFNULL(SUM(f.price*o.quantity),0) AS total_price
FROM booking b
LEFT JOIN orders o ON b.id = o.booking_id
LEFT JOIN food f ON o.food_id = f.id
GROUP BY b.id
ORDER BY b.booking_date DESC
");
$orders = [];
while($row = mysqli_fetch_assoc($orders_res)){
    $orders[] = $row;
}

$booking_options_res = mysqli_query($conn, "SELECT id, customer_name FROM booking ORDER BY booking_date DESC");
$booking_options = [];
while($row=mysqli_fetch_assoc($booking_options_res)) $booking_options[]=$row;

$food_options_res = mysqli_query($conn, "SELECT id, food_name, price FROM food ORDER BY food_name ASC");
$food_options = [];
while($row=mysqli_fetch_assoc($food_options_res)) $food_options[]=$row;
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $restaurant_name ?> Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; }
        h1 { margin-bottom: 10px; color: #0d6efd; }
        .restaurant-info { background-color: #ffffff; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); padding: 15px 20px; margin-bottom: 30px; }
        .restaurant-info p { margin: 5px 0; font-size: 1rem; color: #333333; }
        .restaurant-info span { font-weight: bold; }
        .table-hover tbody tr:hover { background-color: #f1f7ff; }
        .booking-table thead { background-color: #0d6efd; color: white; }
        .order-table thead { background-color: #198754; color: white; }
        .btn i { margin-right: 5px; }
        .modal-body .mb-3 { margin-bottom: 1rem; }
        .mt-4 { margin-top: 2rem !important; }
        .food-row { display:flex; gap:10px; margin-bottom:5px; }
        .food-row select, .food-row input { flex:1; }
    </style>
</head>
<body class="container mt-5">

<div class="restaurant-info">
    <h1><?= htmlspecialchars($restaurant_name) ?></h1>
    <p>地址: <span><?= htmlspecialchars($restaurant_address) ?></span></p>
    <p>电话: <span><?= htmlspecialchars($restaurant_phone) ?></span></p>
    <p>营业时间: <span><?= htmlspecialchars($restaurant_hours) ?></span></p>
</div>

<h3>Bookings</h3>
<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#bookingModal">Add Booking</button>
<table class="table table-hover booking-table">
<thead>
<tr><th>ID</th><th>Name</th><th>Phone</th><th>Date</th><th>Time</th><th>People</th><th>Action</th></tr>
</thead>
<tbody>
<?php foreach($bookings as $b): ?>
<tr>
<td><?= $b['id'] ?></td>
<td><?= htmlspecialchars($b['customer_name']) ?></td>
<td><?= htmlspecialchars($b['phone']) ?></td>
<td><?= $b['booking_date'] ?></td>
<td><?= $b['booking_time'] ?></td>
<td><?= $b['people'] ?></td>
<td>
<a href="?delete_booking=<?= $b['id'] ?>" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i>Delete</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="modal fade" id="bookingModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="post" id="addBookingForm">
<div class="modal-header"><h5 class="modal-title">Add Booking</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-3"><label>Customer Name</label><input type="text" name="customer_name" class="form-control" required></div>
<div class="mb-3"><label>Phone</label><input type="text" name="phone" class="form-control" required></div>
<div class="mb-3"><label>Date</label><input type="date" name="booking_date" class="form-control" id="booking_date_add" required></div>
<div class="mb-3"><label>Time</label><input type="time" name="booking_time" class="form-control" id="booking_time_add" required></div>
<div class="mb-3"><label>People</label><input type="number" name="people" class="form-control" id="people_add" min="1" required>
<p>Remaining: <span id="remaining_add"><?= $max_people_per_slot ?></span></p>
</div>
</div>
<div class="modal-footer">
<button class="btn btn-primary" type="submit" name="save_booking">Add</button>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
</div>
</form></div></div></div>

<h3>Orders</h3>
<button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#orderModal">Add Order</button>
<table class="table table-hover order-table">
<thead>
<tr>
<th>Booking Name</th>
<th>Foods (Qty)</th>
<th>Total Price</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($orders as $o): ?>
<tr>
<td><?= htmlspecialchars($o['customer_name']) ?></td>
<td><?= htmlspecialchars($o['foods']) ?: 'No Order Yet' ?></td>
<td><?= $o['total_price'] ?></td>
<td>
<?php if($o['foods']): ?>
<a href="?delete_booking=<?= $o['booking_id'] ?>" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i>Delete</a>
<?php else: ?>
<span class="text-muted">No actions</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="modal fade" id="orderModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="post">
<div class="modal-header"><h5 class="modal-title">Add Order (Multi Food)</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-3">
<label>Booking</label>
<select name="booking_id" class="form-control" required>
<?php foreach($booking_options as $b_opt): ?>
<option value="<?= $b_opt['id'] ?>"><?= htmlspecialchars($b_opt['customer_name']) ?></option>
<?php endforeach; ?>
</select>
</div>

<div id="foodContainer">
<div class="food-row">
<select name="food_id[]" class="form-control" required>
<?php foreach($food_options as $f_opt): ?>
<option value="<?= $f_opt['id'] ?>"><?= htmlspecialchars($f_opt['food_name']) ?> (<?= $f_opt['price'] ?>)</option>
<?php endforeach; ?>
</select>
<input type="number" name="quantity[]" class="form-control" min="1" value="1" required>
<button type="button" class="btn btn-danger btn-sm remove-food"><i class="bi bi-trash"></i></button>
</div>
</div>

<button type="button" class="btn btn-secondary btn-sm mt-2" id="addFoodRow">Add Another Food</button>

</div>
<div class="modal-footer">
<button class="btn btn-success" type="submit" name="save_order">Add Order</button>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
</div>
</form></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>

const maxPeople = <?= $max_people_per_slot ?>;
function fetchRemaining(date,time,callback){fetch(`dashboard.php?action=get_remaining&date=${date}&time=${time}`).then(res=>res.json()).then(data=>callback(data.remaining)).catch(()=>callback(maxPeople));}
const dateAdd=document.getElementById('booking_date_add');
const timeAdd=document.getElementById('booking_time_add');
const peopleAdd=document.getElementById('people_add');
const remainingAdd=document.getElementById('remaining_add');
function updateRemainingAdd(){const date=dateAdd.value,time=timeAdd.value;if(date&&time){fetchRemaining(date,time,rem=>{remainingAdd.textContent=rem;if(parseInt(peopleAdd.value)>rem)peopleAdd.value=rem;});}}
dateAdd.addEventListener('change',updateRemainingAdd);
timeAdd.addEventListener('change',updateRemainingAdd);
peopleAdd.addEventListener('input',()=>{let rem=parseInt(remainingAdd.textContent);if(parseInt(peopleAdd.value)>rem)peopleAdd.value=rem;});

document.getElementById('addFoodRow').addEventListener('click',()=>{
    const container=document.getElementById('foodContainer');
    const row=document.createElement('div');row.className='food-row';
    row.innerHTML=`
        <select name="food_id[]" class="form-control" required>
            <?php foreach($food_options as $f_opt): ?>
            <option value="<?= $f_opt['id'] ?>"><?= htmlspecialchars($f_opt['food_name']) ?> (<?= $f_opt['price'] ?>)</option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="quantity[]" class="form-control" min="1" value="1" required>
        <button type="button" class="btn btn-danger btn-sm remove-food"><i class="bi bi-trash"></i></button>
    `;
    container.appendChild(row);
});
document.addEventListener('click',function(e){if(e.target.closest('.remove-food')){e.target.closest('.food-row').remove();}});
</script>
</body>
</html>

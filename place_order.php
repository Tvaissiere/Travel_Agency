<?php
session_start();
require_once 'includes/connection.php';
require_once 'includes/extras.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$type             = $_POST['type']             ?? '';
$item_id          = (int)($_POST['item_id']    ?? 0);
$persons          = (int)($_POST['persons']    ?? 1);
$base_cost        = (float)($_POST['base_cost']   ?? 0);
$extras_cost      = (float)($_POST['extras_cost'] ?? 0);
$total            = (float)($_POST['total']       ?? 0);
$return_flight_id = (int)($_POST['return_flight_id'] ?? 0);
$check_in         = $_POST['check_in']  ?? '';
$check_out        = $_POST['check_out'] ?? '';
$posted_extras    = $_POST['extras']    ?? [];

$selected_extras = [];
foreach ($posted_extras as $key) {
    if (isset($extras[$key])) $selected_extras[] = $key;
}

if (!in_array($type, ['flight', 'hotel', 'package']) || !$item_id) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error   = '';

if (isset($_POST['confirm_order'])) {
    $booking_ref = 'TA-' . strtoupper(substr(md5(uniqid()), 0, 8));
    $extras_str  = implode(',', $selected_extras);

    if ($type === 'flight') {
        $rf  = $return_flight_id ?: null;
        $ins = $conn->prepare("INSERT INTO flight_bookings
            (booking_ref, user_id, outbound_flight_id, return_flight_id, passengers,
             extras, base_cost, extras_cost, total_cost)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("siiiisddd",
            $booking_ref, $user_id, $item_id, $rf, $persons,
            $extras_str, $base_cost, $extras_cost, $total);

    } elseif ($type === 'hotel') {
        $ins = $conn->prepare("INSERT INTO hotel_bookings
            (booking_ref, user_id, hotel_id, check_in, check_out, rooms,
             extras, base_cost, extras_cost, total_cost)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("siissisddd",
            $booking_ref, $user_id, $item_id, $check_in, $check_out, $persons,
            $extras_str, $base_cost, $extras_cost, $total);

    } elseif ($type === 'package') {
        $ins = $conn->prepare("INSERT INTO package_bookings
            (booking_ref, user_id, package_id, people,
             extras, base_cost, extras_cost, total_cost)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("siiisddd",
            $booking_ref, $user_id, $item_id, $persons,
            $extras_str, $base_cost, $extras_cost, $total);
    }

    if ($ins->execute()) {
        $booking_id = $conn->insert_id;
        header("Location: payment.php?booking_id=$booking_id&type=$type&booking_ref=" . urlencode($booking_ref));
        exit;
    } else {
        $error = 'Something went wrong. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - Travel Agency</title>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <h1>Confirm Your Order</h1>

        <?php if ($error): ?>
            <p><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="type"        value="<?php echo htmlspecialchars($type); ?>">
            <input type="hidden" name="item_id"     value="<?php echo $item_id; ?>">
            <input type="hidden" name="persons"     value="<?php echo $persons; ?>">
            <input type="hidden" name="base_cost"   value="<?php echo $base_cost; ?>">
            <input type="hidden" name="extras_cost" value="<?php echo $extras_cost; ?>">
            <input type="hidden" name="total"       value="<?php echo $total; ?>">
            <?php if ($return_flight_id): ?>
                <input type="hidden" name="return_flight_id" value="<?php echo $return_flight_id; ?>">
            <?php endif; ?>
            <?php if ($check_in):  ?><input type="hidden" name="check_in"  value="<?php echo htmlspecialchars($check_in); ?>"><?php endif; ?>
            <?php if ($check_out): ?><input type="hidden" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>"><?php endif; ?>
            <?php foreach ($selected_extras as $key): ?>
                <input type="hidden" name="extras[]" value="<?php echo htmlspecialchars($key); ?>">
            <?php endforeach; ?>

            <h2>Order Total: &pound;<?php echo number_format($total, 2); ?></h2>

            <div>
                <button type="submit" name="confirm_order">Proceed to Payment</button>
            </div>
        </form>

        <p><a href="javascript:history.back()">Back to checkout</a></p>
    </main>

</body>
</html>

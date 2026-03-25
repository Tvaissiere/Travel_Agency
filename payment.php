<?php
session_start();
require_once 'includes/connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$booking_id  = (int)($_GET['booking_id']  ?? 0);
$type        = $_GET['type']              ?? '';
$booking_ref = $_GET['booking_ref']       ?? '';

$table_map = [
    'flight'  => 'flight_bookings',
    'hotel'   => 'hotel_bookings',
    'package' => 'package_bookings',
];

if (!$booking_id || !isset($table_map[$type])) {
    header('Location: index.php');
    exit;
}

$table = $table_map[$type];
$stmt  = $conn->prepare("SELECT * FROM $table WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_name   = trim($_POST['card_name']   ?? '');
    $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $card_expiry = trim($_POST['card_expiry'] ?? '');
    $card_cvv    = trim($_POST['card_cvv']    ?? '');

    if (!$card_name || strlen($card_number) < 16 || !$card_expiry || !$card_cvv) {
        $error = 'Please fill in all payment details correctly.';
    } else {
        $last_four = substr($card_number, -4);

        $ins = $conn->prepare("INSERT INTO payments
            (booking_ref, booking_type, booking_id, card_name, card_last_four, card_expiry, amount, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'completed')");
        $ins->bind_param("ssisssd",
            $booking['booking_ref'], $type, $booking_id,
            $card_name, $last_four, $card_expiry, $booking['total_cost']);

        if ($ins->execute()) {
            $upd = $conn->prepare("UPDATE $table SET status = 'confirmed' WHERE id = ?");
            $upd->bind_param("i", $booking_id);
            $upd->execute();

            header("Location: order_confirmation.php?booking_id=$booking_id&type=$type");
            exit;
        } else {
            $error = 'Payment failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Travel Agency</title>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <h1>Payment</h1>

        <p>Booking Reference: <?php echo htmlspecialchars($booking['booking_ref']); ?></p>
        <p>Amount to pay: &pound;<?php echo number_format($booking['total_cost'], 2); ?></p>

        <?php if ($error): ?>
            <p><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST">

            <div>
                <label for="card_name">Name on Card</label>
                <input type="text" id="card_name" name="card_name" required>
            </div>

            <div>
                <label for="card_number">Card Number</label>
                <input type="text" id="card_number" name="card_number" maxlength="19" placeholder="1234 5678 9012 3456" required>
            </div>

            <div>
                <label for="card_expiry">Expiry Date</label>
                <input type="text" id="card_expiry" name="card_expiry" maxlength="5" placeholder="MM/YY" required>
            </div>

            <div>
                <label for="card_cvv">CVV</label>
                <input type="text" id="card_cvv" name="card_cvv" maxlength="4" placeholder="123" required>
            </div>

            <div>
                <button type="submit">Pay &pound;<?php echo number_format($booking['total_cost'], 2); ?></button>
            </div>

        </form>

        <p><a href="javascript:history.back()">Back</a></p>
    </main>

</body>
</html>

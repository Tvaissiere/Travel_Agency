<?php
session_start();
require_once 'includes/connection.php';
require_once 'includes/extras.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$booking_id = (int)($_GET['booking_id'] ?? 0);
$type       = $_GET['type'] ?? '';

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

// Fetch the item details
$item          = null;
$return_flight = null;

if ($type === 'flight') {
    $stmt2 = $conn->prepare("SELECT * FROM flights WHERE id = ?");
    $stmt2->bind_param("i", $booking['outbound_flight_id']);
    $stmt2->execute();
    $item = $stmt2->get_result()->fetch_assoc();

    if ($booking['return_flight_id']) {
        $stmt3 = $conn->prepare("SELECT * FROM flights WHERE id = ?");
        $stmt3->bind_param("i", $booking['return_flight_id']);
        $stmt3->execute();
        $return_flight = $stmt3->get_result()->fetch_assoc();
    }
} elseif ($type === 'hotel') {
    $stmt2 = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt2->bind_param("i", $booking['hotel_id']);
    $stmt2->execute();
    $item = $stmt2->get_result()->fetch_assoc();
} elseif ($type === 'package') {
    $stmt2 = $conn->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt2->bind_param("i", $booking['package_id']);
    $stmt2->execute();
    $item = $stmt2->get_result()->fetch_assoc();
}

$booked_extras = $booking['extras'] ? explode(',', $booking['extras']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - Travel Agency</title>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <h1>Booking Confirmed</h1>

        <p>Thank you for your booking, <?php echo htmlspecialchars($_SESSION['user_first_name']); ?>!</p>
        <p>Your booking reference is: <strong><?php echo htmlspecialchars($booking['booking_ref']); ?></strong></p>

        <hr>

        <?php if ($type === 'flight' && $item): ?>

            <h2>Outbound Flight</h2>
            <p><?php echo htmlspecialchars($item['from_city']); ?> (<?php echo $item['from_airport_code']; ?>)
                &rarr;
                <?php echo htmlspecialchars($item['to_city']); ?> (<?php echo $item['to_airport_code']; ?>)
            </p>
            <p>Flight: <?php echo htmlspecialchars($item['flight_number']); ?></p>
            <p>Departs: <?php echo date('D d M Y, H:i', strtotime($item['departure_datetime'])); ?></p>
            <p>Arrives: <?php echo date('D d M Y, H:i', strtotime($item['arrival_datetime'])); ?></p>

            <?php if ($return_flight): ?>
                <h2>Return Flight</h2>
                <p><?php echo htmlspecialchars($return_flight['from_city']); ?> (<?php echo $return_flight['from_airport_code']; ?>)
                    &rarr;
                    <?php echo htmlspecialchars($return_flight['to_city']); ?> (<?php echo $return_flight['to_airport_code']; ?>)
                </p>
                <p>Flight: <?php echo htmlspecialchars($return_flight['flight_number']); ?></p>
                <p>Departs: <?php echo date('D d M Y, H:i', strtotime($return_flight['departure_datetime'])); ?></p>
                <p>Arrives: <?php echo date('D d M Y, H:i', strtotime($return_flight['arrival_datetime'])); ?></p>
            <?php endif; ?>

            <p>Passengers: <?php echo $booking['passengers']; ?></p>

        <?php elseif ($type === 'hotel' && $item): ?>

            <h2>Hotel</h2>
            <p><?php echo htmlspecialchars($item['name']); ?></p>
            <p><?php echo htmlspecialchars($item['city']); ?>, <?php echo htmlspecialchars($item['country']); ?></p>
            <p>Check In: <?php echo date('D d M Y', strtotime($booking['check_in'])); ?></p>
            <p>Check Out: <?php echo date('D d M Y', strtotime($booking['check_out'])); ?></p>
            <p>Rooms: <?php echo $booking['rooms']; ?></p>

        <?php elseif ($type === 'package' && $item): ?>

            <h2>Package</h2>
            <p><?php echo htmlspecialchars($item['name']); ?></p>
            <p><?php echo htmlspecialchars($item['from_city']); ?> &rarr; <?php echo htmlspecialchars($item['to_city']); ?></p>
            <p>Departure: <?php echo date('D d M Y', strtotime($item['departure_date'])); ?></p>
            <p>Duration: <?php echo $item['duration_nights']; ?> nights</p>
            <p>People: <?php echo $booking['people']; ?></p>

        <?php endif; ?>

        <?php if (!empty($booked_extras)): ?>
            <h2>Extras</h2>
            <?php foreach ($booked_extras as $key): ?>
                <?php if (isset($extras[$key])): ?>
                    <p><?php echo htmlspecialchars($extras[$key]['label']); ?> &mdash; &pound;<?php echo number_format($extras[$key]['price'], 2); ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <hr>
        <p>Base cost: &pound;<?php echo number_format($booking['base_cost'], 2); ?></p>
        <p>Extras: &pound;<?php echo number_format($booking['extras_cost'], 2); ?></p>
        <h2>Total paid: &pound;<?php echo number_format($booking['total_cost'], 2); ?></h2>

        <p><a href="index.php">Back to Home</a></p>
    </main>

</body>
</html>

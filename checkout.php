<?php
session_start();
require_once 'includes/connection.php';
require_once 'includes/extras.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$type    = $_POST['type']    ?? '';
$item_id = (int)($_POST['item_id'] ?? 0);

if (!$type || !$item_id) {
    header('Location: index.php');
    exit;
}

$item           = null;
$return_flight  = null;
$persons        = 1;
$nights         = 0;
$base_cost      = 0.00;
$extras_cost    = 0.00;
$selected_extras = [];

// Fetch selected extras
$posted_extras = $_POST['extras'] ?? [];
foreach ($posted_extras as $key) {
    if (isset($extras[$key])) {
        $selected_extras[$key] = $extras[$key];
    }
}

if ($type === 'flight') {

    $persons          = max(1, (int)($_POST['passengers'] ?? 1));
    $return_flight_id = (int)($_POST['return_flight_id'] ?? 0);

    $stmt = $conn->prepare("SELECT * FROM flights WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    if ($return_flight_id) {
        $stmt2 = $conn->prepare("SELECT * FROM flights WHERE id = ?");
        $stmt2->bind_param("i", $return_flight_id);
        $stmt2->execute();
        $return_flight = $stmt2->get_result()->fetch_assoc();
    }

    $base_cost = $item['price'] * $persons;
    if ($return_flight) {
        $base_cost += $return_flight['price'] * $persons;
    }

} elseif ($type === 'hotel') {

    $persons   = max(1, (int)($_POST['rooms'] ?? 1));
    $check_in  = $_POST['check_in']  ?? '';
    $check_out = $_POST['check_out'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    if ($check_in && $check_out) {
        $nights = (int)((strtotime($check_out) - strtotime($check_in)) / 86400);
        if ($nights < 1) $nights = 1;
    }

    $base_cost = $item['price_per_night'] * $nights * $persons;

} elseif ($type === 'package') {

    $persons = max(1, (int)($_POST['people'] ?? 1));

    $stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    $base_cost = $item['price_per_person'] * $persons;
}

if (!$item) {
    header('Location: index.php');
    exit;
}

foreach ($selected_extras as $extra) {
    $extras_cost += $extra['price'];
}

$total = $base_cost + $extras_cost;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Travel Agency</title>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <h1>Order Summary</h1>

        <?php if ($type === 'flight'): ?>

            <h2>Outbound Flight</h2>
            <p><?php echo htmlspecialchars($item['from_city']); ?> (<?php echo $item['from_airport_code']; ?>)
                &rarr;
                <?php echo htmlspecialchars($item['to_city']); ?> (<?php echo $item['to_airport_code']; ?>)
            </p>
            <p>Flight: <?php echo htmlspecialchars($item['flight_number']); ?></p>
            <p>Departs: <?php echo date('D d M Y, H:i', strtotime($item['departure_datetime'])); ?></p>
            <p>Arrives: <?php echo date('D d M Y, H:i', strtotime($item['arrival_datetime'])); ?></p>
            <p>Passengers: <?php echo $persons; ?></p>
            <p>Cost: &pound;<?php echo number_format($item['price'] * $persons, 2); ?></p>

            <?php if ($return_flight): ?>
                <h2>Return Flight</h2>
                <p><?php echo htmlspecialchars($return_flight['from_city']); ?> (<?php echo $return_flight['from_airport_code']; ?>)
                    &rarr;
                    <?php echo htmlspecialchars($return_flight['to_city']); ?> (<?php echo $return_flight['to_airport_code']; ?>)
                </p>
                <p>Flight: <?php echo htmlspecialchars($return_flight['flight_number']); ?></p>
                <p>Departs: <?php echo date('D d M Y, H:i', strtotime($return_flight['departure_datetime'])); ?></p>
                <p>Arrives: <?php echo date('D d M Y, H:i', strtotime($return_flight['arrival_datetime'])); ?></p>
                <p>Cost: &pound;<?php echo number_format($return_flight['price'] * $persons, 2); ?></p>
            <?php endif; ?>

        <?php elseif ($type === 'hotel'): ?>

            <h2>Hotel</h2>
            <p><?php echo htmlspecialchars($item['name']); ?></p>
            <p><?php echo htmlspecialchars($item['city']); ?>, <?php echo htmlspecialchars($item['country']); ?></p>
            <p>Stars: <?php echo str_repeat('★', $item['star_rating']); ?></p>
            <p>Check In: <?php echo date('D d M Y', strtotime($check_in)); ?></p>
            <p>Check Out: <?php echo date('D d M Y', strtotime($check_out)); ?></p>
            <p>Nights: <?php echo $nights; ?></p>
            <p>Rooms: <?php echo $persons; ?></p>
            <p>Cost: &pound;<?php echo number_format($base_cost, 2); ?> (<?php echo $nights; ?> nights &times; <?php echo $persons; ?> rooms &times; &pound;<?php echo number_format($item['price_per_night'], 2); ?>)</p>

        <?php elseif ($type === 'package'): ?>

            <h2>Package</h2>
            <p><?php echo htmlspecialchars($item['name']); ?></p>
            <p><?php echo htmlspecialchars($item['from_city']); ?> &rarr; <?php echo htmlspecialchars($item['to_city']); ?></p>
            <p>Departure: <?php echo date('D d M Y', strtotime($item['departure_date'])); ?></p>
            <p>Duration: <?php echo $item['duration_nights']; ?> nights</p>
            <p>People: <?php echo $persons; ?></p>
            <p>Cost: &pound;<?php echo number_format($base_cost, 2); ?> (&pound;<?php echo number_format($item['price_per_person'], 2); ?> &times; <?php echo $persons; ?>)</p>

        <?php endif; ?>

        <?php if (!empty($selected_extras)): ?>
            <h2>Extras</h2>
            <?php foreach ($selected_extras as $extra): ?>
                <p><?php echo htmlspecialchars($extra['label']); ?>: &pound;<?php echo number_format($extra['price'], 2); ?></p>
            <?php endforeach; ?>
        <?php endif; ?>

        <hr>
        <h2>Total: &pound;<?php echo number_format($total, 2); ?></h2>

        <form action="place_order.php" method="POST">
            <input type="hidden" name="type"             value="<?php echo htmlspecialchars($type); ?>">
            <input type="hidden" name="item_id"          value="<?php echo $item_id; ?>">
            <input type="hidden" name="persons"          value="<?php echo $persons; ?>">
            <input type="hidden" name="base_cost"        value="<?php echo $base_cost; ?>">
            <input type="hidden" name="extras_cost"      value="<?php echo $extras_cost; ?>">
            <input type="hidden" name="total"            value="<?php echo $total; ?>">
            <?php if ($return_flight): ?>
                <input type="hidden" name="return_flight_id" value="<?php echo $return_flight['id']; ?>">
            <?php endif; ?>
            <?php if ($type === 'hotel'): ?>
                <input type="hidden" name="rooms"     value="<?php echo $persons; ?>">
                <input type="hidden" name="check_in"  value="<?php echo htmlspecialchars($check_in); ?>">
                <input type="hidden" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>">
            <?php endif; ?>
            <?php foreach (array_keys($selected_extras) as $key): ?>
                <input type="hidden" name="extras[]" value="<?php echo htmlspecialchars($key); ?>">
            <?php endforeach; ?>
            <button type="submit">Place Order</button>
        </form>

        <p><a href="javascript:history.back()">Back to booking</a></p>
    </main>

</body>
</html>

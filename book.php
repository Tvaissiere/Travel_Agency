<?php
session_start();
require_once 'includes/connection.php';

$type       = $_GET['type'] ?? '';
$id         = (int)($_GET['id'] ?? 0);
$trip_type   = $_GET['trip_type']  ?? 'one_way';
$passengers  = max(1, (int)($_GET['passengers'] ?? 1));
$from        = trim($_GET['from'] ?? '');
$to          = trim($_GET['to']   ?? '');
$return_date = $_GET['return_date'] ?? '';
$rooms       = max(1, (int)($_GET['rooms']     ?? 1));
$check_in    = $_GET['check_in']   ?? '';
$check_out   = $_GET['check_out']  ?? '';

if (!$type || !$id) {
    header('Location: index.php');
    exit;
}

$item = null;
$return_flights = [];

if ($type === 'flight') {
    $stmt = $conn->prepare("SELECT * FROM flights WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    if ($trip_type === 'return') {
        $sql    = "SELECT * FROM flights WHERE seats_available >= ? AND from_city LIKE ? AND to_city LIKE ?";
        $params = [$passengers, "%$to%", "%$from%"];
        $types  = "iss";

        if ($return_date !== '') {
            $sql    .= " AND DATE(departure_datetime) = ?";
            $params[] = $return_date;
            $types  .= "s";
        }

        $sql .= " ORDER BY departure_datetime ASC";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param($types, ...$params);
        $stmt2->execute();
        $return_flights = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    }

} elseif ($type === 'hotel') {
    $stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

} elseif ($type === 'package') {
    $stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
}

if (!$item) {
    header('Location: index.php');
    exit;
}

require_once 'includes/extras.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book - Travel Agency</title>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <h1>Your Booking</h1>

        <form action="checkout.php" method="POST">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <input type="hidden" name="item_id" value="<?php echo $id; ?>">

            <?php if ($type === 'flight'): ?>

                <h2>Outbound Flight</h2>
                <p><?php echo htmlspecialchars($item['from_city']); ?> (<?php echo $item['from_airport_code']; ?>)
                    &rarr;
                    <?php echo htmlspecialchars($item['to_city']); ?> (<?php echo $item['to_airport_code']; ?>)
                </p>
                <p>Flight: <?php echo htmlspecialchars($item['flight_number']); ?></p>
                <p>Departs: <?php echo date('D d M Y, H:i', strtotime($item['departure_datetime'])); ?></p>
                <p>Arrives: <?php echo date('D d M Y, H:i', strtotime($item['arrival_datetime'])); ?></p>
                <p>Price: &pound;<?php echo number_format($item['price'], 2); ?> per person</p>

                <?php if ($trip_type === 'return'): ?>
                    <h2>Select Return Flight</h2>
                    <?php if (empty($return_flights)): ?>
                        <p>No return flights available for the selected dates.</p>
                    <?php else: ?>
                        <?php foreach ($return_flights as $rf): ?>
                            <div>
                                <label>
                                    <input type="radio" name="return_flight_id" value="<?php echo $rf['id']; ?>" required>
                                    <?php echo htmlspecialchars($rf['from_city']); ?> (<?php echo $rf['from_airport_code']; ?>)
                                    &rarr;
                                    <?php echo htmlspecialchars($rf['to_city']); ?> (<?php echo $rf['to_airport_code']; ?>)
                                    &mdash; <?php echo htmlspecialchars($rf['flight_number']); ?>
                                    &mdash; <?php echo date('D d M Y, H:i', strtotime($rf['departure_datetime'])); ?>
                                    &mdash; &pound;<?php echo number_format($rf['price'], 2); ?> per person
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <h2>Passengers</h2>
                <div>
                    <label for="passengers">Number of Passengers</label>
                    <input type="number" id="passengers" name="passengers" value="<?php echo $passengers; ?>" min="1" max="<?php echo $item['seats_available']; ?>">
                </div>

            <?php elseif ($type === 'hotel'): ?>

                <h2>Hotel</h2>
                <p><?php echo htmlspecialchars($item['name']); ?></p>
                <p><?php echo htmlspecialchars($item['city']); ?>, <?php echo htmlspecialchars($item['country']); ?></p>
                <p>Stars: <?php echo str_repeat('★', $item['star_rating']); ?></p>
                <p>Price: &pound;<?php echo number_format($item['price_per_night'], 2); ?> per night</p>

                <h2>Stay Details</h2>
                <div>
                    <label for="check_in">Check In</label>
                    <input type="date" id="check_in" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>" required>
                </div>
                <div>
                    <label for="check_out">Check Out</label>
                    <input type="date" id="check_out" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>" required>
                </div>
                <div>
                    <label for="rooms">Rooms</label>
                    <input type="number" id="rooms" name="rooms" value="<?php echo $rooms; ?>" min="1" max="<?php echo $item['rooms_available']; ?>">
                </div>

            <?php elseif ($type === 'package'): ?>

                <h2>Package</h2>
                <p><?php echo htmlspecialchars($item['name']); ?></p>
                <p><?php echo htmlspecialchars($item['from_city']); ?> &rarr; <?php echo htmlspecialchars($item['to_city']); ?></p>
                <p>Departure: <?php echo date('D d M Y', strtotime($item['departure_date'])); ?></p>
                <p>Duration: <?php echo $item['duration_nights']; ?> nights</p>
                <p>Price: &pound;<?php echo number_format($item['price_per_person'], 2); ?> per person</p>

                <h2>Travellers</h2>
                <div>
                    <label for="people">Number of People</label>
                    <input type="number" id="people" name="people" value="1" min="1" max="<?php echo $item['max_people'] - $item['people_booked']; ?>">
                </div>

            <?php endif; ?>

            <h2>Extras</h2>
            <?php foreach ($extras as $key => $extra): ?>
                <?php if ($type === 'hotel' && in_array($key, ['priority_boarding', 'seat_selection'])): continue; endif; ?>
                <div>
                    <label>
                        <input type="checkbox" name="extras[]" value="<?php echo $key; ?>">
                        <?php echo htmlspecialchars($extra['label']); ?> &mdash; &pound;<?php echo number_format($extra['price'], 2); ?>
                    </label>
                </div>
            <?php endforeach; ?>

            <div>
                <button type="submit">Continue to Checkout</button>
            </div>

        </form>

        <p><a href="javascript:history.back()">Back to results</a></p>
    </main>

</body>
</html>

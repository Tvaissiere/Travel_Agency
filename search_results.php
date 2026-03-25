<?php
session_start();
require_once 'includes/connection.php';

$type    = $_GET['type'] ?? 'flights';
$results = [];
$error   = '';

if ($type === 'flights') {

    $from       = trim($_GET['from'] ?? '');
    $to         = trim($_GET['to'] ?? '');
    $depart     = $_GET['depart'] ?? '';
    $return     = $_GET['return'] ?? '';
    $passengers = max(1, (int)($_GET['passengers'] ?? 1));
    $trip_type  = $_GET['trip_type'] ?? 'return';

    // Outbound flights
    $sql    = "SELECT * FROM flights WHERE seats_available >= ?";
    $params = [$passengers];
    $types  = "i";

    if ($from !== '') { $sql .= " AND from_city LIKE ?"; $params[] = "%$from%"; $types .= "s"; }
    if ($to !== '')   { $sql .= " AND to_city LIKE ?";   $params[] = "%$to%";   $types .= "s"; }
    if ($depart !== '') { $sql .= " AND DATE(departure_datetime) = ?"; $params[] = $depart; $types .= "s"; }

    $sql .= " ORDER BY departure_datetime ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Return flights (only for return trips)
    $return_results = [];
    if ($trip_type === 'return') {
        $sql2    = "SELECT * FROM flights WHERE seats_available >= ?";
        $params2 = [$passengers];
        $types2  = "i";

        if ($to !== '')   { $sql2 .= " AND from_city LIKE ?"; $params2[] = "%$to%";   $types2 .= "s"; }
        if ($from !== '') { $sql2 .= " AND to_city LIKE ?";   $params2[] = "%$from%"; $types2 .= "s"; }
        if ($return !== '') { $sql2 .= " AND DATE(departure_datetime) = ?"; $params2[] = $return; $types2 .= "s"; }

        $sql2 .= " ORDER BY departure_datetime ASC";

        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param($types2, ...$params2);
        $stmt2->execute();
        $return_results = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    }

} elseif ($type === 'hotels') {

    $destination = trim($_GET['destination'] ?? '');
    $rooms       = max(1, (int)($_GET['rooms'] ?? 1));
    $check_in    = $_GET['check_in']  ?? '';
    $check_out   = $_GET['check_out'] ?? '';

    $sql    = "SELECT * FROM hotels WHERE rooms_available > 0";
    $params = [];
    $types  = "";

    if ($destination !== '') {
        $sql    .= " AND (city LIKE ? OR country LIKE ? OR name LIKE ?)";
        $params[] = "%$destination%";
        $params[] = "%$destination%";
        $params[] = "%$destination%";
        $types  .= "sss";
    }

    $sql .= " ORDER BY star_rating DESC, price_per_night ASC";

    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $results = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

} elseif ($type === 'packages') {

    $from   = trim($_GET['from'] ?? '');
    $to     = trim($_GET['to'] ?? '');
    $when   = $_GET['when'] ?? '';
    $people = max(1, (int)($_GET['people'] ?? 1));

    $sql    = "SELECT * FROM packages WHERE (max_people - people_booked) >= ?";
    $params = [$people];
    $types  = "i";

    if ($from !== '') {
        $sql    .= " AND from_city LIKE ?";
        $params[] = "%$from%";
        $types  .= "s";
    }
    if ($to !== '') {
        $sql    .= " AND to_city LIKE ?";
        $params[] = "%$to%";
        $types  .= "s";
    }
    if ($when !== '') {
        $sql    .= " AND departure_date >= ?";
        $params[] = $when;
        $types  .= "s";
    }

    $sql .= " ORDER BY departure_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Travel Agency</title>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <h1>
            <?php
            if ($type === 'flights')  echo 'Available Flights';
            if ($type === 'hotels')   echo 'Available Hotels';
            if ($type === 'packages') echo 'Available Packages';
            ?>
        </h1>

        <?php if (empty($results) && $type !== 'flights'): ?>
            <p>No results found. <a href="index.php">Search again</a>.</p>

        <?php elseif ($type === 'flights'): ?>

            <h2>Outbound Flights</h2>
            <?php if (empty($results)): ?>
                <p>No outbound flights found for your search.</p>
            <?php else: ?>
                <?php foreach ($results as $flight): ?>
                    <div>
                        <h3><?php echo htmlspecialchars($flight['from_city']); ?> (<?php echo $flight['from_airport_code']; ?>)
                            &rarr;
                            <?php echo htmlspecialchars($flight['to_city']); ?> (<?php echo $flight['to_airport_code']; ?>)
                        </h3>
                        <p>Flight: <?php echo htmlspecialchars($flight['flight_number']); ?></p>
                        <p>Departs: <?php echo date('D d M Y, H:i', strtotime($flight['departure_datetime'])); ?></p>
                        <p>Arrives: <?php echo date('D d M Y, H:i', strtotime($flight['arrival_datetime'])); ?></p>
                        <p>Seats available: <?php echo $flight['seats_available']; ?></p>
                        <p>Price: &pound;<?php echo number_format($flight['price'], 2); ?> per person</p>
                        <a href="book.php?type=flight&id=<?php echo $flight['id']; ?>&trip_type=<?php echo urlencode($trip_type); ?>&passengers=<?php echo $passengers; ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&return_date=<?php echo urlencode($return); ?>">Book Now</a>
                    </div>
                    <hr>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($trip_type === 'return'): ?>
                <h2>Return Flights</h2>
                <?php if (empty($return_results)): ?>
                    <p>No return flights found for your search.</p>
                <?php else: ?>
                    <?php foreach ($return_results as $flight): ?>
                        <div>
                            <h3><?php echo htmlspecialchars($flight['from_city']); ?> (<?php echo $flight['from_airport_code']; ?>)
                                &rarr;
                                <?php echo htmlspecialchars($flight['to_city']); ?> (<?php echo $flight['to_airport_code']; ?>)
                            </h3>
                            <p>Flight: <?php echo htmlspecialchars($flight['flight_number']); ?></p>
                            <p>Departs: <?php echo date('D d M Y, H:i', strtotime($flight['departure_datetime'])); ?></p>
                            <p>Arrives: <?php echo date('D d M Y, H:i', strtotime($flight['arrival_datetime'])); ?></p>
                            <p>Seats available: <?php echo $flight['seats_available']; ?></p>
                            <p>Price: &pound;<?php echo number_format($flight['price'], 2); ?> per person</p>
                        </div>
                        <hr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>

        <?php elseif ($type === 'hotels'): ?>
            <?php foreach ($results as $hotel): ?>
                <div>
                    <h2><?php echo htmlspecialchars($hotel['name']); ?></h2>
                    <p><?php echo htmlspecialchars($hotel['city']); ?>, <?php echo htmlspecialchars($hotel['country']); ?></p>
                    <p>Stars: <?php echo str_repeat('★', $hotel['star_rating']); ?></p>
                    <p>Rooms available: <?php echo $hotel['rooms_available']; ?></p>
                    <p>Price: &pound;<?php echo number_format($hotel['price_per_night'], 2); ?> per night</p>
                    <a href="book.php?type=hotel&id=<?php echo $hotel['id']; ?>&rooms=<?php echo $rooms; ?>&check_in=<?php echo urlencode($check_in); ?>&check_out=<?php echo urlencode($check_out); ?>">Book Now</a>
                </div>
                <hr>
            <?php endforeach; ?>

        <?php elseif ($type === 'packages'): ?>
            <?php foreach ($results as $package): ?>
                <div>
                    <h2><?php echo htmlspecialchars($package['name']); ?></h2>
                    <p><?php echo htmlspecialchars($package['from_city']); ?> &rarr; <?php echo htmlspecialchars($package['to_city']); ?></p>
                    <p>Departure: <?php echo date('D d M Y', strtotime($package['departure_date'])); ?></p>
                    <p>Duration: <?php echo $package['duration_nights']; ?> nights</p>
                    <p>Spaces left: <?php echo $package['max_people'] - $package['people_booked']; ?></p>
                    <p>Price: &pound;<?php echo number_format($package['price_per_person'], 2); ?> per person</p>
                    <a href="book.php?type=package&id=<?php echo $package['id']; ?>">Book Now</a>
                </div>
                <hr>
            <?php endforeach; ?>

        <?php endif; ?>

        <p><a href="index.php">Back to search</a></p>
    </main>

</body>
</html>

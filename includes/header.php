<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<header>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="flights.php">Flights</a></li>
            <li><a href="hotels.php">Hotels</a></li>
            <li><a href="packages.php">Holiday Packages</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </nav>

    <div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="account.php"><?php echo htmlspecialchars($_SESSION['user_first_name']); ?></a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>
</header>

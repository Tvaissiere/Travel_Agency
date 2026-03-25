<?php
require_once 'includes/connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $dob        = $_POST['dob'];
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'An account with that email already exists.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, date_of_birth, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->bind_param("ssssss", $first_name, $last_name, $email, $phone, $dob, $password_hash);

            if ($insert->execute()) {
                $success = 'Account created successfully. <a href="login.php">Login here</a>.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Travel Agency</title>
</head>
<body>

    <main>
        <h1>Create an Account</h1>

        <?php if ($error): ?>
            <p><?php echo $error; ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p><?php echo $success; ?></p>
        <?php else: ?>
        <form action="register.php" method="POST">

            <div>
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>

            <div>
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>

            <div>
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div>
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required>
            </div>

            <div>
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" required>
            </div>

            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div>
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <div>
                <label>
                    <input type="checkbox" name="terms" required>
                    I agree to the <a href="terms.php">Terms and Conditions</a>
                </label>
            </div>

            <div>
                <button type="submit">Create My Account</button>
            </div>

        </form>
        <?php endif; ?>
        <div>
            <a href="login.php">Already have an account? Login here</a>
        </div>
    </main>

</body>
</html>

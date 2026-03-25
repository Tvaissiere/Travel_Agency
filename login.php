<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, first_name, password_hash FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $first_name, $password_hash);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $password_hash)) {
        $_SESSION['user_id']         = $id;
        $_SESSION['user_first_name'] = $first_name;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Travel Agency</title>
</head>
<body>

    <main>
        <h1>Login</h1>

        <?php if ($error): ?>
            <p><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">

            <div>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div>
                <button type="submit">Login</button>
            </div>

        </form>

        <div>
            <a href="forgot_password.php">Forgot your password?</a>
        </div>

        <div>
            <a href="register.php">Don't have an account? Sign up</a>
        </div>
    </main>

</body>
</html>

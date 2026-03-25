<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Travel Agency</title>
</head>
<body>

    <header>
        <div>
            <a href="login.php">Login</a>
        </div>
    </header>

    <main>
        <h1>Login</h1>

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

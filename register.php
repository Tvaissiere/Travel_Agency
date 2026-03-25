<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Travel Agency</title>
</head>
<body>

    <header>
        <div>
            <a href="login.php">Login</a>
        </div>
    </header>

    <main>
        <h1>Create an Account</h1>

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

        <div>
            <a href="login.php">Already have an account? Login here</a>
        </div>
    </main>

</body>
</html>

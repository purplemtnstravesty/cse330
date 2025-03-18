<?php
session_start();

// Redirect the user if already logged in.
if (isset($_SESSION['user_id'])) {
    header("Location: calendar.php");
    exit;
}

require 'database.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['username']) && !empty($_POST['password'])) {
        // Retrieve and sanitize user input
        $user = trim($_POST['username']);
        $pwd_guess = $_POST['password'];

        // Prepare the SQL statement to fetch user data
        if ($stmt = $mysqli->prepare("SELECT COUNT(*), user_id, hashed_password FROM Users WHERE username = ?")) {
            $stmt->bind_param('s', $user);
            $stmt->execute();

            // Bind the results to variables
            $stmt->bind_result($cnt, $user_id, $pwd_hash);
            $stmt->fetch();
            $stmt->close();

            // Check if a matching user was found and the password is correct
            if ($cnt == 1 && password_verify($pwd_guess, $pwd_hash)) {
                // Login successful, set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $user;
                header("Location: calendar.php");
                exit;
            } else {
                $error = "Invalid username or password!";
            }
        } else {
            $error = "Database error: " . $mysqli->error;
        }
    } else {
        $error = "Please enter both username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Calendar</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/dark.css">
</head>
<body>
    <header>
        <h1>Login to calendar</h1>
    </header>
    <main>
        <?php if (!empty($error)) : ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
        
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
        
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here!</a></p>
        <p><a href="calendar.php">View calendar as guest</a></p>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Calendar App</p>
    </footer>
</body>
</html>
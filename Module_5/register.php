<?php
session_start();

// Redirect if user is already logged in.
if (isset($_SESSION['user_id'])) {
    header(header: "Location: index.php");
    exit;
}

require 'database.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Make sure all required fields are provided.
    if (!empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['confirm_password'])) {
        $username = trim(string: $_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify that the two passwords match.
        if ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } else {
            // Check if the username already exists.
            if ($stmt = $mysqli->prepare(query: "SELECT COUNT(*) FROM Users WHERE username = ?")) {
                $stmt->bind_param(types: 's', var: $username);
                $stmt->execute();
                $stmt->bind_result(var: $user_count);
                $stmt->fetch();
                $stmt->close();

                if ($user_count > 0) {
                    $error = "Username already exists. Please choose another.";
                } else {
                    // Hash the password using a secure algorithm (PASSWORD_DEFAULT).
                    $hashed_password = password_hash(password: $password, algo: PASSWORD_DEFAULT);

                    // Insert the new user into the database.
                    if ($insert_stmt = $mysqli->prepare(query: "INSERT INTO Users (username, hashed_password) VALUES (?, ?)")) {
                        $insert_stmt->bind_param(types: 'ss', var: $username, vars: $hashed_password);
                        if ($insert_stmt->execute()) {
                            // Optionally, log the user in automatically after registration.
                            $_SESSION['user_id'] = $insert_stmt->insert_id;
                            $_SESSION['username'] = $username;
                            header(header: "Location: index.php");
                            exit;
                        } else {
                            $error = "Registration failed. Please try again.";
                        }
                        $insert_stmt->close();
                    } else {
                        $error = "Database error. Please try again later.";
                    }
                }
            } else {
                $error = "Database error. Please try again later.";
            }
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - My Reddit Clone</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Register for My Reddit Clone</h1>
    </header>
    <main>
        <?php if (!empty($error)) : ?>
            <p style="color:red;"><?php echo htmlspecialchars(string: $error); ?></p>
        <?php endif; ?>
        <form action="register.php" method="post">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here!</a></p>
    </main>
    <footer>
        <p>&copy; <?php echo date(format: "Y"); ?> My Reddit Clone</p>
    </footer>
</body>
</html>
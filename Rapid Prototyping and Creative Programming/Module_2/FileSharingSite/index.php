<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);

#sanitize user input for username as security feature
if (!ctype_alnum($username) || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $error = "Invalid username format.";
} else {
    #read the users.text file in private directory
    #removes the newline character at the end of each line
    #ignores any empty lines in the file
    $users = file('/var/www/users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
    #Check if username exists in the file
    if (in_array($username, $users)) {
        $_SESSION['username'] = $username;
        header('Location: Dashboard.php');
        exit();
    } else {
        $error = "Username not found.";
    }
}
/*after logging in to index.php users will arrive at dashboard.php
users.txt contains: jess, isa, bob
Scenario 1:
User enters: "jess"
Result: Successful login, redirected to dashboard.php

Scenario 2:
User enters: "alice"
Result: Error message "Username not found"*/
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="Index.css">
    </head>
<body>

<div class="login-container">
    <h2>Login</h2>
    
    <?php if (!empty($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="index.php" method="POST">
        <input type="text" name="username" placeholder="Enter Username" required>
        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>
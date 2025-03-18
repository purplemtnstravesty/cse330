<?php
session_start();

// Only allow access to logged-in users for submitting a story.
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page with a message.
    header("Location: login.php?message=" . urlencode("Please login or register to submit a story."));
    exit;
}

require 'database.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve the terms checkbox value.
    $terms = filter_input(INPUT_POST, "terms", FILTER_VALIDATE_BOOLEAN);
    if (!$terms) {
        $error = "Must accept terms of agreement and conditions.";
    }
    
    if (empty($_POST['title'])) {
        $error = "Title is required.";
    } else {
        // Use the title from the form and store it in the 'name' column.
        $name = trim($_POST["title"]);
        $body = isset($_POST['body']) ? trim($_POST['body']) : "";
        $link = isset($_POST['link']) ? trim($_POST['link']) : "";
        
        // Retrieve and validate the category from the form drop down.
        $category = filter_input(INPUT_POST, "category", FILTER_VALIDATE_INT);
        if ($category === null || $category === false) {
            $category = 1; // Default value (e.g., "None")
        }
        
        // Enforce character limit on the body.
        if (strlen($body) > 2048) {
            $error = "The body must be 2048 characters or fewer.";
        }
        
        // Validate the URL if provided.
        if (!empty($link) && !filter_var($link, FILTER_VALIDATE_URL)) {
            $error = "Please provide a valid URL.";
        }
        
        if (empty($error)) {
            $user_id = $_SESSION['user_id'];
            // Capture the current timestamp for a DATETIME column.
            $submitted_at = date("Y-m-d H:i:s");
            
            // Define the SQL query to insert the story.
            $sql = "INSERT INTO story (name, body, link, category, user_ID, submitted_at) VALUES (?, ?, ?, ?, ?, ?)";
            
            if ($stmt = $mysqli->prepare($sql)) {
                // Bind parameters: "sssiis" means:
                // string (name), string (body), string (link), integer (category), integer (user_ID), string (submitted_at)
                $stmt->bind_param("sssiis", $name, $body, $link, $category, $user_id, $submitted_at);
                if ($stmt->execute()) {
                    $success = "Story submitted successfully!";
                } else {
                    $error = "Error submitting the story. Please try again.";
                }
                $stmt->close();
            } else {
                $error = "Database error. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Story - NewsAggDB</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/dark.css">
</head>
<body>
    <header>
        <h1>Submit a New Story</h1>
        <nav>
            <a href="dashboard.php">Home</a> | <a href="logout.php">Logout</a>
        </nav>
    </header>
    <main>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form action="submission.php" method="post">
            <div>
                <label for="title">Title:</label>
                <input type="text" name="title" id="title" required>
            </div>
            <div>
                <label for="link">Link (URL):</label>
                <input type="url" name="link" id="link">
            </div>
            <div>
                <label for="body">Comments / Body:</label>
                <textarea name="body" id="body" maxlength="2048"></textarea>
            </div>
            <div>
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="1" selected>None</option>
                    <option value="2">Pop Culture</option>
                    <option value="3">Movies &amp; TV</option>
                    <option value="4">News &amp; Politics</option>
                </select>
            </div>
            <div>
                <label>
                    <input type="checkbox" name="terms">
                    I agree to the terms and conditions outlining that all written words are not plagiarized from external sites.
                </label>
            </div>
            <button type="submit">Submit Story</button>
        </form>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> My Reddit Clone</p>
    </footer>
</body>
</html>
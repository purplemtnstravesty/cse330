<?php
session_start();

require 'database.php';

// Validate that a valid story ID is provided.
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid story ID.");
}
$story_id = intval($_GET['id']);

// Retrieve the story details from the story table.
$stmt = $mysqli->prepare("SELECT s.story_ID, s.name, s.body, s.link, s.submitted_at, u.username 
                          FROM story s 
                          JOIN Users u ON s.user_ID = u.user_id 
                          WHERE s.story_ID = ?");
$stmt->bind_param("i", $story_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Story not found.");
}
$story = $result->fetch_assoc();
$stmt->close();

// Process comment submission if the form is submitted.
$comment_error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'comment') {
    if (!isset($_SESSION['user_id'])) {
        $comment_error = "You must be logged in to comment.";
    } else {
        $comment_text = trim($_POST['comment']);
        if (empty($comment_text)) {
            $comment_error = "Please enter a comment.";
        } elseif (strlen($comment_text) > 2048) {
            $comment_error = "Comment must be 2048 characters or fewer.";
        } else {
            $user_id = $_SESSION['user_id'];
            $submitted_at = date("Y-m-d H:i:s");
            // Insert the comment into your separate comments table.
            $stmt = $mysqli->prepare("INSERT INTO comments (story_ID, user_ID, comment_text, submitted_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $story_id, $user_id, $comment_text, $submitted_at);
            if ($stmt->execute()) {
                // Redirect to avoid duplicate form submissions.
                header("Location: story.php?id=" . $story_id);
                exit;
            } else {
                $comment_error = "Error submitting comment. Please try again.";
            }
            $stmt->close();
        }
    }
}

// Retrieve all comments for this story from the comments table.
$comments = [];
$stmt = $mysqli->prepare("SELECT c.commentID, c.comment_text, c.submitted_at, u.username 
                          FROM comments c 
                          LEFT JOIN Users u ON c.user_ID = u.user_id 
                          WHERE c.story_ID = ? 
                          ORDER BY c.submitted_at ASC");
$stmt->bind_param("i", $story_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($story['name']); ?> - Story</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/dark.css">
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($story['name']); ?></h1>
        <nav>
            <a href="dashboard.php">Home</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                | <a href="logout.php">Logout</a>
            <?php else: ?>
                | <a href="login.php">Login</a> | <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <article>
            <?php if (!empty($story['link'])): ?>
                <p><a href="<?php echo htmlspecialchars($story['link']); ?>" target="_blank">Visit link</a></p>
            <?php endif; ?>
            <?php if (!empty($story['body'])): ?>
                <p><?php echo nl2br(htmlspecialchars($story['body'])); ?></p>
            <?php endif; ?>
            <p class="meta">
                Posted by <?php echo htmlspecialchars($story['username']); ?> on <?php echo htmlspecialchars($story['submitted_at']); ?>
            </p>
        </article>

        <section id="comments">
            <h2>Comments</h2>
            <?php if (!empty($comment_error)): ?>
                <div class="error"><?php echo htmlspecialchars($comment_error); ?></div>
            <?php endif; ?>
            <?php if (count($comments) > 0): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <p><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                        <p class="meta">
                            Comment by <?php echo htmlspecialchars($comment['username']); ?> on <?php echo htmlspecialchars($comment['submitted_at']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No comments yet.</p>
            <?php endif; ?>
        </section>

        <section id="new-comment">
            <h2>Add a Comment</h2>
            <?php if (isset($_SESSION['user_id'])): ?>
                <form action="story.php?id=<?php echo $story_id; ?>" method="post">
                    <input type="hidden" name="action" value="comment">
                    <div>
                        <textarea name="comment" id="comment" rows="4" required></textarea>
                    </div>
                    <button type="submit">Submit Comment</button>
                </form>
            <?php else: ?>
                <p>
                    <a href="login.php">Login</a> or <a href="register.php">Register</a> to comment.
                </p>
            <?php endif; ?>
        </section>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> NewsAggDB</p>
    </footer>
</body>
</html>
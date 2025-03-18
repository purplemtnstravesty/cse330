<?php
session_start();

require 'database.php';

// Fetch all stories from the database in descending order of submission.
$query = "SELECT s.story_ID, s.name, s.body, s.link, s.user_ID, u.username, s.submitted_at
          FROM story s
          JOIN Users u ON s.user_ID = u.user_id
          ORDER BY s.submitted_at DESC";
$result = $mysqli->query($query);
$stories = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NewsAgg</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/dark.css">
    <style>
        .button-group {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Dashboard - NewsAgg</h1>
        <nav>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>
    /* <main>
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Story submission form, visible only for logged-in users -->
            <section id="new-story">
                <h2>User Actions</h2>
                <div class="button-group">
                    <form action="submission.php" method="post">
                        <button type="submit">Submit Story</button>
                    </form>
                    <a href="calendar.php"><button>View Calendar</button></a>
                </div>
            </section>
        <?php else: ?>
            <p>
                <a href="login.php">Login</a> or 
                <a href="register.php">Register</a> to post stories and add comments.
            </p>
        <?php endif; ?>

        <section id="stories">
            <h2>Recent Stories</h2>
            <?php if (!empty($stories)): ?>
                <?php foreach ($stories as $story): ?>
                    <article class="story">
                        <h3>
                            <a href="story.php?id=<?php echo $story['story_ID']; ?>">
                                <?php echo htmlspecialchars($story['name']); ?>
                            </a>
                        </h3>
                        <?php if (!empty($story['body'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($story['body'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($story['link'])): ?>
                            <p><a href="<?php echo htmlspecialchars($story['link']); ?>" target="_blank">Visit Link</a></p>
                        <?php endif; ?>
                        <p class="meta">
                            Posted by <?php echo htmlspecialchars($story['username']); ?> on <?php echo htmlspecialchars($story['submitted_at']); ?>
                        </p>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <p>
                                <a href="story.php?id=<?php echo $story['story_ID']; ?>">View / Add Comments</a>
                            </p>
                        <?php else: ?>
                            <p>
                                <a href="login.php">Login</a> to comment
                            </p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No stories available. Be the first to post!</p>
            <?php endif; ?>
        </section>
    </main> */
    <footer>
        <p>&copy; <?php echo date("Y"); ?> NewsAgg</p>
    </footer>
</body>
</html>
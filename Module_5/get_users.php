<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

require 'database.php';

$current_user_id = $_SESSION['user_id'];

if (isset($_GET['username'])) {
    $username_param = $_GET['username'];

    // Prepare statement to search for a specific user by username, excluding the current user
    if ($stmt = $mysqli->prepare("SELECT user_id, username FROM Users WHERE username = ? AND user_id != ? LIMIT 1")) {
        $stmt->bind_param("si", $username_param, $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['error' => 'User not found']);
        }
        $stmt->close();
    } else {
        echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    }
} else {
// Get all users except the current user
    $users = [];
    if ($stmt = $mysqli->prepare("SELECT user_id, username FROM Users WHERE user_id != ? ORDER BY username")) {
        $stmt->bind_param("i", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'user_id' => $row['user_id'],
                'username' => $row['username']
            ];
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
    } else {
        echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    }
}
exit; 
?> 
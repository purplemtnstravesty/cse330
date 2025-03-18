<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

require 'database.php';

// Get event ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid event ID']);
    exit;
}
$event_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Verify that the event belongs to the current user before deleting
if ($stmt = $mysqli->prepare("DELETE FROM Events WHERE eventID = ? AND user_id = ?")) {
    $stmt->bind_param("ii", $event_id, $user_id);
    $stmt->execute();
    
    // Check if any rows were affected (if the event existed and belonged to the user)
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Event deleted successfully'
        ]);
    } else {
        echo json_encode([
            'error' => 'Event not found or you do not have permission to delete it'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
}
?> 
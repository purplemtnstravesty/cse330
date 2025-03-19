<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

require 'database.php';

try {
    // Read JSON input
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($data['event_name'], $data['event_datetime']) || empty($data['event_name']) || empty($data['event_datetime'])) {
        throw new Exception('Missing required event details');
    }

    $user_id = $_SESSION['user_id'];
    $event_name = trim($data['event_name']);
    $event_description = isset($data['event_description']) ? trim($data['event_description']) : NULL;
    $event_datetime = $data['event_datetime'];
    $tags = isset($data['tags']) ? $data['tags'] : []; // Tags array

    // Start transaction
    $mysqli->begin_transaction();

    // Step 1: Insert Event into Events table
    $query = "INSERT INTO Events (user_id, event_name, event_description, event_datetime) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare event insert statement');
    }
    $stmt->bind_param("isss", $user_id, $event_name, $event_description, $event_datetime);
    if (!$stmt->execute()) {
        throw new Exception('Failed to insert event');
    }
    $event_id = $stmt->insert_id;
    $stmt->close();

    // Step 2: Insert Tags into EventTags table
    if (!empty($tags)) {
        $query_tags = "INSERT INTO EventTags (event_id, tag_name) VALUES (?, ?)";
        $stmt = $mysqli->prepare($query_tags);
        if (!$stmt) {
            throw new Exception('Failed to prepare tag insert statement');
        }
        foreach ($tags as $tag) {
            $stmt->bind_param("is", $event_id, $tag);
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert tag: ' . $tag);
            }
        }
        $stmt->close();
    }

    // Commit transaction
    $mysqli->commit();

    // Return success response
    echo json_encode(['success' => true, 'event_id' => $event_id]);
} catch (Exception $e) {
    $mysqli->rollback(); // Rollback transaction on error
    echo json_encode(['error' => $e->getMessage()]);
}

exit;
?>
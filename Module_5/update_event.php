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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['event_name']) || empty($input['event_name'])) {
    echo json_encode(['error' => 'Event name is required']);
    exit;
}

if (!isset($input['event_datetime']) || empty($input['event_datetime'])) {
    echo json_encode(['error' => 'Event date and time are required']);
    exit;
}

// Sanitize input
$event_name = $input['event_name'];
$event_datetime = $input['event_datetime'];
$event_description = isset($input['event_description']) ? $input['event_description'] : '';
$user_id = $_SESSION['user_id'];

// Get shared users if provided
$shared_users = isset($input['shared_users']) ? $input['shared_users'] : [];

// Get tags if provided
$tags = isset($input['tags']) ? $input['tags'] : [];

// First verify that the event belongs to the current user
if ($verify_stmt = $mysqli->prepare("SELECT COUNT(*) FROM Events WHERE eventID = ? AND user_id = ?")) {
    $verify_stmt->bind_param("ii", $event_id, $user_id);
    $verify_stmt->execute();
    $verify_stmt->bind_result($count);
    $verify_stmt->fetch();
    $verify_stmt->close();
    
    if ($count != 1) {
        echo json_encode(['error' => 'You do not have permission to edit this event']);
        exit;
    }
} else {
    echo json_encode(['error' => 'Database error']);
    exit;
}

// Start transaction
$mysqli->begin_transaction();

try {
    // Update the event
    if ($stmt = $mysqli->prepare("UPDATE Events SET event_name = ?, event_description = ?, event_datetime = ? WHERE eventID = ? AND user_id = ?")) {
        $stmt->bind_param("sssii", $event_name, $event_description, $event_datetime, $event_id, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update event: ' . $mysqli->error);
        }
        
        $stmt->close();
        
        // Update shared users
        // First, remove all existing shares
        $delete_shares = $mysqli->prepare("DELETE FROM EventSharing WHERE event_id = ?");
        $delete_shares->bind_param("i", $event_id);
        if (!$delete_shares->execute()) {
            throw new Exception('Failed to update sharing: ' . $mysqli->error);
        }
        $delete_shares->close();
        
        // Then add new shares
        if (!empty($shared_users)) {
            $shared_stmt = $mysqli->prepare("INSERT INTO EventSharing (event_id, shared_with_user_id) VALUES (?, ?)");
            
            foreach ($shared_users as $shared_user_id) {
                // Validate that user exists
                $user_check = $mysqli->prepare("SELECT COUNT(*) FROM Users WHERE user_id = ?");
                $user_check->bind_param("i", $shared_user_id);
                $user_check->execute();
                $user_check->bind_result($user_exists);
                $user_check->fetch();
                $user_check->close();
                
                if ($user_exists > 0) {
                    $shared_stmt->bind_param("ii", $event_id, $shared_user_id);
                    if (!$shared_stmt->execute()) {
                        throw new Exception('Failed to share event: ' . $mysqli->error);
                    }
                }
            }
            
            $shared_stmt->close();
        }
        
        // Update tags
        // First, remove all existing tags
        $delete_tags = $mysqli->prepare("DELETE FROM EventTags WHERE event_id = ?");
        $delete_tags->bind_param("i", $event_id);
        if (!$delete_tags->execute()) {
            throw new Exception('Failed to update tags: ' . $mysqli->error);
        }
        $delete_tags->close();
        
        // Then add new tags
        if (!empty($tags)) {
            $tags_stmt = $mysqli->prepare("INSERT INTO EventTags (event_id, tag_name) VALUES (?, ?)");
            
            foreach ($tags as $tag) {
                // Sanitize tag name
                $tag = trim($tag);
                if (!empty($tag)) {
                    $tags_stmt->bind_param("is", $event_id, $tag);
                    if (!$tags_stmt->execute()) {
                        throw new Exception('Failed to add tag: ' . $mysqli->error);
                    }
                }
            }
            
            $tags_stmt->close();
        }
        
        // Commit transaction
        $mysqli->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Event updated successfully'
        ]);
    } else {
        throw new Exception('Database error: ' . $mysqli->error);
    }
} catch (Exception $e) {
    // Rollback on error
    $mysqli->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}
?> 
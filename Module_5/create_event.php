<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

require 'database.php';

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

// Start transaction
$mysqli->begin_transaction();

try {
    // Insert new event
    if ($stmt = $mysqli->prepare("INSERT INTO Events (user_id, event_name, event_description, event_datetime) VALUES (?, ?, ?, ?)")) {
        $stmt->bind_param("isss", $user_id, $event_name, $event_description, $event_datetime);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create event: ' . $mysqli->error);
        }
        
        $event_id = $mysqli->insert_id;
        $stmt->close();
        
        // Add shared users if any
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
        
        // Add tags if any
        if (!empty($tags)) {
            $tag_stmt = $mysqli->prepare("INSERT INTO EventTags (event_id, tag_name) VALUES (?, ?)");
            
            foreach ($tags as $tag) {
                // Sanitize tag name
                $tag = trim($tag);
                if (!empty($tag)) {
                    $tag_stmt->bind_param("is", $event_id, $tag);
                    if (!$tag_stmt->execute()) {
                        throw new Exception('Failed to add tag: ' . $mysqli->error);
                    }
                }
            }
            
            $tag_stmt->close();
        }
        
        // Commit transaction  
        $mysqli->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Event created successfully',
            'event_id' => $event_id
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
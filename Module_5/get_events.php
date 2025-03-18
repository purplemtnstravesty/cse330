<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

require 'database.php';

// Get month and year from request (defaulting to current month/year)
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get tag filter if provided
$tag_filter = isset($_GET['tag']) ? trim($_GET['tag']) : '';

// Validate month and year
if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
    echo json_encode(['error' => 'Invalid month or year']);
    exit;
}

$user_id = $_SESSION['user_id'];
$events = [];

// First day of the month
$start_date = "$year-$month-01";
// Last day of the month
$end_date = date('Y-m-t', strtotime($start_date));
// Add time to make sure we get the entire day
$end_date .= ' 23:59:59';

// Base query to get all events for the current user within the specified month
// This includes both owned events and shared events
$query = "
    SELECT DISTINCT e.eventID, e.event_name, e.event_description, e.event_datetime, e.user_id, 
           u.username as owner_username, 
           CASE WHEN e.user_id = ? THEN 1 ELSE 0 END as is_owner
    FROM Events e
    JOIN Users u ON e.user_id = u.user_id
";

// Add tag filter if provided
if (!empty($tag_filter)) {
    $query .= "
    JOIN EventTags et ON e.eventID = et.event_id AND et.tag_name = ?
    ";
}

$query .= "
    WHERE (e.user_id = ? OR e.eventID IN (
        SELECT event_id FROM EventSharing WHERE shared_with_user_id = ?
    ))
    AND e.event_datetime BETWEEN ? AND ?
    ORDER BY e.event_datetime
";

if ($stmt = $mysqli->prepare($query)) {
    if (!empty($tag_filter)) {
        $stmt->bind_param("isiiss", $user_id, $tag_filter, $user_id, $user_id, $start_date, $end_date);
    } else {
        $stmt->bind_param("iiiss", $user_id, $user_id, $user_id, $start_date, $end_date);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Extract day component to organize events by day
        $day = date('j', strtotime($row['event_datetime']));
        $time = date('H:i', strtotime($row['event_datetime']));
        
        // Add event to the array, organized by day
        if (!isset($events[$day])) {
            $events[$day] = [];
        }
        
        // Get shared users for this event
        $shared_users = [];
        if ($shared_stmt = $mysqli->prepare("
            SELECT u.user_id, u.username 
            FROM EventSharing es
            JOIN Users u ON es.shared_with_user_id = u.user_id
            WHERE es.event_id = ?
        ")) {
            $shared_stmt->bind_param("i", $row['eventID']);
            $shared_stmt->execute();
            $shared_result = $shared_stmt->get_result();
            
            while ($shared_row = $shared_result->fetch_assoc()) {
                $shared_users[] = [
                    'user_id' => $shared_row['user_id'],
                    'username' => $shared_row['username']
                ];
            }
            
            $shared_stmt->close();
        }
        
        // Get tags for this event
        $tags = [];
        if ($tags_stmt = $mysqli->prepare("
            SELECT tag_name 
            FROM EventTags
            WHERE event_id = ?
            ORDER BY tag_name
        ")) {
            $tags_stmt->bind_param("i", $row['eventID']);
            $tags_stmt->execute();
            $tags_result = $tags_stmt->get_result();
            
            while ($tag_row = $tags_result->fetch_assoc()) {
                $tags[] = $tag_row['tag_name'];
            }
            
            $tags_stmt->close();
        }
        
        $events[$day][] = [
            'id' => $row['eventID'],
            'title' => $row['event_name'],
            'description' => $row['event_description'],
            'datetime' => $row['event_datetime'],
            'time' => $time,
            'owner_id' => $row['user_id'],
            'owner_username' => $row['owner_username'],
            'is_owner' => $row['is_owner'] == 1,
            'shared_users' => $shared_users,
            'tags' => $tags
        ];
    }
    
    $stmt->close();
    
    // Get all available tags for the filter dropdown
    $all_tags = [];
    $tags_query = "
        SELECT DISTINCT et.tag_name
        FROM EventTags et
        JOIN Events e ON et.event_id = e.eventID
        WHERE e.user_id = ? OR e.eventID IN (
            SELECT event_id FROM EventSharing WHERE shared_with_user_id = ?
        )
        ORDER BY et.tag_name
    ";
    
    if ($tags_stmt = $mysqli->prepare($tags_query)) {
        $tags_stmt->bind_param("ii", $user_id, $user_id);
        $tags_stmt->execute();
        $tags_result = $tags_stmt->get_result();
        
        while ($tag_row = $tags_result->fetch_assoc()) {
            $all_tags[] = $tag_row['tag_name'];
        }
        
        $tags_stmt->close();
    }
    
    // Also include calendar metadata
    $calendar_info = [
        'month' => $month,
        'year' => $year,
        'month_name' => date('F', strtotime($start_date)),
        'days_in_month' => date('t', strtotime($start_date)),
        'start_day' => date('w', strtotime($start_date)), // 0 (Sunday) through 6 (Saturday)
        'current_tag' => $tag_filter,
        'all_tags' => $all_tags
    ];
    
    echo json_encode([
        'calendar' => $calendar_info,
        'events' => $events
    ]);
    
} else {
    echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
}
?> 
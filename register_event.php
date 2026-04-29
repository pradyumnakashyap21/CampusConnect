<?php
require_once 'config.php';
require_once 'functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method.');
}

// Must be logged in
if (!is_logged_in()) {
    json_response(false, 'You must be logged in to register for an event.');
}

// Validate event_id
$event_id = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
if ($event_id <= 0) {
    json_response(false, 'Invalid event ID.');
}

$user_id = (int) $_SESSION['user_id'];

// ------------------------------------------------
// Check for duplicate registration
// ------------------------------------------------
$existing = fetch_single_record(
    $conn,
    "SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?",
    [$event_id, $user_id],
    "ii"
);

if ($existing) {
    json_response(false, 'You have already registered for this event.');
}

// ------------------------------------------------
// The hardcoded events (IDs 1, 2, 3) may not exist
// in the events table. Insert a placeholder row
// if needed so the FK constraint is satisfied.
// ------------------------------------------------
$hardcoded = [
    1 => ['Web Development Workshop',      'Technical', 'Tech Club',         '2026-04-15', '14:00:00', 'Computer Lab, Building A'],
    2 => ['Annual Music Festival',          'Cultural',  'Music Club',        '2026-04-20', '18:00:00', 'Main Auditorium'],
    3 => ['Inter-College Sports Championship', 'Sports', 'Sports Association', '2026-04-25', '09:00:00', 'Sports Ground'],
];

if (array_key_exists($event_id, $hardcoded)) {
    // Check if this event already exists in the events table
    $event_row = fetch_single_record(
        $conn,
        "SELECT id FROM events WHERE id = ?",
        [$event_id],
        "i"
    );

    if (!$event_row) {
        // We need a valid created_by user. Use the current user.
        $info = $hardcoded[$event_id];
        $stmt = $conn->prepare(
            "INSERT INTO events (id, title, category, club, event_date, event_time, venue, description, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $desc = $info[0] . ' - College event';
        $stmt->bind_param(
            "isssssssi",
            $event_id,
            $info[0], $info[1], $info[2], $info[3], $info[4], $info[5],
            $desc,
            $user_id
        );
        if (!$stmt->execute()) {
            json_response(false, 'Could not create event record: ' . $stmt->error);
        }
        $stmt->close();
    }
} else {
    // For DB events, verify it actually exists
    $event_row = fetch_single_record(
        $conn,
        "SELECT id FROM events WHERE id = ?",
        [$event_id],
        "i"
    );
    if (!$event_row) {
        json_response(false, 'Event not found.');
    }
}

// ------------------------------------------------
// Insert registration
// ------------------------------------------------
$stmt = execute_query(
    $conn,
    "INSERT INTO event_registrations (event_id, user_id, status) VALUES (?, ?, 'registered')",
    [$event_id, $user_id],
    "ii"
);

if ($stmt && $conn->affected_rows > 0) {
    // Bump the total_registrations counter on the events table
    $conn->query("UPDATE events SET total_registrations = total_registrations + 1 WHERE id = $event_id");
    json_response(true, 'Successfully registered for the event!');
} else {
    json_response(false, 'Registration failed. Please try again.');
}
?>

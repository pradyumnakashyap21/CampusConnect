<?php
require_once 'config.php';
require_once 'functions.php';

// Require login
require_login();

// Get current user
$user = get_logged_in_user($conn);

if (!$user) {
    json_response(false, 'Unauthorized');
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method');
}

// Get request data
$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : null;

if (!$event_id) {
    json_response(false, 'Event ID is required');
}

try {
    // Verify that the user is registered for this event
    $check_query = "SELECT er.id, er.status, e.title 
                    FROM event_registrations er 
                    JOIN events e ON er.event_id = e.id 
                    WHERE er.user_id = ? AND er.event_id = ? AND er.status = 'registered'";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $user['id'], $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $registration = $result->fetch_assoc();
    $stmt->close();

    if (!$registration) {
        json_response(false, 'You are not registered for this event or it cannot be cancelled');
    }

    // Update registration status to 'cancelled'
    $update_query = "UPDATE event_registrations SET status = 'cancelled' WHERE user_id = ? AND event_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $user['id'], $event_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        json_response(true, 'Event registration cancelled successfully');
    } else {
        $stmt->close();
        json_response(false, 'Failed to cancel registration');
    }

} catch (Exception $e) {
    error_log("Cancellation error: " . $e->getMessage());
    json_response(false, 'An error occurred while cancelling the registration');
}
?>

<?php
require_once 'config.php';
require_once 'functions.php';

// Require user to be logged in
require_login();

header('Content-Type: application/json');

$user_id = get_current_user_id();
$error = '';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize input
    $name = sanitize_input($_POST['name'] ?? '');
    $college = sanitize_input($_POST['college'] ?? '');
    $stream = sanitize_input($_POST['stream'] ?? '');
    $year = intval($_POST['year'] ?? 0);
    $interests = sanitize_input($_POST['interests'] ?? '');
    $projects = sanitize_input($_POST['projects'] ?? '');
    $bio = sanitize_input($_POST['bio'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    
    // Validate
    if (empty($name)) {
        json_response(false, 'Name is required', null);
    }
    
    // Update user profile
    $sql = "UPDATE users SET name = ?, college = ?, stream = ?, year = ?, 
            interests = ?, projects = ?, bio = ?, phone = ? WHERE id = ?";
    
    $stmt = execute_query(
        $conn,
        $sql,
        [$name, $college, $stream, $year, $interests, $projects, $bio, $phone, $user_id],
        "sssisssi"
    );
    
    if ($stmt) {
        // Update session data
        $_SESSION['user_name'] = $name;
        
        json_response(true, 'Profile updated successfully', [
            'name' => $name,
            'college' => $college,
            'stream' => $stream,
            'year' => $year,
            'interests' => $interests,
            'projects' => $projects,
            'bio' => $bio,
            'phone' => $phone
        ]);
    } else {
        json_response(false, 'Failed to update profile', null);
    }
} else {
    json_response(false, 'Invalid request method', null);
}

?>

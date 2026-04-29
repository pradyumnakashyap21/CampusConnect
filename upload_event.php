<?php
require_once 'config.php';
require_once 'functions.php';

session_start();

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to upload events']);
    exit;
}

// Check if user is club member
if (!is_club_member($conn)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only club members can upload events']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate form data
$title = $_POST['title'] ?? '';
$category = $_POST['category'] ?? '';
$club = $_POST['club'] ?? '';
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$venue = $_POST['venue'] ?? '';
$description = $_POST['description'] ?? '';
$registration_link = $_POST['registration_link'] ?? '';

// Validation
if (empty($title) || empty($category) || empty($date) || empty($venue) || empty($description)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
    exit;
}

// Handle file upload
$poster_path = null;
if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['poster'];
    $filename = uniqid() . '_' . basename($file['name']);
    $upload_dir = 'uploads/posters/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filepath = $upload_dir . $filename;
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only images allowed']);
        exit;
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
        exit;
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $poster_path = $filepath;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please upload a poster image']);
    exit;
}

// Insert event into database
$user_id = get_current_user_id();
$sql = "INSERT INTO events (title, category, club, event_date, event_time, venue, description, poster_path, registration_link, created_by, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$result = execute_query($conn, $sql, [
    $title,
    $category,
    $club,
    $date,
    $time,
    $venue,
    $description,
    $poster_path,
    $registration_link,
    $user_id
], 'ssssssssi');

if ($result) {
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Event uploaded successfully',
        'event_id' => get_last_id($conn)
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create event in database']);
}
?>

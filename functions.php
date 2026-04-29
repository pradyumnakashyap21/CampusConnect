    <?php
// ================================================
// UTILITY & VALIDATION FUNCTIONS
// ================================================

// ================================================
// INPUT VALIDATION FUNCTIONS
// ================================================

/**
 * Sanitize input string
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password strength
 * Minimum 8 characters, at least 1 uppercase, 1 number
 */
function validate_password($password) {
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

/**
 * Hash password using bcrypt
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate secure token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// ================================================
// DATABASE QUERY FUNCTIONS
// ================================================

/**
 * Execute SQL query safely
 */
function execute_query($conn, $sql, $params = [], $types = '') {
    try {
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch single record
 */
function fetch_single_record($conn, $sql, $params = [], $types = '') {
    $stmt = execute_query($conn, $sql, $params, $types);
    
    if (!$stmt) return false;
    
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();
    
    return $record;
}

/**
 * Fetch all records
 */
function fetch_all_records($conn, $sql, $params = [], $types = '') {
    $stmt = execute_query($conn, $sql, $params, $types);
    
    if (!$stmt) return [];
    
    $result = $stmt->get_result();
    $records = [];
    
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    $stmt->close();
    return $records;
}

/**
 * Get last inserted ID
 */
function get_last_id($conn) {
    return $conn->insert_id;
}

// ================================================
// USER AUTHENTICATION FUNCTIONS
// ================================================

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in user ID
 */
function get_current_user_id() {
    return is_logged_in() ? $_SESSION['user_id'] : null;
}

/**
 * Get current user data
 */
function get_logged_in_user($conn) {
    if (!is_logged_in()) return null;
    
    $user_id = get_current_user_id();
    return fetch_single_record(
        $conn,
        "SELECT * FROM users WHERE id = ?",
        [$user_id],
        "i"
    );
}

/**
 * Check if user is club member
 */
function is_club_member($conn) {
    $user = get_logged_in_user($conn);
    return $user && $user['is_club_member'] == 1;
}

/**
 * Require login - redirect if not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

/**
 * Require club member - redirect if not a club member
 */
function require_club_member($conn) {
    require_login();
    
    if (!is_club_member($conn)) {
        header("Location: index.php?error=only_club_members_can_upload");
        exit();
    }
}

// ================================================
// SESSION MANAGEMENT FUNCTIONS
// ================================================

/**
 * Create user session
 */
function create_session($user_id, $user_email, $user_name) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $user_email;
    $_SESSION['user_name'] = $user_name;
    $_SESSION['login_time'] = time();
}

/**
 * Destroy user session
 */
function destroy_session() {
    session_unset();
    session_destroy();
}

// ================================================
// RESPONSE FUNCTIONS
// ================================================

/**
 * Return JSON response
 */
function json_response($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

/**
 * Redirect with message
 */
function redirect($url, $message = '', $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit();
}

/**
 * Get and clear session message
 */
function get_session_message() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// ================================================
// FILE UPLOAD FUNCTIONS
// ================================================

/**
 * Validate and upload file
 */
function upload_file($file, $upload_dir = UPLOAD_DIR) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_type = $file['type'];
    
    // Validate file size
    if ($file_size > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds 5MB limit'];
    }
    
    // Validate file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if (!in_array($file_ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Validate MIME type
    if (!in_array($file_type, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'message' => 'Invalid MIME type'];
    }
    
    // Create unique filename
    $new_file_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
    $file_path = $upload_dir . $new_file_name;
    
    // Move uploaded file
    if (move_uploaded_file($file_tmp, $file_path)) {
        return ['success' => true, 'file_path' => $file_path, 'file_name' => $new_file_name];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

// ================================================
// FORMAT FUNCTIONS
// ================================================

/**
 * Format date
 */
function format_date($date_string) {
    return date('M d, Y', strtotime($date_string));
}

/**
 * Format time
 */
function format_time($time_string) {
    return date('h:i A', strtotime($time_string));
}

/**
 * Time ago (e.g., "2 hours ago")
 */
function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    
    if ($time_difference < 1) {
        return 'just now';
    }
    
    $conditions = [
        12 * 30 * 24 * 60 * 60 => 'year',
        30 * 24 * 60 * 60 => 'month',
        24 * 60 * 60 => 'day',
        60 * 60 => 'hour',
        60 => 'minute',
        1 => 'second'
    ];
    
    foreach ($conditions as $seconds => $time_period) {
        if ($time_difference >= $seconds) {
            $time_value = floor($time_difference / $seconds);
            return $time_value . ' ' . $time_period . ($time_value != 1 ? 's' : '') . ' ago';
        }
    }
}

?>

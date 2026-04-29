<?php
require_once 'config.php';
require_once 'functions.php';

// If user is already logged in, redirect to dashboard
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Handle signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    
    // Sanitize input
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $name = sanitize_input($_POST['name'] ?? '');
    $college = sanitize_input($_POST['college'] ?? '');
    $stream = sanitize_input($_POST['stream'] ?? '');
    $year = intval($_POST['year'] ?? 0);
    
    // Validation
    if (empty($email) || empty($password) || empty($name)) {
        $error = 'Please fill in all required fields';
    } elseif (!validate_email($email)) {
        $error = 'Invalid email format';
    } elseif (!validate_password($password)) {
        $error = 'Password must be at least 8 characters with 1 uppercase letter and 1 number';
    } elseif ($password != $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $existing_user = fetch_single_record(
            $conn,
            "SELECT id FROM users WHERE email = ?",
            [$email],
            "s"
        );
        
        if ($existing_user) {
            $error = 'Email already registered. Please login instead.';
        } else {
            // Hash password and insert user
            $hashed_password = hash_password($password);
            
            $sql = "INSERT INTO users (email, password, name, college, stream, year, role) 
                    VALUES (?, ?, ?, ?, ?, ?, 'student')";
            
            $stmt = execute_query(
                $conn,
                $sql,
                [$email, $hashed_password, $name, $college, $stream, $year],
                "sssssi"
            );
            
            if ($stmt) {
                $user_id = get_last_id($conn);
                create_session($user_id, $email, $name);
                redirect('dashboard.php', 'Registration successful! Welcome to EventHub!', 'success');
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - EventHub</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="container-auth">
        <!-- Left side - Form -->
        <div class="auth-left">
            <div class="auth-container">
                <div class="auth-logo">
                    <i class="fas fa-calendar-check"></i>
                    <h1>EventHub</h1>
                </div>
                
                <h2>Create Account</h2>
                <p class="auth-subtitle">Join us and discover amazing events!</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="auth-form" id="signupForm">
                    <input type="hidden" name="action" value="signup">
                    
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required placeholder="Enter your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required placeholder="your.email@college.edu">
                    </div>
                    
                    <div class="form-group">
                        <label for="college">College</label>
                        <input type="text" id="college" name="college" placeholder="Your college name">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="stream">Stream</label>
                            <select id="stream" name="stream">
                                <option value="">Select Stream</option>
                                <option value="CSE">Computer Science</option>
                                <option value="ECE">Electronics</option>
                                <option value="ME">Mechanical</option>
                                <option value="CE">Civil</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="year">Year</label>
                            <select id="year" name="year">
                                <option value="">Select Year</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <div class="password-input-group">
                            <input type="password" id="password" name="password" required placeholder="At least 8 characters">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small>Min 8 chars, 1 uppercase, 1 number</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <div class="password-input-group">
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-auth">Create Account</button>
                </form>
                
                <div class="auth-divider">Or</div>
                
                <div class="social-buttons">
                    <button class="social-btn google-btn" disabled>
                        <i class="fab fa-google"></i> Google
                    </button>
                    <button class="social-btn github-btn" disabled>
                        <i class="fab fa-github"></i> GitHub
                    </button>
                </div>
                
                <p class="auth-switch">
                    Already have an account? 
                    <a href="login.php">Log in here</a>
                </p>
            </div>
        </div>
        
        <!-- Right side - Illustration -->
        <div class="auth-right">
            <div class="auth-illustration">
                <div class="illustration-content">
                    <div class="illustration-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Join Our Community</h3>
                    <p>Discover, explore, and engage with amazing college events</p>
                    <ul class="features-list">
                        <li><i class="fas fa-check"></i> Discover amazing events</li>
                        <li><i class="fas fa-check"></i> Connect with students</li>
                        <li><i class="fas fa-check"></i> Never miss an event</li>
                        <li><i class="fas fa-check"></i> Join your favorite clubs</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
            field.setAttribute('type', type);
        }
    </script>
</body>
</html>

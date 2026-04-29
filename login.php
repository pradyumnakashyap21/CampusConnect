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

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password';
    } else {
        // Fetch user from database
        $user = fetch_single_record(
            $conn,
            "SELECT id, email, password, name FROM users WHERE email = ?",
            [$email],
            "s"
        );
        
        if ($user && verify_password($password, $user['password'])) {
            // Login successful
            create_session($user['id'], $user['email'], $user['name']);
            
            // Update last login
            execute_query(
                $conn,
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']],
                "i"
            );
            
            // Redirect to dashboard or previous page
            $redirect = $_GET['redirect'] ?? 'dashboard.php';
            redirect($redirect, 'Login successful! Welcome back!', 'success');
        } else {
            $error = 'Invalid email or password';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EventHub</title>
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
                
                <h2>Welcome Back</h2>
                <p class="auth-subtitle">Sign in to explore amazing college events</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="auth-form">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="your.email@college.edu">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input-group">
                            <input type="password" id="password" name="password" required placeholder="Enter your password">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-remember">
                        <label class="checkbox">
                            <input type="checkbox" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn-auth">Sign In</button>
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
                    Don't have an account? 
                    <a href="signup.php">Create one now</a>
                </p>
            </div>
        </div>
        
        <!-- Right side - Illustration -->
        <div class="auth-right">
            <div class="auth-illustration">
                <div class="illustration-content">
                    <div class="illustration-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <h3>Welcome to EventHub</h3>
                    <p>Your gateway to amazing college events</p>
                    <ul class="features-list">
                        <li><i class="fas fa-check"></i> Discover events happening near you</li>
                        <li><i class="fas fa-check"></i> Connect with your college community</li>
                        <li><i class="fas fa-check"></i> Register for events instantly</li>
                        <li><i class="fas fa-check"></i> Never miss out on anything</li>
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

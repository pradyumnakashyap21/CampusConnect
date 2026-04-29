<?php
require_once 'config.php';
require_once 'functions.php';

// Require user to be logged in
require_login();

// Destroy session
destroy_session();

// Redirect to home page
redirect('index.php', 'You have been logged out successfully!', 'success');
?>

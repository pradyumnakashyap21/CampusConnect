<?php
require_once 'config.php';
require_once 'functions.php';
 $theme = "dashboard.css";
    echo '<link rel="stylesheet" type="text/css" href="css/' . $theme . '">';
// Get session message
$message = get_session_message();

// Require login
require_login();

// Get current user data
$user = get_logged_in_user($conn);

if (!$user) {
    header("Location: login.php");
    exit();
}

// ================================================
// FETCH DASHBOARD DATA FROM DATABASE
// ================================================

$user_id = $user['id'];

// Initialize default values
$registered_count = 0;
$bookmarks_count = 0;
$connections_count = 0;
$attended_count = 0;
$registered_events = [];
$db_error_message = null;

try {
    // 1. Get count of registered events
    $registered_events_query = "SELECT COUNT(*) as total FROM event_registrations WHERE user_id = ? AND status IN ('registered', 'attended')";
    $stmt = $conn->prepare($registered_events_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $registered_result = $stmt->get_result();
        $registered_count = $registered_result->fetch_assoc()['total'] ?? 0;
        $stmt->close();
    }
} catch (Exception $e) {
    // Table doesn't exist yet
    $registered_count = 0;
}

try {
    // 2. Get count of bookmarks (favorites)
    $bookmarks_query = "SELECT COUNT(*) as total FROM user_bookmarks WHERE user_id = ?";
    $stmt = $conn->prepare($bookmarks_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $bookmarks_result = $stmt->get_result();
        $bookmarks_count = $bookmarks_result->fetch_assoc()['total'] ?? 0;
        $stmt->close();
    }
} catch (Exception $e) {
    $bookmarks_count = 0;
}

try {
    // 3. Get count of connections
    $connections_query = "SELECT COUNT(*) as total FROM user_connections WHERE follower_id = ?";
    $stmt = $conn->prepare($connections_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $connections_result = $stmt->get_result();
        $connections_count = $connections_result->fetch_assoc()['total'] ?? 0;
        $stmt->close();
    }
} catch (Exception $e) {
    $connections_count = 0;
}

try {
    // 4. Get count of attended events
    $attended_query = "SELECT COUNT(*) as total FROM event_registrations WHERE user_id = ? AND status = 'attended'";
    $stmt = $conn->prepare($attended_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $attended_result = $stmt->get_result();
        $attended_count = $attended_result->fetch_assoc()['total'] ?? 0;
        $stmt->close();
    }
} catch (Exception $e) {
    $attended_count = 0;
}

try {
    // 5. Get registered events with details (sorted by date, upcoming first)
    $events_query = "SELECT e.id, e.title, e.club, e.event_date, e.event_time, e.venue, e.poster_path, 
                            er.status, er.registration_date 
                     FROM event_registrations er
                     JOIN events e ON er.event_id = e.id
                     WHERE er.user_id = ? AND er.status != 'cancelled'
                     ORDER BY e.event_date DESC";
    $stmt = $conn->prepare($events_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $events_result = $stmt->get_result();
        $registered_events = [];
        while ($event = $events_result->fetch_assoc()) {
            $registered_events[] = $event;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $registered_events = [];
}

// Check if tables exist
$tables_exist = true;
$check_table_query = "SHOW TABLES LIKE 'event_registrations'";
$result = $conn->query($check_table_query);
if ($result->num_rows == 0) {
    $tables_exist = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - EventHub</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">
                <i class="fas fa-calendar-check"></i>
                EventHub
            </a>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Home</a>
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Container -->
    <div class="container">
        <!-- Success Message -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?>">
                <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Setup Warning (if tables don't exist) -->
        <?php if (!$tables_exist): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Dashboard Setup In Progress:</strong> Some tables are still being created. Please create the following database tables to enable all features: <br>
                <code style="font-size: 12px; color: #333;">event_registrations, user_bookmarks, user_connections, event_teams</code>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="dashboard-header">
            <h1><i class="fas fa-user-circle"></i> My Dashboard</h1>
        </div>

        <!-- User Profile Card -->
        <div class="dashboard-grid">
            <!-- Profile Section -->
            <div class="profile-section">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo $user['name']; ?></h2>
                            <p class="profile-email"><?php echo $user['email']; ?></p>
                        </div>
                    </div>

                    <div class="profile-details">
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-school"></i> College</span>
                            <span class="detail-value"><?php echo $user['college'] ?: 'Not set'; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-graduation-cap"></i> Stream</span>
                            <span class="detail-value"><?php echo $user['stream'] ?: 'Not set'; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-calendar"></i> Year</span>
                            <span class="detail-value"><?php echo $user['year'] ? $user['year'] . 'st Year' : 'Not set'; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-heart"></i> Interests</span>
                            <span class="detail-value">
                                <div class="interests-tags">
                                    <?php 
                                    if (!empty($user['interests'])) {
                                        $interests = explode(',', $user['interests']);
                                        foreach ($interests as $interest) {
                                            echo '<span class="interest-tag">' . trim($interest) . '</span>';
                                        }
                                    } else {
                                        echo '<span>Add your interests</span>';
                                    }
                                    ?>
                                </div>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-briefcase"></i> Projects</span>
                            <span class="detail-value"><?php echo !empty($user['projects']) ? $user['projects'] : '0'; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-calendar-check"></i> Joined</span>
                            <span class="detail-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats & Activities -->
            <div class="dashboard-sidebar">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h4><?php echo $registered_count; ?></h4>
                            <p>Events Registered</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h4><?php echo $bookmarks_count; ?></h4>
                            <p>Favorites</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h4><?php echo $connections_count; ?></h4>
                            <p>Connections</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon attended">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h4><?php echo $attended_count; ?></h4>
                            <p>Attended</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="quick-links">
                    <h3>Quick Links</h3>
                    <a href="index.php" class="quick-link">
                        <i class="fas fa-search"></i>
                        <span>Explore Events</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#" class="quick-link">
                        <i class="fas fa-bookmark"></i>
                        <span>My Bookmarks</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#" class="quick-link">
                        <i class="fas fa-clock"></i>
                        <span>Upcoming Events</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Registered Events Section -->
        <div class="registered-events-section">
            <h2><i class="fas fa-list"></i> My Registered Events</h2>
            
            <div class="events-list">
                <?php if (empty($registered_events)): ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                        <p>No registered events yet</p>
                        <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Explore Events</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($registered_events as $event): ?>
                        <div class="event-list-item <?php echo $event['status'] === 'attended' ? 'attended' : ''; ?>" data-event-id="<?php echo $event['id']; ?>">
                            <div class="event-list-image">
                                <?php
                                    $img_src = $event['poster_path'] ?? 'https://via.placeholder.com/200x150';
                                    // Hardcoded images fallback
                                    if ($event['id'] == 1) {
                                        $img_src = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRg8n-KE6-JeSQ52yyFr8ZRfxmKuWe1bso4pA&s';
                                    } elseif ($event['id'] == 2) {
                                        $img_src = 'https://boombarstick.com/wp-content/uploads/2020/11/67658690_10156869451539177_2868809369410076672_o-2.jpg';
                                    } elseif ($event['id'] == 3) {
                                        $img_src = 'https://img.freepik.com/free-vector/sport-events-cancelled-background_23-2148571973.jpg?semt=ais_incoming&w=740&q=80';
                                    }
                                ?>
                                <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                            </div>
                            <div class="event-list-details">
                                <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                <p class="event-club"><i class="fas fa-users"></i> <?php echo htmlspecialchars($event['club']); ?></p>
                                <div class="event-meta">
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($event['event_date'])); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($event['event_time'])); ?></span>
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue']); ?></span>
                                    <?php 
                                        $status_class = 'upcoming';
                                        $status_text = 'Upcoming';
                                        $status_icon = 'fa-clock';
                                        
                                        if ($event['status'] === 'attended') {
                                            $status_class = 'completed';
                                            $status_text = 'Attended';
                                            $status_icon = 'fa-check-circle';
                                        } elseif ($event['status'] === 'cancelled') {
                                            $status_class = 'cancelled';
                                            $status_text = 'Cancelled';
                                            $status_icon = 'fa-times-circle';
                                        } elseif (strtotime($event['event_date']) < strtotime('today')) {
                                            $status_class = 'completed';
                                            $status_text = 'Completed';
                                            $status_icon = 'fa-check-circle';
                                        }
                                    ?>
                                    <span class="status <?php echo $status_class; ?>"><i class="fas <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?></span>
                                </div>
                            </div>
                            <div class="event-list-actions">
                                <button class="btn btn-outline btn-sm">
                                    <i class="fas fa-info-circle"></i> Details
                                </button>
                                <?php if ($event['status'] === 'attended'): ?>
                                    <button class="btn btn-outline btn-sm">
                                        <i class="fas fa-star"></i> Rate Event
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline btn-sm cancel-event-btn" onclick="cancelEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title']); ?>')">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>

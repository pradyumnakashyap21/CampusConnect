
<?php
require_once 'config.php';
require_once 'functions.php';

// ================================================
// FETCH EVENTS FROM DATABASE
// ================================================

$events = [];

// Hardcoded sample events
$hardcoded_events = [
    [
        'id' => 1,
        'title' => 'Web Development Workshop',
        'club' => 'Tech Club',
        'category' => 'Technical',
        'date' => '2026-04-15',
        'time' => '14:00:00',
        'venue' => 'Computer Lab, Building A',
        'description' => 'Learn modern web development with HTML, CSS, and JavaScript. Perfect for beginners and intermediate developers.',
        'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRg8n-KE6-JeSQ52yyFr8ZRfxmKuWe1bso4pA&s',
        'attendees' => 45,
        'teams_participating' => 0,
        'featured' => true
    ],
    [
        'id' => 2,
        'title' => 'Annual Music Festival',
        'club' => 'Music Club',
        'category' => 'Cultural',
        'date' => '2026-04-20',
        'time' => '18:00:00',
        'venue' => 'Main Auditorium',
        'description' => 'Celebrate music with live performances from student bands and artists. Open to all college members.',
        'image' => 'https://boombarstick.com/wp-content/uploads/2020/11/67658690_10156869451539177_2868809369410076672_o-2.jpg',
        'attendees' => 120,
        'teams_participating' => 0,
        'featured' => true
    ],
    [
        'id' => 3,
        'title' => 'Inter-College Sports Championship',
        'club' => 'Sports Association',
        'category' => 'Sports',
        'date' => '2026-04-25',
        'time' => '09:00:00',
        'venue' => 'Sports Ground',
        'description' => 'Compete in various sports events including cricket, badminton, and football. Register your team today!',
        'image' => 'https://img.freepik.com/free-vector/sport-events-cancelled-background_23-2148571973.jpg?semt=ais_incoming&w=740&q=80',
        'attendees' => 200,
        'teams_participating' => 0,
        'featured' => true
    ]
];

foreach ($hardcoded_events as $row) {
    // Format date to match display format
    $row['date_display'] = date('M d, Y', strtotime($row['date']));
    $row['time_display'] = date('h:i A', strtotime($row['time']));
    $row['featured'] = (bool)$row['featured'];
    $events[] = $row;
}

// Filter logic
$filtered_events = $events;
$active_filters = [];

if (isset($_GET['category']) && $_GET['category'] !== '') {
    $filtered_events = array_filter($filtered_events, fn($e) => $e['category'] === $_GET['category']);
    $active_filters['category'] = $_GET['category'];
}

if (isset($_GET['club']) && $_GET['club'] !== '') {
    $filtered_events = array_filter($filtered_events, fn($e) => $e['club'] === $_GET['club']);
    $active_filters['club'] = $_GET['club'];
}

if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search = strtolower($_GET['search']);
    $filtered_events = array_filter($filtered_events, fn($e) => 
        strpos(strtolower($e['title']), $search) !== false || 
        strpos(strtolower($e['description']), $search) !== false
    );
    $active_filters['search'] = $_GET['search'];
}

$view = isset($_GET['view']) ? $_GET['view'] : 'grid';
$categories = array_unique(array_column($events, 'category'));
$clubs = array_unique(array_column($events, 'club'));
sort($categories);
sort($clubs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventHub - Discover College Events</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/search.css">
    <link rel="stylesheet" href="css/events.css">
    <link rel="stylesheet" href="css/modals.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-calendar-days"></i>
                <span>EventHub</span>
            </div>
            <div class="nav-links">
                <?php if (is_logged_in()): ?>
                    
                    <a href="dashboard.php" class="nav-link-btn dashboard-btn">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </a>
                    <a href="logout.php" class="nav-link-btn logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="nav-link-btn login-btn">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="signup.php" class="nav-link-btn signup-btn">
                        <i class="fas fa-user-plus"></i> Sign Up
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Discover Amazing College Events</h1>
            <p>Join workshops, competitions, concerts, and cultural celebrations</p>
        </div>
    </section>

    <main class="container">
        <!-- Search Bar -->
        <div class="search-bar">
            <form method="GET" class="search-form">
                <div class="search-input-wrapper">
                    <i class="fas fa-search"></i>
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by event name or description..." 
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                    >
                </div>
                <a href="index.php" class="btn-clear-search">Reset</a>
            </form>
        </div>

        <!-- Filters Section -->
        <div class="filters-wrapper">
            <form method="GET" class="filters-grid">
                <div class="filter-item">
                    <label for="category">
                        <i class="fas fa-tag"></i> Category
                    </label>
                    <select name="category" id="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : ''; ?>>
                                <?php echo $cat; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-item">
                    <label for="club">
                        <i class="fas fa-users"></i> Club
                    </label>
                    <select name="club" id="club" onchange="this.form.submit()">
                        <option value="">All Clubs</option>
                        <?php foreach ($clubs as $c): ?>
                            <option value="<?php echo $c; ?>" <?php echo (isset($_GET['club']) && $_GET['club'] === $c) ? 'selected' : ''; ?>>
                                <?php echo $c; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-item">
                    <label for="view">
                        <i class="fas fa-th"></i> View
                    </label>
                    <select name="view" id="view" onchange="this.form.submit()">
                        <option value="grid" <?php echo $view === 'grid' ? 'selected' : ''; ?>>Grid</option>
                        <option value="list" <?php echo $view === 'list' ? 'selected' : ''; ?>>List</option>
                    </select>
                </div>

                <?php if (!empty($active_filters)): ?>
                    <a href="index.php" class="filter-item btn-reset-filters">
                        <i class="fas fa-refresh"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Active Filters Display -->
        <?php if (!empty($active_filters)): ?>
            <div class="active-filters-bar">
                <span class="filter-count">
                    <i class="fas fa-filter"></i> Found <?php echo count($filtered_events); ?> event<?php echo count($filtered_events) !== 1 ? 's' : ''; ?>
                </span>
                <div class="filter-tags">
                    <?php foreach ($active_filters as $key => $value): ?>
                        <span class="tag">
                            <span><?php echo ucfirst($key) . ': ' . htmlspecialchars($value); ?></span>
                            <i class="fas fa-times"></i>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Events Grid/List -->
        <div class="events-container <?php echo $view === 'list' ? 'list-view' : 'grid-view'; ?>">
            <?php if (empty($filtered_events)): ?>
                <div class="no-events">
                    <div class="no-events-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3>No events found</h3>
                    <p>Try adjusting your filters or explore all events</p>
                    <a href="index.php" class="btn btn-primary">View All Events</a>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_events as $event): ?>
                    <div class="event-card <?php echo isset($event['featured']) && $event['featured'] ? 'featured' : ''; ?>">
                        <?php if (isset($event['featured']) && $event['featured']): ?>
                            <div class="featured-badge">⭐ Featured</div>
                        <?php endif; ?>
                        
                        <div class="event-image-container">
                            <img src="<?php echo $event['image']; ?>" alt="<?php echo $event['title']; ?>" class="event-image">
                            <div class="event-badges">
                                <span class="badge category-badge"><?php echo $event['category']; ?></span>
                                <span class="badge attendees-badge">
                                    <i class="fas fa-users"></i> <?php echo $event['attendees']; ?>
                                </span>
                            </div>
                        </div>

                        <div class="event-details">
                            <h3 class="event-title"><?php echo $event['title']; ?></h3>
                            <p class="event-club">
                                <i class="fas fa-building"></i> <?php echo $event['club']; ?>
                            </p>
                            
                            <p class="event-description">
                                <?php echo substr($event['description'], 0, 120) . '...'; ?>
                            </p>

                            <div class="event-info">
                                <span class="info-item">
                                    <i class="fas fa-calendar-alt"></i> 
                                    <?php echo $event['date_display']; ?>
                                </span>
                                <span class="info-item">
                                    <i class="fas fa-clock"></i> 
                                    <?php echo $event['time_display']; ?>
                                </span>
                                <span class="info-item">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo $event['venue']; ?>
                                </span>
                                <span class="info-item">
                                    <i class="fas fa-users"></i> 
                                    <?php echo $event['teams_participating']; ?> Teams
                                </span>
                            </div>

                            <div class="full-description" style="display:none;">
                                <?php echo $event['description']; ?>
                            </div>

                            <div class="event-actions">
                                <button class="btn btn-primary" onclick="openRegisterModal('<?php echo htmlspecialchars($event['title']); ?>', <?php echo $event['id']; ?>)">
                                    <i class="fas fa-sign-in-alt"></i> Register
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-overlay" onclick="closeUploadModal()"></div>
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-cloud-upload-alt"></i> Upload Event</h2>
                <button class="close-modal" onclick="closeUploadModal()"></button>
            </div>
            <form class="modal-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Event Title <span>*</span></label>
                        <input type="text" placeholder="e.g., Tech Summit 2025" required>
                    </div>
                    <div class="form-group">
                        <label>Club Name <span>*</span></label>
                        <input type="text" placeholder="e.g., Tech Club" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Category <span>*</span></label>
                        <select required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date <span>*</span></label>
                        <input type="date" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Time <span>*</span></label>
                        <input type="time" required>
                    </div>
                    <div class="form-group">
                        <label>Venue <span>*</span></label>
                        <input type="text" placeholder="Event location" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description <span>*</span></label>
                    <textarea placeholder="Describe your event..." rows="5" required></textarea>
                </div>

                <div class="form-group">
                    <label>Event Poster <span>*</span></label>
                    <div class="file-upload">
                        <input type="file" accept="image/*" required>
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload or drag image</p>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-check"></i> Upload Event
                </button>
            </form>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal">
        <div class="modal-overlay" onclick="closeRegisterModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-ticket-alt"></i> Register for Event</h2>
                <button class="close-modal" onclick="closeRegisterModal()"></button>
            </div>
            <p class="modal-subtitle" id="eventNameDisplay"></p>
            <form class="modal-form" id="registerForm">
                <input type="hidden" id="eventIdInput" name="event_id" value="">
                <div class="form-group">
                    <label>Full Name <span>*</span></label>
                    <input type="text" placeholder="Your full name" name="name" required>
                </div>
                <div class="form-group">
                    <label>Email Address <span>*</span></label>
                    <input type="email" placeholder="your@email.com" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone Number <span>*</span></label>
                    <input type="tel" placeholder="+91 XXXXXXXXXX" name="phone" required>
                </div>
                <div class="form-group">
                    <label>Roll Number <span>*</span></label>
                    <input type="text" placeholder="Your roll number" name="roll_number" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-check"></i> Complete Registration
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 EventHub. Discover • Participate • Celebrate.</p>
    </footer>

    <script>
        // Store the current event ID for registration
        let currentEventId = null;
        let currentEventTitle = null;

        function openUploadModal() {
            document.getElementById('uploadModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function openRegisterModal(eventName, eventId) {
            <?php if (!is_logged_in()): ?>
            alert("Please log in to register for an event.");
            window.location.href = "login.php";
            return;
            <?php endif; ?>
            
            currentEventTitle = eventName;
            currentEventId = eventId;
            document.getElementById('eventNameDisplay').textContent = 'Event: ' + eventName;
            document.getElementById('eventIdInput').value = eventId;
            document.getElementById('registerModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeRegisterModal() {
            document.getElementById('registerModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('registerForm').reset();
        }

        // Handle registration form submission
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('register_event.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Successfully registered for the event!');
                    closeRegisterModal();
                    // Refresh page or update dashboard
                    if (document.title.includes('Dashboard')) {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to register. Please try again.');
            }
        });

        function toggleDetails(btn) {
            const card = btn.closest('.event-card');
            const desc = card.querySelector('.full-description');
            if (desc.style.display === 'none') {
                desc.style.display = 'block';
                btn.classList.add('active');
                btn.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Details';
            } else {
                desc.style.display = 'none';
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fas fa-info-circle"></i> Details';
            }
        }

        window.onclick = function(event) {
            const uploadModal = document.getElementById('uploadModal');
            const registerModal = document.getElementById('registerModal');
            if (event.target === uploadModal) {
                uploadModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            if (event.target === registerModal) {
                registerModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
    </script>
</body>
</html>

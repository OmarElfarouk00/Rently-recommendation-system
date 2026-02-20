<!-- not working with this file -->


<?php
session_start();
require_once '../php files/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login-signup/login.php');
    exit;
}
$isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in
$userId = $_SESSION['user_id'];
$isLoggedIn = true;

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Current date for comparing booking status
$currentDate = date('Y-m-d');

// Build the query based on filtering and sorting
$query = "SELECT r.*, p.*, pi.image_path 
          FROM Rental r
          JOIN Property p ON r.id_property = p.id_property
          LEFT JOIN (
              SELECT property_id, MIN(image_path) as image_path 
              FROM Property_Images 
              GROUP BY property_id
          ) pi ON p.id_property = pi.property_id
          LEFT JOIN PropertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
          WHERE r.id_client = ?";

// Apply filters
if ($filter === 'active') {
    $query .= " AND r.startDate <= '$currentDate' AND DATE_ADD(r.startDate, INTERVAL r.duration DAY) >= '$currentDate' AND r.status = 1";
} elseif ($filter === 'upcoming') {
    $query .= " AND r.startDate > '$currentDate' AND r.status = 1";
} elseif ($filter === 'past') {
    $query .= " AND DATE_ADD(r.startDate, INTERVAL r.duration DAY) < '$currentDate' OR r.status = 0";
}

// Apply sorting
if ($sort === 'price_low') {
    $query .= " ORDER BY p.estimatePrice ASC";
} elseif ($sort === 'price_high') {
    $query .= " ORDER BY p.estimatePrice DESC";
} elseif ($sort === 'duration') {
    $query .= " ORDER BY r.duration DESC";
} elseif ($sort === 'start_date') {
    $query .= " ORDER BY r.startDate ASC";
} else { // newest
    $query .= " ORDER BY r.id DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    // Calculate end date
    $startDate = new DateTime($row['startDate']);
    $endDate = clone $startDate;
    $endDate->modify('+' . $row['duration'] . ' days');
    $row['endDate'] = $endDate->format('Y-m-d');
    
    // Calculate booking status
    $today = new DateTime();
    if ($row['status'] == 0) {
        $row['booking_status'] = 'cancelled';
    } elseif ($startDate > $today) {
        $row['booking_status'] = 'upcoming';
    } elseif ($endDate < $today) {
        $row['booking_status'] = 'completed';
    } else {
        $row['booking_status'] = 'active';
    }
    
    // Calculate days remaining
    if ($row['booking_status'] === 'active') {
        $daysRemaining = $today->diff($endDate)->days;
        $row['days_remaining'] = $daysRemaining;
    }
    
    $bookings[] = $row;
}

// Get counts for badges
$favQuery = "SELECT COUNT(*) as count FROM React WHERE id_client = ? AND count > 0";
$favStmt = $conn->prepare($favQuery);
$favStmt->bind_param("i", $userId);
$favStmt->execute();
$favResult = $favStmt->get_result();
$favoriteCount = $favResult->fetch_assoc()['count'];

// Get unread messages count
$msgQuery = "SELECT COUNT(*) as count FROM Messages WHERE recipient_id = ? AND is_read = 0";
$msgStmt = $conn->prepare($msgQuery);
$msgStmt->bind_param("i", $userId);
$msgStmt->execute();
$msgResult = $msgStmt->get_result();
$unreadMessages = $msgResult->fetch_assoc()['count'];

// Get unread notifications count
$notifQuery = "SELECT COUNT(*) as count FROM Notifications WHERE id_client = ? AND is_read = 0";
$notifStmt = $conn->prepare($notifQuery);
$notifStmt->bind_param("i", $userId);
$notifStmt->execute();
$notifResult = $notifStmt->get_result();
$unreadNotifications = $notifResult->fetch_assoc()['count'];

// Count bookings by status
$activeCount = 0;
$upcomingCount = 0;
$pastCount = 0;

foreach ($bookings as $booking) {
    if ($booking['booking_status'] === 'active') {
        $activeCount++;
    } elseif ($booking['booking_status'] === 'upcoming') {
        $upcomingCount++;
    } else {
        $pastCount++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - RentEstate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
    <style>
        /* Bookings Page Specific Styles */
        .bookings-container {
            padding: 20px;
        }
        
        .bookings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .bookings-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .bookings-count {
            color: #ff385c;
            font-weight: 500;
        }
        
        .bookings-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .bookings-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .bookings-dropdown-btn {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .bookings-dropdown-btn:hover {
            border-color: #bbb;
        }
        
        .bookings-dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            min-width: 160px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            z-index: 1;
            top: 100%;
            right: 0;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .bookings-dropdown-content a {
            color: #333;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .bookings-dropdown-content a:hover {
            background-color: #f5f5f5;
        }
        
        .bookings-dropdown-content a.active {
            background-color: #f0f0f0;
            font-weight: 500;
            color: #ff385c;
        }
        
        .bookings-dropdown.show .bookings-dropdown-content {
            display: block;
        }
        
        .booking-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .booking-tab {
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .booking-tab.active {
            background-color: #ff385c;
            color: white;
        }
        
        .booking-tab:not(.active) {
            background-color: #f5f5f5;
            color: #666;
        }
        
        .booking-tab:not(.active):hover {
            background-color: #eee;
        }
        
        .booking-tab-count {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        
        .booking-tab:not(.active) .booking-tab-count {
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        .bookings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .booking-card {
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }
        
        .booking-image-container {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        
        .booking-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .booking-card:hover .booking-image {
            transform: scale(1.05);
        }
        
        .booking-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        
        .booking-badge.active {
            background-color: #4caf50;
        }
        
        .booking-badge.upcoming {
            background-color: #2196f3;
        }
        
        .booking-badge.completed {
            background-color: #9e9e9e;
        }
        
        .booking-badge.cancelled {
            background-color: #f44336;
        }
        
        .booking-actions {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            gap: 10px;
        }
        
        .booking-action {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            color: #666;
            border: none;
        }
        
        .booking-action:hover {
            background-color: #fff;
            color: #ff385c;
        }
        
        .booking-content {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .booking-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .booking-location {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .booking-location i {
            margin-right: 5px;
            font-size: 16px;
            color: #ff385c;
        }
        
        .booking-price {
            font-size: 20px;
            font-weight: 600;
            color: #ff385c;
            margin-bottom: 10px;
        }
        
        .booking-price .period {
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }
        
        .booking-details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .booking-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .booking-detail-label {
            color: #666;
        }
        
        .booking-detail-value {
            font-weight: 500;
            color: #333;
        }
        
        .booking-progress {
            margin-top: 15px;
            margin-bottom: 15px;
        }
        
        .booking-progress-bar {
            height: 6px;
            background-color: #eee;
            border-radius: 3px;
            overflow: hidden;
            position: relative;
        }
        
        .booking-progress-fill {
            height: 100%;
            background-color: #4caf50;
            border-radius: 3px;
        }
        
        .booking-progress-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .booking-footer {
            margin-top: auto;
            padding-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .booking-btn {
            display: block;
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .booking-btn.primary {
            background-color: #ff385c;
            color: white;
        }
        
        .booking-btn.primary:hover {
            background-color: #e61e4d;
        }
        
        .booking-btn.secondary {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .booking-btn.secondary:hover {
            background-color: #eee;
        }
        
        .booking-btn.danger {
            background-color: #f44336;
            color: white;
        }
        
        .booking-btn.danger:hover {
            background-color: #d32f2f;
        }
        
        .booking-btn.disabled {
            background-color: #ddd;
            color: #999;
            cursor: not-allowed;
        }
        
        .booking-features {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            color: #666;
            font-size: 14px;
        }
        
        .booking-feature {
            display: flex;
            align-items: center;
        }
        
        .booking-feature i {
            margin-right: 5px;
            font-size: 16px;
        }
        
        .booking-countdown {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
            text-align: center;
        }
        
        .booking-countdown.urgent {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .booking-countdown-value {
            font-size: 18px;
            font-weight: 600;
            color: #ff385c;
        }
        
        .no-bookings {
            text-align: center;
            padding: 50px 20px;
            background-color: #f9f9f9;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        .no-bookings i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-bookings h3 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .no-bookings p {
            color: #666;
            margin-bottom: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .explore-btn {
            display: inline-block;
            background-color: #ff385c;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .explore-btn:hover {
            background-color: #e61e4d;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .form-label {
            font-size: 14px;
            font-weight: 500;
            color: #666;
        }
        
        .form-input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #ff385c;
        }
        
        .form-textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            min-height: 100px;
            resize: vertical;
        }
        
        .form-textarea:focus {
            outline: none;
            border-color: #ff385c;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .bookings-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .bookings-controls {
                width: 100%;
                justify-content: space-between;
            }
            
            .bookings-grid {
                grid-template-columns: 1fr;
            }
            
            .booking-tabs {
                padding-bottom: 15px;
            }
            
            .booking-tab {
                padding: 8px 15px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
            <!-- Sidebar Toggle Button -->
            <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Left Sidebar Menu -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <?php if ($isLoggedIn): ?>
                <div class="user-name"><?php echo '<h2 style="text-color:rgb(121, 198, 233)">WELCOME</h2> ',$_SESSION['user_name']; ?></div>
            <?php else: ?>
                <div class="user-name"><h2>Welcome </h2> Guest</div>
                <a href="../login-signup/index.php" class="menu-item">
                    <i class="fas fa-sign-in-alt"></i>
                    Login / Sign Up
                </a>
            <?php endif; ?>
        </div>

        <nav class="sidebar-menu">
            <div class="menu-section">
                <div class="menu-section-title">Main</div>
                <a href="../index.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    Home
                </a>

                <a href="map-view.php" class="menu-item">
                    <i class="fas fa-map-marked-alt"></i>
                    Map View
                </a>
            </div>

            <?php if ($isLoggedIn): ?>
            <div class="menu-section">
                <div class="menu-section-title">Personal</div>
                <a href="." class="menu-item">
                    <i class="fas fa-heart"></i>
                    Favorites
                    <?php if (isset($favoriteCount) && $favoriteCount > 0): ?>
                    <span class="menu-badge"><?php echo $favoriteCount; ?></span>
                    <?php endif; ?>
                </a>

                <a href="messages.php" class="menu-item">
                    <i class="fas fa-envelope"></i>
                    Messages
                    <?php if (isset($unreadMessages) && $unreadMessages > 0): ?>
                    <span class="menu-badge"><?php echo $unreadMessages; ?></span>
                    <?php endif; ?>
                </a>

            </div>

            <div class="menu-section">
                <div class="menu-section-title">Property Management</div>
                <a href="../my-properties.php" class="menu-item">
                    <i class="fas fa-building"></i>
                    My Properties
                </a>
                <a href="../become-host.php" class="menu-item">
                    <i class="fas fa-plus-circle"></i>
                    Add New Property
                </a>
                <a href="#" class="menu-item active">
                    <i class="fas fa-calendar-check"></i>
                    My Bookings
                </a>
                <a href="reviews.php" class="menu-item">
                    <i class="fas fa-star"></i>
                    Reviews
                </a>
            </div>
            <?php endif; ?>

            <div class="menu-section">
                <div class="menu-section-title">Settings</div>
                <a href="#" class="menu-item">
                    <i class="fas fa-cog"></i>
                    Account Settings
                </a>
                <a href="privacy.php" class="menu-item">
                    <i class="fas fa-shield-alt"></i>
                    Privacy & Security
                </a>

            </div>

            <div class="menu-section">
                <div class="menu-section-title">Support</div>
                <a href="../help-center.php" class="menu-item">
                    <i class="fas fa-question-circle"></i>
                    Help Center
                </a>

                <a href="../about-us.php" class="menu-item">
                    <i class="fas fa-info-circle"></i>
                    About Us
                </a>
            </div>

            <?php if ($isLoggedIn): ?>
            <div class="menu-section">
                <a href="../login-signup/php files/logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="page-wrapper" id="pageWrapper">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-home"></i>
                    <a href="../index.php" class="logo">RentEstate</a>
                </div>

                <div class="search-bar">
                    <input type="text" class="search-input" placeholder="Search destinations, properties...">
                </div>

                <div class="nav-item" id="rent-nav">
                    <i class="fas fa-handshake"></i>
                    Rent
                </div>

                <div class="nav-item" id="sell-nav">
                    <i class="fas fa-tag"></i>
                    Buy
                </div>

                <?php if ($isLoggedIn): ?>
                <!-- User Profile with Active Status -->
                <div class="user-profile">
                    <div class="user-status">
                        <span class="status-indicator"></span>
                        <span class="username"><?php echo $_SESSION['user_name']; ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <div class="user-menu">
                    <div class="user-menu-item menu-dropdown">
                        <i class="fas fa-bars" id="menuToggle"></i>
                        <div class="menu-content" id="menuContent">
                            <!-- Menu content here -->
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <div class="bookings-container">
                <div class="bookings-header">
                    <div>
                        <h1 class="bookings-title">My Bookings</h1>
                        <p class="bookings-count"><?php echo count($bookings); ?> bookings</p>
                    </div>
                    
                    <div class="bookings-controls">
                        <!-- Sort Dropdown -->
                        <div class="bookings-dropdown" id="sortDropdown">
                            <button class="bookings-dropdown-btn">
                                <i class="fas fa-sort"></i>
                                Sort: 
                                <?php 
                                    if ($sort === 'price_low') echo 'Price (Low to High)';
                                    elseif ($sort === 'price_high') echo 'Price (High to Low)';
                                    elseif ($sort === 'duration') echo 'Duration';
                                    elseif ($sort === 'start_date') echo 'Start Date';
                                    else echo 'Newest';
                                ?>
                            </button>
                            <div class="bookings-dropdown-content">
                                <a href="?sort=newest<?php echo $filter !== 'all' ? '&filter='.$filter : ''; ?>" class="<?php echo $sort === 'newest' ? 'active' : ''; ?>">Newest</a>
                                <a href="?sort=price_low<?php echo $filter !== 'all' ? '&filter='.$filter : ''; ?>" class="<?php echo $sort === 'price_low' ? 'active' : ''; ?>">Price (Low to High)</a>
                <a href="?sort=price_high<?php echo $filter !== 'all' ? '&filter='.$filter : ''; ?>" class="<?php echo $sort === 'price_high' ? 'active' : ''; ?>">Price (High to Low)</a>
                <a href="?sort=duration<?php echo $filter !== 'all' ? '&filter='.$filter : ''; ?>" class="<?php echo $sort === 'duration' ? 'active' : ''; ?>">Duration</a>
                <a href="?sort=start_date<?php echo $filter !== 'all' ? '&filter='.$filter : ''; ?>" class="<?php echo $sort === 'start_date' ? 'active' : ''; ?>">Start Date</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Booking Tabs -->
                <div class="booking-tabs">
                    <a href="?filter=all<?php echo $sort !== 'newest' ? '&sort='.$sort : ''; ?>" class="booking-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> All
                        <span class="booking-tab-count"><?php echo count($bookings); ?></span>
                    </a>
                    <a href="?filter=active<?php echo $sort !== 'newest' ? '&sort='.$sort : ''; ?>" class="booking-tab <?php echo $filter === 'active' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Active
                        <span class="booking-tab-count"><?php echo $activeCount; ?></span>
                    </a>
                    <a href="?filter=upcoming<?php echo $sort !== 'newest' ? '&sort='.$sort : ''; ?>" class="booking-tab <?php echo $filter === 'upcoming' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-plus"></i> Upcoming
                        <span class="booking-tab-count"><?php echo $upcomingCount; ?></span>
                    </a>
                    <a href="?filter=past<?php echo $sort !== 'newest' ? '&sort='.$sort : ''; ?>" class="booking-tab <?php echo $filter === 'past' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-times"></i> Past
                        <span class="booking-tab-count"><?php echo $pastCount; ?></span>
                    </a>
                </div>
                
                <?php if (count($bookings) > 0): ?>
                    <div class="bookings-grid">
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-image-container">
                                    <img src="<?php echo !empty($booking['image_path']) ? $booking['image_path'] : 'assets/images/property-placeholder.jpg'; ?>" alt="<?php echo $booking['title']; ?>" class="booking-image">
                                    
                                    <!-- Booking Status Badge -->
                                    <div class="booking-badge <?php echo $booking['booking_status']; ?>">
                                        <?php 
                                            if ($booking['booking_status'] === 'active') echo 'Active';
                                            elseif ($booking['booking_status'] === 'upcoming') echo 'Upcoming';
                                            elseif ($booking['booking_status'] === 'completed') echo 'Completed';
                                            else echo 'Cancelled';
                                        ?>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="booking-actions">
                                        <button class="booking-action" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)" title="View details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($booking['booking_status'] === 'active' || $booking['booking_status'] === 'upcoming'): ?>
                                        <button class="booking-action" onclick="contactOwner(<?php echo $booking['id_property']; ?>, '<?php echo htmlspecialchars($booking['owner_name']); ?>')" title="Contact owner">
                                            <i class="fas fa-comment"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="booking-content">
                                    <h3 class="booking-title"><?php echo $booking['title']; ?></h3>
                                    <div class="booking-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo $booking['address'] . ', ' . $booking['city']; ?>
                                    </div>
                                    
                                    <div class="booking-price">
                                        $<?php echo number_format($booking['estimatePrice']); ?>
                                        <span class="period">/ month</span>
                                    </div>
                                    
                                    <div class="booking-features">
                                        <?php if (!empty($booking['bedrooms'])): ?>
                                        <div class="booking-feature">
                                            <i class="fas fa-bed"></i>
                                            <?php echo $booking['bedrooms']; ?> Beds
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($booking['bathrooms'])): ?>
                                        <div class="booking-feature">
                                            <i class="fas fa-bath"></i>
                                            <?php echo $booking['bathrooms']; ?> Baths
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($booking['size'])): ?>
                                        <div class="booking-feature">
                                            <i class="fas fa-vector-square"></i>
                                            <?php echo $booking['size']; ?> sqft
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="booking-details">
                                        <div class="booking-detail">
                                            <span class="booking-detail-label">Check-in:</span>
                                            <span class="booking-detail-value"><?php echo date('M d, Y', strtotime($booking['startDate'])); ?></span>
                                        </div>
                                        <div class="booking-detail">
                                            <span class="booking-detail-label">Check-out:</span>
                                            <span class="booking-detail-value"><?php echo date('M d, Y', strtotime($booking['endDate'])); ?></span>
                                        </div>
                                        <div class="booking-detail">
                                            <span class="booking-detail-label">Duration:</span>
                                            <span class="booking-detail-value"><?php echo $booking['duration']; ?> days</span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($booking['booking_status'] === 'active'): ?>
                                    <div class="booking-progress">
                                        <?php 
                                            $startDate = new DateTime($booking['startDate']);
                                            $endDate = new DateTime($booking['endDate']);
                                            $today = new DateTime();
                                            $totalDays = $startDate->diff($endDate)->days;
                                            $daysElapsed = $startDate->diff($today)->days;
                                            $progressPercentage = min(100, max(0, ($daysElapsed / $totalDays) * 100));
                                        ?>
                                        <div class="booking-progress-bar">
                                            <div class="booking-progress-fill" style="width: <?php echo $progressPercentage; ?>%"></div>
                                        </div>
                                        <div class="booking-progress-labels">
                                            <span><?php echo date('M d', strtotime($booking['startDate'])); ?></span>
                                            <span><?php echo date('M d', strtotime($booking['endDate'])); ?></span>
                                        </div>
                                        
                                        <?php if (isset($booking['days_remaining'])): ?>
                                        <div class="booking-countdown <?php echo $booking['days_remaining'] <= 3 ? 'urgent' : ''; ?>">
                                            <span class="booking-countdown-value"><?php echo $booking['days_remaining']; ?></span> days remaining
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="booking-footer">
                                        <?php if ($booking['booking_status'] === 'active'): ?>
                                            <button class="booking-btn primary" onclick="extendBooking(<?php echo $booking['id']; ?>)">
                                                <i class="fas fa-calendar-plus"></i> Extend Booking
                                            </button>
                                            <button class="booking-btn danger" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                                <i class="fas fa-times-circle"></i> Cancel Booking
                                            </button>
                                        <?php elseif ($booking['booking_status'] === 'upcoming'): ?>
                                            <button class="booking-btn primary" onclick="modifyBooking(<?php echo $booking['id']; ?>)">
                                                <i class="fas fa-edit"></i> Modify Booking
                                            </button>
                                            <button class="booking-btn danger" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                                <i class="fas fa-times-circle"></i> Cancel Booking
                                            </button>
                                        <?php elseif ($booking['booking_status'] === 'completed'): ?>
                                            <a href="reviews.php?property_id=<?php echo $booking['id_property']; ?>" class="booking-btn primary">
                                                <i class="fas fa-star"></i> Write a Review
                                            </a>
                                            <a href="property-details.php?id=<?php echo $booking['id_property']; ?>" class="booking-btn secondary">
                                                <i class="fas fa-home"></i> View Property
                                            </a>
                                        <?php else: ?>
                                            <a href="property-details.php?id=<?php echo $booking['id_property']; ?>" class="booking-btn secondary">
                                                <i class="fas fa-home"></i> View Property
                                            </a>
                                            <button class="booking-btn primary" onclick="bookAgain(<?php echo $booking['id_property']; ?>)">
                                                <i class="fas fa-redo"></i> Book Again
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-bookings">
                        <i class="far fa-calendar-times"></i>
                        <h3>No bookings found</h3>
                        <p>You don't have any bookings yet. Start exploring properties and book your next stay!</p>
                        <a href="explore.php" class="explore-btn">Explore Properties</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Extend Booking Modal -->
    <div id="extendBookingModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('extendBookingModal')">&times;</span>
            <h2 class="modal-title">Extend Your Booking</h2>
            <form id="extendBookingForm" class="modal-form">
                <input type="hidden" id="extendBookingId" name="booking_id">
                <div class="form-group">
                    <label class="form-label" for="extendDays">Additional Days</label>
                    <input type="number" id="extendDays" name="extend_days" class="form-input" min="1" max="30" value="7">
                </div>
                <div class="form-group">
                    <label class="form-label" for="extendReason">Reason (Optional)</label>
                    <textarea id="extendReason" name="extend_reason" class="form-textarea" placeholder="Why do you need to extend your stay?"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="booking-btn secondary" onclick="closeModal('extendBookingModal')">Cancel</button>
                    <button type="submit" class="booking-btn primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Cancel Booking Modal -->
    <div id="cancelBookingModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('cancelBookingModal')">&times;</span>
            <h2 class="modal-title">Cancel Your Booking</h2>
            <form id="cancelBookingForm" class="modal-form">
                <input type="hidden" id="cancelBookingId" name="booking_id">
                <div class="form-group">
                    <label class="form-label" for="cancelReason">Reason for Cancellation</label>
                    <select id="cancelReason" name="cancel_reason" class="form-input">
                        <option value="change_of_plans">Change of Plans</option>
                        <option value="found_better_option">Found a Better Option</option>
                        <option value="emergency">Emergency</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="cancelDetails">Additional Details</label>
                    <textarea id="cancelDetails" name="cancel_details" class="form-textarea" placeholder="Please provide more details about your cancellation"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="booking-btn secondary" onclick="closeModal('cancelBookingModal')">Go Back</button>
                    <button type="submit" class="booking-btn danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modify Booking Modal -->
    <div id="modifyBookingModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('modifyBookingModal')">&times;</span>
            <h2 class="modal-title">Modify Your Booking</h2>
            <form id="modifyBookingForm" class="modal-form">
                <input type="hidden" id="modifyBookingId" name="booking_id">
                <div class="form-group">
                    <label class="form-label" for="modifyStartDate">New Start Date</label>
                    <input type="date" id="modifyStartDate" name="start_date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label" for="modifyDuration">Duration (Days)</label>
                    <input type="number" id="modifyDuration" name="duration" class="form-input" min="1" value="30">
                </div>
                <div class="form-group">
                    <label class="form-label" for="modifyNotes">Additional Notes</label>
                    <textarea id="modifyNotes" name="notes" class="form-textarea" placeholder="Any special requests or information"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="booking-btn secondary" onclick="closeModal('modifyBookingModal')">Cancel</button>
                    <button type="submit" class="booking-btn primary">Submit Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle functionality
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const pageWrapper = document.getElementById('pageWrapper');
            
            function checkWidth() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('expanded');
                    pageWrapper.classList.add('full-width');
                } else {
                    sidebar.classList.remove('collapsed');
                    pageWrapper.classList.remove('full-width');
                }
            }
            
            checkWidth();
            
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.toggle('expanded');
                    sidebarOverlay.classList.toggle('active');
                } else {
                    sidebar.classList.toggle('collapsed');
                    pageWrapper.classList.toggle('full-width');
                }
            });
            
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('expanded');
                sidebarOverlay.classList.remove('active');
            });
            
            window.addEventListener('resize', checkWidth);
            
            // Dropdown functionality
            const dropdowns = document.querySelectorAll('.bookings-dropdown');
            
            dropdowns.forEach(dropdown => {
                const btn = dropdown.querySelector('.bookings-dropdown-btn');
                
                btn.addEventListener('click', function() {
                    dropdown.classList.toggle('show');
                });
                
                // Close dropdown when clicking outside
                window.addEventListener('click', function(event) {
                    if (!dropdown.contains(event.target)) {
                        dropdown.classList.remove('show');
                    }
                });
            });
            
            // Form submissions
            document.getElementById('extendBookingForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const bookingId = document.getElementById('extendBookingId').value;
                const days = document.getElementById('extendDays').value;
                
                // Here you would normally send an AJAX request to the server
                alert(`Extension request submitted for booking #${bookingId} for ${days} additional days.`);
                closeModal('extendBookingModal');
            });
            
            document.getElementById('cancelBookingForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const bookingId = document.getElementById('cancelBookingId').value;
                
                // Here you would normally send an AJAX request to the server
                alert(`Cancellation request submitted for booking #${bookingId}.`);
                closeModal('cancelBookingModal');
                // Reload the page to reflect changes
                window.location.reload();
            });
            
            document.getElementById('modifyBookingForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const bookingId = document.getElementById('modifyBookingId').value;
                
                // Here you would normally send an AJAX request to the server
                alert(`Modification request submitted for booking #${bookingId}.`);
                closeModal('modifyBookingModal');
            });
        });
        
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Booking actions
        function extendBooking(bookingId) {
            document.getElementById('extendBookingId').value = bookingId;
            openModal('extendBookingModal');
        }
        
        function cancelBooking(bookingId) {
            document.getElementById('cancelBookingId').value = bookingId;
            openModal('cancelBookingModal');
        }
        
        function modifyBooking(bookingId) {
            document.getElementById('modifyBookingId').value = bookingId;
            // Here you would normally fetch the current booking details and populate the form
            openModal('modifyBookingModal');
        }
        
        function viewBookingDetails(bookingId) {
            // Redirect to a booking details page or show a modal with details
            alert(`Viewing details for booking #${bookingId}`);
        }
        
        function contactOwner(propertyId, ownerName) {
            // Redirect to messages page with pre-filled message to owner
            window.location.href = `messages.php?property_id=${propertyId}&owner=${encodeURIComponent(ownerName)}`;
        }
        
        function bookAgain(propertyId) {
            // Redirect to property details page with booking form
            window.location.href = `property-details.php?id=${propertyId}&book=true`;
        }
    </script>
</body>
</html>
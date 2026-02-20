<!-- not working with this -->
<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$isLoggedIn = true;

// Check if user is a property owner
$ownerQuery = "SELECT * FROM PropertyOwner WHERE id_propertyOwner = ?";
$ownerStmt = $conn->prepare($ownerQuery);
$ownerStmt->bind_param("i", $userId);
$ownerStmt->execute();
$ownerResult = $ownerStmt->get_result();
$isPropertyOwner = $ownerResult->num_rows > 0;

// Get user's properties if they are an owner
$properties = [];
if ($isPropertyOwner) {
    $propertiesQuery = "SELECT p.*, 
                        (SELECT COUNT(*) FROM React WHERE id_property = p.id_property AND count > 0) as like_count,
                        (SELECT COUNT(*) FROM PropertyReviews WHERE property_id = p.id_property) as review_count,
                        (SELECT AVG(rating) FROM PropertyReviews WHERE property_id = p.id_property) as avg_rating,
                        pi.image_path
                      FROM Property p
                      LEFT JOIN (
                          SELECT property_id, MIN(image_path) as image_path 
                          FROM Property_Images 
                          GROUP BY property_id
                      ) pi ON p.id_property = pi.property_id
                      WHERE p.id_propertyOwner = ?
                      ORDER BY p.id_property DESC";
    $propertiesStmt = $conn->prepare($propertiesQuery);
    $propertiesStmt->bind_param("i", $userId);
    $propertiesStmt->execute();
    $propertiesResult = $propertiesStmt->get_result();
    
    while ($row = $propertiesResult->fetch_assoc()) {
        $properties[] = $row;
    }
}

// Get properties the user has reviewed
$reviewedPropertiesQuery = "SELECT pr.*, p.title, p.address, p.city, p.estimatePrice, p.status, pi.image_path
                           FROM PropertyReviews pr
                           JOIN Property p ON pr.property_id = p.id_property
                           LEFT JOIN (
                               SELECT property_id, MIN(image_path) as image_path 
                               FROM Property_Images 
                               GROUP BY property_id
                           ) pi ON p.id_property = pi.property_id
                           WHERE pr.user_id = ?
                           ORDER BY pr.created_at DESC";
$reviewedPropertiesStmt = $conn->prepare($reviewedPropertiesQuery);
$reviewedPropertiesStmt->bind_param("i", $userId);
$reviewedPropertiesStmt->execute();
$reviewedPropertiesResult = $reviewedPropertiesStmt->get_result();
$reviewedProperties = [];

while ($row = $reviewedPropertiesResult->fetch_assoc()) {
    $reviewedProperties[] = $row;
}

// Handle form submission for adding/editing a review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $propertyId = (int)$_POST['property_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    // Check if user has already reviewed this property
    $checkReviewQuery = "SELECT * FROM PropertyReviews WHERE property_id = ? AND user_id = ?";
    $checkReviewStmt = $conn->prepare($checkReviewQuery);
    $checkReviewStmt->bind_param("ii", $propertyId, $userId);
    $checkReviewStmt->execute();
    $checkReviewResult = $checkReviewStmt->get_result();
    
    if ($checkReviewResult->num_rows > 0) {
        // Update existing review
        $updateReviewQuery = "UPDATE PropertyReviews SET rating = ?, comment = ?, updated_at = NOW() WHERE property_id = ? AND user_id = ?";
        $updateReviewStmt = $conn->prepare($updateReviewQuery);
        $updateReviewStmt->bind_param("isii", $rating, $comment, $propertyId, $userId);
        
        if ($updateReviewStmt->execute()) {
            $successMessage = "Your review has been updated successfully.";
        } else {
            $errorMessage = "Error updating review: " . $conn->error;
        }
    } else {
        // Insert new review
        $insertReviewQuery = "INSERT INTO PropertyReviews (property_id, user_id, rating, comment, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
        $insertReviewStmt = $conn->prepare($insertReviewQuery);
        $insertReviewStmt->bind_param("iiis", $propertyId, $userId, $rating, $comment);
        
        if ($insertReviewStmt->execute()) {
            $successMessage = "Your review has been submitted successfully.";
        } else {
            $errorMessage = "Error submitting review: " . $conn->error;
        }
    }
    
    // Redirect to refresh the page
    header("Location: reviews.php");
    exit;
}

// Get favorite count for badge
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - RentEstate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Reviews Page Specific Styles */
        .reviews-container {
            padding: 20px;
        }
        
        .reviews-header {
            margin-bottom: 30px;
        }
        
        .reviews-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .reviews-subtitle {
            color: #666;
            font-size: 16px;
        }
        
        .reviews-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .reviews-tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .reviews-tab.active {
            color: #ff385c;
            border-bottom-color: #ff385c;
        }
        
        .reviews-tab:hover {
            color: #ff385c;
        }
        
        .reviews-section {
            display: none;
        }
        
        .reviews-section.active {
            display: block;
        }
        
        .property-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .property-header {
            display: flex;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .property-image {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .property-info {
            flex: 1;
        }
        
        .property-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .property-location {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .property-location i {
            margin-right: 5px;
            font-size: 16px;
        }
        
        .property-stats {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        
        .property-stat {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 14px;
        }
        
        .property-stat i {
            margin-right: 5px;
            color: #ff385c;
        }
        
        .property-rating {
            display: flex;
            align-items: center;
            margin-top: 5px;
        }
        
        .rating-stars {
            color: #ffc107;
            margin-right: 5px;
        }
        
        .rating-value {
            font-weight: 600;
            margin-right: 5px;
        }
        
        .rating-count {
            color: #666;
            font-size: 14px;
        }
        
        .reviews-list {
            padding: 15px;
        }
        
        .review-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .review-item:last-child {
            border-bottom: none;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .reviewer-info {
            display: flex;
            align-items: center;
        }
        
        .reviewer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        
        .reviewer-name {
            font-weight: 600;
            color: #333;
        }
        
        .review-date {
            color: #999;
            font-size: 12px;
            margin-top: 3px;
        }
        
        .review-rating {
            color: #ffc107;
        }
        
        .review-content {
            color: #555;
            line-height: 1.5;
            margin-top: 10px;
        }
        
        .review-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }
        
        .review-action {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .review-action i {
            margin-right: 5px;
        }
        
        .review-action:hover {
            color: #ff385c;
        }
        
        .add-review-btn {
            background-color: #ff385c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
            display: inline-block;
            margin-top: 10px;
        }
        
        .add-review-btn:hover {
            background-color: #e61e4d;
        }
        
        .review-form {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        .rating-input {
            display: flex;
            gap: 5px;
        }
        
        .rating-input label {
            cursor: pointer;
            font-size: 24px;
            color: #ddd;
        }
        
        .rating-input input {
            display: none;
        }
        
        .rating-input label:hover,
        .rating-input label:hover ~ label,
        .rating-input input:checked ~ label {
            color: #ffc107;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .btn-primary {
            background-color: #ff385c;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #e61e4d;
        }
        
        .btn-secondary {
            background-color: #f0f0f0;
            color: #666;
        }
        
        .btn-secondary:hover {
            background-color: #e0e0e0;
        }
        
        .no-reviews {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #388e3c;
            border: 1px solid #c8e6c9;
        }
        
        .alert-danger {
            background-color: #ffebee;
            color: #d32f2f;
            border: 1px solid #ffcdd2;
        }
        
        @media (max-width: 768px) {
            .property-header {
                flex-direction: column;
            }
            
            .property-image {
                width: 100%;
                height: 200px;
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .reviews-tabs {
                overflow-x: auto;
                white-space: nowrap;
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
                <img src="<?php echo isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'assets/images/default-avatar.png'; ?>" alt="User Avatar">
                <div class="user-name"><?php echo $_SESSION['user_name']; ?></div>
                <div class="user-email"><?php echo $_SESSION['user_email']; ?></div>
            <?php else: ?>
                <div class="user-name">Welcome, Guest</div>
                <a href="login.php" class="menu-item">
                    <i class="fas fa-sign-in-alt"></i>
                    Login / Sign Up
                </a>
            <?php endif; ?>
        </div>

        <nav class="sidebar-menu">
            <div class="menu-section">
                <div class="menu-section-title">Main</div>
                <a href="index.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    Home
                </a>
                <a href="explore.php" class="menu-item">
                    <i class="fas fa-compass"></i>
                    Explore
                </a>
                <!-- <a href="map-view.php" class="menu-item">
                    <i class="fas fa-map-marked-alt"></i>
                    Map View
                </a> -->
            </div>

            <?php if ($isLoggedIn): ?>
            <div class="menu-section">
                <div class="menu-section-title">Personal</div>
                <a href="favorites.php" class="menu-item">
                    <i class="fas fa-heart"></i>
                    Favorites
                    <?php if ($favoriteCount > 0): ?>
                    <span class="menu-badge"><?php echo $favoriteCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="history.php" class="menu-item">
                    <i class="fas fa-history"></i>
                    Browsing History
                </a>
                <a href="messages.php" class="menu-item">
                    <i class="fas fa-envelope"></i>
                    Messages
                    <?php if ($unreadMessages > 0): ?>
                    <span class="menu-badge"><?php echo $unreadMessages; ?></span>
                    <?php endif; ?>
                </a>
                <a href="notifications.php" class="menu-item">
                    <i class="fas fa-bell"></i>
                    Notifications
                    <?php if ($unreadNotifications > 0): ?>
                    <span class="menu-badge"><?php echo $unreadNotifications; ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Property Management</div>
                <a href="my-properties.php" class="menu-item">
                    <i class="fas fa-building"></i>
                    My Properties
                </a>
                <a href="become-host.php" class="menu-item">
                    <i class="fas fa-plus-circle"></i>
                    Add New Property
                </a>
                <a href="bookings.php" class="menu-item">
                    <i class="fas fa-calendar-check"></i>
                    My Bookings
                </a>
                <a href="reviews.php" class="menu-item active">
                    <i class="fas fa-star"></i>
                    Reviews
                </a>
            </div>
            <?php endif; ?>

            <div class="menu-section">
                <div class="menu-section-title">Settings</div>
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    Account Settings
                </a>
                <a href="privacy.php" class="menu-item">
                    <i class="fas fa-shield-alt"></i>
                    Privacy & Security
                </a>
                <a href="preferences.php" class="menu-item">
                    <i class="fas fa-sliders-h"></i>
                    Preferences
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Support</div>
                <a href="help-center.php" class="menu-item">
                    <i class="fas fa-question-circle"></i>
                    Help Center
                </a>
                <a href="contact.php" class="menu-item">
                    <i class="fas fa-envelope"></i>
                    Contact Us
                </a>
                <a href="about-us.php" class="menu-item">
                    <i class="fas fa-info-circle"></i>
                    About Us
                </a>
            </div>

            <?php if ($isLoggedIn): ?>
            <div class="menu-section">
                <a href="logout.php" class="menu-item">
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
                <a href="../index.php" class="logo">
                    <img src="../../rently2.png" alt="" style="height: 38px; width: 130px;">
                </a>
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

                            <div class="notif">
                <?php include '../includes/notifications.php'; ?>
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
            <div class="reviews-container">
                <div class="reviews-header">
                    <h1 class="reviews-title">Reviews</h1>
                    <p class="reviews-subtitle">Manage reviews for your properties and see your submitted reviews</p>
                </div>
                
                <?php if (isset($successMessage)): ?>
                <div class="alert alert-success">
                    <?php echo $successMessage; ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($errorMessage)): ?>
                <div class="alert alert-danger">
                    <?php echo $errorMessage; ?>
                </div>
                <?php endif; ?>
                
                <div class="reviews-tabs">
                    <?php if ($isPropertyOwner): ?>
                    <div class="reviews-tab active" data-tab="my-properties">My Properties Reviews</div>
                    <?php endif; ?>
                    <div class="reviews-tab <?php echo !$isPropertyOwner ? 'active' : ''; ?>" data-tab="my-reviews">My Reviews</div>
                </div>
                
                <?php if ($isPropertyOwner): ?>
                <!-- My Properties Reviews Section -->
                <div class="reviews-section active" id="my-properties-section">
                    <?php if (count($properties) > 0): ?>
                        <?php foreach ($properties as $property): ?>
                            <div class="property-card">
                                <div class="property-header">
                                    <img src="<?php echo !empty($property['image_path']) ? $property['image_path'] : 'assets/images/property-placeholder.jpg'; ?>" alt="<?php echo $property['title']; ?>" class="property-image">
                                    <div class="property-info">
                                        <h3 class="property-title"><?php echo $property['title']; ?></h3>
                                        <div class="property-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo $property['address'] . ', ' . $property['city']; ?>
                                        </div>
                                        <div class="property-stats">
                                            <div class="property-stat">
                                                <i class="fas fa-heart"></i>
                                                <?php echo $property['like_count']; ?> likes
                                            </div>
                                            <div class="property-stat">
                                                <i class="fas fa-comment"></i>
                                                <?php echo $property['review_count']; ?> reviews
                                            </div>
                                        </div>
                                        <?php if ($property['review_count'] > 0): ?>
                                        <div class="property-rating">
                                            <div class="rating-stars">
                                                <?php 
                                                    $rating = round($property['avg_rating'] * 2) / 2; // Round to nearest 0.5
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $rating) {
                                                            echo '<i class="fas fa-star"></i>';
                                                        } else if ($i - 0.5 == $rating) {
                                                            echo '<i class="fas fa-star-half-alt"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star"></i>';
                                                        }
                                                    }
                                                ?>
                                            </div>
                                            <span class="rating-value"><?php echo number_format($property['avg_rating'], 1); ?></span>
                                            <span class="rating-count">(<?php echo $property['review_count']; ?> reviews)</span>
                                        </div>
                                        <?php else: ?>
                                        <div class="property-rating">
                                            <span class="rating-count">No reviews yet</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($property['review_count'] > 0): ?>
                                    <?php 
                                        // Get reviews for this property
                                        $reviewsQuery = "SELECT pr.*, c.full_name as reviewer_name
                                                        FROM PropertyReviews pr
                                                        JOIN Client c ON pr.user_id = c.id_client
                                                        WHERE pr.property_id = ?
                                                        ORDER BY pr.created_at DESC";
                                        $reviewsStmt = $conn->prepare($reviewsQuery);
                                        $reviewsStmt->bind_param("i", $property['id_property']);
                                        $reviewsStmt->execute();
                                        $reviewsResult = $reviewsStmt->get_result();
                                        $reviews = [];
                                        
                                        while ($row = $reviewsResult->fetch_assoc()) {
                                            $reviews[] = $row;
                                        }
                                    ?>
                                    <div class="reviews-list">
                                        <?php foreach ($reviews as $review): ?>
                                            <div class="review-item">
                                                <div class="review-header">
                                                    <div class="reviewer-info">
                                                        <img src="assets/images/default-avatar.png" alt="<?php echo $review['reviewer_name']; ?>" class="reviewer-avatar">
                                                        <div>
                                                            <div class="reviewer-name"><?php echo $review['reviewer_name']; ?></div>
                                                            <div class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="review-rating">
                                                        <?php 
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                if ($i <= $review['rating']) {
                                                                    echo '<i class="fas fa-star"></i>';
                                                                } else {
                                                                    echo '<i class="far fa-star"></i>';
                                                                }
                                                            }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="review-content">
                                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                                </div>
                                                <div class="review-actions">
                                                    <button class="review-action reply-btn" data-review-id="<?php echo $review['id']; ?>">
                                                        <i class="fas fa-reply"></i> Reply
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="no-reviews">
                                        <i class="far fa-star" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                                        <h3>No reviews yet</h3>
                                        <p>Your property hasn't received any reviews yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-reviews">
                            <i class="fas fa-building" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                            <h3>No properties found</h3>
                            <p>You don't have any properties listed yet.</p>
                            <a href="become-host.php" class="add-review-btn">
                                Add a Property
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- My Reviews Section -->
                <div class="reviews-section <?php echo !$isPropertyOwner ? 'active' : ''; ?>" id="my-reviews-section">
                    <?php if (count($reviewedProperties) > 0): ?>
                        <?php foreach ($reviewedProperties as $review): ?>
                            <div class="property-card">
                                <div class="property-header">
                                    <img src="<?php echo !empty($review['image_path']) ? $review['image_path'] : 'assets/images/property-placeholder.jpg'; ?>" alt="<?php echo $review['title']; ?>" class="property-image">
                                    <div class="property-info">
                                        <h3 class="property-title"><?php echo $review['title']; ?></h3>
                                        <div class="property-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo $review['address'] . ', ' . $review['city']; ?>
                                        </div>
                                        <div class="property-rating">
                                            <div class="rating-stars">
                                                <?php 
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $review['rating']) {
                                                            echo '<i class="fas fa-star"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star"></i>';
                                                        }
                                                    }
                                                ?>
                                            </div>
                                            <span class="rating-value"><?php echo $review['rating']; ?>.0</span>
                                            <span class="review-date">(Reviewed on <?php echo date('F j, Y', strtotime($review['created_at'])); ?>)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="reviews-list">
                                    <div class="review-item">
                                        <div class="review-content">
                                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                        </div>
                                        <div class="review-actions">
                                            <button class="review-action edit-btn" data-review-id="<?php echo $review['id']; ?>" data-property-id="<?php echo $review['property_id']; ?>" data-rating="<?php echo $review['rating']; ?>" data-comment="<?php echo htmlspecialchars($review['comment']); ?>">
                                                <i class="fas fa-edit"></i> Edit Review
                                            </button>
                                            <button class="review-action delete-btn" data-review-id="<?php echo $review['id']; ?>">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-reviews">
                            <i class="far fa-star" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                            <h3>No reviews submitted</h3>
                            <p>You haven't submitted any reviews yet.</p>
                            <a href="explore.php" class="add-review-btn">
                                Explore Properties
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Review Form Modal (hidden by default) -->
            <div id="reviewFormModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
                <div style="background-color: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin: 0;">Write a Review</h3>
                        <button id="closeReviewModal" style="background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
                    </div>
                    <form id="reviewForm" method="POST" action="reviews.php">
                        <input type="hidden" name="property_id" id="property_id" value="">
                        <div class="form-group">
                            <label>Rating</label>
                            <div class="rating-input">
                                <input type="radio" id="star5" name="rating" value="5" required>
                                <label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="comment">Review</label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="button" id="cancelReview" class="btn btn-secondary">Cancel</button>
                            <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
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
            
            // Reviews tabs functionality
            const tabs = document.querySelectorAll('.reviews-tab');
            const sections = document.querySelectorAll('.reviews-section');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs and sections
                    tabs.forEach(t => t.classList.remove('active'));
                    sections.forEach(s => s.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Show corresponding section
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId + '-section').classList.add('active');
                });
            });
            
            // Review form modal functionality
            const reviewFormModal = document.getElementById('reviewFormModal');
            const reviewForm = document.getElementById('reviewForm');
            const propertyIdInput = document.getElementById('property_id');
            const commentInput = document.getElementById('comment');
            const closeReviewModal = document.getElementById('closeReviewModal');
            const cancelReview = document.getElementById('cancelReview');
            
            // Edit review buttons
            const editButtons = document.querySelectorAll('.edit-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const reviewId = this.getAttribute('data-review-id');
                    const propertyId = this.getAttribute('data-property-id');
                    const rating = this.getAttribute('data-rating');
                    const comment = this.getAttribute('data-comment');
                    
                    // Set form values
                    propertyIdInput.value = propertyId;
                    commentInput.value = comment;
                    
                    // Set rating
                    document.querySelector(`input[name="rating"][value="${rating}"]`).checked = true;
                    
                    // Show modal
                    reviewFormModal.style.display = 'flex';
                });
            });
            
            // Close modal
            if (closeReviewModal) {
                closeReviewModal.addEventListener('click', function() {
                    reviewFormModal.style.display = 'none';
                });
            }
            
            if (cancelReview) {
                cancelReview.addEventListener('click', function() {
                    reviewFormModal.style.display = 'none';
                });
            }
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === reviewFormModal) {
                    reviewFormModal.style.display = 'none';
                }
            });
            
            // Delete review functionality
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const reviewId = this.getAttribute('data-review-id');
                    
                    if (confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
                        // Send AJAX request to delete review
                        fetch('includes/delete_review.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'review_id=' + reviewId
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Reload the page to show updated reviews
                                window.location.reload();
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                    }
                });
            });
            
            // Reply to review functionality
            const replyButtons = document.querySelectorAll('.reply-btn');
            replyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const reviewId = this.getAttribute('data-review-id');
                    alert('Reply functionality will be implemented soon!');
                });
            });
        });
    </script>
</body>
</html>
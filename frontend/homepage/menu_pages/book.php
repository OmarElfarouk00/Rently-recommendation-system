
<!-- i think not working with this  -->
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
    <title>Account Settings | RentEstate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
    <style>

.dashboard-tabs {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
}
        /* Settings Page Styles */
.settings-container {
    margin-top: 80px;
    padding: 3rem 2%;
    max-width: 1400px;
    margin-left: 250px;
    margin-right: auto;
    grid-template-columns: 300px 1fr;
    gap: 2rem;

}


.settings-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}

.settings-nav li {
    padding: 1rem 2rem;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    color: #666;
}

.settings-nav li:hover {
    background: var(--light-gray);
    color: var(--primary-color);
}

.settings-nav li.active {
    background: var(--light-gray);
    color: var(--primary-color);
    border-left: 4px solid var(--primary-color);
}

.settings-nav li i {
    width: 20px;
    text-align: center;
}

.settings-nav li a {
    color: inherit;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    width: 100%;
}

.settings-content {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    padding: 2rem;
    max-width: 100%;
}

.settings-tab {
    display: block;
}


.settings-tab h2 {
    margin-bottom: 1.5rem;
    color: var(--text-color);
    font-size: 1.5rem;
}

.settings-form {
    max-width: 600px;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-color);
    font-weight: 500;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group input[type="password"] {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: var(--primary-color);
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-group label {
    margin-bottom: 0;
    cursor: pointer;
}

.btn {
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.3s;
}

.primary-btn {
    background: var(--primary-color);
    color: white;
}

.primary-btn:hover {
    background: #d65b1e;
}

/* Alerts */
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.alert.success {
    background: #d4edda;
    color: #155724;
}

.alert.error {
    background: #f8d7da;
    color: #721c24;
}

/* Favorites Tab */
.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.favorite-card {
    background: var(--light-gray);
    border-radius: 10px;
    overflow: hidden;
    transition: transform 0.3s, opacity 0.3s;
}

.favorite-image {
    position: relative;
    height: 150px;
}

.favorite-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.remove-favorite {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.8);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.3s;
}

.remove-favorite:hover {
    background: white;
}

.favorite-info {
    padding: 1rem;
}

.favorite-info h3 {
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
    color: var(--text-color);
}

.favorite-info p {
    color: #666;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.favorite-price {
    font-weight: bold;
    color: var(--primary-color) !important;
    margin-bottom: 1rem !important;
}

.view-property-btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: var(--primary-color);
    color: white;
    border-radius: 5px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: background-color 0.3s;
}

.view-property-btn:hover {
    background: #d65b1e;
}

.no-favorites {
    text-align: center;
    padding: 3rem 1rem;
}

.no-favorites i {
    font-size: 3rem;
    color: #ccc;
    margin-bottom: 1rem;
}

.no-favorites p {
    color: #666;
    margin-bottom: 1.5rem;
}

/* Responsive */
@media (max-width: 992px) {
    .settings-container {
        grid-template-columns: 1fr;
    }
    

    .user-profile {
        padding: 1.5rem;
    }
    
    .settings-nav {
        display: flex;
        flex-wrap: wrap;
    }
    
    .settings-nav li {
        flex: 1;
        min-width: 150px;
        text-align: center;
        padding: 1rem;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .settings-nav li {
        min-width: 100%;
    }
    
    .favorites-grid {
        grid-template-columns: 1fr;
    }
}

                /* Left Sidebar Menu */
                .sidebar {
            position: fixed;
            left: 0;
            height: 100%;
            width: 250px;
            background-color: #fff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: transform 0.3s ease;
            padding-top: 20px; /* Space for header */
            overflow-y: auto;
        }

        .sidebar.collapsed {
            transform: translateX(-250px);
        }

        .sidebar-toggle {
            position: fixed;
            left: 10px;
            top: 15px;
            z-index: 1001;
            background: transparent;
            border: none;
            color: #333;
            font-size: 1.2rem;
            cursor: pointer;
            display: none;
        }

        .sidebar-header {
            border-bottom: 1px solid #eee;
            text-align: center;
            margin-top: 0px;
        }

        .sidebar-header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }


        .user-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .user-email {
            font-size: 0.8rem;
            color: #777;
        }

        .sidebar-menu {
            padding: 15px 0;
        }

        .menu-section {
            margin-bottom: 20px;
        }

        .menu-section-title {
            padding: 10px 15px;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #777;
            font-weight: bold;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .menu-item:hover {
            background-color: #f5f5f5;
        }

        .menu-item.active {
            background-color: #f0f0f0;
            border-left: 3px solid #ff385c;
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .menu-badge {
            margin-left: auto;
            background-color: #ff385c;
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 0.7rem;
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

                <!-- <a href="map-view.php" class="menu-item">
                    <i class="fas fa-map-marked-alt"></i>
                    Map View
                </a> -->
            </div>

            <?php if ($isLoggedIn): ?>
            <div class="menu-section">
                <div class="menu-section-title">Personal</div>
                <a href="." class="menu-item active">
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
                <a href="bookings.php" class="menu-item">
                    <i class="fas fa-calendar-check"></i>
                    My Bookings
                </a>

            </div>
            <?php endif; ?>

            <div class="menu-section">
                <div class="menu-section-title">Settings</div>
                <a href="../settings.php" class="menu-item">
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

    <!-- Header (Same as index.php) -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                    <a href="../index.php" class="logo">
                        <img src="../../rently2.png" alt="" style="height: 38px; width: 130px;">
                    </a>
            </div>
        </div>
    </header>


        
    <div class="my-properties-container" id="menuContent">

        
        <?php if (!empty($successMessage)): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-tabs">
            <div class="tab-header">
                <button class="tab-btn active" data-tab="properties" id="menuToggle">
                    <i class="fas fa-building"></i> My bookings
                </button>
            </div>
            
            <div class="tab-content active" id="properties-tab">
                <?php if (count($properties) > 0): ?>
                    <div class="properties-grid">
                    <?php foreach ($properties as $property): ?>
    <div class="property-card">
        <div class="property-image">
            <?php
            try {
                $stmt = $pdo->prepare("
                    SELECT image_path 
                    FROM property_images 
                    WHERE property_id = ? 
                    ORDER BY image_order ASC 
                    LIMIT 1
                ");
                $stmt->execute([$property['id_property']]);
                $image = $stmt->fetch(PDO::FETCH_ASSOC);

                $imgPath = !empty($image['image_path']) 
                    ? 'php files/' . htmlspecialchars($image['image_path']) 
                    : '/placeholder.svg?height=500&width=800';
            } catch (PDOException $e) {
                $imgPath = '/placeholder.svg?height=500&width=800';
            }
            ?>
            <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">

            <div class="property-status <?php echo strtolower($property['status']); ?>">
                <?php echo htmlspecialchars($property['status']); ?>
            </div>
        </div>                              
        <div class="property-info">
            <h3><?php echo htmlspecialchars($property['title']); ?></h3>
            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['address'] . ', ' . $property['city']); ?></p>
            <div class="property-meta">
                <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Beds</span>
                <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Baths</span>
                <span><i class="fas fa-ruler-combined"></i> <?php echo $property['size']; ?> m²</span>
            </div>
            <div class="property-price">
                <?php echo number_format($property['estimatePrice'], 0, '.', ','); ?> DZD
                <?php if ($property['ownerNeeds'] == 'renting'): ?>
                    <span>/month</span>
                <?php endif; ?>
            </div>
            <div class="property-actions">
                <a href="property.php?id=<?php echo $property['id_property']; ?>" class="btn view-btn">
                    <i class="fas fa-eye"></i> View
                </a>
                <a href="edit-property.php?id=<?php echo $property['id_property']; ?>" class="btn edit-btn">
                    <i class="fas fa-edit"></i> Edit
                </a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-building"></i>
                        <p>You haven't added any properties yet.</p>
                        <a href="add-property.php" class="btn primary-btn">Add Your First Property</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="bookings-tab">
                <?php if (count($bookings) > 0): ?>
                    <div class="bookings-list">
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-header">
                                    <h3><?php echo htmlspecialchars($booking['property_title']); ?></h3>
                                    <div class="booking-status <?php echo $booking['status'] ? 'confirmed' : 'pending'; ?>">
                                        <?php echo $booking['status'] ? 'Confirmed' : 'Pending'; ?>
                                    </div>
                                </div>
                                <div class="booking-details">
                                    <div class="booking-info">
                                        <p><i class="fas fa-user"></i> <strong>Client:</strong> <?php echo htmlspecialchars($booking['client_name']); ?></p>
                                        <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($booking['client_email']); ?></p>
                                        <p><i class="fas fa-calendar"></i> <strong>Start Date:</strong> <?php echo date('F j, Y', strtotime($booking['startDate'])); ?></p>
                                        <p><i class="fas fa-clock"></i> <strong>Duration:</strong> <?php echo $booking['duration']; ?> months</p>
                                        <p><i class="fas fa-dollar-sign"></i> <strong>Price:</strong> <?php echo number_format($booking['price'], 0, '.', ','); ?> DZD</i></p>
                                        <p><i class="fas fa-phone"></i> <strong>Phone Number:</strong ></i> <?php echo htmlspecialchars($booking['client_phone_number']); ?></p>
                                    </div>
                                    
                                    <?php if (!$booking['status']): ?>
                                        <div class="booking-actions">
                                            <form action="my-properties.php" method="POST">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <input type="hidden" name="status" value="1">
                                                <button type="submit" name="update_booking" class="btn accept-btn">
                                                    <i class="fas fa-check"></i> Accept
                                                </button>
                                            </form>
                                            <form action="my-properties.php" method="POST">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <input type="hidden" name="status" value="0">
                                                <button type="submit" name="update_booking" class="btn reject-btn">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-check"></i>
                        <p>You don't have any booking requests yet.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="negotiations-tab">
                <?php if (count($negotiations) > 0): ?>
                    <div class="negotiations-list">
                        <?php foreach ($negotiations as $negotiation): ?>
                            <div class="negotiation-card">
                                <div class="negotiation-header">
                                    <h3><?php echo htmlspecialchars($negotiation['property_title']); ?></h3>
                                    <div class="negotiation-date">
                                        <?php echo date('F j, Y', strtotime($negotiation['proposedDate'])); ?>
                                    </div>
                                </div>
                                <div class="negotiation-details">
                                    <div class="negotiation-info">
                                        <p><i class="fas fa-user"></i> <strong>Client:</strong> <?php echo htmlspecialchars($negotiation['client_name']); ?></p>
                                        <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($negotiation['client_email']); ?></p>
                                        <p><i class="fas fa-phone"></i> <strong>Phone Number:</strong> <?php echo htmlspecialchars($booking['client_phone_number'])?>
                                        <div class="price-comparison">
                                            <div class="original-price">
                                                <span>Original Price</span>
                                                <strong><?php echo number_format($negotiation['original_price'], 0, '.', ','); ?> DZD</strong>
                                            </div>
                                            <i class="fas fa-arrow-right"></i>
                                            <div class="offered-price">
                                                <span>Offered Price</span>
                                                <strong><?php echo number_format($negotiation['proposedPrice'], 0, '.', ','); ?> DZD</strong>
                                                <?php 
                                                    $percentDiff = round(($negotiation['proposedPrice'] - $negotiation['original_price']) / $negotiation['original_price'] * 100);
                                                    $percentClass = $percentDiff < 0 ? 'discount' : 'increase';
                                                ?>
                                                <span class="percent-diff <?php echo $percentClass; ?>">
                                                    <?php echo $percentDiff; ?>%
                                                </span>
                                            </div>
                                        </div>
                                        <?php if (!empty($negotiation['comments'])): ?>
                                            <div class="client-message">
                                                <strong>Message:</strong>
                                                <p><?php echo nl2br(htmlspecialchars($negotiation['comments'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="negotiation-actions">
                                        <form action="my-properties.php" method="POST">
                                            <input type="hidden" name="negotiation_id" value="<?php echo $negotiation['id']; ?>">
                                            <input type="hidden" name="response" value="accept">
                                            <button type="submit" name="respond_negotiation" class="btn accept-btn">
                                                <i class="fas fa-check"></i> Accept Offer
                                            </button>
                                        </form>
                                        <form action="my-properties.php" method="POST">
                                            <input type="hidden" name="negotiation_id" value="<?php echo $negotiation['id']; ?>">
                                            <input type="hidden" name="response" value="reject">
                                            <button type="submit" name="respond_negotiation" class="btn reject-btn">
                                                <i class="fas fa-times"></i> Reject Offer
                                            </button>
                                        </form>
                                        <!-- <button class="btn counter-btn" data-negotiation-id="<?php echo $negotiation['id']; ?>">
                                            <i class="fas fa-exchange-alt"></i> Counter Offer
                                        </button> -->
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-comments-dollar"></i>
                        <p>You don't have any negotiation requests yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menu toggle
            const menuToggle = document.getElementById('menuToggle');
            const menuContent = document.getElementById('menuContent');
            
            menuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                menuContent.classList.toggle('active');
            });
            
            document.addEventListener('click', function(e) {
                if (!menuContent.contains(e.target) && !menuToggle.contains(e.target)) {
                    menuContent.classList.remove('active');
                }
            });

            
            // Settings tabs
            const tabLinks = document.querySelectorAll('.settings-nav li');
            const tabContents = document.querySelectorAll('.settings-tab');
            
            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs
                    
                    // Add active class to current tab
                    this.classList.add('active');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });
            
            // Remove favorite
            const removeFavoriteButtons = document.querySelectorAll('.remove-favorite');
            
            removeFavoriteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const propertyId = this.getAttribute('data-property-id');
                    const card = this.closest('.favorite-card');
                    
                    fetch('../php files/remove_favorite.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            property_id: propertyId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the card with animation
                            card.style.opacity = '0';
                            setTimeout(() => {
                                card.remove();
                                
                                // Check if there are no more favorites
                                const remainingFavorites = document.querySelectorAll('.favorite-card');
                                if (remainingFavorites.length === 0) {
                                    const favoritesGrid = document.querySelector('.favorites-grid');
                                    favoritesGrid.innerHTML = `
                                        <div class="no-favorites">
                                            <i class="far fa-heart"></i>
                                            <p>You haven't added any properties to your favorites yet.</p>
                                            <a href="index.php" class="btn primary-btn">Browse Properties</a>
                                        </div>
                                    `;
                                }
                            }, 300);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                });
            });
        });
    </script>
</body>
</html>
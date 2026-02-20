<?php
session_start();
require_once '../php files/config.php';
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Get filter parameters
$propertyType = isset($_GET['property_type']) ? $_GET['property_type'] : '';
$city = isset($_GET['city']) ? $_GET['city'] : '';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000000;
$bedrooms = isset($_GET['bedrooms']) ? (int)$_GET['bedrooms'] : 0;
$bathrooms = isset($_GET['bathrooms']) ? (int)$_GET['bathrooms'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build the query
$query = "SELECT p.*, pi.image_path 
          FROM Property p 
          LEFT JOIN (
              SELECT property_id, MIN(image_path) as image_path 
              FROM Property_Images 
              GROUP BY property_id
          ) pi ON p.id_property = pi.property_id 
          WHERE 1=1";

if (!empty($propertyType)) {
    $query .= " AND p.propertyType = '$propertyType'";
}
if (!empty($city)) {
    $query .= " AND p.city = '$city'";
}
if ($minPrice > 0) {
    $query .= " AND p.estimatePrice >= $minPrice";
}
if ($maxPrice > 0) {
    $query .= " AND p.estimatePrice <= $maxPrice";
}
if ($bedrooms > 0) {
    $query .= " AND p.bedrooms >= $bedrooms";
}
if ($bathrooms > 0) {
    $query .= " AND p.bathrooms >= $bathrooms";
}
if (!empty($status)) {
    $query .= " AND p.status = '$status'";
}

// Execute query
$result = $conn->query($query);
$properties = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
}

// Get cities for filter
$cityQuery = "SELECT DISTINCT city FROM Property ORDER BY city";
$cityResult = $conn->query($cityQuery);
$cities = [];
if ($cityResult && $cityResult->num_rows > 0) {
    while ($row = $cityResult->fetch_assoc()) {
        $cities[] = $row['city'];
    }
}

// Track browsing history if user is logged in
if ($isLoggedIn) {
    $currentPage = "explore.php";
    $currentTime = date('Y-m-d H:i:s');
    $historyQuery = "INSERT INTO BrowsingHistory (id_client, page_url, timestamp) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE timestamp = ?";
    $stmt = $conn->prepare($historyQuery);
    $stmt->bind_param("isss", $userId, $currentPage, $currentTime, $currentTime);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Properties - RentEstate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Explore Page Specific Styles */
        .explore-container {
            display: flex;
            flex-direction: column;
            padding: 20px;
        }
        
        .filter-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filter-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }
        
        .filter-button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .apply-filter {
            background-color: #ff385c;
            color: white;
        }
        
        .apply-filter:hover {
            background-color: #e61e4d;
        }
        
        .reset-filter {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .reset-filter:hover {
            background-color: #e0e0e0;
        }
        
        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .property-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .property-image {
            height: 200px;
            width: 100%;
            object-fit: cover;
        }
        
        .property-info {
            padding: 15px;
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
        
        .property-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .property-detail {
            display: flex;
            align-items: center;
            color: #555;
            font-size: 14px;
        }
        
        .property-detail i {
            margin-right: 5px;
            font-size: 16px;
        }
        
        .property-price {
            font-size: 18px;
            font-weight: 600;
            color: #ff385c;
            margin-bottom: 10px;
        }
        
        .property-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-for-rent {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .status-for-sale {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .property-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .property-action {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .property-action i {
            margin-right: 5px;
        }
        
        .property-action:hover {
            color: #ff385c;
        }
        
        .favorite-btn.active {
            color: #ff385c;
        }
        
        .no-properties {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .properties-grid {
                grid-template-columns: 1fr;
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
                <a href="explore.php" class="menu-item active">
                    <i class="fas fa-compass"></i>
                    Explore
                </a>
                <a href="map-view.php" class="menu-item">
                    <i class="fas fa-map-marked-alt"></i>
                    Map View
                </a>
            </div>

            <?php if ($isLoggedIn): ?>
            <div class="menu-section">
                <div class="menu-section-title">Personal</div>
                <a href="favorites.php" class="menu-item">
                    <i class="fas fa-heart"></i>
                    Favorites
                    <?php if (isset($favoriteCount) && $favoriteCount > 0): ?>
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
                    <?php if (isset($unreadMessages) && $unreadMessages > 0): ?>
                    <span class="menu-badge"><?php echo $unreadMessages; ?></span>
                    <?php endif; ?>
                </a>
                <a href="notifications.php" class="menu-item">
                    <i class="fas fa-bell"></i>
                    Notifications
                    <?php if (isset($unreadNotifications) && $unreadNotifications > 0): ?>
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
                <a href="reviews.php" class="menu-item">
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
                    <i class="fas fa-home"></i>
                    <a href="index.php" class="logo">RentEstate</a>
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
            <div class="explore-container">
                <div class="filter-section">
                    <h2>Find Your Perfect Property</h2>
                    <form class="filter-form" action="explore.php" method="GET">
                        <div class="filter-group">
                            <label for="property_type">Property Type</label>
                            <select name="property_type" id="property_type">
                                <option value="">All Types</option>
                                <option value="Apartment" <?php echo $propertyType == 'Apartment' ? 'selected' : ''; ?>>Apartment</option>
                                <option value="Villa" <?php echo $propertyType == 'Villa' ? 'selected' : ''; ?>>Villa</option>
                                <option value="House" <?php echo $propertyType == 'House' ? 'selected' : ''; ?>>House</option>
                                <option value="Room" <?php echo $propertyType == 'Room' ? 'selected' : ''; ?>>Room</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="city">City</label>
                            <select name="city" id="city">
                                <option value="">All Cities</option>
                                <?php foreach ($cities as $cityOption): ?>
                                <option value="<?php echo $cityOption; ?>" <?php echo $city == $cityOption ? 'selected' : ''; ?>><?php echo $cityOption; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="min_price">Min Price ($)</label>
                            <input type="number" name="min_price" id="min_price" min="0" value="<?php echo $minPrice; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="max_price">Max Price ($)</label>
                            <input type="number" name="max_price" id="max_price" min="0" value="<?php echo $maxPrice; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="bedrooms">Bedrooms</label>
                            <input type="number" name="bedrooms" id="bedrooms" min="0" value="<?php echo $bedrooms; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="bathrooms">Bathrooms</label>
                            <input type="number" name="bathrooms" id="bathrooms" min="0" value="<?php echo $bathrooms; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select name="status" id="status">
                                <option value="">All</option>
                                <option value="For Rent" <?php echo $status == 'For Rent' ? 'selected' : ''; ?>>For Rent</option>
                                <option value="For Sale" <?php echo $status == 'For Sale' ? 'selected' : ''; ?>>For Sale</option>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="reset" class="filter-button reset-filter">Reset</button>
                            <button type="submit" class="filter-button apply-filter">Apply Filters</button>
                        </div>
                    </form>
                </div>
                
                <div class="properties-grid">
                    <?php if (count($properties) > 0): ?>
                        <?php foreach ($properties as $property): ?>
                            <div class="property-card">
                                <img src="<?php echo !empty($property['image_path']) ? $property['image_path'] : 'assets/images/property-placeholder.jpg'; ?>" alt="<?php echo $property['title']; ?>" class="property-image">
                                <div class="property-info">
                                    <h3 class="property-title"><?php echo $property['title']; ?></h3>
                                    <div class="property-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo $property['address'] . ', ' . $property['city']; ?>
                                    </div>
                                    <div class="property-details">
                                        <div class="property-detail">
                                            <i class="fas fa-bed"></i>
                                            <?php echo $property['bedrooms']; ?> Beds
                                        </div>
                                        <div class="property-detail">
                                            <i class="fas fa-bath"></i>
                                            <?php echo $property['bathrooms']; ?> Baths
                                        </div>
                                        <div class="property-detail">
                                            <i class="fas fa-ruler-combined"></i>
                                            <?php echo $property['size']; ?> m²
                                        </div>
                                    </div>
                                    <div class="property-price">
                                        $<?php echo number_format($property['estimatePrice']); ?>
                                    </div>
                                    <span class="property-status <?php echo $property['status'] == 'For Rent' ? 'status-for-rent' : 'status-for-sale'; ?>">
                                        <?php echo $property['status']; ?>
                                    </span>
                                    <div class="property-actions">
                                        <a href="property-details.php?id=<?php echo $property['id_property']; ?>" class="property-action">
                                            <i class="fas fa-info-circle"></i> Details
                                        </a>
                                        <?php if ($isLoggedIn): ?>
                                        <button class="property-action favorite-btn" data-id="<?php echo $property['id_property']; ?>">
                                            <i class="far fa-heart"></i> Favorite
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-properties">
                            <i class="fas fa-search" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                            <h3>No properties found</h3>
                            <p>Try adjusting your filters to find more properties.</p>
                        </div>
                    <?php endif; ?>
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
            
            // Favorite button functionality
            const favoriteButtons = document.querySelectorAll('.favorite-btn');
            favoriteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const propertyId = this.getAttribute('data-id');
                    
                    // Toggle heart icon
                    const heartIcon = this.querySelector('i');
                    if (heartIcon.classList.contains('far')) {
                        heartIcon.classList.remove('far');
                        heartIcon.classList.add('fas');
                        this.classList.add('active');
                    } else {
                        heartIcon.classList.remove('fas');
                        heartIcon.classList.add('far');
                        this.classList.remove('active');
                    }
                    
                    // Send AJAX request to add/remove favorite
                    fetch('includes/toggle_favorite.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'property_id=' + propertyId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log(data.message);
                        } else {
                            console.error(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            });
            
            // Reset form button
            const resetButton = document.querySelector('.reset-filter');
            resetButton.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.filter-form select, .filter-form input').forEach(input => {
                    if (input.type === 'number') {
                        input.value = input.name === 'min_price' ? '0' : 
                                     input.name === 'max_price' ? '1000000' : '0';
                    } else {
                        input.value = '';
                    }
                });
            });
        });
    </script>
</body>
</html>
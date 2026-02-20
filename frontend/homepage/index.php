<!-- the second version -->

<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Real Estate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link rel="stylesheet" href="styles.css">
    <!-- <script src="script.js" defer></script> -->
    <style>
        /* Left Sidebar Menu */
        .sidebar {
            position: fixed;
            left: 0;
            height: 90%;
            width: 250px;
            background-color: #fff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: transform 0.3s ease;
            padding-top: 20px;
            /* Space for header */
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



        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 2rem 3%;
            }

            .sidebar {
                transform: translateX(-250px);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-toggle {
                display: block;
                right: 0%;
                left: 90%;
                top: 33px;
            }
        }

        .suggestions-box {
            position: absolute;
            top: calc(100% + 8px);
            /* Add some space between input and dropdown */
            left: 0;
            right: 0;
            max-height: 250px;
            overflow-y: auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            border: none;
            padding: 0px 0;
            transform-origin: top center;
            animation: dropdownFadeIn 0.2s ease-out;

            /* Improved scrollbar styling */
            scrollbar-width: thin;
            scrollbar-color: #e0e0e0 transparent;
        }

        /* Animation for dropdown appearance */
        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Scrollbar styling for webkit browsers */
        .suggestions-box::-webkit-scrollbar {
            width: 6px;
        }

        .suggestions-box::-webkit-scrollbar-track {
            background: transparent;
        }

        .suggestions-box::-webkit-scrollbar-thumb {
            background-color: #e0e0e0;
            border-radius: 6px;
        }

        .suggestions-box div {
            padding: 10px 16px;
            cursor: pointer;
            font-size: 14px;
            color: #333;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
        }

        .suggestions-box div:first-child {
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .suggestions-box div:last-child {
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .suggestions-box div:hover {
            background-color: #f8f8f8;
            color: #ff385c;
        }

        .suggestions-box div:active {
            background-color: #f0f0f0;
        }

        /* Optional: Add an icon to each suggestion */
        .suggestions-box div::before {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-right: 10px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.5;
        }

        /* Optional: Add a subtle divider between items */
        .suggestions-box div:not(:last-child) {
            border-bottom: 1px solid #f5f5f5;
        }

        /* Notification Bell Icon */
        /* Notification Icon - A sleek, interactive bell icon */
        .notification-icon {
            font-size: 20px;
            color: #4a4a4a;
            cursor: pointer;
            position: relative;
            transition: all 0.25s ease-in-out;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            border-radius: 50%;
        }

        .notification-icon .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            min-width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.4);
            padding: 0 4px;
            animation: bounceIn 0.5s forwards;
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            60% {
                transform: scale(1.1);
                opacity: 1;
            }

            100% {
                transform: scale(1);
            }
        }

        .notification-icon:hover {
            color: rgb(241, 147, 24);
            transform: scale(1.1);
        }

        .notification-dropdown {
            display: none;
            position: absolute;
            top: 55px;
            right: 0;
            width: 360px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15), 0 4px 10px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            border: 1px solid rgba(0, 0, 0, 0.08);
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            animation: fadeInScale 0.3s ease-out forwards;
            transform-origin: top right;

        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: translateY(-15px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .notification-header {
            padding: 10px 18px;
            background: linear-gradient(135deg, #f0f2f5, #e0e4e8);
            border-bottom: 1px solid #dcdfe3;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h4 {
            margin: 0;
            font-size: 17px;
            font-weight: 600;
            color: #2c3e50;
        }

        .clear-notifications {
            background: none;
            border: none;
            color: #e74c3c;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 8px;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .clear-notifications:hover {
            background-color: rgba(231, 76, 60, 0.1);
            color: #c0392b;
        }

        .notification-list {
            max-height: 320px;
            overflow-y: auto;
            padding: 8px 0;
        }

        .notification-list::-webkit-scrollbar {
            width: 5px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: #f8f8f8;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background: #cdd2d7;
            border-radius: 3px;
        }

        .notification-list::-webkit-scrollbar-thumb:hover {
            background: #aab0b6;
        }

        .notification-item {
            padding: 12px 22px;
            border-bottom: 1px solid #f5f7f9;
            font-size: 14px;
            color: #34495e;
            transition: background-color 0.2s ease;
            cursor: pointer;
            line-height: 1.4;
        }

        .notification-item:hover {
            background-color: #f8fafd;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item strong {
            font-weight: 600;
            color: #1a1a1a;
        }

        .notification-item span {
            display: block;
            font-size: 11px;
            color: #888;
            margin-top: 4px;
            font-weight: 400;
        }

        .notification-dropdown.show {
            display: block;
        }

        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            min-width: 300px;
            max-width: 400px;
            transform: translateX(100%);
            transition: transform 0.3s ease, opacity 0.3s ease;
            opacity: 0;
            border-left: 4px solid #007bff;
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast-error {
            border-left-color: #dc3545;
        }

        .toast-success {
            border-left-color: #28a745;
        }

        .toast-content {
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toast-icon {
            font-size: 18px;
            flex-shrink: 0;
        }

        .toast-message {
            flex: 1;
            color: #333;
            font-size: 14px;
            line-height: 1.4;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #666;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s ease;
        }

        .toast-close:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>

    <?php


    // Check if the user is logged in
    $isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in
    $userName = $isLoggedIn ? $_SESSION['user_name'] : null; // Get the user's name if logged in
    
    // Check if the user has properties
    $hasProperties = $isLoggedIn ? getProperties($_SESSION['user_id']) : false;

    // Define a function to check if the user has properties
    function getProperties($userId)
    {
        require_once 'php files/config.php';

        // Query to fetch properties owned by the user
        $stmt = $pdo->prepare("
        SELECT p.* 
        FROM Property p
        JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
        JOIN Client c ON po.id_propertyOwner = c.id_client
        WHERE c.id_client = ?
    ");
        // Return true if the user has at least one property
        return $stmt->rowCount() > 0;

    }




    ?>
    <div id="menuToggle">
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
                    <div class="user-name">
                        <?php echo '<h2 style="text-color:rgb(121, 198, 233)">WELCOME</h2> ', $_SESSION['user_name']; ?>
                    </div>
                <?php else: ?>
                    <div class="user-name">
                        <h2>Welcome </h2> Guest
                    </div>
                    <a href="../login-signup/index.php" class="menu-item">
                        <i class="fas fa-sign-in-alt"></i>
                        Login / Sign Up
                    </a>
                <?php endif; ?>
            </div>

            <nav class="sidebar-menu">
                <div class="menu-section">
                    <div class="menu-section-title">Main</div>
                    <a href="index.php" class="menu-item active">
                        <i class="fas fa-home"></i>
                        Home
                    </a>

                    <!-- <a href="menu pages/map-view.php" class="menu-item">
                        <i class="fas fa-map-marked-alt"></i>
                        Map View
                    </a> -->
                </div>

                <?php if ($isLoggedIn): ?>
                    <div class="menu-section">
                        <div class="menu-section-title">Personal</div>
                        <a href="menu pages/favorites.php" class="menu-item">
                            <i class="fas fa-heart"></i>
                            Favorites
                            <?php if (isset($favoriteCount) && $favoriteCount > 0): ?>
                                <span class="menu-badge"><?php echo $favoriteCount; ?></span>
                            <?php endif; ?>
                        </a>

                        <a href="menu pages/messages.php" class="menu-item">
                            <i class="fas fa-envelope"></i>
                            Messages
                            <?php if (isset($unreadMessages) && $unreadMessages > 0): ?>
                                <span class="menu-badge"><?php echo $unreadMessages; ?></span>
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
                        <a href="menu pages/bookings.php" class="menu-item">
                            <i class="fas fa-calendar-check"></i>
                            My Bookings
                        </a>
                    </div>
                    <div class="menu-section">
                        <div class="menu-section-title">Settings</div>
                        <a href="menu pages/settings.php" class="menu-item">
                            <i class="fas fa-cog"></i>
                            Account Settings
                        </a>
                        <a href="menu pages/privacy.php" class="menu-item">
                            <i class="fas fa-shield-alt"></i>
                            Privacy & Security
                        </a>
                    </div>

                <?php endif; ?>

<!-- 
                <div class="menu-section">
                    <div class="menu-section-title">Support</div>
                    <a href="help-center.php" class="menu-item">
                        <i class="fas fa-question-circle"></i>
                        Help Center
                    </a>

                    <a href="about-us.php" class="menu-item">
                        <i class="fas fa-info-circle"></i>
                        About Us
                    </a>
                </div> -->

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

        <!-- Header -->
        <header class="header">



            <div class="header-content">
                <div class="logo">
                    <!-- <i class="fas fa-home"></i> -->
                    <!-- <a href="." class="logo"><i class="fas fa-home"></i> -->
                    <!-- <span class="logo">RENTLY</span> -->
                    <a href="index.php" class="logo">
                        <img src="../rently2.png" alt="" style="height: 38px; width: 130px;">
                    </a>
                </div>

                <div class="search-bar">
                    <input type="text" class="search-input" id="search-input"
                        placeholder="Search destinations, properties..." autocomplete="off">
                    <div id="suggestions" class="suggestions-box"></div>
                </div>

                <!-- Filter Button -->
                <!-- <button id="filterBtn" class="filter-button">Filter Properties</button> -->



                <div class="nav-item" id="filterBtn">
                    <i class="fas fa-sliders-h"></i>
                    Filter
                </div>

                <!-- Filter Modal -->
                <div id="filterModal" class="filter-modal">
                    <div class="filter-content">
                        <span class="close">&times;</span>
                        <h2>Filter Properties</h2>
                        <form id="filterForm">
                            <label for="bedrooms">Bedrooms:</label>
                            <input type="number" name="bedrooms" id="bedrooms" min="0">

                            <label for="bathrooms">Bathrooms:</label>
                            <input type="number" name="bathrooms" id="bathrooms" min="0">

                            <label for="min_price">Min Price ($):</label>
                            <input type="number" name="min_price" id="min_price" min="0">

                            <label for="max_price">Max Price ($):</label>
                            <input type="number" name="max_price" id="max_price" min="0">

                            <label for="type">Type:</label>
                            <select name="type" id="type">
                                <option value="renting">Renting</option>
                                <option value="selling">Selling</option>
                            </select>

                            <label for="propertyType">Property Type:</label>
                            <select name="propertyType" id="propertyType">
                                <option value="">--Select Type--</option>
                                <option value="apartment">Apartment</option>
                                <option value="house">House</option>
                                <option value="villa">Villa</option>
                                <option value="room">Single Room</option>
                            </select>

                            <label for="size">size:</label>
                            <input type="number" name="size" id="size">



                            <label for="city">City:</label>
                            <select name="city" id="city">
                                <option value="">--Select City--</option>
                                <option value="Adrar">Adrar</option>
                                <option value="Chlef">Chlef</option>
                                <option value="Laghouat">Laghouat</option>
                                <option value="Oum El Bouaghi">Oum El Bouaghi</option>
                                <option value="Batna">Batna</option>
                                <option value="Béjaïa">Béjaïa</option>
                                <option value="Biskra">Biskra</option>
                                <option value="Béchar">Béchar</option>
                                <option value="Blida">Blida</option>
                                <option value="Bouira">Bouira</option>
                                <option value="Tamanrasset">Tamanrasset</option>
                                <option value="Tébessa">Tébessa</option>
                                <option value="Tlemcen">Tlemcen</option>
                                <option value="Tiaret">Tiaret</option>
                                <option value="Tizi Ouzou">Tizi Ouzou</option>
                                <option value="Algiers">Algeria</option>
                                <option value="Djelfa">Djelfa</option>
                                <option value="Jijel">Jijel</option>
                                <option value="Sétif">Sétif</option>
                                <option value="Saïda">Saïda</option>
                                <option value="Skikda">Skikda</option>
                                <option value="Sidi Bel Abbès">Sidi Bel Abbès</option>
                                <option value="Annaba">Annaba</option>
                                <option value="Guelma">Guelma</option>
                                <option value="Constantine">Constantine</option>
                                <option value="Médéa">Médéa</option>
                                <option value="Mostaganem">Mostaganem</option>
                                <option value="MSila">M'Sila</option>
                                <option value="Mascara">Mascara</option>
                                <option value="Ouargla">Ouargla</option>
                                <option value="Oran">Oran</option>
                                <option value="El Bayadh">El Bayadh</option>
                                <option value="Illizi">Illizi</option>
                                <option value="Bordj Bou Arréridj">Bordj Bou Arréridj</option>
                                <option value="Boumerdès">Boumerdès</option>
                                <option value="El Tarf">El Tarf</option>
                                <option value="Tindouf">Tindouf</option>
                                <option value="Tissemsilt">Tissemsilt</option>
                                <option value="El Oued">El Oued</option>
                                <option value="Khenchela">Khenchela</option>
                                <option value="Souk Ahras">Souk Ahras</option>
                                <option value="Tipaza">Tipaza</option>
                                <option value="Mila">Mila</option>
                                <option value="Aïn Defla">Aïn Defla</option>
                                <option value="Naâma">Naâma</option>
                                <option value="Aïn Témouchent">Aïn Témouchent</option>
                                <option value="Ghardaïa">Ghardaïa</option>
                                <option value="Relizane">Relizane</option>
                            </select>

                            <button type="submit" class="submit-filter">Apply Filters</button>
                            <button type="reset" class="submit-filter"> reset filter</button>
                        </form>
                    </div>
                </div>


                <div class="nav-item" id="rent-nav">
                    <i class="fas fa-handshake"></i>
                    Rent
                </div>


                <div class="nav-item" id="sell-nav">
                    <i class="fas fa-tag"></i>
                    Buy
                </div>

                <?php include 'includes/notifications.php'; ?>

                <?php if ($isLoggedIn): ?>
                    <!-- User Profile with Active Status -->
                    <div class="user-profile">
                        <div class="user-status">
                            <span class="status-indicator"></span>
                            <span class="username"><?php echo $_SESSION['user_name']; ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </header>
        <!-- Category Navigation -->
        <nav class="category-nav">
            <div class="category-list">
                <div class="category-item active" id="villa">
                    <i class="fas fa-calendar-alt category-icon"></i>
                    <span name="villa">Event</span>
                </div>
                <div class="category-item" id="house">
                    <i class="fas fa-home category-icon"></i>
                    <span name="house">House</span>
                </div>
                <div class="category-item" id="apartment">
                    <i class="fas fa-building category-icon"></i>
                    <span name="apartment">Apartment</span>
                </div>
                <div class="category-item" id="room">
                    <i class="fas fa-bed category-icon"></i>
                    <span name="room">room</span>
                </div>

            </div>
            <!-- <div class="filter-container">
                <label for="price-filter">Sort by Price:</label>
                <select id="price-filter" name="price-filter">
                    <option value="default">Select</option>
                    <option value="low-to-high">Price: Low to High</option>
                    <option value="high-to-low">Price: High to Low</option>
                </select>
            </div> -->

        </nav>
    </div>


    <!-- Main Content -->
    <main class="main-content">
        <div class="listings-grid" id="propertiesContainer">
            <div class="loading">
                <div class="loading-spinner"></div>
                <p>Loading properties...</p>
            </div>
        </div>
    </main>

    <script>
        // Function to format price
        function formatPrice(price) {
            return new Intl.NumberFormat('fr-DZ', {
                style: 'currency',
                currency: 'DZD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(price);
        }



        // Add event listener to the "Sell" navigation item

        // Function to fetch properties for rent
        function fetchPropertiesForRent() {
            fetch('php files/fetch-properties.php?type')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear the current property grid
                        const propertyGrid = document.querySelector('.property-grid');
                        propertyGrid.innerHTML = '';

                        // Display only properties for rent
                        data.properties.forEach(property => {
                            const propertyCard = createPropertyCard(property);
                            propertyGrid.appendChild(propertyCard);
                        });
                    } else {
                        alert('Failed to fetch properties for rent.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }






        //  Function to create property card
        function createPropertyCard(property) {
            const imagePath = property.main_image
                ? `php files/${property.main_image}`
                : '/placeholder.svg?height=200&width=300';
            const isFav = property.is_favorite;
            return `
    <a href="property.php?id=${property.id_property}" class="listing-card-link">
        <div class="listing-card ${property.is_vip ? 'vip-property' : ''}">
            <div class="listing-image">
                <img src="${imagePath}" alt="${property.title}">
                ${property.is_vip ? '<span class="vip-badge" style=" font-weight: bold;">VIP Property</span>' : ''}
               <button class="favorite-btn" data-property-id="${property.id_property}">
                    <i class="${isFav ? 'fas' : 'far'} fa-heart" style="color: ${isFav ? 'red' : 'gray'};"></i>
                </button>
            </div>

            <div class="listing-info">
                <h3 class="listing-title">${property.title}</h3>
                <div class="listing-location">
                    <i class="fas fa-map-marker-alt"></i>
                    ${property.city},${property.address}
                </div>
                <div class="listing-details">
                    <span><i class="fas fa-bed"></i> ${property.bedrooms} beds</span>
                    <span><i class="fas fa-bath"></i> ${property.bathrooms} baths</span>
                    <span><i class="fas fa-ruler-combined"></i> ${property.size} m²</span>
                </div>
                <div class="listing-price">
                    <span class="price-value">${formatPrice(property.estimatePrice)}</span>
                </div>
            </div>
        </div>
    </a>`;
        }



        document.addEventListener('DOMContentLoaded', function () {
            // Menu toggle
            const menuToggle = document.getElementById('menuToggle');
            const menuContent = document.getElementById('menuContent');


            if (menuToggle && menuContent) {
                menuToggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    menuContent.classList.toggle('active');
                });
                document.addEventListener('click', function (e) {
                    if (!menuContent.contains(e.target) && !menuToggle.contains(e.target)) {
                        menuContent.classList.remove('active');
                    }
                });
            }



            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('active');
                });
            }
        });



        // Function to handle favorite button clicks
        function handleFavoriteClick(event) {
            event.preventDefault();
            event.stopPropagation();

            const button = event.currentTarget;
            const propertyId = button.dataset.propertyId;
            const icon = button.querySelector('i');

            // Toggle heart icon
            if (icon.classList.contains('far')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                // Add to favorites in database
                addToFavorites(propertyId);
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                // Remove from favorites in database
                removeFromFavorites(propertyId);
            }
        }
        // Function to add property to favorites
        // Add to favorites function
        function addToFavorites(propertyId) {
            fetch('php files/add_favorite.php', {
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
                    if (!data.success && data.message === 'login_required') {
                        showToast('Please log in first');
                    } else if (data.success) {
                        console.log('Added to favorites');
                    } else {
                        console.error('Error:', data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-icon">${type === 'error' ? '⚠️' : type === 'success' ? '✅' : 'ℹ️'}</span>
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;

            // Add to page
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container';
                document.body.appendChild(toastContainer);
            }

            toastContainer.appendChild(toast);

            // Auto remove after 5 seconds
            setTimeout(() => toast.remove(), 5000);

            // Slide in animation
            setTimeout(() => toast.classList.add('show'), 100);
        }

        // Remove from favorites function
        function removeFromFavorites(propertyId) {
            fetch('php files/remove_favorite.php', {
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
                        console.log('Removed from favorites');
                    } else {
                        console.error('Error:', data.message);
                        showToast('please Login first ');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        // Fetch and display properties
        document.addEventListener('DOMContentLoaded', () => {
            const propertiesContainer = document.getElementById('propertiesContainer');
            const userId = <?php echo isset($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null'; ?>;

            // First: Try fetching recommended properties
            fetch(`php files/fetch_properties.php?user_id=${userId}&recommend=true`)
                .then(response => response.json())
                .then(properties => {
                    if (!properties || properties.length === 0) {
                        // Fallback: fetch all properties
                        return fetch('php files/fetch_properties.php?user_id=' + userId)
                            .then(response => response.json());
                    }
                    return properties;
                })
                .then(properties => {
                    propertiesContainer.innerHTML = ''; // Clear previous content

                    if (!properties || properties.length === 0) {
                        propertiesContainer.innerHTML = `
                    <div class="no-properties">
                        <i class="fas fa-home" style="font-size: 3rem; color: var(--primary-color);"></i>
                        <h2>No Properties Available</h2>
                        <p>Check back later for new listings!</p>
                    </div>
                `;
                        return;
                    }

                    properties.forEach(property => {
                        propertiesContainer.innerHTML += createPropertyCard(property);
                    });

                    document.querySelectorAll('.favorite-btn').forEach(button => {
                        button.addEventListener('click', handleFavoriteClick);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    propertiesContainer.innerHTML = `
                <div class="no-properties">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #dc3545;"></i>
                    <h2>Error Loading Properties</h2>
                    <p>Please try again later.</p>
                </div>
            `;
                });
        });

        document.getElementById('search-input').addEventListener('input', function () {
            const query = this.value.trim();
            const suggestionsBox = document.getElementById('suggestions');

            if (query.length < 2) {
                suggestionsBox.innerHTML = '';
                return;
            }

            fetch('php files/ajax.php?query=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    suggestionsBox.innerHTML = '';
                    suggestionsBox.style.display = 'block';
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.textContent = item.title + ' - ' + item.address;
                        div.addEventListener('click', () => {
                            window.location.href = 'property.php?id=' + item.id_property;
                        });
                        suggestionsBox.appendChild(div);
                    });
                })
                .catch(error => {
                    console.error('Search error:', error);
                    suggestionsBox.innerHTML = '';
                });
        });

        //  Handle Enter key to show property cards
        document.getElementById('search-input').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // Prevent form submission if in a form
                const query = this.value.trim();
                const suggestionsBox = document.getElementById('suggestions');
                suggestionsBox.innerHTML = '';
                suggestionsBox.style.display = 'none';

                if (query.length < 2) return;

                fetch('php files/ajax.php?query=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        const resultsContainer = document.getElementById('propertiesContainer');

                        if (!resultsContainer) {
                            console.error("Missing #property container in HTML");
                            return;
                        }

                        resultsContainer.innerHTML = '';

                        if (data.length === 0) {
                            resultsContainer.innerHTML = `
                        <div class="no-properties"></div>
                            <h2>No Properties Available</h2>
                        </div>
                    `;
                        }

                        data.forEach(property => {
                            resultsContainer.innerHTML += createPropertyCard(property);
                        });
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                    });
            }
        });



        // Category Navigation
        const categoryItems = document.querySelectorAll('.category-item');
        categoryItems.forEach(item => {
            item.addEventListener('click', () => {
                categoryItems.forEach(i => i.classList.remove('active'));
                item.classList.add('active');
            });
        });

        // Favorite Button Functionality
        const favoriteButtons = document.querySelectorAll('.favorite-btn');
        favoriteButtons.forEach(button => {
            button.addEventListener('click', () => {
                button.classList.toggle('active');
                const icon = button.querySelector('i');
                if (button.classList.contains('active')) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                }
            });
        });

        // Add this to your existing JavaScript
        const menuToggle = document.getElementById('menuToggle');
        const menuContent = document.getElementById('menuContent');


        //starts here
        document.addEventListener('DOMContentLoaded', function () {
            const rentNav = document.getElementById('rent-nav');
            const sellNav = document.getElementById('sell-nav');
            const filterBtn = document.getElementById("filterBtn");
            const closeBtn = document.querySelector(".close");
            const modal = document.getElementById("filterModal");
            const filterForm = document.getElementById("filterForm");

            const eventCategory = document.getElementById("villa");
            const apartmentCategory = document.getElementById("apartment");
            const houseCategory = document.getElementById("house");
            const roomCategory = document.getElementById("room");

            let selectedType = '';       // Default type
            let selectedCategory = null;        // No default category

            // Navigation events    
            rentNav.addEventListener('click', () => {
                rentNav.classList.add('active-nav');
                sellNav.classList.remove('active-nav');
                selectedType = 'renting';
                fetchProperties(selectedType, {}, selectedCategory);
            });

            sellNav.addEventListener('click', () => {
                sellNav.classList.add('active-nav');
                rentNav.classList.remove('active-nav');
                selectedType = 'selling';
                fetchProperties(selectedType, {}, selectedCategory);
            });

            // Category clicks
            [eventCategory, apartmentCategory, houseCategory, roomCategory].forEach(cat => {
                cat.addEventListener("click", () => {
                    // Clear active classes
                    [eventCategory, apartmentCategory, houseCategory, roomCategory].forEach(c => c.classList.remove('active'));
                    cat.classList.add('active');
                    selectedCategory = cat.id; // 'villa', 'apartment', 'house'
                    fetchProperties(selectedType, {}, selectedCategory);
                });
            });

            // Modal show/hide
            filterBtn.onclick = () => modal.style.display = "block";
            closeBtn.onclick = () => modal.style.display = "none";
            window.onclick = e => { if (e.target === modal) modal.style.display = "none"; };

            // Handle filter form submit
            filterForm.addEventListener("submit", function (e) {
                e.preventDefault();

                const filters = {
                    bedrooms: document.getElementById("bedrooms").value,
                    bathrooms: document.getElementById("bathrooms").value,
                    min_price: document.getElementById("min_price").value,
                    max_price: document.getElementById("max_price").value,
                    city: document.getElementById("city").value,
                    type: document.getElementById("type").value,
                    propertyType: document.getElementById("propertyType").value,
                    size: document.getElementById("size").value
                };

                fetchProperties(selectedType, filters, selectedCategory);
                modal.style.display = "none";
            });

            function fetchProperties(type, filters = {}, category = null) {
                const mainContent = document.querySelector('.main-content');
                mainContent.innerHTML = `<div class="loading">Loading ${type} properties...</div>`;

                let url = `php files/filter.php?type=${type}`;
                Object.keys(filters).forEach(key => {
                    if (filters[key]) {
                        url += `&${key}=${encodeURIComponent(filters[key])}`;
                    }
                });

                if (category) {
                    url += `&category=${encodeURIComponent(category)}`;
                }

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            displayProperties(data.properties, mainContent, type, category);
                            // disable the search-bar
                            document.getElementById('search-input').disabled = true;
                        } else {
                            mainContent.innerHTML = `<div class="no-results"><h2>${data.message}</h2></div>`;
                        }
                    })
                    .catch(error => {
                        mainContent.innerHTML = `<div class="error"><p>Error: ${error.message}</p></div>`;
                    });
            }

            function displayProperties(properties, container, type, category) {
                container.innerHTML = '';
                const heading = document.createElement('h2');
                heading.textContent = (type === 'selling') ? 'Properties for Sale' : 'Properties for Rent';
                heading.className = 'section-title';
                container.appendChild(heading);

                const listingsGrid = document.createElement('div');
                listingsGrid.className = 'listings-grid';

                properties.forEach(property => {
                    const cardHTML = createPropertyCard(property);
                    const tempContainer = document.createElement('div');
                    tempContainer.innerHTML = cardHTML;
                    listingsGrid.appendChild(tempContainer.firstElementChild);
                });

                container.appendChild(listingsGrid);

            }
        });

        // Search Bar 
        document.getElementById('search-input').addEventListener('input', function () {
            const query = this.value.trim();
            const suggestionsBox = document.getElementById('suggestions');

            if (query.length < 2) {
                suggestionsBox.innerHTML = '';
                return;
            }

            fetch('php files/ajax.php?query=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    suggestionsBox.innerHTML = '';
                    data.forEach(item => {
                        const div = document.createElement('div');

                        div.textContent = item.title + ' - ' + item.address;
                        div.addEventListener('click', () => {
                            window.location.href = 'property.php?id=' + item.id_property;
                        });
                        suggestionsBox.appendChild(div);
                    });
                })
                .catch(error => {
                    console.error('Search error:', error);
                    suggestionsBox.innerHTML = '';
                });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationContainer = document.getElementById('notificationContainer');
            const notificationList = document.getElementById('notificationList');
            const clearButton = document.querySelector('.clear-notifications');

            // Toggle dropdown
            notificationIcon.addEventListener('click', function (e) {
                e.stopPropagation(); // prevent closing when clicking the icon
                notificationContainer.classList.toggle('show');

                // Mark notifications as read
                fetch('php files/fetch_notifications.php?action=mark_read');
            });

            // Close dropdown if clicking outside
            window.addEventListener('click', function (e) {
                if (!notificationContainer.contains(e.target) && !notificationIcon.contains(e.target)) {
                    notificationContainer.classList.remove('show');
                }
            });

            // Fetch notifications
            fetch('php files/fetch_notifications.php?action=fetch')
                .then(response => response.json())
                .then(notifications => {
                    notificationList.innerHTML = ''; // Clear previous content

                    if (!notifications || notifications.length === 0) {
                        notificationList.innerHTML = '<p class="notification-item">No notifications</p>';
                        return;
                    }

                    notifications.forEach(notification => {
                        const notif = document.createElement('div');
                        notif.classList.add('notification-item');

                        if (Number(notification.is_read) === 1) {
                            notif.style.backgroundColor = '#f0f0f0'; // read
                        } else {
                            notif.style.backgroundColor = '#dff0d8'; // unread
                        }

                        notif.innerHTML = `
                    <h3>${notification.message}</h3>
                    <strong><span>${new Date(notification.timestamp).toLocaleString()}</span></strong>
                `;

                        // Click on a single notification to mark as read
                        notif.addEventListener('click', () => {
                            fetch('php files/fetch_notifications.php?action=mark_read', {
                                method: 'POST',
                            })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        notif.style.backgroundColor = '#f0f0f0';
                                    }
                                });
                        });

                        notificationList.appendChild(notif);
                    });
                })
                .catch(err => {
                    console.error('Error fetching notifications:', err);
                    notificationList.innerHTML = '<p class="notification-item">Nothing here</p>';
                });

            // Clear notifications logic
            if (clearButton) {
                clearButton.addEventListener('click', () => {
                    fetch('php files/fetch_notifications.php?action=clear', {
                        method: 'POST'
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                notificationList.innerHTML = '<p class="notification-item">No notifications</p>';
                            } else {
                                console.error('Clear failed:', data.error);
                            }
                        })
                        .catch(err => console.error('Error:', err));
                });
            }
        });



    </script>
</body>

</html>
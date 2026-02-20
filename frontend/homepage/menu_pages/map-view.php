<?php
session_start();
require_once '../php files/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login-signup/index.php');
    exit();
}
$isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in


// Fetch basic property info for display
$sql = "SELECT id_property, title, address, city, state, socialCode, country, estimatePrice
        FROM Property";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Properties Map</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
    <script src="../script.js"></script>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
        }

        #map {
            margin-left: 18%;
            height: 100vh;
            width: 100%;
        }

        .sidebar {
            margin-top: 80px;
        }

        .leaflet-touch .leaflet-control-layers,
        .leaflet-touch .leaflet-bar {
            border: 2px solid rgba(0, 0, 0, 0.2);
            background-clip: padding-box;
            margin-top: 95px;
        }

        @media screen and (max-width: 768px) {
.header-content {
        flex-direction: column; /* Stack items vertically on mobile */
        align-items: flex-start; /* Align items to the start when stacked */
        padding: 10px 15px; /* Reduce padding for smaller screens */
    }
        }
    </style>
</head>

<body>
    <div id="menuToggle">

        <!-- Include header from index.html -->
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
                    <a href="../index.php" class="menu-item ">
                        <i class="fas fa-home"></i>
                        Home
                    </a>

                    <a href="map-view.php" class="menu-item active">
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
                        <a href="my-properties.php" class="menu-item">
                            <i class="fas fa-building"></i>
                            My Properties
                        </a>
                        <a href="#" class="menu-item">
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
                    <a href="settings.php" class="menu-item">
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
                    <a href="help-center.php" class="menu-item">
                        <i class="fas fa-question-circle"></i>
                        Help Center
                    </a>

                    <a href="about-us.php" class="menu-item">
                        <i class="fas fa-info-circle"></i>
                        About Us
                    </a>
                </div>

                <?php if ($isLoggedIn): ?>
                    <div class="menu-section">
                        <a href="../../login-signup/php files/logout.php" class="menu-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                <?php endif; ?>
            </nav>
        </aside>


        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <a href="../index.php" class="logo">
                        <img src="../../rently2.png" alt="" style="height: 38px; width: 130px;">
                    </a>
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
            </div>
        </header>
    </div>
    <div id="map"></div>


    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const properties = <?php echo json_encode($properties); ?>;

        let map = L.map('map').setView([28.0, 3.0], 6); // Algeria center

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Loop through each property
        properties.forEach((property, index) => {
            const fullAddress = `${property.address}, ${property.city}, ${property.state}, ${property.country}`;

            setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            const { lat, lon } = data[0];
                            const marker = L.marker([lat, lon]).addTo(map);

                            marker.bindPopup(`
                            <strong><a href="../property.php?id=${property.id_property}" target="_blank" style="text-decoration: none; color: inherit;">${property.title}</a></strong>
                            ${property.address}, ${property.city}<br>
                            <em>${property.estimatePrice} DZD</em>
                        `);
                        } else {
                            console.warn('No coordinates found for:', fullAddress);
                        }
                    })
                    .catch(error => console.error('Geocoding error:', error));
            }, index * 1000); // 1 second delay to respect Nominatim usage policy
        });
    </script>

</body>

</html>
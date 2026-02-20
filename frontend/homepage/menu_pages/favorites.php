<?php
session_start();
require_once '../php files/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login-signup/index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$successMessage = '';
$errorMessage = '';
$isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in




// Fetch user information
try {
    $stmt = $pdo->prepare("SELECT * FROM Client WHERE id_client = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    $errorMessage = "Error fetching user data: " . $e->getMessage();
}

// Handle form submission
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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
            .settings-container {
                grid-template-columns: 1fr;
                margin-left: 0px;
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
                top:35px;
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
                <div class="user-name">
                    <?php echo '<h2 style="text-color:rgb(121, 198, 233)">WELCOME</h2> ', $_SESSION['user_name']; ?></div>
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
                    <a href="favorites.php" class="menu-item active">
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
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    Account Settings
                </a>
                <a href="privacy.php" class="menu-item">
                    <i class="fas fa-shield-alt"></i>
                    Privacy & Security
                </a>

            </div>

            <!-- <div class="menu-section">
                <div class="menu-section-title">Support</div>
                <a href="../help-center.php" class="menu-item">
                    <i class="fas fa-question-circle"></i>
                    Help Center
                </a>

                <a href="../about-us.php" class="menu-item">
                    <i class="fas fa-info-circle"></i>
                    About Us
                </a>
            </div> -->

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

    <!-- Header (Same as index.php) -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <a href="../index.php" class="logo">
                    <img src="../../rently2.png" alt="" style="height: 38px; width: 130px;">
                </a>
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

            <!-- <div class="user-menu">
                <div class="user-menu-item menu-dropdown">
                    <i class="fas fa-bars" id="menuToggle"></i>
                    <div class="menu-content" id="menuContent">
                    <a href="index.php" class="menu-item active">
                            <i class="fas fa-home"></i>
                            dashboard
                        </a>
                        
                        <?php if (isset($_SESSION['is_owner']) && $_SESSION['is_owner']): ?>
                            <a href="my-properties.php" class="menu-item">
                                <i class="fas fa-building"></i>
                                My Properties
                            </a>
                        <?php endif; ?>
                        
                        <a href="#" class="menu-item" id="language-toggle">
                            <i class="fas fa-globe"></i>
                            Language
                        </a>
                        <a href="#" class="menu-item">
                            <i class="fas fa-question-circle"></i>
                            Help Center
                        </a>

                        <div class="language-menu" id="language-menu">
                            <ul>
                                <li><a href="#" data-lang="en">English</a></li>
                                <li><a href="#" data-lang="es">Spanish</a></li>
                                <li><a href="#" data-lang="fr">French</a></li>
                                <li><a href="#" data-lang="de">German</a></li>
                                <li><a href="#" data-lang="zh">Chinese</a></li>
                            </ul>
                        </div>
                        
                        <a href="#" class="menu-item">
                            <i class="fas fa-info-circle"></i>
                            About Us
                        </a>

                        <div class="menu-divider"></div>
                        <a href="login-signup/php files/logout.php" class="menu-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div> -->
        </div>
    </header>

    <div class="settings-container">


        <div class="settings-content">


            <div class="settings-tab" id="favorites-tab">


                <h2>Your Favorites</h2>
                <div class="favorites-container" id="menuToggle">
                    <?php
                    try {
                        // Fetch user's favorite properties
                        $stmt = $pdo->prepare("
                SELECT p.*
                FROM favorits r
                JOIN Property p ON r.id_property = p.id_property
                WHERE r.id_client = ?
            ");
                        $stmt->execute([$userId]);
                        $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($favorites) > 0):
                            ?>
                            <div class="favorites-grid">
                                <?php foreach ($favorites as $property): ?>
                                    <div class="favorite-card">
                                        <div class="favorite-image">
                                            <?php
                                            // Fetch main image for each property
                                            $imgStmt = $pdo->prepare("
                                SELECT image_path 
                                FROM property_images 
                                WHERE property_id = ? 
                                ORDER BY image_order ASC 
                                LIMIT 1
                            ");
                                            $imgStmt->execute([$property['id_property']]);
                                            $img = $imgStmt->fetch(PDO::FETCH_ASSOC);

                                            $imgPath = ($img && !empty($img['image_path']))
                                                ? '../php files/' . htmlspecialchars($img['image_path'])
                                                : '/placeholder.svg';
                                            ?>
                                            <img src="<?php echo $imgPath; ?>"
                                                alt="<?php echo htmlspecialchars($property['title']); ?>">

                                            <button class="remove-favorite"
                                                data-property-id="<?php echo $property['id_property']; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="favorite-info">
                                            <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                                            <p><i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($property['city'] . ', ' . $property['country']); ?></p>
                                            <p class="favorite-price">
                                                <?php echo number_format($property['estimatePrice'], 0, '.', ','); ?> DZD
                                                <?php if ($property['ownerNeeds'] == 'renting'): ?>
                                                    <span>/month</span>
                                                <?php endif; ?>
                                            </p>
                                            <a href="../property.php?id=<?php echo $property['id_property']; ?>"
                                                class="view-property-btn">View Property</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-favorites">
                                <i class="far fa-heart"></i>
                                <p>You haven't added any properties to your favorites yet.</p>
                                <a href="index.php" class="btn primary-btn">Browse Properties</a>
                            </div>
                        <?php
                        endif;
                    } catch (PDOException $e) {
                        echo '<div class="alert error">Error loading favorites: ' . $e->getMessage() . '</div>';
                    }
                    ?>
                </div>
            </div>


        </div>
    </div>

    <script>
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

            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('active');
                });
            }
        




            // Remove favorite
            const removeFavoriteButtons = document.querySelectorAll('.remove-favorite');

            removeFavoriteButtons.forEach(button => {
                button.addEventListener('click', function () {
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
                fetch('../php files/fetch_notifications.php?action=mark_read');
            });

            // Close dropdown if clicking outside
            window.addEventListener('click', function (e) {
                if (!notificationContainer.contains(e.target) && !notificationIcon.contains(e.target)) {
                    notificationContainer.classList.remove('show');
                }
            });

            // Fetch notifications
            fetch('../php files/fetch_notifications.php?action=fetch')
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
                            fetch('../php files/fetch_notifications.php?action=mark_read', {
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
                    fetch('../php files/fetch_notifications.php?action=clear', {
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
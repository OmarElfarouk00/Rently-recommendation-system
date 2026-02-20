<?php
session_start();
require_once '../php files/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login-signup/index.php');
    exit();
}
$isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in
$userId = $_SESSION['user_id'];
$successMessage = '';
$errorMessage = '';



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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        // Validate inputs
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = "All password fields are required";
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = "New passwords do not match";
        } elseif (strlen($newPassword) < 8) {
            $errorMessage = "Password must be at least 8 characters long";
        } else {
            try {
                // Verify current password
                if (password_verify($currentPassword, $user['password'])) {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE Client SET password = ? WHERE id_client = ?");
                    $stmt->execute([$hashedPassword, $userId]);

                    $successMessage = "Password changed successfully";
                } else {
                    $errorMessage = "Current password is incorrect";
                }
            } catch (PDOException $e) {
                $errorMessage = "Error changing password: " . $e->getMessage();
            }
        }
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
        /* Settings Page Styles */
        .settings-container {
            margin-top: 80px;
            padding: 2rem 5%;
            max-width: 1400px;
            margin-left: 200px;
            margin-right: auto;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        /* Sidebar */
        .settings-sidebar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }



        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 1rem;
            border: 3px solid var(--primary-color);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }



        .member-since {
            font-size: 0.9rem;
            color: #999;
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

        /* Content */
        .settings-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 2rem;
        }

        .settings-tab {
            display: block;
        }

        .settings-tab.active {
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

            .settings-sidebar {
                margin-bottom: 1rem;
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
                top: 35px;
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
                    <a href="." class="menu-item ">
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
                <a href="privacy.php" class="menu-item active">
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
        </div>
    </header>

    <div class="settings-container" id="menuToggle">

        <div class="settings-content">
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



            <div class="settings-tab" id="security-tab">
                <h2>Security Settings</h2>
                <form action="settings.php" method="POST" class="settings-form">
                    <div class="form-group">
                        <label for="current_password">Current Password*</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password*</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password*</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" name="change_password" class="btn primary-btn">Change Password</button>
                </form>
            </div>



            <div class="settings-tab" id="favorites-tab">


            </div>


        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Menu toggle
            const menuToggle = document.getElementById('menuToggle');
            const menuContent = document.getElementById('menuContent');

            menuToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                menuContent.classList.toggle('active');
            });

            document.addEventListener('click', function (e) {
                if (!menuContent.contains(e.target) && !menuToggle.contains(e.target)) {
                    menuContent.classList.remove('active');
                }
            });

            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('active');
                });
            }


            // Settings tabs
            const tabLinks = document.querySelectorAll('.settings-nav li');
            const tabContents = document.querySelectorAll('.settings-tab');

            tabLinks.forEach(link => {
                link.addEventListener('click', function () {
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
                button.addEventListener('click', function () {
                    const propertyId = this.getAttribute('data-property-id');
                    const card = this.closest('.favorite-card');

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
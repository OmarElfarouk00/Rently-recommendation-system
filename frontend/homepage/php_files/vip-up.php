<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login-signup/index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$successMessage = '';
$errorMessage = '';
$isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Get form data
        $name = $_POST['name'] ?? '';
        $username = $_POST['username'] ?? '';

        // if (empty($name) || empty($username)) {
        //     ("Name and username are required.");
        // }

        // Check file
        if (!isset($_FILES['paymentReceipt']) || $_FILES['paymentReceipt']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Payment receipt file is required and must be uploaded successfully.");
        }

        $receiptData = $_FILES['paymentReceipt'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $extension = strtolower(pathinfo($receiptData['name'], PATHINFO_EXTENSION));

        // if (!in_array($extension, $allowedExtensions)) {
        //     throw new Exception("Unsupported file type. Only JPG, PNG, and PDF are allowed.");
        // }

        // Upload file
        $targetDir = "uploads/receipts/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $newFileName = 'receipt_' . uniqid() . '.' . $extension;
        $targetFile = $targetDir . $newFileName;

        if (!move_uploaded_file($receiptData['tmp_name'], $targetFile)) {
            throw new Exception("Failed to upload payment receipt.");
        }

        // Save to database (modify table name and fields as needed)
        $stmt = $pdo->prepare("INSERT INTO PaymentReceipts (name, username, receipt_path, uploaded_at,id_client) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->execute([$name, $username, $targetFile, $userId]);

        $pdo->commit();
        $_SESSION['success_message'] = "Payment receipt uploaded successfully.You will be upgraded in the next 24Hrs.";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
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
    <script src="../script.js"></script>
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

        /* Responsive */


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
                top: 35px;
            }
        }

        /* VIP Upgrade Page Specific Styles */
        .vip-container {
            margin-top: 80px;
            padding: 2rem 5%;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .page-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title h1 {
            font-size: 2.5rem;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .page-title p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Pricing Plans */
        .pricing-plans {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-bottom: 4rem;
            flex-wrap: wrap;
        }

        .plan {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            flex: 1;
            min-width: 280px;
            max-width: 350px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }

        .plan:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .plan.featured {
            border: 2px solid var(--primary-color);
            transform: scale(1.05);
        }

        .plan.featured:hover {
            transform: scale(1.05) translateY(-5px);
        }

        .best-value {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .plan-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .plan-header h2 {
            font-size: 1.8rem;
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .plan-price {
            margin-bottom: 0.5rem;
        }

        .price {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--text-color);
        }

        .period {
            font-size: 1rem;
            color: #666;
        }

        .savings {
            color: #28a745;
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .plan-features {
            margin-bottom: 2rem;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .feature i {
            color: #28a745;
        }

        .select-plan {
            width: 100%;
            padding: 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .select-plan:hover {
            background-color: #d65b1e;
        }

        /* Payment Section */
        .payment-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 4rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: block;
        }

        .payment-section h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--text-color);
        }

        .selected-plan-info {
            background: var(--light-gray);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .selected-plan-info p {
            margin: 0.5rem 0;
            color: var(--text-color);
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            margin-bottom: 1rem;
            color: var(--text-color);
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .form-input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .card-input-wrapper {
            position: relative;
        }

        .card-icons {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            gap: 5px;
        }

        .card-icons i {
            font-size: 1.5rem;
            color: #666;
        }

        .terms-agreement {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .terms-agreement label {
            font-size: 0.9rem;
            color: #666;
        }

        .terms-agreement a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .submit-payment {
            width: 100%;
            padding: 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-payment:hover {
            background-color: #d65b1e;
        }

        /* Success Message */
        .success-message {
            background: white;
            border-radius: 15px;
            padding: 3rem 2rem;
            margin-bottom: 4rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            display: none;
        }

        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1.5rem;
        }

        .success-message h2 {
            color: var(--text-color);
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .success-message p {
            color: #666;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .return-home {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.8rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .return-home:hover {
            background-color: #d65b1e;
        }

        /* VIP Benefits Section */
        .vip-benefits {
            margin-bottom: 4rem;
        }

        .vip-benefits h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--text-color);
            font-size: 2rem;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .benefit-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }

        .benefit-card:hover {
            transform: translateY(-5px);
        }

        .benefit-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }

        .benefit-card h3 {
            color: var(--text-color);
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .benefit-card p {
            color: #666;
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


                <!-- Main Content -->
                <div class="vip-container">
                    <div class="page-title">
                        <!-- success message -->
                        <?php if (!empty($_SESSION['success_message'])): ?>
                            <div class="alert success">
                                <i class="fas fa-check-circle"></i>
                                <?php
                                echo $_SESSION['success_message'];
                                unset($_SESSION['success_message']);
                                ?>
                            </div>
                        <?php endif; ?>
                        <h1>Upgrade to VIP</h1>
                        <p>Unlock premium features and get the most out of Rently</p>
                    </div>

                    <div class="pricing-plans">

                        <div class="plan featured">
                            <div class="best-value">Best Value</div>
                            <div class="plan-header">
                                <h2>Unlimited</h2>
                                <div class="plan-price">
                                    <span class="price">1000DA</span>
                                    <span class="period">/Unlimited</span>
                                </div>
                            </div>
                            <div class="plan-features">
                                <!-- <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>Access to premium listings</span>
                    </div> -->
                                <div class="feature">
                                    <i class="fas fa-check"></i>
                                    <span>Top Listing</span>
                                </div>

                                <div class="feature">
                                    <i class="fas fa-check"></i>
                                    <span>Unlimited property photos upload</span>
                                </div>
                                <!-- <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>Priority customer support</span>
                    </div> -->
                                <div class="feature">
                                    <i class="fas fa-check"></i>
                                    <span>No ads experience</span>
                                </div>

                                <div class="feature">
                                    <i class="fas fa-check"></i>
                                    <span>List properties for sale</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Payment Form (Initially Hidden) -->
                    <div class="payment-section" id="paymentSection">
                        <h2>Complete Your VIP Upgrade</h2>
                        <div class="selected-plan-info">
                            <p>Selected Plan: <span id="selectedPlanName">Unlimited</span></p>
                            <p>Price: <span id="selectedPlanPrice">1000DA</span></p>
                        </div>

                        <!-- <form id="paymentForm" action="process-vip-upgrade.php" method="POST">
                            <input type="hidden" id="planType" name="planType" value="annual">
                            <input type="hidden" id="planPrice" name="planPrice" value="49.99"> -->

                        <div class="form-section">
                            <h3>Payment Information*</h3>
                            <div class="form-group">
                                <select name="baridi" class="form-input">
                                    <option value="">-Select-</option>
                                    <option value="baridi">Baridi Mob</option>
                                </select>
                            </div>


                            <div class="form-group">
                                <label for="cardNumber">Card Number*</label>
                                <div class="card-input-wrapper">
                                    <input type="text" id="cardNumber" name="cardNumber" class="form-input"
                                        value="6280 7031 5432 1407" required minlength="16" readonly>
                                </div>
                            </div>
                            <form action="vip-up.php" method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="name">Your name*</label>
                                    <input type="text" class="form-input" name="name" id="name" required>
                                </div>
                                <?php
                                    // Get user full name
                                    $fullName = '';
                                    $stmt = $pdo->prepare("SELECT full_name FROM client WHERE id_client = ?");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $user = $stmt->fetch();
                                    if ($user) {
                                        $fullName = htmlspecialchars($user['full_name']); // avoid XSS
                                    }
                                ?>
                                <div class="form-group">
                                    <label for="username">Your username*</label>
                                    <input type="text" class="form-input" name="username" id="username"
                                        value="<?php echo $fullName; ?>" required readonly>
                                </div>
                                <div class="form-group">
                                    <label for="paymentReceiptUpload">Upload Payment Receipt*</label>
                                    <input type="file" id="paymentReceiptUpload" name="paymentReceipt"
                                        accept=".pdf, image/*" required>
                                    <small class="form-text text-muted">Please upload a clear image (JPG, PNG) or
                                        PDF of your payment receipt.</small>
                                </div>
                                <!-- <button type="submit">Submit</button>
                                </form> -->
                                <!-- <div class="form-row">
                        <div class="form-group">
                            <label for="expiryDate">Expiry Date</label>
                            <input type="text" id="expiryDate" name="expiryDate" class="form-input" placeholder="MM/YY" required pattern="\d{2}/\d{2}">
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" name="cvv" class="form-input" placeholder="1234" required minlength="4">
                        </div>
                    </div> -->
                        </div>


                        <div class="terms-agreement">
                            <input type="checkbox" id="termsAgree" name="termsAgree" required>
                            <label for="termsAgree">I agree to the <a href="#">Terms and Conditions</a> and <a
                                    href="#">Privacy
                                    Policy</a></label>
                        </div>

                        <button type="submit" class="submit-payment">Complete Upgrade</button>
                        </form>
                    </div>

                    <!-- Success Message (Initially Hidden) -->
                    <div class="success-message" id="successMessage">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2>Congratulations!</h2>
                        <p>Your account has been successfully upgraded to VIP status.</p>
                        <p>You now have access to all premium features.</p>
                        <a href="index.php" class="return-home">Return to Homepage</a>
                    </div>

                    <!-- VIP Benefits Section -->
                </div>
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

        });

                document.addEventListener('DOMContentLoaded', () => {
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationContainer = document.getElementById('notificationContainer');
            const notificationList = document.getElementById('notificationList');

            // Toggle dropdown
            notificationIcon.addEventListener('click', function () {
                notificationContainer.classList.toggle('show');
                fetch('fetch_notifications.php?action=mark_read');
            });

            // Fetch notifications
            fetch('fetch_notifications.php?action=fetch')
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
                        console.log(notification.is_read);
                        if (Number(notification.is_read) == 0) {
                            notif.style.backgroundColor = '#f0f0f0'; // unread
                        } else {
                            notif.style.backgroundColor = '#dff0d8'; // read
                        }
                        notif.innerHTML = `
                    <h3>${notification.message}</h3>
                    <strong><span>${new Date(notification.timestamp).toLocaleString()}</span></strong>
                `;
                        notificationList.appendChild(notif);
                    });
                })
                .catch(err => {
                    console.error('Error fetching notifications:', err);
                    notificationList.innerHTML = '<p class="notification-item">Nothing here</p>';
                });

            // Optional: Clear notifications logic
            document.querySelector('.clear-notifications').addEventListener('click', () => {
                fetch('fetch_notifications.php?action=clear', {
                    method: 'POST'
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const notificationList = document.querySelector('.notification-list');
                            notificationList.innerHTML = '<p class="notification-item">No notifications</p>';
                        } else {
                            console.error('Clear failed:', data.error);
                        }
                    })
                    .catch(err => console.error('Error:', err));
            });

            notif.addEventListener('click', () => {
                fetch('fetch_notifications.php?action=mark_read', {
                    method: 'POST',
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            notif.style.backgroundColor = '#dff0d8'; // Mark visually as read
                        }
                    });
            });

        });



    </script>
</body>

</html>
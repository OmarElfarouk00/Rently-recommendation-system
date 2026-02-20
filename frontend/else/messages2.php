<?php
session_start();
require_once '../php files/config.php';

// Check if client is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$isLoggedIn = true;
$successMessage = '';
$errorMessage = '';

try {
    // Fetch client's negotiations and related messages from owners
    $stmt = $pdo->prepare("
    SELECT 
        n.id AS negotiation_id,
        n.proposedPrice,
        n.status,
        p.title AS property_title,
        p.estimatePrice AS property_price,
        m.message,
        m.sent_at
    FROM Negotiation n
    JOIN Property p ON n.id_property = p.id_property
    JOIN Messages m ON m.id_negotiation = n.id AND m.sender_role = 'owner'
    WHERE n.id_client = ?
    ORDER BY m.sent_at DESC
");    $stmt->execute([$userId]);
    $negotiations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group messages by negotiation
    $groupedNegotiations = [];
    foreach ($negotiations as $item) {
        $negotiationId = $item['negotiation_id'];
        
        if (!isset($groupedNegotiations[$negotiationId])) {
            $groupedNegotiations[$negotiationId] = [
                'negotiation_id' => $item['negotiation_id'],
                'property_title' => $item['property_title'],
                'property_price' => $item['property_price'],
                'proposed_price' => $item['proposedPrice'],
                'status' => $item['status'],
                'messages' => []
            ];
        }
        
        if (!empty($item['message_id'])) {
            $groupedNegotiations[$negotiationId]['messages'][] = [
                'message_id' => $item['message_id'],
                'message' => $item['message'],
                'sent_at' => $item['sent_at'],
                'is_read' => $item['is_read']
            ];
        }
    }

    // Mark unread messages as read
    $unreadMessageIds = [];
    foreach ($groupedNegotiations as $negotiation) {
        foreach ($negotiation['messages'] as $message) {
            if ($message['is_read'] == 0) {
                $unreadMessageIds[] = $message['message_id'];
            }
        }
    }

    if (!empty($unreadMessageIds)) {
        $placeholders = implode(',', array_fill(0, count($unreadMessageIds), '?'));
        $updateStmt = $pdo->prepare("UPDATE Messages SET is_read = 1 WHERE id IN ($placeholders)");
        $updateStmt->execute($unreadMessageIds);
    }

    // Get property images
    $propertyImages = [];
    $propertyIds = array_column($negotiations, 'id_property');
    
    if (!empty($propertyIds)) {
        $uniquePropertyIds = array_unique($propertyIds);
        $placeholders = implode(',', array_fill(0, count($uniquePropertyIds), '?'));
        
        $imgStmt = $pdo->prepare("
            SELECT property_id, image_path 
            FROM Property_Images 
            WHERE property_id IN ($placeholders)
            GROUP BY property_id
        ");
        $imgStmt->execute($uniquePropertyIds);
        
        while ($row = $imgStmt->fetch(PDO::FETCH_ASSOC)) {
            $propertyImages[$row['property_id']] = $row['image_path'];
        }
    }

    // Get counts for badges
    $favStmt = $pdo->prepare("SELECT COUNT(*) FROM React WHERE id_client = ? AND count > 0");
    $favStmt->execute([$userId]);
    $favoriteCount = $favStmt->fetchColumn();

    $unreadMsgStmt = $pdo->prepare("
        SELECT COUNT(*) FROM Messages m
        JOIN Negotiation n ON m.id_negotiation = n.id
        WHERE n.id_client = ? AND m.sender_role = 'owner' AND m.is_read = 0
    ");
    $unreadMsgStmt->execute([$userId]);
    $unreadMessages = $unreadMsgStmt->fetchColumn();

    $notifStmt = $pdo->prepare("SELECT COUNT(*) FROM Notifications WHERE id_client = ? AND is_read = 0");
    $notifStmt->execute([$userId]);
    $unreadNotifications = $notifStmt->fetchColumn();

} catch (PDOException $e) {
    $errorMessage = "Failed to fetch messages: " . $e->getMessage();
    $groupedNegotiations = [];
}

// Handle sending new messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $negotiationId = $_POST['negotiation_id'];
    $messageContent = $_POST['message_content'];
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO Messages (id_negotiation, sender_role, message, sent_at, is_read)
            VALUES (?, 'client', ?, NOW(), 0)
        ");
        $stmt->execute([$negotiationId, $messageContent]);
        $successMessage = "Message sent successfully";
        
        // Redirect to prevent form resubmission
        header("Location: messages.php?success=1");
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Failed to send message: " . $e->getMessage();
    }
}

// Handle success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $successMessage = "Message sent successfully";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Messages - RentEstate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* My Messages Page Styles */
        .my-messages-container {
            margin-top: 80px;
            padding: 2rem 5%;
            max-width: 1400px;
            margin-left: 200px;
            margin-right: auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            color: var(--text-color);
            font-size: 2rem;
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

        /* Dashboard Tabs */
        .dashboard-tabs {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .tab-header {
            display: flex;
            border-bottom: 1px solid var(--border-color);
        }

        .tab-btn {
            flex: 1;
            padding: 1.2rem;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
            position: relative;
        }

        .tab-btn i {
            font-size: 1.2rem;
        }

        .tab-btn:hover {
            color: var(--primary-color);
            background: var(--light-gray);
        }

        .tab-btn.active {
            color: var(--primary-color);
            background: var(--light-gray);
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary-color);
        }

        .count {
            background: var(--primary-color);
            color: white;
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            margin-left: 0.5rem;
        }

        .tab-content {
            display: none;
            padding: 2rem;
        }

        .tab-content.active {
            display: block;
        }

        /* Messages List */
        .messages-list {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .message-card {
            background: var(--light-gray);
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .message-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: white;
            border-bottom: 1px solid var(--border-color);
        }

        .message-header h3 {
            margin: 0;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .message-header h3 i {
            color: var(--primary-color);
        }

        .message-date {
            color: #666;
            font-size: 0.9rem;
        }

        .message-content {
            display: flex;
            flex-direction: column;
            padding: 0;
        }

        .message-property {
            display: flex;
            border-bottom: 1px solid var(--border-color);
        }

        .property-image {
            width: 200px;
            height: 150px;
            flex-shrink: 0;
        }

        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .property-details {
            flex: 1;
            padding: 1.5rem;
            background: white;
        }

        .property-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .property-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .property-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .property-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #666;
            font-size: 0.9rem;
        }

        .property-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .property-price span {
            font-size: 0.9rem;
            font-weight: normal;
            color: #666;
        }

        .negotiation-details {
            padding: 1.5rem;
            background: white;
            border-bottom: 1px solid var(--border-color);
        }

        .negotiation-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .price-comparison {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            margin: 1rem 0;
            padding: 1rem;
            background: var(--light-gray);
            border-radius: 10px;
        }

        .original-price, .offered-price {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .original-price span, .offered-price span {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .original-price strong {
            color: var(--text-color);
            font-size: 1.2rem;
        }

        .offered-price strong {
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .percent-diff {
            margin-top: 0.5rem;
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .percent-diff.discount {
            background: #d4edda;
            color: #155724;
        }

        .percent-diff.increase {
            background: #f8d7da;
            color: #721c24;
        }

        .negotiation-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 1rem;
        }

        .negotiation-status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .negotiation-status.accepted {
            background: #d4edda;
            color: #155724;
        }

        .negotiation-status.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .client-message {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--light-gray);
            border-radius: 10px;
        }

        .client-message strong {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .client-message p {
            margin: 0;
            color: #666;
            line-height: 1.5;
        }

        .messages-section {
            padding: 1.5rem;
        }

        .messages-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .messages-title i {
            color: var(--primary-color);
        }

        .message-thread {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .message-bubble {
            max-width: 80%;
            padding: 1rem;
            border-radius: 10px;
            position: relative;
        }

        .message-bubble.owner {
            align-self: flex-start;
            background: var(--light-gray);
            color: var(--text-color);
            border-bottom-left-radius: 0;
        }

        .message-bubble.client {
            align-self: flex-end;
            background: var(--primary-color);
            color: white;
            border-bottom-right-radius: 0;
        }

        .message-text {
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .message-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .message-sender {
            font-weight: 500;
        }

        .message-time {
            text-align: right;
        }

        .reply-form {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
        }

        .reply-input {
            flex: 1;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            resize: none;
            font-family: inherit;
            font-size: 1rem;
        }

        .reply-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .send-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0 1.5rem;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .send-btn:hover {
            background: #d65b1e;
        }

        .message-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .view-btn {
            background: var(--light-gray);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .view-btn:hover {
            background: #e0e0e0;
        }

        .primary-btn {
            background: var(--primary-color);
            color: white;
        }

        .primary-btn:hover {
            background: #d65b1e;
        }

        /* No Messages */
        .no-messages {
            text-align: center;
            padding: 3rem 1rem;
        }

        .no-messages i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .no-messages p {
            color: #666;
            margin-bottom: 1.5rem;
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

        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 999;
            padding: 15px 0;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff385c;
            text-decoration: none;
        }

        .logo i {
            margin-right: 10px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .my-messages-container {
                margin-left: 0;
                padding: 2rem;
            }

            .sidebar-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-250px);
            }

            .sidebar.expanded {
                transform: translateX(0);
            }

            .message-property {
                flex-direction: column;
            }

            .property-image {
                width: 100%;
                height: 200px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .price-comparison {
                flex-direction: column;
                gap: 1rem;
            }

            .price-comparison i {
                transform: rotate(90deg);
            }

            .message-bubble {
                max-width: 90%;
            }
        }

        @media (max-width: 576px) {
            .message-actions {
                flex-direction: column;
                width: 100%;
            }

            .message-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .reply-form {
                flex-direction: column;
            }

            .send-btn {
                width: 100%;
                justify-content: center;
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
                <div class="user-name"><?php echo '<h2 style="color:rgb(121, 198, 233)">WELCOME</h2> ',$_SESSION['user_name']; ?></div>
            <?php else: ?>
                <div class="user-name"><h2>Welcome </h2> Guest</div>
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
                    <?php if ($favoriteCount > 0): ?>
                    <span class="menu-badge"><?php echo $favoriteCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="messages.php" class="menu-item active">
                    <i class="fas fa-envelope"></i>
                    Messages
                    <?php if ($unreadMessages > 0): ?>
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
                <a href="logout.php" class="menu-item">
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
                <i class="fas fa-home"></i>
                <a href="index.php" class="logo">RentEstate</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="my-messages-container">
        <div class="dashboard-header">
            <h1>My Messages</h1>
        </div>
        
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
                <button class="tab-btn active" data-tab="all">
                    <i class="fas fa-envelope"></i> All Messages
                    <span class="count"><?php echo count($groupedNegotiations); ?></span>
                </button>
                <button class="tab-btn" data-tab="pending">
                    <i class="fas fa-clock"></i> Pending
                    <span class="count"><?php echo count(array_filter($groupedNegotiations, function($n) { return $n['status'] === 'pending'; })); ?></span>
                </button>
                <button class="tab-btn" data-tab="accepted">
                    <i class="fas fa-check-circle"></i> Accepted
                    <span class="count"><?php echo count(array_filter($groupedNegotiations, function($n) { return $n['status'] === 'accepted'; })); ?></span>
                </button>
                <button class="tab-btn" data-tab="rejected">
                    <i class="fas fa-times-circle"></i> Rejected
                    <span class="count"><?php echo count(array_filter($groupedNegotiations, function($n) { return $n['status'] === 'rejected'; })); ?></span>
                </button>
            </div>
            
            <div class="tab-content active" id="all-tab">
                <?php if (count($groupedNegotiations) > 0): ?>
                    <div class="messages-list">
                        <?php foreach ($groupedNegotiations as $negotiation): ?>
                            <div class="message-card">
                                <div class="message-header">
                                    <h3>
                                        <i class="fas fa-comments-dollar"></i>
                                        Negotiation for <?php echo htmlspecialchars($negotiation['property_title']); ?>
                                    </h3>
                                    <div class="message-date">
                                        <?php echo date('F j, Y', strtotime($negotiation['proposed_date'])); ?>
                                    </div>
                                </div>
                                <div class="message-content">
                                    <div class="message-property">
                                        <div class="property-image">
                                            <?php 
                                                $imgPath = isset($propertyImages[$negotiation['id_property']]) 
                                                    ? $propertyImages[$negotiation['id_property']] 
                                                    : '/placeholder.svg?height=500&width=800';
                                            ?>
                                            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($negotiation['property_title']); ?>">
                                        </div>
                                        <div class="property-details">
                                            <h3 class="property-title"><?php echo htmlspecialchars($negotiation['property_title']); ?></h3>
                                            <div class="property-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($negotiation['address'] . ', ' . $negotiation['city']); ?>
                                            </div>
                                            <div class="property-meta">
                                                <span><i class="fas fa-bed"></i> <?php echo $negotiation['bedrooms']; ?> Beds</span>
                                                <span><i class="fas fa-bath"></i> <?php echo $negotiation['bathrooms']; ?> Baths</span>
                                                <span><i class="fas fa-ruler-combined"></i> <?php echo $negotiation['size']; ?> m²</span>
                                            </div>
                                            <div class="property-price">
                                                <?php echo number_format($negotiation['property_price'], 0, '.', ','); ?> DZD
                                                <?php if ($negotiation['owner_needs'] == 'renting'): ?>
                                                    <span>/month</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="negotiation-details">
                                        <h3 class="negotiation-title">Negotiation Details</h3>
                                        
                                        <div class="price-comparison">
                                            <div class="original-price">
                                                <span>Original Price</span>
                                                <strong><?php echo number_format($negotiation['property_price'], 0, '.', ','); ?> DZD</strong>
                                            </div>
                                            <i class="fas fa-arrow-right"></i>
                                            <div class="offered-price">
                                                <span>Your Offer</span>
                                                <strong><?php echo number_format($negotiation['proposed_price'], 0, '.', ','); ?> DZD</strong>
                                                <?php 
                                                    $percentDiff = round(($negotiation['proposed_price'] - $negotiation['property_price']) / $negotiation['property_price'] * 100);
                                                    $percentClass = $percentDiff < 0 ? 'discount' : 'increase';
                                                ?>
                                                <span class="percent-diff <?php echo $percentClass; ?>">
                                                    <?php echo $percentDiff; ?>%
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <?php 
                                            $statusClass = '';
                                            $statusText = '';
                                            
                                            if ($negotiation['status'] === 'pending') {
                                                $statusClass = 'pending';
                                                $statusText = 'Pending Response';
                                            } elseif ($negotiation['status'] === 'accepted') {
                                                $statusClass = 'accepted';
                                                $statusText = 'Offer Accepted';
                                            } elseif ($negotiation['status'] === 'rejected') {
                                                $statusClass = 'rejected';
                                                $statusText = 'Offer Rejected';
                                            }
                                        ?>
                                        
                                        <div class="negotiation-status <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </div>
                                        
                                        <?php if (!empty($negotiation['comments'])): ?>
                                            <div class="client-message">
                                                <strong>Your message:</strong>
                                                <p><?php echo nl2br(htmlspecialchars($negotiation['comments'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="messages-section">
                                        <h3 class="messages-title">
                                            <i class="fas fa-comments"></i>
                                            Conversation with Owner
                                        </h3>
                                        
                                        <?php if (count($negotiation['messages']) > 0): ?>
                                            <div class="message-thread">
                                                <?php foreach ($negotiation['messages'] as $message): ?>
                                                    <div class="message-bubble owner">
                                                        <div class="message-text">
                                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                                        </div>
                                                        <div class="message-meta">
                                                            <div class="message-sender">Owner</div>
                                                            <div class="message-time">
                                                                <?php echo date('M d, g:i a', strtotime($message['sent_at'])); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <form class="reply-form" action="messages.php" method="POST">
                                                <input type="hidden" name="negotiation_id" value="<?php echo $negotiation['negotiation_id']; ?>">
                                                <textarea class="reply-input" name="message_content" placeholder="Type your reply here..." required></textarea>
                                                <button type="submit" name="send_message" class="send-btn">
                                                    <i class="fas fa-paper-plane"></i> Send
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <p>No messages yet. The owner has not responded to your offer.</p>
                                            
                                            <?php if ($negotiation['status'] === 'pending'): ?>
                                                <form class="reply-form" action="messages.php" method="POST">
                                                    <input type="hidden" name="negotiation_id" value="<?php echo $negotiation['negotiation_id']; ?>">
                                                    <textarea class="reply-input" name="message_content" placeholder="Send a message to the owner..." required></textarea>
                                                    <button type="submit" name="send_message" class="send-btn">
                                                        <i class="fas fa-paper-plane"></i> Send
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <div class="message-actions">
                                            <a href="property.php?id=<?php echo $negotiation['id_property']; ?>" class="btn view-btn">
                                                <i class="fas fa-eye"></i> View Property
                                            </a>
                                            <?php if ($negotiation['status'] === 'accepted'): ?>
                                                <a href="booking.php?property_id=<?php echo $negotiation['id_property']; ?>" class="btn primary-btn">
                                                    <i class="fas fa-calendar-check"></i> Book Now
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-messages">
                        <i class="fas fa-comments"></i>
                        <p>You don't have any messages yet. Start by negotiating on a property you're interested in.</p>
                        <a href="explore.php" class="btn primary-btn">
                            <i class="fas fa-search"></i> Explore Properties
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="pending-tab">
                <?php 
                $pendingNegotiations = array_filter($groupedNegotiations, function($n) { 
                    return $n['status'] === 'pending'; 
                });
                
                if (count($pendingNegotiations) > 0): 
                ?>
                    <div class="messages-list">
                        <?php foreach ($pendingNegotiations as $negotiation): ?>
                            <div class="message-card">
                                <!-- Same content structure as in the "all" tab -->
                                <div class="message-header">
                                    <h3>
                                        <i class="fas fa-comments-dollar"></i>
                                        Negotiation for <?php echo htmlspecialchars($negotiation['property_title']); ?>
                                    </h3>
                                    <div class="message-date">
                                        <?php echo date('F j, Y', strtotime($negotiation['proposed_date'])); ?>
                                    </div>
                                </div>
                                <!-- Rest of the content is the same as in the "all" tab -->
                                <div class="message-content">
                                    <!-- Property details section -->
                                    <div class="message-property">
                                        <!-- Same as in "all" tab -->
                                        <div class="property-image">
                                            <?php 
                                                $imgPath = isset($propertyImages[$negotiation['id_property']]) 
                                                    ? $propertyImages[$negotiation['id_property']] 
                                                    : '/placeholder.svg?height=500&width=800';
                                            ?>
                                            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($negotiation['property_title']); ?>">
                                        </div>
                                        <div class="property-details">
                                            <!-- Same as in "all" tab -->
                                            <h3 class="property-title"><?php echo htmlspecialchars($negotiation['property_title']); ?></h3>
                                            <div class="property-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($negotiation['address'] . ', ' . $negotiation['city']); ?>
                                            </div>
                                            <div class="property-meta">
                                                <span><i class="fas fa-bed"></i> <?php echo $negotiation['bedrooms']; ?> Beds</span>
                                                <span><i class="fas fa-bath"></i> <?php echo $negotiation['bathrooms']; ?> Baths</span>
                                                <span><i class="fas fa-ruler-combined"></i> <?php echo $negotiation['size']; ?> m²</span>
                                            </div>
                                            <div class="property-price">
                                                <?php echo number_format($negotiation['property_price'], 0, '.', ','); ?> DZD
                                                <?php if ($negotiation['owner_needs'] == 'renting'): ?>
                                                    <span>/month</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Negotiation details section -->
                                    <div class="negotiation-details">
                                        <!-- Same as in "all" tab -->
                                        <h3 class="negotiation-title">Negotiation Details</h3>
                                        
                                        <div class="price-comparison">
                                            <div class="original-price">
                                                <span>Original Price</span>
                                                <strong><?php echo number_format($negotiation['property_price'], 0, '.', ','); ?> DZD</strong>
                                            </div>
                                            <i class="fas fa-arrow-right"></i>
                                            <div class="offered-price">
                                                <span>Your Offer</span>
                                                <strong><?php echo number_format($negotiation['proposed_price'], 0, '.', ','); ?> DZD</strong>
                                                <?php 
                                                    $percentDiff = round(($negotiation['proposed_price'] - $negotiation['property_price']) / $negotiation['property_price'] * 100);
                                                    $percentClass = $percentDiff < 0 ? 'discount' : 'increase';
                                                ?>
                                                <span class="percent-diff <?php echo $percentClass; ?>">
                                                    <?php echo $percentDiff; ?>%
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="negotiation-status pending">
                                            Pending Response
                                        </div>
                                        
                                        <?php if (!empty($negotiation['comments'])): ?>
                                            <div class="client-message">
                                                <strong>Your message:</strong>
                                                <p><?php echo nl2br(htmlspecialchars($negotiation['comments'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Messages section -->
                                    <div class="messages-section">
                                        <!-- Same as in "all" tab -->
                                        <h3 class="messages-title">
                                            <i class="fas fa-comments"></i>
                                            Conversation with Owner
                                        </h3>
                                        
                                        <?php if (count($negotiation['messages']) > 0): ?>
                                            <div class="message-thread">
                                                <?php foreach ($negotiation['messages'] as $message): ?>
                                                    <div class="message-bubble owner">
                                                        <div class="message-text">
                                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                                        </div>
                                                        <div class="message-meta">
                                                            <div class="message-sender">Owner</div>
                                                            <div class="message-time">
                                                                <?php echo date('M d, g:i a', strtotime($message['sent_at'])); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <form class="reply-form" action="messages.php" method="POST">
                                                <input type="hidden" name="negotiation_id" value="<?php echo $negotiation['negotiation_id']; ?>">
                                                <textarea class="reply-input" name="message_content" placeholder="Type your reply here..." required></textarea>
                                                <button type="submit" name="send_message" class="send-btn">
                                                    <i class="fas fa-paper-plane"></i> Send
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <p>No messages yet. The owner has not responded to your offer.</p>
                                            
                                            <form class="reply-form" action="messages.php" method="POST">
                                                <input type="hidden" name="negotiation_id" value="<?php echo $negotiation['negotiation_id']; ?>">
                                                <textarea class="reply-input" name="message_content" placeholder="Send a message to the owner..." required></textarea>
                                                <button type="submit" name="send_message" class="send-btn">
                                                    <i class="fas fa-paper-plane"></i> Send
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <div class="message-actions">
                                            <a href="property.php?id=<?php echo $negotiation['id_property']; ?>" class="btn view-btn">
                                                <i class="fas fa-eye"></i> View Property
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-messages">
                        <i class="fas fa-clock"></i>
                        <p>You don't have any pending negotiations.</p>
                        <a href="explore.php" class="btn primary-btn">
                            <i class="fas fa-search"></i> Explore Properties
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="accepted-tab">
                <?php 
                $acceptedNegotiations = array_filter($groupedNegotiations, function($n) { 
                    return $n['status'] === 'accepted'; 
                });
                
                if (count($acceptedNegotiations) > 0): 
                ?>
                    <div class="messages-list">
                        <?php foreach ($acceptedNegotiations as $negotiation): ?>
                            <!-- Similar structure to "all" tab but with accepted status -->
                            <div class="message-card">
                                <!-- Content similar to "all" tab but with accepted status -->
                                <div class="message-header">
                                    <h3>
                                        <i class="fas fa-check-circle"></i>
                                        Accepted Offer for <?php echo htmlspecialchars($negotiation['property_title']); ?>
                                    </h3>
                                    <div class="message-date">
                                        <?php echo date('F j, Y', strtotime($negotiation['proposed_date'])); ?>
                                    </div>
                                </div>
                                <!-- Rest of content similar to "all" tab -->
                                <div class="message-content">
                                    <!-- Property details section -->
                                    <div class="message-property">
                                        <div class="property-image">
                                            <?php 
                                                $imgPath = isset($propertyImages[$negotiation['id_property']]) 
                                                    ? $propertyImages[$negotiation['id_property']] 
                                                    : '/placeholder.svg?height=500&width=800';
                                            ?>
                                            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($negotiation['property_title']); ?>">
                                        </div>
                                        <div class="property-details">
                                            <h3 class="property-title"><?php echo htmlspecialchars($negotiation['property_title']); ?></h3>
                                            <div class="property-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($negotiation['address'] . ', ' . $negotiation['city']); ?>
                                            </div>
                                            <div class="property-meta">
                                                <span><i class="fas fa-bed"></i> <?php echo $negotiation['bedrooms']; ?> Beds</span>
                                                <span><i class="fas fa-bath"></i> <?php echo $negotiation['bathrooms']; ?> Baths</span>
                                                <span><i class="fas fa-ruler-combined"></i> <?php echo $negotiation['size']; ?> m²</span>
                                            </div>
                                            <div class="property-price">
                                                <?php echo number_format($negotiation['property_price'], 0, '.', ','); ?> DZD
                                                <?php if ($negotiation['owner_needs'] == 'renting'): ?>
                                                    <span>/month</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Negotiation details section -->
                                    <div class="negotiation-details">
                                        <h3 class="negotiation-title">Negotiation Details</h3>
                                        
                                        <div class="price-comparison">
                                            <div class="original-price">
                                                <span>Original Price</span>
                                                <strong><?php echo number_format($negotiation['property_price'], 0, '.', ','); ?> DZD</strong>
                                            </div>
                                            <i class="fas fa-arrow-right"></i>
                                            <div class="offered-price">
                                                <span>Your Offer (Accepted)</span>
                                                <strong><?php echo number_format($negotiation['proposed_price'], 0, '.', ','); ?> DZD</strong>
                                                <?php 
                                                    $percentDiff = round(($negotiation['proposed_price'] - $negotiation['property_price']) / $negotiation['property_price'] * 100);
                                                    $percentClass = $percentDiff < 0 ? 'discount' : 'increase';
                                                ?>
                                                <span class="percent-diff <?php echo $percentClass; ?>">
                                                    <?php echo $percentDiff; ?>%
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="negotiation-status accepted">
                                            Offer Accepted
                                        </div>
                                        
                                        <?php if (!empty($negotiation['comments'])): ?>
                                            <div class="client-message">
                                                <strong>Your message:</strong>
                                                <p><?php echo nl2br(htmlspecialchars($negotiation['comments'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Messages section -->
                                    <div class="messages-section">
                                        <h3 class="messages-title">
                                            <i class="fas fa-comments"></i>
                                            Conversation with Owner
                                        </h3>
                                        
                                        <?php if (count($negotiation['messages']) > 0): ?>
                                            <div class="message-thread">
                                                <?php foreach ($negotiation['messages'] as $message): ?>
                                                    <div class="message-bubble owner">
                                                        <div class="message-text">
                                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                                        </div>
                                                        <div class="message-meta">
                                                            <div class="message-sender">Owner</div>
                                                            <div class="message-time">
                                                                <?php echo date('M d, g:i a', strtotime($message['sent_at'])); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <form class="reply-form" action="messages.php" method="POST">
                                                <input type="hidden" name="negotiation_id" value="<?php echo $negotiation['negotiation_id']; ?>">
                                                <textarea class="reply-input" name="message_content" placeholder="Type your reply here..." required></textarea>
                                                <button type="submit" name="send_message" class="send-btn">
                                                    <i class="fas fa-paper-plane"></i> Send
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <p>No messages yet from the owner.</p>
                                            
                                            <form class="reply-form" action="messages.php" method="POST">
                                                <input type="hidden" name="negotiation_id" value="<?php echo $negotiation['negotiation_id']; ?>">
                                                <textarea class="reply-input" name="message_content" placeholder="Send a message to the owner..." required></textarea>
                                                <button type="submit" name="send_message" class="send-btn">
                                                    <i class="fas fa-paper-plane"></i> Send
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <div class="message-actions">
                                            <a href="property.php?id=<?php echo $negotiation['id_property']; ?>" class="btn view-btn">
                                                <i class="fas fa-eye"></i> View Property
                                            </a>
                                            <a href="booking.php?property_id=<?php echo $negotiation['id_property']; ?>" class="btn primary-btn">
                                                <i class="fas fa-calendar-check"></i> Book Now
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-messages">
                        <i class="fas fa-check-circle"></i>
                        <p>You don't have any accepted negotiations yet.</p>
                        <a href="explore.php" class="btn primary-btn">
                            <i class="fas fa-search"></i> Explore Properties
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="rejected-tab">
                <?php 
                $rejectedNegotiations = array_filter($groupedNegotiations, function($n) { 
                    return $n['status'] === 'rejected'; 
                });
                
                if (count($rejectedNegotiations) > 0): 
                ?>
                    <div class="messages-list">
                        <?php foreach ($rejectedNegotiations as $negotiation): ?>
                            <!-- Similar structure to "all" tab but with rejected status -->
                            <div class="message-card">
                                <div class="message-header">
                                    <h3>
                                        <i class="fas fa-times-circle"></i>
                                        Rejected Offer for <?php echo htmlspecialchars($negotiation['property_title']); ?>
                                    </h3>
                                    <div class="message-date">
                                        <?php echo date('F j, Y', strtotime($negotiation['proposed_date'])); ?>
                                    </div>
                                </div>
                                <!-- Rest of content similar to "all" tab -->
                                <div class="message-content">
                                    <!-- Property details section -->
                                    <div class="message-property">
                                        <div class="property-image">
                                            <?php 
                                                $imgPath = isset($propertyImages[$negotiation['id_property']]) 
                                                    ? $propertyImages[$negotiation['id_property']] 
                                                    : '/placeholder.svg?height=500&width=800';
                                            ?>
                                            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($negotiation['property_title']); ?>">
                                        </div>
                                        <div class="property-details">
                                            <h3 class="property-title"><?php echo htmlspecialchars($negotiation['property_title']); ?></h3>
                                            <div class="property-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($negotiation['address'] . ', ' . $negotiation['city']); ?>
                                            </div>
                                            <div class="property-meta">
                                                <span><i class="fas fa-bed"></i> <?php echo $negotiation['bedrooms']; ?> Beds</span>
                                                <span><i class="fas fa-bath"></i> <?php echo $negotiation['bathrooms']; ?> Baths</span>
                                                <span><i class="fas fa-ruler-combined"></i> <?php echo $negotiation['size']; ?> m²</span>
                                            </div>
                                            <div class="property-price">
                                                <?php echo number_format($negotiation['property_price'], 0, '.', ','); ?> DZD
                                                <?php if ($negotiation['owner_needs'] == 'renting'): ?>
                                                    <span>/month</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Negotiation details section -->
                                    <div class="negotiation-details">
                                        <h3 class="negotiation-title">Negotiation Details</h3>
                                        
                                        <div class="price-comparison">
                                            <div class="original-price">
                                                <span>Original Price</span>
                                                <strong><?php echo number_format($negotiation['property_price'], 0, '.', ','); ?> DZD</strong>
                                            </div>
                                            <i class="fas fa-arrow-right"></i>
                                            <div class="offered-price">
                                                <span>Your Offer (Rejected)</span>
                                                <strong><?php echo number_format($negotiation['proposed_price'], 0, '.', ','); ?> DZD</strong>
                                                <?php 
                                                    $percentDiff = round(($negotiation['proposed_price'] - $negotiation['property_price']) / $negotiation['property_price'] * 100);
                                                    $percentClass = $percentDiff < 0 ? 'discount' : 'increase';
                                                ?>
                                                <span class="percent-diff <?php echo $percentClass; ?>">
                                                    <?php echo $percentDiff; ?>%
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="negotiation-status rejected">
                                            Offer Rejected
                                        </div>
                                        
                                        <?php if (!empty($negotiation['comments'])): ?>
                                            <div class="client-message">
                                                <strong>Your message:</strong>
                                                <p><?php echo nl2br(htmlspecialchars($negotiation['comments'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Messages section -->
                                    <div class="messages-section">
                                        <h3 class="messages-title">
                                            <i class="fas fa-comments"></i>
                                            Conversation with Owner
                                        </h3>
                                        
                                        <?php if (count($negotiation['messages']) > 0): ?>
                                            <div class="message-thread">
                                                <?php foreach ($negotiation['messages'] as $message): ?>
                                                    <div class="message-bubble owner">
                                                        <div class="message-text">
                                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                                        </div>
                                                        <div class="message-meta">
                                                            <div class="message-sender">Owner</div>
                                                            <div class="message-time">
                                                                <?php echo date('M d, g:i a', strtotime($message['sent_at'])); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <form class="reply-form" action="messages.php" method="POST">
                                                <input type="hidden" name="negotiation_id" value="<?php echo $negotiation['negotiation_id']; ?>">
                                                <textarea class="reply-input" name="message_content" placeholder="Type your reply here..." required></textarea>
                                                <button type="submit" name="send_message" class="send-btn">
                                                    <i class="fas fa-paper-plane"></i> Send
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <p>No messages yet from the owner.</p>
                                            
                                            <form class="reply-form" action="messages.php" method="POST">
                                                <input type="hidden" name="negotiation_id" value="<?php echo $negotiation['negotiation_id']; ?>">
                                                <textarea class="reply-input" name="message_content" placeholder="Send a message to the owner..." required></textarea>
                                                <button type="submit" name="send_message" class="send-btn">
                                                    <i class="fas fa-paper-plane"></i> Send
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <div class="message-actions">
                                            <a href="property.php?id=<?php echo $negotiation['id_property']; ?>" class="btn view-btn">
                                                <i class="fas fa-eye"></i> View Property
                                            </a>
                                            <a href="explore.php" class="btn primary-btn">
                                                <i class="fas fa-search"></i> Find Similar Properties
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-messages">
                        <i class="fas fa-times-circle"></i>
                        <p>You don't have any rejected negotiations.</p>
                        <a href="explore.php" class="btn primary-btn">
                            <i class="fas fa-search"></i> Explore Properties
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle functionality
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            function checkWidth() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('expanded');
                } else {
                    sidebar.classList.remove('collapsed');
                }
            }
            
            checkWidth();
            
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.toggle('expanded');
                    sidebarOverlay.classList.toggle('active');
                } else {
                    sidebar.classList.toggle('collapsed');
                }
            });
            
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('expanded');
                sidebarOverlay.classList.remove('active');
            });
            
            window.addEventListener('resize', checkWidth);
            
            // Tab functionality
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to current tab
                    this.classList.add('active');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });
            
            // Auto-resize textarea
            const replyInputs = document.querySelectorAll('.reply-input');
            replyInputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            });
        });
    </script>
</body>
</html>
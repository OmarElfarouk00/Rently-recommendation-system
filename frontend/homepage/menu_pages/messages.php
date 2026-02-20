<?php
session_start();
require_once '../php files/config.php';

// Check if client is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login-signup/index.php');
    exit();
}
$isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in

$userId = $_SESSION['user_id'];
$senderRole = isset($_POST['sender_role']) ? $_POST['sender_role'] : '';
$i = 0;

// try {
//     $stmt = $pdo->prepare("
//         SELECT id_client, content, type, related_id, is_read,timestamp
//         FROM Notifications
//         WHERE related_id = ? 
//         ORDER BY timestamp DESC
//     ");
//     $stmt->execute([$userId]);
//     $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// } catch (PDOException $e) {
//     echo "Error fetching notifications: " . $e->getMessage();
//     exit();
// }


try {
    // Messages from owner for pending negotiations
    $stmt = $pdo->prepare("
        SELECT
            n.id_client,
            n.id_property,
            n.proposedPrice,
            n.status,
            n.proposedDate,
            p.title AS property_title,
            p.estimatePrice AS property_price,
            r.message,
            r.sent_at,
            r.priceOffer,
            r.sender_role,
            r.id_negotiation
        FROM Response r
        JOIN Negotiation n ON r.id_negotiation = n.id_negotiation
        JOIN Property p ON n.id_property = p.id_property
        WHERE n.id_client = ?
          AND r.sender_role = 'owner'
          AND n.status = 'pending'
        ORDER BY r.sent_at DESC
    ");
    $stmt->execute([$userId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rejected Negotiations and Rentals
    $stmt = $pdo->prepare("
SELECT
    'Negotiation' AS source,
    n.id_negotiation AS record_id,
    n.endDate,
    p.title AS property_title,
    p.id_property,
    n.proposedDate AS date,
    COALESCE(n.proposedPrice, p.estimatePrice) AS price,
    n.status AS status,
    (SELECT rsp.message 
        FROM Response rsp 
        WHERE rsp.id_negotiation = n.id_negotiation 
        ORDER BY rsp.sent_at DESC 
        LIMIT 1) AS message
FROM Negotiation n
JOIN Property p ON n.id_property = p.id_property
WHERE n.id_client = ?
AND n.status = 'rejected'

UNION ALL

SELECT
    'Rental' AS source,
    CONCAT(r.id_property, '-', r.id_client, '-', DATE_FORMAT(r.startDate, '%Y%m%d')) AS record_id,
    r.endDate,
    p.title AS property_title,
    p.id_property,
    r.startDate AS date,
    COALESCE(
        (SELECT rsp.priceOffer 
        FROM Response rsp 
        JOIN Negotiation neg ON rsp.id_negotiation = neg.id_negotiation
        WHERE neg.id_property = r.id_property 
        AND neg.id_client = r.id_client
        ORDER BY rsp.sent_at DESC 
        LIMIT 1),
        p.estimatePrice) AS price,
    'rejected' AS status,
    (SELECT rsp.message 
    FROM Response rsp 
    JOIN Negotiation neg ON rsp.id_negotiation = neg.id_negotiation
    WHERE neg.id_property = r.id_property 
    AND neg.id_client = r.id_client
    ORDER BY rsp.sent_at DESC 
    LIMIT 1) AS message
FROM Rental r
JOIN Property p ON r.id_property = p.id_property
WHERE r.id_client = ?
AND r.status = 2
ORDER BY date DESC    ");
    $stmt->execute([$userId, $userId]);
    $rejections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Accepted by client
    $stmt = $pdo->prepare("
        SELECT
            n.proposedPrice,
            n.proposedDate,
            n.endDate,
            p.title AS property_title,
            p.estimatePrice AS property_price,
            p.id_property,
            r.message,
            r.sent_at,
            r.priceOffer,
            r.sender_role,
            v.finalPrice,
            v.validationDate,
            v.terms
        FROM Negotiation n
        JOIN Property p ON n.id_property = p.id_property
        JOIN ValidateNegotiation v ON v.id_negotiation = n.id_negotiation
        LEFT JOIN Response r 
            ON r.id_negotiation = n.id_negotiation
           AND r.sent_at = (
               SELECT MAX(r2.sent_at)
               FROM Response r2
               WHERE r2.id_negotiation = n.id_negotiation
           )
        WHERE n.id_client = ?
          AND v.accepted_by = 'client'
        ORDER BY v.validationDate DESC
    ");
    $stmt->execute([$userId]);
    $acceptedByClient = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Accepted by owner
    $stmt = $pdo->prepare("
        SELECT
            n.proposedPrice,
            n.proposedDate,
            n.endDate,
            p.title AS property_title,
            p.estimatePrice AS property_price,
            p.id_property,
            r.message,
            r.sent_at,
            r.priceOffer,
            r.sender_role,
            v.finalPrice,
            v.validationDate,
            v.terms
        FROM Negotiation n
        JOIN Property p ON n.id_property = p.id_property
        JOIN ValidateNegotiation v ON v.id_negotiation = n.id_negotiation
        LEFT JOIN Response r 
            ON r.id_negotiation = n.id_negotiation
           AND r.sent_at = (
               SELECT MAX(r2.sent_at)
               FROM Response r2
               WHERE r2.id_negotiation = n.id_negotiation
           )
        WHERE n.id_client = ?
          AND v.accepted_by = 'owner'
        ORDER BY v.validationDate DESC
    ");
    $stmt->execute([$userId]);
    $acceptedByOwner = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Failed to fetch messages: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while retrieving your messages.";
    echo "DB Error: " . $e->getMessage();
    exit();
}


if (isset($_POST['respond_negotiation'])) {
    $negotiationId = isset($_POST['negotiation_id']) ? intval($_POST['negotiation_id']) : 0;
    $idClient = isset($_POST['id_client']) ? intval($_POST['id_client']) : 0;
    $idProperty = isset($_POST['id_property']) ? intval($_POST['id_property']) : 0;
    $proposedDate = isset($_POST['proposedDate']) ? $_POST['proposedDate'] : '';
    $response = $_POST['response'];
    $senderRole = $_POST['sender_role'];
    $priceOffer = isset($_POST['priceOffer']) ? intval($_POST['priceOffer']) : 0;

    // Validate input
    if ($idClient <= 0 || $idProperty <= 0) {
        $errorMessage = "Invalid negotiation identifiers.";
        return;
    }

    // Select the start date and duration from the Negotiation table
    $stmt = $pdo->prepare("
        SELECT proposedDate, endDate 
        FROM Negotiation
        WHERE id_client = ? AND id_property = ? AND proposedDate = ?
    ");
    $stmt->execute([$idClient, $idProperty, $proposedDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        // $errorMessage = "Negotiation not found.";
        $_SESSION['error_message'] = "Negotiation not found.";
        $i = 1;
        return;
    }

    // $startDate = $result['proposedDate'];
    $endDate = $result['endDate'];

    try {
        if ($response === 'accept') {
                // Check if property is already booked for the requested period
    $stmt = $pdo->prepare("
    SELECT COUNT(*) FROM Rental 
    WHERE id_property = ? 
      AND status = true
      AND (
            (startDate <= ? AND endDate >= ?) -- Overlapping date range
      )
        ");
    $stmt->execute([$idProperty, $endDate, $proposedDate]);
    $isBooked = $stmt->fetchColumn() > 0;

    if ($isBooked) {
        // echo json_encode(['success' => false, 'message' => 'Property is already booked for the selected dates']);
        $_SESSION['error_message'] = "Property is already booked for the selected dates";
        header("locator: messages.php");
        // exit();
    }else{
            // Insert into ValidateNegotiation using data from the latest Response and its related Negotiation
            $stmt = $pdo->prepare("
                INSERT INTO ValidateNegotiation (id_negotiation, validationDate, finalPrice, terms, id_client, id_property, proposedDate, accepted_by)
                VALUES (? , CURDATE(), ?, 'standard contract', ?, ?, ?, 'client')
            ");

            $stmt->execute([$negotiationId, $priceOffer, $idClient, $idProperty, $proposedDate]);



            // Insert into Rental table
            $stmt = $pdo->prepare("
                INSERT INTO Rental (id_client, id_property, startDate, endDate, status, contractTerms)
                VALUES (?, ?, ?, ?, '1', 'standard contract')
            ");
            $stmt->execute([$idClient, $idProperty, $proposedDate, $endDate]);

            // Update Negotiation status
            $stmt = $pdo->prepare("
                UPDATE Negotiation 
                SET status = 'accepted'
                WHERE id_client = ? AND id_property = ? AND proposedDate = ?
            ");
            $stmt->execute([$idClient, $idProperty, $proposedDate]);

            // update property status table 
            $stmt = $pdo->prepare("
                UPDATE Property 
                SET status = 'rented'
                WHERE id_property = ?
            ");
            $stmt->execute([$idProperty]);

            // $successMessage = "Negotiation accepted successfully";
            $_SESSION['success_message'] = "Negotiation accepted successfully";
            $i = 1;
            header("Location: messages.php");
        }
    } else {
            // Reject the negotiation
            $stmt = $pdo->prepare("
                UPDATE Negotiation 
                SET status = 'rejected'
                WHERE id_negotiation = ?
            ");
            $stmt->execute([$negotiationId]);

            // $successMessage = "Negotiation rejected";
            $_SESSION['success_message'] = "Negotiation rejected.";
            $i = 1;
            header("Location: messages.php");
        }
    } catch (PDOException $e) {
        $errorMessage = "Error responding to negotiation: " . $e->getMessage();
    }
}


?>
<!-- if ($response === 'acceptClient') {
            $stmt = $pdo->prepare("
                INSERT INTO Rental (id_client, id_property, startDate, duration, status, contractTerms)
                VALUES (?, ?, ?, ?, '1', 'standard contract')
            ");
            $stmt->execute([$idClient, $idProperty, $startDate, $duration]);

            $successMessage = "The property has been rented successfully";

        } else -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties | RentEstate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
    <style>
        .view-btn {
            background: var(--light-gray);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            /* position at the left */
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .view-btn:hover {
            background: #e0e0e0;
        }

        /* Notification Styles */
        .notifications-container {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            max-width: 600px;
            margin: 1rem auto;
            color: var(--text-color);
            display: none;
            /* Initially hidden */
            position: fixed;
            top: 60px;
            right: 20px;
            z-index: 1001;
            max-height: 80vh;
            overflow-y: auto;
            width: 350px;
        }

        .notifications-container.active {
            display: block;
        }

        .notifications-container h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notifications-container ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .notifications-container li {
            padding: 15px 20px;
            margin-bottom: 12px;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            font-size: 1rem;
            line-height: 1.4;
            transition: background-color 0.3s ease;
            position: relative;
        }

        .notifications-container li.unread {
            background-color: #dff0d8;
            /* light green */
            font-weight: 600;
        }

        .notifications-container li.read {
            background-color: #f0f0f0;
            /* light gray */
            color: #666;
        }

        .notifications-container li small {
            display: block;
            margin-left: 0%;

        }

        /* My Properties Page Styles */
        .my-properties-container {
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

        .add-property-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .add-property-btn:hover {
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

        /* Dashboard Tabs */
        .dashboard-tabs {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .tab-header {
            border-bottom: 1px solid var(--border-color);
            display: flex;
        }

        .tab-btn {
            flex: 1;
            padding: 1.2rem;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            color: #666;
            display: inline;
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

        /* Properties Tab */
        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .property-card {
            background: var(--light-gray);
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            height: fit-content;
        }

        .property-card:hover {
            transform: translateY(-5px);
        }

        .property-image {
            position: relative;
            height: 200px;
        }

        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .property-status {
            position: absolute;
            top: 1rem;
            left: 1rem;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .property-status.available {
            background: #28a745;
            color: white;
        }

        .property-status.pending {
            background: #ffc107;
            color: #212529;
        }

        .property-status.sold {
            background: #dc3545;
            color: white;
        }

        .property-status.rented {
            background: #17a2b8;
            color: white;
        }

        /* Chat-like message box styling */
        .message-box {
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .property-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }

        .message-box p {
            margin-bottom: 0.8rem;
            padding: 0.8rem;
            border-radius: 8px;
        }

        .message-box p:nth-child(odd) {
            background-color: #f8f9fa;
            align-self: flex-start;
            border-top-left-radius: 0;
        }

        .message-box p:nth-child(even) {
            background-color: #e9ecef;
            align-self: flex-end;
            border-top-right-radius: 0;
        }

        .timestamp {
            font-size: 0.8rem;
            color: #6c757d;
            text-align: right;
            margin-top: 0.5rem;
            font-style: italic;
        }

        .negotiation-actions {
            display: flex;
            gap: 0.5rem;
            padding: 1rem;
            justify-content: space-between;
        }

        .btn {
            padding: 0.4rem 0.5rem;
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

        .accept-btn {
            background: #28a745;
            color: white;
            width: fit-content;
        }

        .accept-btn:hover {
            background: #218838;
        }

        .reject-btn {
            background: #dc3545;
            color: white;
            width: fit-content;
        }

        .reject-btn:hover {
            background: #c82333;
        }

        .counter-btn {
            background: #17a2b8;
            color: white;
            width: fit-content;
        }

        .counter-btn:hover {
            background: #138496;
        }

        /* No Data */
        .no-data {
            text-align: center;
            padding: 3rem 1rem;
        }

        .no-data i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .no-data p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .primary-btn {
            background: var(--primary-color);
            color: white;
        }

        .primary-btn:hover {
            background: #d65b1e;
        }

        /* Popup */
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .popup-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            overflow: hidden;
        }

        .popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .popup-header h3 {
            margin: 0;
            color: var(--text-color);
        }

        .close-popup {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .popup-body {
            padding: 1.5rem;
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

        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
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

        /* Responsive styles */
        @media (max-width: 992px) {
            .my-properties-container {
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
                top: 35px;
            }
        }

        @media (max-width: 768px) {
            .negotiation-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <!-- <div id="menuToggle"> -->



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
                <a href="../../login-signup/index.php" class="menu-item">
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
                        <?php if (isset($favoriteCount) && $favoriteCount > 0): ?>
                            <span class="menu-badge"><?php echo $favoriteCount; ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="messages.php" class="menu-item active">
                        <i class="fas fa-envelope"></i>
                        Messages
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
        </div>
    </header>

    <div class="my-properties-container" id="menuContent">
        <div class="dashboard-header">
            <h1>My messages Dashboard</h1>
        </div>

        <div class="notifications-container">
            <h3>Your Notifications</h3>
            <?php if (empty($notifications)): ?>
                <p>No notifications available.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($notifications as $notification): ?>
                        <li
                            style="background-color: <?= $notification['is_read'] ? '#f0f0f0' : '#dff0d8'; ?>; padding: 10px; margin-bottom: 10px;">
                            <!-- <strong><?= htmlspecialchars($notification['']) ?></strong><br> -->
                            <?= htmlspecialchars($notification['content']) ?><br>
                            <small><?= htmlspecialchars($notification['timestamp']) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="alert-container">
            <div class="alerts">
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $_SESSION['error_message'];
                        if ($i == 0):
                            unset($_SESSION['error_message']);
                        endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert success">
                        <i class="fas fa-check-circle"></i>
                        <?php
                        echo $_SESSION['success_message'];
                        if ($i == 0):
                            unset($_SESSION['success_message']);
                        endif; ?>

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
                        <button class="tab-btn active" data-tab="messages" id="menuToggle">
                            <i class="fas fa-envelope"></i> All Messages
                            <span class="count"><?php echo count($messages); ?></span>
                        </button>
                        <button class="tab-btn" data-tab="rejection" id="menuToggle">
                            <i class="fas fa-times"></i> rejected
                            <span class="count"><?php echo count($rejections); ?></span>
                        </button>
                        <button class="tab-btn" data-tab="owner-accept">
                            <i class="fas fa-calendar-check"></i> accepted by me
                            <span class="count"><?php echo count($acceptedByClient); ?></span>
                        </button>
                        <button class="tab-btn" data-tab="client-accept">
                            <i class="fas fa-comments-dollar"></i> accepted by the owner
                            <span class="count"><?php echo count($acceptedByOwner); ?></span>
                        </button>
                    </div>

                    <div class="tab-content active" id="messages-tab">
                        <?php if (count($messages) > 0): ?>
                            <div class="properties-grid">
                                <?php foreach ($messages as $message): ?>
                                    <div class="property-card">
                                        <div class="property-image">
                                            <?php
                                            // retrieve the property id from the message table using id negotiation
                                            $propertyId = $message['id_property'];
                                            // retrieve the image path for the property
                                            try {
                                                $stmt = $pdo->prepare("
                                            SELECT image_path 
                                            FROM property_images 
                                            WHERE property_id = ? 
                                            ORDER BY image_order ASC 
                                            LIMIT 1
                                        ");
                                                $stmt->execute([$propertyId]);
                                                $image = $stmt->fetch(PDO::FETCH_ASSOC);

                                                $imgPath = !empty($image['image_path'])
                                                    ? '../php files/' . htmlspecialchars($image['image_path'])
                                                    : '/placeholder.svg?height=500&width=800';
                                            } catch (PDOException $e) {
                                                $imgPath = '/placeholder.svg?height=500&width=800';
                                            }
                                            ?>
                                            <img src="<?php echo $imgPath; ?>"
                                                alt="<?php echo htmlspecialchars($message['property_title']); ?>">
                                        </div>
                                        <div class="message-box">
                                            <?php if (isset($message['proposedPrice'])): ?>
                                                <div class="property-title"><?= htmlspecialchars($message['property_title']) ?>
                                                </div>
                                                <p><strong>Your Offer:</strong> <?= number_format($message['proposedPrice']) ?> DA
                                                </p>
                                                <p><strong>Final's Offer:</strong> <?= number_format($message['priceOffer']) ?> DA
                                                </p>
                                                <p><strong>Start at:</strong> <?= date('F j, Y', strtotime($message['proposedDate'])) ?> DA
                                                </p>
                                            <?php endif; ?>

                                            <p><strong>Owner's Message:</strong> <?= htmlspecialchars($message['message']) ?>
                                            </p>

                                            <div class="timestamp">
                                                <?= $message['sent_at'] ? "Sent at: " . htmlspecialchars($message['sent_at']) : "No response yet" ?>
                                            </div>
                                        </div>

                                        <div class="negotiation-actions">
                                            <?php if (isset($message['proposedPrice'])): ?>
                                                <!-- Accept Form -->
                                                <form action="messages.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="id_client"
                                                        value="<?= htmlspecialchars($message['id_client']) ?>">
                                                    <input type="hidden" name="id_property"
                                                        value="<?= htmlspecialchars($message['id_property']) ?>">
                                                    <input type="hidden" name="proposedDate"
                                                        value="<?= htmlspecialchars($message['proposedDate']) ?>">
                                                    <input type="hidden" name="priceOffer" value="<?= $message['priceOffer'] ?>">
                                                    <input type="hidden" name="negotiation_id"
                                                        value="<?php echo $message['id_negotiation']; ?>"> <input type="hidden"
                                                        name="response" value="accept">
                                                    <input type="hidden" name="sender_role" value="client">
                                                    <button type="submit" name="respond_negotiation" class="btn accept-btn">
                                                        <i class="fas fa-check"></i> Accept
                                                    </button>
                                                </form>

                                                <!-- Reject Form -->
                                                <form action="messages.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="id_client"
                                                        value="<?= htmlspecialchars($message['id_client']) ?>">
                                                    <input type="hidden" name="id_property"
                                                        value="<?= htmlspecialchars($message['id_property']) ?>">
                                                    <input type="hidden" name="proposedDate"
                                                        value="<?= htmlspecialchars($message['proposedDate']) ?>">
                                                    <input type="hidden" name="response" value="reject">
                                                    <input type="hidden" name="sender_role" value="client">
                                                    <input type="hidden" name="negotiation_id"
                                                        value="<?php echo $message['id_negotiation']; ?>">
                                                    <button type="submit" name="respond_negotiation" class="btn reject-btn">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Delete Form -->
                                                <form action="messages.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="id_client"
                                                        value="<?= htmlspecialchars($message['id_client']) ?>">
                                                    <input type="hidden" name="id_property"
                                                        value="<?= htmlspecialchars($message['id_property']) ?>">
                                                    <input type="hidden" name="response" value="reject">
                                                    <input type="hidden" name="sender_role" value="client">
                                                    <input type="hidden" name="proposedDate" value="0000-00-00">
                                                    <input type="hidden" name="negotiation_id"
                                                        value="<?php echo $message['id_negotiation']; ?>">
                                                    <button type="submit" name="respond_negotiation" class="btn reject-btn">
                                                        <i class="fas fa-times"></i> Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="../property.php?id=<?php echo $propertyId; ?>" class="btn view-btn">
                                                <i class="fas fa-eye"></i> View
                                            </a>

                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>You don not have any messages yet.</p>
                                <a href="../index.php" class="btn primary-btn">explore more</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-content" id="rejection-tab">
                        <?php if (count($rejections) > 0): ?>
                            <div class="properties-grid">
                                <?php foreach ($rejections as $rejection): ?>
                                    <div class="property-card">
                                        <div class="property-image">
                                            <?php
                                            // retrieve the property id from the message table using id negotiation
                                            $propertyId = $rejection['id_property'];
                                            // retrieve the image path for the property
                                            try {
                                                $stmt = $pdo->prepare("
                                            SELECT image_path 
                                            FROM property_images 
                                            WHERE property_id = ? 
                                            ORDER BY image_order ASC 
                                            LIMIT 1
                                        ");
                                                $stmt->execute([$propertyId]);
                                                $image = $stmt->fetch(PDO::FETCH_ASSOC);

                                                $imgPath = !empty($image['image_path'])
                                                    ? '../php files/' . htmlspecialchars($image['image_path'])
                                                    : '/placeholder.svg?height=500&width=800';
                                            } catch (PDOException $e) {
                                                $imgPath = '/placeholder.svg?height=500&width=800';
                                            }
                                            ?>
                                            <img src="<?php echo $imgPath; ?>"
                                                alt="<?php echo htmlspecialchars($rejection['property_title']); ?>">

                                        </div>
                                        <div class="message-box">
                                            <div class="property-title"><?= htmlspecialchars($rejection['property_title']) ?>
                                            </div>
                                            <!-- <p><strong>Your Offer:</strong> <?= number_format($rejection['price']) ?> DA</p> -->
                                            <!-- <p><strong>final's Offer:</strong>
                                        <?php
                                        echo isset($rejection['price']) && !empty($rejection['price'])
                                            ? number_format($rejection['price']) . " DA"
                                            : number_format($rejection['price']) . " DA";
                                        ?> -->
                                            <!-- </p> -->
                                            <!-- <p><strong>Owner's Message:</strong>
                                        <?php
                                        echo !empty($rejection['message'])
                                            ? htmlspecialchars($rejection['message'])
                                            : 'No message provided.';
                                        ?>
                                    </p> -->
                                            <p><strong>Start at:</strong> <?php echo isset($rejection['date']) && !empty($rejection['date']) && $rejection['date'] != '0000-00-00'
                                                ? htmlspecialchars($rejection['date']) : 'No date provided.' ?>
                                                <br><br><strong>End at:</strong>
                                                <?php echo isset($rejection['endDate']) && !empty($rejection['endDate']) && $rejection['endDate'] != '0000-00-00'
                                                    ? htmlspecialchars($rejection['endDate']) : 'No date provided.' ?>

                                            </p>
                                            <div class="negotiation-status accepted">
                                                <i class="fas fa-check-circle"></i> rejected
                                            </div>

                                            <div class="timestamp">
                                                <?= isset($rejection['sent_at']) ? "Sent at: " . htmlspecialchars($rejection['sent_at']) : "No response yet" ?>
                                            </div>
                                            <a href="../property.php?id=<?php echo $propertyId; ?>" class="btn view-btn">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>



                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>You don not have any rejections yet</p>
                                <a href="../index.php" class="btn primary-btn">explore more</a>
                            </div>
                        <?php endif; ?>
                    </div>


                    <div class="tab-content" id="owner-accept-tab">
                        <?php if (count($acceptedByClient) > 0): ?>
                            <div class="properties-grid">
                                <?php foreach ($acceptedByClient as $acceptedC): ?>
                                    <div class="property-card">
                                        <div class="property-image">
                                            <?php
                                            // retrieve the property id from the message table using id negotiation
                                            $propertyId = $acceptedC['id_property'];
                                            // retrieve the image path for the property
                                            try {
                                                $stmt = $pdo->prepare("
                                            SELECT image_path 
                                            FROM property_images 
                                            WHERE property_id = ? 
                                            ORDER BY image_order ASC 
                                            LIMIT 1
                                        ");
                                                $stmt->execute([$propertyId]);
                                                $image = $stmt->fetch(PDO::FETCH_ASSOC);

                                                $imgPath = !empty($image['image_path'])
                                                    ? '../php files/' . htmlspecialchars($image['image_path'])
                                                    : '/placeholder.svg?height=500&width=800';
                                            } catch (PDOException $e) {
                                                $imgPath = '/placeholder.svg?height=500&width=800';
                                            }
                                            ?>
                                            <img src="<?php echo $imgPath; ?>"
                                                alt="<?php echo htmlspecialchars($acceptedC['property_title']); ?>">

                                        </div>
                                        <div class="message-box">
                                            <div class="property-title"><?= htmlspecialchars($acceptedC['property_title']) ?>
                                            </div>
                                            <p><strong>Your Offer:</strong> <?= number_format($acceptedC['proposedPrice']) ?> DA
                                            </p>
                                            <p><strong>final's Offer:</strong> <?= number_format($acceptedC['priceOffer']) ?> DA
                                            </p>
                                            <p><strong>Owner's Message:</strong> <?= htmlspecialchars($acceptedC['message']) ?>
                                            </p>
                                            <p><strong>Start at:</strong> <?= htmlspecialchars($acceptedC['proposedDate']) ?>
                                                <br><br><strong>End at:</strong> <?= htmlspecialchars($acceptedC['endDate']) ?>
                                            </p>
                                            <div class="negotiation-status accepted">
                                                <i class="fas fa-check-circle"></i> Accepted by you
                                            </div>

                                            <div class="timestamp">
                                                <?= $acceptedC['sent_at'] ? "Sent at: " . htmlspecialchars($acceptedC['sent_at']) : "No response yet" ?>
                                            </div>
                                            <a href="../property.php?id=<?php echo $propertyId; ?>" class="btn view-btn">
                                                <i class="fas fa-eye"></i> View
                                            </a>

                                        </div>



                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>You don not have any accepted negotiations yet.</p>
                                <a href="../index.php" class="btn primary-btn">explore more</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-content" id="client-accept-tab">
                        <?php if (count($acceptedByOwner) > 0): ?>
                            <div class="properties-grid">
                                <?php foreach ($acceptedByOwner as $acceptedO): ?>
                                    <div class="property-card">
                                        <!-- Similar structure to other tabs -->
                                        <div class="property-image">
                                            <?php
                                            // retrieve the property id from the message table using id negotiation
                                            $propertyId = $acceptedO['id_property'];
                                            // retrieve the image path for the property
                                            try {
                                                $stmt = $pdo->prepare("
                                            SELECT image_path 
                                            FROM property_images 
                                            WHERE property_id = ? 
                                            ORDER BY image_order ASC 
                                            LIMIT 1
                                        ");
                                                $stmt->execute([$propertyId]);
                                                $image = $stmt->fetch(PDO::FETCH_ASSOC);

                                                $imgPath = !empty($image['image_path'])
                                                    ? '../php files/' . htmlspecialchars($image['image_path'])
                                                    : '/placeholder.svg?height=500&width=800';
                                            } catch (PDOException $e) {
                                                $imgPath = '/placeholder.svg?height=500&width=800';
                                            }
                                            ?>
                                            <img src="<?php echo $imgPath; ?>"
                                                alt="<?php echo htmlspecialchars($acceptedO['property_title']); ?>">

                                        </div>
                                        <div class="message-box">
                                            <div class="property-title"><?= htmlspecialchars($acceptedO['property_title']) ?>
                                            </div>
                                            <p><strong>Your Offer:</strong> <?= number_format($acceptedO['proposedPrice']) ?> DA
                                            </p>
                                            <p><strong>final Offer:</strong>
                                                <?php
                                                echo isset($acceptedO['priceOffer']) && !empty($acceptedO['priceOffer'])
                                                    ? number_format($acceptedO['priceOffer'])
                                                    : number_format($acceptedO['proposedPrice']);
                                                ?> DA
                                            </p>
                                            <p><strong>Owner's Message:</strong>
                                                <?php
                                                echo !empty($acceptedO['message'])
                                                    ? htmlspecialchars($acceptedO['message'])
                                                    : 'No message provided.';
                                                ?>
                                            </p>
                                            <p><strong>start at:</strong> <?= htmlspecialchars($acceptedO['proposedDate']) ?>
                                                <br><br><strong>End at:</strong> <?= htmlspecialchars($acceptedO['endDate']) ?>
                                            </p>

                                            <div class="timestamp">
                                                <?= $acceptedO['sent_at'] ? "Sent at: " . htmlspecialchars($acceptedO['sent_at']) : "No response yet" ?>
                                            </div>
                                            <a href="../property.php?id=<?php echo $propertyId; ?>" class="btn view-btn">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>You haven't accepted any negotiations yet.</p>
                                <a href="../index.php" class="btn primary-btn">explore more</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Counter Offer Popup -->
                <!-- <div class="popup-overlay" id="counterOfferPopup">
            <div class="popup-content">
                <div class="popup-header">
                    <h3>Make Counter Offer</h3>
                    <button class="close-popup" id="closeCounterOfferPopup">&times;</button>
                </div>
                <div class="popup-body">
                    <form id="counterOfferForm" action="../php files/send_message.php" method="POST">
                        <input type="hidden" name="negotiation_id" id="counterOfferNegotiationId">

                        <div class="form-group">
                            <label for="counterOfferPrice">Counter Offer Price (DZD)</label>
                            <input type="number" id="counterOfferPrice" name="counter_price" required min="1">
                        </div>
                        <input type="hidden" name="sender_role" value='client'>
                        <div class="form-group">
                            <label for="counterOfferMessage">Message (Optional)</label>
                            <textarea id="counterOfferMessage" name="counter_message" rows="4"
                                placeholder="Explain your counter offer..."></textarea>
                        </div>

                        <button type="submit" class="btn primary-btn">Send Counter Offer</button>
                    </form>
                </div>
            </div>
        </div> -->

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        // Menu toggle
                        const menuToggle = document.getElementById('menuToggle');
                        const menuContent = document.getElementById('menuContent');
                        console.log('menuToggle:', menuToggle); // Will print 'null' if not found


                        menuToggle.addEventListener('click', function (e) {
                            e.stopPropagation();
                            menuContent.classList.toggle('active');
                        });

                        document.addEventListener('click', function (e) {
                            if (!menuContent.contains(e.target) && !menuToggle.contains(e.target)) {
                                menuContent.classList.remove('active');
                            }
                        });

                        // Sidebar toggle
                        const sidebarToggle = document.getElementById('sidebarToggle');
                        const sidebar = document.getElementById('sidebar');

                        if (sidebarToggle) {
                            sidebarToggle.addEventListener('click', function () {
                                sidebar.classList.toggle('active');
                            });

                        }

                        // Dashboard tabs
                        const tabButtons = document.querySelectorAll('.tab-btn');
                        const tabContents = document.querySelectorAll('.tab-content');

                        tabButtons.forEach(button => {
                            button.addEventListener('click', function () {
                                const tabId = this.getAttribute('data-tab');

                                // Remove active class from all tabs
                                tabButtons.forEach(btn => btn.classList.remove('active'));
                                tabContents.forEach(content => content.classList.remove('active'));

                                // Add active class to current tab
                                this.classList.add('active');
                                document.getElementById(`${tabId}-tab`).classList.add('active');
                            });
                        });

                        // Counter offer popup
                        const counterOfferBtns = document.querySelectorAll('.counter-btn');
                        const counterOfferPopup = document.getElementById('counterOfferPopup');
                        const closeCounterOfferPopup = document.getElementById('closeCounterOfferPopup');
                        const counterOfferNegotiationId = document.getElementById('counterOfferNegotiationId');

                        // counterOfferBtns.forEach(btn => {
                        //     btn.addEventListener('click', function () {
                        //         const negotiationId = this.getAttribute('data-negotiation-id');
                        //         counterOfferNegotiationId.value = negotiationId;
                        //         counterOfferPopup.style.display = 'flex';
                        //     });
                        // });

                        // closeCounterOfferPopup.addEventListener('click', function () {
                        //     counterOfferPopup.style.display = 'none';
                        // });

                        // // Close popup when clicking outside
                        // window.addEventListener('click', function (e) {
                        //     if (e.target === counterOfferPopup) {
                        //         counterOfferPopup.style.display = 'none';
                        //     }
                        // });
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
            </div>
</body>

</html>
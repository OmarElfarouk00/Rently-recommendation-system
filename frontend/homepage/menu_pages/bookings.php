<?php
session_start();
require_once '../php files/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login-signup/index.php');
    exit();
}

$isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in
$userId = $_SESSION['user_id'];
$successMessage = '';
$errorMessage = '';





// check if the user has rented any properties before
try {
    $today = date('Y-m-d');

    $stmt = $pdo->prepare("
    SELECT 
        r.*, 
        p.title AS property_title,
        COALESCE(resp.priceOffer, n.proposedPrice, p.estimatePrice) AS final_price
    FROM Rental r
    JOIN Property p ON r.id_property = p.id_property
    LEFT JOIN Negotiation n 
        ON r.id_property = n.id_property 
       AND r.id_client = n.id_client 
       AND r.startDate = n.proposedDate
    LEFT JOIN Response resp 
        ON resp.id_negotiation = n.id_negotiation 
       AND resp.sent_at = (
           SELECT MAX(r2.sent_at)
           FROM Response r2
           WHERE r2.id_negotiation = n.id_negotiation
       )
        LEFT JOIN (
    SELECT property_id, image_path
    FROM property_images
    GROUP BY property_id
) img ON img.property_id = p.id_property
    WHERE r.id_client = ?
    ORDER BY r.startDate DESC
");
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $allBookings = [];
    $upcomingBookings = [];
    $pastBookings = [];

    foreach ($results as $booking) {
        $allBookings[] = $booking;

        if ($booking['startDate'] >= $today) {
            $upcomingBookings[] = $booking;
        } elseif ($booking['endDate'] < $today) {
            $pastBookings[] = $booking;
        }
    }
    // $endDate = date('Y-m-d', strtotime('+1 month', strtotime($booking['startDate'])));

    // if ($booking['startDate'] > $endDate) {
//     $pastBookings[] = $booking;
// }

} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
}


// calculate the end date from the start date +1 month
// Check if user is a property owner
try {
    $stmt = $pdo->prepare("
        SELECT po.id_propertyOwner 
        FROM propertyOwner po
        WHERE po.id_propertyOwner = ?
    ");
    $stmt->execute([$userId]);
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$owner) {
        // User is not a property owner, create owner record
        $stmt = $pdo->prepare("INSERT INTO propertyOwner (id_propertyOwner) VALUES (?)");
        $stmt->execute([$userId]);

        $ownerId = $userId;
        $_SESSION['is_owner'] = true;
    } else {
        $ownerId = $owner['id_propertyOwner'];
        $_SESSION['is_owner'] = true;
    }
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
    $ownerId = null;
}


// Handle booking status updates
if (isset($_POST['update_booking'])) {
    $bookingId = $_POST['booking_id'];
    $status = $_POST['status'];


    try {
        // Update the booking status
        $stmt = $pdo->prepare("UPDATE Rental SET status = ? WHERE id = ?");
        $stmt->execute([$status, $bookingId]);

        // If status is accepted (1), insert into Valid table
        if ($status == 1) {
            // Get id_client and id_property for this booking
            $stmt = $pdo->prepare("SELECT id_client, id_property FROM Rental WHERE id = ?");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            //update the property status
            $stmt = $pdo->prepare("UPDATE Property SET status = 'pending' WHERE id_property = ?");
            $stmt->execute([$booking['id_property']]);

            // Insert into Valid table
            if ($booking) {
                $stmt = $pdo->prepare("
                    INSERT INTO Valid (id_client, id_property, status)
                    VALUES (?, ?, 'true')
                    ON DUPLICATE KEY UPDATE status = 'true'
                ");
                $stmt->execute([$booking['id_client'], $booking['id_property']]);
            }
        }

        $successMessage = "Booking status updated successfully";
    } catch (PDOException $e) {
        $errorMessage = "Error updating booking: " . $e->getMessage();
    }
}

// Handle negotiation responses
if (isset($_POST['respond_negotiation'])) {
    $negotiationId = $_POST['negotiation_id'];
    $response = $_POST['response'];

    try {
        if ($response === 'accept') {
            // Accept the negotiation
            $stmt = $pdo->prepare("
                INSERT INTO ValidateNegotiation (validationDate, finalPrice, terms, id_negotiation)
                SELECT CURDATE(), proposedPrice, 'Accepted by owner', id
                FROM Negotiation
                WHERE id = ?
            ");
            $stmt->execute([$negotiationId]);

            // Update negotiation status
            $stmt = $pdo->prepare("UPDATE Negotiation SET status = 'accepted' WHERE id = ?");
            $stmt->execute([$negotiationId]);

            $successMessage = "Negotiation accepted successfully";
        } else {
            // Reject the negotiation
            $stmt = $pdo->prepare("UPDATE Negotiation SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$negotiationId]);

            $successMessage = "Negotiation rejected";
        }
    } catch (PDOException $e) {
        $errorMessage = "Error responding to negotiation: " . $e->getMessage();
    }
}

// Fetch owner's properties
try {
    $stmt = $pdo->prepare("
        SELECT * FROM Property
        WHERE id_propertyOwner = ?
        ORDER BY id_property DESC
    ");
    $stmt->execute([$ownerId]);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching properties: " . $e->getMessage();
    $properties = [];
}

// Fetch booking requests
// try {
//     $stmt = $pdo->prepare("
//         SELECT r.*, c.full_name as client_name, c.email as client_email, p.title as property_title, c.phone as client_phone_number, p.estimatePrice as price
//         FROM Rental r
//         JOIN Client c ON r.id_client = c.id_client
//         JOIN Property p ON r.id_property = p.id_property
//         WHERE p.id_propertyOwner = ?
//         ORDER BY r.startDate DESC
//     ");
//     $stmt->execute([$ownerId]);
//     $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

//     // Debug information
//     error_log("Fetched " . count($bookings) . " bookings");
//     if (count($bookings) > 0) {
//         error_log("First booking keys: " . implode(", ", array_keys($bookings[0])));
//     }
// } catch (PDOException $e) {
//     $errorMessage = "Error fetching bookings: " . $e->getMessage();
//     $bookings = [];
// }

// Fetch negotiation requests
try {
    $stmt = $pdo->prepare("
        SELECT n.*, c.full_name as client_name, c.email as client_email, 
            p.title as property_title, p.estimatePrice as original_price
        FROM Negotiation n
        JOIN Client c ON n.id_client = c.id_client
        JOIN Property p ON n.id_property = p.id_property
        WHERE p.id_propertyOwner = ? AND n.status = 'pending'
        ORDER BY n.proposedDate DESC
    ");
    $stmt->execute([$ownerId]);
    $negotiations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug information
    error_log("Fetched " . count($negotiations) . " negotiations");
    if (count($negotiations) > 0) {
        error_log("First negotiation keys: " . implode(", ", array_keys($negotiations[0])));
    }
} catch (PDOException $e) {
    $errorMessage = "Error fetching negotiations: " . $e->getMessage();
    $negotiations = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties | RentEstate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
    <style>
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

        .property-info h3 {
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .property-info p {
            color: #666;
            margin-bottom: 1rem;
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
            margin-bottom: 1rem;
        }

        .property-price span {
            font-size: 0.9rem;
            font-weight: normal;
            color: #666;
        }

        .property-actions {
            display: flex;
            gap: 1rem;
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

        .edit-btn {
            background: var(--primary-color);
            color: white;
        }

        .edit-btn:hover {
            background: #d65b1e;
        }

        .property-info {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 90px;
            font-family: 'Poppins', sans-serif;
            font-size: 1.0rem;
        }

        /* Bookings Tab */
        .bookings-list {
            /* better and professional */
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .booking-card {
            /* make it better professional */
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 350px;
            background: var(--light-gray);

        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: white;
            border-bottom: 1px solid var(--border-color);
        }

        .booking-header h3 {
            margin: 0;
            color: var(--text-color);
        }

        .booking-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .booking-status.confirmed {
            width: max-content;
            background: #28a745;
            color: white;
        }

        .booking-status.pending {
            background: #ffc107;
            color: #212529;
        }

        .booking-details {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .booking-info {
            flex: 1;
        }

        .booking-info p {
            margin-bottom: 0.8rem;
            color: #666;
        }

        .booking-info p i {
            width: 20px;
            color: var(--primary-color);
        }

        .booking-actions {
            display: flex;
            gap: 1rem;
        }

        .accept-btn {
            background: #28a745;
            color: white;
        }

        .accept-btn:hover {
            background: #218838;
        }

        .reject-btn {
            background: #dc3545;
            color: white;
        }

        .reject-btn:hover {
            background: #c82333;
        }

        /* Negotiations Tab */
        .negotiations-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .negotiation-card {
            background: var(--light-gray);
            border-radius: 10px;
            overflow: hidden;
        }

        .negotiation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: white;
            border-bottom: 1px solid var(--border-color);
        }

        .negotiation-header h3 {
            margin: 0;
            color: var(--text-color);
        }

        .negotiation-date {
            color: #666;
            font-size: 0.9rem;
        }

        .negotiation-details {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .negotiation-info {
            flex: 1;
        }

        .negotiation-info p {
            margin-bottom: 0.8rem;
            color: #666;
        }

        .negotiation-info p i {
            width: 20px;
            color: var(--primary-color);
        }

        .price-comparison {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            margin: 1rem 0;
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
        }

        .original-price,
        .offered-price {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .original-price span,
        .offered-price span {
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

        .client-message {
            margin-top: 1rem;
            padding: 1rem;
            background: white;
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

        .negotiation-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        /* .counter-btn {
    background: #17a2b8;
    color: white;
}

.counter-btn:hover {
    background: #138496;
} */

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
        /* .popup-overlay {
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
} */

        /* .popup-content {
    background: white;
    border-radius: 15px;
    width: 90%;
    max-width: 500px;
    overflow: hidden;
} */

        /* .popup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
} */

        /* .popup-header h3 {
    margin: 0;
    color: var(--text-color);
} */

        /* .close-popup {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
} */

        /* .popup-body {
    padding: 1.5rem;
} */

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

        /* Responsive */
        @media (max-width: 992px) {
            /* .tab-header {
                flex-direction: column;
            } */

            .tab-btn {
                justify-content: flex-start;
                padding: 1rem 2rem;
            }

            .tab-btn.active::after {
                width: 3px;
                height: 100%;
                top: 0;
                bottom: auto;
            }

            .negotiation-details {
                flex-direction: column;
            }

            .booking-details {
                flex-direction: column;
                gap: 1.5rem;
            }

            .booking-actions {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 768px) {
            .properties-grid {
                grid-template-columns: 1fr;
            }

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
        }

        @media (max-width: 576px) {
            .negotiation-actions {
                flex-direction: column;
                width: 100%;
            }

            .negotiation-actions form {
                width: 100%;
            }

            .negotiation-actions button {
                width: 100%;
                justify-content: center;
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

            .tab-btn {
                justify-content: flex-start;
                padding: 1rem 2rem;
                display: inline;
            }

            .tab-btn.active::after {
                width: 3px;
                height: 100%;
                top: 0;
                bottom: auto;
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
                    <a href="bookings.php" class="menu-item active">
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
                        <a href="settings.php" class="menu-item">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                        
                        <a href="my-properties.php" class="menu-item">
                            <i class="fas fa-building"></i>
                            My Properties
                        </a>
                        
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
                        <a href="../login-signup/php files/logout.php" class="menu-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div> -->
        </div>
    </header>

    <div class="my-properties-container" id="menuContent">
        <div class="dashboard-header">
            <h1>My Bookings</h1>
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
                <button class="tab-btn active" data-tab="all-bookings" id="menuToggle">
                    <i class="fas fa-list"></i> My Bookings
                    <span class="count"><?php echo count($allBookings); ?></span>
                </button>
                <button class="tab-btn" data-tab="upcoming-bookings">
                    <i class="fas fa-clock"></i> Upcoming
                    <span class="count"><?php echo count($upcomingBookings); ?></span>
                </button>
                <button class="tab-btn" data-tab="past-bookings">
                    <i class="fas fa-history"></i> Past
                    <span class="count"><?php echo count($pastBookings); ?></span>
                </button>
            </div>


            <div class="tab-content active" id="all-bookings-tab">


                <?php if (count($allBookings) > 0): ?>
                    <div class="bookings-list">
                        <?php foreach ($allBookings as $booking): ?>
                            <div class="booking-card">
                                <div class="property-image">
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT p.*
                                        FROM property p
                                        WHERE p.id_property = ?
                                    ");
                                    $stmt->execute([$booking['id_property']]);
                                    $property = $stmt->fetch(PDO::FETCH_ASSOC);
                                    try {
                                        $stmt = $pdo->prepare("
                                            SELECT image_path 
                                            FROM property_images 
                                            WHERE property_id = ? 
                                            ORDER BY image_order ASC 
                                            LIMIT 1
                                        ");
                                        $stmt->execute([$booking['id_property']]);
                                        $image = $stmt->fetch(PDO::FETCH_ASSOC);

                                        $imgPath = !empty($image['image_path'])
                                            ? '../php files/' . htmlspecialchars($image['image_path'])
                                            : '/placeholder.svg?height=500&width=800';
                                    } catch (PDOException $e) {
                                        $imgPath = '/placeholder.svg?height=500&width=800';
                                    }
                                    ?>
                                    <a href="../property.php?id=<?php echo $booking['id_property']; ?>"
                                        style=" text-decoration: none; color: inherit;"><img src="<?php echo $imgPath; ?>"
                                            alt="<?php echo htmlspecialchars($property['title']); ?>"></a>

                                </div>
                                <div class="property-info">
                                    <h3><?php echo htmlspecialchars($booking['property_title']); ?></h3>
                                    <p><strong>Start Date:</strong> <?php echo $booking['startDate']; ?></p>
                                    <p><strong>End Date:</strong> <?php echo $booking['endDate']; ?></p>
                                    <p><strong>Price:</strong>
                                        <?php echo number_format($booking['final_price'], 0, '.', ','); ?>
                                        DZD</p>
                                    <div class="booking-status <?php echo $booking['status'] ? 'confirmed' : 'pending'; ?>">
                                        <?php echo $booking['status'] ? 'Confirmed' : 'Pending'; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>

                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-check"></i>
                        <p>No bookings found.</p>
                    </div>
                <?php endif; ?>

            </div>

            <div class="tab-content" id="upcoming-bookings">
                <?php if (empty($upcomingBookings)): ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-check"></i>
                        <p>No upcoming booking.</p>
                    </div>
                <?php else: ?>
                    <div class="bookings-list">
                        <?php foreach ($upcomingBookings as $booking): ?>
                            <div class="booking-card">
                                <div class="property-image">
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT p.*
                                        FROM property p
                                        WHERE p.id_property = ?
                                    ");
                                    $stmt->execute([$booking['id_property']]);
                                    $property = $stmt->fetch(PDO::FETCH_ASSOC);
                                    try {
                                        $stmt = $pdo->prepare("
                                            SELECT image_path 
                                            FROM property_images 
                                            WHERE property_id = ? 
                                            ORDER BY image_order ASC 
                                            LIMIT 1
                                        ");
                                        $stmt->execute([$booking['id_property']]);
                                        $image = $stmt->fetch(PDO::FETCH_ASSOC);

                                        $imgPath = !empty($image['image_path'])
                                            ? '../php files/' . htmlspecialchars($image['image_path'])
                                            : '/placeholder.svg?height=500&width=800';
                                    } catch (PDOException $e) {
                                        $imgPath = '/placeholder.svg?height=500&width=800';
                                    }
                                    ?>
                                    <a href="../property.php?id=<?php echo $booking['id_property']; ?>"
                                        style=" text-decoration: none; color: inherit;"><img src="<?php echo $imgPath; ?>"
                                            alt="<?php echo htmlspecialchars($property['title']); ?>"></a>
                                    <!-- <div class="property-status <?php echo strtolower($booking['status']); ?>">
                                    <?php echo htmlspecialchars($booking['status']); ?>
                                </div> -->
                                    <div class="property-info">
                                        <p><strong>Start Date:</strong> <?= $booking['startDate'] ?></p>
                                        <p><strong>End Date:</strong> <?= $booking['endDate'] ?></p>

                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>


            </div>



            <div class="tab-content" id="past-bookings">
                <div class="bookings-list">
                    <?php if (empty($pastBookings)): ?>
                        <div class="no-data">
                            <i class="fas fa-calendar-check"></i>
                            <p>No past bookings.</p>
                        </div>

                    <?php else: ?>
                        <?php foreach ($pastBookings as $booking): ?>
                            <div class="booking-card">
                                <div class="property-image">
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT p.*
                                        FROM property p
                                        WHERE p.id_property = ?
                                    ");
                                    $stmt->execute([$booking['id_property']]);
                                    $property = $stmt->fetch(PDO::FETCH_ASSOC);
                                    try {
                                        $stmt = $pdo->prepare("
                                            SELECT image_path 
                                            FROM property_images 
                                            WHERE property_id = ? 
                                            ORDER BY image_order ASC 
                                            LIMIT 1
                                        ");
                                        $stmt->execute([$booking['id_property']]);
                                        $image = $stmt->fetch(PDO::FETCH_ASSOC);

                                        $imgPath = !empty($image['image_path'])
                                            ? '../php files/' . htmlspecialchars($image['image_path'])
                                            : '/placeholder.svg?height=500&width=800';
                                    } catch (PDOException $e) {
                                        $imgPath = '/placeholder.svg?height=500&width=800';
                                    }
                                    ?>
                                    <a href="../property.php?id=<?php echo $booking['id_property']; ?>"
                                        style=" text-decoration: none; color: inherit;"><img src="<?php echo $imgPath; ?>"
                                            alt="<?php echo htmlspecialchars($property['title']); ?>"></a>

                                    <div class="property-status <?php echo strtolower($booking['status']); ?>">
                                        <?php echo htmlspecialchars($booking['status']); ?>
                                    </div>
                                </div>
                                <p><strong>Start:</strong> <?= $booking['startDate'] ?></p>
                                <p><strong>Ended on:</strong>
                                    <?= $booking['endDate'] ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
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
                <form id="counterOfferForm" action="counter_offer.php" method="POST">
                    <input type="hidden" name="negotiation_id" id="counterOfferNegotiationId">
                    
                    <div class="form-group">
                        <label for="counterOfferPrice">Counter Offer Price (DZD)</label>
                        <input type="number" id="counterOfferPrice" name="counter_price" required min="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="counterOfferMessage">Message (Optional)</label>
                        <textarea id="counterOfferMessage" name="counter_message" rows="4" placeholder="Explain your counter offer..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn primary-btn">Send Counter Offer</button>
                </form>
            </div>
        </div>
    </div> -->
        <!-- </div> -->
        <!-- <script src="../script.js"></script> -->
        <script defer>
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




                // Dashboard tabs
                // const tabButtons = document.querySelectorAll('.tab-btn');
                // const tabContents = document.querySelectorAll('.tab-content');

                // tabButtons.forEach(button => {
                //     button.addEventListener('click', function () {
                //         const tabId = this.getAttribute('data-tab');

                //         // Remove active class from all tabs
                //         tabButtons.forEach(btn => btn.classList.remove('active'));
                //         tabContents.forEach(content => content.classList.remove('active'));

                //         // Add active class to current tab
                //         this.classList.add('active');
                //         document.getElementById(`${tabId}-tab`).classList.add('active');
                //     });
                // });

                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                        btn.classList.add('active');
                        const tabId = btn.getAttribute('data-tab');
                        document.getElementById(tabId + '-tab')?.classList.add('active');
                        document.getElementById(tabId)?.classList.add('active'); // fallback
                    });
                });


                // Counter offer popup
                // const counterOfferBtns = document.querySelectorAll('.counter-btn');
                // const counterOfferPopup = document.getElementById('counterOfferPopup');
                // const closeCounterOfferPopup = document.getElementById('closeCounterOfferPopup');
                // const counterOfferNegotiationId = document.getElementById('counterOfferNegotiationId');

                // counterOfferBtns.forEach(btn => {
                //     btn.addEventListener('click', function() {
                //         const negotiationId = this.getAttribute('data-negotiation-id');
                //         counterOfferNegotiationId.value = negotiationId;
                //         counterOfferPopup.style.display = 'flex';
                //     });
                // });

                // closeCounterOfferPopup.addEventListener('click', function() {
                //     counterOfferPopup.style.display = 'none';
                // });

                // // Close popup when clicking outside
                // window.addEventListener('click', function(e) {
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
</body>

</html>
<?php
session_start();
require_once 'php files/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login-signup/index.php');
    exit();
}

$isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in
$userId = $_SESSION['user_id'];
$senderRole = isset($_POST['sender_role']) ? $_POST['sender_role'] : '';
$i = 0;
$m=1;
// $successMessage = isset($_SESSION['success_message']) ?? $_SESSION['success_message'];
// $errorMessage = isset($_SESSION['error_message']) ?? $_SESSION['error_message'];


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
    // $errorMessage = "Database error: " . $e->getMessage();
    $ownerId = null;
}


// Handle booking status updates
if (isset($_POST['update_booking'])) {
    // $bookingId = $_POST['booking_id'];
    $id_client = $_POST['id_client'];
    $id_property = $_POST['id_property'];
    $startDate = $_POST['startDate'];
    $status = $_POST['status'];

    try {
        if ($status == 1) {
            // Get startDate and endDate for this booking
            $stmt = $pdo->prepare("SELECT endDate FROM Rental WHERE id_client = ? AND id_property = ? AND startDate = ?");
            $stmt->execute([$id_client, $id_property, $startDate]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($booking) {
                $endDate = $booking['endDate'];

                // Check for overlapping rentals
                // Check if property is already booked for the requested period
                $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM Rental 
                WHERE id_property = ? 
                AND status = true
                AND (
                        (startDate <= ? AND endDate >= ?) -- Overlapping date range
                )
                    ");
                $stmt->execute([$id_property, $endDate, $startDate]);
                $overlapCount = $stmt->fetchColumn();

                if ($overlapCount > 0) {
                    $_SESSION['error_message'] = "This property is already booked for the selected period.";
                    header("Location: my-properties.php");
                    exit();
                }

                // Proceed with update
                $stmt = $pdo->prepare("UPDATE Property SET status = 'rented' WHERE id_property = ?");
                $stmt->execute([$id_property]);

                $stmt = $pdo->prepare("
                    UPDATE Rental SET status = '1' 
                    WHERE id_client = ? AND id_property = ? AND startDate = ?
                ");
                $stmt->execute([$id_client, $id_property, $startDate]);
                $_SESSION['success_message'] = "Booking confirmed successfully.";
                $i=1;
                header("Location: my-properties.php");

                
                $stmt = $pdo->prepare("
                    INSERT INTO Valid (id_client, id_property, startDate, status)
                    VALUES (?, ?, ?, 'true')
                ");
                $stmt->execute([$id_client, $id_property, $startDate]);
                exit();
                // echo json_encode(['success' => true, 'message' => 'Booking confirmed']);
            }
        } else {
            $stmt = $pdo->prepare("
                UPDATE Rental SET status = '2' 
                WHERE id_client = ? AND id_property = ? AND startDate = ? AND status = '0'
            ");
            $stmt->execute([$id_client, $id_property, $startDate]);
            // echo json_encode(['success' => true, 'message' => 'Booking canceled']);
        }
        $_SESSION['success_message'] = "Booking status updated successfully.";
        $i = 1;
        header("Location: my-properties.php");
        // $successMessage = "Booking status updated successfully";
    } catch (PDOException $e) {
        $errorMessage = "Error updating booking: " . $e->getMessage();
    }

}

// Fetch negotiation requests
try {
    $stmt = $pdo->prepare("
SELECT 
    n.*, 
    c.full_name AS client_name, 
    c.email AS client_email, 
    p.title AS property_title, 
    p.estimatePrice AS original_price,
    p.ownerNeeds,
    c.phone AS phone_number,
    c.id_client,
    p.id_property
FROM negotiation n
JOIN Client c ON n.id_client = c.id_client
JOIN Property p ON n.id_property = p.id_property
WHERE p.id_propertyOwner = ? AND n.status = 'pending' 
ORDER BY 
    CASE WHEN n.proposedPrice IS NULL THEN 0 ELSE 1 END,
        n.proposedPrice DESC
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

$m=2;
// Handle negotiation responses
if (!empty($_POST['respond_negotiation'])) {
    $negotiationId = isset($_POST['negotiation_id']) ? intval($_POST['negotiation_id']) : 0;
    $response = $_POST['response'];
    $id_client = $_POST['id_client'];
    $id_property = $_POST['id_property'];
    $proposedDate = $_POST['proposedDate'];
    $response = $_POST['response'];
    $senderRole = $_POST['sender_role'];
$m=3;
    try {
        if ($response === 'accept') {
            // Get negotiation info
            $stmt = $pdo->prepare("
                SELECT * FROM negotiation WHERE id_negotiation = ?
            ");
            $stmt->execute([$negotiationId]);
            $negotiation = $stmt->fetch(PDO::FETCH_ASSOC);

$m=4;
if ($negotiation) {
    $m= 44;
}
            // $startDate = $negotiation['proposedDate'];
            $endDate = $negotiation['endDate'];
var_dump($negotiation);
            if (!$negotiation) {
                throw new Exception("Negotiation not found.");
            }
            // Check if property is already booked for the requested period
            // Check if property is already booked for the requested period
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM Rental 
                WHERE id_property = ? 
                AND status = 1
                AND (
                        (startDate <= ? AND endDate >= ?) 
                )
                    ");
                    
            $stmt->execute([$id_property, $endDate, $proposedDate]);
            $isBooked = $stmt->fetchColumn() > 0;
$m=5;
            if ($isBooked) {
                // echo json_encode(['success' => false, 'message' => 'Property is already booked for the selected dates']);
                $_SESSION['error_message'] = "This property is already booked for the selected period.";
                header("Location: my-properties.php");
                exit();
            }

$m=6;
            // Insert into ValidateNegotiation
            $stmt = $pdo->prepare("
                INSERT INTO ValidateNegotiation (id_negotiation, validationDate, finalPrice, terms, id_client, id_property, proposedDate, accepted_by)
                VALUES (? , CURDATE(), ?, 'Accepted by owner', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $negotiation['id_negotiation'],
                $negotiation['proposedPrice'],
                $id_client,
                $id_property,
                $proposedDate,
                $senderRole
            ]);

$m=7;
            // Update negotiation status
            $stmt = $pdo->prepare("
                UPDATE Negotiation SET status = 'accepted' 
                WHERE id_negotiation = ?
            ");
            $stmt->execute([$negotiation['id_negotiation']]);

$m=8;
            // Insert into Rental
            $stmt = $pdo->prepare("
                INSERT INTO Rental (id_client, id_property, startDate, endDate, status, contractTerms)
                VALUES (?, ?, ?, ?, 1, 'standard terms')
            ");
            $stmt->execute([
                $id_client,
                $id_property,
                $proposedDate,
                $endDate
            ]);

$m=9;
            header("Location: my-properties.php");
            // $successMessage = "Negotiation accepted successfully";
            $_SESSION['success_message'] = "Negotiation accepted successfully.";
            $i = 1;
            header("Location: my-properties.php");

$m=10;

        } else {
            // Reject the negotiation
            $stmt = $pdo->prepare("
                UPDATE Negotiation SET status = 'rejected' 
                WHERE id_negotiation = ?
            ");
            $stmt->execute([$negotiationId]);

            $_SESSION['success_message'] = "Negotiation rejected.";
            $i = 1;
            header("Location: my-properties.php");

            // header("Location: my-properties.php");
        }

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
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
try {
    // $stmt = $pdo->prepare("
    //     SELECT r.*, c.full_name as client_name, c.email as client_email, p.title as property_title, c.phone as client_phone_number, p.estimatePrice as price
    //     FROM Rental r
    //     JOIN Client c ON r.id_client = c.id_client
    //     JOIN Property p ON r.id_property = p.id_property
    //     WHERE p.id_propertyOwner = ? 
    //     ORDER BY r.startDate DESC
    // ");
    // $stmt->execute([$ownerId]);
    // $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
    SELECT 
        r.*, 
        c.full_name AS client_name, 
        c.email AS client_email, 
        c.phone AS client_phone_number,
        p.title AS property_title,
        r.status as negotiation_status,
        COALESCE(m.priceOffer, n.proposedPrice, p.estimatePrice) AS price
    FROM Rental r
    JOIN Client c ON r.id_client = c.id_client
    JOIN Property p ON r.id_property = p.id_property
    LEFT JOIN Negotiation n 
        ON n.id_client = r.id_client 
        AND n.id_property = r.id_property 
        AND n.proposedDate = r.startDate
    LEFT JOIN Response m 
        ON m.id_negotiation = n.id_negotiation
        AND m.sent_at = (
            SELECT MAX(m2.sent_at) 
            FROM Response m2 
            WHERE m2.id_negotiation = n.id_negotiation
        )
    WHERE p.id_propertyOwner = ? 
    ORDER BY r.startDate DESC
");
    $stmt->execute([$ownerId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // Debug information
    error_log("Fetched " . count($bookings) . " bookings");
    if (count($bookings) > 0) {
        error_log("First booking keys: " . implode(", ", array_keys($bookings[0])));
    }
} catch (PDOException $e) {
    $errorMessage = "Error fetching bookings: " . $e->getMessage();
    $bookings = [];
}

try {
    // why am getting two line in the ownerNeeds=selling one from response and the other from negotiation
    $stmt = $pdo->prepare("
SELECT 
    n.*, 
    c.full_name AS client_name, 
    c.email AS client_email,
    c.phone AS client_phone_number,
    p.title AS property_title,
    p.ownerNeeds,
    p.id_property,
    n.proposedDate AS startDate,
    n.endDate,
    n.comments
FROM Negotiation n
JOIN Client c ON n.id_client = c.id_client
JOIN Property p ON n.id_property = p.id_property
WHERE 
    p.id_propertyOwner = ? 
    AND n.status = 'rejected'
    AND EXISTS (
        SELECT 1 
        FROM Response r 
        WHERE r.id_negotiation = n.id_negotiation
    )
ORDER BY n.proposedDate DESC;
    ");

    $stmt->execute([$ownerId]); // $ownerId is from session or previous logic
    $rejections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Optional debug
    error_log("Found " . count($rejections) . " rejected negotiations.");
} catch (PDOException $e) {
    error_log("Error fetching rejected negotiations: " . $e->getMessage());
    $rejections = [];
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties | RentEstate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
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

            /* .sidebar-toggle {
                display: block;
                right: 0%;
                left: 90%;
                top: 33px;

            } */
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

        .property-info {
            padding: 1.5rem;
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

        .delete-btn {
            /* i want only the icon will be show */
            background: none;
            color: #dc3545;
            border: none;
        }



        .edit-btn {
            background: var(--primary-color);
            color: white;
        }

        .edit-btn:hover {
            background: #d65b1e;
        }

        /* Bookings Tab */
        .bookings-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .booking-card {
            background: var(--light-gray);
            border-radius: 10px;
            overflow: hidden;
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
            background: #28a745;
            color: white;
        }

        .booking-status.pending {
            background: #ffc107;
            color: #212529;
        }

        .booking-status.rejected {
            background: #dc3545;
            color: white;
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

        .counter-btn {
            background: #17a2b8;
            color: white;
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
                display: inline;
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
            /* Hidden by default on desktop */
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

        /* Responsive styles for mobile devices */
        @media screen and (max-width: 768px) {

            /* Show the sidebar toggle button on mobile */
            .sidebar-toggle {
                display: block;
                right: 0%;
                left: 90%;
                top: 32px;
            }

            /* Collapse sidebar by default on mobile */
            .sidebar {
                transform: translateX(-250px);
            }

            /* When sidebar is active (not collapsed) */
            .sidebar.active {
                transform: translateX(0);
            }

            /* Add overlay when sidebar is open on mobile */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }

            .sidebar-overlay.active {
                display: block;
            }

            /* Adjust main content when sidebar is open */
            .main-content {
                transition: margin-left 0.3s ease;
            }

            .main-content.sidebar-open {
                margin-left: 0;
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
                <a href="index.php" class="menu-item ">
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
                    <a href="my-properties.php" class="menu-item active">
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
            <?php endif; ?>

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

            <!-- <div class="menu-section">
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

    <!-- Header (Same as index.php) -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <a href="index.php" class="logo">
                    <img src="../rently2.png" alt="" style="height: 38px; width: 130px;">
                </a>
            </div>

            <div class="notif">
                <?php include 'includes/notifications.php'; ?>
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
    <div class="success-message" id="successMessage" style="display: none;">
        Your request has been sent successfully!
    </div>
    <div class="error-message" id="errorMessage" style="display: none;"></div>

    <div class="my-properties-container" id="menuContent">
        <div class="dashboard-header">
            <h1>My Properties Dashboard</h1>
            <a href="become-host.php" class="add-property-btn">
                <i class="fas fa-plus"></i> Add New Property
            </a>
        </div>
        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php
                echo $_SESSION['success_message'];
                if ($i == 0):
                    unset($_SESSION['success_message']);
                endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php
                echo $_SESSION['error_message'];
                if ($i == 0):
                    unset($_SESSION['error_message']);
                endif; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-tabs">
            <div class="tab-header">
                <button class="tab-btn active" data-tab="properties" id="menuToggle">
                    <i class="fas fa-building"></i> My Properties
                    <span class="count"><?php echo count($properties); ?></span>
                </button>
                <button class="tab-btn" data-tab="bookings">
                    <i class="fas fa-calendar-check"></i> Booking Requests
                    <span class="count"><?php echo count($bookings); ?></span>
                </button>
                <button class="tab-btn" data-tab="negotiations">
                    <i class="fas fa-comments-dollar"></i> Negotiations
                    <span class="count"><?php echo count($negotiations); ?></span>
                </button>
                <button class="tab-btn" data-tab="rejections">
                    <i class="fas fa-ban"></i> rejections
                    <span class="count"><?php echo count($rejections); ?></span>
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
                                    <img src="<?php echo $imgPath; ?>"
                                        alt="<?php echo htmlspecialchars($property['title']); ?>">

                                    <div class="property-status <?php echo strtolower($property['status']); ?>">
                                        <?php echo htmlspecialchars($property['status']); ?>
                                    </div>
                                </div>
                                <div class="property-info">
                                    <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                                    <p><i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($property['address'] . ', ' . $property['city']); ?></p>
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
                                        <a href="php files/edit-property.php?id=<?php echo $property['id_property']; ?>"
                                            class="btn edit-btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form action="php files/delete-property.php" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $property['id_property']; ?>">
                                            <button type="submit" class="btn delete-btn">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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
                                    <a href="property.php?id=<?php echo $booking['id_property']; ?>"
                                        style="text-decoration: none;">
                                        <h3><?php echo htmlspecialchars($booking['property_title']); ?></h3>
                                    </a>
                                    <div class="booking-status 
                                        <?php
                                        echo ($booking['negotiation_status'] == '1') ? 'confirmed' :
                                            (($booking['negotiation_status'] == '2') ? 'rejected' : 'pending');
                                        ?>">
                                        <?php
                                        echo ($booking['negotiation_status'] == '1') ? 'Confirmed' :
                                            (($booking['negotiation_status'] == '2') ? 'Rejected' : 'Pending');
                                        ?>
                                    </div>
                                </div>
                                <div class="booking-details">
                                    <div class="booking-info">
                                        <p><i class="fas fa-user"></i> <strong>Client:</strong>
                                            <?php echo htmlspecialchars($booking['client_name']); ?></p>
                                        <p><i class="fas fa-envelope"></i> <strong>Email:</strong>
                                            <?php echo htmlspecialchars($booking['client_email']); ?></p>
                                        <p><i class="fas fa-calendar"></i> <strong>Start Date:</strong>
                                            <?php echo date('F j, Y', strtotime($booking['startDate'])); ?></p>
                                        <p><i class="fas fa-calendar"></i> <strong>End Date:</strong>
                                            <?php echo date('F j, Y', strtotime($booking['endDate'])); ?>
                                        </p>
                                        <p><i class="fas fa-dollar-sign"></i> <strong>Price:</strong>
                                            <?php echo number_format($booking['price'], 0, '.', ','); ?> DZD</i></p>
                                        <p><i class="fas fa-phone"></i> <strong>Phone Number:</strong></i>
                                            <?php echo htmlspecialchars($booking['client_phone_number']); ?></p>
                                    </div>

                                    <?php if ($booking['status'] == '0'): ?>
                                        <div class="booking-actions">
                                            <form action="my-properties.php" method="POST">
                                                <!-- <input type="text" name="id_booking" value="<?php echo $booking['id_booking']; ?>"> -->
                                                <input type="hidden" name="id_client" value="<?php echo $booking['id_client']; ?>">
                                                <input type="hidden" name="id_property"
                                                    value="<?php echo $booking['id_property']; ?>">
                                                <input type="hidden" name="startDate" value="<?php echo $booking['startDate']; ?>">

                                                <input type="hidden" name="status" value="1">
                                                <button type="submit" name="update_booking" class="btn accept-btn">
                                                    <i class="fas fa-check"></i> Accept
                                                </button>
                                            </form>
                                            <form action="my-properties.php" method="POST">
                                                <!-- <input type="text" name="id_booking" value="<?php echo $booking['id_booking']; ?>"> -->
                                                <input type="hidden" name="id_client" value="<?php echo $booking['id_client']; ?>">
                                                <input type="hidden" name="id_property"
                                                    value="<?php echo $booking['id_property']; ?>">
                                                <input type="hidden" name="startDate" value="<?php echo $booking['startDate']; ?>">
                                                <input type="hidden" name="status" value="2">
                                                <!-- Use 2 for 'rejected' to distinguish from 'pending' -->
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
                            <div class="booking-card">
                                <div class="negotiation-header">
                                    <a href="property.php?id=<?php echo $negotiation['id_property']; ?>"
                                        style="text-decoration: none;">
                                        <h3><?php echo htmlspecialchars($negotiation['property_title']);  ?></h3>
                                    </a>
                                    <div class="negotiation-date">
                                        <?php
                                        // $boo = isset($negotiation['proposedPrice']);
                                        if (!empty($negotiation) && !isset($negotiation['proposedPrice'])) {
                                            $msg = 'Buy';
                                            $boo = false;
                                        } else {
                                            $msg = 'Rent';
                                            $boo = true;
                                        }
                                        echo ($msg); ?>
                                        <br>
                                        <hr>
                                        <strong><?php echo date('F j, Y, g:i A', strtotime($negotiation['sent_at'])); ?></strong>
                                    </div>

                                </div>
                                <div class="negotiation-details">
                                    <div class="negotiation-info">
                                        <p><i class="fas fa-user"></i> <strong>Client:</strong>
                                            <?php echo htmlspecialchars($negotiation['client_name']); ?></p>
                                        <p><i class="fas fa-envelope"></i> <strong>Email:</strong>
                                            <?php echo htmlspecialchars($negotiation['client_email']); ?></p>
                                        <p><i class="fas fa-phone"></i> <strong>Phone Number:</strong>
                                            <?php echo htmlspecialchars($negotiation['phone_number']) ?>
                                            <?php if ($negotiation['ownerNeeds'] == 'renting'): ?>
                                                <?php if (isset($negotiation['proposedDate'])): ?>
                                                <p><i class="fas fa-calendar"></i> <strong>Start Date:</strong>
                                                    <?php echo htmlentities(date('F j, Y', strtotime($negotiation['proposedDate']))) ?>
                                                </p>
                                                <p><i class="fas fa-calendar"></i> <strong>Estimated End Date:</strong>
                                                    <?php echo date('F j, Y', strtotime($negotiation['endDate'])); ?>
                                                </p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <div class="price-comparison">
                                            <div class="original-price">
                                                <span>Original Price</span>
                                                <strong><?php echo number_format($negotiation['original_price'], 0, '.', ','); ?>
                                                    DZD</strong>
                                            </div>
                                            <i class="fas fa-arrow-right"></i>
                                            <div class="offered-price">
                                                <span>Offered Price</span>
                                                <?php if (isset($negotiation['proposedPrice'])): ?>
                                                    <strong><?php echo number_format($negotiation['proposedPrice'], 0, '.', ','); ?>
                                                        DZD</strong>
                                                <?php else: ?>
                                                    <strong>Not Offered</strong>
                                                <?php endif; ?>
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
                                        <?php if (isset($negotiation['proposedPrice'])): ?>
                                            <form action="my-properties.php" method="POST" id="acceptOfferForm">
                                                <input type="hidden" name="id_client"
                                                    value="<?php echo $negotiation['id_client']; ?>">
                                                <input type="hidden" name="negotiation_id"
                                                    value="<?php echo $negotiation['id_negotiation']; ?>">
                                                <input type="hidden" name="id_property"
                                                    value="<?php echo $negotiation['id_property']; ?>">
                                                <input type="hidden" name="proposedDate"
                                                    value="<?php echo $negotiation['proposedDate']; ?>">
                                                <input type="hidden" name="response" value="accept">
                                                <input type="hidden" name="sender_role" value="owner">
                                                <button type="submit" name="respond_negotiation" class="btn accept-btn" value="1">
                                                    <i class="fas fa-check"></i> Accept Offer
                                                </button>
                                            </form>

                                            <form action="my-properties.php" method="POST" id="rejectOfferForm">
                                                <input type="hidden" name="id_client"
                                                    value="<?php echo $negotiation['id_client']; ?>">
                                                <input type="hidden" name="negotiation_id"
                                                    value="<?php echo $negotiation['id_negotiation']; ?>">
                                                <input type="hidden" name="id_property"
                                                    value="<?php echo $negotiation['id_property']; ?>">
                                                <input type="hidden" name="proposedDate"
                                                    value="<?php echo $negotiation['proposedDate']; ?>">
                                                <input type="hidden" name="response" value="reject">
                                                <input type="hidden" name="sender_role" value="owner">
                                                <button type="submit" name="respond_negotiation" class="btn reject-btn" value="1">
                                                    <i class="fas fa-times"></i> Reject Offer
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (isset($negotiation['proposedPrice'])): ?>
                                            <button class="btn counter-btn"
                                                data-client-id="<?php echo $negotiation['id_client']; ?>"
                                                data-property-id="<?php echo $negotiation['id_property']; ?>"
                                                data-proposed-date="<?php echo $negotiation['proposedDate']; ?>"
                                                data-negotiation-id="<?php echo $negotiation['id_negotiation']; ?>">

                                                <i class="fas fa-exchange-alt"></i> Counter Offer
                                            </button>
                                        <?php else: ?>
                                            <button class="btn counter-btn"
                                                data-client-id="<?php echo $negotiation['id_client']; ?>"
                                                data-property-id="<?php echo $negotiation['id_property']; ?>"
                                                data-proposed-date="<?php echo $negotiation['proposedDate']; ?>"
                                                data-negotiation-id="<?php echo $negotiation['id_negotiation']; ?>">

                                                <i class="fas fa-exchange-alt"></i> Send Message
                                            </button>
                                        <?php endif; ?>

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

            <div class="tab-content" id="rejections-tab">
                <?php if (count($rejections) > 0): ?>
                    <div class="bookings-list">
                        <?php foreach ($rejections as $rejection): ?>
                            <div class="booking-card">
                                <div class="booking-header">
                                    <a href="property.php?id=<?php echo $rejection['id_property']; ?>"
                                        style="text-decoration: none;">
                                        <h3><?php echo htmlspecialchars($rejection['property_title']); ?></h3>
                                    </a>
                                </div>
                                <div class="booking-details">
                                    <div class="booking-info">
                                        <p><i class="fas fa-user"></i> <strong>Client:</strong>
                                            <?php echo htmlspecialchars($rejection['client_name']); ?></p>
                                        <p><i class="fas fa-envelope"></i> <strong>Email:</strong>
                                            <?php echo htmlspecialchars($rejection['client_email']); ?></p>
                                        <?php if ($rejection['ownerNeeds'] == 'renting'): ?>
                                            <p><i class="fas fa-calendar"></i> <strong>Start Date:</strong>
                                                <?php echo date('F j, Y', strtotime($rejection['startDate'])); ?></p>
                                            <p><i class="fas fa-calendar"></i> <strong>End Date:</strong>
                                                <?php echo date('F j, Y', strtotime($rejection['endDate'])); ?>
                                            </p>
                                        <?php endif; ?>
                                        <p><i class="fas fa-phone"></i> <strong>Phone Number:</strong></i>
                                            <?php echo htmlspecialchars($rejection['client_phone_number']); ?></p>
                                        <p><i class="fas fa-envelope"> </i><strong>Message:</strong></i>
                                            <?php if ($rejection['comments'] != null):
                                                echo htmlspecialchars($rejection['comments']);
                                            else:
                                                echo 'No message provided';
                                            endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-check"></i>
                        <p>You don't have any rejections made yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>


    <!-- Counter Offer Popup -->
    <div class="popup-overlay" id="counterOfferPopup">
        <div class="popup-content">
            <div class="popup-header">
                <h3><?php  echo $boo ?  $negotiation['proposedPrice'].' Make Counter Offer' : 'Send Message'; ?></h3>
                <button class="close-popup" id="closeCounterOfferPopup">&times;</button>
            </div>
            <div class="popup-body">
                <form id="counterOfferForm" action="php files/send_message.php" method="POST">
                    <input type="hidden" name="negotiation_id" id="counterOfferNegotiationId">
                    <input type="hidden" name="id_client" value="<?php echo $negotiation['id_client']; ?>">
                    <input type="hidden" name="id_property" value="<?php echo $negotiation['id_property']; ?>">
                    <input type="hidden" name="proposedDate" value="<?php echo $negotiation['proposedDate']; ?>">

                    <?php if (isset($negotiation['proposedPrice'])): ?>
                        <div class="form-group">
                            <label for="counterOfferPrice">Counter Offer Price (DZD)*</label>
                            <input type="number" id="counterOfferPrice" name="counter_price" min="1">
                        </div>
                    <?php endif; ?>
                    <input type="hidden" name="sender_role" value='owner'>

                    <div class="form-group">
                        <label for="counterOfferMessage">Message (Optional)</label>
                        <textarea id="counterOfferMessage" name="counter_message" rows="4"
                            placeholder="Explain your counter offer..."></textarea>
                    </div>

                    <button type="submit" class="btn primary-btn">Send</button>
                </form>
            </div>
        </div>
    </div>
    <!-- </div> -->

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



            // const acceptOfferForm = document.getElementById('acceptOfferForm');
            // if (acceptOfferForm) {
            //     acceptOfferForm.addEventListener('submit', function (e) {
            //         e.preventDefault();

            //         const formData = new FormData(this);

            //         fetch(this.action, {
            //             method: 'POST',
            //             body: formData
            //         })
            //             .then(response => response.json())
            //             .then(data => {
            //                 if (data.success) {
            //                     location.reload();
            //                 } else {
            //                     showErrorMessage('An error occurred. Please try again.');
            //                     // alert(data.message || 'An error occurred. Please try again.');
            //                 }
            //             })
            //             .catch(error => {
            //                 console.error('Error:', error);
            //                 showErrorMessage('An error occurred. Please try again.');
            //                 // alert('An error occurred. Please try again.');
            //             });
            //     });
            // }


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

            counterOfferBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    const negotiationId = this.getAttribute('data-negotiation-id');
                    counterOfferNegotiationId.value = negotiationId;
                    counterOfferPopup.style.display = 'flex';
                });
            });

            closeCounterOfferPopup.addEventListener('click', function () {
                counterOfferPopup.style.display = 'none';
            });

            // Close popup when clicking outside
            window.addEventListener('click', function (e) {
                if (e.target === counterOfferPopup) {
                    counterOfferPopup.style.display = 'none';
                }
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
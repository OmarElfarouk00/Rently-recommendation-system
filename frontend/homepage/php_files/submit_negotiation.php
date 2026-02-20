<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'login_required']);
    exit();
}

// Check if all required fields are provided
switch ($_POST['ownerNeeds']) {
    case 'renting':
        if (!isset($_POST['property_id']) || !isset($_POST['proposed_price']) || !isset($_POST['start_date']) || !isset($_POST['end_date'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
    case 'selling':
        if (!isset($_POST['property_id']) || !isset($_POST['comments'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
}



$property_id = $_POST['property_id'];
// $proposed_price = isset($_POST['proposed_price']) ? $_POST['proposed_price'] : 0;
$proposed_price = isset($_POST['proposed_price']) ? $_POST['proposed_price'] : null;
$comments = isset($_POST['comments']) ? $_POST['comments'] : '';
$client_id = $_SESSION['user_id'];
// $start_date = isset($_POST['start_date'])? $_POST['start_date'] : '';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

try {
    // Check if property exists
    $stmt = $pdo->prepare("SELECT * FROM Property WHERE id_property = ?");
    $stmt->execute([$property_id]);
    $property = $stmt->fetch();

    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Property not found']);
        exit();
    }
    // Check if property is already booked for the requested period
    $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM Rental 
                WHERE id_property = ? 
                AND status = true
                AND (
                        (startDate <= ? AND endDate >= ?) -- Overlapping date range
                )
                    ");
    $stmt->execute([$property_id, $end_date, $start_date]);
    $isBooked = $stmt->fetchColumn() > 0;

    // check if the start date given not after the end date
    if (strtotime($start_date) > strtotime($end_date)) {
        echo json_encode(['success' => false, 'message' => 'Start date must be before end date']);
        exit();
    }

    // check if the end date given not before the start date
    if (strtotime($end_date) < strtotime($start_date)) {
        echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
        exit();
    }

    if ($isBooked) {
        echo json_encode(['success' => false, 'message' => 'Property is already booked for the selected dates']);
        exit();
    } else {
        // Insert negotiation
        $stmt = $pdo->prepare("
    INSERT INTO Negotiation (proposedPrice, comments, status, proposedDate, id_client, id_property, endDate,sent_at) 
    VALUES (?, ?, 'pending', ?, ?, ?, ?, NOW())
");
        $stmt->execute([$proposed_price, $comments, $start_date, $client_id, $property_id, $end_date]);

        try {
            if ($_POST['ownerNeeds'] === 'selling') {
                // retrieve the property owner id
                $stmt = $pdo->prepare("SELECT id_propertyOwner FROM Property WHERE id_property = ?");
                $stmt->execute([$property_id]);
                $owner_id = $stmt->fetchColumn();

                // Insert message using composite key: id_client, id_property, proposedDate
                $stmt = $pdo->prepare("
        INSERT INTO Response (id_negotiation, id_propertyOwner, message, sent_at, sender_role)
        VALUES (LAST_INSERT_ID(), ?, ?, NOW(), 'client')
    ");
                $stmt->execute([$owner_id, $comments]);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo json_encode(['success' => true, 'message' => 'Negotiation submitted successfully']);

            }

        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        echo json_encode(['success' => true]);

    }


} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
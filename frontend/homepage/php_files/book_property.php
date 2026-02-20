<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'login_required']);
    exit();
}

// Check if all required fields are provided
if (!isset($_POST['property_id']) || !isset($_POST['start_date']) || !isset($_POST['end_date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// check if the start date given not after the end date
if (strtotime($_POST['start_date']) > strtotime($_POST['end_date'])) {
    echo json_encode(['success' => false, 'message' => 'Start date must be before end date']);
    exit();
}

$property_id = $_POST['property_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$client_id = $_SESSION['user_id'];

try {
    // Check if property exists and is available for rent
    $stmt = $pdo->prepare("SELECT * FROM Property WHERE id_property = ? AND ownerNeeds = 'renting'");
    $stmt->execute([$property_id]);
    $property = $stmt->fetch();

    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Property not found or not available for rent']);
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

    if ($isBooked) {
        // echo json_encode(['success' => false, 'message' => 'Property is already booked for the selected dates']);
        $_SESSION['error_message'] = "Property is already booked for the selected dates";
        header("locator: my-properties.php");
        exit();
    } else {
        // Insert rental request
        $stmt = $pdo->prepare("
        INSERT INTO Rental (startDate, endDate, status, contractTerms, id_client, id_property) 
        VALUES (?, ?, false, 'Standard rental agreement', ?, ?)
    ");
        $stmt->execute([$start_date, $end_date, $client_id, $property_id]);
        $_SESSION['success_message'] = "Rental request sent successfully";
    }


    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode("Already sent");
}
?>
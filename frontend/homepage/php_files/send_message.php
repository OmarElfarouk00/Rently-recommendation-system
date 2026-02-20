<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login-signup/login.php');
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $negotiationId = isset($_POST['negotiation_id']) ? intval($_POST['negotiation_id']) : 0;
    $idClient = isset($_POST['id_client']) ? intval($_POST['id_client']) : 0;
    $idProperty = isset($_POST['id_property']) ? intval($_POST['id_property']) : 0;
    $proposedDate = isset($_POST['proposedDate']) ? $_POST['proposedDate'] : '';
    $message = isset($_POST['counter_message']) ? trim($_POST['counter_message']) : '';
    $senderRole = isset($_POST['sender_role']) ? $_POST['sender_role'] : '';
    $proposedPrice = isset($_POST['counter_price']) ? floatval($_POST['counter_price']) : 0;

    // Basic validation
    if ($idClient <= 0 || $idProperty <= 0 || empty($senderRole) || !in_array($senderRole, ['owner', 'client'])) {
        $_SESSION['error'] = "Invalid message data.";
        header("Location: ../my-properties.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id_propertyOwner FROM Property WHERE id_property = ?");
        $stmt->execute([$idProperty]);
        $owner_id = $stmt->fetchColumn();

        // Insert message using client and property identifiers
        $stmt = $pdo->prepare("
        INSERT INTO Response (id_negotiation, id_propertyOwner, message, sent_at, sender_role,priceOffer)
            VALUES (?, ?, ?, NOW(), 'owner', ?)
        ");
        $stmt->execute([
            $negotiationId,
            $owner_id,
            $message,
            $proposedPrice,
        ]);

        $_SESSION['success_message'] = "Message sent successfully.";
        header("Location: ../my-properties.php");
        exit();

    } catch (PDOException $e) {
        error_log("Message error: " . $e->getMessage());
        $_SESSION['error_message'] = "An error occurred. Please try again.";
        header("Location: ../my-properties.php");
        exit();
    }
}
?>

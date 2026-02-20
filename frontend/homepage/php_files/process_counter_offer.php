<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit();
}

// Get form inputs
$negotiationId = isset($_POST['negotiation_id']) ? intval($_POST['negotiation_id']) : 0;
$offerAmount = isset($_POST['offer_amount']) ? floatval($_POST['offer_amount']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$action = $_POST['action'] ?? '';

if ($negotiationId <= 0 || $offerAmount <= 0 || empty($action)) {
    $_SESSION['error'] = "Invalid form data.";
    header("Location: ../counter_offer.php?negotiation_id=$negotiationId");
    exit();
}

try {
    // Get negotiation info
    $stmt = $pdo->prepare("
        SELECT n.*, p.id_propertyOwner
        FROM Negotiation n
        JOIN Property p ON n.id_property = p.id_property
        WHERE n.id = ?
    ");
    $stmt->execute([$negotiationId]);
    $negotiation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$negotiation) {
        $_SESSION['error'] = "Negotiation not found.";
        header('Location: ../dashboard.php');
        exit();
    }

    // Check if user is owner
    $stmtOwner = $pdo->prepare("
        SELECT c.id_client
        FROM Client c
        JOIN propertyOwner po ON c.id_client = po.id_propertyOwner
        WHERE po.id_propertyOwner = ? AND c.id_client = ?
    ");
    $stmtOwner->execute([$negotiation['id_propertyOwner'], $userId]);
    $isOwner = $stmtOwner->rowCount() > 0;

    $isBuyer = ($negotiation['id_client'] == $userId);

    if (!$isOwner && !$isBuyer) {
        $_SESSION['error'] = "Unauthorized action.";
        header('Location: ../dashboard.php');
        exit();
    }

    if ($isOwner) {
        switch ($action) {
            case 'accept':
                // Accept the negotiation, log final price in ValidateNegotiation
                $stmt = $pdo->prepare("
                    UPDATE Negotiation 
                    SET status = 'accepted'
                    WHERE id = ?
                ");
                $stmt->execute([$negotiationId]);

                $stmt = $pdo->prepare("
                    INSERT INTO ValidateNegotiation (id_negotiation, validationDate, finalPrice, terms)
                    VALUES (?, NOW(), ?, ?)
                ");
                $stmt->execute([$negotiationId, $negotiation['proposedPrice'], $message]);

                $_SESSION['success'] = "Offer accepted!";
                break;

            case 'counter':
                // Counter the negotiation
                $stmt = $pdo->prepare("
                    UPDATE Negotiation 
                    SET proposedPrice = ?, comments = ?, status = 'countered'
                    WHERE id = ?
                ");
                $stmt->execute([$offerAmount, $message, $negotiationId]);

                $_SESSION['success'] = "Counter offer sent.";
                break;

            case 'reject':
                $stmt = $pdo->prepare("
                    UPDATE Negotiation 
                    SET status = 'rejected'
                    WHERE id = ?
                ");
                $stmt->execute([$negotiationId]);

                $_SESSION['success'] = "Offer rejected.";
                break;

            default:
                $_SESSION['error'] = "Invalid action.";
        }
    } elseif ($isBuyer) {
        switch ($action) {
            case 'update':
                $stmt = $pdo->prepare("
                    UPDATE Negotiation 
                    SET proposedPrice = ?, comments = ?, status = 'pending'
                    WHERE id = ?
                ");
                $stmt->execute([$offerAmount, $message, $negotiationId]);

                $_SESSION['success'] = "Offer updated.";
                break;

            case 'cancel':
                $stmt = $pdo->prepare("
                    UPDATE Negotiation 
                    SET status = 'cancelled'
                    WHERE id = ?
                ");
                $stmt->execute([$negotiationId]);

                $_SESSION['success'] = "Negotiation cancelled.";
                break;

            default:
                $_SESSION['error'] = "Invalid action.";
        }
    }

    header("Location: ../counter_offer.php?negotiation_id=$negotiationId");
    exit();

} catch (PDOException $e) {
    error_log("Negotiation error: " . $e->getMessage());
    $_SESSION['error'] = "Something went wrong. Please try again.";
    header("Location: ../counter_offer.php?negotiation_id=$negotiationId");
    exit();
}

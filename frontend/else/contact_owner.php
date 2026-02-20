<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'login_required']);
    exit();
}

// Check if all required fields are provided
if (!isset($_POST['property_id']) || !isset($_POST['subject']) || !isset($_POST['message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$property_id = $_POST['property_id'];
$subject = $_POST['subject'];
$message = $_POST['message'];
$client_id = $_SESSION['user_id'];

try {
    // Get property owner information
    $stmt = $pdo->prepare("
        SELECT c.email, c.full_name, p.title
        FROM Property p
        JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
        JOIN Client c ON po.id_propertyOwner = c.id_client
        WHERE p.id_property = ?
    ");
    $stmt->execute([$property_id]);
    $owner = $stmt->fetch();
    
    if (!$owner) {
        echo json_encode(['success' => false, 'message' => 'Property owner not found']);
        exit();
    }
    
    // Get sender information
    $stmt = $pdo->prepare("SELECT email, full_name FROM Client WHERE id_client = ?");
    $stmt->execute([$client_id]);
    $sender = $stmt->fetch();
    
    // Send email (this is a placeholder - implement your email sending logic)
    $to = $owner['email'];
    $from = $sender['email'];
    $subject = "Property Inquiry: " . $subject;
    $emailMessage = "Hello " . $owner['full_name'] . ",\n\n";
    $emailMessage .= $sender['full_name'] . " is interested in your property: " . $owner['title'] . "\n\n";
    $emailMessage .= "Message:\n" . $message . "\n\n";
    $emailMessage .= "You can reply directly to this email to contact the interested party.\n\n";
    $emailMessage .= "Best regards,\nRentEstate Team";
    
    // In a real application, you would send the email here
    // mail($to, $subject, $emailMessage, "From: " . $from);
    
    // For now, we'll just log it to a file for demonstration
    $logFile = fopen("contact_log.txt", "a");
    fwrite($logFile, date("Y-m-d H:i:s") . " - Email to: " . $to . " - Subject: " . $subject . "\n");
    fclose($logFile);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'login_required']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$propertyId = $data['property_id']; // Match the parameter name from JavaScript
$userId = $_SESSION['user_id'];

try {
    // Check if already in favorites
    $checkStmt = $pdo->prepare("
        SELECT * 
        FROM Favorits 
        WHERE id_property = ? and id_client = ?
    ");
    $checkStmt->execute([$propertyId, $userId]);
    
    if ($checkStmt->rowCount() === 0) {
        // Add to favorites
        $stmt = $pdo->prepare("
            INSERT INTO Favorits (id_client, id_property)
            VALUES (?, ?)
        ");
        $stmt->execute([$userId, $propertyId]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Already in favorites']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

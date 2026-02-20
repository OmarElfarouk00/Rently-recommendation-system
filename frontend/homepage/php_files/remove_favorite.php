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
$propertyId = $data['property_id'];
$userId = $_SESSION['user_id'];

try {
    
    // Remove from favorites
    $stmt = $pdo->prepare("
        DELETE FROM favorits 
        WHERE id_client = ? AND id_property = ?
    ");
    $stmt->execute([$userId, $propertyId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Favorite not found']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
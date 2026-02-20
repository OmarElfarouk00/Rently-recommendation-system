<?php session_start();
require 'config.php';

$userId = $_SESSION['user_id']; // adjust depending on your login system
$action = $_GET['action'] ?? $_POST['action'] ?? 'fetch';

if ($action == 'fetch') {
    $sql = "SELECT id, message, timestamp, is_read FROM Notifications WHERE user_id = ? ORDER BY timestamp DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($notifications);
    $unreadCount = 0;

//     $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND user_id = :user_id");
//     $stmt->execute(['user_id' => $_SESSION['user_id']]);
//     $unreadCount = $stmt->fetchColumn();

// if ($unreadCount > 0) {
//     echo json_encode($unreadCount);
// }
} elseif ($action === 'clear') {
    // Clear all notifications
    $stmt = $pdo->prepare("DELETE FROM Notifications WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true]);
} elseif ($action === 'mark_read') {
    $stmt = $pdo->prepare("UPDATE Notifications SET is_read = 1 WHERE user_id = ?
");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
?>
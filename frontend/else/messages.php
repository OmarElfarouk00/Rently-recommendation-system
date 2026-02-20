<?php
session_start();
require_once 'config.php';

// Check if client is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // Fetch client's negotiations and related messages from owners
    $stmt = $pdo->prepare("
    SELECT 
        n.id AS negotiation_id,
        n.proposedPrice,
        n.status,
        p.title AS property_title,
        p.estimatePrice AS property_price,
        m.message,
        m.sent_at
    FROM Negotiation n
    JOIN Property p ON n.id_property = p.id_property
    JOIN Messages m ON m.id_negotiation = n.id AND m.sender_role = 'owner'
    WHERE n.id_client = ?
    ORDER BY m.sent_at DESC
");
    $stmt->execute([$userId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Failed to fetch messages: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while retrieving your messages.";
    echo "DB Error: " . $e->getMessage();
        exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Messages</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .message-box { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .property-title { font-size: 1.2em; font-weight: bold; }
        .timestamp { color: #888; font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>Messages from Property Owners</h1>

    <?php if (empty($messages)): ?>
        <p>No messages found.</p>
    <?php else: ?>
        <?php foreach ($messages as $msg): ?>
            <div class="message-box">
                <div class="property-title"><?= htmlspecialchars($msg['property_title']) ?></div>
                <p><strong>Your Offer:</strong> $<?= number_format($msg['proposedPrice'], 2) ?></p>
                <p><strong>Owner's Message:</strong> <?= htmlspecialchars($msg['message'])?></p>
                <div class="timestamp"><?= $msg['sent_at'] ? "Sent at: " . htmlspecialchars($msg['sent_at']) : "No response yet" ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>

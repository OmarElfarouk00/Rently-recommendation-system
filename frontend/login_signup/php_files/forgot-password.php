<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    try {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
        $stmt->execute([$token, $expiry, $email]);
        
        if ($stmt->rowCount() > 0) {
            // Send reset email (implement your email sending logic here)
            $resetLink = "https://yourwebsite.com/reset-password.php?token=" . $token;
            // sendResetEmail($email, $resetLink);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Email not found']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}
?>


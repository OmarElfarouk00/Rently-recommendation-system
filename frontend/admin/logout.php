<?php
session_start();
require_once '../homepage/php files/config.php';

// Log admin logout
if(isset($_SESSION['admin_id'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO AdminLog (id_admin, action, ip_address) VALUES (?, 'logout', ?)");
        $stmt->execute([$_SESSION['admin_id'], $_SERVER['REMOTE_ADDR']]);
    } catch(PDOException $e) {
        // Just log the error, don't stop the logout process
        error_log("Error logging admin logout: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
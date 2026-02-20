<?php
require_once 'includes/init.php';

// Log the logout action
if (isAdminLoggedIn()) {
    logAdminAction('logout', 'Admin logout');
}

// Logout admin
logoutAdmin();

// Redirect to login page
header("Location: login.php");
exit;
?>

<?php
// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone
date_default_timezone_set('UTC');

// Include required files
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Set content type for AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
}

// Check for AJAX requests
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Handle CSRF token for AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjaxRequest()) {
    // Skip CSRF check for login page
    $currentScript = basename($_SERVER['SCRIPT_NAME']);
    
    if ($currentScript !== 'login.php' && $currentScript !== 'ajax/get_stats.php' && $currentScript !== 'ajax/get_charts.php') {
        // Check CSRF token
        $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        
        if (!verifyCSRFToken($csrfToken)) {
            sendJsonResponse(['error' => 'Invalid CSRF token'], 403);
        }
    }
}
?>

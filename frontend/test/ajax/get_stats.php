<?php
// Include initialization file
require_once '../includes/init.php';

// Require admin login
if (!isAdminLoggedIn()) {
    sendJsonResponse(['error' => 'Unauthorized'], 401);
}

// Get statistics
$stats = [
    'totalClients' => getTotalClients(),
    'totalPropertyOwners' => getTotalPropertyOwners(),
    'totalVIPPropertyOwners' => getTotalVIPPropertyOwners(),
    'propertiesByType' => getTotalPropertiesByType(),
    'totalActiveRentals' => getTotalActiveRentals(),
    'negotiationStatusCounts' => getNegotiationStatusCounts(),
    'totalRevenue' => getTotalRevenue()
];

// Send response
sendJsonResponse($stats);
?>

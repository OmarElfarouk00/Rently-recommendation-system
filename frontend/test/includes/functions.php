<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sanitize input
function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    return $input;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Format date
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

// Format currency
function formatCurrency($amount, $currency = '€') {
    return $currency . ' ' . number_format($amount, 2, '.', ',');
}

// Get total clients
function getTotalClients() {
    return fetchValue("SELECT COUNT(*) FROM Client");
}

// Get total property owners
function getTotalPropertyOwners() {
    return fetchValue("SELECT COUNT(*) FROM PropertyOwner");
}

// Get total VIP property owners
function getTotalVIPPropertyOwners() {
    return fetchValue("SELECT COUNT(*) FROM PropertyOwner_VIP WHERE vipEndDate >= CURDATE()");
}

// Get total properties by type
function getTotalPropertiesByType() {
    $sql = "SELECT 
                SUM(CASE WHEN p.propertyType = 'Room' THEN 1 ELSE 0 END) as rooms,
                SUM(CASE WHEN p.propertyType = 'Apartment' THEN 1 ELSE 0 END) as apartments,
                SUM(CASE WHEN p.propertyType = 'Villa' THEN 1 ELSE 0 END) as villas,
                SUM(CASE WHEN p.propertyType = 'House' THEN 1 ELSE 0 END) as houses
            FROM Property p";
    
    return fetchRow($sql);
}

// Get total active rentals
function getTotalActiveRentals() {
    return fetchValue("SELECT COUNT(*) FROM Rental WHERE status = 'active'");
}

// Get negotiation status counts
function getNegotiationStatusCounts() {
    $sql = "SELECT 
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM Negotiation";
    
    return fetchRow($sql);
}

// Get total revenue
function getTotalRevenue() {
    return fetchValue("SELECT SUM(finalPrice) FROM ValidateNegotiation");
}

// Get monthly client registrations (for the last 12 months)
function getMonthlyClientRegistrations() {
    $sql = "SELECT 
                DATE_FORMAT(registrationDate, '%Y-%m') as month,
                COUNT(*) as count
            FROM Client
            WHERE registrationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(registrationDate, '%Y-%m')
            ORDER BY month";
    
    return fetchRows($sql);
}

// Get properties by city
function getPropertiesByCity() {
    $sql = "SELECT 
                city,
                COUNT(*) as count
            FROM Property
            GROUP BY city
            ORDER BY count DESC
            LIMIT 10";
    
    return fetchRows($sql);
}

// Get top rented properties
function getTopRentedProperties() {
    $sql = "SELECT 
                p.id_property,
                p.title,
                COUNT(r.id_property) as rental_count
            FROM Property p
            JOIN Rental r ON p.id_property = r.id_property
            GROUP BY p.id_property
            ORDER BY rental_count DESC
            LIMIT 5";
    
    return fetchRows($sql);
}

// Get latest registered clients
function getLatestClients($limit = 5) {
    $sql = "SELECT 
                id_client,
                fullname,
                email,
                phone,
                registrationDate
            FROM Client
            ORDER BY registrationDate DESC
            LIMIT ?";
    
    return fetchRows($sql, "i", [$limit]);
}

// Get latest negotiations
function getLatestNegotiations($limit = 5) {
    $sql = "SELECT 
                n.id_negotiation,
                n.proposedPrice,
                n.status,
                n.proposedDate,
                c.fullname as client_name,
                p.title as property_title
            FROM Negotiation n
            JOIN Client c ON n.id_client = c.id_client
            JOIN Property p ON n.id_property = p.id_property
            ORDER BY n.proposedDate DESC
            LIMIT ?";
    
    return fetchRows($sql, "i", [$limit]);
}

// Get latest property reports
function getLatestPropertyReports($limit = 5) {
    $sql = "SELECT 
                pr.report_type,
                pr.report_date,
                c.fullname as client_name,
                p.title as property_title
            FROM PropertyReport pr
            JOIN Client c ON pr.id_client = c.id_client
            JOIN Property p ON pr.id_property = p.id_property
            ORDER BY pr.report_date DESC
            LIMIT ?";
    
    return fetchRows($sql, "i", [$limit]);
}

// Search clients
function searchClients($search) {
    $search = "%$search%";
    
    $sql = "SELECT 
                id_client,
                fullname,
                email,
                phone,
                registrationDate
            FROM Client
            WHERE fullname LIKE ? OR email LIKE ? OR id_client LIKE ?
            ORDER BY fullname
            LIMIT 20";
    
    return fetchRows($sql, "sss", [$search, $search, $search]);
}

// Search properties
function searchProperties($search, $type = null, $city = null, $status = null) {
    $search = "%$search%";
    $params = [$search, $search];
    $types = "ss";
    
    $sql = "SELECT 
                p.id_property,
                p.title,
                p.propertyType,
                p.city,
                p.status,
                p.estimatedPrice,
                po.id_PropertyOwner
            FROM Property p
            JOIN PropertyOwner po ON p.id_propertyOwner = po.id_PropertyOwner
            WHERE (p.title LIKE ? OR p.id_property LIKE ?)";
    
    if ($type) {
        $sql .= " AND p.propertyType = ?";
        $params[] = $type;
        $types .= "s";
    }
    
    if ($city) {
        $sql .= " AND p.city = ?";
        $params[] = $city;
        $types .= "s";
    }
    
    if ($status) {
        $sql .= " AND p.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $sql .= " ORDER BY p.title LIMIT 20";
    
    return fetchRows($sql, $types, $params);
}

// Delete client
function deleteClient($clientId) {
    // Check if client has active rentals
    $activeRentals = fetchValue(
        "SELECT COUNT(*) FROM Rental WHERE id_client = ? AND status = 'active'",
        "i",
        [$clientId]
    );
    
    if ($activeRentals > 0) {
        return [
            'success' => false,
            'message' => 'Cannot delete client with active rentals'
        ];
    }
    
    // Begin transaction
    $conn = getDBConnection();
    $conn->begin_transaction();
    
    try {
        // Delete client's favorites
        executeQuery("DELETE FROM Favorits WHERE id_client = ?", "i", [$clientId]);
        
        // Delete client's comments
        executeQuery("DELETE FROM Comments WHERE id_client = ?", "i", [$clientId]);
        
        // Delete client's property reports
        executeQuery("DELETE FROM PropertyReport WHERE id_client = ?", "i", [$clientId]);
        
        // Delete client's negotiations
        $negotiations = fetchRows(
            "SELECT id_negotiation FROM Negotiation WHERE id_client = ?",
            "i",
            [$clientId]
        );
        
        foreach ($negotiations as $negotiation) {
            $negotiationId = $negotiation['id_negotiation'];
            
            // Delete validated negotiations
            executeQuery(
                "DELETE FROM ValidateNegotiation WHERE id_negotiation = ?",
                "i",
                [$negotiationId]
            );
            
            // Delete responses
            executeQuery(
                "DELETE FROM Response WHERE id_negotiation = ?",
                "i",
                [$negotiationId]
            );
        }
        
        // Delete negotiations
        executeQuery("DELETE FROM Negotiation WHERE id_client = ?", "i", [$clientId]);
        
        // Delete rentals
        $rentals = fetchRows(
            "SELECT id_rental FROM Rental WHERE id_client = ?",
            "i",
            [$clientId]
        );
        
        foreach ($rentals as $rental) {
            $rentalId = $rental['id_rental'];
            
            // Delete valid rentals
            executeQuery(
                "DELETE FROM Valid WHERE id_rental = ?",
                "i",
                [$rentalId]
            );
        }
        
        // Delete rentals
        executeQuery("DELETE FROM Rental WHERE id_client = ?", "i", [$clientId]);
        
        // Finally, delete the client
        executeQuery("DELETE FROM Client WHERE id_client = ?", "i", [$clientId]);
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Client deleted successfully'
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        return [
            'success' => false,
            'message' => 'Error deleting client: ' . $e->getMessage()
        ];
    }
}

// Delete property owner
function deletePropertyOwner($ownerId) {
    // Check if owner has properties
    $properties = fetchValue(
        "SELECT COUNT(*) FROM Property WHERE id_propertyOwner = ?",
        "i",
        [$ownerId]
    );
    
    if ($properties > 0) {
        return [
            'success' => false,
            'message' => 'Cannot delete owner with properties'
        ];
    }
    
    // Begin transaction
    $conn = getDBConnection();
    $conn->begin_transaction();
    
    try {
        // Delete VIP status
        executeQuery(
            "DELETE FROM PropertyOwner_VIP WHERE id_propertyOwner = ?",
            "i",
            [$ownerId]
        );
        
        // Delete property owner
        executeQuery(
            "DELETE FROM PropertyOwner WHERE id_PropertyOwner = ?",
            "i",
            [$ownerId]
        );
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Property owner deleted successfully'
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        return [
            'success' => false,
            'message' => 'Error deleting property owner: ' . $e->getMessage()
        ];
    }
}

// Promote property owner to VIP
function promoteOwnerToVIP($ownerId, $months = 3) {
    // Check if owner exists
    $ownerExists = fetchValue(
        "SELECT COUNT(*) FROM PropertyOwner WHERE id_PropertyOwner = ?",
        "i",
        [$ownerId]
    );
    
    if ($ownerExists == 0) {
        return [
            'success' => false,
            'message' => 'Property owner not found'
        ];
    }
    
    // Check if owner is already VIP
    $isVIP = fetchRow(
        "SELECT id_propertyOwner_VIP, vipEndDate FROM PropertyOwner_VIP 
         WHERE id_propertyOwner = ? AND vipEndDate >= CURDATE()",
        "i",
        [$ownerId]
    );
    
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+$months months"));
    
    if ($isVIP) {
        // Extend VIP period
        $newEndDate = date('Y-m-d', strtotime($isVIP['vipEndDate'] . " +$months months"));
        
        $result = updateData(
            'PropertyOwner_VIP',
            ['vipEndDate' => $newEndDate],
            'id_propertyOwner_VIP = ?',
            'i',
            [$isVIP['id_propertyOwner_VIP']]
        );
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'VIP status extended successfully',
                'endDate' => $newEndDate
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error extending VIP status'
            ];
        }
    } else {
        // Add new VIP status
        $data = [
            'id_propertyOwner' => $ownerId,
            'vipStartDate' => $startDate,
            'vipEndDate' => $endDate
        ];
        
        $result = insertData('PropertyOwner_VIP', $data);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Owner promoted to VIP successfully',
                'endDate' => $endDate
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error promoting owner to VIP'
            ];
        }
    }
}

// Block or hide a property
function blockProperty($propertyId, $status = 'blocked') {
    // Check if property exists
    $propertyExists = fetchValue(
        "SELECT COUNT(*) FROM Property WHERE id_property = ?",
        "i",
        [$propertyId]
    );
    
    if ($propertyExists == 0) {
        return [
            'success' => false,
            'message' => 'Property not found'
        ];
    }
    
    // Update property status
    $result = updateData(
        'Property',
        ['status' => $status],
        'id_property = ?',
        'i',
        [$propertyId]
    );
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Property ' . $status . ' successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error updating property status'
        ];
    }
}

// Get negotiation messages
function getNegotiationMessages($negotiationId) {
    // Check if negotiation exists
    $negotiationExists = fetchValue(
        "SELECT COUNT(*) FROM Negotiation WHERE id_negotiation = ?",
        "i",
        [$negotiationId]
    );
    
    if ($negotiationExists == 0) {
        return [
            'success' => false,
            'message' => 'Negotiation not found'
        ];
    }
    
    // Get negotiation details
    $negotiation = fetchRow(
        "SELECT 
            n.id_negotiation,
            n.id_client,
            n.id_property,
            n.proposedPrice,
            n.comments,
            n.status,
            n.proposedDate,
            n.duration,
            c.fullname as client_name,
            p.title as property_title,
            po.id_PropertyOwner as owner_id
         FROM Negotiation n
         JOIN Client c ON n.id_client = c.id_client
         JOIN Property p ON n.id_property = p.id_property
         JOIN PropertyOwner po ON p.id_propertyOwner = po.id_PropertyOwner
         WHERE n.id_negotiation = ?",
        "i",
        [$negotiationId]
    );
    
    // Get messages
    $messages = fetchRows(
        "SELECT 
            id,
            sender_role,
            message,
            send_at,
            priceOffer,
            proposedDate
         FROM Response
         WHERE id_negotiation = ?
         ORDER BY send_at ASC",
        "i",
        [$negotiationId]
    );
    
    return [
        'success' => true,
        'negotiation' => $negotiation,
        'messages' => $messages
    ];
}

// Get property cities
function getPropertyCities() {
    $sql = "SELECT DISTINCT city FROM Property ORDER BY city";
    $cities = fetchRows($sql);
    
    $result = [];
    foreach ($cities as $city) {
        $result[] = $city['city'];
    }
    
    return $result;
}

// Get property types
function getPropertyTypes() {
    return ['Room', 'Apartment', 'Villa', 'House'];
}

// Get property statuses
function getPropertyStatuses() {
    return ['available', 'rented', 'pending', 'blocked', 'hidden'];
}

// Log admin action
function logAdminAction($action, $details = '') {
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    $adminId = $_SESSION['admin_id'];
    $adminUsername = $_SESSION['admin_username'];
    
    // Check if AdminLog table exists
    $conn = getDBConnection();
    $tableExists = $conn->query("SHOW TABLES LIKE 'AdminLog'")->num_rows > 0;
    
    if (!$tableExists) {
        // Create AdminLog table
        $sql = "CREATE TABLE AdminLog (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            admin_username VARCHAR(50) NOT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45) NOT NULL,
            user_agent VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->query($sql);
    }
    
    // Get client IP and user agent
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    // Insert log entry
    $data = [
        'admin_id' => $adminId,
        'admin_username' => $adminUsername,
        'action' => $action,
        'details' => $details,
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent
    ];
    
    return insertData('AdminLog', $data);
}
?>

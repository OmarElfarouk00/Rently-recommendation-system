<?php
require_once 'config.php';
header('Content-Type: application/json');
session_start();
$userid = isset($_SESSION['user_id'])? $_SESSION['user_id']:null ;



try {
    // Validate and sanitize inputs
    // $type = isset($_GET['type']) && in_array($_GET['type'], ['renting', 'selling']) 
    //         ? $_GET['type'] 
    //         : 'renting';
    $category = isset($_GET['category']) && in_array($_GET['category'], ['villa', 'apartment', 'house', 'room']) 
            ? $_GET['category'] 
            : null;
            $i=0;
    
    $filters = [
        'bedrooms' => isset($_GET['bedrooms']) ? (int)$_GET['bedrooms'] : null,
        'bathrooms' => isset($_GET['bathrooms']) ? (int)$_GET['bathrooms'] : null,
        'min_price' => isset($_GET['min_price']) ? (float)$_GET['min_price'] : null,
        'max_price' => isset($_GET['max_price']) ? (float)$_GET['max_price'] : null,
        'city' => isset($_GET['city']) ? $_GET['city'] : null,
        'size' => isset($_GET['size']) ? $_GET['size'] : null,
        'propertyType' => isset($_GET['propertyType']) ? $_GET['propertyType'] : null,
        'type' => isset($_GET['type']) && in_array($_GET['type'], ['renting', 'selling' ]) ? $_GET['type'] : 'renting'
        // 'searchTerm' => $_GET['searchTerm'] ?? null
    ];


    // Base query
    $query = "SELECT 
        p.*,
        EXISTS (
        SELECT 1 
        FROM Favorits f 
        WHERE f.id_property = p.id_property AND f.id_client = :userid
    ) AS is_favorite,
        c.full_name AS owner_name,
        (
            SELECT pi.image_path 
            FROM property_images pi 
            WHERE pi.property_id = p.id_property 
            ORDER BY pi.image_order ASC 
            LIMIT 1
        ) AS main_image
    FROM property p
    JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
    JOIN Client c ON po.id_propertyOwner = c.id_client
    WHERE (p.status = 'available' or p.status = 'rented' or p.status = 'pending')
";
    
$params = [':userid' => $userid];
    // Add category filter if provided
    if ($category !== null) {
        $query .= " AND p.propertyType = :category";
        $params[':category'] = $category;
    }

//     $searchTerm = $filters['searchTerm'];
// if ($searchTerm) {
//     $query .= " AND (p.title LIKE :searchTerm OR p.description LIKE :searchTerm)";
//     $params[':searchTerm'] = '%' . $searchTerm . '%';
// }

    // Add other filters
    if ($filters['bedrooms'] !== null) {
        $query .= " AND p.bedrooms >= :bedrooms";
        $params[':bedrooms'] = $filters['bedrooms'];
    }
    
    if ($filters['bathrooms'] !== null) {
        $query .= " AND p.bathrooms >= :bathrooms";
        $params[':bathrooms'] = $filters['bathrooms'];
    }
    
    if ($filters['min_price'] !== null) {
        $query .= " AND p.estimatePrice >= :min_price";
        $params[':min_price'] = $filters['min_price'];
    }
    
    if ($filters['max_price'] !== null) {
        $query .= " AND p.estimatePrice <= :max_price";
        $params[':max_price'] = $filters['max_price'];
    }
    
    if ($filters['city'] !== null && !empty($filters['city'])) {
        $query .= " AND p.city LIKE :city";
        $params[':city'] = '%' . $filters['city'] . '%';
    }


  
    if($filters['type'] !== null && !empty($filters['type'])) {
        $query .= " AND p.ownerNeeds = :type";
        $params[':type'] = $filters['type'];
    }

    if($filters['size'] !== null && !empty($filters['size'])) {
        $query .= " AND p.size = :size";
        $params[':size'] = $filters['size'];
    }

    if($filters['propertyType'] !== null && !empty($filters['propertyType'])) {
        $query .= " AND p.propertyType = :propertyType";
        $params[':propertyType'] = $filters['propertyType'];
    }


    // Order by price
    $query .= " ORDER BY p.estimatePrice ASC";

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return response
    echo json_encode([
        'status' => !empty($properties) ? 'success' : 'empty',
        'message' => !empty($properties) ? '' : 'No properties found matching your criteria',
        'properties' => $properties
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
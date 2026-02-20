<?php
ob_clean();
header('Content-Type: application/json');

require_once 'config.php';

$query = $_GET['query'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}
// clear the page
ob_clean();

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.id_property, 
            p.title, 
            p.address,
            p.city,
            p.country,
            p.propertyType,
            p.bedrooms,
            p.bathrooms,
            p.size,
            p.estimatePrice,
            i.image_path as main_image
        FROM Property p
        LEFT JOIN property_images i ON p.id_property = i.property_id and image_order = 1
        WHERE 
            title LIKE :query OR 
            address LIKE :query OR 
            city LIKE :query OR  
            country LIKE :query OR 
            propertyType LIKE :query OR
            description LIKE :query 
        LIMIT 5
    ");
    $stmt->execute(['query' => '%' . $query . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
    exit;

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

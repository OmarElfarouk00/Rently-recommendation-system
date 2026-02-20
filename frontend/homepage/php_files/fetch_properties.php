<?php
require_once 'config.php';
session_start();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$recommend = $_GET['recommend'] ?? false;

// // Get the request body
// $requestBody = file_get_contents('php://input');
// $requestData = json_decode($requestBody, true);

// // Get the type of properties to fetch (renting or selling)
// $type = isset($requestData['type']) ? $requestData['type'] : '';
// if($type!==''){


// // Validate the type
// if ($type !== 'renting' && $type !== 'selling') {
//     echo json_encode(['success' => false, 'message' => 'Invalid property type.']);
//     exit();
// }

// try {
//     // Fetch properties based on the type
//     $stmt = $pdo->prepare("
//         SELECT 
//             p.id_property,
//             p.title,
//             p.description,
//             p.propertyType,
//             p.size,
//             p.bedrooms,
//             p.bathrooms,
//             p.address,
//             p.city,
//             p.state,
//             p.country,
//             p.socialCode,
//             p.estimatePrice,
//             p.status,
//             p.ownerNeeds,
//             c.full_name AS owner_name
//         FROM Property p
//         JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
//         JOIN Client c ON po.id_propertyOwner = c.id_client
//         WHERE p.ownerNeeds = ?
//     ");
//     $stmt->execute([$type]);
//     $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

//     // Return the properties as JSON
//     echo json_encode(['success' => true, 'properties' => $properties]);
// } catch (PDOException $e) {
//     echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
// }
// }else{




try {
    $recommended_ids = [];
    if ($user_id != null) {



        if ($recommend) {
            // Step 1: Get recommended property IDs from the Python API
            $api_url = "http://127.0.0.1:5050/recommend?user_id=$user_id";
            $response = file_get_contents($api_url);
            $data = json_decode($response, true);
            $recommended_ids = $data['recommended_property_ids'] ?? [];
        }

        $properties = [];

        // Step 2: Fetch recommended properties (if any)
        if (!empty($recommended_ids)) {
            $placeholders = str_repeat('?,', count($recommended_ids) - 1) . '?';
            $sql = "
            SELECT 
                p.*,
                EXISTS (
                    SELECT 1 
                    FROM Favorits f 
                    WHERE f.id_property = p.id_property AND f.id_client = ?
                ) AS is_favorite,
                c.full_name AS owner_name,
                CASE 
                    WHEN pov.id_propertyOwner_VIP IS NOT NULL THEN true
                    ELSE false
                END AS is_vip,
                (
                    SELECT image_path 
                    FROM Property_Images 
                    WHERE property_id = p.id_property 
                    ORDER BY image_order ASC 
                    LIMIT 1
                ) AS main_image
            FROM Property p
            JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
            JOIN Client c ON po.id_propertyOwner = c.id_client
            LEFT JOIN propertyOwner_VIP pov ON po.id_propertyOwner = pov.id_propertyOwner
            WHERE p.id_property IN ($placeholders)
        ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge([$user_id], $recommended_ids));
            $recommended_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $properties = $recommended_properties;

            foreach ($properties as &$prop) {
                $prop['is_recommended'] = true;
            }
        }
    }

    // Step 3: Fetch remaining properties NOT in recommended list
    $whereNotIn = '';
    $params = [$user_id];

    if (!empty($recommended_ids)) {
        $notInPlaceholders = str_repeat('?,', count($recommended_ids) - 1) . '?';
        $whereNotIn = "AND p.id_property NOT IN ($notInPlaceholders)";
        $params = array_merge($params, $recommended_ids);
    }

    $sql = "
        SELECT 
            p.*,
            EXISTS (
                SELECT 1 
                FROM Favorits f 
                WHERE f.id_property = p.id_property AND f.id_client = ?
            ) AS is_favorite,
            c.full_name AS owner_name,
            CASE 
                WHEN pov.id_propertyOwner_VIP IS NOT NULL THEN true
                ELSE false
            END AS is_vip,
            (
                SELECT image_path 
                FROM Property_Images 
                WHERE property_id = p.id_property 
                ORDER BY image_order ASC 
                LIMIT 1
            ) AS main_image
        FROM Property p
        JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
        JOIN Client c ON po.id_propertyOwner = c.id_client
        LEFT JOIN propertyOwner_VIP pov ON po.id_propertyOwner = pov.id_propertyOwner
        WHERE 1=1 $whereNotIn
        ORDER BY 
            CASE WHEN pov.id_propertyOwner_VIP IS NOT NULL THEN 1 ELSE 2 END,
            p.estimatePrice ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $other_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // // Step 4: Merge results — recommended first
    // $properties = array_merge($properties, $other_properties);

    foreach ($other_properties as &$prop) {
        $prop['is_recommended'] = false;
    }
    if($user_id!= null){
            $properties = array_merge($properties, $other_properties);
    }else{
        $properties = $other_properties;
    }

    header('Content-Type: application/json');
    echo json_encode($properties);

} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}

// }
?>
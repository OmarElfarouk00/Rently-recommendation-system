<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Get user data
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM client WHERE id_client = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        throw new Exception("User not found.");
    }

    // Check if user is VIP
    $stmtVIP = $pdo->prepare("SELECT pov.* FROM propertyOwner_VIP pov JOIN propertyOwner po ON pov.id_propertyOwner = po.id_propertyOwner WHERE po.id_propertyOwner = ? ");
    $stmtVIP->execute([$userId]);
    $isVIP = $stmtVIP->rowCount() > 0;

    // Validate number of images
    $maxImages = $isVIP ? PHP_INT_MAX : 3;
    if (isset($_FILES['property_images']) && count($_FILES['property_images']['name']) > $maxImages) {
        // throw new Exception("Non-VIP users can only upload up to 3 images.");
        $_SESSION['error_message'] = "Non-VIP users can only upload up to 3 images.";
        header('Location: ../become-host.php');
        exit();
    }

    // Create owner record if doesn't exist
    $stmtCheckOwner = $pdo->prepare("SELECT id_propertyOwner FROM propertyOwner WHERE id_propertyOwner = ?");
    $stmtCheckOwner->execute([$userId]);
    $ownerData = $stmtCheckOwner->fetch();

    if (!$ownerData) {
        $stmtCreateOwner = $pdo->prepare("INSERT INTO propertyOwner (id_propertyOwner) VALUES (?)");
        $stmtCreateOwner->execute([$userId]);
    }
    $propertyType = $_POST['propertyType'];

    // Insert property
    $stmt = $pdo->prepare("INSERT INTO property (title, description, propertyType, size, bedrooms, bathrooms, address, city, country, socialCode, estimatePrice, status, ownerNeeds, id_propertyOwner) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', ?, ?)");
    $stmt->execute([
        $_POST['title'],
        $_POST['description'],
        $_POST['propertyType'],
        $_POST['size'],
        $_POST['bedrooms'],
        $_POST['bathrooms'],
        $_POST['address'],
        $_POST['city'],
        $_POST['country'],
        $_POST['socialCode'],
        $_POST['estimatePrice'],
        $_POST['ownerNeeds'],
        $userId
    ]);

    $propertyId = $pdo->lastInsertId();

        // Handle property type specific details
        switch ($propertyType) {
            case 'apartment':
                $stmt = $pdo->prepare("
                    INSERT INTO Apartment (
                        id_property, floor_number, building_name, has_elevator, 
                        has_parking, monthly_maintenance_fee
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $propertyId,
                    $_POST['apartment_floor'] ?? null,
                    $_POST['apartment_building_name'] ?? null,
                    $_POST['apartment_has_elevator'] ?? 0,
                    $_POST['apartment_has_parking'] ?? 0,
                    $_POST['apartment_monthly_fees'] ?? null
                ]);
                break;
                
            case 'house':
                $stmt = $pdo->prepare("
                    INSERT INTO House (
                        id_property, floors, garden_size, has_garage, 
                        garage_capacity, has_basement
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $propertyId,
                    $_POST['house_floors'] ?? 1,
                    $_POST['house_garden_size'] ?? null,
                    $_POST['house_has_garage'] ?? 0,
                    $_POST['house_garage_capacity'] ?? null,
                    $_POST['house_has_basement'] ?? 0
                ]);
                break;
                
            case 'villa':
                $stmt = $pdo->prepare("
                    INSERT INTO Villa (
                        id_property, floors, garden_size, has_pool, 
                        has_garage, garage_capacity, has_security_system
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $propertyId,
                    $_POST['villa_floors'] ?? 1,
                    $_POST['villa_garden_size'] ?? null,
                    $_POST['villa_has_pool'] ?? 0,
                    $_POST['villa_has_garage'] ?? 0,
                    $_POST['villa_garage_capacity'] ?? null,
                    $_POST['villa_security_system'] ?? 0
                ]);
                break;
                
            case 'room':
                $stmt = $pdo->prepare("
                    INSERT INTO Room (
                        id_property, room_type, floor_number, has_private_bathroom, 
                        has_private_kitchen, is_furnished
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $propertyId,
                    $_POST['room_type'] ?? 'private',
                    $_POST['room_floor'] ?? 0,
                    $_POST['room_has_private_bathroom'] ?? 0,
                    $_POST['room_has_private_kitchen'] ?? 0,
                    $_POST['room_furnished'] ?? 0
                ]);
                break;
        }
    $_SESSION['success_message'] = "Property added successfully!";

    // Insert into rental, buying, or selling based on ownerNeeds

    // if ($_POST['ownerNeeds'] === 'renting') {
    //     $stmt = $pdo->prepare("INSERT INTO rental (id_client, id_property) VALUES (?, ?)");
    //     $stmt->execute([$userId, $propertyId]);
    // } else
if ($_POST['ownerNeeds'] === 'selling') {
        $stmt = $pdo->prepare("INSERT INTO selling (id_client, id_property) VALUES (?, ?)");
        $stmt->execute([$userId, $propertyId]);
    }

    // Handle image uploads
    $uploadedFiles = [];
    if (isset($_FILES['property_images'])) {
        $stmtImage = $pdo->prepare("INSERT INTO Property_Images (property_id, image_path, image_order) VALUES (?, ?, ?)");

        foreach ($_FILES['property_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['property_images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileData = [
                    'name' => $_FILES['property_images']['name'][$key],
                    'tmp_name' => $tmp_name,
                    'size' => $_FILES['property_images']['size'][$key]
                ];

                $imagePath = handleImageUpload($fileData, $propertyId);
                $stmtImage->execute([$propertyId, $imagePath, $key + 1]);
                $uploadedFiles[] = ['path' => $imagePath, 'order' => $key + 1];
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Property listed successfully.', 'property_id' => $propertyId, 'images' => $uploadedFiles]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleImageUpload($file, $propertyId) {
    $target_dir = "uploads/properties/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = $propertyId . '_' . uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (!move_uploaded_file($file["tmp_name"], $target_file)) {
        throw new Exception("Failed to upload image.");
    }
    return $target_file;
}

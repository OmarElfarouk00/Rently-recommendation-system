<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login-signup/index.php');
    exit();
}
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'];

// Check if property ID is provided
if (!isset($_GET['id'])) {
    header('Location: my-properties.php');
    exit();
}

$propertyId = $_GET['id'];

// Check if the user owns this property
try {
    $stmt = $pdo->prepare("
        SELECT * FROM Property 
        WHERE id_property = ? 
    ");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        $_SESSION['error'] = "You don't have permission to edit this property.";
        header('Location: my-properties.php');
        exit();
    }

    // Fetch property details based on the propertyType
    if ($property['propertyType'] == 'house') {
        $stmt = $pdo->prepare("SELECT * FROM house WHERE id_property = ?");
        $stmt->execute([$propertyId]);
        $propertyDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($property['propertyType'] == 'apartment') {
        $stmt = $pdo->prepare("SELECT * FROM apartment WHERE id_property = ?");
        $stmt->execute([$propertyId]);
        $propertyDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($property['propertyType'] == 'villa') {
        $stmt = $pdo->prepare("SELECT * FROM villa WHERE id_property = ?");
        $stmt->execute([$propertyId]);
        $propertyDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM room WHERE id_property = ?");
        $stmt->execute([$propertyId]);
        $propertyDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        $ii = 2;
    }

    // Fetch property images
    $stmt = $pdo->prepare("SELECT id,image_path, image_order FROM Property_images WHERE property_id = ? ORDER BY image_order ASC");
    $stmt->execute([$propertyId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while retrieving property details.";
    header('Location: my-properties.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Common fields for all property types
        $title = $_POST['title'];
        $description = $_POST['description'];
        $size = $_POST['size'];
        $bedrooms = $_POST['bedrooms'];
        $bathrooms = $_POST['bathrooms'];
        $address = $_POST['address'];
        $city = $_POST['city'];
        $country = $_POST['country'];
        $socialCode = $_POST['socialCode'];
        $estimatePrice = $_POST['estimatePrice'];
        $status = $_POST['status'];
        $ownerNeeds = $_POST['ownerNeeds'];

        // Update property details
        $stmt = $pdo->prepare("
            UPDATE Property SET
                title = ?,
                description = ?,
                size = ?,
                bedrooms = ?,
                bathrooms = ?,
                address = ?,
                city = ?,
                country = ?,
                socialCode = ?,
                estimatePrice = ?,
                status = ?,
                ownerNeeds = ?
            WHERE id_property = ? 
        ");

        $stmt->execute([
            $title,
            $description,
            $size,
            $bedrooms,
            $bathrooms,
            $address,
            $city,
            $country,
            $socialCode,
            $estimatePrice,
            $status,
            $ownerNeeds,
            $propertyId,

        ]);
        $ii = 3;

        // Update specific property type details
// Update specific property type details
        if ($property['propertyType'] == 'house') {
            $gardenSize = $_POST['garden_size'];
            $floors = $_POST['floors'];
            $hasGarage = isset($_POST['has_garage']) && $_POST['has_garage'] == 'yes' ? 1 : 0;
            $garageCapacity = $_POST['garage_capacity'];
            $hasBasement = isset($_POST['has_basement']) && $_POST['has_basement'] == 'yes' ? 1 : 0;

            $stmt = $pdo->prepare("
        UPDATE House SET
            garden_size = ?,
            floors = ?,
            has_garage = ?,
            garage_capacity = ?,
            has_basement = ?
        WHERE id_property = ?
    ");

            $stmt->execute([
                $gardenSize,
                $floors,
                $hasGarage,
                $garageCapacity,
                $hasBasement,
                $propertyId
            ]);

        } elseif ($property['propertyType'] == 'apartment') {
            $floorNumber = $_POST['floor_number'];
            $hasElevator = isset($_POST['has_elevator']) && $_POST['has_elevator'] == 'yes' ? 1 : 0;
            $hasParking = isset($_POST['has_parking']) && $_POST['has_parking'] == 'yes' ? 1 : 0;
            $monthlyFees = $_POST['monthly_maintenance_fee'];

            $stmt = $pdo->prepare("
        UPDATE Apartment SET
            floor_number = ?,
            has_elevator = ?,
            has_parking = ?,
            monthly_maintenance_fee = ?
        WHERE id_property = ?
    ");

            $stmt->execute([
                $floorNumber,
                $hasElevator,
                $hasParking,
                $monthlyFees,
                $propertyId
            ]);

        } elseif ($property['propertyType'] == 'villa') {
            $floors = isset($_POST['floors']) ? (int) $_POST['floors'] : null;
            $gardenSize = isset($_POST['garden_size']) ? (float) $_POST['garden_size'] : null;
            $garageCapacity = isset($_POST['garage_capacity']) ? (int) $_POST['garage_capacity'] : null;

            $hasPool = isset($_POST['has_pool']) && $_POST['has_pool'] == 'yes' ? 1 : 0;
            $hasGarage = isset($_POST['has_garage']) && $_POST['has_garage'] == 'yes' ? 1 : 0;
            $hasSecuritySys = isset($_POST['has_security_system']) && $_POST['has_security_system'] == 'yes' ? 1 : 0;

            $stmt = $pdo->prepare("
        UPDATE Villa SET
            floors = ?,
            garden_size = ?,
            has_pool = ?,
            has_garage = ?,
            garage_capacity = ?,
            has_security_system = ?
        WHERE id_property = ?
    ");
            $ii = 4;
            $stmt->execute([
                $floors,
                $gardenSize,
                $hasPool,
                $hasGarage,
                $garageCapacity,
                $hasSecuritySys,
                $propertyId
            ]);
            $ii = 5;

        } elseif ($property['propertyType'] == 'room') {
            $roomType = isset($_POST['room_type']) && $_POST['room_type'] == 'private' ? 'private' : 'shared';
            $floorNumber = isset($_POST['floor_number']) ? $_POST['floor_number'] : null;
            $privateBathroom = isset($_POST['has_private_bathroom']) && $_POST['has_private_bathroom'] == 'yes' ? 1 : 0;
            $privateKitchen = isset($_POST['has_private_kitchen']) && $_POST['has_private_kitchen'] == 'yes' ? 1 : 0;
            $isFurnished = isset($_POST['is_furnished']) && $_POST['is_furnished'] == 'yes' ? 1 : 0;
            $ii = 4;


            $stmt = $pdo->prepare("
                UPDATE room SET
                    room_type = ?,
                    floor_number = ?,
                    has_private_bathroom = ?,
                    has_private_kitchen = ?,
                    is_furnished = ?
                WHERE id_property = ?
            ");
            $ii = 5;

            $stmt->execute([
                $roomType,
                $floorNumber,
                $privateBathroom,
                $privateKitchen,
                $isFurnished,
                $propertyId
            ]);
        }

        function isOwnerVIP($ownerId)
        {
            global $pdo;
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM propertyOwner_VIP WHERE id_propertyOwner = ?");
            $stmt->execute([$ownerId]);
            return $stmt->fetchColumn() > 0;
        }

        function handleImageUpload($file, $propertyId)
        {
            $target_dir = "uploads/properties/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            $new_filename = $propertyId . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;

            if (!move_uploaded_file($file["tmp_name"], $target_file)) {
                throw new Exception("Failed to upload image.");
            }

            return $target_file; // This will be saved into the database
        }
        // Check if user is VIP
        $isVIP = isOwnerVIP($property['id_propertyOwner']);  

        // Get current number of images for this property
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM property_images WHERE property_id = ?");
        $stmt->execute([$propertyId]);
        $currentImageCount = (int) $stmt->fetchColumn();

        // Set max image count for non-VIP users
        $maxImages = $isVIP ? PHP_INT_MAX : 3;

        // Prepare insert statement
        $stmtImage = $pdo->prepare("INSERT INTO Property_Images (property_id, image_path, image_order) VALUES (?, ?, ?)");

        // Image upload logic
        $uploadedFiles = [];
        if (isset($_FILES['property_images'])) {
            foreach ($_FILES['property_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['property_images']['error'][$key] === UPLOAD_ERR_OK) {
                    // Check image limit
                    if ($currentImageCount >= $maxImages) {
                        break; // Stop if image limit is reached
                    }

                    $fileData = [
                        'name' => $_FILES['property_images']['name'][$key],
                        'tmp_name' => $tmp_name,
                        'size' => $_FILES['property_images']['size'][$key]
                    ];

                    try {
                        $imagePath = handleImageUpload($fileData, $propertyId);
                        $imageOrder = $currentImageCount + 1;

                        $stmtImage->execute([$propertyId, $imagePath, $imageOrder]);
                        $uploadedFiles[] = ['path' => $imagePath, 'order' => $imageOrder];

                        $currentImageCount++;
                    } catch (Exception $e) {
                        error_log("Image upload failed: " . $e->getMessage());
                    }
                }
            }
        }








        // Handle image deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
    $deleteImage = $pdo->prepare("
        SELECT image_path FROM property_images 
        WHERE id = ? AND property_id = ?
    ");

    $removeImage = $pdo->prepare("
        DELETE FROM property_images 
        WHERE id = ? AND property_id = ?
    ");

    foreach ($_POST['delete_images'] as $imageId) {
        $imageId = (int)$imageId; // Sanitize input

        // Step 1: Get image path
        $deleteImage->execute([$imageId, $propertyId]);
        $imagePath = $deleteImage->fetchColumn();

        // Step 2: Delete record from database
        $removeImage->execute([$imageId, $propertyId]);

        // Step 3: Remove file from filesystem
        if ($imagePath && file_exists($imagePath)) {
            if (!unlink($imagePath)) {
                error_log("Failed to delete image file: $imagePath");
            }
        }
    }
}

        // Commit transaction
        $pdo->commit();

        // header('Location: edit-property.php?id=' . $propertyId);
        header("Refresh:0");
        $_SESSION['success'] = "Property updated successfully.";
        exit();


        // exit();

    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log("Database error: " . $e->getMessage());

        $_SESSION['error'] = "An error occurred while updating the property.$ii";
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while processing your request.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property | RentEstate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
    <style>
        /* Edit Property Page Styles */
        .edit-property-container {
            margin-top: 80px;
            padding: 2rem 5%;
            max-width: 1200px;
            margin-left: 250px;
            margin-right: auto;
        }

        .edit-property-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .edit-property-header h1 {
            color: var(--text-color);
            font-size: 2rem;
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #f5f5f5;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
        }

        /* Form Tabs */
        .form-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 2rem;
            overflow-x: auto;
            scrollbar-width: none;
            /* Firefox */
        }

        .form-tabs::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Edge */
        }

        .tab-button {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 1rem;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .tab-button:hover {
            color: var(--primary-color);
        }

        .tab-button.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .tab-content h2 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1.5rem;
        }

        .tab-content h3 {
            font-size: 1.2rem;
            color: #444;
            margin: 1.5rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        /* Property Type Sections */
        .property-type-section {
            display: none;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: #f9f9f9;
            border-radius: 8px;
        }

        .property-type-section.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        /* Form Styles */
        .edit-property-form {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        label {
            display: block;
            margin-bottom: -0.1rem;
            font-weight: 500;
            color: #444;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 1rem;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(238, 114, 56, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            margin: 0;
        }

        .checkbox-group label {
            margin-bottom: 0;
        }

        /* Features Tab */
        .features-info {
            margin-bottom: 1.5rem;
            color: #666;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .feature-item input[type="checkbox"] {
            margin: 0;
        }

        /* Images Tab */
        .current-images,
        .upload-new-images {
            margin-bottom: 2rem;
        }

        .current-images h3,
        .upload-new-images h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .current-images p,
        .upload-new-images p {
            margin-bottom: 1rem;
            color: #666;
        }

        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        .image-item {
            border: 1px solid #eee;
            border-radius: 4px;
            overflow: hidden;
        }

        .image-preview {
            height: 150px;
            overflow: hidden;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-actions {
            padding: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #f9f9f9;
        }

        .file-upload-container {
            margin-top: 1rem;
        }

        .file-upload-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background-color: #f5f5f5;
            border: 1px dashed #ccc;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload-label:hover {
            background-color: #eee;
            border-color: #bbb;
        }

        .file-upload-label i {
            font-size: 1.2rem;
            color: #666;
        }

        input[type="file"] {
            display: none;
        }

        .file-upload-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .preview-item {
            border: 1px solid #eee;
            border-radius: 4px;
            overflow: hidden;
        }

        .preview-item img {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }

        .preview-item span {
            display: block;
            padding: 0.3rem;
            font-size: 0.8rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            background-color: #f9f9f9;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .cancel-button {
            padding: 0.8rem 1.5rem;
            background-color: #f5f5f5;
            color: #333;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .cancel-button:hover {
            background-color: #e5e5e5;
        }

        .save-button {
            padding: 0.8rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .save-button:hover {
            background-color: #d65b1e;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .edit-property-container {
                margin-left: 0;
                padding: 2rem 3%;
            }
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 1rem;
            }

            .edit-property-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .form-tabs {
                flex-wrap: nowrap;
            }

            .tab-button {
                flex: 0 0 auto;
                padding: 0.8rem 1rem;
            }

            .features-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .edit-property-form {
                padding: 1.5rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .images-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                    <a href="../index.php" class="logo">
                        <img src="../../rently2.png" alt="" style="height: 38px; width: 130px;">
                    </a>
            </div>
                                    <div class="notif">
                <?php include '../includes/notifications.php'; ?>
            </div>
                        <?php if ($isLoggedIn): ?>
                <!-- User Profile with Active Status -->
                <div class="user-profile">
                    <div class="user-status">
                        <span class="status-indicator"></span>
                        <span class="username"><?php echo $_SESSION['user_name']; ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </header>
    

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Left Sidebar Menu -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <?php if ($isLoggedIn): ?>
                <div class="user-name">
                    <?php echo '<h2 style="text-color:rgb(121, 198, 233)">WELCOME</h2> ', $_SESSION['user_name']; ?>
                </div>
            <?php else: ?>
                <div class="user-name">
                    <h2>Welcome </h2> Guest
                </div>
                <a href="../../login-signup/index.php" class="menu-item">
                    <i class="fas fa-sign-in-alt"></i>
                    Login / Sign Up
                </a>
            <?php endif; ?>
        </div>

        <nav class="sidebar-menu">
            <div class="menu-section">
                <div class="menu-section-title">Main</div>
                <a href="../index.php" class="menu-item ">
                    <i class="fas fa-home"></i>
                    Home
                </a>

                <a href="map-view.php" class="menu-item">
                    <i class="fas fa-map-marked-alt"></i>
                    Map View
                </a>
            </div>

            <?php if ($isLoggedIn): ?>
                <div class="menu-section">
                    <div class="menu-section-title">Personal</div>
                    <a href="../menu pages/favorites.php" class="menu-item">
                        <i class="fas fa-heart"></i>
                        Favorites
                    </a>

                    <a href="../menu pages/messages.php" class="menu-item">
                        <i class="fas fa-envelope"></i>
                        Messages
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">Property Management</div>
                    <a href="../my-properties.php" class="menu-item">
                        <i class="fas fa-building"></i>
                        My Properties
                    </a>
                    <a href="../become-host.php" class="menu-item">
                        <i class="fas fa-plus-circle"></i>
                        Add New Property
                    </a>
                    <a href="../menu pages/bookings.php" class="menu-item">
                        <i class="fas fa-calendar-check"></i>
                        My Bookings
                    </a>
                </div>
            <?php endif; ?>

            <div class="menu-section">
                <div class="menu-section-title">Settings</div>
                <a href="../menu pages/settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    Account Settings
                </a>
                <a href="../menu pages/privacy.php" class="menu-item">
                    <i class="fas fa-shield-alt"></i>
                    Privacy & Security
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Support</div>
                <a href="../help-center.php" class="menu-item">
                    <i class="fas fa-question-circle"></i>
                    Help Center
                </a>

                <a href="../about-us.php" class="menu-item">
                    <i class="fas fa-info-circle"></i>
                    About Us
                </a>
            </div>

            <?php if ($isLoggedIn): ?>
                <div class="menu-section">
                    <a href="../../login-signup/php files/logout.php" class="menu-item">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            <?php endif; ?>
        </nav>
    </aside>

    <div class="edit-property-container">
        <div class="edit-property-header">
            <h1>Edit Property</h1>
            <a href="../property.php?id=<?php echo $propertyId; ?>" class="back-button">
                <i class="fas fa-arrow-left"></i> Go to Property
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form action="edit-property.php?id=<?php echo $propertyId; ?>" method="POST" enctype="multipart/form-data"
            class="edit-property-form">
            <div class="form-tabs">
                <button type="button" class="tab-button active" data-tab="basic-info">Basic Info</button>
                <button type="button" class="tab-button" data-tab="property-details">Property Details</button>
                <button type="button" class="tab-button" data-tab="images">Images</button>
            </div>

            <div class="tab-content active" id="basic-info">
                <h2>Basic Information</h2>

                <div class="form-group">
                    <label for="title">Property Title*</label>
                    <input type="text" id="title" name="title"
                        value="<?php echo htmlspecialchars($property['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description*</label>
                    <textarea id="description" name="description" rows="6" required
                        value="<?php echo htmlspecialchars($property['description']); ?>"><?php echo htmlspecialchars($property['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="estimatePrice">estimate Price (DZD)*</label>
                        <input type="number" id="estimatePrice" name="estimatePrice"
                            value="<?php echo $property['estimatePrice']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="status">Status*</label>
                        <select id="status" name="status" required>
                            <option value="available" <?php echo $property['status'] == 'available' ? 'selected' : ''; ?>>
                                Available</option>
                            <option value="pending" <?php echo $property['status'] == 'pending' ? 'selected' : ''; ?>>
                                Pending</option>
                            <option value="rented" <?php echo $property['status'] == 'rented' ? 'selected' : ''; ?>>Rented
                            </option>
                            <option value="sold" <?php echo $property['status'] == 'sold' ? 'selected' : ''; ?>>Sold
                            </option>
                        </select>
                    </div>
                </div>

                <!-- <div class="form-group">
                    <label for="propertyType">Property Type*</label>
                    <select id="propertyType" name="propertyType" required>
                        <option value="apartment" <?php echo $property['propertyType'] == 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                        <option value="house" <?php echo $property['propertyType'] == 'house' ? 'selected' : ''; ?>>House
                        </option>
                        <option value="villa" <?php echo $property['propertyType'] == 'villa' ? 'selected' : ''; ?>>Villa
                        </option>
                        <option value="room" <?php echo $property['propertyType'] == 'room' ? 'selected' : ''; ?>>Room
                        </option>
                    </select>
                </div> -->

                <div class="form-row">
                    <div class="form-group">
                        <label for="address">Address*</label>
                        <input type="text" id="address" name="address"
                            value="<?php echo htmlspecialchars($property['address']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City*</label>
                        <input type="text" id="city" name="city"
                            value="<?php echo htmlspecialchars($property['city']); ?>" required>
                    </div>


                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="country">Country*</label>
                        <input type="text" id="country" name="country"
                            value="<?php echo htmlspecialchars($property['country']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="socialCode">Social Code</label>
                        <input type="text" id="socialCode" name="socialCode"
                            value="<?php echo htmlspecialchars($property['socialCode']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="ownerNeeds">Owner Needs</label>
                    <select name="ownerNeeds" id="ownerNeeds">
                        <option value="renting" <?php echo $property['ownerNeeds'] == 'renting' ? 'selected' : ''; ?>>
                            renting</option>
                            <?php if($isVIP):  ?>
                        <option value="selling" <?php echo $property['ownerNeeds'] == 'selling' ? 'selected' : ''; ?>>
                            selling</option>
                            <?php endif;?>
                    </select>
                </div>
            </div>

            <div class="tab-content" id="property-details">
                <h2>Property Details</h2>

                <!-- Common fields for all property types -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="size">Size (sq ft)*</label>
                        <input type="number" id="size" name="size" value="<?php echo $property['size']; ?>" min="0"
                            required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bedrooms">Bedrooms*</label>
                        <input type="number" id="bedrooms" name="bedrooms" value="<?php echo $property['bedrooms']; ?>"
                            min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="bathrooms">Bathrooms*</label>
                        <input type="number" id="bathrooms" name="bathrooms"
                            value="<?php echo $property['bathrooms']; ?>" min="0" step="0.5" required>
                    </div>
                </div>

                <?php if ($property['propertyType'] == 'house'): ?>
                    <!-- House specific fields -->
                    <div class="property-type-section active" id="house-details">
                        <h3>House Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="floors">Floors</label>
                                <input type="number" id="floors" name="floors"
                                    value="<?php echo $propertyDetails['floors']; ?>" min="1">
                            </div>

                            <div class="form-group">
                                <label for="garden_size">Garden Size (sq ft)</label>
                                <input type="number" id="garden_size" name="garden_size"
                                    value="<?php echo $propertyDetails['garden_size']; ?>" min="0">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="has_garage">Has Garage</label>
                                <select id="has_garage" name="has_garage">
                                    <option value="yes" <?php echo $propertyDetails['has_garage'] ? 'selected' : ''; ?>>Yes
                                    </option>
                                    <option value="no" <?php echo !$propertyDetails['has_garage'] ? 'selected' : ''; ?>>No
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="garage_capacity">Garage Capacity</label>
                                <input type="number" id="garage_capacity" name="garage_capacity"
                                    value="<?php echo $propertyDetails['garage_capacity']; ?>" min="0">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="has_basement">Has Basement</label>
                            <select id="has_basement" name="has_basement">
                                <option value="yes" <?php echo $propertyDetails['has_basement'] ? 'selected' : ''; ?>>Yes
                                </option>
                                <option value="no" <?php echo !$propertyDetails['has_basement'] ? 'selected' : ''; ?>>No
                                </option>
                            </select>
                        </div>
                    </div>
                <?php elseif ($property['propertyType'] == 'apartment'): ?>
                    <!-- Apartment specific fields -->
                    <div class="property-type-section active" id="apartment-details">
                        <h3>Apartment Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="floor_number">Floor Number</label>
                                <input type="number" id="floor_number" name="floor_number"
                                    value="<?php echo $propertyDetails['floor_number']; ?>" min="0">
                            </div>

                            <div class="form-group">
                                <label for="monthly_maintenance_fee">Monthly Fees</label>
                                <input type="number" id="monthly_maintenance_fee" name="monthly_maintenance_fee"
                                    value="<?php echo $propertyDetails['monthly_maintenance_fee']; ?>" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="has_elevator">Has Elevator</label>
                                <select id="has_elevator" name="has_elevator">
                                    <option value="yes" <?php echo $propertyDetails['has_elevator'] ? 'selected' : ''; ?>>
                                        Yes
                                    </option>
                                    <option value="no" <?php echo !$propertyDetails['has_elevator'] ? 'selected' : ''; ?>>No
                                    </option>
                                </select>
                            </div>
                            <!-- <div class="form-group checkbox-group">
                                <input type="checkbox" id="has_elevator" name="has_elevator" <?php echo $propertyDetails['has_elevator'] ? 'checked' : ''; ?>>
                                <label for="has_elevator">Has Elevator</label>
                            </div> -->

                            <div class="form-group">
                                <label for="has_parking">Has Parking</label>
                                <select id="has_parking" name="has_parking">
                                    <option value="yes" <?php echo $propertyDetails['has_parking'] ? 'selected' : ''; ?>>Yes
                                    </option>
                                    <option value="no" <?php echo !$propertyDetails['has_parking'] ? 'selected' : ''; ?>>No
                                    </option>
                                </select>
                            </div>
                            <!-- <div class="form-group checkbox-group">
                                <input type="checkbox" id="has_parking" name="has_parking" <?php echo $propertyDetails['has_parking'] ? 'checked' : ''; ?>>
                                <label for="has_parking">Has Parking</label>
                            </div> -->
                        </div>
                    </div>
                <?php elseif ($property['propertyType'] == 'villa'): ?>
                    <!-- Villa specific fields -->
                    <div class="property-type-section active" id="villa-details">
                        <h3>Villa Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="floors">Floors</label>
                                <input type="number" id="floors" name="floors"
                                    value="<?php echo $propertyDetails['floors']; ?>" min="1">
                            </div>

                            <div class="form-group">
                                <label for="garden_size">Garden Size (sq ft)</label>
                                <input type="number" id="garden_size" name="garden_size"
                                    value="<?php echo $propertyDetails['garden_size']; ?>" min="0">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="has_pool">Has Pool</label>
                                <select name="has_pool" id="has_pool">
                                    <option value="yes" <?php echo $propertyDetails['has_pool'] ? 'selected' : ''; ?>>Yes
                                    </option>
                                    <option value="no" <?php echo !$propertyDetails['has_pool'] ? 'selected' : ''; ?>>No
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="has_garage">Has Garage</label>
                            <select id="has_garage" name="has_garage">
                                <option value="yes" <?php echo $propertyDetails['has_garage'] ? 'selected' : ''; ?>>Yes
                                </option>
                                <option value="no" <?php echo !$propertyDetails['has_garage'] ? 'selected' : ''; ?>>No
                                </option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="garage_capacity">Garage Capacity</label>
                                <input type="number" id="garage_capacity" name="garage_capacity"
                                    value="<?php echo $propertyDetails['garage_capacity']; ?>" min="0">
                            </div>

                            <div class="form-group">
                                <label for="has_security_system">Has Security System</label>
                                <select name="has_security_system" id="has_security_system">
                                    <option value="yes" <?php echo $propertyDetails['has_security_system'] ? 'selected' : ''; ?>>Yes</option>
                                    <option value="no" <?php echo !$propertyDetails['has_security_system'] ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                <?php elseif ($property['propertyType'] == 'room'): ?>
                    <!-- Room specific fields -->
                    <div class="property-type-section active" id="room-details">
                        <h3>Room Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="room_type">Room Type</label>
                                <select id="room_type" name="room_type">
                                    <option value="private" <?php echo $propertyDetails['room_type'] == 'private' ? 'selected' : ''; ?>>Private Room</option>
                                    <option value="shared" <?php echo $propertyDetails['room_type'] == 'shared' ? 'selected' : ''; ?>>Shared Room</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="floor_number">Floor Number</label>
                                <input type="number" id="floor_number" name="floor_number"
                                    value="<?php echo $propertyDetails['floor_number']; ?>" min="0">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="has_private_bathroom">Has Private Bathroom</label>
                                <select id="has_private_bathroom" name="has_private_bathroom">
                                    <option value="yes" <?php echo ($propertyDetails['has_private_bathroom']) ? 'selected' : ''; ?>>Yes</option>
                                    <option value="no" <?php echo (!$propertyDetails['has_private_bathroom']) ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="has_private_kitchen">Has Private Kitchen</label>
                                <select id="has_private_kitchen" name="has_private_kitchen">
                                    <option value="yes" <?php echo ($propertyDetails['has_private_kitchen']) ? 'selected' : ''; ?>>Yes</option>
                                    <option value="no" <?php echo (!$propertyDetails['has_private_kitchen']) ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="is_furnished">Is Furnished</label>
                                <select id="is_furnished" name="is_furnished">
                                    <option value="yes" <?php echo ($propertyDetails['is_furnished']) ? 'selected' : ''; ?>>
                                        Yes</option>
                                    <option value="no" <?php echo (!$propertyDetails['is_furnished']) ? 'selected' : ''; ?>>
                                        No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-content" id="images">
                <h2>Property Images</h2>
                        <?php if(count($images) >= 3): ?>
                        <small>you can upload up to 3 images</small>

                        <?php endif; ?>
                <?php if (!empty($images)): ?>
                    <div class="current-images">
                        <h3>Current Images</h3>

                        <p>Select images you want to delete:</p>

                        <div class="images-grid">
                            <?php foreach ($images as $image): ?>
                                <div class="image-item">
                                    <div class="image-preview">
                                        <img src=" <?php echo htmlspecialchars($image['image_path']); ?>"
                                            alt="Property image">
                                    </div>
                                    <div class="image-actions">
                                        <input type="checkbox" id="delete-<?php echo $image['id']; ?>" name="delete_images[]"
                                            value="<?php echo $image['id']; ?>">
                                        <label for="delete-<?php echo $image['id']; ?>">Delete</label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="upload-new-images">
                    <h3>Upload New Images</h3>
                    <p>Select multiple images to upload (JPG, PNG, or GIF):</p>

                    <div class="file-upload-container">
                        <input type="file" id="new_images" name="property_images[]" multiple accept="image/*">
                        <label for="new_images" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Choose Files</span>
                        </label>
                        <div id="file-upload-preview" class="file-upload-preview"></div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="../my-properties.php" class="cancel-button">Cancel</a>
                <button type="submit" class="save-button">Save Changes</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Tab navigation
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function () {
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Add active class to clicked button and corresponding content
                    this.classList.add('active');
                    document.getElementById(this.dataset.tab).classList.add('active');
                });
            });

            // Property type dependent fields
            const propertyTypeSelect = document.getElementById('propertyType');

            // if (propertyTypeSelect) {
            //     propertyTypeSelect.addEventListener('change', function () {
            //         alert('Please save your changes and reload the page to update property type specific fields.');
            //     });
            // }

            // File upload preview
            const fileInput = document.getElementById('new_images');
            const filePreview = document.getElementById('file-upload-preview');

            if (fileInput && filePreview) {
                fileInput.addEventListener('change', function () {
                    filePreview.innerHTML = '';

                    if (this.files.length > 0) {
                        for (let i = 0; i < this.files.length; i++) {
                            const file = this.files[i];

                            if (file.type.match('image.*')) {
                                const reader = new FileReader();

                                reader.onload = function (e) {
                                    const imgContainer = document.createElement('div');
                                    imgContainer.className = 'preview-item';

                                    const img = document.createElement('img');
                                    img.src = e.target.result;
                                    img.alt = file.name;

                                    const fileName = document.createElement('span');
                                    fileName.textContent = file.name;

                                    imgContainer.appendChild(img);
                                    imgContainer.appendChild(fileName);
                                    filePreview.appendChild(imgContainer);
                                };

                                reader.readAsDataURL(file);
                            }
                        }
                    }
                });
            }

            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');

            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('active');
                });

                // Close sidebar when clicking outside
                document.addEventListener('click', function (e) {
                    if (!sidebar.contains(e.target) && e.target !== sidebarToggle) {
                        sidebar.classList.remove('active');
                    }
                });
            }
        });

                document.addEventListener('DOMContentLoaded', () => {
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationContainer = document.getElementById('notificationContainer');
            const notificationList = document.getElementById('notificationList');

            // Toggle dropdown
            notificationIcon.addEventListener('click', function () {
                notificationContainer.classList.toggle('show');
                fetch('fetch_notifications.php?action=mark_read');
            });

            // Fetch notifications
            fetch('fetch_notifications.php?action=fetch')
                .then(response => response.json())
                .then(notifications => {
                    notificationList.innerHTML = ''; // Clear previous content

                    if (!notifications || notifications.length === 0) {
                        notificationList.innerHTML = '<p class="notification-item">No notifications</p>';
                        return;
                    }
                    notifications.forEach(notification => {
                        const notif = document.createElement('div');
                        notif.classList.add('notification-item');
                        console.log(notification.is_read);
                        if (Number(notification.is_read) == 0) {
                            notif.style.backgroundColor = '#f0f0f0'; // unread
                        } else {
                            notif.style.backgroundColor = '#dff0d8'; // read
                        }
                        notif.innerHTML = `
                    <h3>${notification.message}</h3>
                    <strong><span>${new Date(notification.timestamp).toLocaleString()}</span></strong>
                `;
                        notificationList.appendChild(notif);
                    });
                })
                .catch(err => {
                    console.error('Error fetching notifications:', err);
                    notificationList.innerHTML = '<p class="notification-item">Nothing here</p>';
                });

            // Optional: Clear notifications logic
            document.querySelector('.clear-notifications').addEventListener('click', () => {
                fetch('fetch_notifications.php?action=clear', {
                    method: 'POST'
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const notificationList = document.querySelector('.notification-list');
                            notificationList.innerHTML = '<p class="notification-item">No notifications</p>';
                        } else {
                            console.error('Clear failed:', data.error);
                        }
                    })
                    .catch(err => console.error('Error:', err));
            });

            notif.addEventListener('click', () => {
                fetch('fetch_notifications.php?action=mark_read', {
                    method: 'POST',
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            notif.style.backgroundColor = '#dff0d8'; // Mark visually as read
                        }
                    });
            });

        });

    </script>
</body>

</html>
<!-- not working with file -->
<?php
session_start();
require_once 'php files/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login-signup/index.php');
    exit();
}
$isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in


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

    // Fetch property details based on the propertyType

if ($property['propertyType'] == 'house') {
    $stmt = $pdo->prepare("
        SELECT * FROM house 
        WHERE id_property = ?
    ");
    $stmt->execute([$propertyId]);
    $propertyT = $stmt->fetch(PDO::FETCH_ASSOC);
}elseif ($property['propertyType'] == 'apartment') {
    $stmt = $pdo->prepare("
        SELECT * FROM apartment 
        WHERE id_property = ?
    ");
    $stmt->execute([$propertyId]);
    $propertyT = $stmt->fetch(PDO::FETCH_ASSOC);
}elseif($property['propertyType'] == 'villa') {
    $stmt = $pdo->prepare("
        SELECT * FROM villa 
        WHERE id_property = ?
    ");
    $stmt->execute([$propertyId]);
    $propertyT = $stmt->fetch(PDO::FETCH_ASSOC);
}else{
    $stmt = $pdo->prepare("
        SELECT * FROM room 
        WHERE id_property = ?
    ");
    $stmt->execute([$propertyId]);
    $propertyT = $stmt->fetch(PDO::FETCH_ASSOC);
}
    if (!$property) {
        $_SESSION['error'] = "You don't have permission to edit this property.";
        header('Location: my-properties.php');
        exit();
    }
    
    // Fetch property images
    $stmt = $pdo->prepare("SELECT image_path, image_order FROM Property_Images WHERE property_id = ? ORDER BY image_order ASC");
    $stmt->execute([$propertyId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    // // Fetch all available features
    // $stmt = $pdo->prepare("
    //     SELECT * FROM Feature ORDER BY feature_name
    // ");
    // $stmt->execute();
    // $allFeatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch property's current features
    // $stmt = $pdo->prepare("
    //     SELECT id_feature FROM PropertyFeature 
    //     WHERE id_property = ?
    // ");
    // $stmt->execute([$propertyId]);
    // $propertyFeatures = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
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
        $price = $_POST['price'];
        $priceNegotiable = isset($_POST['price_negotiable']) ? 1 : 0;
        $location = $_POST['location'];
        $propertyType = $_POST['propertyType'];
        $status = $_POST['status'];
        
        // Type-specific fields with defaults
        $bedrooms = isset($_POST['bedrooms']) ? $_POST['bedrooms'] : 0;
        $bathrooms = isset($_POST['bathrooms']) ? $_POST['bathrooms'] : 0;
        $area = isset($_POST['area']) ? $_POST['area'] : 0;
        $yearBuilt = isset($_POST['year_built']) ? $_POST['year_built'] : null;
        
        // Additional fields based on property type
        $floors = isset($_POST['floors']) ? $_POST['floors'] : null;
        $parkingSpaces = isset($_POST['parking_spaces']) ? $_POST['parking_spaces'] : null;
        $furnished = isset($_POST['furnished']) ? 1 : 0;
        $petFriendly = isset($_POST['pet_friendly']) ? 1 : 0;
        $hasGarden = isset($_POST['has_garden']) ? 1 : 0;
        $hasPool = isset($_POST['has_pool']) ? 1 : 0;
        $hasBalcony = isset($_POST['has_elevator']) ? 1 : 0;
        $floorNumber = isset($_POST['floor_number']) ? $_POST['floor_number'] : null;
        $landSize = isset($_POST['land_size']) ? $_POST['land_size'] : null;
        $commercialType = isset($_POST['commercial_type']) ? $_POST['commercial_type'] : null;
        
        // Update property details
        $stmt = $pdo->prepare("
            UPDATE Property SET
                title = ?,
                description = ?,
                estimatedPrice = ?,
                price_negotiable = ?,
                location = ?,
                bedrooms = ?,
                bathrooms = ?,
                area = ?,
                year_built = ?,
                propertyType = ?,
                status = ?,
                floors = ?,
                parking_spaces = ?,
                furnished = ?,
                pet_friendly = ?,
                has_garden = ?,
                has_pool = ?,
                has_elevator = ?,
                floor_number = ?,
                land_size = ?,
                commercial_type = ?
            WHERE id_property = ? AND id_client = ?
        ");
        
        $stmt->execute([
            $title,
            $description,
            $price,
            $priceNegotiable,
            $location,
            $bedrooms,
            $bathrooms,
            $area,
            $yearBuilt,
            $propertyType,
            $status,
            $floors,
            $parkingSpaces,
            $furnished,
            $petFriendly,
            $hasGarden,
            $hasPool,
            $hasBalcony,
            $floorNumber,
            $landSize,
            $commercialType,
            $propertyId,
            $userId
        ]);
        
        // Update features
        // First, remove all current features
        $stmt = $pdo->prepare("
            DELETE FROM PropertyFeature 
            WHERE id_property = ?
        ");
        $stmt->execute([$propertyId]);
        
        // Then add selected features
        if (isset($_POST['features']) && is_array($_POST['features'])) {
            $insertFeature = $pdo->prepare("
                INSERT INTO PropertyFeature (id_property, id_feature) 
                VALUES (?, ?)
            ");
            
            foreach ($_POST['features'] as $featureId) {
                $insertFeature->execute([$propertyId, $featureId]);
            }
        }
        
        // Handle image uploads
        if (isset($_FILES['new_images']) && $_FILES['new_images']['error'][0] != UPLOAD_ERR_NO_FILE) {
            $uploadDir = 'uploads/properties/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $insertImage = $pdo->prepare("
                INSERT INTO PropertyImage (id_property, image_url) 
                VALUES (?, ?)
            ");
            
            foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['new_images']['error'][$key] == UPLOAD_ERR_OK) {
                    $fileName = time() . '_' . basename($_FILES['new_images']['name'][$key]);
                    $targetFile = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($tmp_name, $targetFile)) {
                        $insertImage->execute([$propertyId, $targetFile]);
                    }
                }
            }
        }
        
        // Handle image deletions
        if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
            $deleteImage = $pdo->prepare("
                SELECT image_url FROM PropertyImage 
                WHERE id_image = ? AND id_property = ?
            ");
            
            $removeImage = $pdo->prepare("
                DELETE FROM PropertyImage 
                WHERE id_image = ? AND id_property = ?
            ");
            
            foreach ($_POST['delete_images'] as $imageId) {
                // Get image URL before deleting
                $deleteImage->execute([$imageId, $propertyId]);
                $imageUrl = $deleteImage->fetchColumn();
                
                // Delete from database
                $removeImage->execute([$imageId, $propertyId]);
                
                // Delete file from server
                if ($imageUrl && file_exists($imageUrl)) {
                    unlink($imageUrl);
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success'] = "Property updated successfully.";
        header('Location: property-details.php?id=' . $propertyId);
        exit();
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while updating the property.";
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
    <link rel="stylesheet" href="styles.css">
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
  scrollbar-width: none; /* Firefox */
}

.form-tabs::-webkit-scrollbar {
  display: none; /* Chrome, Safari, Edge */
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
  from { opacity: 0; }
  to { opacity: 1; }
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
  margin-bottom: 0.5rem;
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
    <!-- Header (Same as index.php) -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-home"></i>
                <a href="index.php" class="logo">RentEstate</a>
            </div>
            
            <!-- Include notification bell-->
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
                <a href="../login-signup/index.php" class="menu-item">
                    <i class="fas fa-sign-in-alt"></i>
                    Login / Sign Up
                </a>
            <?php endif; ?>
        </div>

        <nav class="sidebar-menu">
            <div class="menu-section">
                <div class="menu-section-title">Main</div>
                <a href="index.php" class="menu-item ">
                    <i class="fas fa-home"></i>
                    Home
                </a>

                <a href="menu pages/map-view.php" class="menu-item">
                    <i class="fas fa-map-marked-alt"></i>
                    Map View
                </a>
            </div>

            <?php if ($isLoggedIn): ?>
                <div class="menu-section">
                    <div class="menu-section-title">Personal</div>
                    <a href="menu pages/favorites.php" class="menu-item">
                        <i class="fas fa-heart"></i>
                        Favorites
                        <?php if (isset($favoriteCount) && $favoriteCount > 0): ?>
                            <span class="menu-badge"><?php echo $favoriteCount; ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="menu pages/messages.php" class="menu-item">
                        <i class="fas fa-envelope"></i>
                        Messages
                        <?php if (isset($unreadMessages) && $unreadMessages > 0): ?>
                            <span class="menu-badge"><?php echo $unreadMessages; ?></span>
                        <?php endif; ?>
                    </a>

                </div>

                <div class="menu-section">
                    <div class="menu-section-title">Property Management</div>
                    <a href="my-properties.php" class="menu-item">
                        <i class="fas fa-building"></i>
                        My Properties
                    </a>
                    <a href="become-host.php" class="menu-item">
                        <i class="fas fa-plus-circle"></i>
                        Add New Property
                    </a>
                    <a href="menu pages/bookings.php" class="menu-item">
                        <i class="fas fa-calendar-check"></i>
                        My Bookings
                    </a>

                </div>
            <?php endif; ?>

            <div class="menu-section">
                <div class="menu-section-title">Settings</div>
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    Account Settings
                </a>
                <a href="privacy.php" class="menu-item">
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
                    <a href="../login-signup/php files/logout.php" class="menu-item">
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
            <a href="property.php?id=<?php echo $propertyId; ?>" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Property
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form action="edit-property.php?id=<?php echo $propertyId; ?>" method="POST" enctype="multipart/form-data" class="edit-property-form">
            <div class="form-tabs">
                <button type="button" class="tab-button active" data-tab="basic-info">Basic Info</button>
                <button type="button" class="tab-button" data-tab="property-details">Property Details</button>
                <button type="button" class="tab-button" data-tab="features">Features</button>
                <button type="button" class="tab-button" data-tab="images">Images</button>
            </div>

            <div class="tab-content active" id="basic-info">
                <h2>Basic Information</h2>
                
                <div class="form-group">
                    <label for="title">Property Title*</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($property['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description*</label>
                    <textarea id="description" name="description" rows="6" required><?php echo htmlspecialchars($property['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price (DZD)*</label>
                        <input type="number" id="price" name="estimatePrice" value="<?php echo $property['estimatePrice']; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="location">Location*</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($property['address']); ?>" required>
                </div>

                <!-- <div class="form-row"> -->
                    <div class="form-group">
                        <label for="propertyType">Property Type*</label>
                        <select id="propertyType" name="propertyType" required>
                          <?php if ($property['propertyType'] == 'apartment'): ?>
                            <option value="apartment" <?php echo $property['propertyType'] == 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                            <?php elseif ($property['propertyType'] == 'house'): ?>
                            <option value="house" <?php echo $property['propertyType'] == 'house' ? 'selected' : ''; ?>>House</option>
                            <?php elseif ($property['propertyType'] == 'villa'): ?>
                            <option value="villa" <?php echo $property['propertyType'] == 'villa' ? 'selected' : ''; ?>>villa</option>
                            <?php else: ?>
                            <option value="room" <?php echo $property['propertyType'] == 'room' ? 'selected' : ''; ?>>room</option>
                            <?php endif?>
                          </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status*</label>
                        <select id="status" name="status" required>
                            <option value="available" <?php echo $property['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="pending" <?php echo $property['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="rented" <?php echo $property['status'] == 'rented' ? 'selected' : ''; ?>>Rented</option>
                            <option value="sold" <?php echo $property['status'] == 'sold' ? 'selected' : ''; ?>>Sold</option>
                        </select>
                    </div>
                <!-- </div> -->
            </div>

            <div class="tab-content" id="property-details">
                <h2>Property Details</h2>
                
                <!-- Common fields for most property types -->
                <!-- <div class="form-group" data-property-types="apartment,house,condo,townhouse"> -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bedrooms">Bedrooms*</label>
                            <input type="number" id="bedrooms" name="bedrooms" value="<?php echo $property['bedrooms']; ?>" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="bathrooms">Bathrooms*</label>
                            <input type="number" id="bathrooms" name="bathrooms" value="<?php echo $property['bathrooms']; ?>" min="0" step="0.5">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="area">Living Area (sq ft)*</label>
                            <input type="number" id="area" name="size" value="<?php echo $property['size']; ?>" min="0">
                        </div>
                    </div>
                    
                <!-- </div> -->
                <?php if($property['propertyType'] =='house'): ?>
                <!-- House and Townhouse specific fields -->
                <div class="property-type-section" data-property-types="house">
                    <h3>House Details</h3>
                    <div class="form-group">
                        <label for="land_size">Floors</label>
                        <input type="number" id="floors" name="floors" value="<?php echo $propertyT['floors']; ?>" step="1">
                    </div>
                    <div class="form-group">
                        <label for="garden_size">Garden Size</label>
                        <input type="number" id="garden_size" name="gardenSize" value="<?php echo $propertyT['garden_size']; ?>" step="1">
                    </div>
                    <div class="form-group">
                        <label for="has_garage">Has Garage</label>
                        <select name="has_garage" id= "has_garage" value="<?php echo $propertyT['has_garage']; ?>">
                          <option value="no">No</option>
                          <option value="yes">Yes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="garage_capacity">Garage Capacity</label>
                        <input type="number" id="garage_capacity" name="garage_capacity" value="<?php echo $propertyT['garage_capacity']; ?>" min="0">
                    </div>
                    <div class="form-group">
                      <label for="has_basement">Has Basement</label>
                      <select name="has_basement" id="has_basement" value="<?php echo $propertyT['has_basement']; ?>">
                        <option value="no">No</option>
                        <option value="yes">Yes</option>
                      </select>
                    </div>
                </div>
                <?php elseif($property['propertyType'] == 'apartment'): ?>
                <!-- Apartment and Condo specific fields -->
                <div class="property-type-section" data-property-types="apartment,condo">
                    <div">
                    <h3>Apartment Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="floor_number">Floor Number</label>
                            <input type="number" id="floor_number" name="floor_number" value="<?php echo $propertyT['floor_number']; ?>" min="0">
                        </div>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="has_elevator" name="has_Elevator" <?php echo $propertyT['has_elevator'] ? 'checked' : ''; ?>>
                            <label for="has_elevator">Has Elevator</label>
                        </div>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="has_parking" name="has_parking" <?php echo $propertyT['has_parking'] ? 'checked' : ''; ?>>
                            <label for="has_parking">Has Parking</label>
                        </div>
                        <div class="from-group">
                          <input type="text" id="building name" name="building_name"<?php echo $propertyT['building_name']; ?>>
                          <label for="building_name">Building Name</label>
                        </div>
                        <div class="form-group">
                            <label for="monthly_maintenance_fee">Monthly Fees</label>
                            <input type="number" id="monthly_maintenance_fee" name="monthly_maintenance_fee" value="<?php echo $propertyT['monthly_maintenance_fee']; ?>" step="0.01">
                        </div>
                    </div>
                </div>
                <?php elseif($property['propertyType'] == 'villa'): ?>
                <!-- villa specific fields -->
                <div class="property-type-section" data-property-types="villa">
                    <h3>villa Details</h3>
                    
                    <div class="form-group">
                        <label for="land_size">Floors</label>
                        <input type="number" id="floors" name="floors" value="<?php echo $propertyT['floors']; ?>" min="0" step="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="garden_size">Garden Size</label>
                        <input type="text" id="garden_size" name="garden_size" rows="4"<?php echo htmlspecialchars($propertyT['garden_size']); ?>>
                    </div>

                    <div class="form-group checkbox-group">
                            <input type="checkbox" id="has_pool" name="has_pool" <?php echo $propertyT['has_pool'] ? 'checked' : ''; ?>>
                            <label for="has_pool">Has Pool</label>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="has_garage" name="has_garage" <?php echo $propertyT['has_garage'] ? 'checked' : ''; ?>>
                            <label for="has_garage">Has Garage</label>
                        </div>

                        <div class="form-group">
                            <label for="land_size">Garage Capacity</label>
                            <input type="number" id="Garage_capacity" name="Garage_capacity" value="<?php echo $propertyT['Garage_capacity']; ?>" min="0" step="1">
                        </div>


                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="Has_security_system" name="Has_security_system" <?php echo $propertyT['Has_security_system'] ? 'checked' : ''; ?>>
                            <label for="Has_security_system">Has Security System</label>
                        </div>
                </div>
                <?php elseif($property['propertyType'] == 'room'): ?>
                <!-- Room specific fields -->
                <!-- Commercial specific fields -->
                <div class="property-type-section" data-property-types="room">
                    <h3>Room Details</h3>
                    
                    <div class="form-group">
                        <label for="room"> Room Type*</label>
                        <select id="room" name="room_type">
                            <option value="private" <?php echo $property['room_type'] == 'private' ? 'selected' : ''; ?>>Private Room</option>
                            <option value="shared" <?php echo $property['room_type'] == 'shared' ? 'selected' : ''; ?>>Shared Room</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="floor_number">floor number*</label>
                            <input type="number" id="floor_number" name="floor_number" <?php echo $property['floor_number']; ?>>
                        </div>
                        
                        <div class="form-group checkbox-group">
                          <input type="checkbox" name="has_private_bathroom" id="has_private_kitchen" <?php echo $property['has_private_kitchen'] ? 'checked' : ''; ?>>
                          <label for="has_private_kitchen">Has Private Kitchen</label>
                        </div>

                        <div class="form-group checkbox-group">
                          <input type="checkbox" name="has_private_bathroom" id="has_private_bathroom" <?php echo $property['has_private_bathroom'] ? 'checked' : ''; ?>>
                          <label for="has_private_bathroom">Has Private Bathroom</label>
                        </div>
                        <div class="form-group checkbox-group">
                          <input type="checkbox" name="is_furnished" id="is_furnished" <?php echo $property['is_furnished'] ? 'checked' : ''; ?>>
                          <label for="is_furnished">Is Furnished</label>
                        </div>
                    </div>

                </div>
            </div>
            <?php endif; ?>

            <div class="tab-content" id="features">
                <h2>Property Features</h2>
                <p class="features-info">Select all features that apply to your property:</p>
                
                <div class="features-grid">
                    <?php foreach ($allFeatures as $feature): ?>
                        <div class="feature-item">
                            <input type="checkbox" id="feature-<?php echo $feature['id_feature']; ?>" name="features[]" value="<?php echo $feature['id_feature']; ?>" 
                                <?php echo in_array($feature['id_feature'], $propertyFeatures) ? 'checked' : ''; ?>>
                            <label for="feature-<?php echo $feature['id_feature']; ?>"><?php echo htmlspecialchars($feature['feature_name']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-content" id="images">
                <h2>Property Images</h2>
                
                <?php if (!empty($images)): ?>
                    <div class="current-images">
                        <h3>Current Images</h3>
                        <p>Select images you want to delete:</p>
                        
                        <div class="images-grid">
                            <?php foreach ($images as $image): ?>
                                <div class="image-item">
                                    <div class="image-preview">
                                        <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="Property image">
                                    </div>
                                    <div class="image-actions">
                                        <input type="checkbox" id="delete-<?php echo $image['id_image']; ?>" name="delete_images[]" value="<?php echo $image['id_image']; ?>">
                                        <label for="delete-<?php echo $image['id_image']; ?>">Delete</label>
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
                        <input type="file" id="new_images" name="new_images[]" multiple accept="image/*">
                        <label for="new_images" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Choose Files</span>
                        </label>
                        <div id="file-upload-preview" class="file-upload-preview"></div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="property-details.php?id=<?php echo $propertyId; ?>" class="cancel-button">Cancel</a>
                <button type="submit" class="save-button">Save Changes</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
  // Tab navigation
  const tabButtons = document.querySelectorAll('.tab-button');
  const tabContents = document.querySelectorAll('.tab-content');
  
  tabButtons.forEach(button => {
    button.addEventListener('click', function() {
      // Remove active class from all buttons and contents
      tabButtons.forEach(btn => btn.classList.remove('active'));
      tabContents.forEach(content => content.classList.remove('active'));
      
      // Add active class to clicked button and corresponding content
      this.classList.add('active');
      document.getElementById(this.dataset.tab).classList.add('active');
    });
  });
  
  // Property type dependent fields
  // const propertyTypeSelect = document.getElementById('propertyType');
  // const propertySections = document.querySelectorAll('.property-type-section');
  
  // function updatePropertyTypeSections() {
  //   const selectedType = propertyTypeSelect.value;
    
  //   // Hide all property type sections first
  //   propertySections.forEach(section => {
  //     section.classList.remove('active');
  //   });
    
  //   // Show sections that match the selected property type
  //   propertySections.forEach(section => {
  //     const propertyTypes = section.dataset.propertyTypes.split(',');
  //     if (propertyTypes.includes(selectedType)) {
  //       section.classList.add('active');
  //     }
  //   });
    
  //   // Update required fields based on property type
  //   updateRequiredFields(selectedType);
  // }
  
  // function updateRequiredFields(propertyType) {
  //   // Reset all fields to non-required first
  //   document.querySelectorAll('[data-required-for]').forEach(field => {
  //     field.removeAttribute('required');
  //   });
    
    // // Set required fields based on property type
    // document.querySelectorAll(`[data-required-for*="${propertyType}"]`).forEach(field => {
    //   field.setAttribute('required', 'required');
    // });
    
    // Special case handling
  //   if (propertyType === 'land') {
  //     document.getElementById('land_size').setAttribute('required', 'required');
  //     document.getElementById('bedrooms').removeAttribute('required');
  //     document.getElementById('bathrooms').removeAttribute('required');
  //   } else if (propertyType === 'commercial') {
  //     document.getElementById('commercial_type').setAttribute('required', 'required');
  //   }
  // }
  
  // if (propertyTypeSelect) {
  //   propertyTypeSelect.addEventListener('change', updatePropertyTypeSections);
  //   // Initialize on page load
  //   updatePropertyTypeSections();
  // }
  
  // File upload preview
  // const fileInput = document.getElementById('new_images');
  // const filePreview = document.getElementById('file-upload-preview');
  
  // if (fileInput && filePreview) {
  //   fileInput.addEventListener('change', function() {
  //     filePreview.innerHTML = '';
      
  //     if (this.files.length > 0) {
  //       for (let i = 0; i < this.files.length; i++) {
  //         const file = this.files[i];
          
  //         if (file.type.match('image.*')) {
  //           const reader = new FileReader();
            
  //           reader.onload = function(e) {
  //             const imgContainer = document.createElement('div');
  //             imgContainer.className = 'preview-item';
              
  //             const img = document.createElement('img');
  //             img.src = e.target.result;
  //             img.alt = file.name;
              
  //             const fileName = document.createElement('span');
  //             fileName.textContent = file.name;
              
  //             imgContainer.appendChild(img);
  //             imgContainer.appendChild(fileName);
  //             filePreview.appendChild(imgContainer);
  //           };
            
  //           reader.readAsDataURL(file);
  //         }
  //       }
  //     }
  //   });
  // }
  
  // // Form validation
  // const editPropertyForm = document.querySelector('.edit-property-form');
  
  // if (editPropertyForm) {
  //   editPropertyForm.addEventListener('submit', function(e) {
  //     const propertyType = propertyTypeSelect.value;
  //     let isValid = true;
      
  //     // Validate based on property type
  //     if (propertyType === 'apartment' || propertyType === 'house' || propertyType === 'condo' || propertyType === 'townhouse') {
  //       const bedrooms = document.getElementById('bedrooms').value;
  //       const bathrooms = document.getElementById('bathrooms').value;
  //       const area = document.getElementById('area').value;
        
  //       if (!bedrooms || !bathrooms || !area) {
  //         isValid = false;
  //         alert('Please fill in all required fields for ' + propertyType);
  //       }
  //     } else if (propertyType === 'land') {
  //       const landSize = document.getElementById('land_size').value;
        
  //       if (!landSize) {
  //         isValid = false;
  //         alert('Please enter the land size');
  //       }
  //     } else if (propertyType === 'commercial') {
  //       const commercialType = document.getElementById('commercial_type').value;
  //       const area = document.getElementById('area').value;
        
  //       if (!commercialType || !area) {
  //         isValid = false;
  //         alert('Please fill in all required fields for commercial property');
  //       }
  //     }
      
  //     if (!isValid) {
  //       e.preventDefault();
  //     }
  //   });
  // }
  
  // Sidebar toggle
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.sidebar');
  
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('active');
    });
    
    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
      if (!sidebar.contains(e.target) && e.target !== sidebarToggle) {
        sidebar.classList.remove('active');
      }
    });
  }
});
    </script>
</body>
</html>
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login-signup/index.php');
    exit();
}
$isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in

// Fetch user data
require_once 'php files/config.php';

try {
    // Fetch user data
    $stmt = $pdo->prepare("SELECT * FROM client WHERE id_client = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        // If user data not found, redirect to login
        header('Location: ../login-signup/index.html');
        exit();
    }

    // Check if user is VIP
    $stmtVIP = $pdo->prepare("
        SELECT pov.* 
        FROM propertyOwner_VIP pov
        JOIN propertyOwner po ON pov.id_propertyOwner = po.id_propertyOwner
        WHERE po.id_propertyOwner = ? 
    ");
    $stmtVIP->execute([$_SESSION['user_id']]);
    $isVIP = $stmtVIP->rowCount() > 0; // Returns true if VIP, false otherwise

} catch (PDOException $e) {
    // Log error and redirect to error page
    error_log("Database Error: " . $e->getMessage());
    header('Location: error.php');
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Host - RentEstate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
    <style>
        body {
            background-color: var(--light-gray);
        }

        /* Property Type Specific Sections */
        .property-type-section {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .property-type-section h3 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            border-bottom: 1px solid rgb(248, 245, 245);
            padding-bottom: 10px;
        }

        .property-type-section .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .property-type-section .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .section-icon {
            margin-right: 8px;
            color: #3498db;
        }

        .form-group label .optional {
            font-size: 12px;
            color: #95a5a6;
            font-style: italic;
            margin-left: 5px;
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
            display: hidden;
        }

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
    </style>
</head>

<body>
    <div id="menuToggle">

        <!-- Include header from index.html -->
        <!-- Sidebar Toggle Button -->
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

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

                    <!-- <a href="menu pages/map-view.php" class="menu-item">
                        <i class="fas fa-map-marked-alt"></i>
                        Map View
                    </a> -->
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
                        <a href="#" class="menu-item active">
                            <i class="fas fa-plus-circle"></i>
                            Add New Property
                        </a>
                        <a href="menu pages/bookings.php" class="menu-item">
                            <i class="fas fa-calendar-check"></i>
                            My Bookings
                        </a>

                    </div>
                <?php endif; ?>

                <!-- <div class="menu-section">
                    <div class="menu-section-title">Settings</div>
                    <a href="menu pages/settings.php" class="menu-item">
                        <i class="fas fa-cog"></i>
                        Account Settings
                    </a>
                    <a href="menu pages/privacy.php" class="menu-item">
                        <i class="fas fa-shield-alt"></i>
                        Privacy & Security
                    </a>
                </div> -->

                <div class="menu-section">
                    <div class="menu-section-title">Support</div>
                    <a href="help-center.php" class="menu-item">
                        <i class="fas fa-question-circle"></i>
                        Help Center
                    </a>

                    <a href="about-us.php" class="menu-item">
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



        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php" class="logo">
                        <img src="../rently2.png" alt="" style="height: 38px; width: 130px;">
                    </a>
                </div>


                <div class="notif">
                    <?php include 'includes/notifications.php'; ?>
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

        <div class="host-container">
            <div class="page-title">
                <h1>Become a Host </h1>
                <p>Share your space and earn extra income</p>
            </div>

            <!-- <?php if (!empty($_SESSION['success_message'])): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?> -->
            <div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1000;"></div>


            <div class="membership-status">
                <div class="status-info">
                    <div class="status-badge">Standard Host</div>
                    <p>You can list up to 3 photos per property</p>
                </div>
                <?php if (!$isVIP): ?>
                    <button class="upgrade-button" onclick="window.location.href='php files/vip-up.php';">Upgrade to
                        VIP</button>
                <?php endif; ?>
            </div>

            <form action="php files/host_process.php" method="post" id="hostForm" enctype="multipart/form-data">
                <input type="hidden" name="owner_id" value="<?php echo $_SESSION['user_id']; ?>">

                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-user"></i>
                        Personal Information
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name <span style="color: red;">*</span></label>
                            <input type="text" class="form-input" name="full_name"
                                value="<?php echo htmlspecialchars($userData['full_name']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Email <span style="color: red;">*</span></label>
                            <input type="email" class="form-input" name="email"
                                value="<?php echo htmlspecialchars($userData['email']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Phone Number <span style="color: red;">*</span></label>
                            <input type="tel" class="form-input" name="phone"
                                value="<?php echo htmlspecialchars($userData['phone']); ?>"
                                placeholder="Enter your phone number" required readonly>
                        </div>
                        <!-- <div class="form-group">
                            <label>2nd phone number <span class="optional">(optional)</span></label>
                            <input type="tel" class="form-input" name="phone2"
                                placeholder="Enter your second phone number">
                        </div> -->
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-home"></i>
                        Property Details
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Property Type <span style="color: red;">*</span></label>
                            <select class="form-input" name="propertyType" id="propertyType" required>
                                <option value="">Select type</option>
                                <option value="apartment">Apartment</option>
                                <option value="house">House</option>
                                <option value="villa">Villa</option>
                                <option value="room">Single Room</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>I Am : <span style="color: red;">*</span></label>
                            <select class="form-input" name="ownerNeeds" required>
                                <?php if ($isVIP): ?>
                                    <option value="selling">Selling</option>
                                <?php endif; ?>
                                <option value="renting">Renting</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Property Size (sq ft) <span style="color: red;">*</span></label>
                            <input type="number" class="form-input" name="size" placeholder="Enter size" required>
                        </div>
                        <div class="form-group">
                            <label>Bedrooms <span style="color: red;">*</span></label>
                            <input type="number" class="form-input" name="bedrooms" id="bedrooms" required>
                        </div>
                        <div class="form-group">
                            <label>Bathrooms <span style="color: red;">*</span></label>
                            <input type="number" class="form-input" name="bathrooms" id="bathrooms" required>
                        </div>
                        <div class="form-group">
                            <label>Address <span style="color: red;">*</span></label>
                            <input type="text" class="form-input" name="address" placeholder="Enter property address"
                                required>
                        </div>
                        <div class="form-group">
                            <label>City <span style="color: red;">*</span></label>
                            <select name="city" id="city" class="form-input" required>
                                <option value="">--Select City--</option>
                                <option value="Adrar">01 Adrar</option>
                                <option value="Chlef">02 Chlef</option>
                                <option value="Laghouat">03 Laghouat</option>
                                <option value="Oum El Bouaghi">04 Oum El Bouaghi</option>
                                <option value="Batna">05 Batna</option>
                                <option value="Béjaïa">06 Béjaïa</option>
                                <option value="Biskra">07 Biskra</option>
                                <option value="Béchar">08 Béchar</option>
                                <option value="Blida">09 Blida</option>
                                <option value="Bouira">10 Bouira</option>
                                <option value="Tamanrasset">11 Tamanrasset</option>
                                <option value="Tébessa">12 Tébessa</option>
                                <option value="Tlemcen">13 Tlemcen</option>
                                <option value="Tiaret">14 Tiaret</option>
                                <option value="Tizi Ouzou">15 Tizi Ouzou</option>
                                <option value="Algiers">16 Algiers</option>
                                <option value="Djelfa">17 Djelfa</option>
                                <option value="Jijel">18 Jijel</option>
                                <option value="Sétif">19 Sétif</option>
                                <option value="Saïda">20 Saïda</option>
                                <option value="Skikda">21 Skikda</option>
                                <option value="Sidi Bel Abbès">22 Sidi Bel Abbès</option>
                                <option value="Annaba">23 Annaba</option>
                                <option value="Guelma">24 Guelma</option>
                                <option value="Constantine">25 Constantine</option>
                                <option value="Médéa">26 Médéa</option>
                                <option value="Mostaganem">27 Mostaganem</option>
                                <option value="M'Sila">28 M'Sila</option>
                                <option value="Mascara">29 Mascara</option>
                                <option value="Ouargla">30 Ouargla</option>
                                <option value="Oran">31 Oran</option>
                                <option value="El Bayadh">32 El Bayadh</option>
                                <option value="Illizi">33 Illizi</option>
                                <option value="Bordj Bou Arréridj">34 Bordj Bou Arréridj</option>
                                <option value="Boumerdès">35 Boumerdès</option>
                                <option value="El Tarf">36 El Tarf</option>
                                <option value="Tindouf">37 Tindouf</option>
                                <option value="Tissemsilt">38 Tissemsilt</option>
                                <option value="El Oued">39 El Oued</option>
                                <option value="Khenchela">40 Khenchela</option>
                                <option value="Souk Ahras">41 Souk Ahras</option>
                                <option value="Tipaza">42 Tipaza</option>
                                <option value="Mila">43 Mila</option>
                                <option value="Aïn Defla">44 Aïn Defla</option>
                                <option value="Naâma">45 Naâma</option>
                                <option value="Aïn Témouchent">46 Aïn Témouchent</option>
                                <option value="Ghardaïa">47 Ghardaïa</option>
                                <option value="Relizane">48 Relizane</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Country <span style="color: red;">*</span></label>
                            <input type="text" class="form-input" name="country" required>
                        </div>
                        <div class="form-group">
                            <label>Zip/Postal Code <span style="color: red;">*</span></label>
                            <input type="text" class="form-input" name="socialCode" required>
                        </div>
                    </div>
                </div>

                <!-- Property Type Specific Sections -->
                <!-- Apartment Section -->
                <div id="apartment-section" class="property-type-section">
                    <h3><i class="fas fa-building section-icon"></i>Apartment Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Floor Number <span style="color: red;">*</span></label>
                            <input type="number" class="form-input" name="apartment_floor" min="1">
                        </div>
                        <div class="form-group">
                            <label>Building Name <span class="optional">(optional)</span></label>
                            <input type="text" class="form-input" name="apartment_building_name">
                        </div>
                        <div class="form-group">
                            <label>Has Elevator <span style="color: red;">*</span></label>
                            <select class="form-input" name="apartment_has_elevator">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Has Parking <span style="color: red;">*</span></label>
                            <select class="form-input" name="apartment_has_parking">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Monthly Maintenance Fees <span class="optional">(optional)</span></label>
                            <input type="number" class="form-input" name="apartment_monthly_fees" min="0" step="0.01">
                        </div>
                    </div>
                </div>

                <!-- House Section -->
                <div id="house-section" class="property-type-section">
                    <h3><i class="fas fa-home section-icon"></i>House Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Number of Floors <span style="color: red;">*</span></label>
                            <input type="number" class="form-input" name="house_floors" min="1" value="1">
                        </div>
                        <div class="form-group">
                            <label>Garden Size (sq ft) <span class="optional">(optional)</span></label>
                            <input type="number" class="form-input" name="house_garden_size" min="0">
                        </div>
                        <div class="form-group">
                            <label>Has Garage <span style="color: red;">*</span></label>
                            <select class="form-input" name="house_has_garage">
                                <option value="1">Yes</option>
                                <option value="0" selected>No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Garage Capacity -cars- <span class="optional">(if applicable)</span></label>
                            <input type="number" class="form-input" name="house_garage_capacity" min="0">
                        </div>
                        <div class="form-group">
                            <label>Has Basement <span style="color: red;">*</span></label>
                            <select class="form-input" name="house_has_basement">
                                <option value="1">Yes</option>
                                <option value="0" selected>No</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Villa Section -->
                <div id="villa-section" class="property-type-section">
                    <h3><i class="fas fa-landmark section-icon"></i>Villa Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Number of Floors <span style="color: red;">*</span></label>
                            <input type="number" class="form-input" name="villa_floors" min="1" value="1">
                        </div>
                        <div class="form-group">
                            <label>Garden Size (sq ft) <span style="color: red;">*</span></label>
                            <input type="number" class="form-input" name="villa_garden_size" min="0">
                        </div>
                        <div class="form-group">
                            <label>Has Swimming Pool <span style="color: red;">*</span></label>
                            <select class="form-input" name="villa_has_pool">
                                <option value="1">Yes</option>
                                <option value="0" selected>No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Has Garage <span style="color: red;">*</span></label>
                            <select class="form-input" name="villa_has_garage">
                                <option value="1">Yes</option>
                                <option value="0" selected>No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Garage Capacity <span class="optional">(if applicable)</span></label>
                            <input type="number" class="form-input" name="villa_garage_capacity" min="0">
                        </div>
                        <div class="form-group">
                            <label>Has Security System <span style="color: red;">*</span></label>
                            <select class="form-input" name="villa_security_system">
                                <option value="1">Yes</option>
                                <option value="0" selected>No</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Room Section -->
                <div id="room-section" class="property-type-section">
                    <h3><i class="fas fa-door-open section-icon"></i>Room Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Room Type <span style="color: red;">*</span></label>
                            <select class="form-input" name="room_type">
                                <option value="private">Private Room</option>
                                <option value="shared">Shared Room</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Floor Number <span style="color: red;">*</span></label>
                            <input type="number" class="form-input" name="room_floor" min="0" value="0" required>
                        </div>
                        <div class="form-group">
                            <label>Has Private Bathroom <span style="color: red;">*</span></label>
                            <select class="form-input" name="room_has_private_bathroom">
                                <option value="1">Yes</option>
                                <option value="0" selected>No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Has Private Kitchen <span style="color: red;">*</span></label>
                            <select class="form-input" name="room_has_private_kitchen">
                                <option value="1">Yes</option>
                                <option value="0" selected>No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Is Furnished <span style="color: red;">*</span></label>
                            <select class="form-input" name="room_furnished">
                                <option value="1">Yes</option>
                                <option value="0" selected>No</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-images"></i>
                        Property Photos <span style="color: red;">*</span>
                    </h2>
                    <div class="image-upload-container" id="imageUpload">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p>Click to select files</p>
                        <label for="new_images" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"> <input type="file" id="fileInput"
                                    name="property_images[]" multiple accept="image/*" class="file-input">
                            </i>
                            <!-- <span>Choose Files</span> -->
                        </label>
                        <div class="vip-lock">
                            <i class="fas fa-lock"></i>
                            Standard hosts can upload up to 3 photos. Upgrade to VIP for unlimited photos.
                        </div>
                    </div>
                    <div class="image-preview" id="imagePreview">
                        <!-- Preview images will be added here -->
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-list"></i>
                        Property Description
                    </h2>
                    <div class="form-group">
                        <label>Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-input" name="title"
                            placeholder="Enter an attractive title for your property" required>
                    </div>
                    <div class="form-group">
                        <label>Description <span style="color: red;">*</span></label>
                        <textarea class="form-input" name="description" rows="5"
                            placeholder="Describe your property in detail" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Estimated Price <span style="color: red;">*</span></label>
                        <input type="number" class="form-input" name="estimatePrice" placeholder="Enter estimated price"
                            required>
                    </div>
                </div>

                <button type="submit" class="submit-button">List Your Property</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Property type selection handling
            const propertyTypeSelect = document.getElementById('propertyType');
            const bedrooms = document.getElementById('bedrooms');
            const bathrooms = document.getElementById('bathrooms');

            // Property type specific sections
            const apartmentSection = document.getElementById('apartment-section');
            const houseSection = document.getElementById('house-section');
            const villaSection = document.getElementById('villa-section');
            const roomSection = document.getElementById('room-section');

            // Handle property type change
            propertyTypeSelect.addEventListener('change', function () {
                const selectedType = this.value.toLowerCase();

                // Hide all property type sections first
                apartmentSection.style.display = 'none';
                houseSection.style.display = 'none';
                villaSection.style.display = 'none';
                roomSection.style.display = 'none';


                // Show the appropriate section based on selection
                if (selectedType === 'apartment') {
                    apartmentSection.style.display = 'block';
                } else if (selectedType === 'house') {
                    houseSection.style.display = 'block';
                } else if (selectedType === 'villa') {
                    villaSection.style.display = 'block';
                } else {
                    roomSection.style.display = 'block';
                }
            });

            // Image upload handling
            const imageUpload = document.getElementById('imageUpload');
            const fileInput = document.getElementById('fileInput');
            const imagePreview = document.getElementById('imagePreview');
            const isVIP = <?php echo isset($isVIP) && $isVIP ? 'true' : 'false'; ?>;
            const maxImages = isVIP ? Infinity : 3;
            let uploadedImages = 0;

            fileInput.addEventListener('change', function (e) {
                const files = Array.from(e.target.files);

                // Validate number of files
                if (files.length > maxImages && !isVIP) {
showErrorMessage(`You can only upload ${maxImages} images. Upgrade to VIP for unlimited uploads.`);
                    return;
                }

                // Validate file types and sizes
                const validFiles = files.filter(file => {
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                    const maxSize = 30 * 1024 * 1024; // 30MB

                    if (!validTypes.includes(file.type)) {
showErrorMessage(`File ${file.name} is not a valid image type. Please use JPG, PNG or GIF.`);
                        return false;
                    }

                    if (file.size > maxSize) {
showErrorMessage(`File ${file.name} is too large. Maximum size is 30MB.`);
                        return false;
                    }

                    return true;
                });

                if (validFiles.length === 0) return;

                // Clear previous previews
                imagePreview.innerHTML = '';
                uploadedImages = 0;

                // Create previews
                validFiles.forEach(file => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const preview = document.createElement('div');
                        preview.className = 'preview-item';
                        preview.innerHTML = `
                            <img src="${e.target.result}" alt="Property Image">
                            <div class="remove-image" data-filename="${file.name}">×</div>
                        `;

                        preview.querySelector('.remove-image').addEventListener('click', function () {
                            const filename = this.dataset.filename;
                            preview.remove();
                            uploadedImages--;

                            // Update the actual file input
                            const dt = new DataTransfer();
                            const currentFiles = Array.from(fileInput.files);
                            currentFiles.forEach(f => {
                                if (f.name !== filename) {
                                    dt.items.add(f);
                                }
                            });
                            fileInput.files = dt.files;
                        });

                        imagePreview.appendChild(preview);
                        uploadedImages++;
                    };
                    reader.readAsDataURL(file);
                });

                // Update the actual file input
                const dt = new DataTransfer();
                validFiles.forEach(file => dt.items.add(file));
                fileInput.files = dt.files;
            });

            // Update VIP status display
            document.querySelector('.status-badge').textContent = isVIP ? 'VIP Host' : 'Standard Host';
            if (isVIP) {
                document.querySelector('.vip-lock').style.display = 'none';
                document.querySelector('.upgrade-button').style.display = 'none';
            }
        });

        // Form Submission
        document.getElementById('hostForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);

            try {
                const response = await fetch('php files/host_process.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // // alert('Property listed successfully!');
                    // showToast('Property listed successfully!');
                    showSuccessMessage('Property listed successfully!');
                    window.location.href = 'become-host.php'; // Redirect to dashboard or property listing
                } else {
showErrorMessage(result.message || 'Failed to list property');
                }
            } catch (error) {
                console.error('Error:', error);
                // alert('An error occurred while submitting the form');
                $_SESSION['error'] = 'An error occurred while submitting the form';
                window.location.href = 'become-host.php';

            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationContainer = document.getElementById('notificationContainer');
            const notificationList = document.getElementById('notificationList');

            // Toggle dropdown
            notificationIcon.addEventListener('click', function () {
                notificationContainer.classList.toggle('show');
                fetch('php files/fetch_notifications.php?action=mark_read');
            });

            // Fetch notifications
            fetch('php files/fetch_notifications.php?action=fetch')
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
                fetch('php files/fetch_notifications.php?action=clear', {
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
                fetch('php files/fetch_notifications.php?action=mark_read', {
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

        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert ${type}`;
            alert.textContent = message;

            alertContainer.appendChild(alert);

            setTimeout(() => {
                alert.remove();
            }, 4000);
        }

        function showSuccessMessage(message) {
            showAlert(message, 'success');
        }

        function showErrorMessage(message) {
            showAlert(message, 'error');
        }


    </script>
</body>

</html>
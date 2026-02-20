<?php 
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login-signup/index.html');
    exit();
}

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
        WHERE po.id_propertyOwner = ? AND pov.VipEntDate >= CURRENT_DATE
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
    </style>
</head>

<body>
    <!-- Include header from index.html -->
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">RentEstate</a>
            <div class="user-menu">
                <div class="user-menu-item menu-dropdown">
                    <i class="fas fa-bars" id="menuToggle"></i>
                    <div class="menu-content" id="menuContent">
                        <a href="index.php" class="menu-item">
                            <i class="fas fa-home"></i>
                            dashboard
                        </a>
                        <div class="menu-divider"></div>

                        <a href="#" class="menu-item">
                            <i class="fas fa-question-circle"></i>
                            Help Center
                        </a>
                        <a href="#" class="menu-item" id="language-toggle">
                            <i class="fas fa-globe"></i>
                            Language
                        </a>
                        <div class="language-menu" id="language-menu">
                            <ul>
                                <li><a href="#" data-lang="en">English</a></li>
                                <li><a href="#" data-lang="es">Spanish</a></li>
                                <li><a href="#" data-lang="fr">French</a></li>
                                <li><a href="#" data-lang="de">German</a></li>
                                <li><a href="#" data-lang="zh">Chinese</a></li>
                            </ul>
                        </div>
                        <a href="#" class="menu-item">
                            <i class="fas fa-info-circle"></i>
                            About Us
                        </a>

                        <div class="menu-divider"></div>
                        <a href="../login-signup/php files/logout.php" class="menu-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="host-container">
        <div class="page-title">
            <h1>Become a Host </h1>
            <p>Share your space and earn extra income</p>
        </div>

        <div class="membership-status">
            <div class="status-info">
                <div class="status-badge">Standard Host</div>
                <p>You can list up to 3 photos per property</p>
            </div>
            <?php if (!$isVIP): ?>
            <button class="upgrade-button" onclick="window.location.href='vip-upgrade.html';">Upgrade to VIP</button>
            <?php endif; ?>
        </div>

        <form action="php files/host_process.php" method="post" id="hostForm" enctype="multipart/form-data">
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-user"></i>
                    Personal Information
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-input" name="full_name"
                            value="<?php echo htmlspecialchars($userData['full_name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-input" name="email"
                            value="<?php echo htmlspecialchars($userData['email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" class="form-input" name="phone"
                            value="<?php echo htmlspecialchars($userData['phone']); ?>"
                            placeholder="Enter your phone number" required>
                    </div>
                    <div class="form-group">
                        <label>2nd phone number</label>
                        <input type="tel" class="form-input" name="phone2" placeholder="Enter your second phone number">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-home"></i>
                    Property Details
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Property Type</label>
                        <select class="form-input" name="propertyType" id="propertyType" required>
                            <option value="">Select type</option>
                            <option value="apartment">Apartment</option>
                            <option value="house">House</option>
                            <option value="villa">Villa</option>
                            <option value="room">single room</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>I Am :</label>
                        <select class="form-input" name="ownerNeeds" required>
                            <?php if ($isVIP): ?>
                                <option value="selling">Selling</option>
                            <?php endif; ?>
                            <option value="renting">Renting</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Property Size (sq ft)</label>
                        <input type="number" class="form-input" name="size" placeholder="Enter size" required>
                    </div>
                    <div class="form-group">
                        <label>Bedrooms</label>
                        <input type="number" class="form-input" name="bedrooms" id="bedrooms" required>
                    </div>
                    <div class="form-group">
                        <label>Bathrooms</label>
                        <input type="number" class="form-input" name="bathrooms" id="bathrooms" required>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" class="form-input" name="address" placeholder="Enter property address"
                            required>
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <select name="city" id="city" class="form-input" required>
                            <option value="">--Select City--</option>
                            <option value="Adrar">Adrar</option>
                            <option value="Chlef">Chlef</option>
                            <option value="Laghouat">Laghouat</option>
                            <option value="Oum El Bouaghi">Oum El Bouaghi</option>
                            <option value="Batna">Batna</option>
                            <option value="Béjaïa">Béjaïa</option>
                            <option value="Biskra">Biskra</option>
                            <option value="Béchar">Béchar</option>
                            <option value="Blida">Blida</option>
                            <option value="Bouira">Bouira</option>
                            <option value="Tamanrasset">Tamanrasset</option>
                            <option value="Tébessa">Tébessa</option>
                            <option value="Tlemcen">Tlemcen</option>
                            <option value="Tiaret">Tiaret</option>
                            <option value="Tizi Ouzou">Tizi Ouzou</option>
                            <option value="Algiers">Algeria</option>
                            <option value="Djelfa">Djelfa</option>
                            <option value="Jijel">Jijel</option>
                            <option value="Sétif">Sétif</option>
                            <option value="Saïda">Saïda</option>
                            <option value="Skikda">Skikda</option>
                            <option value="Sidi Bel Abbès">Sidi Bel Abbès</option>
                            <option value="Annaba">Annaba</option>
                            <option value="Guelma">Guelma</option>
                            <option value="Constantine">Constantine</option>
                            <option value="Médéa">Médéa</option>
                            <option value="Mostaganem">Mostaganem</option>
                            <option value="MSila">M'Sila</option>
                            <option value="Mascara">Mascara</option>
                            <option value="Ouargla">Ouargla</option>
                            <option value="Oran">Oran</option>
                            <option value="El Bayadh">El Bayadh</option>
                            <option value="Illizi">Illizi</option>
                            <option value="Bordj Bou Arréridj">Bordj Bou Arréridj</option>
                            <option value="Boumerdès">Boumerdès</option>
                            <option value="El Tarf">El Tarf</option>
                            <option value="Tindouf">Tindouf</option>
                            <option value="Tissemsilt">Tissemsilt</option>
                            <option value="El Oued">El Oued</option>
                            <option value="Khenchela">Khenchela</option>
                            <option value="Souk Ahras">Souk Ahras</option>
                            <option value="Tipaza">Tipaza</option>
                            <option value="Mila">Mila</option>
                            <option value="Aïn Defla">Aïn Defla</option>
                            <option value="Naâma">Naâma</option>
                            <option value="Aïn Témouchent">Aïn Témouchent</option>
                            <option value="Ghardaïa">Ghardaïa</option>
                            <option value="Relizane">Relizane</option>
                        </select>

                    </div>
                    <div class="form-group">
                        <label>State/Province</label>
                        <input type="text" class="form-input" name="state" required>
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" class="form-input" name="country" required>
                    </div>
                    <div class="form-group">
                        <label>Zip/Postal Code</label>
                        <input type="text" class="form-input" name="socialCode" required>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-images"></i>
                    Property Photos
                </h2>
                <div class="image-upload-container" id="imageUpload">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <p>Click to select files</p>
                    <input type="file" id="fileInput" name="property_images[]" multiple accept="image/*"
                        class="file-input">
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
                    <label>Title</label>
                    <input type="text" class="form-input" name="title"
                        placeholder="Enter an attractive title for your property" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea class="form-input" name="description" rows="5"
                        placeholder="Describe your property in detail" required></textarea>
                </div>
                <div class="form-group">
                    <label>Estimated Price</label>
                    <input type="number" class="form-input" name="estimatePrice" placeholder="Enter estimated price"
                        required>
                </div>
            </div>

            <button type="submit" class="submit-button">List Your Property</button>
        </form>
    </div>

    <script>
        // disable bathrooms and Bedrooms for single room
        document.getElementById('propertyType').addEventListener('change', function () {
        const selectedType = this.value.toLowerCase();
        const bedroomsField = document.getElementById('bedrooms');
        const bathroomsField = document.getElementById('bathrooms');

        if (selectedType === 'room') {
            bedroomsField.disabled = true;
            bathroomsField.disabled = true;
            bedroomsField.value = 1;
            bathroomsField.value = 1;
        } else {
            bedroomsField.disabled = false;
            bathroomsField.disabled = false;
        }
    });

        document.addEventListener('DOMContentLoaded', function () {
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
                    alert(`You can only upload ${maxImages} images. Upgrade to VIP for unlimited uploads.`);
                    return;
                }

                // Validate file types and sizes
                const validFiles = files.filter(file => {
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    const maxSize = 5 * 1024 * 1024; // 5MB

                    if (!validTypes.includes(file.type)) {
                        alert(`File ${file.name} is not a valid image type. Please use JPG, PNG or GIF.`);
                        return false;
                    }

                    if (file.size > maxSize) {
                        alert(`File ${file.name} is too large. Maximum size is 5MB.`);
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
                    alert('Property listed successfully!');
                    window.location.href = 'index.php'; // Redirect to dashboard or property listing
                } else {
                    alert('Error: ' + (result.message || 'Failed to list property'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while submitting the form');
            }
        });
    </script>
</body>

</html>
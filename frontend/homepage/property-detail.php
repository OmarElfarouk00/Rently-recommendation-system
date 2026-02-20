<!-- not working with this file -->

<?php
session_start();

// Database connection
require_once 'php files/config.php';

// Fetch property data
try {
    $stmt = $pdo->prepare("SELECT * FROM Property WHERE id_property = ?");
    $stmt->execute([$_SESSION['user_id']]); // Assuming the property ID is passed via URL
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        echo "Property not found";
        exit();
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Detail - RentEstate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
    <style>
        /* Include the base styles from index.html
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        :root {
            --primary-color: #ee7238;
            --text-color: #2c3e50;
            --light-gray: #f8f9fa;
            --border-color: #eaeaea;
        }
 */
        body {
            background-color: var(--light-gray);
        }


        /* Responsive Design */
        @media (max-width: 768px) {
            .property-gallery {
                grid-template-columns: 1fr;
                height: auto;
            }

            .gallery-grid {
                display: none;
            }

            .property-content {
                grid-template-columns: 1fr;
            }

            .booking-card {
                position: static;
                margin-top: 2rem;
            }

            .amenities {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Include the same header from index.html -->
    <!-- Header -->
    <header class="header">

        <div class="header-content">
            <div class="logo">
                    <a href="index.php" class="logo">
                        <img src="../rently2.png" alt="" style="height: 38px; width: 130px;">
                    </a>
            </div>

            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search destinations, properties...">
            </div>

            <div class="nav-item" id="rent-nav">
                <i class="fas fa-handshake"></i>
                Rent
            </div>

            
                <div class="nav-item" id="sell-nav">
                    <i class="fas fa-tag"></i>
                    Buy
                </div>
            




            <div class="user-menu">

                <div class="user-menu-item menu-dropdown">
                    <i class="fas fa-bars" id="menuToggle"></i>
                    <div class="menu-content" id="menuContent">
                        <?php if (!$isLoggedIn): ?>
                            <a href="../login-signup/index.php" class="menu-item">
                                <i class="fas fa-sign-in-alt"></i>
                                Login
                            </a>
                            <a href="../login-signup/signup.html" class="menu-item">
                                <i class="fas fa-user-plus"></i>
                                Sign Up
                            </a>
                        <?php endif; ?>
                        <?php if (!$isLoggedIn): ?>
                            <a href="../login-signup/index.php" class="menu-item">
                                <i class="fas fa-arrow-right"></i>
                                become a host
                            <?php else: ?>
                                <a href="become-host.php" class="menu-item">
                                    <i class="fas fa-arrow-right"></i>
                                    become a host
                                </a>
                            <?php endif; ?>
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
                            <div class="menu-divider"></div>
                            <a href="#" class="menu-item">
                                <i class="fas fa-info-circle"></i>
                                About Us
                            </a>
                            <?php if ($hasProperties): ?>
                                <a href="my-properties.php" class="menu-item">
                                    <i class="fas fa-home"></i>
                                    my properties
                                </a>
                            <?php endif; ?>
                            <?php if ($isLoggedIn): ?>

                                <div class="menu-divider"></div>
                                <a href="../login-signup/php files/logout.php" class="menu-item">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Logout
                                </a>
                            <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>

    </header>

    <div class="property-container">
    <div class="property-gallery">
        <div class="main-image">
            <img src="<?php echo htmlspecialchars($property['main_image']); ?>" alt="Property Main View">
            <div class="view-all-photos">
                <i class="fas fa-images"></i> View all photos
            </div>
        </div>
        <div class="gallery-grid">
            <div class="gallery-item">
                <img src="<?php echo htmlspecialchars($property['image_1']); ?>" alt="Property Image 2">
            </div>
            <div class="gallery-item">
                <img src="<?php echo htmlspecialchars($property['image_2']); ?>" alt="Property Image 3">
            </div>
        </div>
    </div>

    <div class="property-content">
        <div class="property-info">
            <h1 class="property-title"> <?php echo htmlspecialchars($property['title']); ?> </h1>

            <div class="property-meta">
                <div class="meta-item"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?></div>
                <div class="meta-item"><i class="fas fa-home"></i> <?php echo htmlspecialchars($property['propertyType']); ?></div>
                <div class="meta-item"><i class="fas fa-ruler-combined"></i> <?php echo htmlspecialchars($property['size']); ?> sq ft</div>
            </div>

            <div class="property-description">
                <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
            </div>

            <h2>Amenities</h2>
            <div class="amenities">
                <div class="amenity-item"><i class="fas fa-bed"></i> <?php echo htmlspecialchars($property['bedrooms']); ?> Bedrooms</div>
                <div class="amenity-item"><i class="fas fa-bath"></i> <?php echo htmlspecialchars($property['bathrooms']); ?> Bathrooms</div>
                <div class="amenity-item"><i class="fas fa-wifi"></i> High-speed WiFi</div>
                <div class="amenity-item"><i class="fas fa-swimming-pool"></i> Private Pool</div>
                <div class="amenity-item"><i class="fas fa-parking"></i> Parking Available</div>
                <div class="amenity-item"><i class="fas fa-wind"></i> Air Conditioning</div>
            </div>
        </div>

        <div class="booking-card">
            <div class="booking-price">
                $<?php echo htmlspecialchars($property['price']); ?> <span>/night</span>
            </div>
            <form class="booking-form" method="POST" action="book.php">
                <input type="hidden" name="property_id" value="<?php echo $property['id_property']; ?>">
                <div class="date-inputs">
                    <input type="date" class="form-input" name="checkin" required>
                    <input type="date" class="form-input" name="checkout" required>
                </div>
                <input type="number" class="form-input" name="guests" placeholder="Number of guests" min="1">
                <button type="submit" class="book-button">Book Now</button>
            </form>
            <button type="button" class="negotiate-button" onclick="showNegotiationPopup()">
                <i class="fas fa-handshake"></i> Negotiate Price
            </button>

            <div class="host-info">
                <div class="host-avatar">
                    <img src="<?php echo htmlspecialchars($property['host_image']); ?>" alt="Host">
                </div>
                <div class="host-details">
                    <h3>Hosted by <?php echo htmlspecialchars($property['host_name']); ?></h3>
                    <p>Superhost · 245 reviews</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showNegotiationPopup() {
        alert('Negotiation popup coming soon!');
    }
</script>

</body>
</html>




                <!-- <div class="property-amenities">
                    <h2>Amenities</h2>
                    <div class="amenities-grid">
                    <?php
                        // Fetch specific amenities based on property type
                        if ($property['propertyType'] == 'apartment') {
                            try {
                                $stmt = $pdo->prepare("SELECT * FROM Apartment WHERE id_apartment = ?");
                                $stmt->execute([$property['id_property']]);
                                $amenities = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($amenities) {
                                    echo '<div class="amenity"><i class="fas fa-building"></i> Floor: ' . htmlspecialchars($amenities['floorNumber']) . '</div>';
                                    echo '<div class="amenity"><i class="fas fa-' . ($amenities['hasElevator'] ? 'check' : 'times') . '"></i> Elevator</div>';
                                    echo '<div class="amenity"><i class="fas fa-' . ($amenities['hasConcierge'] ? 'check' : 'times') . '"></i> Concierge</div>';
                                    echo '<div class="amenity"><i class="fas fa-money-bill"></i> Monthly Fees: ' . htmlspecialchars($amenities['monthlyFees']) . '</div>';
                                }
                            } catch (PDOException $e) {
                                // Handle error silently
                            }
                        } elseif ($property['propertyType'] == 'villa') {
                            try {
                                $stmt = $pdo->prepare("SELECT * FROM Villa WHERE id_villa = ?");
                                $stmt->execute([$property['id_property']]);
                                $amenities = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($amenities) {
                                    echo '<div class="amenity"><i class="fas fa-tree"></i> Garden: ' . htmlspecialchars($amenities['gardenSize']) . ' m²</div>';
                                    echo '<div class="amenity"><i class="fas fa-' . ($amenities['hasPool'] ? 'check' : 'times') . '"></i> Swimming Pool</div>';
                                    echo '<div class="amenity"><i class="fas fa-car"></i> Parking Spaces: ' . htmlspecialchars($amenities['parkingSpaces']) . '</div>';
                                    echo '<div class="amenity"><i class="fas fa-' . ($amenities['hasGuestHouse'] ? 'check' : 'times') . '"></i> Guest House</div>';
                                }
                            } catch (PDOException $e) {
                                // Handle error silently
                            }
                        }
                        ?>
                        <div class="amenity"><i class="fas fa-wifi"></i> WiFi</div>
                        <div class="amenity"><i class="fas fa-snowflake"></i> Air Conditioning</div>
                        <div class="amenity"><i class="fas fa-tv"></i> TV</div>
                        <div class="amenity"><i class="fas fa-utensils"></i> Kitchen</div>
                    </div>
                </div> -->

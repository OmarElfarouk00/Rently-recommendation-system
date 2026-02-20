<!-- the original version -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Real Estate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <!-- <script src="script.js" defer></script> -->
</head>

<body>

    <?php
    session_start();

    // $stmt = $pdo->prepare("SELECT OwnerNeeds,  FROM property WHERE email = ?");
    // $stmt->execute([$email]);
    // $user = $stmt->fetch();
    // $_SESSION['']=$user['OwnerNeeds'];

        // Check if the user is logged in
    $isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in
    $userName = $isLoggedIn ? $_SESSION['user_name'] : null; // Get the user's name if logged in

    // Check if the user has properties
    $hasProperties = $isLoggedIn ? getProperties($_SESSION['user_id']) : false;

    // Define a function to check if the user has properties
    function getProperties($userId)
    {
        require_once 'php files/config.php';

        // Query to fetch properties owned by the user
        $stmt = $pdo->prepare("
        SELECT p.* 
        FROM Property p
        JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
        JOIN Client c ON po.id_propertyOwner = c.id_client
        WHERE c.id_client = ?
    ");
        $stmt->execute([$userId]);

        // Return true if the user has at least one property
        return $stmt->rowCount() > 0;

    }

    ?>

    <!-- Header -->
    <header class="header">

        <div class="header-content">
            <div class="logo">
                <i class="fas fa-home"></i>
                <a href="." class="logo">RentEstate</a>
            </div>

            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search destinations, properties...">
            </div>

            <!-- Filter Button -->
            <!-- <button id="filterBtn" class="filter-button">Filter Properties</button> -->

            <div class="nav-item" id="filterBtn">
                <i class="fas fa-filter"></i>
                Filter
            </div>

            <!-- Filter Modal -->
            <div id="filterModal" class="filter-modal">
                <div class="filter-content">
                    <span class="close">&times;</span>
                    <h2>Filter Properties</h2>
                    <form id="filterForm">
                        <label for="bedrooms">Bedrooms:</label>
                        <input type="number" name="bedrooms" id="bedrooms" min="0">

                        <label for="bathrooms">Bathrooms:</label>
                        <input type="number" name="bathrooms" id="bathrooms" min="0">

                        <label for="min_price">Min Price ($):</label>
                        <input type="number" name="min_price" id="min_price" min="0">

                        <label for="max_price">Max Price ($):</label>
                        <input type="number" name="max_price" id="max_price" min="0">

                        <label for="type">Type:</label>
                        <select name="type" id="type">
                            <option value="renting">Renting</option>
                            <option value="selling">Selling</option>
                        </select>

                        <label for="city">City:</label>
                        <select name="city" id="city">
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

                        <button type="submit" class="submit-filter">Apply Filters</button>
                        <button type="reset" class="submit-filter"> reset filter</button>
                    </form>
                </div>
            </div>


            <div class="nav-item" id="rent-nav">
                <i class="fas fa-handshake"></i>
                Rent
            </div>


            <div class="nav-item" id="sell-nav">
                <i class="fas fa-tag"></i>
                Buy
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


            <div class="user-menu">

                <div class="user-menu-item menu-dropdown">
                    <i class="fas fa-bars" id="menuToggle"></i>
                    <div class="menu-content" id="menuContent">
                        <?php if (!$isLoggedIn): ?>
                            <a href="../login-signup/index.php" class="menu-item">
                                <i class="fas fa-sign-in-alt"></i>
                                Login
                            </a>
                            <a href="../login-signup/signup.php" class="menu-item">
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
                            <?php if ($hasProperties): ?>
                                <a href="my-properties.php" class="menu-item">
                                    <i class="fas fa-home"></i>
                                    my properties
                                </a>
                            <?php endif; ?>
                            <div class="menu-divider"></div>
                            <a href="#" class="menu-item" id="language-toggle">
                                <i class="fas fa-globe"></i>
                                Language
                            </a>
                            <a href="help-center.php" class="menu-item">
                                <i class="fas fa-question-circle"></i>
                                Help Center
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
                            <a href="about-us.php" class="menu-item">
                                <i class="fas fa-info-circle"></i>
                                About Us
                            </a>

                            <a href="settings.php" class="menu-item active">
                            <i class="fas fa-cog"></i>
                            Settings
                            </a>

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

    <!-- Category Navigation -->
    <nav class="category-nav">
        <div class="category-list">
            <div class="category-item active" id="villa">
                <i class="fas fa-calendar-alt category-icon"></i>
                <span name="villa">Event</span>
            </div>
            <div class="category-item" id="house">
                <i class="fas fa-home category-icon"></i>
                <span name="house">House</span>
            </div>
            <div class="category-item" id="apartment">
                <i class="fas fa-building category-icon"></i>
                <span name="apartment">Apartment</span>
            </div>
            <div class="category-item" id="room">
                <i class="fas fa-bed category-icon"></i>
                <span name="room">room</span>
            </div>

        </div>
    </nav>



    <!-- Main Content -->
    <main class="main-content">
        <div class="listings-grid" id="propertiesContainer">
            <div class="loading">
                <div class="loading-spinner"></div>
                <p>Loading properties...</p>
            </div>
        </div>
    </main>

    <script>
        // Function to format price
        function formatPrice(price) {
            return new Intl.NumberFormat('fr-DZ', {
                style: 'currency',
                currency: 'DZD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(price);
        }



                // Add event listener to the "Sell" navigation item

        // Function to fetch properties for rent
        function fetchPropertiesForRent() {
            fetch('php files/fetch-properties.php?type=rent')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear the current property grid
                        const propertyGrid = document.querySelector('.property-grid');
                        propertyGrid.innerHTML = '';

                        // Display only properties for rent
                        data.properties.forEach(property => {
                            const propertyCard = createPropertyCard(property);
                            propertyGrid.appendChild(propertyCard);
                        });
                    } else {
                        alert('Failed to fetch properties for rent.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }






        //  Function to create property card
        function createPropertyCard(property) {
    const imagePath = property.main_image 
        ? `php files/${property.main_image}` 
        : '/placeholder.svg?height=200&width=300';

    return `
    <a href="property.php?id=${property.id_property}" class="listing-card-link">
        <div class="listing-card ${property.is_vip ? 'vip-property' : ''}">
            <div class="listing-image">
                <img src="${imagePath}" alt="${property.title}">
                ${property.is_vip ? '<span class="vip-badge" style=" font-weight: bold;">VIP Property</span>' : ''}
                <button class="favorite-btn" data-property-id="${property.id_property}">
                    <i class="far fa-heart"></i>
                </button>
            </div>

            <div class="listing-info">
                <h3 class="listing-title">${property.title}</h3>
                <div class="listing-location">
                    <i class="fas fa-map-marker-alt"></i>
                    ${property.city}, ${property.state}, ${property.address}
                </div>
                <div class="listing-details">
                    <span><i class="fas fa-bed"></i> ${property.bedrooms} beds</span>
                    <span><i class="fas fa-bath"></i> ${property.bathrooms} baths</span>
                    <span><i class="fas fa-ruler-combined"></i> ${property.size} m²</span>
                </div>
                <div class="listing-price">
                    <span class="price-value">${formatPrice(property.estimatePrice)}</span>
                </div>
            </div>
        </div>
    </a>`;
}

        // Function to handle favorite button clicks
        function handleFavoriteClick(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const button = event.currentTarget;
    const propertyId = button.dataset.propertyId;
    const icon = button.querySelector('i');

    // Toggle heart icon
    if (icon.classList.contains('far')) {
        icon.classList.remove('far');
        icon.classList.add('fas');
        // Add to favorites in database
        addToFavorites(propertyId);
    } else {
        icon.classList.remove('fas');
        icon.classList.add('far');
        // Remove from favorites in database
        removeFromFavorites(propertyId);
    }
}
        // Function to add property to favorites
// Add to favorites function
function addToFavorites(propertyId) {
    fetch('php files/add_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            property_id: propertyId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success && data.message === 'login_required') {
alert('Please log in to add properties to favorites.');
        } else if (data.success) {
            console.log('Added to favorites');
        } else {
            console.error('Error:', data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Remove from favorites function
function removeFromFavorites(propertyId) {
    fetch('php files/remove_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            property_id: propertyId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Removed from favorites');
        } else {
            console.error('Error:', data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}
        // Fetch and display properties
        document.addEventListener('DOMContentLoaded', () => {
            const propertiesContainer = document.getElementById('propertiesContainer');

            fetch('php files/fetch_properties.php')
                .then(response => response.json())
                .then(properties => {
                    propertiesContainer.innerHTML = ''; // Clear loading message

                    if (properties.length === 0) {
                        propertiesContainer.innerHTML = `
                            <div class="no-properties">
                                <i class="fas fa-home" style="font-size: 3rem; color: var(--primary-color);"></i>
                                <h2>No Properties Available</h2>
                                <p>Check back later for new listings!</p>
                            </div>
                        `;
                        return;
                    }

                    properties.forEach(property => {
                        propertiesContainer.innerHTML += createPropertyCard(property);
                    });

                    // Add event listeners to favorite buttons
                    document.querySelectorAll('.favorite-btn').forEach(button => {
                        button.addEventListener('click', handleFavoriteClick);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    propertiesContainer.innerHTML = `
                        <div class="no-properties">
                            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #dc3545;"></i>
                            <h2>Error Loading Properties</h2>
                            <p>Please try again later.</p>
                        </div>
                    `;
                });
        });


        // Category Navigation
        const categoryItems = document.querySelectorAll('.category-item');
        categoryItems.forEach(item => {
            item.addEventListener('click', () => {
                categoryItems.forEach(i => i.classList.remove('active'));
                item.classList.add('active');
            });
        });

        // Favorite Button Functionality
        const favoriteButtons = document.querySelectorAll('.favorite-btn');
        favoriteButtons.forEach(button => {
            button.addEventListener('click', () => {
                button.classList.toggle('active');
                const icon = button.querySelector('i');
                if (button.classList.contains('active')) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                }
            });
        });

        // Add this to your existing JavaScript
        const menuToggle = document.getElementById('menuToggle');
        const menuContent = document.getElementById('menuContent');
        // const languageSelector = document.querySelector('.language-selector');

        // Toggle menu on click
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            menuContent.classList.toggle('active');
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!menuContent.contains(e.target) && !menuToggle.contains(e.target)) {
                menuContent.classList.remove('active');
            }
        });

        // Language selection function
        document.addEventListener('DOMContentLoaded', function () {
            const languageToggle = document.getElementById('language-toggle');
            const languageMenu = document.getElementById('language-menu');

            // Toggle the visibility of the language menu
            languageToggle.addEventListener('click', function (event) {
                event.preventDefault(); // Prevent the default anchor behavior
                languageMenu.style.display = languageMenu.style.display === 'block' ? 'none' : 'block';
            });

            // // Change language on click
            // const languageLinks = document.querySelectorAll('.language-menu a');
            // languageLinks.forEach(link => {
            //     link.addEventListener('click', function (event) {
            //         event.preventDefault(); // Prevent the default anchor behavior
            //         const selectedLanguage = this.getAttribute('data-lang');
            //         changeLanguage(selectedLanguage);
            //         languageMenu.style.display = 'none'; // Hide the menu after selection
            //     });
            // });

            // Function to change the language
            function changeLanguage(lang) {
                // Here you can implement the logic to change the language
                // For example, you could load a different language file or update the text on the page
                console.log('Language changed to:', lang);
                // You can also store the selected language in localStorage or cookies if needed
            }

            // Close the menu if clicked outside
            window.addEventListener('click', function (event) {
                if (!languageToggle.contains(event.target) && !languageMenu.contains(event.target)) {
                    languageMenu.style.display = 'none';
                }
            });
        });

        // Mobile-specific handling for language selector
        if (window.innerWidth <= 768) {
            languageSelector.addEventListener('click', (e) => {
                e.stopPropagation();
                languageSelector.classList.toggle('active');
            });
        }


        //starts here
        document.addEventListener('DOMContentLoaded', function () {
    const rentNav = document.getElementById('rent-nav'); 
    const sellNav = document.getElementById('sell-nav'); 
    const filterBtn = document.getElementById("filterBtn"); 
    const closeBtn = document.querySelector(".close"); 
    const modal = document.getElementById("filterModal"); 
    const filterForm = document.getElementById("filterForm");

    const eventCategory = document.getElementById("villa");
    const apartmentCategory = document.getElementById("apartment");
    const houseCategory = document.getElementById("house");
    const roomCategory = document.getElementById("room");

    let selectedType = 'renting';       // Default type
    let selectedCategory = null;        // No default category

    // Navigation events    
    rentNav.addEventListener('click', () => {
        rentNav.classList.add('active-nav');
        sellNav.classList.remove('active-nav');
        selectedType = 'renting';
        fetchProperties(selectedType, {}, selectedCategory);
    });

    sellNav.addEventListener('click', () => {
        sellNav.classList.add('active-nav');
        rentNav.classList.remove('active-nav');
        selectedType = 'selling';
        fetchProperties(selectedType, {}, selectedCategory);
    });

    // Category clicks
    [eventCategory, apartmentCategory, houseCategory , roomCategory].forEach(cat => {
        cat.addEventListener("click", () => {
            // Clear active classes
            [eventCategory, apartmentCategory, houseCategory , roomCategory].forEach(c => c.classList.remove('active'));
            cat.classList.add('active');
            selectedCategory = cat.id; // 'villa', 'apartment', 'house'
            fetchProperties(selectedType, {}, selectedCategory);
        });
    });

    // Modal show/hide
    filterBtn.onclick = () => modal.style.display = "block";
    closeBtn.onclick = () => modal.style.display = "none";
    window.onclick = e => { if (e.target === modal) modal.style.display = "none"; };

    // Handle filter form submit
    filterForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const filters = {
            bedrooms: document.getElementById("bedrooms").value,
            bathrooms: document.getElementById("bathrooms").value,
            min_price: document.getElementById("min_price").value,
            max_price: document.getElementById("max_price").value,
            city: document.getElementById("city").value
        };

        fetchProperties(selectedType, filters, selectedCategory);
        modal.style.display = "none";
    });

    function fetchProperties(type, filters = {}, category = null) {
        const mainContent = document.querySelector('.main-content');
        mainContent.innerHTML = `<div class="loading">Loading ${type} properties...</div>`;

        let url = `php files/filter.php?type=${type}`;
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                url += `&${key}=${encodeURIComponent(filters[key])}`;
            }
        });

        if (category) {
            url += `&category=${encodeURIComponent(category)}`;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    displayProperties(data.properties, mainContent, type, category);
                } else {
                    mainContent.innerHTML = `<div class="no-results"><p>${data.message}</p></div>`;
                }
            })
            .catch(error => {
                mainContent.innerHTML = `<div class="error"><p>Error: ${error.message}</p></div>`;
            });
    }

    function displayProperties(properties, container, type, category) {
        container.innerHTML = '';
        const heading = document.createElement('h2');
        heading.textContent = (type === 'renting') ? 'Properties for Rent' : 'Properties for Sale';
        heading.className = 'section-title';
        container.appendChild(heading);

        const listingsGrid = document.createElement('div');
        listingsGrid.className = 'listings-grid';

        properties.forEach(property => {
            const cardHTML = createPropertyCard(property);
            const tempContainer = document.createElement('div');
            tempContainer.innerHTML = cardHTML;
            listingsGrid.appendChild(tempContainer.firstElementChild);
        });

        container.appendChild(listingsGrid);
    }
});


    </script>
</body>

</html>
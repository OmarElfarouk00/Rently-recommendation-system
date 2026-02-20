<!-- not working with this file -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Real Estate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
    <script src="../script.js" defer></script>
    
    <style>
        .property-status {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: var(--primary-color);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .vip-property {
            border: 2px solid #ffd700;
        }

        .vip-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #ffd700;
            color: #000;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 1;
        }

        .no-properties {
            text-align: center;
            padding: 2rem;
            color: var(--text-color);
            background: white;
            border-radius: 10px;
            margin: 2rem auto;
            max-width: 500px;
        }

        .loading {
            text-align: center;
            padding: 2rem;
        }

        .loading-spinner {
            border: 4px solid var(--light-gray);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Header section remains the same -->

    <!-- Header -->
    <header class="header">
        
        <div class="header-content">
            <a href="#" class="logo">RentEstate</a>
            
            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search destinations, properties...">
            </div>

            <div class="nav-item">
                Rent
            <div class="dropdown-content">
                <div class="dropdown-item menu-item">Apartment</div>
                <a href="../login-signup/index_after.html">
                    
                </a>
                <div class="dropdown-item">House</div>
                <div class="dropdown-item">Villas</div>
            </div>
        </div>

            <div class="nav-item">
                <a class="nav-item premium-item">
                    Buy
                </a>
                <div class="dropdown-content">
                    <div class="dropdown-item">Apartment</div>
                    <div class="dropdown-item">House</div>
                    <div class="dropdown-item">Villas</div>
                </div>    
            </div>

            <div class="nav-item">
                <a class="nav-item premium-item">
                    Sell
                    <span class="premium-badge">VIP</span>
                </a>
                <div class="dropdown-content">
                    <div class="dropdown-item">Apartment</div>
                    <div class="dropdown-item">House</div>
                    <div class="dropdown-item">Villas</div>
                </div>
            </div>

            <div class="user-menu">
                
                <div class="user-menu-item menu-dropdown">
                    <i class="fas fa-bars" id="menuToggle"></i>
                    <div class="menu-content" id="menuContent">

                        <a href="become-host.html" class="menu-item">
                            <i class="fas fa-arrow-right"></i>
                            become a host
                        </a>
                        <a href="user-negotiations.html"class="menu-item"><i class="fas fa-comments"></i>negotiations</a></a>
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

                    <div class="menu-divider"></div>
                        <a href="index.html" class="menu-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>

                    </div>
                </div>
            </div>
            
        </div>
    
    </header>

    <!-- Category Navigation -->
    <nav class="category-nav">
        <div class="category-list">
            <div class="category-item active">
                <i class="fas fa-calendar-alt category-icon"></i>
                <span>Events</span>
            </div>
            <div class="category-item">
                <i class="fas fa-umbrella-beach category-icon"></i>
                <span>Vacation</span>
            </div>
            <div class="category-item">
                <i class="fas fa-home category-icon"></i>
                <span>Family</span>
            </div>
            <div class="category-item">
                <i class="fas fa-building category-icon"></i>
                <span>Apartment</span>
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
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(price);
        }

        // Function to create property card
        function createPropertyCard(property) {
            return `
                <div class="listing-card ${property.is_vip ? 'vip-property' : ''}">
                    <div class="listing-image">
                        <img src="/placeholder.svg?height=200&width=300" alt="${property.title}">
                        ${property.is_vip ? '<span class="vip-badge">VIP Property</span>' : ''}
                        <button class="favorite-btn" data-property-id="${property.property_id}">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                    <div class="listing-info">
                        <h3 class="listing-title">${property.title}</h3>
                        <div class="listing-location">
                            <i class="fas fa-map-marker-alt"></i>
                            ${property.city}, ${property.state}
                        </div>
                        <div class="listing-details">
                            <span><i class="fas fa-bed"></i> ${property.bedrooms} beds</span>
                            <span><i class="fas fa-bath"></i> ${property.bathrooms} baths</span>
                        </div>
                        <div class="listing-price">
                            <span class="price-value">${formatPrice(property.estimated_price)}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        // Function to handle favorite button clicks
        function handleFavoriteClick(event) {
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
        function addToFavorites(propertyId) {
            fetch('add_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ property_id: propertyId })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Please login to save favorites');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Function to remove property from favorites
        function removeFromFavorites(propertyId) {
            fetch('remove_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ property_id: propertyId })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error removing from favorites');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Fetch and display properties
        document.addEventListener('DOMContentLoaded', () => {
            const propertiesContainer = document.getElementById('propertiesContainer');

            fetch('fetch_properties.php')
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

        // Rest of your existing JavaScript (category navigation, menu toggle, etc.)
    </script>
</body>
</html>
<?php
require_once 'config.php';

try {
    // Start transaction
    $pdo->beginTransaction();

    // 1. Insert a test client
    $stmt = $pdo->prepare("
        INSERT INTO Client (full_name, email, phone, password) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        'John Doe',
        'john@example.com',
        '1234567890',
        password_hash('password123', PASSWORD_DEFAULT)
    ]);
    $clientId = $pdo->lastInsertId();

    // 2. Insert a property owner
    $stmt = $pdo->prepare("
        INSERT INTO Owner (client_id, property_id) 
        VALUES (?, ?)
    ");
    
    // 3. Insert test properties
    $propertyStmt = $pdo->prepare("
        INSERT INTO Property (
            owner_id, description, title, property_type, 
            size, bedrooms, bathrooms, address, city, 
            state, country, postal_code, estimated_price, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // Insert 3 properties
    $properties = [
        [
            'Luxury Apartment in Downtown',
            'Modern apartment with great views',
            'apartment',
            120.5,
            2,
            2,
            '123 Main St',
            'New York',
            'NY',
            'USA',
            '10001',
            500000,
            'available'
        ],
        [
            'Beachfront Villa',
            'Beautiful villa with ocean access',
            'villa',
            250.0,
            4,
            3,
            '456 Beach Road',
            'Miami',
            'FL',
            'USA',
            '33139',
            1200000,
            'available'
        ],
        [
            'Cozy Family House',
            'Perfect family home in quiet neighborhood',
            'house',
            180.0,
            3,
            2,
            '789 Oak Street',
            'Los Angeles',
            'CA',
            'USA',
            '90001',
            800000,
            'available'
        ]
    ];

    foreach ($properties as $property) {
        // Insert property
        $propertyStmt->execute([
            1, // owner_id (we'll update this after creating the owner)
            $property[1], // description
            $property[0], // title
            $property[2], // property_type
            $property[3], // size
            $property[4], // bedrooms
            $property[5], // bathrooms
            $property[6], // address
            $property[7], // city
            $property[8], // state
            $property[9], // country
            $property[10], // postal_code
            $property[11], // estimated_price
            $property[12]  // status
        ]);
        $propertyId = $pdo->lastInsertId();

        // Now create owner record
        $stmt->execute([$clientId, $propertyId]);
        $ownerId = $pdo->lastInsertId();

        // Update property with correct owner_id
        $pdo->prepare("UPDATE Property SET owner_id = ? WHERE property_id = ?")
            ->execute([$ownerId, $propertyId]);
    }

    // 4. Make one owner a VIP owner
    $stmt = $pdo->prepare("
        INSERT INTO VIP_Owner (
            owner_id, 
            vip_property_id,
            vip_start_date,
            vip_end_date
        ) VALUES (?, ?, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR))
    ");
    $stmt->execute([1, 1]); // Make first property VIP

    // Commit transaction
    $pdo->commit();

    echo "Test data inserted successfully!";

} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>




document.addEventListener('DOMContentLoaded', function() {
    // 1. Add reference to the filter form
    const filterForm = document.getElementById('filterForm');
    
    // 2. Track active nav state
    let activeType = 'renting';
    const rentNav = document.getElementById('rent-nav');
    const sellNav = document.getElementById('sell-nav');
    
    // 3. Update nav click handlers to track active state
    rentNav.addEventListener('click', () => {
        activeType = 'renting';
        rentNav.classList.add('active');
        sellNav.classList.remove('active');
        fetchProperties(activeType);
    });
    
    sellNav.addEventListener('click', () => {
        activeType = 'selling';
        sellNav.classList.add('active');
        rentNav.classList.remove('active');
        fetchProperties(activeType);
    });

    // 4. Improved fetchProperties function
    function fetchProperties(type, filters = {}) {
        const mainContent = document.querySelector('.main-content');
        mainContent.innerHTML = '<div class="loading-spinner"></div>';
        
        // Build URL with parameters
        const url = new URL('php files/filter.php', window.location.origin);
        url.searchParams.append('type', type);
        
        // Add filters if they exist
        for (const [key, value] of Object.entries(filters)) {
            if (value) {
                url.searchParams.append(key, value);
            }
        }

        fetch(url)
            .then(handleResponse)
            .then(data => {
                if (data.status === 'success') {
                    displayProperties(data.properties, type);
                } else {
                    mainContent.innerHTML = `<div class="empty-state">${data.message || 'No properties found'}</div>`;
                }
            })
            .catch(handleError);
    }

    // 5. New response handler
    function handleResponse(response) {
        if (!response.ok) throw new Error('Network error');
        return response.json();
    }

    // 6. New error handler
    function handleError(error) {
        console.error('Fetch error:', error);
        const mainContent = document.querySelector('.main-content');
        mainContent.innerHTML = `<div class="error">Error loading properties. Please try again.</div>`;
    }

    // 7. Updated filter form submission
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const filters = {
            bedrooms: document.getElementById('bedrooms').value,
            bathrooms: document.getElementById('bathrooms').value,
            min_price: document.getElementById('min_price').value,
            max_price: document.getElementById('max_price').value
        };

        // Remove empty filters
        Object.keys(filters).forEach(key => {
            if (!filters[key]) delete filters[key];
        });

        fetchProperties(activeType, filters);
        document.getElementById('filterModal').style.display = 'none';
    });

    // Initial load
    fetchProperties(activeType);
});
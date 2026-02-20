<?php
require_once 'config.php';

try {
    echo "<h2>Database Check and Seed:</h2>";

    // 1. First check if tables are empty
    $tables = ['Client', 'Owner', 'Property', 'VIP_Owner'];
    $isEmpty = true;

    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<p>$table count: $count</p>";
        if ($count > 0) $isEmpty = false;
    }

    if ($isEmpty) {
        echo "<p>Tables are empty. Adding test data...</p>";

        // Begin transaction
        $pdo->beginTransaction();

        // 2. Insert test client
        $stmt = $pdo->prepare("
            INSERT INTO Client (full_name, email, phone, password) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute(['John Doe', 'john@example.com', '1234567890', password_hash('password123', PASSWORD_DEFAULT)]);
        $clientId = $pdo->lastInsertId();
        echo "<p>Added client with ID: $clientId</p>";

        // 3. Insert test property and owner
        $stmt = $pdo->prepare("
            INSERT INTO Property (
                title, description, property_type, size, bedrooms, 
                bathrooms, address, city, state, country, 
                postal_code, estimated_price, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Add multiple properties
        $properties = [
            [
                'Luxury Beach House', 'Beautiful beachfront property', 'House',
                2500, 4, 3, '123 Beach Rd', 'Miami', 'Florida', 'USA',
                '33139', 750000, 'available'
            ],
            [
                'Modern City Apartment', 'Downtown luxury apartment', 'Apartment',
                1200, 2, 2, '456 City Ave', 'New York', 'New York', 'USA',
                '10001', 500000, 'available'
            ],
            [
                'Mountain Villa', 'Scenic mountain retreat', 'Villa',
                3000, 5, 4, '789 Mountain View', 'Denver', 'Colorado', 'USA',
                '80201', 1200000, 'available'
            ]
        ];

        foreach ($properties as $property) {
            $stmt->execute($property);
            $propertyId = $pdo->lastInsertId();
            
            // Create owner record
            $stmtOwner = $pdo->prepare("
                INSERT INTO Owner (client_id, property_id)
                VALUES (?, ?)
            ");
            $stmtOwner->execute([$clientId, $propertyId]);
            $ownerId = $pdo->lastInsertId();
            
            echo "<p>Added property with ID: $propertyId and owner with ID: $ownerId</p>";

            // Make one property VIP
            if ($property[0] === 'Luxury Beach House') {
                $stmtVIP = $pdo->prepare("
                    INSERT INTO VIP_Owner (
                        owner_id, 
                        vip_property_id,
                        vip_start_date,
                        vip_end_date
                    ) VALUES (?, ?, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR))
                ");
                $stmtVIP->execute([$ownerId, $propertyId]);
                echo "<p>Made property ID: $propertyId VIP</p>";
            }
        }

        // Commit transaction
        $pdo->commit();
        echo "<p style='color: green;'>Successfully added test data!</p>";
    } else {
        echo "<p>Tables already contain data. Showing sample data:</p>";
        
        // Show sample of existing data
        $tables = [
            'Client' => 'SELECT * FROM Client LIMIT 1',
            'Property' => 'SELECT * FROM Property LIMIT 1',
            'Owner' => 'SELECT * FROM Owner LIMIT 1',
            'VIP_Owner' => 'SELECT * FROM VIP_Owner LIMIT 1'
        ];

        foreach ($tables as $name => $query) {
            $result = $pdo->query($query)->fetch(PDO::FETCH_ASSOC);
            echo "<h3>$name Sample:</h3>";
            echo "<pre>" . print_r($result, true) . "</pre>";
        }
    }

} catch(PDOException $e) {
    // If error occurs, rollback the transaction
    if (isset($pdo)) $pdo->rollBack();
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
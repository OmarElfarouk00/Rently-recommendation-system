<?php
session_start();
require_once 'php files/config.php';

// Check if property ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$property_id = $_GET['id'];
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;
$reasons = $_POST['report_reasons'] ?? [];
$otherReason = trim($_POST['other_reason'] ?? '');


$stmt = $pdo->prepare("SELECT image_path, image_order FROM Property_Images WHERE property_id = ? ORDER BY image_order ASC");
$stmt->execute([$property_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

//     // Check if property exists and is available for rent
//     $stmt = $pdo->prepare("SELECT * FROM Property WHERE id_property = ? AND ownerNeeds = 'renting'");
//     $stmt->execute([$property_id]);
//     $property = $stmt->fetch();

//     if (!$property) {
//         echo json_encode(['success' => false, 'message' => 'Property not found or not available for rent']);
//         exit();
//     }

// Fetch property details
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            c.full_name AS owner_name,
            c.email AS owner_email,
            c.phone AS owner_phone,
            CASE 
                WHEN pv.id_propertyOwner_VIP IS NOT NULL THEN 1
                ELSE 0
            END AS is_vip
        FROM Property p
        JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
        JOIN Client c ON po.id_propertyOwner = c.id_client
        LEFT JOIN propertyOwner_VIP pv ON po.id_propertyOwner = pv.id_propertyOwner
        WHERE p.id_property = ?
    ");
    $stmt->execute([$property_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        header('Location: index.php');
        exit();
    }

    if ($property['propertyType'] == 'apartment') {
        $stmt = $pdo->prepare("
    SELECT * FROM apartment
    WHERE id_property = ?
    ");
        $stmt->execute([$property_id]);
        $apartment = $stmt->fetch(PDO::FETCH_ASSOC);
    } else
        if ($property['propertyType'] == 'house') {
            $stmt = $pdo->prepare("
    SELECT * FROM house
    WHERE id_property = ?
    ");
            $stmt->execute([$property_id]);
            $house = $stmt->fetch(PDO::FETCH_ASSOC);
        } else
            if ($property['propertyType'] == 'villa') {
                $stmt = $pdo->prepare("
    SELECT * FROM villa
    WHERE id_property = ?
    ");
                $stmt->execute([$property_id]);
                $villa = $stmt->fetch(PDO::FETCH_ASSOC);
            } else
                if ($property['propertyType'] == 'room') {
                    $stmt = $pdo->prepare("
    SELECT * FROM room
    WHERE id_property = ?
    ");
                    $stmt->execute([$property_id]);
                    $room = $stmt->fetch(PDO::FETCH_ASSOC);
                }

    // if ($property) {
    //     // Fetch property images
    //     $stmtImages = $pdo->prepare(query: "SELECT image_path, image_order FROM Property_Images WHERE property_id = ? ORDER BY image_order ASC");
    //     $stmtImages->execute([$property_id]);
    //     $property['images'] = $stmtImages->fetchAll(PDO::FETCH_ASSOC);

    // } else {
    //     echo json_encode([
    //         'status' => 'error',
    //         'message' => 'Property not found.'
    //     ]);
    // }

    // Check if user has favorited this property
    $isFavorite = false;
    if ($isLoggedIn) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM favorits 
            WHERE id_client = ? AND id_property = ?
        ");
        $stmt->execute([$userId, $property_id]);
        $isFavorite = $stmt->fetchColumn() > 0;
    }

    // Get similar properties
    $stmt = $pdo->prepare("
        SELECT * FROM Property 
        WHERE city = ? AND id_property != ? 
        AND ownerNeeds = ? 
        LIMIT 3
    ");
    $stmt->execute([$property['city'], $property_id, $property['ownerNeeds']]);
    $similarProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if (isset($_POST['report_comment'])) {
    $submit = $_POST['submit'];

    if ($submit == 'comment') {
        $comment = trim($_POST['comment']);

        if (!empty($comment)) {
            $stmt = $pdo->prepare("INSERT INTO Comments (id_property, comment, id_client,created_at) VALUES (?, ?,?, NOW())");
            $stmt->execute([$property_id, $comment, $userId]);
        }
        $successMessage = urlencode('Your comment has been sent successfully!');
        header("Location: property.php?id=$property_id&comment_success=$successMessage");
        exit();

    } else {
        $finalReasons = array_map('trim', $reasons);

        if (in_array('Other', $finalReasons) && $otherReason) {
            $finalReasons[array_search('Other', $finalReasons)] = "Other: " . $otherReason;
        }

        $reportText = implode(', ', $finalReasons);

        // Insert into DB
        $stmt = $pdo->prepare("INSERT INTO PropertyReports (id_property, id_client, report_type, report_date) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$property_id, $userId, $reportText]);

        $successMessage = urlencode('Your report has been sent successfully!');
        header("Location: property.php?id=$property_id&report_success=$successMessage");
        exit();
    }
}
$propertyPhotos = [];
$stmt = $pdo->prepare("SELECT image_path FROM Property_images WHERE property_id = ?");
$stmt->execute([$property['id_property']]);
while ($row = $stmt->fetch()) {
    $propertyPhotos[] = $row['image_path']; // adjust as needed
}



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['title']); ?> | RentEstate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        .checkbox-group {
            display: grid;
            gap: 8px;
            margin-top: 8px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }

        #co,
        #rep {
            background-color: #d65b1e;
            /* Green */
            color: white;
            border: none;
            padding: 10px 20px;
            margin-top: 15px;
            font-size: 15px;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #co,
        #rep:hover {
            background-color: #c34b0f;
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.2);
        }


        .popup-body textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            resize: vertical;
            font-size: 14px;
            font-family: inherit;
        }

        .comments-section {
            margin-top: 40px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }

        .comments-section h4 {
            margin-bottom: 15px;
            font-size: 18px;
            color: #333;
        }

        .comment-box {
            background-color: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .comment-box p {
            margin: 0;
            font-size: 15px;
            color: #444;
            line-height: 1.5;
        }

        .comment-box small {
            display: block;
            margin-top: 8px;
            color: #888;
            font-size: 12px;
        }



        /* Property Detail Page Styles */
        .property-container {
            margin-top: 80px;
            padding: 2rem 5%;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* User Profile with Active Status */
        .user-profile {
            display: flex;
            align-items: center;
            margin-left: 15px;
            /* Changed from margin-right */
            margin-left: auto;
            /* Pushes it to the left if in a flex container */
            padding: 5px 12px;
            border-radius: 20px;
            background-color: #f5f5f5;
            transition: all 0.3s ease;
            max-width: 100px;
        }

        .user-profile:hover {
            background-color: #e9e9e9;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .user-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            background-color: #2ecc71;
            /* Green color for active status */
            border-radius: 50%;
            display: inline-block;
            position: relative;
        }


        /* Property Gallery */
        .property-gallery {
            display: grid;
            grid-template-columns: 5fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
            height: 500px;
            border-radius: 15px;
            overflow: hidden;
        }

        .main-image {
            position: relative;
            height: 100%;
            border-radius: 15px;
            overflow: hidden;
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-grid {
            display: grid;
            grid-template-rows: 1fr 1fr;
            gap: 1rem;
        }

        .gallery-item {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            cursor: pointer;
        }

        .gallery-item img {
            width: 99%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .gallery-item:hover img {
            transform: scale(1.05);
        }

        .view-all-photos {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.9);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .view-all-photos:hover {
            background: white;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            overflow: hidden;
        }

        .modal-content.wide-gallery {
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            height: 80%;
            margin: 5% auto;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin-top: 8%;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
        }

        .horizontal-scroll-container {
            display: flex;
            flex-direction: row;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            gap: 1rem;
            padding: 1rem;
            height: 100%;
        }

        .horizontal-scroll-container img {
            flex: 0 0 100%;
            max-width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
            scroll-snap-align: start;
        }


        .vip-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: #ffd700;
            color: #000;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
            z-index: 5;
        }

        /* Property Content */
        .property-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .property-info-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .property-title {
            font-size: 2rem;
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .property-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
            color: #666;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .property-features {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--light-gray);
            border-radius: 10px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color);
        }

        .property-description {
            margin-bottom: 2rem;
        }

        .property-description h2 {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .property-description p {
            line-height: 1.6;
            color: #666;
        }

        .property-amenities {
            margin-bottom: 2rem;
        }

        .property-amenities h2 {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .amenity {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem;
            background: var(--light-gray);
            border-radius: 8px;
            color: var(--text-color);
        }

        .property-location {
            margin-bottom: 2rem;
        }

        .property-location h2 {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .map-container {
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
        }

        /* Similar Properties */
        .similar-properties {
            margin-top: 2rem;
        }

        .similar-properties h2 {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .similar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }

        .similar-card {
            background: var(--light-gray);
            border-radius: 10px;
            overflow: hidden;
            text-decoration: none;
            color: var(--text-color);
            transition: transform 0.3s;
        }

        .similar-card:hover {
            transform: translateY(-5px);
        }

        .similar-image {
            height: 150px;
        }

        .similar-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .similar-info {
            padding: 1rem;
        }

        .similar-info h3 {
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .similar-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .similar-price {
            font-weight: bold;
            color: var(--primary-color) !important;
        }

        /* Booking Section */
        .booking-section {
            position: relative;
        }

        .booking-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            position: sticky;
            top: 100px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .booking-price {
            font-size: 2rem;
            color: var(--text-color);
            margin-bottom: 1.5rem;
            font-weight: bold;
        }

        .price-period {
            font-size: 1rem;
            color: #666;
            font-weight: normal;
        }

        .booking-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .date-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            color: var(--text-color);
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .book-button {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 1rem;
        }

        .book-button:hover {
            background-color: #d65b1e;
        }

        .negotiate-button {
            width: 100%;
            padding: 1rem;
            background: white;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .negotiate-button:hover {
            background: var(--primary-color);
            color: white;
        }

        .host-info {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color, #e0e0e0);
            background-color: #fafafa;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }

        .host-details h3 {
            color: #222;
            font-size: 1.2rem;
            margin: 0 0 0.5rem;
        }

        .host-details p {
            color: #555;
            font-size: 0.95rem;
            margin: 0.3rem 0;
        }

        .host-details .vip {
            color: #d4af37;
            font-weight: bold;
            font-size: 1rem;
        }


        .login-prompt {
            text-align: center;
            padding: 1.5rem;
            background: var(--light-gray);
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .login-prompt a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .owner-notice {
            text-align: center;
            padding: 1.5rem;
            background: var(--light-gray);
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .edit-listing-btn {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.8rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .edit-listing-btn:hover {
            background-color: #d65b1e;
        }

        /* Popup Styles */
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
        }

        .popup-content {
            background: white;
            width: 90%;
            max-width: 500px;
            border-radius: 15px;
            overflow: hidden;
            animation: popupSlideIn 0.3s ease;
            margin: 2rem auto;
            margin-top: 10rem;
        }

        @keyframes popupSlideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .popup-header {
            padding: 1.5rem;
            background: var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .popup-header h3 {
            margin: 0;
            color: var(--text-color);
            font-size: 1.25rem;
        }

        .close-popup {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s;
        }

        .close-popup:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        .popup-body {
            padding: 1.5rem;
        }

        @media (max-width: 600px) {
            .popup-content {
                padding: 20px;
                width: 95%;
                overflow: auto;
                margin: 20px;
            }

            .submit-offer {
                width: 100%;
            }
        }

        .property-summary {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .property-thumbnail {
            width: 150px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        .property-info {
            flex: 1;
        }

        .property-info h4 {
            margin: 0 0 0.5rem 0;
            color: var(--text-color);
        }

        .property-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .listed-price {
            font-weight: 500;
            color: var(--text-color);
        }

        .negotiation-form,
        .contact-form {
            display: grid;
            gap: 1.5rem;
        }

        .price-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .currency-symbol {
            position: absolute;
            left: 1rem;
            color: #666;
        }

        #offerPrice,
        #subject {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
        }

        #message,
        #contactMessage {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            resize: vertical;
            font-family: inherit;
            font-size: 0.9rem;
            min-height: 100px;
        }

        .submit-offer {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }

        .submit-offer:hover {
            background: #d65b1e;
        }

        /* Success Message */
        .success-message {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            animation: slideIn 0.3s ease;
            z-index: 1001;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .error-message {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            animation: slideIn 0.3s ease;
            z-index: 1001;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .property-content {
                grid-template-columns: 1fr;
            }

            .booking-card {
                position: static;
                margin-bottom: 2rem;
            }
        }

        @media (max-width: 768px) {
            .property-gallery {
                grid-template-columns: 1fr;
                height: auto;
            }

            .main-image {
                height: 300px;
            }

            .gallery-grid {
                grid-template-columns: 1fr 1fr;
                grid-template-rows: auto;
            }

            .gallery-item {
                height: 150px;
            }

            .property-features {
                grid-template-columns: repeat(2, 1fr);
            }



            .property-summary {
                flex-direction: column;
            }

            .property-thumbnail {
                width: 100%;
                height: 150px;
            }
        }

        @media (max-width: 480px) {
            .property-container {
                padding: 1rem;
            }

            .property-meta {
                flex-direction: column;
                gap: 0.8rem;
            }

            .date-inputs {
                grid-template-columns: 1fr;
            }

            .amenities-grid {
                grid-template-columns: 1fr;
            }
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <!-- Header (Same as index.php) -->
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
                        <a href="index.php" class="menu-item" id="dashboard-toggle">
                            <i class="fas fa-home"></i>
                            dashboard
                        </a>
                        <a href="menu pages/messages.php" class="menu-item">
                            <i class="fas fa-envelope"></i>
                            Messages
                        </a>
                        <a href="my-properties.php" class="menu-item">
                            <i class="fas fa-building"></i>
                            My Properties
                        </a>



                        <?php if (!$isLoggedIn): ?>
                            <a href="../login-signup/index.php" class="menu-item">
                                <i class="fas fa-arrow-right"></i>
                                Add New Property
                            </a>
                        <?php else: ?>
                            <a href="become-host.php" class="menu-item">
                                <i class="fas fa-arrow-right"></i>
                                Add New Property
                            </a>
                        <?php endif; ?>
                        <a href="menu pages/bookings.php" class="menu-item">
                            <i class="fas fa-calendar-check"></i>
                            My Bookings
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

    <!-- Property Detail Content -->
    <div class="property-container">
        <!-- Property Gallery -->
        <div class="property-gallery">
            <div class="main-image">
                <?php if (!empty($images)): ?>
                    <img src="php files/<?php echo htmlspecialchars($images[0]['image_path']); ?>"
                        alt="<?php echo htmlspecialchars($property['title']); ?>">
                <?php else: ?>
                    <img src="/placeholder.svg?height=500&width=800"
                        alt="<?php echo htmlspecialchars($property['title']); ?>">
                <?php endif; ?>

                <button class="favorite-btn <?php echo $isFavorite ? 'active' : ''; ?>"
                    data-property-id="<?php echo $property['id_property']; ?>">
                    <i class="<?php echo $isFavorite ? 'fas' : 'far'; ?> fa-heart"></i>
                </button>

                <?php if ($property['is_vip']): ?>
                    <div class="vip-badge">VIP Property</div>
                <?php endif; ?>
            </div>

            <div class="gallery-grid">
                <?php foreach (array_slice($images, 1, 2) as $index => $image): ?>
                    <div class="gallery-item">
                        <img src="php files/<?php echo htmlspecialchars($image['image_path']); ?>" alt="Property Image"
                            width="100" height="100">

                        <!-- Trigger Button -->
                        <div class="view-all-photos" onclick="openPhotoGallery()">
                            <i class="fas fa-images"></i> View All Photos
                        </div>

                        <!-- Modal for Horizontal Scroll Gallery -->
                        <div id="photoGalleryModal" class="modal">
                            <div class="modal-content wide-gallery">
                                <span class="close-btn" onclick="closePhotoGallery()">&times;</span>
                                <div class="horizontal-scroll-container" id="photoGalleryContainer">
                                    <!-- Images will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Property Content -->
        <div class="property-content">
            <!-- Property Info -->
            <div class="property-info-section">
                <h1 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h1>

                <div class="property-meta">
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($property['address'] . ', ' . $property['city'] . ', ' . $property['country']); ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-home"></i>
                        <?php echo htmlspecialchars($property['propertyType']); ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-ruler-combined"></i>
                        <?php echo htmlspecialchars($property['size']); ?> m²
                    </div>
                </div>

                <div class="property-features">
                    <div class="feature">
                        <i class="fas fa-bed"></i>
                        <span><?php echo htmlspecialchars($property['bedrooms']); ?> Bedrooms</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-bath"></i>
                        <span><?php echo htmlspecialchars($property['bathrooms']); ?> Bathrooms</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-tag"></i>
                        <span><?php echo htmlspecialchars($property['ownerNeeds']); ?></span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($property['status']); ?></span>
                    </div>
                </div>
                <?php if ($property['propertyType'] == 'apartment'): ?>
                    <div class="property-features">
                        <div class="feature">
                            <i class="fas fa-layer-group"></i>
                            <span>Floor Number<strong>:
                                </strong><?php echo htmlspecialchars($apartment['floor_number']); ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-building"></i>
                            <span>Building Name<strong>: </strong>
                                <?php echo htmlspecialchars($apartment['building_name']); ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-elevator"></i>
                            <?php if ($apartment['has_elevator'] == 1): ?>
                                <span> Has Elevator</span>
                            <?php else: ?>
                                <span> No Elevator</span>
                            <?php endif; ?>
                        </div>
                        <div class="feature">
                            <i class="fas fa-parking"></i>
                            <?php if ($apartment['has_parking'] == 1): ?>
                                <span> Has Parking</span>
                            <?php else: ?>
                                <span> No Parking</span>
                            <?php endif; ?>
                        </div>
                        <div class="feature">
                            <i class="fas fa-tag"></i>
                            <?php if ($apartment['monthly_maintenance_fee'] == 1): ?>
                                <span> Has monthly maintenance fee</span>
                            <?php else: ?>
                                <span> No monthly maintenance fee</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($property['propertyType'] == 'villa'): ?>
                    <div class="property-features">
                        <div class="feature">
                            <i class="fas fa-layer-group"></i>
                            <span>Floors<strong>: </strong><?php echo htmlspecialchars($villa['floors']); ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-tree"></i>
                            <span>Garden Size<strong>: </strong>
                                <?php echo htmlspecialchars($villa['garden_size']); ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-swimming-pool"></i>
                            <?php if ($villa['has_pool'] == 1): ?>
                                <span> Has Pool</span>
                            <?php else: ?>
                                <span> No Pool</span>
                            <?php endif; ?>
                        </div>
                        <div class="feature">
                            <i class="fas fa-parking"></i>
                            <?php if ($villa['has_garage'] == 1): ?>
                                <span> Has Garage</span>
                            <?php else: ?>
                                <span> No Garage</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($villa['has_garage'] == 1): ?>
                            <div class="feature">
                                <i class="fas fa-car"></i>
                                <span>Garage Capacity<strong>: </strong>
                                    <?php echo htmlspecialchars($villa['garage_capacity']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="feature">
                            <i class="fas fa-key"></i>
                            <?php if ($villa['has_security_system'] == 1): ?>
                                <span> Has Security System</span>
                            <?php else: ?>
                                <span> No Security System</span>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($property['propertyType'] == 'room'): ?>
                    <div class="property-features">
                        <div class="feature">
                            <i class="fas fa-layer-group"></i>
                            <span>Floor Number<strong>:
                                </strong><?php echo htmlspecialchars($room['floor_number']); ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-bed"></i>
                            <span>Room Type<strong>: </strong> <?php echo htmlspecialchars($room['room_type']); ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-bath"></i>
                            <?php if ($room['has_private_bathroom'] == 1): ?>
                                <span> Has Private Bathroom</span>
                            <?php else: ?>
                                <span> No Private Bathroom</span>
                            <?php endif; ?>
                        </div>
                        <div class="feature">
                            <i class="fas fa-utensils"></i>
                            <?php if ($room['has_private_kitchen'] == 1): ?>
                                <span> Has Private Kitchen</span>
                            <?php else: ?>
                                <span> No Private Kitchen</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($room['is_furnished'] == 1): ?>
                            <div class="feature">
                                <i class="fas fa-couch"></i>
                                <span> Furnished</span>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($property['propertyType'] == 'house'): ?>
                    <div class="property-features">
                        <div class="feature">
                            <i class="fas fa-layer-group"></i>
                            <span>Floors<strong>: </strong><?php echo htmlspecialchars($house['floors']); ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-tree"></i>
                            <span>Garden Size<strong>: </strong>
                                <?php echo htmlspecialchars($house['garden_size']); ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-warehouse"></i>
                            <?php if ($house['has_basement'] == 1): ?>
                                <span> Has Basement</span>
                            <?php else: ?>
                                <span> No Basement</span>
                            <?php endif; ?>
                        </div>
                        <div class="feature">
                            <i class="fas fa-parking"></i>
                            <?php if ($house['has_garage'] == 1): ?>
                                <span> Has Garage</span>
                            <?php else: ?>
                                <span> No Garage</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($house['has_garage'] == 1): ?>
                            <div class="feature">
                                <i class="fas fa-car"></i>
                                <span>Garage Capacity<strong>: </strong>
                                    <?php echo htmlspecialchars($house['garage_capacity']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

                <div class="property-description">
                    <h2>Description</h2>
                    <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                </div>


                <div class="property-location">
                    <h2>Location</h2>
                    <div class="map-container">
                        <iframe width="100%" height="300" frameborder="0" scrolling="no" marginheight="0"
                            marginwidth="0"
                            src="https://maps.google.com/maps?q=<?php echo urlencode($property['address'] . ', ' . $property['city'] . ', ' . $property['country']); ?>&output=embed">
                        </iframe>
                    </div>
                </div>

                <!-- Similar Properties -->
                <!-- <?php if (!empty($similarProperties)): ?>
                <div class="similar-properties">
                    <h2>Similar Properties</h2>
                    <div class="similar-grid">
                        <?php foreach ($similarProperties as $similar): ?>
                        <a href="property.php?id=<?php echo $similar['id_property']; ?>" class="similar-card">
                            <div class="similar-image">
                                <img src="/placeholder.svg?height=150&width=200" alt="<?php echo htmlspecialchars($similar['title']); ?>">
                            </div>
                            <div class="similar-info">
                                <h3><?php echo htmlspecialchars($similar['title']); ?></h3>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($similar['city']); ?></p>
                                <p class="similar-price">
                                    <?php echo number_format($similar['estimatePrice'], 0, '.', ','); ?> DZD
                                </p>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?> -->

                <?php $stmt = $pdo->prepare("SELECT comment,id_client, created_at FROM Comments WHERE id_property = ? ORDER BY created_at DESC");
                $stmt->execute([$property['id_property']]);
                $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <div class="comments-section">
                    <h4>Comments</h4>
                    <?php foreach ($comments as $c): ?>
                        <div class="comment-box">
                            <p><?= htmlspecialchars($c['comment']) ?></p>
                            <!-- retrieve the name of the user based on his id -->
                            <?php $stmt = $pdo->prepare("SELECT full_name FROM client WHERE id_client = ?");
                            $stmt->execute([$c['id_client']]);
                            $username = $stmt->fetch(PDO::FETCH_ASSOC); ?>
                            <small><?= $username['full_name'] ?></small>
                            <small><?= $c['created_at'] ?></small>
                            <!-- if its the owners property it will have delete button -->
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($isLoggedIn): ?>
                    <!-- try to verify if the user id in the propertyreports table -->
                    <?php $stmt = $pdo->prepare("SELECT id_client FROM propertyReports WHERE id_property = ? AND id_client = ?");
                    $stmt->execute([$property['id_property'], $_SESSION['user_id']]);
                    $report = $stmt->fetch(PDO::FETCH_ASSOC); ?>
                    <!-- if he reported he can't do it again -->
                    <?php if (!$report): ?>
                    <button class="report-btn" id="rep"
                        onclick="document.getElementById('reportPopup').style.display='block'">
                        <i class="fas fa-flag"></i> Report Property
                    </button>
                    <?php endif; ?>
                    <button class="open-comment-popup" id="co">Add Comment</button>
                <?php endif; ?>
                <div>
                    <br>
                    <?php if (isset($_GET['report_success'])): ?>
                        <div class="alert-success" id="success_message">
                            <?= htmlspecialchars($_GET['report_success']) ?>
                        </div>
                    <?php elseif (isset($_GET['comment_success'])): ?>
                        <div class="alert-success">
                            <?= htmlspecialchars($_GET['comment_success']) ?>
                        </div>
                        <script>
                            setTimeout(() => {
                                const successMsg = document.getElementById('success_message');
                                if (successMsg) {
                                    successMsg.style.display = 'none';
                                }
                            }, 5000);
                        </script>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Booking Card -->
            <div class="booking-section">
                <div class="booking-card">
                    <div class="booking-price">
                        <?php echo number_format($property['estimatePrice'], 0, '.', ','); ?> DZD
                        <span class="price-period">
                            <?php echo $property['ownerNeeds'] == 'renting' ? '/month' : ''; ?>
                        </span>
                    </div>

                    <?php if ($isLoggedIn && $userId != $property['id_propertyOwner']): ?>
                        <!-- Negotiate Button -->
                        <?php if ($property['ownerNeeds'] == 'renting'): ?>
                            <button class="negotiate-button" id="negotiateBtn">
                                <i class="fas fa-comments-dollar"></i> Negotiate Price
                            </button>
                        <?php endif; ?>

                        <!-- Booking Form -->
                        <?php if ($property['ownerNeeds'] == 'renting'): ?>
                            <form class="booking-form" id="bookingForm" action="php files/book_property.php" method="POST">
                                <input type="hidden" name="property_id" value="<?php echo $property['id_property']; ?>">

                                <div class="date-inputs">
                                    <!-- Move-in Date -->
                                    <div class="form-group" style="position: relative;">
                                        <label for="check-in">Move-in Date*</label>
                                        <input type="text" id="check-in" name="start_date" class="form-input" required
                                            style="padding-right: 30px;">
                                        <i class="fa fa-calendar" style="
                                            position: absolute;
                                            right: 10px;
                                            top: 47px;
                                            pointer-events: none;
                                            color: #888;
                                        "></i>
                                    </div>

                                    <div class="form-group" style="position: relative;">
                                        <label for="check-out">Move-out Date*</label>
                                        <input type="text" id="check-out" name="end_date" class="form-input" required
                                            style="padding-right: 30px;">
                                        <i class="fa fa-calendar" style="
                                            position: absolute;
                                            right: 10px;
                                            top: 47px;
                                            pointer-events: none;
                                            color: #888;
                                        "></i>
                                    </div>
                                </div>

                                <button type="submit" class="book-button" id="bookBtn">Book Now </button>
                            </form>
                        <?php else: ?>
                            <div class="contact-owner">
                                <button class="book-button" id="contactOwnerBtn">Contact Owner</button>
                            </div>
                        <?php endif; ?>
                    <?php elseif (!$isLoggedIn): ?>
                        <div class="login-prompt">
                            <p>Please <a href="../login-signup/index.php">login</a> to book or negotiate for this property.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="owner-notice">
                            <p>This is your property listing.</p>
                            <a href="php files/edit-property.php?id=<?php echo $property['id_property'] ?>"
                                class="edit-listing-btn">Edit Listing</a>
                        </div>
                    <?php endif; ?>

                    <!-- Host Info -->
                    <div class="host-info">
                        <div class="host-details">
                            <h3> <i class="fas fa-user-circle" style="font-size: 1.5rem; color:rgb(118, 170, 253);"></i>
                                Hosted by <?php echo htmlspecialchars($property['owner_name']); ?></h3>
                            <p><strong>Contact Number:</strong>
                                <?php echo htmlspecialchars($property['owner_phone']); ?></p>
                            <?php if ($property['is_vip']): ?>
                                <p class="vip"><i class="fas fa-crown"></i> VIP Host</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Negotiation Popup -->
    <?php if ($property['ownerNeeds'] == 'renting'): ?>
        <div class="popup-overlay" id="negotiationPopup">
            <div class="popup-content">
                <div class="popup-header">
                    <h3>Make an Offer</h3>
                    <button class="close-popup" id="closeNegotiationPopup">&times;</button>
                </div>
                <div class="popup-body">
                    <div class="property-summary">
                        <div class="property-info">
                            <h4><?php echo htmlspecialchars($property['title']); ?></h4>
                            <p><?php echo htmlspecialchars($property['address'] . ', ' . $property['city']); ?></p>
                            <p>Listed price: <span
                                    class="listed-price"><?php echo number_format($property['estimatePrice'], 0, '.', ','); ?>
                                    DZD</span></p>
                        </div>
                    </div>

                    <form class="negotiation-form" id="negotiationForm" action="php files/submit_negotiation.php"
                        method="POST">
                        <input type="hidden" name="property_id" value="<?php echo $property['id_property']; ?>">
                        <input type="hidden" name="ownerNeeds" value=<?php echo $property['ownerNeeds']; ?>>

                        <div class="form-group">
                            <label for="offerPrice">Your Offer (DZD)*</label>
                            <div class="price-input-wrapper">
                                <input type="number" id="offerPrice" name="proposed_price" required min="1"
                                    max="<?php echo $property['estimatePrice']; ?>"
                                    value="<?php echo round($property['estimatePrice'] * 0.98); ?>">
                            </div>
                        </div>

                        <div class="date-inputs">
                            <div class="form-group" style="position: relative;">
                                <label for="check-in">Move-in Date*</label>
                                <input type="text" id="check-in" name="start_date" class="form-input" required
                                    style="padding-right: 30px;">
                                <i class="fa fa-calendar" style="
                                            position: absolute;
                                            right: 10px;
                                            top: 47px;
                                            pointer-events: none;
                                            color: #888;
                                        "></i>
                            </div>

                            <div class="form-group" style="position: relative;">
                                <label for="check-out">Move-out Date*</label>
                                <input type="text" id="check-out" name="end_date" class="form-input" required
                                    style="padding-right: 30px;">
                                <i class="fa fa-calendar" style="
                                            position: absolute;
                                            right: 10px;
                                            top: 47px;
                                            pointer-events: none;
                                            color: #888;
                                        "></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message">Message to Owner (Optional)</label>
                            <textarea id="message" name="comments" rows="4"
                                placeholder="Explain why you're making this offer..."></textarea>
                        </div>

                        <button type="submit" class="submit-offer">Submit Offer</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contact Owner Popup -->
    <div class="popup-overlay" id="contactPopup">
        <div class="popup-content">
            <div class="popup-header">
                <h3>Contact Owner</h3>
                <button class="close-popup" id="closeContactPopup">&times;</button>
            </div>
            <div class="popup-body">
                <div class="property-summary">
                    <div class="property-info">
                        <h4><?php echo htmlspecialchars($property['title']); ?></h4>
                        <p><?php echo htmlspecialchars($property['address'] . ', ' . $property['city']); ?></p>
                    </div>
                </div>

                <form class="contact-form" id="contactForm" action="php files/submit_negotiation.php" method="POST">
                    <input type="hidden" name="property_id" value="<?php echo $property['id_property']; ?>">
                    <input type="hidden" name="ownerNeeds" value=<?php echo $property['ownerNeeds']; ?>>
                    <input type="hidden" name="start_date" value="0000-00-00">
                    <input type="hidden" name="end_date" value="0000-00-00">

                    <div class="form-group">
                        <label for="contactMessage">Message</label>
                        <textarea id="contactMessage" name="comments" rows="4" required
                            placeholder="Hello, I would like to inquire about this property..."></textarea>
                    </div>

                    <button type="submit" class="submit-offer">Send Message</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <div class="success-message" id="successMessage" style="display: none;">
        Your request has been sent successfully!
    </div>
    <div class="error-message" id="errorMessage" style="display: none;"></div>
    <!-- submit a comment -->
    <div class="popup-overlay" id="commentPopup">
        <div class="popup-content">
            <div class="popup-header">
                <h3>Submit a Comment</h3>
                <button class="close-popup" id="closeCommentPopup">&times;</button>
            </div>
            <div class="popup-body">
                <form id="commentForm" action="property.php?id=<?php echo $property['id_property']; ?>" method="POST">
                    <input type="hidden" name="property_id" value="<?php echo $property['id_property']; ?>">
                    <input type="hidden" name="submit" value="comment">

                    <div class="form-group">
                        <label for="comment">Comment</label>
                        <textarea id="comment" name="comment" rows="4" required
                            placeholder="Write your comment here..."></textarea>
                    </div>

                    <button type="submit" class="submit-offer" name="report_comment">Submit Comment</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Report Popup -->
    <div class="popup-overlay" id="reportPopup" style="display:none;">
        <div class="popup-content">
            <div class="popup-header">
                <h3>Report This Property</h3>
                <button class="close-popup"
                    onclick="document.getElementById('reportPopup').style.display='none'">&times;</button>
            </div>
            <div class="popup-body">
                <form id="reportForm" action="property.php?id=<?php echo $property['id_property']; ?>" method="POST">
                    <input type="hidden" name="property_id" value="<?= htmlspecialchars($property['id_property']) ?>">
                    <input type="hidden" name="submit" value="report">


                    <div class="form-group">
                        <label><strong>Select reason(s):</strong></label>
                        <div class="checkbox-group">
                            <?php
                            $reasons = [
                                "Incorrect Information",
                                "Inappropriate Content",
                                "Suspicious Owner Behavior",
                                "Safety Concerns -Thief/Robbery",
                            ];
                            foreach ($reasons as $reason): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="report_reasons[]" value="<?= $reason ?>"
                                        onclick="toggleOtherReason(this)">
                                    <?= $reason ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group" id="other_reason_container" style="display: none;">
                        <label for="other_reason">Please explain</label>
                        <textarea name="other_reason" id="other_reason" rows="4"
                            placeholder="Describe the issue..."></textarea>
                    </div>

                    <button type="submit" class="submit-offer" name="report_comment">Submit Report</button>
                </form>
                <div id="reportSuccessMsg" class="alert-success" style="display:none;"></div>
            </div>
        </div>
    </div>

    <script>
        // Format price function
        function formatPrice(price) {
            return new Intl.NumberFormat('fr-DZ', {
                style: 'currency',
                currency: 'DZD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(price);
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Menu toggle
            const menuToggle = document.getElementById('menuToggle');
            const menuContent = document.getElementById('menuContent');

            menuToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                menuContent.classList.toggle('active');
            });

            document.addEventListener('click', function (e) {
                if (!menuContent.contains(e.target) && !menuToggle.contains(e.target)) {
                    menuContent.classList.remove('active');
                }
            });


            // Favorite button
            const favoriteBtn = document.querySelector('.favorite-btn');
            if (favoriteBtn) {
                favoriteBtn.addEventListener('click', function () {
                    const propertyId = this.dataset.propertyId;
                    const icon = this.querySelector('i');

                    if (icon.classList.contains('far')) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        this.classList.add('active');
                        addToFavorites(propertyId);
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        this.classList.remove('active');
                        removeFromFavorites(propertyId);
                    }
                });
            }

            document.querySelector('.open-comment-popup').addEventListener('click', function () {
                document.getElementById('commentPopup').style.display = 'block';
            });

            document.getElementById('closeCommentPopup').addEventListener('click', function () {
                document.getElementById('commentPopup').style.display = 'none';
            });

            // Optional: Close when clicking outside popup
            window.addEventListener('click', function (e) {
                const popup = document.getElementById('commentPopup');
                if (e.target === popup) {
                    popup.style.display = 'none';
                }
            });

            function toggleOtherReason(checkbox) {
                const container = document.getElementById('other_reason_container');
                if (checkbox.value === 'Other') {
                    container.style.display = checkbox.checked ? 'block' : 'none';
                }
            }

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
                            alert("You must be logged in to add this property to your favorites.");
                            // window.location.href = '../login-signup/index.php';
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
                    .catch(error => console.error('Error:', error));
            }

            // Negotiation popup
            const negotiateBtn = document.getElementById('negotiateBtn');
            const negotiationPopup = document.getElementById('negotiationPopup');
            const closeNegotiationPopup = document.getElementById('closeNegotiationPopup');

            if (negotiateBtn) {
                negotiateBtn.addEventListener('click', function () {
                    negotiationPopup.style.display = 'flex';
                });
            }

            if (closeNegotiationPopup) {
                closeNegotiationPopup.addEventListener('click', function () {
                    negotiationPopup.style.display = 'none';
                });
            }

            // Contact owner popup
            const contactOwnerBtn = document.getElementById('contactOwnerBtn');
            const contactPopup = document.getElementById('contactPopup');
            const closeContactPopup = document.getElementById('closeContactPopup');

            if (contactOwnerBtn) {
                contactOwnerBtn.addEventListener('click', function () {
                    contactPopup.style.display = 'flex';
                });
            }

            if (closeContactPopup) {
                closeContactPopup.addEventListener('click', function () {
                    contactPopup.style.display = 'none';
                });
            }

            // Close popups when clicking outside
            window.addEventListener('click', function (e) {
                if (e.target === negotiationPopup) {
                    negotiationPopup.style.display = 'none';
                }
                if (e.target === contactPopup) {
                    contactPopup.style.display = 'none';
                }
            });

            // Handle negotiation form submission
            const negotiationForm = document.getElementById('negotiationForm');
            if (negotiationForm) {
                negotiationForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const formData = new FormData(this);

                    fetch(this.action, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                negotiationPopup.style.display = 'none';
                                showSuccessMessage('Your offer has been sent to the owner!');
                                const btn = document.getElementById('negotiateBtn');
                                btn.disabled = true;
                                setTimeout(() => {
                                    btn.disabled = false;
                                }, 60000); // 10 seconds
                            } else {
                                showErrorMessage(data.message || 'An error occurred. Please try again.');
                                // alert(data.message || 'An error occurred. Please try again.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showErrorMessage('An error occurred. Please try again.');
                            // alert('An error occurred. Please try again.');
                        });
                });
            }

            // $.ajax({
            //     url: 'property.php',
            //     method: 'POST',
            //     data: { id: propertyId },
            //     dataType: 'json',
            //     success: function (response) {
            //         if (!response.success) {
            //             toastr.error(response.message); // Professional toast message
            //         } else {
            //             toastr.success("Property is available!");
            //         }
            //     }
            // });

            // Handle contact form submission
            const contactForm = document.getElementById('contactForm');
            if (contactForm) {
                contactForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const formData = new FormData(this);

                    fetch(this.action, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                contactPopup.style.display = 'none';
                                showSuccessMessage('Your message has been sent to the owner!');
                            } else {
                                // alert(data.message || 'An error occurred. Please try again.');
                                showErrorMessage(data.message || 'An error occurred. Please try again.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // alert('An error occurred. Please try again.');
                            showErrorMessage('An error occurred. Please try again.');
                        });
                });
            }

            // Handle booking form submission
            const bookingForm = document.getElementById('bookingForm');
            if (bookingForm) {
                bookingForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const formData = new FormData(this);

                    fetch(this.action, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // refresh the property
                                // location.reload();
                                showSuccessMessage('Your booking request has been sent!');
                                const btn = document.getElementById('bookBtn');
                                btn.disabled = true;
                                setTimeout(() => {
                                    btn.disabled = false;
                                }, 60000); // 10 seconds

                            } else {
                                // alert(data.message || 'An error occurred. Please try again.');
                                showErrorMessage(data.message || 'An error occurred. Please try again.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // alert('An error occurred. Please try again.');
                            showErrorMessage('An error occurred. Please try again.');
                        });
                });
            }

            // Show success message function
            function showSuccessMessage(message) {
                const successMessage = document.getElementById('successMessage');
                successMessage.textContent = message;
                successMessage.style.display = 'block';

                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 5000);
            }

            // Show error message function
            function showErrorMessage(message) {
                const errorMessage = document.getElementById('errorMessage');
                errorMessage.textContent = message;
                errorMessage.style.display = 'block';

                setTimeout(() => {
                    errorMessage.style.display = 'none';
                }, 5000);
            }
        });
        // select only the future dates
        flatpickr("#check-in", {
            dateFormat: "Y-m-d",
            minDate: "today" // disables all past dates
        });

        flatpickr("#check-out", {
            dateFormat: "Y-m-d",
            maxDate: "<?php echo date('Y-m-d', strtotime('+1 year')); ?>"
        });

        flatpickr("#check-out", {
            dateFormat: "Y-m-d",
            minDate: "today"
        })

        function openPhotoGallery() {
            const modal = document.getElementById('photoGalleryModal');
            const container = document.getElementById('photoGalleryContainer');
            container.innerHTML = '';

            // Replace with actual PHP-generated array of image URLs
            const photoUrls = <?php echo json_encode($propertyPhotos); ?>;

            if (photoUrls.length === 0) {
                container.innerHTML = '<p>No photos available.</p>';
            } else {
                photoUrls.forEach(url => {
                    const img = document.createElement('img');
                    img.src = "php files/" + url;
                    container.appendChild(img);
                });
            }

            modal.style.display = 'block';
        }

        function closePhotoGallery() {
            document.getElementById('photoGalleryModal').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', () => {
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationContainer = document.getElementById('notificationContainer');
            const notificationList = document.getElementById('notificationList');
            const clearButton = document.querySelector('.clear-notifications');

            // Toggle dropdown
            notificationIcon.addEventListener('click', function (e) {
                e.stopPropagation(); // prevent closing when clicking the icon
                notificationContainer.classList.toggle('show');

                // Mark notifications as read
                fetch('php files/fetch_notifications.php?action=mark_read');
            });

            // Close dropdown if clicking outside
            window.addEventListener('click', function (e) {
                if (!notificationContainer.contains(e.target) && !notificationIcon.contains(e.target)) {
                    notificationContainer.classList.remove('show');
                }
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

                        if (Number(notification.is_read) === 1) {
                            notif.style.backgroundColor = '#f0f0f0'; // read
                        } else {
                            notif.style.backgroundColor = '#dff0d8'; // unread
                        }

                        notif.innerHTML = `
                    <h3>${notification.message}</h3>
                    <strong><span>${new Date(notification.timestamp).toLocaleString()}</span></strong>
                `;

                        // Click on a single notification to mark as read
                        notif.addEventListener('click', () => {
                            fetch('php files/fetch_notifications.php?action=mark_read', {
                                method: 'POST',
                            })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        notif.style.backgroundColor = '#f0f0f0';
                                    }
                                });
                        });

                        notificationList.appendChild(notif);
                    });
                })
                .catch(err => {
                    console.error('Error fetching notifications:', err);
                    notificationList.innerHTML = '<p class="notification-item">Nothing here</p>';
                });

            // Clear notifications logic
            if (clearButton) {
                clearButton.addEventListener('click', () => {
                    fetch('php files/fetch_notifications.php?action=clear', {
                        method: 'POST'
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                notificationList.innerHTML = '<p class="notification-item">No notifications</p>';
                            } else {
                                console.error('Clear failed:', data.error);
                            }
                        })
                        .catch(err => console.error('Error:', err));
                });
            }
        });


    </script>
</body>

</html>
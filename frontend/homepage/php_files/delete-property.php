<!-- create a full delete property process -->
<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login-signup/index.php');
    exit();
}

// Check if property ID is provided
if (!isset($_POST['id'])) {
    header('Location: ../my-properties.php');
    exit();
}

$propertyId = $_POST['id'];

// Check if the user owns this property
try {
    $stmt = $pdo->prepare("
        SELECT * FROM Property 
        WHERE id_property = ?
    ");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching property: " . $e->getMessage();
    $property = [];
}

if (!$property) {
    $_SESSION['error'] = "You don't have permission to delete this property.";
    header('Location: ../my-properties.php');
    exit();

}

// Delete property
try {
    $stmt = $pdo->prepare("
    SELECT propertyType FROM Property
    WHERE id_property = ?
    ");
    $stmt->execute([$propertyId]);
    $propertyType = $stmt->fetchColumn();
    // delete property
    $stmt = $pdo->prepare("
        DELETE FROM Property 
        WHERE id_property = ?
    ");
    $stmt->execute([$propertyId]);

    // retrieve the property images path
    $stmt = $pdo->prepare("
        SELECT image_path FROM Property_Images
        WHERE property_id = ?
    ");
    $stmt->execute([$propertyId]);
    $imagePaths = $stmt->fetchAll(PDO::FETCH_COLUMN);
    // delete property images
    $stmt = $pdo->prepare("
        DELETE FROM Property_Images
        WHERE property_id = ?
    ");
    $stmt->execute([$propertyId]);

    // delete property features
    if($propertyType == 'villa'){
        $stmt = $pdo->prepare("
        DELETE FROM villa
        WHERE id_property = ?
        ");
        $stmt->execute([$propertyId]);
    }elseif($propertyType == 'apartment'){
        $stmt = $pdo->prepare("
        DELETE FROM apartment
        WHERE id_property = ?
        ");
        $stmt->execute([$propertyId]);
    }elseif($propertyType == 'house'){
        $stmt = $pdo->prepare("
        DELETE FROM house
        WHERE id_property = ?
        ");
        $stmt->execute([$propertyId]);
    }elseif($propertyType == 'room'){
        $stmt = $pdo->prepare("
        DELETE FROM room
        WHERE id_property = ?
        ");
        $stmt->execute([$propertyId]);
    }

    // delete from favorits
    $stmt = $pdo->prepare("
        DELETE FROM favorits
        WHERE id_property = ?
    ");
    $stmt->execute([$propertyId]);

    // delete from rental
    $stmt = $pdo->prepare("
        DELETE FROM rental
        WHERE id_property = ?
    ");
    $stmt->execute([$propertyId]);

    // delete from buying
    $stmt = $pdo->prepare("
        DELETE FROM buying
        WHERE id_property = ?
    ");
    $stmt->execute([$propertyId]);

$uploadsDir = realpath(__DIR__ . "");
foreach ($imagePaths as $imagePath) {
    $file = realpath($uploadsDir . DIRECTORY_SEPARATOR . $imagePath);
    if ($file && strpos($file, $uploadsDir) === 0 && file_exists($file)) {
        unlink($file);
    }
}


    $_SESSION['success_message'] = "Property deleted successfully.";
    header('Location: ../my-properties.php');
    exit();
} catch (PDOException $e) {
    $errorMessage = "Error deleting property: " . $e->getMessage();
    $_SESSION['error'] = $errorMessage;
    header('Location: ../my-properties.php');
    exit();
}

?>
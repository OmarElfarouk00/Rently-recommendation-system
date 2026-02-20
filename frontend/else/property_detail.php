<!-- not working with this one -->
<?php
$host = 'localhost';
$db = 'rental_platform';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_GET['id'];
$sql = "SELECT * FROM properties WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$conn->close();
echo json_encode($property);
?>

<!-- filter code -->
 
<?php
// Include database connection
require_once 'config.php';

header('Content-Type: application/json');

// Get the type from the request 
$type = $_GET['type'] ?? '';

try {
    // Prepare SQL query based on the type (renting or selling)
    $stmt = $pdo->prepare("
        SELECT *
        FROM property
        WHERE ownerNeeds = :type AND status = 'available'
        ORDER BY estimatePrice ASC
    ");
    
    // Execute the query with the type parameter
    $stmt->execute(['type' => $type]);
    
    // Fetch all results
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if properties were found
    if (count($properties) > 0) {
        echo json_encode([
            'status' => 'success',
            'properties' => $properties
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No properties found for ' . $type
        ]);
    }
} catch(PDOException $e) {
    // Return error message if query fails
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
<?php
session_start();
require_once 'config.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Ensure the user is logged in
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("User not logged in.");
        }

        // Get input data
        $client_id = $_SESSION['user_id']; // Client ID from session
        $property_id = $_POST['property_id']; // Property ID from form
        $proposedPrice = $_POST['proposedPrice']; // Proposed price from form
        $comments = $_POST['comments'] ?? ''; // Optional comments from form
        $status = 'pending'; // Default status for new negotiations
        $proposedDate = date('Y-m-d'); // Current date as proposed date

        // Validate input data
        if (empty($property_id) || empty($proposedPrice)) {
            throw new Exception("Property ID and proposed price are required.");
        }

        // Insert negotiation into the database
        $sql = "INSERT INTO Negotiation (
                    proposedPrice, 
                    comments, 
                    status, 
                    proposedDate, 
                    id_client, 
                    id_property
                ) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare SQL statement.");
        }

        // Bind parameters
        $stmt->bind_param("dsssii", $proposedPrice, $comments, $status, $proposedDate, $client_id, $property_id);

        // Execute the statement
        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success", 
                "message" => "Negotiation request sent successfully."
            ]);
        } else {
            throw new Exception("Failed to send negotiation request.");
        }

        // Close the statement
        $stmt->close();
    } catch (Exception $e) {
        // Handle errors
        echo json_encode([
            "status" => "error", 
            "message" => $e->getMessage()
        ]);
    }
} else {
    // Handle invalid request method
    echo json_encode([
        "status" => "error", 
        "message" => "Invalid request method."
    ]);
}

// Close the database connection
$conn->close();
?>
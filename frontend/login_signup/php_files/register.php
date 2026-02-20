<!-- not working with this file -->
<?php
session_start();
require_once 'config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'], $_POST['username'], $_POST['password'] , $_POST['phone'])) {
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $phoneNumber = trim($_POST['phone']);
        $password = trim($_POST['password']);

if (empty($email) || empty($username) || empty($password) || empty($phoneNumber))  {
    $_SESSION['error'] = "All fields are required.";
    header("Location: ../signup.php");
    exit;
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format.";
    header("Location: ../signup.php");
    exit;
} elseif (strlen($password) < 8) {
    $_SESSION['error'] = "Password must be at least 8 characters long.";
    header("Location: ../signup.php");
    exit;
} else {
    $sql = "SELECT * FROM client WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email already exists.";
        $stmt->close();
        $conn->close();
        header("Location: ../signup.php");
        exit;
    }

        
            // Proceed with registration logic
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $registration_date = date('Y-m-d'); // or 'Y-m-d H:i:s' if using DATETIME
            $sql = "INSERT INTO client (full_name, email, password, phone, registration_date) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $phoneNumber, $registration_date);
            if ($stmt->execute()) {
                echo "Registration successful!";
            } else {
                echo "Error: " . $stmt->error;
            }
            // $stmt->close();
            // $conn->close();
            header("Location:../../homepage/index.php");
        }
    } else {
        echo "Required fields are missing.";
    }
} else {
    echo "Invalid request method.";
}
?>
<style>
.alert {
    padding: 10px 20px;
    background-color: #f44336;
    color: white;
    border-radius: 5px;
    margin-top: 10px;
}
</style>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">




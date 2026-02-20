<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    $email = $_POST['email'];
    $password = $_POST['password'];

// function validateCredentials($email, $password) {
//     // Validate email
//     if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
//         return false;
//     }

//     // Validate password (minimum 8 characters, 1 number, 1 letter)
//     if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
//         return false;
//     }

//     return true;
// }
// $isValid = validateCredentials($email, $password);
// $_SESSION['valid'] = $isValid;
    
    // $remember = isset($_POST['remember']) ? true : false;

    try {
        $stmt = $pdo->prepare("SELECT id_client, full_name, password FROM client WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();


        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_client'];
            $_SESSION['user_name'] = $user['full_name'];
            

            // if ($remember) {
            //     $token = bin2hex(random_bytes(32));
            //     $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            //     $stmt->execute([$token, $user['id']]);
            //     setcookie('remember_token', $token, time() + (86400 * 30), '/');
            // }
            
            echo json_encode(['success' => true]);
            header('Location: ../../homepage/index.php');
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            header('location: ../index.php');
            $_SESSION['valid'] = false;
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}
?>
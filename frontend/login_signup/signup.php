<?php
session_start();
require_once 'php files/config.php';
    $isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'], $_POST['username'], $_POST['password'] , $_POST['phone'])) {
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $phoneNumber = trim($_POST['phone']);
        $password = trim($_POST['password']);

if (!preg_match('/^[\w.-]+@[\w.-]+\.(com|fr|org|net|edu|info)$/i', $email)) {
    $_SESSION['error'] =  'Email must be valid and end with .com, .fr, .org, .net, .edu, .info.';
}
if (empty($email) || empty($username) || empty($password) || empty($phoneNumber))  {
    $_SESSION['error'] = "All fields are required."; 
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format.";
} elseif (strlen($password) < 8 || 
    !preg_match('/[A-Z]/', $password) || 
    !preg_match('/[a-z]/', $password)) {
    $_SESSION['error'] = 'Password must be at least 8 characters and contain uppercase and lowercase letters.';

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
        
    }else{

        
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
            header("Location:../homepage/index.php");
        }
    }
    } else {
        echo "Required fields are missing.";
    }

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="../homepage/index.php" class="logo">
                <img src="../rently2.png" alt="" style="height: 38px; width: 130px;">
            </a>

            <div class="user-menu">
                <div class="user-menu-item menu-dropdown">
                    <i class="fas fa-bars" id="menuToggle"></i>

                    <div class="menu-content" id="menuContent">
                        <a href="../login-signup/index.php" class="menu-item">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
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

                            <a href="../homepage/index.php" class="menu-item">
                                <i class="fas fa-home"></i>
                                dashboard
                            </a>

                            <div class="menu-divider"></div>


                            <a href="#" class="menu-item" id="language-toggle">
                                <i class="fas fa-globe"></i> Language
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
                                <i class="fas fa-info-circle"></i> About Us
                            </a>
                            <a href="#" class="menu-item">
                                <i class="fas fa-question-circle"></i> Help Center
                            </a>

                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="login-container">
        <h1>Create Account</h1>
        <div>
        <?php if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']); // remove message after showing
        }
        ?>
        </div>
        <form id="signupForm" action="signup.php" method="POST">
            <div class="form-group">
                <input type="text" id="name" name="username" placeholder=" " required>
                <label for="username">Full Name*</label>
            </div>
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder=" " required>
                <label for="email">Email*</label>
            </div>
            <div class="form-group">
                <input type="phone" id="phone" name="phone" placeholder=" " required min="10">
                <label for="phone">Phone number*</label>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder=" " required>
                <label for="password">Password*</label>
                <span class="password-toggle" onclick="togglePassword()">Show</span>
            </div>
            <button type="submit" class="login-button">Sign Up</button>

            <div class="additional-links">
                <span>Already have an account?</span>
                <a href="index.php">Login</a>
            </div>

            <!-- <div class="divider">
                <span>or sign up with</span>
            </div>

            <div class="social-login">

                <div class="social-buttons">
                    <button type="button" class="social-button google">
                        <i class="fab fa-google"></i>Google
                    </button>
                    <button type="button" class="social-button facebook">
                        <i class="fab fa-facebook"></i>Facebook
                    </button>
                    <button type="button" class="social-button apple">
                        <i class="fab fa-apple"></i>Apple
                    </button>
                </div>
            </div> -->

            <p class="terms">
                By signing up, you agree to our
                <a href="#">Terms of Service</a> and
                <a href="#">Privacy Policy</a>
            </p>
        </form>
    </div>


</html>
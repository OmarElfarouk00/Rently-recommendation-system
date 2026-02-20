<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
    <style>
        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            max-width: 400px;
            animation: slideIn 0.3s ease;
        }

        .error-icon {
            color: #ef4444;
            font-size: 18px;
            margin-top: -2%;
            flex-shrink: 0;
        }

        .error-content p {
            color: #7f1d1d;
            font-size: 13px;
            line-height: 1.5;
            margin: 0 0 8px 0;
        }

        /* Variation for multiple failed attempts */
        .error-message.warning {
            background: #fef3c7;
            border-color: #fde68a;
        }

        .error-message.warning .error-icon {
            color: #d97706;
        }

        .error-message.warning p {
            color: #78350f;
        }

        /* Variation for account lockout */
        .error-message.critical {
            background: #fef2f2;
            border-color: #fca5a5;
            border-width: 2px;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .demo-section {
            margin-bottom: 40px;
        }
    </style>
</head>

<body>
    <?php
    session_start();

    $isLoggedIn = isset($_SESSION['user_id']); // Check if the user is logged in
    $userName = $isLoggedIn ? $_SESSION['user_name'] : null; // Get the user's name if logged in
    // Check if the user is logged in
    $isLoggedIn = isset($_SESSION['user_id']);

    // Check if the user has properties
// $hasProperties = $isLoggedIn ? getProperties($_SESSION['user_id']) : false;
    
    // Define a function to check if the user has properties
// function getProperties($userId) {
//     require_once 'php files/config.php';
    
    //     // Query to fetch properties owned by the user
//     $stmt = $pdo->prepare("
//         SELECT p.* 
//         FROM Property p
//         JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
//         JOIN Client c ON po.id_propertyOwner = c.id_client
//         WHERE c.id_client = ?
//     ");
//     $stmt->execute([$userId]);
    
    //     // Return true if the user has at least one property
//     return $stmt->rowCount() > 0;
// }
    ?>

    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <a href="../homepage/index.php" class="logo">
                    <img src="../rently2.png" alt="" style="height: 38px; width: 130px;">
                </a>
            </div>

            <div class="user-menu">
                <div class="user-menu-item menu-dropdown">
                    <i class="fas fa-bars" id="menuToggle"></i>

                    <div class="menu-content" id="menuContent">
                        <a href="../login-signup/signup.php" class="menu-item">
                            <i class="fas fa-user-plus"></i> Sign Up
                        </a>
                        <?php if (!$isLoggedIn): ?>
                            <a href="../login-signup/index.php" class="menu-item">
                                <i class="fas fa-arrow-right"></i>
                                List your property
                            <?php else: ?>
                                <a href="become-host.php" class="menu-item">
                                    <i class="fas fa-arrow-right"></i>
                                    List your property
                                </a>
                            <?php endif; ?>

                            <a href="../homepage/index.php" class="menu-item">
                                <i class="fas fa-home"></i>
                                Dashboard
                            </a>

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

        <h1>Login</h1>
        <form id="loginForm" action="php files/login.php" method="POST">
            <?php include 'php files/login.php';
            $isValid = $_SESSION['valid'] ?? true;
            unset($_SESSION['valid']);
            if (!$isValid): ?>
                <div class="demo-section">
                    <div class="error-message">
                        <div class="error-icon">⚠️</div>
                        <div class="error-content">
                            <p>Incorrect email or password. Please try again.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder=" " required>
                <label for="email">Email</label>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder=" " required>
                <label for="password">Password</label>
                <span class="password-toggle" onclick="togglePassword()">Show</span>
            </div>
            <button type="submit" class="login-button">Login</button>

            <div class="additional-links">
                <!-- <a href="forgot-password.html" id="forgotPassword">Forgot Password?</a> -->
                <!-- <span style="color: #bdc3c7">•</span> -->
                <p>Don't have an account?</p>
                <a href="signup.php" id="signUp">Sign Up</a>
            </div>

            <!-- <div class="divider">
                <span>or continue with</span>
            </div> -->
            <!-- <div class="social-login">
                <div class="social-buttons">
                    <button type="button" class="social-button google">
                        <i class="fab fa-google"></i> Google
                    </button>
                    <button type="button" class="social-button facebook">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </button>
                    <button type="button" class="social-button apple">
                        <i class="fab fa-apple"></i> Apple
                    </button>
                </div>
            </div> -->
        </form>
    </div>

</body>

</html>
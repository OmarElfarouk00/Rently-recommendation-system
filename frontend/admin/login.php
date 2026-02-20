<?php
session_start();
require_once '../homepage/php files/config.php';

// Check if admin is already logged in
if(isset($_SESSION['id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

// Process login form
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if(empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM Admin WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($admin && password_verify($password, $admin['password'])) {
                // Set admin session
                $_SESSION['id'] = $admin['id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Log admin login
                $logStmt = $pdo->prepare("INSERT INTO AdminLog (id_admin, action, ip_address) VALUES (?, 'login', ?)");
                $logStmt->execute([$admin['id'], $_SERVER['REMOTE_ADDR']]);
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="css/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-lock"></i> Admin Panel</h1>
            <p>Enter your credentials to access the admin area</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="login-form">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-key"></i> Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="login-footer">
            <a href="../index.php">Back to Website</a>
        </div>
    </div>
    
    <script src="js/admin.js"></script>
</body>
</html>
<?php
session_start();
require_once 'config.php'; 
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['admin_id'];
$i=0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate input
    if ($newPassword !== $confirmPassword) {
        // die('New passwords do not match.');

        $_SESSION['error_message'] = 'New passwords do not match.';
        $i=1;
        header('Location: settings.php');
        
    }

    try {
        $pdo = getDBConnection();
        // Get current hashed password from DB
        $stmt = $pdo->prepare("SELECT password,username FROM admin WHERE id = ?");
        $stmt->execute([$userId]);
        $storedHash = $stmt->fetchColumn();

        if (!$storedHash || !password_verify($currentPassword, $storedHash)) {
            $_SESSION['error_message'] = 'Current password or username is incorrect.';
            $i=1;
            header('Location: settings.php');
            
        }else{

        // Hash new password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update username and password
        $stmt = $pdo->prepare("UPDATE admin SET username = ?, password = ? WHERE id = ?");
        $stmt->execute([$username, $newHash, $userId]);
        $_SESSION['success_message'] = 'Settings updated successfully.';
        header('Location: settings.php');
        $i=1;
    }

    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Rental Platform</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="content">
                <div class="content-header">
                    <h1>Settings</h1>
                    <p>Configure your rental platform preferences</p>
                </div>

                <div class="settings-container">
                    <div class="settings-nav">
                        <ul class="settings-tabs">
                            <li class="tab-item active" data-tab="security">
                                <i class="fas fa-shield-alt"></i>
                                Security
                            </li>
                        </ul>
                    </div>

                    <div class="settings-content">

                        <div class="tab-content active" id="security">
                            <div class="settings-section">
                                <h3>Security Settings</h3>
                                <form class="settings-form" action="settings.php" method="POST">
                                    <div class="form-group">
                                        <label>Username*</label>
                                        <input type="text" class="form-input" name="username" autocomplete="off"
                                            required>
                                    </div>
                                    <div class="form-group">
                                        <label>Current Password*</label>
                                        <input type="password" class="form-input" name="current_password" required>
                                    </div>
                                    <div class="form-group">
                                        <label>New Password*</label>
                                        <input type="password" class="form-input" name="new_password" autocomplete="off"
                                            required>
                                    </div>
                                    <div class="form-group">
                                        <label>Confirm New Password*</label>
                                        <input type="password" class="form-input" name="confirm_password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Password</button>
                                </form>
                                <!-- the success message -->
                                <?php if (isset($_SESSION['success_message'])) : ?>
                                    <div class="flash-message">
                                        <?php echo $_SESSION['success_message']; echo $i;
                                        if($i==0):
                                        unset($_SESSION['success_message']); endif;?>
                                    </div>
                                <?php endif; ?>

                                <!-- the error message -->
                                <?php if (isset($_SESSION['error_message'])) : ?>
                                    <div class="error-message">
                                        <?php echo $_SESSION['error_message'];echo $i;
                                        if($i==0):
                                        unset($_SESSION['error_message']); endif;?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- <div class="tab-content" id="billing">
                            <div class="settings-section">
                                <h3>Billing Information</h3>
                                <div class="billing-info">
                                    <p>Current Plan: <strong>Professional</strong></p>
                                    <p>Next Billing Date: <strong>January 15, 2024</strong></p>
                                    <p>Amount: <strong>$99.00/month</strong></p>
                                </div>
                                <button class="btn btn-primary">Manage Subscription</button>
                            </div>
                        </div> -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
    <script>
        // Settings tabs functionality
        document.querySelectorAll('.tab-item').forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.dataset.tab;

                // Remove active class from all tabs and content
                document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
</body>

</html>
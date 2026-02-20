<?php
session_start();
require_once '../homepage/php files/config.php';
require_once 'includes/admin-functions.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];
$admin_role = $_SESSION['admin_role'];

// Get statistics
$stats = getAdminDashboardStats($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="dashboard">
                <h1>Dashboard</h1>
                
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Total Users</h3>
                            <p class="stat-number"><?php echo $stats['total_users']; ?></p>
                            <p class="stat-info">
                                <span class="<?php echo $stats['new_users_trend'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                    <i class="fas fa-<?php echo $stats['new_users_trend'] > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php echo abs($stats['new_users_trend']); ?>%
                                </span>
                                since last month
                            </p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Properties</h3>
                            <p class="stat-number"><?php echo $stats['total_properties']; ?></p>
                            <p class="stat-info">
                                <span class="<?php echo $stats['new_properties_trend'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                    <i class="fas fa-<?php echo $stats['new_properties_trend'] > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php echo abs($stats['new_properties_trend']); ?>%
                                </span>
                                since last month
                            </p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Negotiations</h3>
                            <p class="stat-number"><?php echo $stats['total_negotiations']; ?></p>
                            <p class="stat-info">
                                <span class="<?php echo $stats['new_negotiations_trend'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                    <i class="fas fa-<?php echo $stats['new_negotiations_trend'] > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php echo abs($stats['new_negotiations_trend']); ?>%
                                </span>
                                since last month
                            </p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-danger">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Favorites</h3>
                            <p class="stat-number"><?php echo $stats['total_favorites']; ?></p>
                            <p class="stat-info">
                                <span class="<?php echo $stats['new_favorites_trend'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                    <i class="fas fa-<?php echo $stats['new_favorites_trend'] > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php echo abs($stats['new_favorites_trend']); ?>%
                                </span>
                                since last month
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <div class="chart-card">
                        <h3>User Registration</h3>
                        <canvas id="userChart"></canvas>
                    </div>
                    
                    <div class="chart-card">
                        <h3>Property Listings</h3>
                        <canvas id="propertyChart"></canvas>
                    </div>
                </div>
                
                <div class="recent-activity">
                    <h2>Recent Activity</h2>
                    <div class="activity-list">
                        <?php foreach($stats['recent_activities'] as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?php echo getActivityIcon($activity['type']); ?>"></i>
                                </div>
                                <div class="activity-details">
                                    <p><?php echo $activity['description']; ?></p>
                                    <span class="activity-time"><?php echo formatTimeAgo($activity['created_at']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Chart data from PHP
        const userData = <?php echo json_encode($stats['user_chart_data']); ?>;
        const propertyData = <?php echo json_encode($stats['property_chart_data']); ?>;
        
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // User registration chart
            const userCtx = document.getElementById('userChart').getContext('2d');
            new Chart(userCtx, {
                type: 'line',
                data: {
                    labels: userData.labels,
                    datasets: [{
                        label: 'New Users',
                        data: userData.data,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Property listings chart
            const propertyCtx = document.getElementById('propertyChart').getContext('2d');
            new Chart(propertyCtx, {
                type: 'bar',
                data: {
                    labels: propertyData.labels,
                    datasets: [{
                        label: 'New Properties',
                        data: propertyData.data,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        });
    </script>
    
    <script src="js/admin.js"></script>
</body>
</html>
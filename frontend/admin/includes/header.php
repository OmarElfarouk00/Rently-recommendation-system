<header class="admin-header">
    <div class="header-left">
        <button id="toggle-sidebar" class="toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="header-right">
        <button class="theme-toggle" aria-label="Toggle dark mode">
            <i class="fas fa-moon"></i>
        </button>
        
        <div class="notifications dropdown">
            <button class="dropdown-toggle">
                <i class="fas fa-bell"></i>
                <!-- <?php
                // Get unread notifications count
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM AdminNotification 
                    WHERE is_read = 0
                ");
                $stmt->execute();
                $notificationCount = $stmt->fetchColumn();
                
                if($notificationCount > 0):
                ?> -->
                <span class="badge"><?php echo $notificationCount; ?></span>
                <?php endif; ?>
            </button>
            
            <div class="dropdown-menu">
                <div class="dropdown-header">
                    <h4>Notifications</h4>
                    <?php if($notificationCount > 0): ?>
                        <a href="mark-notifications-read.php" class="mark-all-read">Mark all as read</a>
                    <?php endif; ?>
                </div>
                
                <div class="dropdown-body">
                    <!-- <?php
                    // Get recent notifications
                    $stmt = $pdo->prepare("
                        SELECT * FROM AdminNotification 
                        ORDER BY created_at DESC 
                        LIMIT 5
                    ");
                    $stmt->execute();
                    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if(count($notifications) > 0):
                        foreach($notifications as $notification):
                    ?> -->
                        <a href="<?php echo $notification['link']; ?>" class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                            <div class="notification-icon">
                                <i class="fas fa-<?php echo $notification['icon']; ?>"></i>
                            </div>
                            <div class="notification-content">
                                <p><?php echo $notification['message']; ?></p>
                                <span class="notification-time"><?php echo formatTimeAgo($notification['created_at']); ?></span>
                            </div>
                        </a>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <div class="no-notifications">
                            <p>No notifications</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="dropdown-footer">
                    <a href="notifications.php">View all notifications</a>
                </div>
            </div>
        </div>
        
        <div class="admin-profile dropdown">
            <button class="dropdown-toggle">
                <span class="admin-name"><?php echo $_SESSION['admin_username']; ?></span>
                <i class="fas fa-user-circle"></i>
            </button>
            
            <div class="dropdown-menu">
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <div class="dropdown-divider"></div>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</header>
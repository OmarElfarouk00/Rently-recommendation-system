<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-cogs"></i> Admin Panel</h2>
    </div>
    
    <div class="sidebar-menu">
        <ul>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <a href="users.php"><i class="fas fa-users"></i> Users</a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'properties.php' ? 'active' : ''; ?>">
                <a href="properties.php"><i class="fas fa-home"></i> Properties</a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'negotiations.php' ? 'active' : ''; ?>">
                <a href="negotiations.php"><i class="fas fa-handshake"></i> Negotiations</a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            </li>
            
            <?php if($_SESSION['admin_role'] == 'super_admin'): ?>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : ''; ?>">
                    <a href="admins.php"><i class="fas fa-user-shield"></i> Admins</a>
                </li>
            <?php endif; ?>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            </li>
        </ul>
    </div>
    
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
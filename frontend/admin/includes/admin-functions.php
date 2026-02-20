<?php
/**
 * Admin Functions
 * Contains helper functions for the admin panel
 */

/**
 * Get dashboard statistics
 */
function getAdminDashboardStats($pdo) {
    $stats = [];
    
    try {
        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) FROM Client");
        $stats['total_users'] = $stmt->fetchColumn();
        
        // Total properties
        $stmt = $pdo->query("SELECT COUNT(*) FROM Property");
        $stats['total_properties'] = $stmt->fetchColumn();
        
        // Total negotiations
        $stmt = $pdo->query("SELECT COUNT(*) FROM Negotiation");
        $stats['total_negotiations'] = $stmt->fetchColumn();
        
        // Total favorites
        $stmt = $pdo->query("SELECT COUNT(*) FROM React");
        $stats['total_favorites'] = $stmt->fetchColumn();
        
        // User trend (% change from last month)
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM Client WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) as current_month,
                (SELECT COUNT(*) FROM Client WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) 
                 AND created_at < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) as last_month
        ");
        $userTrend = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['new_users_trend'] = $userTrend['last_month'] > 0 
            ? round((($userTrend['current_month'] - $userTrend['last_month']) / $userTrend['last_month']) * 100) 
            : 0;
        
        // Property trend
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM Property WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) as current_month,
                (SELECT COUNT(*) FROM Property WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) 
                 AND created_at < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) as last_month
        ");
        $propertyTrend = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['new_properties_trend'] = $propertyTrend['last_month'] > 0 
            ? round((($propertyTrend['current_month'] - $propertyTrend['last_month']) / $propertyTrend['last_month']) * 100) 
            : 0;
        
        // Negotiation trend
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM Negotiation WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) as current_month,
                (SELECT COUNT(*) FROM Negotiation WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) 
                 AND created_at < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) as last_month
        ");
        $negotiationTrend = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['new_negotiations_trend'] = $negotiationTrend['last_month'] > 0 
            ? round((($negotiationTrend['current_month'] - $negotiationTrend['last_month']) / $negotiationTrend['last_month']) * 100) 
            : 0;
        
        // Favorites trend
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM React WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) as current_month,
                (SELECT COUNT(*) FROM React WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) 
                 AND created_at < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) as last_month
        ");
        $favoritesTrend = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['new_favorites_trend'] = $favoritesTrend['last_month'] > 0 
            ? round((($favoritesTrend['current_month'] - $favoritesTrend['last_month']) / $favoritesTrend['last_month']) * 100) 
            : 0;
        
        // Chart data for users (last 7 days)
        $userChartData = ['labels' => [], 'data' => []];
        $stmt = $pdo->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM Client
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        $userData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fill in missing dates
        $endDate = new DateTime();
        $startDate = new DateTime('-6 days');
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate);
        
        $formattedData = [];
        foreach ($userData as $day) {
            $formattedData[$day['date']] = $day['count'];
        }
        
        foreach ($dateRange as $date) {
            $formattedDate = $date->format('Y-m-d');
            $userChartData['labels'][] = $date->format('M d');
            $userChartData['data'][] = isset($formattedData[$formattedDate]) ? $formattedData[$formattedDate] : 0;
        }
        
        $stats['user_chart_data'] = $userChartData;
        
        // Chart data for properties (last 7 days)
        $propertyChartData = ['labels' => [], 'data' => []];
        $stmt = $pdo->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM Property
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        $propertyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fill in missing dates
        $formattedData = [];
        foreach ($propertyData as $day) {
            $formattedData[$day['date']] = $day['count'];
        }
        
        foreach ($dateRange as $date) {
            $formattedDate = $date->format('Y-m-d');
            $propertyChartData['labels'][] = $date->format('M d');
            $propertyChartData['data'][] = isset($formattedData[$formattedDate]) ? $formattedData[$formattedDate] : 0;
        }
        
        $stats['property_chart_data'] = $propertyChartData;
        
        // Recent activities
        $stmt = $pdo->query("
            (SELECT 
                'user' as type,
                CONCAT('New user registered: ', full_name) as description,
                created_at
            FROM Client
            ORDER BY created_at DESC
            LIMIT 5)
            
            UNION
            
            (SELECT 
                'property' as type,
                CONCAT('New property listed: ', title) as description,
                created_at
            FROM Property
            ORDER BY created_at DESC
            LIMIT 5)
            
            UNION
            
            (SELECT 
                'negotiation' as type,
                CONCAT('New negotiation for property #', id_property) as description,
                created_at
            FROM Negotiation
            ORDER BY created_at DESC
            LIMIT 5)
            
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stats['recent_activities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error getting admin stats: " . $e->getMessage());
        // Return empty stats on error
        $stats = [
            'total_users' => 0,
            'total_properties' => 0,
            'total_negotiations' => 0,
            'total_favorites' => 0,
            'new_users_trend' => 0,
            'new_properties_trend' => 0,
            'new_negotiations_trend' => 0,
            'new_favorites_trend' => 0,
            'user_chart_data' => ['labels' => [], 'data' => []],
            'property_chart_data' => ['labels' => [], 'data' => []],
            'recent_activities' => []
        ];
    }
    
    return $stats;
}

/**
 * Get icon for activity type
 */
function getActivityIcon($type) {
    switch ($type) {
        case 'user':
            return 'user-plus';
        case 'property':
            return 'home';
        case 'negotiation':
            return 'handshake';
        case 'favorite':
            return 'heart';
        default:
            return 'bell';
    }
}

/**
 * Format time ago
 */
function formatTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

/**
 * Check if admin has permission
 */
function adminHasPermission($requiredRole, $adminRole) {
    $roles = ['admin', 'super_admin'];
    $adminRoleIndex = array_search($adminRole, $roles);
    $requiredRoleIndex = array_search($requiredRole, $roles);
    
    return $adminRoleIndex !== false && $requiredRoleIndex !== false && $adminRoleIndex >= $requiredRoleIndex;
}

/**
 * Log admin action
 */
function logAdminAction($pdo, $adminId, $action, $details = '') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO AdminLog (id_admin, action, details, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, $action, $details, $_SERVER['REMOTE_ADDR']]);
    } catch (PDOException $e) {
        error_log("Error logging admin action: " . $e->getMessage());
    }
}
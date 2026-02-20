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

// Process actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $userId = $_GET['id'];
    
    switch($action) {
        case 'delete':
            if(adminHasPermission('admin', $admin_role)) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM Client WHERE id_client = ?");
                    $stmt->execute([$userId]);
                    
                    logAdminAction($pdo, $admin_id, 'delete_user', "Deleted user ID: $userId");
                    
                    $_SESSION['admin_message'] = [
                        'type' => 'success',
                        'text' => 'User deleted successfully'
                    ];
                } catch(PDOException $e) {
                    $_SESSION['admin_message'] = [
                        'type' => 'error',
                        'text' => 'Error deleting user: ' . $e->getMessage()
                    ];
                }
            } else {
                $_SESSION['admin_message'] = [
                    'type' => 'error',
                    'text' => 'You do not have permission to delete users'
                ];
            }
            break;
            
        case 'ban':
            if(adminHasPermission('admin', $admin_role)) {
                try {
                    $stmt = $pdo->prepare("UPDATE Client SET status = 'banned' WHERE id_client = ?");
                    $stmt->execute([$userId]);
                    
                    logAdminAction($pdo, $admin_id, 'ban_user', "Banned user ID: $userId");
                    
                    $_SESSION['admin_message'] = [
                        'type' => 'success',
                        'text' => 'User banned successfully'
                    ];
                } catch(PDOException $e) {
                    $_SESSION['admin_message'] = [
                        'type' => 'error',
                        'text' => 'Error banning user: ' . $e->getMessage()
                    ];
                }
            } else {
                $_SESSION['admin_message'] = [
                    'type' => 'error',
                    'text' => 'You do not have permission to ban users'
                ];
            }
            break;
            
        case 'unban':
            if(adminHasPermission('admin', $admin_role)) {
                try {
                    $stmt = $pdo->prepare("UPDATE Client SET status = 'active' WHERE id_client = ?");
                    $stmt->execute([$userId]);
                    
                    logAdminAction($pdo, $admin_id, 'unban_user', "Unbanned user ID: $userId");
                    
                    $_SESSION['admin_message'] = [
                        'type' => 'success',
                        'text' => 'User unbanned successfully'
                    ];
                } catch(PDOException $e) {
                    $_SESSION['admin_message'] = [
                        'type' => 'error',
                        'text' => 'Error unbanning user: ' . $e->getMessage()
                    ];
                }
            } else {
                $_SESSION['admin_message'] = [
                    'type' => 'error',
                    'text' => 'You do not have permission to unban users'
                ];
            }
            break;
    }
    
    header('Location: users.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search and filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "SELECT * FROM Client WHERE 1=1";
$countQuery = "SELECT COUNT(*) FROM Client WHERE 1=1";
$params = [];

if($search) {
    $query .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $countQuery .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if($status) {
    $query .= " AND status = ?";
    $countQuery .= " AND status = ?";
    $params[] = $status;
}

$query .= " ORDER BY created_at DESC LIMIT $offset, $limit";

// Get users
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);
} catch(PDOException $e) {
    $users = [];
    $totalUsers = 0;
    $totalPages = 1;
    
    $_SESSION['admin_message'] = [
        'type' => 'error',
        'text' => 'Error fetching users: ' . $e->getMessage()
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
    <link rel="stylesheet" href="css/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content-wrapper">
                <div class="content-header">
                    <h1><i class="fas fa-users"></i> User Management</h1>
                    
                    <div class="content-actions">
                        <a href="add-user.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add New User
                        </a>
                    </div>
                </div>
                
                <?php if(isset($_SESSION['admin_message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['admin_message']['type']; ?>">
                        <?php 
                            echo $_SESSION['admin_message']['text'];
                            unset($_SESSION['admin_message']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="filter-container">
                    <form action="" method="GET" class="filter-form">
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="form-group">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="banned" <?php echo $status == 'banned' ? 'selected' : ''; ?>>Banned</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        
                        <a href="users.php" class="btn btn-outline">
                            <i class="fas fa-sync-alt"></i> Reset
                        </a>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($users) > 0): ?>
                                <?php foreach($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id_client']; ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $user['status']; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td class="actions">
                                            <a href="view-user.php?id=<?php echo $user['id_client']; ?>" class="btn-icon" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <a href="edit-user.php?id=<?php echo $user['id_client']; ?>" class="btn-icon" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if($user['status'] == 'banned'): ?>
                                                <a href="users.php?action=unban&id=<?php echo $user['id_client']; ?>" class="btn-icon" title="Unban" onclick="return confirm('Are you sure you want to unban this user?')">
                                                    <i class="fas fa-user-check"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="users.php?action=ban&id=<?php echo $user['id_client']; ?>" class="btn-icon" title="Ban" onclick="return confirm('Are you sure you want to ban this user?')">
                                                    <i class="fas fa-user-slash"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="users.php?action=delete&id=<?php echo $user['id_client']; ?>" class="btn-icon text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="pagination-item">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="pagination-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="pagination-item">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="js/admin.js"></script>
</body>
</html>
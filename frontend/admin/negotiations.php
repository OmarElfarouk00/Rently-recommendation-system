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
    $negotiationId = $_GET['id'];
    
    switch($action) {
        case 'delete':
            if(adminHasPermission('admin', $admin_role)) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM Negotiation WHERE id_negotiation = ?");
                    $stmt->execute([$negotiationId]);
                    
                    logAdminAction($pdo, $admin_id, 'delete_negotiation', "Deleted negotiation ID: $negotiationId");
                    
                    $_SESSION['admin_message'] = [
                        'type' => 'success',
                        'text' => 'Negotiation deleted successfully'
                    ];
                } catch(PDOException $e) {
                    $_SESSION['admin_message'] = [
                        'type' => 'error',
                        'text' => 'Error deleting negotiation: ' . $e->getMessage()
                    ];
                }
            } else {
                $_SESSION['admin_message'] = [
                    'type' => 'error',
                    'text' => 'You do not have permission to delete negotiations'
                ];
            }
            break;
    }
    
    header('Location: negotiations.php');
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
$query = "
    SELECT 
        n.*,
        p.title AS property_title,
        p.price AS property_price,
        c.full_name AS client_name,
        po.full_name AS owner_name
    FROM Negotiation n
    JOIN Property p ON n.id_property = p.id_property
    JOIN Client c ON n.id_client = c.id_client
    JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
    JOIN Client poc ON po.id_propertyOwner = poc.id_client
    WHERE 1=1
";

$countQuery = "
    SELECT COUNT(*) 
    FROM Negotiation n
    JOIN Property p ON n.id_property = p.id_property
    JOIN Client c ON n.id_client = c.id_client
    JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
    JOIN Client poc ON po.id_propertyOwner = poc.id_client
    WHERE 1=1
";

$params = [];

if($search) {
    $query .= " AND (p.title LIKE ? OR c.full_name LIKE ? OR poc.full_name LIKE ?)";
    $countQuery .= " AND (p.title LIKE ? OR c.full_name LIKE ? OR poc.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if($status) {
    $query .= " AND n.status = ?";
    $countQuery .= " AND n.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY n.created_at DESC LIMIT $offset, $limit";

// Get negotiations
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $negotiations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalNegotiations = $countStmt->fetchColumn();
    $totalPages = ceil($totalNegotiations / $limit);
} catch(PDOException $e) {
    $negotiations = [];
    $totalNegotiations = 0;
    $totalPages = 1;
    
    $_SESSION['admin_message'] = [
        'type' => 'error',
        'text' => 'Error fetching negotiations: ' . $e->getMessage()
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Negotiation Management - Admin Panel</title>
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
                    <h1><i class="fas fa-handshake"></i> Negotiation Management</h1>
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
                            <input type="text" name="search" placeholder="Search negotiations..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="form-group">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="accepted" <?php echo $status == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        
                        <a href="negotiations.php" class="btn btn-outline">
                            <i class="fas fa-sync-alt"></i> Reset
                        </a>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Property</th>
                                <th>Client</th>
                                <th>Owner</th>
                                <th>Offer Price</th>
                                <th>Original Price</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($negotiations) > 0): ?>
                                <?php foreach($negotiations as $negotiation): ?>
                                    <tr>
                                        <td><?php echo $negotiation['id_negotiation']; ?></td>
                                        <td><?php echo htmlspecialchars($negotiation['property_title']); ?></td>
                                        <td><?php echo htmlspecialchars($negotiation['client_name']); ?></td>
                                        <td><?php echo htmlspecialchars($negotiation['owner_name']); ?></td>
                                        <td><?php echo number_format($negotiation['offer_price'], 2); ?></td>
                                        <td><?php echo number_format($negotiation['property_price'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $negotiation['status']; ?>">
                                                <?php echo ucfirst($negotiation['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($negotiation['created_at'])); ?></td>
                                        <td class="actions">
                                            <a href="view-negotiation.php?id=<?php echo $negotiation['id_negotiation']; ?>" class="btn-icon" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <a href="respond-negotiation.php?id=<?php echo $negotiation['id_negotiation']; ?>" class="btn-icon" title="Respond">
                                                <i class="fas fa-reply"></i>
                                            </a>
                                            
                                            <a href="negotiations.php?action=delete&id=<?php echo $negotiation['id_negotiation']; ?>" class="btn-icon text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this negotiation? This action cannot be undone.')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="no-data">No negotiations found</td>
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
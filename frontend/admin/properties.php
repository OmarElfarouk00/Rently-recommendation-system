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
    $propertyId = $_GET['id'];
    
    switch($action) {
        case 'delete':
            if(adminHasPermission('admin', $admin_role)) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM Property WHERE id_property = ?");
                    $stmt->execute([$propertyId]);
                    
                    logAdminAction($pdo, $admin_id, 'delete_property', "Deleted property ID: $propertyId");
                    
                    $_SESSION['admin_message'] = [
                        'type' => 'success',
                        'text' => 'Property deleted successfully'
                    ];
                } catch(PDOException $e) {
                    $_SESSION['admin_message'] = [
                        'type' => 'error',
                        'text' => 'Error deleting property: ' . $e->getMessage()
                    ];
                }
            } else {
                $_SESSION['admin_message'] = [
                    'type' => 'error',
                    'text' => 'You do not have permission to delete properties'
                ];
            }
            break;
            
        case 'approve':
            try {
                $stmt = $pdo->prepare("UPDATE Property SET status = 'active' WHERE id_property = ?");
                $stmt->execute([$propertyId]);
                
                logAdminAction($pdo, $admin_id, 'approve_property', "Approved property ID: $propertyId");
                
                $_SESSION['admin_message'] = [
                    'type' => 'success',
                    'text' => 'Property approved successfully'
                ];
            } catch(PDOException $e) {
                $_SESSION['admin_message'] = [
                    'type' => 'error',
                    'text' => 'Error approving property: ' . $e->getMessage()
                ];
            }
            break;
            
        case 'reject':
            try {
                $stmt = $pdo->prepare("UPDATE Property SET status = 'rejected' WHERE id_property = ?");
                $stmt->execute([$propertyId]);
                
                logAdminAction($pdo, $admin_id, 'reject_property', "Rejected property ID: $propertyId");
                
                $_SESSION['admin_message'] = [
                    'type' => 'success',
                    'text' => 'Property rejected successfully'
                ];
            } catch(PDOException $e) {
                $_SESSION['admin_message'] = [
                    'type' => 'error',
                    'text' => 'Error rejecting property: ' . $e->getMessage()
                ];
            }
            break;
            
        case 'feature':
            try {
                $stmt = $pdo->prepare("UPDATE Property SET is_featured = 1 WHERE id_property = ?");
                $stmt->execute([$propertyId]);
                
                logAdminAction($pdo, $admin_id, 'feature_property', "Featured property ID: $propertyId");
                
                $_SESSION['admin_message'] = [
                    'type' => 'success',
                    'text' => 'Property featured successfully'
                ];
            } catch(PDOException $e) {
                $_SESSION['admin_message'] = [
                    'type' => 'error',
                    'text' => 'Error featuring property: ' . $e->getMessage()
                ];
            }
            break;
            
        case 'unfeature':
            try {
                $stmt = $pdo->prepare("UPDATE Property SET is_featured = 0 WHERE id_property = ?");
                $stmt->execute([$propertyId]);
                
                logAdminAction($pdo, $admin_id, 'unfeature_property', "Unfeatured property ID: $propertyId");
                
                $_SESSION['admin_message'] = [
                    'type' => 'success',
                    'text' => 'Property unfeatured successfully'
                ];
            } catch(PDOException $e) {
                $_SESSION['admin_message'] = [
                    'type' => 'error',
                    'text' => 'Error unfeaturing property: ' . $e->getMessage()
                ];
            }
            break;
    }
    
    header('Location: properties.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search and filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$featured = isset($_GET['featured']) ? $_GET['featured'] : '';

// Build query
$query = "
    SELECT 
        p.*,
        c.full_name AS owner_name,
        CASE 
            WHEN pv.id_propertyOwner_VIP IS NOT NULL THEN 1
            ELSE 0
        END AS is_vip
    FROM Property p
    JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
    JOIN Client c ON po.id_propertyOwner = c.id_client
    LEFT JOIN propertyOwner_VIP pv ON po.id_propertyOwner = pv.id_propertyOwner
    WHERE 1=1
";

$countQuery = "
    SELECT COUNT(*) 
    FROM Property p
    JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
    JOIN Client c ON po.id_propertyOwner = c.id_client
    LEFT JOIN propertyOwner_VIP pv ON po.id_propertyOwner = pv.id_propertyOwner
    WHERE 1=1
";

$params = [];

if($search) {
    $query .= " AND (p.title LIKE ? OR p.description LIKE ? OR p.address LIKE ? OR c.full_name LIKE ?)";
    $countQuery .= " AND (p.title LIKE ? OR p.description LIKE ? OR p.address LIKE ? OR c.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if($status) {
    $query .= " AND p.status = ?";
    $countQuery .= " AND p.status = ?";
    $params[] = $status;
}

if($type) {
    $query .= " AND p.type = ?";
    $countQuery .= " AND p.type = ?";
    $params[] = $type;
}

if($featured !== '') {
    $query .= " AND p.is_featured = ?";
    $countQuery .= " AND p.is_featured = ?";
    $params[] = $featured;
}

$query .= " ORDER BY p.created_at DESC LIMIT $offset, $limit";

// Get properties
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalProperties = $countStmt->fetchColumn();
    $totalPages = ceil($totalProperties / $limit);
} catch(PDOException $e) {
    $properties = [];
    $totalProperties = 0;
    $totalPages = 1;
    
    $_SESSION['admin_message'] = [
        'type' => 'error',
        'text' => 'Error fetching properties: ' . $e->getMessage()
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Management - Admin Panel</title>
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
                    <h1><i class="fas fa-home"></i> Property Management</h1>
                    
                    <div class="content-actions">
                        <a href="add-property.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add New Property
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
                            <input type="text" name="search" placeholder="Search properties..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="form-group">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <select name="type">
                                <option value="">All Types</option>
                                <option value="sale" <?php echo $type == 'sale' ? 'selected' : ''; ?>>For Sale</option>
                                <option value="rent" <?php echo $type == 'rent' ? 'selected' : ''; ?>>For Rent</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <select name="featured">
                                <option value="">All</option>
                                <option value="1" <?php echo $featured === '1' ? 'selected' : ''; ?>>Featured</option>
                                <option value="0" <?php echo $featured === '0' ? 'selected' : ''; ?>>Not Featured</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        
                        <a href="properties.php" class="btn btn-outline">
                            <i class="fas fa-sync-alt"></i> Reset
                        </a>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Owner</th>
                                <th>Price</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($properties) > 0): ?>
                                <?php foreach($properties as $property): ?>
                                    <tr>
                                        <td><?php echo $property['id_property']; ?></td>
                                        <td>
                                            <div class="property-thumbnail">
                                                <img src="<?php echo !empty($property['image_url']) ? '../' . $property['image_url'] : '../assets/images/property-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($property['title']); ?>
                                            <?php if($property['is_vip']): ?>
                                                <span class="badge badge-vip">VIP</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($property['owner_name']); ?></td>
                                        <td><?php echo number_format($property['price'], 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $property['type'] == 'sale' ? 'primary' : 'success'; ?>">
                                                <?php echo $property['type'] == 'sale' ? 'For Sale' : 'For Rent'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $property['status']; ?>">
                                                <?php echo ucfirst($property['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($property['is_featured']): ?>
                                                <span class="badge badge-featured">
                                                    <i class="fas fa-star"></i> Featured
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">
                                                    <i class="far fa-star"></i> No
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions">
                                            <a href="../property.php?id=<?php echo $property['id_property']; ?>" class="btn-icon" title="View on Site" target="_blank">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            
                                            <a href="view-property.php?id=<?php echo $property['id_property']; ?>" class="btn-icon" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <a href="edit-property.php?id=<?php echo $property['id_property']; ?>" class="btn-icon" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if($property['status'] == 'pending'): ?>
                                                <a href="properties.php?action=approve&id=<?php echo $property['id_property']; ?>" class="btn-icon text-success" title="Approve">
                                                    <i class="fas fa-check-circle"></i>
                                                </a>
                                                
                                                <a href="properties.php?action=reject&id=<?php echo $property['id_property']; ?>" class="btn-icon text-danger" title="Reject">
                                                    <i class="fas fa-times-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if($property['is_featured']): ?>
                                                <a href="properties.php?action=unfeature&id=<?php echo $property['id_property']; ?>" class="btn-icon" title="Remove from Featured">
                                                    <i class="far fa-star"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="properties.php?action=feature&id=<?php echo $property['id_property']; ?>" class="btn-icon" title="Add to Featured">
                                                    <i class="fas fa-star"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="properties.php?action=delete&id=<?php echo $property['id_property']; ?>" class="btn-icon text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this property? This action cannot be undone.')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="no-data">No properties found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type=<?php echo urlencode($type); ?>&featured=<?php echo urlencode($featured); ?>" class="pagination-item">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type=<?php echo urlencode($type); ?>&featured=<?php echo urlencode($featured); ?>" class="pagination-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type=<?php echo urlencode($type); ?>&featured=<?php echo urlencode($featured); ?>" class="pagination-item">
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
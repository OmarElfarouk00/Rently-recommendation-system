<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle search and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$rentals = getAllRentals($limit, $offset, $search);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rental_id'])) {
    $idToDelete = (int) $_POST['delete_rental_id'];

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM rental WHERE id_property = ?");
        $stmt->execute([$idToDelete]);

        $_SESSION['message'] = "rental #$idToDelete deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['message'] = "Error deleting rental: " . $e->getMessage();
    }

    // Redirect to avoid resubmission
    header("Location:  rentals.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentals Management - Rental Platform</title>
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
                    <h1>Rentals Management</h1>
                    <p>Track and manage all rental agreements</p>
                </div>

                <div class="page-actions">
                    <div class="search-filters">
                        <form method="GET" class="search-form">
                            <div class="search-group">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search rentals..." class="search-input-page">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    Search
                                </button>
                            </div>
                        </form>
                    </div>
                    <button class="btn btn-success">
                        <i class="fas fa-plus"></i>
                        New Rental
                    </button>
                </div>

                    <?php if (isset($_SESSION['message'])): ?>
                    <div class="flash-message">
                        <?php echo htmlspecialchars($_SESSION['message']); ?>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                <div class="table-container full-width">
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Property</th>
                                    <th>Type</th>
                                    <th>Start Date</th>
                                    <th>End  Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rentals as $rental): ?>
                                <tr>
                                    <td>
                                        <div class="client-info">
                                            <strong><?php echo htmlspecialchars($rental['client_name']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="property-title">
                                            <?php echo htmlspecialchars($rental['property_title']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="property-type">
                                            <?php echo ucfirst($rental['propertyType']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($rental['startDate'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($rental['endDate']))?> </td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($rental['status']); ?>">
                                            <?php echo ucfirst($rental['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- <button class="btn-action view" title="View Contract">
                                                <i class="fas fa-file-contract"></i>
                                            </button> -->
                                            <!-- <button class="btn-action edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button> -->
                                                <form method="POST" action="rentals.php" style="display:inline;">
                                                    <input type="hidden" name="delete_rental_id"
                                                        value="<?php echo $rental['id_property']; ?>">
                                                    <button type="submit" class="btn-action delete" title="terminate">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>                                        
                                            </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="pagination">
                        <button class="btn btn-secondary" <?php echo $page <= 1 ? 'disabled' : ''; ?> 
                                onclick="window.location.href='?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>'">
                            <i class="fas fa-chevron-left"></i>
                            Previous
                        </button>
                        <span class="page-info">Page <?php echo $page; ?></span>
                        <button class="btn btn-secondary" 
                                onclick="window.location.href='?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>'">
                            Next
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="script.js"></script>
</body>
</html>

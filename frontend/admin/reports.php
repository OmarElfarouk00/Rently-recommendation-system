<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$reports = getRecentReports(50);
$information = getReportsInfo(50);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reports_id'])) {
    $idToDelete = (int) $_POST['delete_reports_id'];

    // Example: Use PDO to delete the reports
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM propertyreports WHERE id_property = ?");
        $stmt->execute([$idToDelete]);

        $_SESSION['message'] = "Reports #$idToDelete deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['message'] = "Error deleting reports: " . $e->getMessage();
    }

    // Redirect to avoid resubmission
    header("Location: reports.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Rental Platform</title>
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
                    <h1>Reports</h1>
                    <p>Comprehensive insights into your rental platform performance</p>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="flash-message">
                        <?php echo htmlspecialchars($_SESSION['message']); ?>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <div class="messages-container">
                    <div class="messages-list">
                        <hr>
                        <?php foreach ($reports as $report): ?>
                            <div class="message-item">
                                <div class="message-header">
                                    <div class="sender-info">
                                        <i class="fas fa-user-circle"></i>
                                        <span
                                            class="sender-name"><?php echo htmlspecialchars($report['sender_name']); ?></span>
                                    </div>
                                    <div class="message-time">
                                        <?php echo date('M d, Y ', strtotime($report['report_date'])); ?>
                                    </div>
                                </div>
                                <!-- i want an arrow to the right then the property name will be show there -->
                                <div class="message-content" onclick="toggleProperty(this)">
                                    <span class="arrow">&#9654;</span> <!-- Right arrow -->
                                    <p style="margin: 0;"><?php echo htmlspecialchars($report['report_type']); ?></p>
                                    <span class="propertyOwner-name"><strong>Property Owner:</strong>
                                        <?php echo htmlspecialchars($information['propertyOwner_name']); ?></span>
                                    <span class="property-name"><strong>Property Name:
                                        </strong><?php echo htmlspecialchars($report['property_name']); ?></span>
                                </div>
                                <!-- <div class="message-content">
                                    <p><?php echo htmlspecialchars($report['report_type']); ?> </p>
                                </div> -->
                                <div class="message-actions">
                                    <button class="btn btn-sm btn-primary"
                                        onclick="window.open('../homepage/property.php?id=<?php echo $report['id_property']; ?>', '_blank')">
                                        <i class="fas fa-eye"></i>
                                        View Property
                                        <form method="POST" action="reports.php" style="display:inline;">
                                            <input type="hidden" name="delete_reports_id"
                                                value="<?php echo $report['id_property']; ?>">
                                            <button type="submit" class="btn-action delete" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                </div>
                                <br>
                                <hr>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>

</html>
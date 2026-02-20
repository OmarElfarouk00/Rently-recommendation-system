<!-- not working with file -->

<?php
session_start();
require_once 'php files/config.php';


// Check if user is logged in
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['negotiation_id']) && is_numeric($_POST['negotiation_id'])) {
        $negotiationId = $_POST['negotiation_id'];

        // Proceed with fetching negotiation, as in your original code...
    } else {
        header('Location: index.php');
        exit();
    }
} else {
    // header('Location: index.html');
    // exit();
   try{ 
   }catch(PDOException $e){
            error_log("Error checking user properties: " . $e->getMessage());
    return false;

    }
}


$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Check if negotiation ID is provided
if (!isset($_POST['negotiation_id']) || !is_numeric($_POST['negotiation_id'])) {
    header('Location:index.php');
    exit();
}

$negotiationId = $_POST['negotiation_id'];

// Fetch negotiation details
try {
    $stmt = $pdo->prepare("
        SELECT n.*, p.title, p.price, p.image_url, p.id_property,
               c1.full_name as buyer_name, c2.full_name as owner_name
        FROM Negotiation n
        JOIN Property p ON n.id_property = p.id_property
        JOIN Client c1 ON n.id_client = c1.id_client
        JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
        JOIN Client c2 ON po.id_propertyOwner = c2.id_client
        WHERE n.id_negotiation = ?
    ");
    $stmt->execute([$negotiationId]);
    $negotiation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$negotiation) {
        header('Location: index.php');
        exit();
    }

    // Check if the user is either the property owner or the client who made the offer
    $isOwner = false;
    $isBuyer = false;

    // Check if user is the property owner
    $stmtOwner = $pdo->prepare("
        SELECT c.id_client
        FROM Client c
        JOIN propertyOwner po ON c.id_client = po.id_propertyOwner
        JOIN Property p ON po.id_propertyOwner = p.id_propertyOwner
        WHERE p.id_property = ? AND c.id_client = ?
    ");
    $stmtOwner->execute([$negotiation['id_property'], $userId]);
    $isOwner = $stmtOwner->rowCount() > 0;

    // Check if user is the buyer
    $isBuyer = ($negotiation['id_client'] == $userId);

    if (!$isOwner && !$isBuyer) {
        header('Location: index.php');
        exit();
    }

    // Fetch negotiation history
    // $stmtHistory = $pdo->prepare("
    //     SELECT nh.*, c.full_name
    //     FROM NegotiationHistory nh
    //     JOIN Client c ON nh.id_client = c.id_client
    //     WHERE nh.id_negotiation = ?
    //     ORDER BY nh.created_at ASC
    // ");
    // $stmtHistory->execute([$negotiationId]);
    // $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching negotiation: " . $e->getMessage());
    header('Location: index.php');
    exit();
}

// Function to check if the user has properties
function getProperties($userId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.* 
            FROM Property p
            JOIN propertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
            JOIN Client c ON po.id_propertyOwner = c.id_client
            WHERE c.id_client = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking user properties: " . $e->getMessage());
        return false;
    }
}

// Check if the user has properties
$hasProperties = getProperties($userId, $pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counter Offer - Property Negotiation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="property-styles.css">
    <style>
        .negotiation-container {
            max-width: 900px;
            margin: 30px auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .property-summary {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        
        .property-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
        }
        
        .property-info h2 {
            margin-top: 0;
            color: #333;
        }
        
        .price {
            font-size: 1.4em;
            color: #2c3e50;
            font-weight: bold;
        }
        
        .negotiation-history {
            margin-bottom: 30px;
        }
        
        .history-item {
            padding: 15px;
            border-left: 3px solid #3498db;
            background-color: #f9f9f9;
            margin-bottom: 10px;
            border-radius: 0 5px 5px 0;
        }
        
        .history-item.owner {
            border-left-color: #e74c3c;
        }
        
        .history-item .name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .history-item .amount {
            font-size: 1.2em;
            color: #2c3e50;
        }
        
        .history-item .date {
            font-size: 0.8em;
            color: #7f8c8d;
            text-align: right;
        }
        
        .counter-offer-form {
            background-color: #f5f7fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        .counter-offer-form h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .btn-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-success {
            background-color: #2ecc71;
            color: white;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #f39c12;
            color: white;
        }
        
        .status-accepted {
            background-color: #2ecc71;
            color: white;
        }
        
        .status-rejected {
            background-color: #e74c3c;
            color: white;
        }
        
        .status-countered {
            background-color: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="negotiation-container">
        <h1>Property Negotiation</h1>
        
        <div class="property-summary">
            <img src="<?php echo htmlspecialchars($negotiation['image_url']); ?>" alt="<?php echo htmlspecialchars($negotiation['title']); ?>" class="property-image">
            <div class="property-info">
                <h2><?php echo htmlspecialchars($negotiation['title']); ?></h2>
                <p class="price">Original Price: $<?php echo number_format($negotiation['price']); ?></p>
                <p>
                    <strong>Status:</strong> 
                    <?php if($negotiation['status'] == 'pending'): ?>
                        <span class="status-badge status-pending">Pending</span>
                    <?php elseif($negotiation['status'] == 'accepted'): ?>
                        <span class="status-badge status-accepted">Accepted</span>
                    <?php elseif($negotiation['status'] == 'rejected'): ?>
                        <span class="status-badge status-rejected">Rejected</span>
                    <?php elseif($negotiation['status'] == 'countered'): ?>
                        <span class="status-badge status-countered">Countered</span>
                    <?php endif; ?>
                </p>
                <p><strong>Buyer:</strong> <?php echo htmlspecialchars($negotiation['buyer_name']); ?></p>
                <p><strong>Owner:</strong> <?php echo htmlspecialchars($negotiation['owner_name']); ?></p>
                <p><a href="property.php?id=<?php echo $negotiation['id_property']; ?>" class="btn btn-primary">View Property</a></p>
            </div>
        </div>
        
        <div class="negotiation-history">
            <h2>Negotiation History</h2>
            
            <?php if(empty($history)): ?>
                <p>No negotiation history available.</p>
            <?php else: ?>
                <?php foreach($history as $item): ?>
                    <div class="history-item <?php echo ($isOwner && $item['id_client'] == $userId) || (!$isOwner && $item['id_client'] != $negotiation['id_client']) ? 'owner' : ''; ?>">
                        <div class="name"><?php echo htmlspecialchars($item['full_name']); ?></div>
                        <div class="amount">$<?php echo number_format($item['offer_amount']); ?></div>
                        <div class="message"><?php echo htmlspecialchars($item['message']); ?></div>
                        <div class="date"><?php echo date('F j, Y, g:i a', strtotime($item['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if($negotiation['status'] == 'pending' || $negotiation['status'] == 'countered'): ?>
            <div class="counter-offer-form">
                <h3><?php echo $isOwner ? 'Respond to Offer' : 'Update Your Offer'; ?></h3>
                
                <form action="php files/process_counter_offer.php" method="post">
                    <input type="hidden" name="negotiation_id" value="<?php echo $negotiationId; ?>">
                    
                    <div class="form-group">
                        <label for="offer_amount">Your <?php echo $isOwner ? 'Counter ' : ''; ?>Offer Amount ($)</label>
                        <input type="number" id="offer_amount" name="offer_amount" value="<?php echo $isOwner ? $negotiation['price'] : $negotiation['offer_amount']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message (Optional)</label>
                        <textarea id="message" name="message" placeholder="Add a message to explain your offer..."></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <?php if($isOwner): ?>
                            <button type="submit" name="action" value="accept" class="btn btn-success">Accept Offer</button>
                            <button type="submit" name="action" value="counter" class="btn btn-primary">Send Counter Offer</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger">Reject Offer</button>
                        <?php else: ?>
                            <button type="submit" name="action" value="update" class="btn btn-primary">Update Offer</button>
                            <button type="submit" name="action" value="cancel" class="btn btn-danger">Cancel Negotiation</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        <?php elseif($negotiation['status'] == 'accepted'): ?>
            <div class="counter-offer-form">
                <h3>Negotiation Completed</h3>
                <p>This offer has been accepted. Please proceed with the transaction.</p>
                <?php if($isBuyer): ?>
                    <a href="php files/finalize_purchase.php?negotiation_id=<?php echo $negotiationId; ?>" class="btn btn-success">Finalize Purchase</a>
                <?php endif; ?>
            </div>
        <?php elseif($negotiation['status'] == 'rejected'): ?>
            <div class="counter-offer-form">
                <h3>Negotiation Rejected</h3>
                <p>This offer has been rejected by the property owner.</p>
                <?php if($isBuyer): ?>
                    <a href="property.php?id=<?php echo $negotiation['id_property']; ?>" class="btn btn-primary">View Property Again</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="property.js"></script>
    <script>
        // Additional JavaScript for counter offer page if needed
    </script>
</body>
</html>
<?php
session_start();
include("../db.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location:../auth/login.php");
    exit;
}

// Handle mark as claimed
if(isset($_GET['mark_claimed'], $_GET['claim_id'])) {
    $claim_id = intval($_GET['claim_id']);
    
    // Get claim info
    $claim = $con->query("SELECT * FROM claims WHERE claim_id=$claim_id")->fetch_assoc();
    
    // Update item as claimed
    $con->query("UPDATE {$claim['item_type']}_items SET claim_status='claimed' WHERE {$claim['item_type']}_id={$claim['item_id']}");
    
    // Update claim
    $con->query("UPDATE claims SET claim_status='claimed' WHERE claim_id=$claim_id");
    
    $_SESSION['success'] = "Item marked as claimed!";
    header("Location: manage_claims.php");
    exit;
}

// Fetch approved claims
$approved_claims = $con->query("SELECT c.*, u.username as claimer_name, u.email as claimer_email, 
                                s.username as reviewer_name
                                FROM claims c 
                                JOIN users u ON c.claimer_id = u.user_id 
                                LEFT JOIN users s ON c.reviewed_by = s.user_id
                                WHERE c.claim_status='approved' 
                                ORDER BY c.reviewed_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Claims - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    padding: 2rem;
}
.claim-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>
</head>
<body>

<div class="container" style="max-width: 1200px;">
    <div class="bg-white rounded-3 p-4 mb-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-tasks me-2"></i>Manage Claims</h2>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if($approved_claims->num_rows > 0): ?>
        <?php while($claim = $approved_claims->fetch_assoc()): 
            $item = $con->query("SELECT * FROM {$claim['item_type']}_items WHERE {$claim['item_type']}_id={$claim['item_id']}")->fetch_assoc();
        ?>
        <div class="claim-card">
            <h5><?= htmlspecialchars($item['item_name']) ?></h5>
            <p><strong>Claimer:</strong> <?= htmlspecialchars($claim['claimer_name']) ?> (<?= htmlspecialchars($claim['claimer_email']) ?>)</p>
            <p><strong>Approved by:</strong> <?= htmlspecialchars($claim['reviewer_name']) ?></p>
            <p><strong>Reason:</strong> <?= nl2br(htmlspecialchars($claim['claim_message'])) ?></p>
            
            <a href="?mark_claimed=1&claim_id=<?= $claim['claim_id'] ?>" 
               class="btn btn-success"
               onclick="return confirm('Mark this item as claimed?')">
                <i class="fas fa-check-double me-2"></i>Mark as Claimed
            </a>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
            <h4>No Approved Claims</h4>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
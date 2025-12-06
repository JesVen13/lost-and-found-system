<?php
session_start();
include("../db.php");
if(!isset($_SESSION['role']) || $_SESSION['role']!="staff") {
    header("Location:../auth/login.php"); exit;
}

$staff_id = $_SESSION['user_id'];

// Handle claim approval/rejection
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $claim_id = intval($_POST['claim_id']);
    $action = $_POST['action'];
    $item_type = $_POST['item_type'];
    $item_id = intval($_POST['item_id']);
    $claimer_id = intval($_POST['claimer_id']);
    
    if($action == 'approve') {
        // Approve the claim
        $con->query("UPDATE claims SET claim_status='approved', reviewed_by=$staff_id, reviewed_at=NOW() WHERE claim_id=$claim_id");
        
        // Update item status to claimed
        $con->query("UPDATE {$item_type}_items SET claim_status='claimed', status='claimed' WHERE {$item_type}_id=$item_id");
        
        // If found item, set claimed_by
        if($item_type == 'found') {
            $con->query("UPDATE found_items SET claimed_by=$claimer_id, claimed_at=NOW() WHERE found_id=$item_id");
        }
        
        $_SESSION['success'] = "Claim approved successfully!";
    } 
    elseif($action == 'reject') {
        // Reject the claim
        $con->query("UPDATE claims SET claim_status='rejected', reviewed_by=$staff_id, reviewed_at=NOW() WHERE claim_id=$claim_id");
        
        // Reset item status back to unclaimed
        $con->query("UPDATE {$item_type}_items SET claim_status='unclaimed' WHERE {$item_type}_id=$item_id");
        
        $_SESSION['success'] = "Claim rejected successfully!";
    }
    
    header("Location: review_claims.php");
    exit;
}

// Get filter
$filter = $_GET['filter'] ?? 'pending';

// Build query based on filter
$where_clause = "WHERE c.claim_status='$filter'";
if($filter == 'all') {
    $where_clause = "";
}

// Fetch claims with item and user details
$claims_query = "
    SELECT 
        c.*,
        u.username as claimer_name,
        u.email as claimer_email,
        CASE 
            WHEN c.item_type = 'lost' THEN l.item_name
            WHEN c.item_type = 'found' THEN f.item_name
        END as item_name,
        CASE 
            WHEN c.item_type = 'lost' THEN l.description
            WHEN c.item_type = 'found' THEN f.description
        END as item_description,
        CASE 
            WHEN c.item_type = 'lost' THEN l.image_path
            WHEN c.item_type = 'found' THEN f.image_path
        END as item_image,
        CASE 
            WHEN c.item_type = 'lost' THEN l.location_lost
            WHEN c.item_type = 'found' THEN f.location_found
        END as item_location,
        CASE 
            WHEN c.item_type = 'lost' THEN l.date_lost
            WHEN c.item_type = 'found' THEN f.date_found
        END as item_date,
        CASE 
            WHEN c.item_type = 'lost' THEN l.reporter_id
            WHEN c.item_type = 'found' THEN f.reporter_id
        END as reporter_id,
        reporter.username as reporter_name,
        reviewer.username as reviewed_by_name
    FROM claims c
    JOIN users u ON c.claimer_id = u.user_id
    LEFT JOIN lost_items l ON c.item_id = l.lost_id AND c.item_type = 'lost'
    LEFT JOIN found_items f ON c.item_id = f.found_id AND c.item_type = 'found'
    LEFT JOIN users reporter ON (
        (c.item_type = 'lost' AND l.reporter_id = reporter.user_id) OR
        (c.item_type = 'found' AND f.reporter_id = reporter.user_id)
    )
    LEFT JOIN users reviewer ON c.reviewed_by = reviewer.user_id
    $where_clause
    ORDER BY 
        CASE c.claim_status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            WHEN 'rejected' THEN 3 
        END,
        c.claimed_at DESC
";

$claims = $con->query($claims_query);
$total_claims = $claims->num_rows;

// Count by status
$pending_count = $con->query("SELECT COUNT(*) as cnt FROM claims WHERE claim_status='pending'")->fetch_assoc()['cnt'];
$approved_count = $con->query("SELECT COUNT(*) as cnt FROM claims WHERE claim_status='approved'")->fetch_assoc()['cnt'];
$rejected_count = $con->query("SELECT COUNT(*) as cnt FROM claims WHERE claim_status='rejected'")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Review Claims - Staff Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #667eea;
    --primary-dark: #764ba2;
    --danger: #ef4444;
    --success: #10b981;
    --warning: #f59e0b;
    --bg-light: #f8f9fa;
    --text-dark: #1a1a1a;
    --text-muted: #737373;
    --border: #e5e7eb;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    min-height: 100vh;
    color: var(--text-dark);
}

.navbar {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    padding: 1rem 0;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.25rem;
    color: var(--primary);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.btn-back {
    background: var(--bg-light);
    border: none;
    color: var(--text-dark);
    padding: 0.625rem 1.25rem;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-back:hover {
    background: #e5e7eb;
    transform: translateX(-4px);
}

.container-main {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
}

.page-header {
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.9);
}

.filter-tabs {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.tabs-nav {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.tab-btn:hover {
    background: var(--bg-light);
    color: var(--text-dark);
}

.tab-btn.active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.tab-badge {
    background: rgba(0, 0, 0, 0.1);
    padding: 0.25rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
}

.tab-btn.active .tab-badge {
    background: rgba(255, 255, 255, 0.2);
}

.claims-container {
    display: grid;
    gap: 1.5rem;
}

.claim-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.claim-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
}

.claim-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.claim-status-badge {
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.claim-status-badge.pending {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.claim-status-badge.approved {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.claim-status-badge.rejected {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.claim-content {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
}

.item-preview {
    background: var(--bg-light);
    padding: 1.5rem;
    border-radius: 12px;
}

.item-image {
    width: 100%;
    height: 200px;
    border-radius: 10px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    overflow: hidden;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-image i {
    font-size: 3rem;
    color: var(--text-muted);
    opacity: 0.3;
}

.item-info h4 {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.item-type {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 1rem;
}

.item-type.lost {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.item-type.found {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.item-details {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    font-size: 0.875rem;
    color: var(--text-muted);
}

.item-details span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.claim-details {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.detail-section {
    border-left: 3px solid var(--primary);
    padding-left: 1rem;
}

.detail-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.detail-value {
    font-size: 0.9375rem;
    color: var(--text-dark);
    font-weight: 500;
}

.claim-message {
    background: var(--bg-light);
    padding: 1.5rem;
    border-radius: 10px;
    line-height: 1.6;
    color: var(--text-dark);
}

.claim-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 2px solid var(--border);
}

.btn-action {
    flex: 1;
    padding: 0.875rem 1.5rem;
    border-radius: 10px;
    border: none;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-approve {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.btn-approve:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.btn-reject {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.btn-reject:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
}

.review-info {
    background: var(--bg-light);
    padding: 1rem;
    border-radius: 10px;
    font-size: 0.875rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.empty-state {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 4rem 2rem;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: var(--bg-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: var(--text-muted);
}

.empty-state h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text-dark);
}

.empty-state p {
    color: var(--text-muted);
    font-size: 1rem;
}

@media (max-width: 968px) {
    .claim-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .container-main {
        padding: 1.5rem 1rem;
    }
    
    .page-title {
        font-size: 1.75rem;
    }
    
    .claim-card {
        padding: 1.5rem;
    }
    
    .claim-actions {
        flex-direction: column;
    }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center w-100">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-shield-alt"></i>
                Lost & Found System
            </a>
            <a href="dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>
</nav>

<div class="container-main">
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-hand-holding me-2"></i>Review Claims</h1>
        <p class="page-subtitle">Review and approve/reject item claims from users</p>
    </div>

    <div class="filter-tabs">
        <div class="tabs-nav">
            <a href="?filter=pending" class="tab-btn <?= $filter == 'pending' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i>
                Pending
                <span class="tab-badge"><?= $pending_count ?></span>
            </a>
            <a href="?filter=approved" class="tab-btn <?= $filter == 'approved' ? 'active' : '' ?>">
                <i class="fas fa-check-circle"></i>
                Approved
                <span class="tab-badge"><?= $approved_count ?></span>
            </a>
            <a href="?filter=rejected" class="tab-btn <?= $filter == 'rejected' ? 'active' : '' ?>">
                <i class="fas fa-times-circle"></i>
                Rejected
                <span class="tab-badge"><?= $rejected_count ?></span>
            </a>
            <a href="?filter=all" class="tab-btn <?= $filter == 'all' ? 'active' : '' ?>">
                <i class="fas fa-list"></i>
                All Claims
                <span class="tab-badge"><?= $total_claims ?></span>
            </a>
        </div>
    </div>

    <?php if($total_claims > 0): ?>
    <div class="claims-container">
        <?php while($claim = $claims->fetch_assoc()): ?>
        <div class="claim-card">
            <div class="claim-header">
                <div>
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem;">
                        Claim #<?= $claim['claim_id'] ?>
                    </h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">
                        Submitted <?= date('M d, Y \a\t g:i A', strtotime($claim['claimed_at'])) ?>
                    </p>
                </div>
                <span class="claim-status-badge <?= $claim['claim_status'] ?>">
                    <?= ucfirst($claim['claim_status']) ?>
                </span>
            </div>

            <div class="claim-content">
                <div class="item-preview">
                    <div class="item-image">
                        <?php if(!empty($claim['item_image'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($claim['item_image']) ?>" alt="Item">
                        <?php else: ?>
                            <i class="fas fa-image"></i>
                        <?php endif; ?>
                    </div>
                    <div class="item-info">
                        <span class="item-type <?= $claim['item_type'] ?>"><?= ucfirst($claim['item_type']) ?> Item</span>
                        <h4><?= htmlspecialchars($claim['item_name']) ?></h4>
                        <div class="item-details">
                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($claim['item_location']) ?></span>
                            <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($claim['item_date'])) ?></span>
                            <span><i class="fas fa-user"></i> Reported by: <?= htmlspecialchars($claim['reporter_name']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="claim-details">
                    <div class="detail-section">
                        <div class="detail-label">Claimed By</div>
                        <div class="detail-value">
                            <i class="fas fa-user me-2"></i><?= htmlspecialchars($claim['claimer_name']) ?>
                            (<?= htmlspecialchars($claim['claimer_email']) ?>)
                        </div>
                    </div>

                    <div class="detail-section">
                        <div class="detail-label">Proof of Ownership</div>
                        <div class="claim-message">
                            <?= nl2br(htmlspecialchars($claim['claim_message'])) ?>
                        </div>
                    </div>

                    <?php if($claim['claim_status'] != 'pending'): ?>
                    <div class="review-info">
                        <i class="fas fa-info-circle"></i>
                        <span>
                            <?= ucfirst($claim['claim_status']) ?> by 
                            <strong><?= htmlspecialchars($claim['reviewed_by_name'] ?? 'System') ?></strong>
                            on <?= date('M d, Y \a\t g:i A', strtotime($claim['reviewed_at'])) ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php if($claim['claim_status'] == 'pending'): ?>
                    <form method="POST" class="claim-actions">
                        <input type="hidden" name="claim_id" value="<?= $claim['claim_id'] ?>">
                        <input type="hidden" name="item_type" value="<?= $claim['item_type'] ?>">
                        <input type="hidden" name="item_id" value="<?= $claim['item_id'] ?>">
                        <input type="hidden" name="claimer_id" value="<?= $claim['claimer_id'] ?>">
                        
                        <button type="submit" name="action" value="approve" class="btn-action btn-approve" 
                                onclick="return confirm('Are you sure you want to approve this claim? This will mark the item as claimed.')">
                            <i class="fas fa-check"></i> Approve Claim
                        </button>
                        <button type="submit" name="action" value="reject" class="btn-action btn-reject"
                                onclick="return confirm('Are you sure you want to reject this claim?')">
                            <i class="fas fa-times"></i> Reject Claim
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fas fa-inbox"></i>
        </div>
        <h3>No <?= $filter != 'all' ? ucfirst($filter) : '' ?> Claims Found</h3>
        <p>There are currently no claims to review in this category.</p>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
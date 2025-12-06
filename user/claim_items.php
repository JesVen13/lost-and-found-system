<?php
session_start();
include("../db.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != "user") {
    header("Location:../auth/login.php");
    exit;
}

$item_type = $_GET['type'] ?? 'lost';
$item_id = intval($_GET['id'] ?? 0);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $claim_message = mysqli_real_escape_string($con, $_POST['claim_message']);
    $claimer_id = $_SESSION['user_id'];
    
    // Insert claim with all required fields
    $insert_query = "INSERT INTO claims (item_id, item_type, claimer_id, claim_message, claim_status) 
                     VALUES ($item_id, '$item_type', $claimer_id, '$claim_message', 'pending')";
    
    if($con->query($insert_query)) {
        // Update item status to pending_claim
        $update_query = "UPDATE {$item_type}_items SET claim_status='pending_claim' WHERE {$item_type}_id=$item_id";
        $con->query($update_query);
        
        $_SESSION['success'] = "Claim submitted successfully! Staff will review your request.";
        header("Location: dashboard.php");
        exit;
    } else {
        $_SESSION['error'] = "Failed to submit claim. Please try again.";
    }
}

// Fetch item details with reporter info
$query = "SELECT i.*, u.username as reporter_name, u.email as reporter_email 
          FROM {$item_type}_items i 
          JOIN users u ON i.reporter_id = u.user_id 
          WHERE {$item_type}_id=$item_id";
$item = $con->query($query)->fetch_assoc();

if(!$item) {
    $_SESSION['error'] = "Item not found.";
    header("Location: dashboard.php");
    exit;
}

// Check if user is trying to claim their own item
if($item['reporter_id'] == $_SESSION['user_id']) {
    $_SESSION['error'] = "You cannot claim your own item.";
    header("Location: dashboard.php");
    exit;
}

// Check if item is already claimed
if(isset($item['claim_status']) && $item['claim_status'] == 'pending_claim') {
    $_SESSION['error'] = "This item already has a pending claim.";
    header("Location: dashboard.php");
    exit;
}

$location = $item_type == 'lost' ? $item['location_lost'] : $item['location_found'];
$date = $item_type == 'lost' ? $item['date_lost'] : $item['date_found'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Claim Item - Lost & Found</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root {
    --primary: #4a90e2;
    --danger: #e74c3c;
    --success: #2ecc71;
    --warning: #f39c12;
    --bg-primary: #ffffff;
    --bg-secondary: #f5f7fa;
    --text-primary: #2c3e50;
    --text-secondary: #7f8c8d;
    --border: #e1e8ed;
    --shadow: 0 2px 8px rgba(0,0,0,0.08);
}

[data-theme="dark"] {
    --primary: #5dade2;
    --danger: #ec7063;
    --success: #58d68d;
    --warning: #f8b739;
    --bg-primary: #1e1e1e;
    --bg-secondary: #2a2a2a;
    --text-primary: #ecf0f1;
    --text-secondary: #95a5a6;
    --border: #3a3a3a;
    --shadow: 0 2px 8px rgba(0,0,0,0.3);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    transition: background-color 0.3s ease, color 0.3s ease;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background: var(--bg-secondary);
    color: var(--text-primary);
    padding: 2rem 1rem;
    min-height: 100vh;
}

.container {
    max-width: 800px;
    margin: 0 auto;
}

.page-header {
    text-align: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.page-header p {
    color: var(--text-secondary);
    font-size: 1rem;
}

.claim-card {
    background: var(--bg-primary);
    border-radius: 12px;
    padding: 2rem;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.item-preview {
    background: var(--bg-secondary);
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    border: 1px solid var(--border);
}

.item-preview-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.item-image-small {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    background: var(--bg-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}

.item-image-small img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-image-small i {
    font-size: 2rem;
    color: var(--text-secondary);
    opacity: 0.5;
}

.item-info h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.item-type-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.item-type-badge.lost {
    background: rgba(231, 76, 60, 0.1);
    color: var(--danger);
}

.item-type-badge.found {
    background: rgba(46, 204, 113, 0.1);
    color: var(--success);
}

.item-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    align-items: start;
    gap: 0.75rem;
}

.detail-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: var(--bg-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    flex-shrink: 0;
}

.detail-content {
    flex: 1;
}

.detail-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.detail-value {
    font-size: 0.95rem;
    color: var(--text-primary);
    font-weight: 500;
}

.item-description {
    padding: 1rem;
    background: var(--bg-primary);
    border-radius: 8px;
    border: 1px solid var(--border);
}

.item-description h4 {
    font-size: 0.85rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.item-description p {
    color: var(--text-primary);
    line-height: 1.6;
    margin: 0;
}

.form-section {
    margin-top: 2rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.section-subtitle {
    color: var(--text-secondary);
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.form-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    display: block;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: 0.95rem;
    background: var(--bg-secondary);
    color: var(--text-primary);
    transition: all 0.2s;
    font-family: inherit;
    resize: vertical;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
}

.form-text {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-top: 0.5rem;
    display: block;
}

.alert-info {
    background: rgba(74, 144, 226, 0.1);
    border: 1px solid rgba(74, 144, 226, 0.3);
    color: var(--primary);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: start;
    gap: 0.75rem;
}

.alert-info i {
    margin-top: 0.2rem;
}

.btn-group {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.btn {
    padding: 0.75rem 2rem;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    justify-content: center;
}

.btn-primary {
    background: var(--success);
    color: white;
}

.btn-primary:hover {
    background: #27ae60;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
}

.btn-secondary {
    background: var(--bg-secondary);
    color: var(--text-primary);
    border: 1.5px solid var(--border);
}

.btn-secondary:hover {
    background: var(--border);
}

.theme-toggle {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--bg-primary);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: var(--text-primary);
    transition: all 0.2s;
    z-index: 1000;
}

.theme-toggle:hover {
    transform: scale(1.1);
}

@media (max-width: 768px) {
    body {
        padding: 1rem;
    }
    
    .claim-card {
        padding: 1.5rem;
    }
    
    .item-preview {
        padding: 1rem;
    }
    
    .item-preview-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>
</head>
<body>

<button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
    <i class="fas fa-moon" id="theme-icon"></i>
</button>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-hand-holding me-2"></i>Claim This Item</h1>
        <p>Provide details to prove this item belongs to you</p>
    </div>

    <div class="claim-card">
        <div class="item-preview">
            <div class="item-preview-header">
                <div class="item-image-small">
                    <?php if(!empty($item['image_path'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                    <?php else: ?>
                        <i class="fas fa-image"></i>
                    <?php endif; ?>
                </div>
                <div class="item-info">
                    <h3><?= htmlspecialchars($item['item_name']) ?></h3>
                    <span class="item-type-badge <?= $item_type ?>"><?= ucfirst($item_type) ?> Item</span>
                </div>
            </div>

            <div class="item-details-grid">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Location</div>
                        <div class="detail-value"><?= htmlspecialchars($location ?? 'Unknown') ?></div>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Date</div>
                        <div class="detail-value"><?= date('M d, Y', strtotime($date)) ?></div>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Reported By</div>
                        <div class="detail-value"><?= htmlspecialchars($item['reporter_name']) ?></div>
                    </div>
                </div>
            </div>

            <?php if(!empty($item['description'])): ?>
            <div class="item-description">
                <h4>Description</h4>
                <p><?= htmlspecialchars($item['description']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="alert-info">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Important:</strong> Please provide specific details that only the owner would know to verify your claim. Generic descriptions may delay the review process.
            </div>
        </div>

        <form method="POST">
            <div class="form-section">
                <h3 class="section-title">Proof of Ownership</h3>
                <p class="section-subtitle">Describe specific details that prove this item belongs to you</p>

                <div class="mb-3">
                    <label class="form-label">Why do you think this item is yours? <span style="color: var(--danger);">*</span></label>
                    <textarea name="claim_message" class="form-control" rows="8" required 
                        placeholder="Example: I lost my blue backpack on November 15th near the library. It has a small tear on the left pocket and contains my student ID with the number 12345. There's also a keychain with my initials 'JD' attached to the zipper."></textarea>
                    <small class="form-text">
                        <i class="fas fa-lightbulb"></i> 
                        Include unique identifiers, contents, or distinguishing features that only the owner would know.
                    </small>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Claim
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleTheme() {
    const html = document.documentElement;
    const icon = document.getElementById('theme-icon');
    const currentTheme = html.getAttribute('data-theme');
    
    if (currentTheme === 'dark') {
        html.setAttribute('data-theme', 'light');
        icon.className = 'fas fa-moon';
        localStorage.setItem('theme', 'light');
    } else {
        html.setAttribute('data-theme', 'dark');
        icon.className = 'fas fa-sun';
        localStorage.setItem('theme', 'dark');
    }
}

window.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme') || 'light';
    const icon = document.getElementById('theme-icon');
    document.documentElement.setAttribute('data-theme', savedTheme);
    icon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
});
</script>
</body>
</html>
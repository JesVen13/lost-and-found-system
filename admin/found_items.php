<?php
session_start();
include("../db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../auth/login.php");
    exit;
}

// Handle approval actions
if(isset($_POST['approval_action'])){
    $found_id = intval($_POST['found_id']);
    $action = $_POST['approval_action'];
    
    if($action == 'approve'){
        $con->query("UPDATE found_items SET approval_status='approved' WHERE found_id=$found_id");
    } elseif($action == 'reject'){
        $con->query("UPDATE found_items SET approval_status='rejected' WHERE found_id=$found_id");
    }
    
    header("Location: found_items.php");
    exit;
}

// Handle delete action
if(isset($_GET['delete']) && isset($_GET['found_id'])){
    $found_id = intval($_GET['found_id']);
    $con->query("DELETE FROM found_items WHERE found_id=$found_id");
    header("Location: found_items.php");
    exit;
}

// Fetch found items with reporter and claimer info
$found_items = $con->query("
    SELECT f.*, u.username, u.role, c.username AS claimed_by_user
    FROM found_items f
    JOIN users u ON f.reporter_id = u.user_id
    LEFT JOIN users c ON f.claimed_by = c.user_id
    ORDER BY f.created_at DESC
");

// Get statistics
$total_items = $con->query("SELECT COUNT(*) as count FROM found_items")->fetch_assoc()['count'];
$pending_approval = $con->query("SELECT COUNT(*) as count FROM found_items WHERE approval_status='pending'")->fetch_assoc()['count'];
$claimed_items = $con->query("SELECT COUNT(*) as count FROM found_items WHERE status='claimed'")->fetch_assoc()['count'];
$available_items = $con->query("SELECT COUNT(*) as count FROM found_items WHERE status='available' AND approval_status='approved'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Found Items Management - Lost & Found System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body { 
    font-family: 'Poppins', sans-serif; 
    background: #f0f2f5;
    min-height: 100vh;
}

/* Navbar */
.navbar { 
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 1rem 0;
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
    color: #5f27cd !important;
}

.nav-link {
    color: #666 !important;
    font-weight: 500;
    margin: 0 0.5rem;
    transition: all 0.3s;
}

.nav-link:hover {
    color: #5f27cd !important;
}

.btn-logout {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white !important;
    border-radius: 25px;
    padding: 0.5rem 1.5rem;
    border: none;
}

/* Main Container */
.main-container {
    max-width: 1600px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

/* Page Header */
.page-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-radius: 20px;
    padding: 2.5rem;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.page-header p {
    opacity: 0.9;
    margin: 0;
}

/* Statistics Cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s;
    border-left: 4px solid;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.stat-card.total {
    border-color: #28a745;
}

.stat-card.pending {
    border-color: #ffc107;
}

.stat-card.available {
    border-color: #17a2b8;
}

.stat-card.claimed {
    border-color: #667eea;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin-bottom: 1rem;
}

.stat-card.total .stat-icon {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.stat-card.pending .stat-icon {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: white;
}

.stat-card.available .stat-icon {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
}

.stat-card.claimed .stat-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
}

.stat-label {
    color: #666;
    font-size: 0.95rem;
    font-weight: 500;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.btn-custom {
    border-radius: 10px;
    padding: 0.7rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-secondary-custom {
    background: #6c757d;
    color: white;
}

.btn-secondary-custom:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

/* Content Card */
.content-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
    overflow: hidden;
}

.card-header-custom {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 1.25rem 1.5rem;
    font-weight: 600;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.card-body-custom {
    padding: 1.5rem;
}

/* Filter Tabs */
.filter-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.filter-tab {
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    border: 2px solid #e9ecef;
    background: white;
    color: #666;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
}

.filter-tab:hover {
    border-color: #28a745;
    color: #28a745;
}

.filter-tab.active {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border-color: #28a745;
}

/* Search Box */
.search-box {
    position: relative;
    margin-bottom: 1.5rem;
}

.search-box input {
    width: 100%;
    padding: 0.7rem 1rem 0.7rem 2.5rem;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.search-box input:focus {
    outline: none;
    border-color: #28a745;
}

.search-icon {
    position: absolute;
    left: 0.9rem;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

/* Table */
.table-container {
    overflow-x: auto;
}

.table-modern {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.table-modern thead th {
    background: #f8f9fa;
    color: #333;
    font-weight: 600;
    padding: 1rem;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.table-modern tbody td {
    padding: 1rem;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

.table-modern tbody tr {
    transition: all 0.3s;
}

.table-modern tbody tr:hover {
    background: #f0fff4;
}

.table-modern tbody tr:last-child td {
    border-bottom: none;
}

/* Item Info */
.item-icon {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    margin-right: 0.75rem;
    vertical-align: middle;
}

.item-details {
    display: inline-block;
    vertical-align: middle;
}

.item-name {
    font-weight: 600;
    color: #333;
    display: block;
    margin-bottom: 0.2rem;
}

.item-meta {
    font-size: 0.85rem;
    color: #888;
}

/* Reporter Badge */
.reporter-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    background: #f8f9fa;
    font-size: 0.9rem;
}

.reporter-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.8rem;
}

/* Claimed Badge */
.claimed-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    background: #e3f2fd;
    font-size: 0.85rem;
}

.claimed-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.75rem;
}

/* Badges */
.badge-modern {
    padding: 0.4rem 0.9rem;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.badge-available {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-claimed {
    background: #e3f2fd;
    color: #1976d2;
}

.badge-approved {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-pending {
    background: #fff3e0;
    color: #f57c00;
}

.badge-rejected {
    background: #ffebee;
    color: #c62828;
}

.badge-role {
    background: #f3e5f5;
    color: #7b1fa2;
    font-size: 0.75rem;
}

/* Action Buttons */
.btn-action {
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    border: none;
    transition: all 0.3s;
    cursor: pointer;
    margin: 0 0.2rem;
}

.btn-approve {
    background: #28a745;
    color: white;
}

.btn-approve:hover {
    background: #218838;
    transform: scale(1.05);
}

.btn-reject {
    background: #ffc107;
    color: #333;
}

.btn-reject:hover {
    background: #e0a800;
    transform: scale(1.05);
}

.btn-view {
    background: #17a2b8;
    color: white;
}

.btn-view:hover {
    background: #138496;
    transform: scale(1.05);
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-delete:hover {
    background: #c82333;
    transform: scale(1.05);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #999;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.empty-state p {
    font-size: 1.1rem;
    margin: 0;
}

/* Footer */
.footer {
    text-align: center;
    padding: 2rem;
    color: #999;
    margin-top: 3rem;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        padding: 1.5rem;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-custom {
        width: 100%;
        justify-content: center;
    }
    
    .table-modern {
        font-size: 0.85rem;
    }
    
    .filter-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
    }
}
</style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="dashboard.php">
        <i class="fas fa-box-open me-2"></i>Lost & Found
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item">
                <span class="nav-link">
                    <i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($_SESSION['username']) ?>
                </span>
            </li>
            <li class="nav-item">
                <a class="btn btn-logout" href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>
    </div>
  </div>
</nav>

<!-- Main Container -->
<div class="main-container">
    
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-treasure-chest me-3"></i>Found Items Management</h1>
        <p>View, manage, and approve all found item reports</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card total">
            <div class="stat-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-number"><?= $total_items ?></div>
            <div class="stat-label">Total Items</div>
        </div>
        
        <div class="stat-card pending">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-number"><?= $pending_approval ?></div>
            <div class="stat-label">Pending Approval</div>
        </div>
        
        <div class="stat-card available">
            <div class="stat-icon">
                <i class="fas fa-hand-holding"></i>
            </div>
            <div class="stat-number"><?= $available_items ?></div>
            <div class="stat-label">Available to Claim</div>
        </div>
        
        <div class="stat-card claimed">
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-number"><?= $claimed_items ?></div>
            <div class="stat-label">Claimed Items</div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="dashboard.php" class="btn-custom btn-secondary-custom">
            <i class="fas fa-arrow-left"></i>Back to Dashboard
        </a>
    </div>

    <!-- Found Items Table -->
    <div class="content-card">
        <div class="card-header-custom">
            <i class="fas fa-list"></i>
            <span>All Found Items</span>
        </div>
        <div class="card-body-custom">
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterItems('all')">
                    <i class="fas fa-th me-1"></i> All Items
                </button>
                <button class="filter-tab" onclick="filterItems('pending')">
                    <i class="fas fa-clock me-1"></i> Pending Approval
                </button>
                <button class="filter-tab" onclick="filterItems('approved')">
                    <i class="fas fa-check me-1"></i> Approved
                </button>
                <button class="filter-tab" onclick="filterItems('available')">
                    <i class="fas fa-hand-holding me-1"></i> Available
                </button>
                <button class="filter-tab" onclick="filterItems('claimed')">
                    <i class="fas fa-user-check me-1"></i> Claimed
                </button>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" placeholder="Search by item name, location, reporter, or claimer..." onkeyup="searchTable()">
            </div>

            <!-- Table -->
            <div class="table-container">
                <table class="table-modern" id="itemsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Details</th>
                            <th>Reporter</th>
                            <th>Date Found</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Approval</th>
                            <th>Claimed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($found_items->num_rows > 0): ?>
                            <?php while($item = $found_items->fetch_assoc()): ?>
                            <tr data-status="<?= $item['status'] ?>" data-approval="<?= $item['approval_status'] ?>">
                                <td>#<?= $item['found_id'] ?></td>
                                <td>
                                    <div class="item-icon">
                                        <i class="fas fa-gift"></i>
                                    </div>
                                    <div class="item-details">
                                        <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                                        <span class="item-meta">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?= date('M d, Y', strtotime($item['created_at'])) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="reporter-badge">
                                        <div class="reporter-avatar">
                                            <?= strtoupper(substr($item['username'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;"><?= htmlspecialchars($item['username']) ?></div>
                                            <span class="badge-modern badge-role"><?= ucfirst($item['role']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= date('M d, Y', strtotime($item['date_found'])) ?></td>
                                <td>
                                    <i class="fas fa-map-marker-alt text-success me-1"></i>
                                    <?= htmlspecialchars($item['location_found']) ?>
                                </td>
                                <td>
                                    <span class="badge-modern badge-<?= $item['status'] ?>">
                                        <i class="fas fa-<?= $item['status'] == 'claimed' ? 'user-check' : 'hand-holding' ?> me-1"></i>
                                        <?= ucfirst($item['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-modern badge-<?= $item['approval_status'] ?>">
                                        <?= ucfirst($item['approval_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($item['claimed_by_user']): ?>
                                    <div class="claimed-badge">
                                        <div class="claimed-avatar">
                                            <?= strtoupper(substr($item['claimed_by_user'], 0, 1)) ?>
                                        </div>
                                        <span style="font-weight: 600;"><?= htmlspecialchars($item['claimed_by_user']) ?></span>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($item['approval_status'] == 'pending'): ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="found_id" value="<?= $item['found_id'] ?>">
                                        <button name="approval_action" value="approve" class="btn-action btn-approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="found_id" value="<?= $item['found_id'] ?>">
                                        <button name="approval_action" value="reject" class="btn-action btn-reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <a href="view_found_item.php?found_id=<?= $item['found_id'] ?>" class="btn-action btn-view">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <a href="?delete=1&found_id=<?= $item['found_id'] ?>" 
                                       class="btn-action btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this item?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="fas fa-gift"></i>
                                        <p>No found items to display</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Footer -->
<div class="footer">
    <p>&copy; 2025 Lost & Found System. All rights reserved.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Filter items by status
function filterItems(filter) {
    const rows = document.querySelectorAll('#itemsTable tbody tr');
    const tabs = document.querySelectorAll('.filter-tab');
    
    // Update active tab
    tabs.forEach(tab => tab.classList.remove('active'));
    event.target.closest('.filter-tab').classList.add('active');
    
    rows.forEach(row => {
        if(filter === 'all') {
            row.style.display = '';
        } else if(filter === 'pending' || filter === 'approved') {
            const approval = row.getAttribute('data-approval');
            row.style.display = approval === filter ? '' : 'none';
        } else {
            const status = row.getAttribute('data-status');
            row.style.display = status === filter ? '' : 'none';
        }
    });
}

// Search table
function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('itemsTable');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length; j++) {
            const cell = cells[j];
            if (cell) {
                const textValue = cell.textContent || cell.innerText;
                if (textValue.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        rows[i].style.display = found ? '' : 'none';
    }
}
</script>

</body>
</html>
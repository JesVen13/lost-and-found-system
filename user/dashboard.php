<?php
session_start();
include("../db.php");
if(!isset($_SESSION['role']) || $_SESSION['role']!="user") {
    header("Location:../auth/login.php"); exit;
}

$user_id = $_SESSION['user_id'];
$lost_count = $con->query("SELECT COUNT(*) as cnt FROM lost_items WHERE reporter_id=$user_id")->fetch_assoc()['cnt'];
$found_count = $con->query("SELECT COUNT(*) as cnt FROM found_items WHERE reporter_id=$user_id")->fetch_assoc()['cnt'];

// Get filter and search parameters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query based on filter
if($filter == 'my_items') {
    $lost_items = $con->query("SELECT * FROM lost_items WHERE reporter_id=$user_id ORDER BY date_lost DESC");
    $found_items = $con->query("SELECT * FROM found_items WHERE reporter_id=$user_id ORDER BY date_found DESC");
} else {
    // Show all items (for claiming)
    $lost_query = "SELECT l.*, u.username as reporter_name FROM lost_items l 
                   JOIN users u ON l.reporter_id = u.user_id 
                   WHERE 1=1";
    $found_query = "SELECT f.*, u.username as reporter_name FROM found_items f 
                    JOIN users u ON f.reporter_id = u.user_id 
                    WHERE 1=1";
    
    if(!empty($search)) {
        $search_term = mysqli_real_escape_string($con, $search);
        $lost_query .= " AND (l.item_name LIKE '%$search_term%' OR l.description LIKE '%$search_term%' OR l.location_lost LIKE '%$search_term%')";
        $found_query .= " AND (f.item_name LIKE '%$search_term%' OR f.description LIKE '%$search_term%' OR f.location_found LIKE '%$search_term%')";
    }
    
    $lost_query .= " ORDER BY l.date_lost DESC";
    $found_query .= " ORDER BY f.date_found DESC";
    
    $lost_items = $con->query($lost_query);
    $found_items = $con->query($found_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lost & Found Dashboard</title>
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
    --card-bg: #ffffff;
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
    --card-bg: #2a2a2a;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background: var(--bg-secondary);
    color: var(--text-primary);
    padding: 2rem 1rem;
    min-height: 100vh;
}

.container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Header */
.header {
    background: var(--card-bg);
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-brand {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.brand-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary), var(--success));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.brand-text h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: var(--text-primary);
}

.brand-text p {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.theme-toggle {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    width: 42px;
    height: 42px;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: var(--text-primary);
    transition: all 0.2s;
}

.theme-toggle:hover {
    background: var(--border);
}

.btn-logout {
    background: var(--danger);
    color: white;
    border: none;
    padding: 0.7rem 1.5rem;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-logout:hover {
    background: #c0392b;
    color: white;
    transform: translateY(-1px);
}

.user-welcome {
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: 10px;
    border-left: 4px solid var(--primary);
}

.user-welcome p {
    margin: 0;
    font-size: 0.95rem;
    color: var(--text-secondary);
}

.user-welcome strong {
    color: var(--text-primary);
    font-weight: 600;
}

/* Stats Overview */
.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--card-bg);
    padding: 1.75rem;
    border-radius: 12px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    flex-shrink: 0;
}

.stat-card.lost .stat-icon {
    background: rgba(231, 76, 60, 0.1);
    color: var(--danger);
}

.stat-card.found .stat-icon {
    background: rgba(46, 204, 113, 0.1);
    color: var(--success);
}

.stat-info h3 {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.stat-info .number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
}

/* Quick Actions */
.quick-actions {
    margin-bottom: 2.5rem;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.action-card {
    background: var(--card-bg);
    padding: 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    transition: all 0.2s;
}

.action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.action-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.action-card.lost .action-icon {
    background: rgba(231, 76, 60, 0.1);
    color: var(--danger);
}

.action-card.found .action-icon {
    background: rgba(46, 204, 113, 0.1);
    color: var(--success);
}

.action-text h4 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.action-text p {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin: 0;
}

/* Filter Bar */
.filter-bar {
    background: var(--card-bg);
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    margin-bottom: 2rem;
}

.filter-content {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    gap: 0.5rem;
}

.filter-btn {
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg-secondary);
    color: var(--text-primary);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
}

.filter-btn:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.filter-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.search-box {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 0.6rem 1rem 0.6rem 2.5rem;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg-secondary);
    color: var(--text-primary);
    font-size: 0.9rem;
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
}

/* Section */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--text-primary);
}

/* Items Grid */
.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.item-card {
    background: var(--card-bg);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    transition: all 0.3s;
}

.item-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.item-image {
    width: 100%;
    height: 180px;
    background: var(--bg-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-image i {
    font-size: 3.5rem;
    color: var(--text-secondary);
    opacity: 0.3;
}

.item-status {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 0.35rem 0.85rem;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    backdrop-filter: blur(10px);
}

.item-status.lost {
    background: rgba(231, 76, 60, 0.9);
    color: white;
}

.item-status.found {
    background: rgba(46, 204, 113, 0.9);
    color: white;
}

.item-status.pending {
    background: rgba(243, 156, 18, 0.9);
    color: white;
}

.item-body {
    padding: 1.25rem;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 0.5rem;
}

.item-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.reporter-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    background: var(--bg-secondary);
    color: var(--text-secondary);
    white-space: nowrap;
    margin-left: 0.5rem;
}

.item-desc {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.5;
}

.item-details {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.item-details span {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.item-footer {
    display: flex;
    gap: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border);
}

.btn-action {
    flex: 1;
    padding: 0.6rem;
    border-radius: 8px;
    border: 1.5px solid;
    background: transparent;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
}

.btn-edit {
    border-color: var(--primary);
    color: var(--primary);
}

.btn-edit:hover {
    background: var(--primary);
    color: white;
}

.btn-delete {
    border-color: var(--danger);
    color: var(--danger);
}

.btn-delete:hover {
    background: var(--danger);
    color: white;
}

.btn-claim {
    border-color: var(--success);
    color: var(--success);
}

.btn-claim:hover {
    background: var(--success);
    color: white;
}

/* Empty State */
.empty-state {
    background: var(--card-bg);
    padding: 4rem 2rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: var(--bg-secondary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: var(--text-secondary);
}

.empty-state h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text-primary);
}

.empty-state p {
    color: var(--text-secondary);
    margin-bottom: 2rem;
    font-size: 1rem;
}

.empty-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-primary, .btn-success {
    padding: 0.75rem 2rem;
    border-radius: 10px;
    border: none;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.btn-primary {
    background: var(--danger);
}

.btn-primary:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.btn-success {
    background: var(--success);
}

.btn-success:hover {
    background: #27ae60;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    body {
        padding: 1rem;
    }
    
    .header {
        padding: 1.5rem;
    }
    
    .header-top {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .header-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .items-grid {
        grid-template-columns: 1fr;
    }
    
    .brand-text h1 {
        font-size: 1.25rem;
    }
    
    .filter-content {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        min-width: 100%;
    }
}
</style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="header-top">
            <div class="header-brand">
                <div class="brand-icon">
                    <i class="fas fa-search-location"></i>
                </div>
                <div class="brand-text">
                    <h1>Lost & Found System</h1>
                    <p>Track and recover lost items</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
                    <i class="fas fa-moon" id="theme-icon"></i>
                </button>
                <a href="../auth/logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        <div class="user-welcome">
            <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['username']); ?></strong> 👋</p>
        </div>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Stats Overview -->
    <div class="stats-overview">
        <div class="stat-card lost">
            <div class="stat-icon">
                <i class="fas fa-search"></i>
            </div>
            <div class="stat-info">
                <h3>My Lost Items</h3>
                <div class="number"><?= $lost_count ?></div>
            </div>
        </div>
        <div class="stat-card found">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3>My Found Items</h3>
                <div class="number"><?= $found_count ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="actions-grid">
            <a href="add_lost_items.php" class="action-card lost">
                <div class="action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="action-text">
                    <h4>Report Lost Item</h4>
                    <p>Submit a new lost item report</p>
                </div>
            </a>
            <a href="add_found_items.php" class="action-card found">
                <div class="action-icon">
                    <i class="fas fa-hand-holding"></i>
                </div>
                <div class="action-text">
                    <h4>Report Found Item</h4>
                    <p>Help someone recover their item</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-content">
            <div class="filter-group">
                <a href="?filter=all" class="filter-btn <?= $filter == 'all' ? 'active' : '' ?>">
                    <i class="fas fa-globe me-1"></i> All Items
                </a>
                <a href="?filter=my_items" class="filter-btn <?= $filter == 'my_items' ? 'active' : '' ?>">
                    <i class="fas fa-user me-1"></i> My Items
                </a>
            </div>
            <form method="GET" class="search-box">
                <i class="fas fa-search"></i>
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <input type="text" name="search" placeholder="Search items..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>
    </div>

    <!-- Items Section -->
    <div class="section-header">
        <h2 class="section-title">
            <?= $filter == 'my_items' ? 'My Items' : 'All Items' ?>
        </h2>
    </div>

    <?php 
    $all_items = [];
    
    if($lost_items->num_rows > 0) {
        $lost_items->data_seek(0);
        while($item = $lost_items->fetch_assoc()) {
            $item['type'] = 'lost';
            $item['sort_date'] = $item['date_lost'];
            $item['location'] = $item['location_lost'];
            $all_items[] = $item;
        }
    }
    
    if($found_items->num_rows > 0) {
        $found_items->data_seek(0);
        while($item = $found_items->fetch_assoc()) {
            $item['type'] = 'found';
            $item['sort_date'] = $item['date_found'];
            $item['location'] = $item['location_found'];
            $all_items[] = $item;
        }
    }
    
    usort($all_items, function($a, $b) {
        return strtotime($b['sort_date']) - strtotime($a['sort_date']);
    });
    ?>

    <?php if(count($all_items) > 0): ?>
    <div class="items-grid">
        <?php foreach($all_items as $item): ?>
        <div class="item-card">
            <div class="item-image">
                <?php if(!empty($item['image_path'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                <?php else: ?>
                    <i class="fas fa-image"></i>
                <?php endif; ?>
                <span class="item-status <?= isset($item['claim_status']) && $item['claim_status'] == 'pending_claim' ? 'pending' : $item['type'] ?>">
                    <?= isset($item['claim_status']) && $item['claim_status'] == 'pending_claim' ? 'Pending' : ucfirst($item['type']) ?>
                </span>
            </div>
            <div class="item-body">
                <div class="item-header">
                    <div class="item-title"><?= htmlspecialchars($item['item_name']) ?></div>
                    <?php if(isset($item['reporter_name']) && $filter == 'all'): ?>
                        <span class="reporter-badge">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($item['reporter_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="item-desc"><?= htmlspecialchars($item['description'] ?? 'No description provided') ?></div>
                <div class="item-details">
                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($item['location'] ?? 'Unknown') ?></span>
                    <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($item['sort_date'])) ?></span>
                </div>
                <div class="item-footer">
                    <?php if($item['reporter_id'] == $user_id): ?>
                        <!-- User's own items -->
                        <?php if($item['type'] == 'lost'): ?>
                        <a href="edit_lost_items.php?id=<?= $item['lost_id'] ?>" class="btn-action btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="delete_lost_items.php?id=<?= $item['lost_id'] ?>" class="btn-action btn-delete" onclick="return confirm('Delete this item?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                        <?php else: ?>
                        <a href="edit_found_items.php?id=<?= $item['found_id'] ?>" class="btn-action btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="delete_found_items.php?id=<?= $item['found_id'] ?>" class="btn-action btn-delete" onclick="return confirm('Delete this item?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Other users' items - show claim button -->
                        <?php 
                        $item_id = $item['type'] == 'lost' ? $item['lost_id'] : $item['found_id'];
                        $can_claim = !isset($item['claim_status']) || $item['claim_status'] != 'pending_claim';
                        ?>
                        <?php if($can_claim): ?>
                        <a href="claim_items.php?type=<?= $item['type'] ?>&id=<?= $item_id ?>" class="btn-action btn-claim" style="flex: 1;">
                            <i class="fas fa-hand-holding"></i> Claim This Item
                        </a>
                        <?php else: ?>
                        <button class="btn-action btn-claim" style="flex: 1; opacity: 0.5; cursor: not-allowed;" disabled>
                            <i class="fas fa-clock"></i> Claim Pending
                        </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fas fa-inbox"></i>
        </div>
        <h3>No Items Found</h3>
        <p>Start by reporting a lost or found item to help the community</p>
        <div class="empty-actions">
            <a href="add_lost_items.php" class="btn-primary">
                <i class="fas fa-search"></i> Report Lost Item
            </a>
            <a href="add_found_items.php" class="btn-success">
                <i class="fas fa-hand-holding"></i> Report Found Item
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
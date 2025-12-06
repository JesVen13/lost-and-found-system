<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!="admin"){
    header("Location: ../auth/login.php");
    exit;
}
include("../db.php");

// Get statistics
$total_users = $con->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
$pending_users = $con->query("SELECT COUNT(*) as cnt FROM users WHERE status='pending'")->fetch_assoc()['cnt'];
$total_lost = $con->query("SELECT COUNT(*) as cnt FROM lost_items")->fetch_assoc()['cnt'];
$total_found = $con->query("SELECT COUNT(*) as cnt FROM found_items")->fetch_assoc()['cnt'];
$approved_claims = $con->query("SELECT COUNT(*) as cnt FROM claims WHERE claim_status='approved'")->fetch_assoc()['cnt'];
$pending_claims = $con->query("SELECT COUNT(*) as cnt FROM claims WHERE claim_status='pending'")->fetch_assoc()['cnt'];
$rejected_claims = $con->query("SELECT COUNT(*) as cnt FROM claims WHERE claim_status='rejected'")->fetch_assoc()['cnt'];
$total_claims = $con->query("SELECT COUNT(*) as cnt FROM claims")->fetch_assoc()['cnt'];

// User distribution
$user_count = $con->query("SELECT COUNT(*) as cnt FROM users WHERE role='user'")->fetch_assoc()['cnt'];
$staff_count = $con->query("SELECT COUNT(*) as cnt FROM users WHERE role='staff'")->fetch_assoc()['cnt'];
$admin_count = $con->query("SELECT COUNT(*) as cnt FROM users WHERE role='admin'")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Lost & Found</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #ff8c00;
    --primary-dark: #ff4500;
    --bg-light: #fff3cd;
    --bg-gradient: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    --text-dark: #1a1a1a;
    --text-muted: #666;
    --card-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: var(--bg-gradient);
    min-height: 100vh;
    color: var(--text-dark);
}

.navbar {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    box-shadow: 0 4px 20px rgba(255, 140, 0, 0.3);
    padding: 1rem 0;
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
    color: white !important;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.navbar-brand i {
    font-size: 1.75rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: white;
}

.user-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.user-details {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.user-name {
    font-weight: 600;
    font-size: 0.95rem;
    line-height: 1.2;
}

.user-role {
    font-size: 0.75rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-logout {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 0.625rem 1.5rem;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-logout:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
    transform: translateY(-2px);
}

.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2.5rem 1.5rem;
}

.page-header {
    margin-bottom: 3rem;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-dark);
    margin-bottom: 0.5rem;
    text-shadow: 2px 2px 4px rgba(255, 69, 0, 0.1);
}

.page-subtitle {
    font-size: 1.125rem;
    color: var(--text-muted);
    font-weight: 400;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: var(--card-shadow);
    transition: all 0.3s ease;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
    border-color: var(--primary);
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1.5rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    background: linear-gradient(135deg, rgba(255, 140, 0, 0.1) 0%, rgba(255, 69, 0, 0.1) 100%);
    color: var(--primary-dark);
}

.stat-label {
    font-size: 0.8125rem;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 3rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
}

.section-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--primary-dark);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-title i {
    font-size: 1.5rem;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.action-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: var(--card-shadow);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
    border-color: var(--primary);
}

.action-icon {
    width: 70px;
    height: 70px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    flex-shrink: 0;
    box-shadow: 0 4px 15px rgba(255, 140, 0, 0.3);
}

.action-content {
    flex: 1;
}

.action-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.25rem;
}

.action-desc {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0;
}

.action-badge {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 0.35rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    margin-left: 0.5rem;
}

.info-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: var(--card-shadow);
    margin-bottom: 2rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
}

.info-item {
    text-align: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(255, 140, 0, 0.05) 0%, rgba(255, 69, 0, 0.05) 100%);
    border-radius: 12px;
}

.info-item-icon {
    width: 50px;
    height: 50px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.info-item-label {
    font-size: 0.875rem;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.info-item-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-dark);
}

.table-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: var(--card-shadow);
    margin-bottom: 2rem;
}

.table {
    margin: 0;
}

.table thead th {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    font-weight: 600;
    border: none;
    padding: 1rem;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table tbody td {
    padding: 1rem;
    border: none;
    vertical-align: middle;
    border-bottom: 1px solid #f0f0f0;
}

.table tbody tr:hover {
    background: rgba(255, 140, 0, 0.05);
}

.badge-role {
    padding: 0.35rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-user {
    background: #6c757d;
    color: white;
}

.badge-staff {
    background: #ffc107;
    color: #212529;
}

.badge-admin {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.claims-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.claim-stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    text-align: center;
    transition: all 0.3s ease;
}

.claim-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.claim-stat-icon {
    width: 50px;
    height: 50px;
    margin: 0 auto 1rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.claim-stat-icon.pending {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.claim-stat-icon.approved {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.claim-stat-icon.rejected {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.claim-stat-icon.total {
    background: linear-gradient(135deg, rgba(255, 140, 0, 0.1) 0%, rgba(255, 69, 0, 0.1) 100%);
    color: var(--primary-dark);
}

.claim-stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.25rem;
}

.claim-stat-label {
    font-size: 0.8125rem;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 1.5rem 1rem;
    }

    .page-title {
        font-size: 2rem;
    }

    .stats-grid,
    .quick-actions {
        grid-template-columns: 1fr;
    }

    .stat-value {
        font-size: 2.5rem;
    }

    .user-details {
        display: none;
    }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center w-100">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-crown"></i>
                Admin Dashboard
            </a>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="user-role">Administrator</span>
                </div>
                <a href="../auth/logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="dashboard-container">
    <div class="page-header">
        <h1 class="page-title">Welcome, Admin</h1>
        <p class="page-subtitle">Manage users and monitor system activity</p>
    </div>

    <!-- System Overview Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?= $total_users ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Pending Users</div>
                    <div class="stat-value"><?= $pending_users ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-user-clock"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Total Lost Items</div>
                    <div class="stat-value"><?= $total_lost ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-box-open"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Total Found Items</div>
                    <div class="stat-value"><?= $total_found ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Claims Overview Section -->
    <h2 class="section-title">
        <i class="fas fa-hand-holding"></i>
        Claims Overview
    </h2>
    <div class="claims-stats-grid">
        <div class="claim-stat-card">
            <div class="claim-stat-icon total">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="claim-stat-value"><?= $total_claims ?></div>
            <div class="claim-stat-label">Total Claims</div>
        </div>
        <div class="claim-stat-card">
            <div class="claim-stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="claim-stat-value"><?= $pending_claims ?></div>
            <div class="claim-stat-label">Pending</div>
        </div>
        <div class="claim-stat-card">
            <div class="claim-stat-icon approved">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="claim-stat-value"><?= $approved_claims ?></div>
            <div class="claim-stat-label">Approved</div>
        </div>
        <div class="claim-stat-card">
            <div class="claim-stat-icon rejected">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="claim-stat-value"><?= $rejected_claims ?></div>
            <div class="claim-stat-label">Rejected</div>
        </div>
    </div>

    <!-- User Distribution -->
    <h2 class="section-title">
        <i class="fas fa-chart-pie"></i>
        User Distribution
    </h2>
    <div class="info-card">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-item-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="info-item-label">Regular Users</div>
                <div class="info-item-value"><?= $user_count ?></div>
            </div>
            <div class="info-item">
                <div class="info-item-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="info-item-label">Staff Members</div>
                <div class="info-item-value"><?= $staff_count ?></div>
            </div>
            <div class="info-item">
                <div class="info-item-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="info-item-label">Administrators</div>
                <div class="info-item-value"><?= $admin_count ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <h2 class="section-title">
        <i class="fas fa-bolt"></i>
        Quick Actions
    </h2>
    <div class="quick-actions">
        <a href="manage_users.php" class="action-card">
            <div class="action-icon">
                <i class="fas fa-users-cog"></i>
            </div>
            <div class="action-content">
                <h3 class="action-title">
                    Manage Users
                    <?php if($pending_users > 0): ?>
                    <span class="action-badge"><?= $pending_users ?></span>
                    <?php endif; ?>
                </h3>
                <p class="action-desc">View, approve, and manage all users</p>
            </div>
        </a>

        <a href="lost_items.php?type=lost" class="action-card">
            <div class="action-icon">
                <i class="fas fa-box-open"></i>
            </div>
            <div class="action-content">
                <h3 class="action-title">View Lost Items</h3>
                <p class="action-desc">Browse all reported lost items</p>
            </div>
        </a>

        <a href="found_items.php?type=found" class="action-card">
            <div class="action-icon">
                <i class="fas fa-search-plus"></i>
            </div>
            <div class="action-content">
                <h3 class="action-title">View Found Items</h3>
                <p class="action-desc">Browse all reported found items</p>
            </div>
        </a>

        <a href="review_claims.php" class="action-card">
            <div class="action-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="action-content">
                <h3 class="action-title">
                    View All Claims
                    <?php if($pending_claims > 0): ?>
                    <span class="action-badge"><?= $pending_claims ?> pending</span>
                    <?php endif; ?>
                </h3>
                <p class="action-desc">Monitor all claim submissions and staff reviews</p>
            </div>
        </a>
    </div>

    <!-- Recent Claims Activity -->
    <h2 class="section-title">
        <i class="fas fa-history"></i>
        Recent Claims Activity
    </h2>
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Claim ID</th>
                        <th>Item Name</th>
                        <th>Type</th>
                        <th>Claimed By</th>
                        <th>Status</th>
                        <th>Reviewed By</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent_claims_query = "
                        SELECT 
                            c.*,
                            u.username as claimer_name,
                            CASE 
                                WHEN c.item_type = 'lost' THEN l.item_name
                                WHEN c.item_type = 'found' THEN f.item_name
                            END as item_name,
                            reviewer.username as reviewed_by_name
                        FROM claims c
                        JOIN users u ON c.claimer_id = u.user_id
                        LEFT JOIN lost_items l ON c.item_id = l.lost_id AND c.item_type = 'lost'
                        LEFT JOIN found_items f ON c.item_id = f.found_id AND c.item_type = 'found'
                        LEFT JOIN users reviewer ON c.reviewed_by = reviewer.user_id
                        ORDER BY c.claimed_at DESC
                        LIMIT 10
                    ";
                    $recent_claims = $con->query($recent_claims_query);
                    
                    if($recent_claims->num_rows > 0) {
                        while($claim = $recent_claims->fetch_assoc()) {
                            $status_class = '';
                            $status_text = '';
                            switch($claim['claim_status']) {
                                case 'pending':
                                    $status_class = 'bg-warning text-dark';
                                    $status_text = 'Pending';
                                    break;
                                case 'approved':
                                    $status_class = 'bg-success';
                                    $status_text = 'Approved';
                                    break;
                                case 'rejected':
                                    $status_class = 'bg-danger';
                                    $status_text = 'Rejected';
                                    break;
                            }
                            
                            $type_badge = $claim['item_type'] == 'lost' 
                                ? '<span class="badge bg-danger">Lost</span>' 
                                : '<span class="badge bg-success">Found</span>';
                            
                            echo "<tr>
                                <td><strong>#{$claim['claim_id']}</strong></td>
                                <td>" . htmlspecialchars($claim['item_name']) . "</td>
                                <td>{$type_badge}</td>
                                <td>" . htmlspecialchars($claim['claimer_name']) . "</td>
                                <td><span class='badge {$status_class}'>{$status_text}</span></td>
                                <td>" . ($claim['reviewed_by_name'] ? htmlspecialchars($claim['reviewed_by_name']) : '<em class=\"text-muted\">Not reviewed</em>') . "</td>
                                <td>" . date('M d, Y', strtotime($claim['claimed_at'])) . "</td>
                                <td>
                                    <a href='review_claims.php?claim_id={$claim['claim_id']}' class='btn btn-sm btn-primary'>
                                        <i class='fas fa-eye'></i> View
                                    </a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='empty-state'>
                            <i class='fas fa-inbox'></i>
                            <p class='mb-0 mt-2'>No claims submitted yet</p>
                        </td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Pending Users -->
    <h2 class="section-title">
        <i class="fas fa-user-clock"></i>
        Recent Pending User Approvals
    </h2>
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $pending_users_query = $con->query("SELECT * FROM users WHERE status='pending' ORDER BY created_at DESC LIMIT 5");
                    if($pending_users_query->num_rows > 0) {
                        while($user = $pending_users_query->fetch_assoc()) {
                            $role_class = $user['role']=='admin' ? 'badge-admin' : ($user['role']=='staff' ? 'badge-staff' : 'badge-user');
                            echo "<tr>
                                <td><strong>#{$user['user_id']}</strong></td>
                                <td>{$user['username']}</td>
                                <td>{$user['email']}</td>
                                <td><span class='badge-role {$role_class}'>{$user['role']}</span></td>
                                <td><span class='badge bg-warning text-dark'>Pending</span></td>
                                <td>".date('M d, Y', strtotime($user['created_at']))."</td>
                                <td>
                                    <a href='manage_users.php' class='btn btn-sm btn-primary'>
                                        <i class='fas fa-eye'></i> Review
                                    </a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='empty-state'>
                            <i class='fas fa-check-circle'></i>
                            <p class='mb-0 mt-2'>No pending user approvals</p>
                        </td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
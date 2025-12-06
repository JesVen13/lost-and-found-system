<?php
session_start();
include("../db.php");
if(!isset($_SESSION['role']) || $_SESSION['role']!="staff") {
    header("Location:../auth/login.php"); exit;
}

$type = isset($_GET['type']) && in_array($_GET['type'], ['lost','found']) ? $_GET['type'] : 'lost';

// Handle Approve/Reject
if(isset($_GET['action'], $_GET['id'])){
    $id = intval($_GET['id']);
    if($_GET['action']=="approve") $con->query("UPDATE {$type}_items SET approval_status='approved' WHERE {$type}_id=$id");
    if($_GET['action']=="reject") $con->query("UPDATE {$type}_items SET approval_status='rejected' WHERE {$type}_id=$id");
    header("Location:manage_items.php?type={$type}"); exit;
}

// Fetch pending items
$items = $con->query("SELECT * FROM {$type}_items WHERE approval_status='pending' ORDER BY date_{$type} DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= ucfirst($type) ?> Items - Staff</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: #fafafa;
    color: #1a1a1a;
    line-height: 1.6;
}

.navbar {
    background: #ffffff;
    border-bottom: 1px solid #e5e5e5;
    padding: 1.25rem 0;
}

.navbar-brand {
    font-weight: 600;
    font-size: 1.25rem;
    color: #1a1a1a;
}

.btn-logout {
    background: transparent;
    border: 1px solid #e5e5e5;
    color: #666;
    padding: 0.5rem 1.5rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-logout:hover {
    background: #f5f5f5;
    border-color: #d4d4d4;
    color: #1a1a1a;
}

.content-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 3rem 1.5rem;
}

.page-header {
    margin-bottom: 2rem;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #737373;
    margin-bottom: 1rem;
}

.breadcrumb a {
    color: #737373;
    text-decoration: none;
    transition: color 0.2s ease;
}

.breadcrumb a:hover {
    color: #1a1a1a;
}

.breadcrumb-separator {
    color: #d4d4d4;
}

.page-title-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
}

.page-subtitle {
    font-size: 0.9375rem;
    color: #737373;
    margin-top: 0.25rem;
}

.btn-back {
    background: transparent;
    border: 1px solid #e5e5e5;
    color: #666;
    padding: 0.5rem 1.25rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-back:hover {
    background: #f5f5f5;
    border-color: #d4d4d4;
    color: #1a1a1a;
}

.table-card {
    background: #ffffff;
    border: 1px solid #e5e5e5;
    border-radius: 12px;
    overflow: hidden;
}

.table-card-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e5e5;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-card-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
}

.item-count {
    font-size: 0.875rem;
    color: #737373;
    background: #f5f5f5;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
}

.table-responsive {
    overflow-x: auto;
}

.custom-table {
    width: 100%;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.custom-table thead {
    background: #fafafa;
}

.custom-table th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #737373;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #e5e5e5;
}

.custom-table td {
    padding: 1rem 1.5rem;
    font-size: 0.9375rem;
    color: #1a1a1a;
    border-bottom: 1px solid #f5f5f5;
}

.custom-table tbody tr {
    transition: background-color 0.2s ease;
}

.custom-table tbody tr:hover {
    background: #fafafa;
}

.custom-table tbody tr:last-child td {
    border-bottom: none;
}

.item-id {
    font-weight: 600;
    color: #737373;
}

.item-name {
    font-weight: 500;
    color: #1a1a1a;
}

.reporter-name {
    color: #666;
}

.color-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #f5f5f5;
    border-radius: 6px;
    font-size: 0.8125rem;
    color: #666;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-approve {
    background: #f0fdf4;
    color: #059669;
    border: 1px solid #d1fae5;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}

.btn-approve:hover {
    background: #059669;
    color: #fff;
    border-color: #059669;
}

.btn-reject {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}

.btn-reject:hover {
    background: #dc2626;
    color: #fff;
    border-color: #dc2626;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state-icon {
    width: 64px;
    height: 64px;
    background: #f5f5f5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 1.5rem;
    color: #d4d4d4;
}

.empty-state-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 0.5rem;
}

.empty-state-text {
    font-size: 0.9375rem;
    color: #737373;
}

@media (max-width: 768px) {
    .content-wrapper {
        padding: 2rem 1rem;
    }
    
    .page-title-section {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .custom-table th,
    .custom-table td {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
    
    .action-buttons {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-approve,
    .btn-reject {
        width: 100%;
        justify-content: center;
    }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center w-100">
            <span class="navbar-brand">Lost & Found System</span>
            <a href="../auth/logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="content-wrapper">
    <div class="page-header">
        <div class="breadcrumb">
            <a href="dashboard.php">Dashboard</a>
            <span class="breadcrumb-separator">/</span>
            <span><?= ucfirst($type) ?> Items</span>
        </div>
        
        <div class="page-title-section">
            <div>
                <h1 class="page-title">Pending <?= ucfirst($type) ?> Items</h1>
                <p class="page-subtitle">Review and approve pending submissions</p>
            </div>
            <a href="dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <h2 class="table-card-title"><?= ucfirst($type) ?> Items Awaiting Review</h2>
            <span class="item-count"><?= $items->num_rows ?> items</span>
        </div>
        
        <?php if($items->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item Name</th>
                        <th>Reporter</th>
                        <th>Date <?= ucfirst($type) ?></th>
                        <th>Color</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                while($row = $items->fetch_assoc()){
                    $reporter = $con->query("SELECT username FROM users WHERE user_id=".$row['reporter_id'])->fetch_assoc()['username'];
                    $date_col = $type == 'lost' ? $row['date_lost'] : $row['date_found'];
                    echo "<tr>
                        <td><span class='item-id'>#{$row["{$type}_id"]}</span></td>
                        <td><span class='item-name'>{$row['item_name']}</span></td>
                        <td><span class='reporter-name'>{$reporter}</span></td>
                        <td>{$date_col}</td>
                        <td><span class='color-badge'>{$row['color']}</span></td>
                        <td>
                            <div class='action-buttons'>
                                <a href='?type={$type}&action=approve&id={$row["{$type}_id"]}' class='btn-approve'>
                                    <i class='fas fa-check'></i> Approve
                                </a>
                                <a href='?type={$type}&action=reject&id={$row["{$type}_id"]}' class='btn-reject'>
                                    <i class='fas fa-times'></i> Reject
                                </a>
                            </div>
                        </td>
                    </tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-inbox"></i>
            </div>
            <h3 class="empty-state-title">No Pending Items</h3>
            <p class="empty-state-text">All <?= $type ?> items have been reviewed</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
include("../db.php");
if(!isset($_SESSION['role']) || $_SESSION['role']!="staff") {
    header("Location:../auth/login.php"); exit;
}

// Count pending items
$pending_lost = $con->query("SELECT COUNT(*) as cnt FROM lost_items WHERE approval_status='pending'")->fetch_assoc()['cnt'];
$pending_found = $con->query("SELECT COUNT(*) as cnt FROM found_items WHERE approval_status='pending'")->fetch_assoc()['cnt'];
$pending_claims = $con->query("SELECT COUNT(*) as cnt FROM claims WHERE claim_status='pending'")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    color: #1a1a1a;
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
    color: #667eea;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.navbar-brand i {
    font-size: 1.5rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
}

.user-details {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.user-name {
    font-weight: 600;
    font-size: 0.875rem;
    color: #1a1a1a;
    line-height: 1.2;
}

.user-role {
    font-size: 0.75rem;
    color: #737373;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-logout {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
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
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-logout:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    color: white;
}

.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 3rem 1.5rem;
}

.header-section {
    margin-bottom: 3rem;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.page-subtitle {
    font-size: 1.125rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 300;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    padding: 2.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.stat-card.lost::before {
    background: linear-gradient(90deg, #f43f5e 0%, #dc2626 100%);
}

.stat-card.found::before {
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.stat-card.claims::before {
    background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.stat-card:hover::before {
    opacity: 1;
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}

.stat-info {
    flex: 1;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.stat-card.lost .stat-icon {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    color: #dc2626;
}

.stat-card.found .stat-icon {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    color: #059669;
}

.stat-card.claims .stat-icon {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706;
}

.stat-label {
    font-size: 0.8125rem;
    color: #737373;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.5rem;
}

.stat-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1a1a1a;
}

.stat-value {
    font-size: 3.5rem;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1;
    margin-bottom: 2rem;
}

.stat-card.lost .stat-value {
    background: linear-gradient(135deg, #f43f5e 0%, #dc2626 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-card.found .stat-value {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-card.claims .stat-value {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-action {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.75rem;
    background: linear-gradient(135deg, #f5f5f5 0%, #e5e5e5 100%);
    border: none;
    border-radius: 12px;
    color: #1a1a1a;
    font-size: 0.9375rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.stat-card.lost .stat-action {
    background: linear-gradient(135deg, #f43f5e 0%, #dc2626 100%);
    color: white;
}

.stat-card.found .stat-action {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.stat-card.claims .stat-action {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
}

.stat-action:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    color: white;
}

.stat-action i {
    font-size: 0.875rem;
    transition: transform 0.3s ease;
}

.stat-action:hover i {
    transform: translateX(4px);
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 2rem 1rem;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .page-subtitle {
        font-size: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .stat-card {
        padding: 2rem;
    }
    
    .stat-value {
        font-size: 2.75rem;
    }
    
    .user-details {
        display: none;
    }
}

@media (max-width: 576px) {
    .user-avatar {
        width: 36px;
        height: 36px;
        font-size: 0.75rem;
    }
    
    .btn-logout {
        padding: 0.5rem 1rem;
        font-size: 0.8125rem;
    }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center w-100">
            <span class="navbar-brand">
                <i class="fas fa-shield-alt"></i>
                Lost & Found System
            </span>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="user-role">Staff Member</span>
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
    <div class="header-section">
        <h1 class="page-title">Staff Dashboard</h1>
        <p class="page-subtitle">Review and manage pending submissions and claims</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card lost">
            <div class="stat-header">
                <div class="stat-info">
                    <div class="stat-label">Pending Review</div>
                    <div class="stat-title">Lost Items</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-box-open"></i>
                </div>
            </div>
            <div class="stat-value"><?= $pending_lost ?></div>
            <a href="manage_items.php?type=lost" class="stat-action">
                <span>View Lost Items</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="stat-card found">
            <div class="stat-header">
                <div class="stat-info">
                    <div class="stat-label">Pending Review</div>
                    <div class="stat-title">Found Items</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-value"><?= $pending_found ?></div>
            <a href="manage_items.php?type=found" class="stat-action">
                <span>View Found Items</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="stat-card claims">
            <div class="stat-header">
                <div class="stat-info">
                    <div class="stat-label">Pending Review</div>
                    <div class="stat-title">Item Claims</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-hand-holding"></i>
                </div>
            </div>
            <div class="stat-value"><?= $pending_claims ?></div>
            <a href="review_claims.php" class="stat-action">
                <span>Review Claims</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
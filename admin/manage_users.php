<?php
session_start();
include("../db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../auth/login.php");
    exit;
}

// Handle Approve/Reject Actions
if(isset($_POST['action'])){
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if($action == "approve" && $user_id){
        $con->query("UPDATE users SET status='approved' WHERE user_id=$user_id AND role='staff'");
    }

    if($action == "reject" && $user_id){
        $con->query("UPDATE users SET status='rejected' WHERE user_id=$user_id AND role='staff'");
    }

    header("Location: manage_users.php");
    exit;
}

// Handle Delete Action
if(isset($_GET['delete']) && isset($_GET['user_id'])){
    $user_id = intval($_GET['user_id']);
    $con->query("DELETE FROM users WHERE user_id=$user_id");
    header("Location: manage_users.php");
    exit;
}

// Fetch pending staff
$pending_staff = $con->query("SELECT * FROM users WHERE role='staff' AND status='pending' ORDER BY created_at DESC");

// Fetch all approved/rejected users and staff
$all_users = $con->query("SELECT * FROM users WHERE NOT (role='staff' AND status='pending') ORDER BY created_at DESC");

// Get statistics
$total_users = $con->query("SELECT COUNT(*) as count FROM users WHERE role='user'")->fetch_assoc()['count'];
$total_staff = $con->query("SELECT COUNT(*) as count FROM users WHERE role='staff' AND status='approved'")->fetch_assoc()['count'];
$pending_count = $con->query("SELECT COUNT(*) as count FROM users WHERE role='staff' AND status='pending'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management - Lost & Found System</title>
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
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

/* Page Header */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 2.5rem;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
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

.stat-card.users {
    border-color: #667eea;
}

.stat-card.staff {
    border-color: #28a745;
}

.stat-card.pending {
    border-color: #ffc107;
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

.stat-card.users .stat-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-card.staff .stat-icon {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.stat-card.pending .stat-icon {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
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

.btn-primary-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-secondary-custom {
    background: #6c757d;
    color: white;
}

.btn-secondary-custom:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

/* Cards */
.content-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
    overflow: hidden;
}

.card-header-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

/* Tables */
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
    background: #f8f9ff;
}

.table-modern tbody tr:last-child td {
    border-bottom: none;
}

/* User Avatar */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    margin-right: 0.75rem;
    vertical-align: middle;
}

.user-info {
    display: inline-block;
    vertical-align: middle;
}

.user-name {
    font-weight: 600;
    color: #333;
    text-decoration: none;
    display: block;
    transition: color 0.3s;
}

.user-name:hover {
    color: #667eea;
}

.user-email {
    font-size: 0.85rem;
    color: #888;
}

/* Badges */
.badge-modern {
    padding: 0.4rem 0.9rem;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-role {
    background: #e3f2fd;
    color: #1976d2;
}

.badge-approved {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-rejected {
    background: #ffebee;
    color: #c62828;
}

.badge-pending {
    background: #fff3e0;
    color: #f57c00;
}

/* Action Buttons in Table */
.btn-action {
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    border: none;
    transition: all 0.3s;
    cursor: pointer;
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

.btn-edit {
    background: #667eea;
    color: white;
}

.btn-edit:hover {
    background: #5568d3;
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

/* Search & Filter */
.search-filter-container {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 250px;
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
    border-color: #667eea;
}

.search-icon {
    position: absolute;
    left: 0.9rem;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
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
    
    .user-avatar {
        width: 35px;
        height: 35px;
    }
}

/* Footer */
.footer {
    text-align: center;
    padding: 2rem;
    color: #999;
    margin-top: 3rem;
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
        <h1><i class="fas fa-users-cog me-3"></i>User Management</h1>
        <p>Manage all users, staff members, and pending approvals in one place</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card users">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?= $total_users ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        
        <div class="stat-card staff">
            <div class="stat-icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-number"><?= $total_staff ?></div>
            <div class="stat-label">Active Staff</div>
        </div>
        
        <div class="stat-card pending">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-number"><?= $pending_count ?></div>
            <div class="stat-label">Pending Approvals</div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="dashboard.php" class="btn-custom btn-secondary-custom">
            <i class="fas fa-arrow-left"></i>Back to Dashboard
        </a>
        <a href="add_user.php" class="btn-custom btn-primary-custom">
            <i class="fas fa-user-plus"></i>Add New User
        </a>
    </div>

    <!-- Pending Staff Approvals -->
    <?php if($pending_count > 0): ?>
    <div class="content-card">
        <div class="card-header-custom">
            <i class="fas fa-hourglass-half"></i>
            <span>Pending Staff Approvals (<?= $pending_count ?>)</span>
        </div>
        <div class="card-body-custom">
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User Details</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $pending_staff->data_seek(0); while($u = $pending_staff->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $u['user_id'] ?></td>
                            <td>
                                <span class="user-avatar"><?= strtoupper(substr($u['username'], 0, 1)) ?></span>
                                <div class="user-info">
                                    <a href="edit_user.php?user_id=<?= $u['user_id'] ?>" class="user-name">
                                        <?= htmlspecialchars($u['username']) ?>
                                    </a>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <a href="edit_user.php?user_id=<?= $u['user_id'] ?>" class="btn-action btn-edit me-1">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    <button name="action" value="approve" class="btn-action btn-approve">
                                        <i class="fas fa-check me-1"></i>Approve
                                    </button>
                                </form>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    <button name="action" value="reject" class="btn-action btn-reject">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- All Users & Staff -->
    <div class="content-card">
        <div class="card-header-custom">
            <i class="fas fa-address-book"></i>
            <span>All Users & Staff</span>
        </div>
        <div class="card-body-custom">
            <!-- Search & Filter -->
            <div class="search-filter-container">
                <div class="search-box" style="position: relative;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Search by name or email..." onkeyup="filterTable()">
                </div>
            </div>

            <div class="table-container">
                <table class="table-modern" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User Details</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($u = $all_users->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $u['user_id'] ?></td>
                            <td>
                                <span class="user-avatar"><?= strtoupper(substr($u['username'], 0, 1)) ?></span>
                                <div class="user-info">
                                    <a href="edit_user.php?user_id=<?= $u['user_id'] ?>" class="user-name">
                                        <?= htmlspecialchars($u['username']) ?>
                                    </a>
                                </div>
                            </td>
                            <td class="user-email"><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge-modern badge-role">
                                    <i class="fas fa-<?= $u['role'] == 'admin' ? 'crown' : ($u['role'] == 'staff' ? 'user-tie' : 'user') ?> me-1"></i>
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-modern badge-<?= $u['status'] ?>">
                                    <?= ucfirst($u['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit_user.php?user_id=<?= $u['user_id'] ?>" class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if($u['role'] != 'admin'): ?>
                                <a href="?delete=1&user_id=<?= $u['user_id'] ?>" 
                                   class="btn-action btn-delete" 
                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
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
function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('usersTable');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const username = rows[i].getElementsByClassName('user-name')[0];
        const email = rows[i].getElementsByClassName('user-email')[0];
        
        if (username || email) {
            const usernameText = username ? username.textContent || username.innerText : '';
            const emailText = email ? email.textContent || email.innerText : '';
            
            if (usernameText.toLowerCase().indexOf(filter) > -1 || 
                emailText.toLowerCase().indexOf(filter) > -1) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
}
</script>

</body>
</html>
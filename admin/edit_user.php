<?php
session_start();
include("../db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../auth/login.php");
    exit;
}

$user_id = intval($_GET['user_id'] ?? 0);

$user = $con->query("SELECT * FROM users WHERE user_id=$user_id")->fetch_assoc();
if(!$user){
    header("Location: manage_users.php");
    exit;
}

$message = '';
$message_type = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $new_username = $con->real_escape_string($_POST['username']);
    $new_email = $con->real_escape_string($_POST['email']);
    $new_role = $con->real_escape_string($_POST['role']);
    $new_status = $con->real_escape_string($_POST['status']);
    $new_password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    $sql = "UPDATE users SET username='$new_username', email='$new_email', role='$new_role', status='$new_status'";
    if($new_password) $sql .= ", password='$new_password'";
    $sql .= " WHERE user_id=$user_id";
    
    if($con->query($sql)){
        $message = 'User updated successfully!';
        $message_type = 'success';
        // Refresh user data
        $user = $con->query("SELECT * FROM users WHERE user_id=$user_id")->fetch_assoc();
    } else {
        $message = 'Error updating user.';
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit User - Lost & Found System</title>
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
    padding: 2rem 0;
}

/* Navbar */
.navbar {
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 1rem 0;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
    color: #5f27cd !important;
}

.nav-link {
    color: #666 !important;
    font-weight: 500;
    transition: all 0.3s;
}

.nav-link:hover {
    color: #5f27cd !important;
}

/* Main Content */
.main-container {
    max-width: 800px;
    margin: 100px auto 2rem;
    padding: 0 1.5rem;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 2rem;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.page-header h1 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.page-header p {
    opacity: 0.9;
    margin: 0;
    font-size: 0.95rem;
}

/* Card */
.edit-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-body-custom {
    padding: 2rem;
}

/* User Avatar Section */
.user-avatar-section {
    text-align: center;
    padding: 2rem 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.user-avatar-large {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 600;
    border: 4px solid rgba(255,255,255,0.3);
    margin-bottom: 1rem;
}

.user-id-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Form Styles */
.form-section {
    margin-bottom: 2rem;
}

.form-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-label {
    font-weight: 500;
    color: #555;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-control, .form-select {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 0.7rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.1);
    outline: none;
}

.input-group {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    z-index: 10;
}

.form-control.with-icon {
    padding-left: 2.8rem;
}

/* Password Toggle */
.password-toggle {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #999;
    z-index: 10;
}

.password-toggle:hover {
    color: #667eea;
}

/* Buttons */
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

.btn-danger-custom {
    background: #dc3545;
    color: white;
}

.btn-danger-custom:hover {
    background: #c82333;
    transform: translateY(-2px);
}

.button-group {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}

/* Alert */
.alert-custom {
    border-radius: 10px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    border: none;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: #d4edda;
    color: #155724;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
}

/* Info Box */
.info-box {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.info-box i {
    color: #2196f3;
    margin-right: 0.5rem;
}

/* Badges */
.role-badge {
    display: inline-block;
    padding: 0.4rem 0.9rem;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.85rem;
    margin-left: 0.5rem;
}

.role-admin {
    background: #e3f2fd;
    color: #1976d2;
}

.role-staff {
    background: #f3e5f5;
    color: #7b1fa2;
}

.role-user {
    background: #e8f5e9;
    color: #388e3c;
}

/* Responsive */
@media (max-width: 768px) {
    .main-container {
        margin-top: 80px;
    }
    
    .page-header {
        padding: 1.5rem;
    }
    
    .page-header h1 {
        font-size: 1.4rem;
    }
    
    .card-body-custom {
        padding: 1.5rem;
    }
    
    .button-group {
        flex-direction: column;
    }
    
    .btn-custom {
        width: 100%;
        justify-content: center;
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
    <div class="ms-auto">
        <a href="manage_users.php" class="nav-link d-inline-block">
            <i class="fas fa-arrow-left me-2"></i>Back to Users
        </a>
    </div>
  </div>
</nav>

<!-- Main Container -->
<div class="main-container">
    
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-user-edit me-3"></i>Edit User Profile</h1>
        <p>Update user information, role, and permissions</p>
    </div>

    <?php if($message): ?>
    <div class="alert-custom alert-<?= $message_type ?>">
        <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <span><?= $message ?></span>
    </div>
    <?php endif; ?>

    <!-- Edit Card -->
    <div class="edit-card">
        <!-- User Avatar Section -->
        <div class="user-avatar-section">
            <div class="user-avatar-large">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <div>
                <h3><?= htmlspecialchars($user['username']) ?></h3>
                <span class="user-id-badge">ID: #<?= $user['user_id'] ?></span>
            </div>
        </div>

        <!-- Form Section -->
        <div class="card-body-custom">
            <form method="post" id="editUserForm">
                
                <!-- Account Information -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-user-circle"></i>
                        <span>Account Information</span>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <div class="input-group">
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($user['username']) ?>" 
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <div class="input-group">
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($user['email']) ?>" 
                                   required>
                        </div>
                    </div>
                </div>

                <!-- Role & Status -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-shield-alt"></i>
                        <span>Role & Status</span>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">
                                <i class="fas fa-user-tag"></i> User Role
                            </label>
                            <select id="role" name="role" class="form-select" required>
                                <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>User</option>
                                <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">
                                <i class="fas fa-flag"></i> Account Status
                            </label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="approved" <?= $user['status'] == 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="pending" <?= $user['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="rejected" <?= $user['status'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Security -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-lock"></i>
                        <span>Security Settings</span>
                    </div>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <span>Leave the password field blank to keep the current password unchanged.</span>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-key"></i> New Password (Optional)
                        </label>
                        <div class="input-group" style="position: relative;">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="Enter new password">
                            <span class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </span>
                        </div>
                        <small class="text-muted">Minimum 6 characters recommended</small>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="button-group">
                    <button type="submit" class="btn-custom btn-primary-custom">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="manage_users.php" class="btn-custom btn-secondary-custom">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <?php if($user['role'] != 'admin'): ?>
                    <a href="manage_users.php?delete=1&user_id=<?= $user['user_id'] ?>" 
                       class="btn-custom btn-danger-custom ms-auto"
                       onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                        <i class="fas fa-trash"></i> Delete User
                    </a>
                    <?php endif; ?>
                </div>

            </form>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Form validation
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    
    if (password && password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long.');
        return false;
    }
});
</script>

</body>
</html>
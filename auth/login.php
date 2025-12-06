<?php
session_start();
include("../db.php");
$error = "";

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    // Validation
    if(empty($identifier) || empty($password)){
        $error = "Please fill in all fields.";
    } else {
        $stmt = $con->prepare("SELECT * FROM users WHERE username=? OR email=? LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows == 1){
            $user = $result->fetch_assoc();
            
            // Check account status
            if($user['status'] == "pending"){
                $error = "Your account is pending approval. Please wait for admin approval.";
            } elseif($user['status'] == "rejected"){
                $error = "Your account has been rejected. Please contact the administrator.";
            } elseif(password_verify($password, $user['password'])){
                // Successful login
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on role
                if($user['role'] == "admin"){
                    header("Location: ../admin/dashboard.php");
                } elseif($user['role'] == "staff"){
                    header("Location: ../staff/dashboard.php");
                } else {
                    header("Location: ../user/dashboard.php");
                }
                exit;
            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "Account not found. Please check your username/email or <a href='register.php' style='color: #667eea; font-weight: 600;'>register here</a>.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Lost & Found System</title>
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
}

.login-container {
    max-width: 450px;
    width: 100%;
}

.login-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    animation: slideUp 0.5s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card-header-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2.5rem 2rem;
    text-align: center;
    color: white;
}

.logo-icon {
    width: 70px;
    height: 70px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2rem;
}

.card-header-custom h2 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.card-header-custom p {
    opacity: 0.9;
    margin: 0;
    font-size: 0.95rem;
}

.card-body-custom {
    padding: 2rem;
}

/* Alert Styles */
.alert-custom {
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    border: none;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.alert-danger {
    background: #fee;
    color: #c33;
}

/* Form Styles */
.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    font-weight: 500;
    color: #555;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 1rem;
    z-index: 10;
}

.form-control-custom {
    width: 100%;
    padding: 0.8rem 1rem 0.8rem 2.8rem;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 0.95rem;
    transition: all 0.3s;
    background: #f8f9fa;
}

.form-control-custom:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.1);
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
    font-size: 1rem;
}

.password-toggle:hover {
    color: #667eea;
}

/* Remember Me */
.remember-forgot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #666;
}

.remember-me input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.forgot-link {
    color: #667eea;
    font-size: 0.9rem;
    text-decoration: none;
    font-weight: 500;
}

.forgot-link:hover {
    color: #5568d3;
    text-decoration: underline;
}

/* Button */
.btn-login {
    width: 100%;
    padding: 0.9rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-login:active {
    transform: translateY(0);
}

/* Divider */
.divider {
    display: flex;
    align-items: center;
    margin: 1.5rem 0;
    color: #999;
    font-size: 0.9rem;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e9ecef;
}

.divider span {
    padding: 0 1rem;
}

/* Quick Login Info */
.quick-login {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.quick-login h6 {
    font-size: 0.85rem;
    font-weight: 600;
    color: #667eea;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quick-login-item {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #666;
    padding: 0.3rem 0;
}

.quick-login-item strong {
    color: #333;
}

/* Footer Links */
.card-footer-custom {
    text-align: center;
    padding: 1.5rem 2rem;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

.card-footer-custom p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.card-footer-custom a {
    color: #667eea;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}

.card-footer-custom a:hover {
    color: #5568d3;
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 576px) {
    .card-header-custom {
        padding: 2rem 1.5rem;
    }
    
    .card-header-custom h2 {
        font-size: 1.5rem;
    }
    
    .card-body-custom {
        padding: 1.5rem;
    }
    
    .remember-forgot {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }
}
</style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <!-- Header -->
        <div class="card-header-custom">
            <div class="logo-icon">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <h2>Welcome Back</h2>
            <p>Login to access your account</p>
        </div>

        <!-- Body -->
        <div class="card-body-custom">
            <!-- Error Message -->
            <?php if($error): ?>
            <div class="alert-custom alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= $error ?></span>
            </div>
            <?php endif; ?>
            <!-- Login Form -->
            <form method="post" id="loginForm">
                <!-- Username/Email -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Username or Email
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" 
                               name="identifier" 
                               class="form-control-custom" 
                               placeholder="Enter username or email"
                               value="<?= isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : '' ?>"
                               required
                               autocomplete="username">
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               name="password" 
                               id="password"
                               class="form-control-custom" 
                               placeholder="Enter your password"
                               required
                               autocomplete="current-password">
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </button>
            </form>

            <!-- Divider -->
            <div class="divider">
                <span>OR</span>
            </div>

            <!-- Social Login (Optional - can be removed) -->
            <p style="text-align: center; color: #999; font-size: 0.85rem;">
                <i class="fas fa-shield-alt"></i> Secure login with encrypted password
            </p>
        </div>

        <!-- Footer -->
        <div class="card-footer-custom">
            <p>Don't have an account? <a href="register.php">Create one here</a></p>
        </div>
    </div>
</div>

<script>
// Toggle Password Visibility
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Form validation
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const identifier = document.querySelector('[name="identifier"]').value;
    const password = document.querySelector('[name="password"]').value;
    
    if (!identifier || !password) {
        e.preventDefault();
        alert('Please fill in all fields.');
    }
});

setTimeout(function() {
    const quickLogin = document.querySelector('.quick-login');
    if(quickLogin) {
        quickLogin.style.transition = 'opacity 0.5s';
        quickLogin.style.opacity = '0';
        setTimeout(() => quickLogin.remove(), 500);
    }
}, 10000);
</script>

</body>
</html>
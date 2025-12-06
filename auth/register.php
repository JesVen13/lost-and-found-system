<?php
session_start();
include("../db.php");
$error = "";
$success = "";

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // user/staff
    $agree_terms = isset($_POST['agree_terms']) ? $_POST['agree_terms'] : '';

    // Validation
    if(empty($username) || empty($email) || empty($password) || empty($confirm_password)){
        $error = "Please fill in all fields.";
    } elseif(strlen($username) < 3){
        $error = "Username must be at least 3 characters long.";
    } elseif(strlen($password) < 6){
        $error = "Password must be at least 6 characters long.";
    } elseif($password !== $confirm_password){
        $error = "Passwords do not match.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Please enter a valid email address.";
    } elseif(empty($agree_terms)){
        $error = "You must agree to use your real email address and accept the terms.";
    } else {
        // Check if username already exists
        $check_username = $con->prepare("SELECT user_id FROM users WHERE username = ?");
        $check_username->bind_param("s", $username);
        $check_username->execute();
        $check_username->store_result();
        
        if($check_username->num_rows > 0){
            $error = "Username is already taken. Please choose another one.";
        } else {
            // Check if email already exists
            $check_email = $con->prepare("SELECT user_id FROM users WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            $check_email->store_result();
            
            if($check_email->num_rows > 0){
                $error = "Email is already registered. Please use another email or <a href='login.php' style='color: #667eea; font-weight: 600;'>login here</a>.";
            } else {
                // Create account
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $status = ($role == "staff") ? "pending" : "approved";
                $stmt = $con->prepare("INSERT INTO users(username, email, password, role, status) VALUES(?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $username, $email, $hashed, $role, $status);
                
                if($stmt->execute()){
                    if($role == "staff"){
                        $success = "Staff account created successfully! Please wait for admin approval before logging in.";
                    } else {
                        $success = "Account created successfully! You can now <a href='login.php' style='color: #28a745; font-weight: 600;'>login here</a>.";
                    }
                } else {
                    $error = "An error occurred. Please try again.";
                }
                $stmt->close();
            }
            $check_email->close();
        }
        $check_username->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Lost & Found System</title>
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

.register-container {
    max-width: 480px;
    width: 100%;
}

.register-card {
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

.alert-success {
    background: #efe;
    color: #2a7;
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

.form-control-custom.error {
    border-color: #dc3545;
}

.form-select-custom {
    width: 100%;
    padding: 0.8rem 1rem 0.8rem 2.8rem;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 0.95rem;
    transition: all 0.3s;
    background: #f8f9fa;
    cursor: pointer;
}

.form-select-custom:focus {
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

/* Role Selection Info */
.role-info {
    background: #f8f9fa;
    border-left: 4px solid #667eea;
    padding: 0.75rem;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #666;
    margin-top: 0.5rem;
}

.role-info i {
    color: #667eea;
    margin-right: 0.5rem;
}

/* Terms and Conditions Checkbox */
.terms-container {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s;
}

.terms-container.checked {
    border-color: #667eea;
    background: #f0f3ff;
}

.terms-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
}

.terms-checkbox input[type="checkbox"] {
    width: 20px;
    height: 20px;
    margin-top: 2px;
    cursor: pointer;
    accent-color: #667eea;
    flex-shrink: 0;
}

.terms-text {
    font-size: 0.9rem;
    color: #555;
    line-height: 1.6;
}

.terms-text strong {
    color: #333;
}

.terms-text a {
    color: #667eea;
    font-weight: 600;
    text-decoration: none;
}

.terms-text a:hover {
    text-decoration: underline;
}

.terms-highlight {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 0.75rem;
    margin-top: 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    color: #856404;
}

.terms-highlight i {
    margin-right: 0.5rem;
    color: #ffc107;
}

/* Button */
.btn-register {
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

.btn-register:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-register:active {
    transform: translateY(0);
}

.btn-register:disabled {
    opacity: 0.6;
    cursor: not-allowed;
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

/* Password Strength Indicator */
.password-strength {
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    margin-top: 0.5rem;
    overflow: hidden;
}

.password-strength-bar {
    height: 100%;
    transition: all 0.3s;
    border-radius: 2px;
}

.password-strength-text {
    font-size: 0.8rem;
    margin-top: 0.25rem;
    font-weight: 500;
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
}
</style>
</head>
<body>

<div class="register-container">
    <div class="register-card">
        <!-- Header -->
        <div class="card-header-custom">
            <div class="logo-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Create Account</h2>
            <p>Join our Lost & Found community</p>
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

            <!-- Success Message -->
            <?php if($success): ?>
            <div class="alert-custom alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= $success ?></span>
            </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <?php if(!$success): ?>
            <form method="post" id="registerForm">
                <!-- Username -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" 
                               name="username" 
                               class="form-control-custom" 
                               placeholder="Choose a username"
                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                               required>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" 
                               name="email" 
                               class="form-control-custom" 
                               placeholder="@gmail.com"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                               required>
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
                               placeholder="Create a strong password"
                               required
                               onkeyup="checkPasswordStrength()">
                        <span class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                            <i class="fas fa-eye" id="toggleIcon1"></i>
                        </span>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="password-strength-text" id="strengthText"></div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               name="confirm_password" 
                               id="confirm_password"
                               class="form-control-custom" 
                               placeholder="Re-enter your password"
                               required>
                        <span class="password-toggle" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                            <i class="fas fa-eye" id="toggleIcon2"></i>
                        </span>
                    </div>
                </div>

                <!-- Role Selection -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-users"></i> Account Type
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-users input-icon"></i>
                        <select name="role" id="roleSelect" class="form-select-custom" onchange="showRoleInfo()" required>
                            <option value="user" <?= (isset($_POST['role']) && $_POST['role'] == 'user') ? 'selected' : '' ?>>Regular User</option>
                            <option value="staff" <?= (isset($_POST['role']) && $_POST['role'] == 'staff') ? 'selected' : '' ?>>Staff Member</option>
                        </select>
                    </div>
                    <div class="role-info" id="roleInfo">
                        <i class="fas fa-info-circle"></i>
                        <span id="roleInfoText">As a regular user, you can report lost/found items.</span>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="terms-container" id="termsContainer">
                    <label class="terms-checkbox">
                        <input type="checkbox" 
                               name="agree_terms" 
                               id="agreeTerms" 
                               value="1"
                               onchange="updateTermsContainer()">
                        <div class="terms-text">
                            <strong>I confirm that:</strong>
                            <ul style="margin: 0.5rem 0 0 0; padding-left: 1.25rem;">
                                <li>I am using my <strong>real and active email address</strong></li>
                                <li>This email will be used for <strong>password recovery</strong></li>
                                <li>This email will be used for <strong>account notifications</strong></li>
                                <li>I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></li>
                            </ul>
                        </div>
                    </label>
                    <div class="terms-highlight">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Important:</strong> Use a valid email you can access. Without it, you won't be able to recover your account if you forget your password.
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-register" id="submitBtn" disabled>
                    <i class="fas fa-user-plus"></i>
                    <span>Create Account</span>
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="card-footer-custom">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</div>

<script>
// Toggle Password Visibility
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
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

// Password Strength Checker
function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;
    
    const strengthLevels = [
        { width: '0%', color: '#e9ecef', text: '' },
        { width: '20%', color: '#dc3545', text: 'Very Weak' },
        { width: '40%', color: '#fd7e14', text: 'Weak' },
        { width: '60%', color: '#ffc107', text: 'Fair' },
        { width: '80%', color: '#28a745', text: 'Good' },
        { width: '100%', color: '#20c997', text: 'Strong' }
    ];
    
    const level = strengthLevels[strength];
    strengthBar.style.width = level.width;
    strengthBar.style.backgroundColor = level.color;
    strengthText.textContent = level.text;
    strengthText.style.color = level.color;
}

// Show Role Information
function showRoleInfo() {
    const role = document.getElementById('roleSelect').value;
    const infoText = document.getElementById('roleInfoText');
    
    if (role === 'user') {
        infoText.innerHTML = 'As a regular user, you can report lost/found items.';
    } else {
        infoText.innerHTML = 'Staff accounts require admin approval before you can login.';
    }
}

// Update Terms Container Styling and Enable/Disable Submit Button
function updateTermsContainer() {
    const checkbox = document.getElementById('agreeTerms');
    const container = document.getElementById('termsContainer');
    const submitBtn = document.getElementById('submitBtn');
    
    if (checkbox.checked) {
        container.classList.add('checked');
        submitBtn.disabled = false;
    } else {
        container.classList.remove('checked');
        submitBtn.disabled = true;
    }
}

// Form Validation
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const agreeTerms = document.getElementById('agreeTerms').checked;
    
    if (!agreeTerms) {
        e.preventDefault();
        alert('Please confirm that you are using your real email address and agree to the terms.');
        return;
    }
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return;
    }
});
</script>

</body>
</html>
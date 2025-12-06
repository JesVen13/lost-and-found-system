<?php
session_start();
include("../db.php"); // your DB connection

require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';
require '../PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";
$success = "";
$step = 1;

// POST HANDLERS
if($_SERVER['REQUEST_METHOD'] == "POST"){

    // STEP 1 — SEND CODE
    if(isset($_POST['send_code'])){
        $email = trim($_POST['email']);

        if(empty($email)){
            $error = "Please enter your email.";
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $error = "Invalid email format.";
        } else {
            $stmt = $con->prepare("SELECT user_id, username, email FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result->num_rows == 1){
                $user = $result->fetch_assoc();

                $reset_code = sprintf("%06d", mt_rand(1, 999999));
                $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                $_SESSION['reset_code'] = $reset_code;
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_user_id'] = $user['user_id'];
                $_SESSION['reset_expires'] = $expires_at;

                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;

                    // YOUR GMAIL + APP PASSWORD
                    $mail->Username = 'igopxmrcabalo@gmail.com';
                    $mail->Password = 'dzea tunb vsyc emeh';

                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('YOUR_EMAIL@gmail.com', 'Lost & Found System');
                    $mail->addAddress($email, $user['username']);
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Code';
                    $mail->Body = "
                        Hello <b>{$user['username']}</b>,<br><br>
                        Your password reset code is: <b>{$reset_code}</b><br>
                        It expires in 15 minutes.<br><br>
                        If you did not request this, please ignore this email.
                    ";

                    $mail->send();
                    $success = "Reset code sent to your email!";
                } catch (Exception $e) {
                    $error = "Email error: {$mail->ErrorInfo}";
                }

                $step = 2;
            } else {
                $error = "Email not found.";
            }
            $stmt->close();
        }
    }

    // STEP 2 — VERIFY CODE
    elseif(isset($_POST['verify_code'])){
        $entered = trim($_POST['reset_code']);

        if(empty($entered)){
            $error = "Enter the code.";
            $step = 2;
        } elseif(strtotime($_SESSION['reset_expires']) < time()){
            $error = "Code expired. Request a new one.";
            session_unset();
            $step = 1;
        } elseif($entered != $_SESSION['reset_code']){
            $error = "Incorrect code.";
            $step = 2;
        } else {
            $success = "Code verified!";
            $step = 3;
        }
    }

    // STEP 3 — RESET PASSWORD
    elseif(isset($_POST['reset_password'])){
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if(empty($new) || empty($confirm)){
            $error = "Fill all fields.";
            $step = 3;
        } elseif(strlen($new) < 6){
            $error = "Password must be at least 6 characters.";
            $step = 3;
        } elseif($new !== $confirm){
            $error = "Passwords do not match.";
            $step = 3;
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $id = $_SESSION['reset_user_id'];

            $stmt = $con->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed, $id);

            if($stmt->execute()){
                session_unset();
                $success = "Password reset successful!";
                $step = 4;
            } else {
                $error = "Error resetting password.";
                $step = 3;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password | Lost & Found</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
body {
    margin: 0;
    padding: 0;
    font-family: "Poppins", sans-serif;
    background: linear-gradient(135deg, #4E65FF, #92EFFD);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
.forgot-container {
    width: 100%;
    max-width: 420px;
    padding: 20px;
}
.forgot-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    padding: 28px 32px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.25);
    animation: fadeIn 0.5s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.logo-icon {
    width: 70px;
    height: 70px;
    background: rgba(255,255,255,0.35);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: auto;
    font-size: 30px;
    margin-bottom: 10px;
    color: #fff;
}
h2 { color: #fff; text-align:center; font-size:26px; }
p { text-align:center; color:#f3f3f3; font-size:14px; }

.form-control-custom {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    background: rgba(255,255,255,0.25);
    border: 1px solid rgba(255,255,255,0.4);
    margin-bottom: 15px;
    color: #fff;
}
.form-control-custom::placeholder { color:#eaeaea; }

.btn-custom {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    font-weight: 600;
    border: none;
    background: #fff;
    color: #4E65FF;
    transition: 0.3s ease;
}
.btn-custom:hover { background:#f1f1f1; }

.alert-custom {
    padding: 10px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    color: #fff;
}
.alert-danger { background: rgba(255,0,0,0.3); }
.alert-success { background: rgba(0,255,0,0.3); }
.alert-custom i { margin-right: 10px; }

.card-footer-custom {
    text-align:center;
    margin-top: 10px;
    color: #fff;
}
.card-footer-custom a { color:#fff; font-weight:600; }
</style>

</head>
<body>

<div class="forgot-container">
    <div class="forgot-card">

        <div class="logo-icon"><i class="fa fa-key"></i></div>
        <h2>Password Reset</h2>

        <p>
            <?php if($step == 1): ?> Enter your email to receive a code
            <?php elseif($step == 2): ?> Enter the verification code
            <?php elseif($step == 3): ?> Create your new password
            <?php else: ?> Reset Complete!
            <?php endif; ?>
        </p>

        <?php if($error): ?>
            <div class="alert-custom alert-danger"><i class="fa fa-warning"></i> <?= $error ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert-custom alert-success"><i class="fa fa-check"></i> <?= $success ?></div>
        <?php endif; ?>

        <!-- STEP 1 -->
        <?php if($step == 1): ?>
        <form method="POST">
            <input type="email" name="email" class="form-control-custom" placeholder="Enter email">
            <button name="send_code" class="btn-custom">Send Code</button>
        </form>
        <?php endif; ?>

        <!-- STEP 2 -->
        <?php if($step == 2): ?>
        <form method="POST">
            <input type="text" name="reset_code" maxlength="6" class="form-control-custom" placeholder="Enter 6-digit code">
            <button name="verify_code" class="btn-custom">Verify Code</button>
        </form>
        <?php endif; ?>

        <!-- STEP 3 -->
        <?php if($step == 3): ?>
        <form method="POST">
            <input type="password" name="new_password" class="form-control-custom" placeholder="New Password">
            <input type="password" name="confirm_password" class="form-control-custom" placeholder="Confirm Password">
            <button name="reset_password" class="btn-custom">Reset Password</button>
        </form>
        <?php endif; ?>

        <!-- STEP 4 -->
        <?php if($step == 4): ?>
        <div class="text-center" style="color:#fff;">
            <h3>Password Reset Successful!</h3>
            <a href="login.php" class="btn-custom" style="margin-top:10px;display:block;">Go to Login</a>
        </div>
        <?php endif; ?>

        <div class="card-footer-custom">
            <p><a href="login.php">Back to Login</a></p>
        </div>
    </div>
</div>

</body>
</html>

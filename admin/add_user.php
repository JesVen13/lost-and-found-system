<?php
session_start();
include("../db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../auth/login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD']=='POST'){
    $username = $con->real_escape_string($_POST['username']);
    $email = $con->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $con->query("INSERT INTO users (username,email,password,role,status) VALUES ('$username','$email','$password','$role','approved')");

    header("Location: manage_users.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add User - Lost & Found System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
body {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
}
.container {
    background-color: #ffffff;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    margin-top: 20px;
    margin-bottom: 20px;
}
.header {
    text-align: center;
    margin-bottom: 30px;
    color: #1976d2;
}
.header h1 {
    font-size: 2.5rem;
    font-weight: bold;
}
.header p {
    font-size: 1.1rem;
    color: #666;
}
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}
.card-header {
    background: linear-gradient(90deg, #1976d2, #42a5f5);
    color: white;
    font-weight: bold;
    border-radius: 10px 10px 0 0 !important;
}
.btn {
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
}
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.btn-success {
    background-color: #4caf50;
    border-color: #4caf50;
}
.btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
}
.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #ddd;
}
.form-control:focus, .form-select:focus {
    border-color: #1976d2;
    box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.25);
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="bi bi-person-plus-fill"></i> Add User</h1>
        <p>Admin Panel for Adding New Users, Staff, or Admins</p>
    </div>

    <!-- Add User Form Card -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-person-plus"></i> Add New User / Staff / Admin
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-3">
                    <label for="username" class="form-label"><i class="bi bi-person"></i> Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required>
                </div>
                <div class="col-md-3">
                    <label for="email" class="form-label"><i class="bi bi-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter email" required>
                </div>
                <div class="col-md-3">
                    <label for="password" class="form-label"><i class="bi bi-lock"></i> Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <div class="col-md-2">
                    <label for="role" class="form-label"><i class="bi bi-shield"></i> Role</label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="user">User</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-success w-100">
                        <i class="bi bi-plus-circle"></i> Add
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="text-center">
        <a href="manage_users.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Manage Users
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

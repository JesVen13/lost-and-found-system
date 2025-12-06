<?php
session_start();
include("../db.php");
if(!isset($_SESSION['role']) || $_SESSION['role']!="user") {
    header("Location:../auth/login.php"); 
    exit;
}

$id = intval($_GET['id']);
$res = $con->query("SELECT * FROM found_items WHERE found_id=$id AND reporter_id=".$_SESSION['user_id']);
$item = $res->fetch_assoc();
if(!$item) {
    header("Location:found_items.php"); 
    exit;
}

$error = ""; 
$success = "";

if($_SERVER['REQUEST_METHOD'] == "POST") {
    $item_name = trim($_POST['item_name']);
    $date_found = $_POST['date_found'];
    $color = trim($_POST['color']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $contact_info = trim($_POST['contact_info']);
    
    // Handle image upload
    $image_path = $item['image_path']; // Keep existing image by default
    
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)) {
            $upload_dir = "../uploads/";
            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $new_filename = uniqid() . '_' . time() . '.' . $filetype;
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if exists
                if($item['image_path'] && file_exists($upload_dir . $item['image_path'])) {
                    unlink($upload_dir . $item['image_path']);
                }
                $image_path = $new_filename;
            } else {
                $error = "Error uploading image.";
            }
        } else {
            $error = "Invalid image format. Only JPG, JPEG, PNG, and GIF allowed.";
        }
    }
    
    if(!$error && $item_name && $date_found && $color && $location) {
        $stmt = $con->prepare("UPDATE found_items SET item_name=?, date_found=?, color=?, location_found=?, description=?, contact_info=?, image_path=?, approval_status='pending' WHERE found_id=?");
        $stmt->bind_param("sssssssi", $item_name, $date_found, $color, $location, $description, $contact_info, $image_path, $id);
        
        if($stmt->execute()) {
            $success = "Item updated successfully! Waiting for staff approval.";
            // Refresh item data
            $res = $con->query("SELECT * FROM found_items WHERE found_id=$id");
            $item = $res->fetch_assoc();
        } else {
            $error = "Error updating item.";
        }
        $stmt->close();
    } else if(!$error) {
        $error = "Please fill all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Found Item</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #e8f5e9;
    min-height: 100vh;
    padding: 2rem 1rem;
}

.main-container {
    max-width: 600px;
    margin: 0 auto;
}

.page-header {
    text-align: center;
    margin-bottom: 2rem;
}

.item-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #28a745;
    color: white;
    padding: 0.5rem 1.25rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 0.5rem;
}

.page-header p {
    color: #666;
    font-size: 0.95rem;
}

.form-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border: 1px solid #f0f0f0;
}

.alert {
    border-radius: 10px;
    border: none;
    padding: 0.875rem 1rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.625rem;
    font-size: 0.9rem;
}

.alert i {
    font-size: 1.1rem;
}

.alert-danger {
    background: #ffe5e5;
    color: #c33;
}

.alert-success {
    background: #d4edda;
    color: #155724;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.form-label .required {
    color: #28a745;
}

.form-control {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 0.625rem 0.875rem;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.form-control:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.08);
}

textarea.form-control {
    min-height: 90px;
    resize: vertical;
}

.current-image-wrapper {
    margin-bottom: 1rem;
    text-align: center;
}

.current-image-wrapper img {
    max-width: 100%;
    max-height: 180px;
    border-radius: 8px;
    border: 2px solid #f0f0f0;
}

.current-image-wrapper p {
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: #999;
}

.upload-box {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 1.5rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: #fafafa;
}

.upload-box:hover {
    border-color: #28a745;
    background: #f1f8f4;
}

.upload-box i {
    font-size: 2rem;
    color: #28a745;
    margin-bottom: 0.5rem;
}

.upload-box p {
    margin: 0;
    color: #666;
    font-size: 0.875rem;
}

.upload-box .file-name {
    margin-top: 0.5rem;
    color: #28a745;
    font-weight: 600;
    font-size: 0.85rem;
}

#image {
    display: none;
}

.image-preview {
    margin-top: 1rem;
    display: none;
    text-align: center;
}

.image-preview img {
    max-width: 100%;
    max-height: 180px;
    border-radius: 8px;
    border: 2px solid #f0f0f0;
}

.btn-update {
    width: 100%;
    padding: 0.875rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    background: #28a745;
    border: none;
    color: white;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-update:hover {
    background: #218838;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.25);
}

.btn-dashboard {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    color: #666;
    text-decoration: none;
    font-weight: 500;
    padding: 0.75rem;
    border-radius: 8px;
    background: white;
    transition: all 0.2s;
    margin-top: 1rem;
    border: 1px solid #e0e0e0;
    font-size: 0.9rem;
}

.btn-dashboard:hover {
    background: #f8f8f8;
    color: #333;
    border-color: #ccc;
}

@media (max-width: 768px) {
    body {
        padding: 1rem 0.75rem;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .form-card {
        padding: 1.5rem;
    }
}
</style>
</head>
<body>

<div class="main-container">
    <div class="page-header">
        <div class="item-type-badge">
            <i class="fas fa-box-open"></i> Found Item
        </div>
        <h1>Edit Found Item</h1>
        <p>Update details · Requires approval</p>
    </div>

    <div class="form-card">
        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= $success ?></span>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Item Name <span class="required">*</span></label>
                <input class="form-control" type="text" name="item_name" placeholder="e.g., iPhone 13, Blue Backpack" value="<?= htmlspecialchars($item['item_name']) ?>" required>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Date Found <span class="required">*</span></label>
                        <input class="form-control" type="date" name="date_found" max="<?= date('Y-m-d') ?>" value="<?= $item['date_found'] ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Color <span class="required">*</span></label>
                        <input class="form-control" type="text" name="color" placeholder="e.g., Black, Red" value="<?= htmlspecialchars($item['color']) ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Location Found <span class="required">*</span></label>
                <input class="form-control" type="text" name="location" placeholder="e.g., Building A - Room 101" value="<?= htmlspecialchars($item['location_found']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Contact Information</label>
                <input class="form-control" type="text" name="contact_info" placeholder="Phone or email" value="<?= htmlspecialchars($item['contact_info'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" placeholder="Additional details"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Item Photo</label>
                
                <?php if(!empty($item['image_path']) && file_exists("../uploads/".$item['image_path'])): ?>
                <div class="current-image-wrapper">
                    <img src="../uploads/<?= htmlspecialchars($item['image_path']) ?>" alt="Current Item">
                    <p>Current photo</p>
                </div>
                <?php endif; ?>
                
                <label for="image" class="upload-box">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p><strong>Click to upload</strong> or drag & drop</p>
                    <p style="font-size: 0.8rem; color: #999; margin-top: 0.25rem;">PNG, JPG, GIF (max 5MB)</p>
                    <span class="file-name"></span>
                </label>
                <input type="file" id="image" name="image" accept="image/*">
                <div class="image-preview">
                    <img id="preview" src="" alt="Preview">
                </div>
            </div>

            <button type="submit" class="btn btn-update">
                <i class="fas fa-save"></i>Update Item
            </button>
        </form>
    </div>

    <a href="dashboard.php" class="btn-dashboard">
        <i class="fas fa-arrow-left"></i>Back to Dashboard
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const imageInput = document.getElementById('image');
const imagePreview = document.querySelector('.image-preview');
const preview = document.getElementById('preview');
const fileName = document.querySelector('.file-name');

imageInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if(file) {
        fileName.textContent = file.name;
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            imagePreview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        fileName.textContent = '';
        imagePreview.style.display = 'none';
    }
});

const uploadArea = document.querySelector('.upload-box');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    uploadArea.addEventListener(eventName, () => {
        uploadArea.style.borderColor = '#28a745';
        uploadArea.style.background = '#f1f8f4';
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, () => {
        uploadArea.style.borderColor = '#ddd';
        uploadArea.style.background = '#fafafa';
    }, false);
});

uploadArea.addEventListener('drop', function(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    imageInput.files = files;
    const event = new Event('change', { bubbles: true });
    imageInput.dispatchEvent(event);
}, false);
</script>
</body>
</html>
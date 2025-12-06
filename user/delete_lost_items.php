<?php
session_start();
include("../db.php");

// Check if user is logged in
if(!isset($_SESSION['role']) || $_SESSION['role'] != "user") {
    header("Location:../auth/login.php"); 
    exit;
}

// Check if ID is provided
if(!isset($_GET['id'])) {
    header("Location:dashboard.php");
    exit;
}

$id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Verify the item belongs to this user
$res = $con->query("SELECT * FROM lost_items WHERE lost_id=$id AND reporter_id=$user_id");
$item = $res->fetch_assoc();

if(!$item) {
    // Item doesn't exist or doesn't belong to this user
    $_SESSION['error'] = "Item not found or you don't have permission to delete it.";
    header("Location:dashboard.php");
    exit;
}

// Delete the image file if it exists
if(!empty($item['image_path'])) {
    $image_file = "../uploads/" . $item['image_path'];
    if(file_exists($image_file)) {
        unlink($image_file);
    }
}

// Delete the item from database
$stmt = $con->prepare("DELETE FROM lost_items WHERE lost_id=? AND reporter_id=?");
$stmt->bind_param("ii", $id, $user_id);

if($stmt->execute()) {
    $_SESSION['success'] = "Lost item deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting item. Please try again.";
}

$stmt->close();
$con->close();

// Redirect back to dashboard
header("Location:dashboard.php");
exit;
?>
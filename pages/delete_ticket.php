<?php
session_start();
require '../config/db.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// 2. Role Check (Only Admin can soft-delete)
$user_id = $_SESSION['user_id'];
$role_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$role_stmt->execute([$user_id]);
$user_role = $role_stmt->fetchColumn();

if ($user_role !== 'admin') {
    die("Unauthorized: Only admins can perform this action.");
}

// 3. Perform Soft Delete
if (isset($_GET['id'])) {
    $ticket_id = $_GET['id'];
    
    // We UPDATE the flag instead of DELETING the row
    $sql = "UPDATE tickets SET is_deleted = 1 WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$ticket_id])) {
        header("Location: ../index.php?msg=deleted");
        exit;
    } else {
        die("Error updating record.");
    }
}
?>
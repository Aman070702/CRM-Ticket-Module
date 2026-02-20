<?php
// pages/restore_ticket.php
session_start();
require '../config/db.php';

// 1. Security Check: Only Admins can restore
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$role_stmt->execute([$user_id]);

if ($role_stmt->fetchColumn() !== 'admin') {
    die("Access Denied: Only Admins can restore archived tickets.");
}

// 2. Process Restore Action
$ticket_id = $_GET['id'] ?? null;

if ($ticket_id) {
    try {
        // Change is_deleted back to 0
        $stmt = $pdo->prepare("UPDATE tickets SET is_deleted = 0 WHERE id = ?");
        $stmt->execute([$ticket_id]);
    } catch (PDOException $e) {
        die("Error restoring ticket: " . $e->getMessage());
    }
}

// Redirect back to the Archived tab
header("Location: ../index.php?view=archived");
exit;
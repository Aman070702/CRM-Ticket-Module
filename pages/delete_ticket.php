<?php
// pages/delete_ticket.php
session_start();
require '../config/db.php';

// 1. Check if ID exists
if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$ticket_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 2. Security Check: Only the CREATOR can delete the ticket
// We run a query to see if this ticket belongs to the logged-in user
$stmt = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND created_by = ?");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch();

if ($ticket) {
    // 3. Delete the Ticket
    $del_stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
    $del_stmt->execute([$ticket_id]);
    
    // Redirect to Dashboard with a success flag (optional)
    header("Location: ../index.php?msg=deleted");
    exit;
} else {
    die("❌ Error: You cannot delete this ticket (Access Denied).");
}
?>
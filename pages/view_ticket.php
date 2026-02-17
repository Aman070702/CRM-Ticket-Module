<?php
// pages/view_ticket.php
session_start();
require '../config/db.php';

// 1. Security Check: Login Required
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// 2. Get Ticket ID from URL
if (!isset($_GET['id'])) {
    die("Error: No Ticket ID provided.");
}
$ticket_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 3. Fetch Ticket Details (Secure Query)
// We check if the user is the CREATOR or the ASSIGNEE (Security Rule)
$sql = "SELECT tickets.*, users.name as creator_name 
        FROM tickets 
        JOIN users ON tickets.created_by = users.id 
        WHERE tickets.id = ? AND (created_by = ? OR assigned_to = ?)";

$stmt = $pdo->prepare($sql);
$stmt->execute([$ticket_id, $user_id, $user_id]);
$ticket = $stmt->fetch();

// 4. Security Check: Does the ticket exist and do they have permission?
if (!$ticket) {
    die("❌ Error: Ticket not found or Access Denied.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ticket #<?php echo $ticket['id']; ?></title>
    <style>
        body { font-family: sans-serif; padding: 40px; background: #f4f4f4; }
        .container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .status { padding: 5px 10px; border-radius: 4px; font-weight: bold; text-transform: uppercase; font-size: 12px; }
        .status-pending { background: #ffeeba; color: #856404; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-inprogress { background: #cce5ff; color: #004085; }
        .meta { color: #666; font-size: 14px; margin-bottom: 20px; }
        .description { background: #f9f9f9; padding: 20px; border-left: 4px solid #007bff; }
        .btn { text-decoration: none; display: inline-block; padding: 10px 20px; background: #6c757d; color: white; border-radius: 4px; margin-top: 20px;}
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>#<?php echo $ticket['id']; ?>: <?php echo htmlspecialchars($ticket['name']); ?></h2>
        <div>
            <span class="status status-<?php echo $ticket['status']; ?>">
                <?php echo $ticket['status']; ?>
            </span>
        </div>
    </div>

    <div class="meta">
        <strong>Created By:</strong> <?php echo htmlspecialchars($ticket['creator_name']); ?> <br>
        <strong>Date:</strong> <?php echo date("F j, Y, g:i a", strtotime($ticket['created_at'])); ?>
    </div>

    <h3>Description</h3>
    <div class="description">
        <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
    </div>

    <br>
    <br>
    <a href="../index.php" class="btn">← Back to Dashboard</a>
    
    <a href="edit_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn" style="background:#ffc107; color:black;">
        ✎ Edit Ticket
    </a>
    
    </div>

</body>
</html>
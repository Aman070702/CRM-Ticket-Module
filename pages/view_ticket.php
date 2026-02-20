<?php
// pages/view_ticket.php
session_start();
require '../config/db.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$ticket_id = $_GET['id'] ?? null;

if (!$ticket_id) { die("Invalid Ticket ID."); }

// 2. Fetch Ticket & User Role in a single trip for performance
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND is_deleted = 0");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) { die("Ticket not found or has been archived."); }

$role_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$role_stmt->execute([$user_id]);
$user_role = $role_stmt->fetchColumn();

// 3. Permission Flags
$is_admin = ($user_role === 'admin');
$is_assignee = ($ticket['assigned_to'] == $user_id);
$is_creator = ($ticket['created_by'] == $user_id);

// Deny access if they have no relation to this ticket
if (!$is_admin && !$is_assignee && !$is_creator) {
    die("Access Denied: You do not have authority to view this ticket.");
}

// 4. Update Logic (Authorized Staff or Admin only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($is_admin || $is_assignee)) {
    $new_status = $_POST['status'];
    
    try {
        if ($is_admin) {
            // Admin has FULL authority
            $new_name = $_POST['name'];
            $new_desc = $_POST['description'];
            $sql = "UPDATE tickets SET name = ?, description = ?, status = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$new_name, $new_desc, $new_status, $ticket_id]);
        } else {
            // Assignee has STATUS authority only
            $sql = "UPDATE tickets SET status = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$new_status, $ticket_id]);
        }
        header("Location: view_ticket.php?id=$ticket_id&msg=updated");
        exit;
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Ticket #<?php echo $ticket['id']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; padding: 40px; color: #1f2937; }
        .container { max-width: 700px; margin: auto; background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .authority-tag { font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 4px; background: #e0e7ff; color: #4338ca; }
        
        .badge { padding: 6px 14px; border-radius: 99px; font-size: 13px; font-weight: 600; }
        .badge-pending { background: #fffbeb; color: #b45309; }
        .badge-inprogress { background: #eff6ff; color: #1d4ed8; }
        .badge-completed { background: #ecfdf5; color: #047857; }
        
        label { display: block; margin-top: 24px; font-weight: 600; font-size: 14px; color: #374151; }
        .readonly-box { background: #f9fafb; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb; margin-top: 8px; color: #4b5563; line-height: 1.6; }
        
        input, textarea, select { width: 100%; padding: 12px; margin-top: 8px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; transition: border-color 0.2s; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #2563eb; ring: 2px solid #bfdbfe; }
        
        button { margin-top: 32px; padding: 14px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600; font-size: 16px; transition: background 0.2s; }
        button:hover { background: #1d4ed8; }
        
        .back-btn { display: block; text-align: center; margin-top: 24px; color: #6b7280; text-decoration: none; font-size: 14px; font-weight: 500; }
        .back-btn:hover { color: #111827; }
        
        .msg { background: #ecfdf5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; text-align: center; border: 1px solid #a7f3d0; }
    </style>
</head>
<body>

<div class="container">
    <?php if(isset($_GET['msg'])): ?>
        <div class="msg">✅ Changes saved successfully.</div>
    <?php endif; ?>

    <div class="header-flex">
        <h2 style="font-size: 22px;">Ticket #<?php echo $ticket['id']; ?></h2>
        <div>
            <span class="authority-tag">
                <?php 
                    if($is_admin) echo "Admin Authority";
                    elseif($is_assignee) echo "Staff Authority";
                    else echo "View Only Access";
                ?>
            </span>
            <span class="badge badge-<?php echo str_replace(' ', '', strtolower($ticket['status'])); ?>">
                <?php echo ucfirst($ticket['status']); ?>
            </span>
        </div>
    </div>

    <?php if ($is_admin || $is_assignee): ?>
        <form method="POST">
            <label>Subject</label>
            <?php if ($is_admin): ?>
                <input type="text" name="name" value="<?php echo htmlspecialchars($ticket['name']); ?>" required>
            <?php else: ?>
                <div class="readonly-box"><?php echo htmlspecialchars($ticket['name']); ?></div>
            <?php endif; ?>

            <label>Description</label>
            <?php if ($is_admin): ?>
                <textarea name="description" rows="5" required><?php echo htmlspecialchars($ticket['description']); ?></textarea>
            <?php else: ?>
                <div class="readonly-box"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></div>
            <?php endif; ?>

            <?php if (!empty($ticket['file_path'])): ?>
                <label>Attachment</label>
                <div style="margin-top: 8px;">
                    <a href="../uploads/<?php echo htmlspecialchars($ticket['file_path']); ?>" target="_blank" style="background: #e5e7eb; color: #374151; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: inline-block;">
                        📎 View Attached File
                    </a>
                </div>
            <?php endif; ?>

            <label>Update Status Authority</label>
            <select name="status">
                <option value="pending" <?php if($ticket['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                <option value="inprogress" <?php if($ticket['status'] == 'inprogress') echo 'selected'; ?>>In Progress</option>
                <option value="completed" <?php if($ticket['status'] == 'completed') echo 'selected'; ?>>Completed</option>
            </select>

            <button type="submit">Save Authority Action</button>
        </form>
    <?php else: ?>
        <label>Subject</label>
        <div class="readonly-box"><?php echo htmlspecialchars($ticket['name']); ?></div>

        <label>Description</label>
        <div class="readonly-box"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></div>
        
        <?php if (!empty($ticket['file_path'])): ?>
            <label>Attachment</label>
            <div style="margin-top: 8px;">
                <a href="../uploads/<?php echo htmlspecialchars($ticket['file_path']); ?>" target="_blank" style="background: #e5e7eb; color: #374151; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: inline-block;">
                    📎 View Attached File
                </a>
            </div>
        <?php endif; ?>
        
        <label>Current Status</label>
        <div class="readonly-box" style="font-weight: 600;">
            <?php echo ucfirst($ticket['status']); ?>
        </div>
        <p style="margin-top: 20px; font-size: 13px; color: #6b7280; text-align: center; font-style: italic;">
            Note: As the creator, you can no longer modify this ticket once it has been processed.
        </p>
    <?php endif; ?>

    <a href="../index.php" class="back-btn">← Back to Dashboard</a>
</div>

</body>
</html>
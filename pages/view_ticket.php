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

// 2. Fetch Ticket (Removed 'is_deleted = 0' so we can view archived tickets too)
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) { die("Ticket not found in the database."); }

// Check if the ticket is archived
$is_archived = ($ticket['is_deleted'] == 1);

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

// 4. Update Logic (Authorized Staff or Admin only, AND ticket must NOT be archived)
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($is_admin || $is_assignee) && !$is_archived) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ticket #<?php echo $ticket['id']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; padding: 40px 20px; color: #1e293b; }
        .container { max-width: 750px; margin: auto; background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; }
        .header-left h2 { font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 5px; }
        .authority-tag { font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 6px 10px; border-radius: 6px; background: #e0e7ff; color: #4338ca; display: inline-block; margin-right: 8px; }
        .badge { padding: 6px 14px; border-radius: 99px; font-size: 13px; font-weight: 600; display: inline-block; }
        .badge-pending { background: #fef3c7; color: #b45309; }
        .badge-inprogress { background: #e0e7ff; color: #4338ca; }
        .badge-completed { background: #d1fae5; color: #059669; }
        .badge-archived { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        label { display: block; margin-top: 24px; font-weight: 600; font-size: 14px; color: #475569; margin-bottom: 8px; }
        .readonly-box { background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; color: #334155; line-height: 1.6; font-size: 15px; }
        input, textarea, select { width: 100%; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; transition: all 0.2s; background: #f8fafc; color: #1e293b; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); background: #ffffff; }
        button { margin-top: 32px; padding: 14px; background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600; font-size: 16px; }
        button:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3); }
        .back-btn { display: block; text-align: center; margin-top: 24px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: 600; }
        .msg { background: #ecfdf5; color: #065f46; padding: 16px; border-radius: 8px; margin-bottom: 24px; text-align: center; border: 1px solid #a7f3d0; font-weight: 500; }
        .locked-warning { background: #fef2f2; color: #991b1b; padding: 12px 16px; border-radius: 8px; border: 1px solid #fecaca; font-size: 14px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
<div class="container">
    
    <?php if($is_archived): ?>
        <div class="locked-warning">🔒 This ticket is archived. It is read-only and cannot be modified.</div>
    <?php endif; ?>

    <?php if(isset($_GET['msg'])): ?>
        <div class="msg">✅ Changes saved successfully.</div>
    <?php endif; ?>

    <div class="header-flex">
        <div class="header-left">
            <h2>Ticket #<?php echo $ticket['id']; ?></h2>
        </div>
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
            
            <?php if($is_archived): ?>
                <span class="badge badge-archived">Archived</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (($is_admin || $is_assignee) && !$is_archived): ?>
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
                    <a href="../uploads/<?php echo htmlspecialchars($ticket['file_path']); ?>" target="_blank" style="background: #e2e8f0; color: #334155; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-block;">
                        📎 View Attached File
                    </a>
                </div>
            <?php endif; ?>

            <label>Update Status</label>
            <select name="status">
                <option value="pending" <?php if($ticket['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                <option value="inprogress" <?php if($ticket['status'] == 'inprogress') echo 'selected'; ?>>In Progress</option>
                <option value="completed" <?php if($ticket['status'] == 'completed') echo 'selected'; ?>>Completed</option>
            </select>

            <button type="submit">Save Changes</button>
        </form>
        
    <?php else: ?>
        <label>Subject</label>
        <div class="readonly-box"><?php echo htmlspecialchars($ticket['name']); ?></div>

        <label>Description</label>
        <div class="readonly-box"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></div>
        
        <?php if (!empty($ticket['file_path'])): ?>
            <label>Attachment</label>
            <div style="margin-top: 8px;">
                <a href="../uploads/<?php echo htmlspecialchars($ticket['file_path']); ?>" target="_blank" style="background: #e2e8f0; color: #334155; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-block;">
                    📎 View Attached File
                </a>
            </div>
        <?php endif; ?>
        
        <label>Current Status</label>
        <div class="readonly-box" style="font-weight: 600; display: inline-block; margin-bottom: 20px;">
            <?php echo ucfirst($ticket['status']); ?>
        </div>
        
        <?php if(!$is_archived): ?>
            <p style="margin-top: 20px; font-size: 13px; color: #64748b; text-align: center; font-style: italic;">
                Note: As the creator, you can no longer modify this ticket once it has been processed.
            </p>
        <?php endif; ?>
    <?php endif; ?>

    <a href="../index.php<?php echo $is_archived ? '?view=archived' : ''; ?>" class="back-btn">← Back to Dashboard</a>
</div>
</body>
</html>
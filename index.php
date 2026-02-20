<?php
// index.php
session_start();
require 'config/db.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Fetch User Role
$role_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$role_stmt->execute([$user_id]);
$user_role = $role_stmt->fetchColumn(); 

// 3. Fetch Tickets with Visibility Logic
if ($user_role == 'admin') {
    // Admin sees everything
    $sql = "SELECT t.*, u.name as assignee_name 
            FROM tickets t 
            LEFT JOIN users u ON t.assigned_to = u.id 
            WHERE t.is_deleted = 0 
            ORDER BY t.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
} else {
    // Users/Staff see tickets they CREATED (to track) OR tickets ASSIGNED to them (to work)
    $sql = "SELECT t.*, 
            CASE 
                WHEN t.assigned_to = ? THEN 'You (Assignee)' 
                WHEN t.created_by = ? THEN 'You (Creator)' 
            END as relation
            FROM tickets t 
            WHERE (t.assigned_to = ? OR t.created_by = ?) 
            AND t.is_deleted = 0 
            ORDER BY t.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
}
$tickets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - CRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; color: #1f2937; padding-bottom: 50px; }
        .navbar { background: #fff; padding: 15px 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .user-info { font-size: 14px; color: #6b7280; border-left: 1px solid #e5e7eb; padding-left: 20px; }
        .admin-btn { background: #e0e7ff; color: #4338ca; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 14px; }
        .container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .btn-create { background: #2563eb; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.2s; }
        .btn-create:hover { background: #1d4ed8; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; text-align: left; padding: 16px; font-size: 13px; color: #4b5563; border-bottom: 1px solid #e5e7eb; }
        td { padding: 16px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .badge { padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; }
        .badge-pending { background: #fffbeb; color: #b45309; }
        .badge-inprogress { background: #eff6ff; color: #1d4ed8; }
        .badge-completed { background: #ecfdf5; color: #047857; }
        .action-link { text-decoration: none; font-weight: 500; font-size: 13px; margin-right: 15px; }
    </style>
</head>
<body>

    <div class="navbar">
        <h2>🚀 CRM System</h2>
        <div class="nav-right">
            <?php if($user_role == 'admin'): ?>
                <a href="pages/manage_users.php" class="admin-btn">👥 Manage Users</a>
            <?php endif; ?>
            <div class="user-info">
                <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (<?php echo ucfirst($user_role); ?>)
                <a href="auth/logout.php" style="color:#ef4444; margin-left:10px; text-decoration:none;">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="action-bar">
            <h1>Your Workspace</h1>
            
            <?php if($user_role !== 'admin' && $user_role !== 'staff'): ?>
                <a href="pages/create_ticket.php" class="btn-create">+ Create New Ticket</a>
            <?php endif; ?>
        </div>

        <div class="card">
            <?php if(count($tickets) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Authority Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td>#<?php echo $ticket['id']; ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($ticket['name']); ?></td>
                            <td>
                                <?php 
                                    if($user_role == 'admin') {
                                        echo "👤 " . ($ticket['assignee_name'] ?? "<span style='color:gray'>Unassigned</span>");
                                    } else {
                                        echo $ticket['relation']; 
                                    }
                                ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($ticket['status']); ?>">
                                    <?php echo ucfirst($ticket['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="pages/view_ticket.php?id=<?php echo $ticket['id']; ?>" class="action-link" style="color: #4f46e5;">View</a>
                                <?php if($user_role == 'admin'): ?>
                                    <a href="pages/assign_ticket.php?id=<?php echo $ticket['id']; ?>" class="action-link" style="color: #059669;">Assign</a>
                                    <a href="pages/delete_ticket.php?id=<?php echo $ticket['id']; ?>" class="action-link" style="color: #dc2626;" onclick="return confirm('Archive?');">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; color: #6b7280;">No tickets found.</div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
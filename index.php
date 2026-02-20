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

// 3. Determine which view we are in (Active vs Archived)
$view_archived = (isset($_GET['view']) && $_GET['view'] === 'archived') ? 1 : 0;

// 4. Fetch Tickets based on Role AND View Status
if ($user_role == 'admin') {
    $sql = "SELECT t.*, u.name as assignee_name 
            FROM tickets t 
            LEFT JOIN users u ON t.assigned_to = u.id 
            WHERE t.is_deleted = ? 
            ORDER BY t.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$view_archived]);
} else {
    $sql = "SELECT t.*, 
            CASE 
                WHEN t.assigned_to = ? THEN 'You (Assignee)' 
                WHEN t.created_by = ? THEN 'You (Creator)' 
            END as relation
            FROM tickets t 
            WHERE (t.assigned_to = ? OR t.created_by = ?) 
            AND t.is_deleted = ? 
            ORDER BY t.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $view_archived]);
}
$tickets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CRM System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #1e293b; padding-bottom: 60px; }

        .navbar { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 16px 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .navbar h2 { font-size: 22px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .nav-right { display: flex; align-items: center; gap: 24px; }
        .user-info { font-size: 14px; color: #64748b; border-left: 2px solid #e2e8f0; padding-left: 24px; display: flex; align-items: center; gap: 15px;}
        .user-info strong { color: #0f172a; font-weight: 600; }
        
        .admin-btn { background: #e0e7ff; color: #4338ca; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 14px; transition: all 0.2s; }
        .admin-btn:hover { background: #c7d2fe; }

        .btn-create { background: linear-gradient(135deg, #4f46e5, #3b82f6); color: #fff; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: transform 0.2s, box-shadow 0.2s; }
        .btn-create:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4); }

        .logout-btn { color: #ef4444; text-decoration: none; font-weight: 600; font-size: 14px; transition: color 0.2s; }
        .logout-btn:hover { color: #b91c1c; }

        .container { max-width: 1200px; margin: 40px auto 0; padding: 0 20px; }
        .action-bar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
        .action-bar h1 { font-size: 28px; font-weight: 700; color: #0f172a; margin-bottom: 15px; }

        /* Modern Tabs */
        .tabs { display: flex; gap: 10px; }
        .tab { padding: 8px 18px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; background: #e2e8f0; color: #64748b; }
        .tab.active { background: #0f172a; color: #ffffff; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .tab:hover:not(.active) { background: #cbd5e1; color: #1e293b; }

        .card { background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #f1f5f9; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; text-align: left; padding: 18px 24px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        td { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; transition: background 0.2s; }
        tr:hover td { background: #f8fafc; }
        tr:last-child td { border-bottom: none; }

        .ticket-id { font-weight: 700; color: #94a3b8; }
        .ticket-subject { font-weight: 600; color: #0f172a; font-size: 15px; }
        
        .badge { padding: 6px 12px; border-radius: 99px; font-size: 12px; font-weight: 700; display: inline-block; }
        .badge-pending { background: #fef3c7; color: #b45309; }
        .badge-inprogress { background: #e0e7ff; color: #4338ca; }
        .badge-completed { background: #d1fae5; color: #059669; }

        .action-link { text-decoration: none; font-weight: 600; font-size: 13px; margin-right: 16px; transition: color 0.2s; }
        .link-view { color: #4f46e5; }
        .link-view:hover { color: #312e81; }
        .link-assign { color: #059669; }
        .link-assign:hover { color: #064e3b; }
        .link-delete { color: #ef4444; }
        .link-delete:hover { color: #991b1b; }
        .link-restore { color: #d97706; } /* Orange for restore */
        .link-restore:hover { color: #92400e; }

        .empty-state { padding: 60px 20px; text-align: center; color: #64748b; }
        .empty-state h3 { color: #0f172a; font-size: 18px; margin-bottom: 8px; }
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
                <div>Logged in as <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> <span style="color:#94a3b8; font-size: 12px; margin-left: 4px;">(<?php echo ucfirst($user_role); ?>)</span></div>
                <a href="auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="action-bar">
            <div>
                <h1><?php echo $view_archived ? "Archived Tickets" : "Your Workspace"; ?></h1>
                
                <div class="tabs">
                    <a href="index.php" class="tab <?php echo !$view_archived ? 'active' : ''; ?>">Active</a>
                    <a href="index.php?view=archived" class="tab <?php echo $view_archived ? 'active' : ''; ?>">Archived</a>
                </div>
            </div>
            
            <?php if($user_role !== 'admin' && $user_role !== 'staff'): ?>
                <a href="pages/create_ticket.php" class="btn-create">+ Create New Ticket</a>
            <?php endif; ?>
        </div>

        <div class="card">
            <?php if(count($tickets) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Subject</th>
                            <th>Authority Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                        <tr style="<?php echo $view_archived ? 'opacity: 0.7;' : ''; ?>">
                            <td class="ticket-id">#<?php echo $ticket['id']; ?></td>
                            <td class="ticket-subject"><?php echo htmlspecialchars($ticket['name']); ?></td>
                            <td>
                                <?php 
                                    if($user_role == 'admin') {
                                        echo "👤 <span style='font-weight: 500;'>" . ($ticket['assignee_name'] ?? "<span style='color:#94a3b8; font-style: italic;'>Unassigned</span>") . "</span>";
                                    } else {
                                        echo "<span style='font-weight: 500; color: #475569;'>" . $ticket['relation'] . "</span>"; 
                                    }
                                ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $ticket['status'])); ?>">
                                    <?php echo ucfirst($ticket['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="pages/view_ticket.php?id=<?php echo $ticket['id']; ?>" class="action-link link-view">View</a>
                                
                                <?php if($user_role == 'admin'): ?>
                                    <?php if($view_archived): ?>
                                        <a href="pages/restore_ticket.php?id=<?php echo $ticket['id']; ?>" class="action-link link-restore" onclick="return confirm('Restore this ticket to the main dashboard?');">Restore</a>
                                    <?php else: ?>
                                        <a href="pages/assign_ticket.php?id=<?php echo $ticket['id']; ?>" class="action-link link-assign">Assign</a>
                                        <a href="pages/delete_ticket.php?id=<?php echo $ticket['id']; ?>" class="action-link link-delete" onclick="return confirm('Archive this ticket?');">Archive</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No tickets found</h3>
                    <p>There are no <?php echo $view_archived ? "archived" : "active"; ?> tickets in your workspace.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
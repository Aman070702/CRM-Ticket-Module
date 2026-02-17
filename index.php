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

// 2. Fetch User Role (Force Fresh Check from DB)
// This ensures that if you change your role in phpMyAdmin, it updates immediately on refresh.
$role_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$role_stmt->execute([$user_id]);
$user_role = $role_stmt->fetchColumn(); // Returns 'admin' or 'user'

// 3. Fetch Tickets
$sql = "SELECT * FROM tickets WHERE created_by = ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - CRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* 1. Global Reset & Typography */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; color: #1f2937; padding-bottom: 50px; }

        /* 2. Navigation Bar */
        .navbar {
            background: #ffffff;
            padding: 15px 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        .navbar h2 { font-size: 20px; color: #111827; display: flex; align-items: center; gap: 10px; }
        
        /* Right side of Navbar */
        .nav-right { display: flex; align-items: center; gap: 20px; }

        .user-info { font-size: 14px; color: #6b7280; padding-left: 20px; border-left: 1px solid #e5e7eb; }
        .user-info strong { color: #111827; }
        
        .logout-link {
            margin-left: 10px;
            color: #ef4444;
            text-decoration: none;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .logout-link:hover { background: #fee2e2; }

        /* Admin Link Style */
        .admin-btn {
            background-color: #e0e7ff;
            color: #4338ca;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 6px;
            display: inline-block;
            transition: background 0.2s;
        }
        .admin-btn:hover { background-color: #c7d2fe; }

        /* 3. Main Container */
        .container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }

        /* 4. Action Bar (Create Button) */
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-create {
            background-color: #2563eb;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
            transition: all 0.2s;
        }
        .btn-create:hover { background-color: #1d4ed8; transform: translateY(-1px); }

        /* 5. Messages */
        .msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 8px;}
        .msg-green { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }

        /* 6. Modern Table Card */
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        
        thead { background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        th { text-align: left; padding: 16px; font-weight: 600; color: #374151; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; }
        
        td { padding: 16px; border-bottom: 1px solid #f3f4f6; color: #4b5563; font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #f9fafb; }

        /* 7. Status Badges */
        .badge { padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-pending { background: #fffbeb; color: #b45309; }
        .badge-inprogress { background: #eff6ff; color: #1d4ed8; }
        .badge-completed { background: #ecfdf5; color: #047857; }
        .badge-onhold { background: #fef2f2; color: #b91c1c; }

        /* 8. Action Links */
        .action-link { text-decoration: none; font-weight: 500; margin-right: 12px; font-size: 13px; }
        .link-view { color: #4f46e5; }
        .link-view:hover { text-decoration: underline; }
        .link-delete { color: #dc2626; }
        .link-delete:hover { color: #b91c1c; }

        /* Empty State */
        .empty-state { padding: 40px; text-align: center; color: #6b7280; }
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
                Logged in as <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                <a href="auth/logout.php" class="logout-link">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="msg msg-green">✅ Ticket deleted successfully!</div>
        <?php endif; ?>

        <div class="action-bar">
            <h1 style="font-size: 24px; font-weight: 600; color: #111827;">Your Tickets</h1>
            <a href="pages/create_ticket.php" class="btn-create">+ Create New Ticket</a>
        </div>

        <div class="card">
            <?php if(count($tickets) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th width="10%">ID</th>
                            <th width="40%">Subject</th>
                            <th width="15%">Status</th>
                            <th width="20%">Date Created</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><strong>#<?php echo $ticket['id']; ?></strong></td>
                            <td style="color: #111827; font-weight: 500;"><?php echo htmlspecialchars($ticket['name']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $ticket['status']; ?>">
                                    <?php echo ucfirst($ticket['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date("M d, Y", strtotime($ticket['created_at'])); ?></td>
                            <td>
                                <a href="pages/view_ticket.php?id=<?php echo $ticket['id']; ?>" class="action-link link-view">View</a>
                                <a href="pages/delete_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                   class="action-link link-delete"
                                   onclick="return confirm('⚠️ Delete this ticket permanently?');">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>You haven't created any support tickets yet.</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

</body>
</html>
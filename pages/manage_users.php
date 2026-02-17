<?php
// pages/manage_users.php
session_start();
require '../config/db.php';

// 1. Security Check: Login & Admin Role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Fetch current user to check role securely
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

if ($current_user['role'] !== 'admin') {
    die("❌ Access Denied: You must be an Administrator to view this page.");
}

// 2. Fetch All Users
$sql = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC";
$users = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Users</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; padding: 40px; }
        .container { max-width: 900px; margin: auto; }
        .card { background: white; padding: 0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden; }
        
        /* Header */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-back { text-decoration: none; color: #4b5563; font-weight: 500; }
        
        /* Table Styles */
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; padding: 16px; text-align: left; font-size: 13px; color: #374151; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; }
        td { padding: 16px; border-bottom: 1px solid #f3f4f6; color: #4b5563; font-size: 14px; }
        
        /* Badges */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-admin { background: #e0e7ff; color: #3730a3; }
        .badge-user { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>👥 User Management</h2>
        <a href="../index.php" class="btn-back">← Back to Dashboard</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?php echo $u['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $u['role']; ?>">
                            <?php echo ucfirst($u['role']); ?>
                        </span>
                    </td>
                    <td><?php echo date("M d, Y", strtotime($u['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
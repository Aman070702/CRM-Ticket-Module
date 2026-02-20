<?php
// pages/manage_users.php
session_start();
require '../config/db.php';

// 1. Security Check: Only Admins allowed
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$role_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$role_stmt->execute([$admin_id]);
$user_role = $role_stmt->fetchColumn();

if ($user_role !== 'admin') {
    die("Access Denied: Only administrators can manage users.");
}

// 2. Handle Role Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_role'])) {
    $target_user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];

    // Prevent admin from accidentally demoting themselves
    if ($target_user_id == $admin_id) {
        $error = "You cannot change your own role.";
    } else {
        $update_stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $update_stmt->execute([$new_role, $target_user_id]);
        $success = "User role updated successfully!";
    }
}

// 3. Fetch all users
$stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Team - CRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; padding: 40px; color: #1f2937; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f9fafb; text-align: left; padding: 16px; font-weight: 600; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb; }
        td { padding: 16px; border-bottom: 1px solid #f3f4f6; font-size: 14px; vertical-align: middle; }
        
        select { padding: 8px; border-radius: 6px; border: 1px solid #d1d5db; font-size: 14px; }
        .btn-update { background: #2563eb; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: 500; margin-left: 8px; }
        .btn-update:hover { background: #1d4ed8; }
        
        .badge { padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .role-admin { background: #fee2e2; color: #991b1b; }
        .role-staff { background: #e0e7ff; color: #4338ca; }
        .role-user { background: #f3f4f6; color: #4b5563; }

        .msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .msg-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .msg-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        
        .back-link { display: inline-block; margin-bottom: 20px; color: #4f46e5; text-decoration: none; font-weight: 500; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <a href="../index.php" class="back-link">← Back to Dashboard</a>
    
    <div class="header">
        <h1>👥 Manage Users & Roles</h1>
    </div>

    <?php if(!empty($success)): ?>
        <div class="msg msg-success">✅ <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="msg msg-error">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Current Role</th>
                    <th>Change Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td style="font-weight: 500;"><?php echo htmlspecialchars($u['name']); ?></td>
                    <td style="color: #6b7280;"><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <span class="badge role-<?php echo strtolower($u['role'] ? $u['role'] : 'user'); ?>">
                            <?php echo htmlspecialchars($u['role'] ? $u['role'] : 'user'); ?>
                        </span>
                    </td>
                    <td>
                        <?php if($u['id'] == $admin_id): ?>
                            <span style="color: #9ca3af; font-size: 13px;">Cannot edit yourself</span>
                        <?php else: ?>
                            <form method="POST" style="display: flex; align-items: center;">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <select name="new_role">
                                    <option value="user" <?php if($u['role'] == 'user') echo 'selected'; ?>>User (Client)</option>
                                    <option value="staff" <?php if($u['role'] == 'staff') echo 'selected'; ?>>Staff (Assignee)</option>
                                    <option value="admin" <?php if($u['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                                </select>
                                <button type="submit" name="update_role" class="btn-update">Save</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Remove POST data on refresh so the success message disappears nicely
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>

</body>
</html>
<?php
// pages/manage_users.php
session_start();
require '../config/db.php';

// 1. Security Check: ONLY Admins allowed
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$role_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$role_stmt->execute([$admin_id]);
$user_role = $role_stmt->fetchColumn();

if ($user_role !== 'admin') {
    die("Access Denied: Only administrators can create staff or manage users.");
}

$success = "";
$error = "";

// 2. Handle ADD NEW STAFF form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_staff'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'staff'; 

    $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->execute([$email]);
    
    if ($check_stmt->rowCount() > 0) {
        $error = "A user with this email already exists!";
    } else {
        $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
        $insert_stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        if ($insert_stmt->execute([$name, $email, $hashed_pw, $role])) {
            $success = "Successfully created new STAFF member: " . htmlspecialchars($name);
        } else {
            $error = "Database error. Could not create staff member.";
        }
    }
}

// 3. Handle USER DELETION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $target_user_id = $_POST['user_id'];

    // Get the target user's details before deleting
    $check_target = $pdo->prepare("SELECT name, role FROM users WHERE id = ?");
    $check_target->execute([$target_user_id]);
    $target_user = $check_target->fetch();

    // SECURITY LAYER: Prevent Admin from deleting themselves or other Admins
    if ($target_user_id == $admin_id) {
        $error = "You cannot delete your own account.";
    } elseif ($target_user && $target_user['role'] === 'admin') {
        $error = "Security Error: You cannot delete an Admin account.";
    } else {
        try {
            // Step A: Safety Cleanup - Unassign any tickets currently assigned to this staff member
            $cleanup_stmt = $pdo->prepare("UPDATE tickets SET assigned_to = NULL WHERE assigned_to = ?");
            $cleanup_stmt->execute([$target_user_id]);

            // Step B: Delete the actual user account
            $delete_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $delete_stmt->execute([$target_user_id]);
            
            $success = "User '" . htmlspecialchars($target_user['name']) . "' has been permanently deleted.";
        } catch (PDOException $e) {
            $error = "Could not delete user. They may be deeply linked to existing system records.";
        }
    }
}

// 4. Fetch all users
$stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Team - CRM System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #1e293b; padding: 40px 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 28px; font-weight: 700; color: #0f172a; }
        
        .back-link { display: inline-flex; align-items: center; margin-bottom: 20px; color: #4f46e5; text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .back-link:hover { color: #312e81; }

        .card { background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); padding: 30px; margin-bottom: 30px; border: 1px solid #e2e8f0; }
        .card h2 { font-size: 18px; margin-bottom: 20px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .input-group label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 12px 16px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; transition: all 0.2s; }
        input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); background: #ffffff; }

        .fixed-role-badge { padding: 12px 16px; background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; border-radius: 8px; font-weight: 600; font-size: 14px; text-align: center; }

        .btn-submit { background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3); }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f8fafc; text-align: left; padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; color: #334155; }
        tr:hover td { background: #f8fafc; }

        .badge { padding: 6px 12px; border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .role-admin { background: #fee2e2; color: #991b1b; }
        .role-staff { background: #e0e7ff; color: #4338ca; }
        .role-user { background: #f1f5f9; color: #475569; }

        /* Delete Button Styling */
        .btn-delete { background: #ef4444; color: white; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; transition: background 0.2s; }
        .btn-delete:hover { background: #dc2626; box-shadow: 0 4px 6px rgba(220, 38, 38, 0.2); }

        .msg { padding: 16px; border-radius: 8px; margin-bottom: 25px; font-size: 14px; font-weight: 500; }
        .msg-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .msg-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        
        .locked-admin { color: #ef4444; font-size: 13px; font-style: italic; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; }
    </style>
</head>
<body>

<div class="container">
    <a href="../index.php" class="back-link">← Return to Dashboard</a>
    
    <div class="header">
        <h1>👥 Manage Team & Users</h1>
    </div>

    <?php if(!empty($success)): ?>
        <div class="msg msg-success">✅ <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="msg msg-error">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>+ Create New Staff</h2>
        <form method="POST" action="">
            <div class="form-grid">
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Your Name " required>
                </div>
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="123@gmail.com" required>
                </div>
                <div class="input-group">
                    <label>Temporary Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="input-group">
                    <label>Assigned Role</label>
                    <div class="fixed-role-badge">Staff Member</div>
                </div>
            </div>
            <button type="submit" name="add_staff" class="btn-submit">Create Staff Account</button>
        </form>
    </div>

    <div class="card" style="padding: 0;">
        <h2 style="padding: 30px 30px 0 30px; border-bottom: none;">Current Accounts</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td style="font-weight: 600;"><?php echo htmlspecialchars($u['name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <span class="badge role-<?php echo strtolower($u['role'] ? $u['role'] : 'user'); ?>">
                            <?php echo htmlspecialchars($u['role'] ? $u['role'] : 'user'); ?>
                        </span>
                    </td>
                    <td>
                        <?php if($u['id'] == $admin_id): ?>
                            <span style="color: #94a3b8; font-size: 13px; font-style: italic;">Cannot modify yourself</span>
                        <?php elseif($u['role'] === 'admin'): ?>
                            <span class="locked-admin">🔒 Locked: Admin Account</span>
                        <?php else: ?>
                            <form method="POST" style="display: flex; align-items: center;" onsubmit="return confirm('WARNING: Are you sure you want to permanently delete this user? This action cannot be undone.');">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" name="delete_user" class="btn-delete">Delete Account</button>
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
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>

</body>
</html>
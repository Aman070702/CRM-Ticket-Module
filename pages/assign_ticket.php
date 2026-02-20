<?php
session_start();
require '../config/db.php';

// 1. Security: Only Admins can grant authority
$user_id = $_SESSION['user_id'];
$role_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$role_stmt->execute([$user_id]);
$user_role = $role_stmt->fetchColumn();

if ($user_role !== 'admin') {
    die("Access Denied: Only Admins can assign authority.");
}

$ticket_id = $_GET['id'] ?? null;

// 2. Handle the Assignment Action
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id = $_POST['staff_id'];
    $stmt = $pdo->prepare("UPDATE tickets SET assigned_to = ? WHERE id = ?");
    $stmt->execute([$staff_id, $ticket_id]);
    
    header("Location: ../index.php?msg=assigned");
    exit;
}

// 3. Get Ticket details
$t_stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$t_stmt->execute([$ticket_id]);
$ticket = $t_stmt->fetch();

// 4. Get all Staff members to populate the dropdown
$u_stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'staff'");
$u_stmt->execute();
$staff_members = $u_stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Authority</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .assign-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { margin-bottom: 10px; font-size: 20px; }
        .ticket-info { color: #6b7280; font-size: 14px; margin-bottom: 20px; }
        select, button { width: 100%; padding: 12px; margin-top: 10px; border-radius: 8px; border: 1px solid #d1d5db; }
        button { background: #2563eb; color: white; border: none; font-weight: 600; cursor: pointer; margin-top: 20px; }
        button:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="assign-card">
        <h2>Assign Authority</h2>
        <div class="ticket-info">Assigning Staff to Ticket #<?php echo $ticket['id']; ?>: <strong><?php echo htmlspecialchars($ticket['name']); ?></strong></div>
        
        <form method="POST">
            <label style="font-size: 14px; font-weight: 600;">Select Staff Member:</label>
            <select name="staff_id" required>
                <option value="">-- Choose Staff --</option>
                <?php foreach($staff_members as $staff): ?>
                    <option value="<?php echo $staff['id']; ?>" <?php echo ($ticket['assigned_to'] == $staff['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($staff['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Grant Authority</button>
        </form>
        <a href="../index.php" style="display:block; text-align:center; margin-top:15px; font-size:13px; color:#6b7280; text-decoration:none;">Cancel</a>
    </div>
</body>
</html>
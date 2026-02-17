<?php
// pages/edit_ticket.php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../index.php");
    exit;
}

$ticket_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 1. Fetch the Ticket
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("❌ Error: Ticket not found.");
}

// 2. Check Permissions
$is_author = ($ticket['created_by'] == $user_id);
$is_assignee = ($ticket['assigned_to'] == $user_id);

if (!$is_author && !$is_assignee) {
    die("❌ Access Denied: You cannot edit this ticket.");
}

// 3. Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];
    $completed_at = ($status == 'completed') ? date("Y-m-d H:i:s") : NULL;

    // --- SCENARIO A: AUTHOR UPDATE ---
    if ($is_author) {
        $name = $_POST['name'];
        $desc = $_POST['description'];
        $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : NULL;

        // SQL: Added 'updated_at = NOW()' to force the date update
        $sql = "UPDATE tickets SET name=?, description=?, assigned_to=?, status=?, completed_at=?, updated_at=NOW() WHERE id=?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$name, $desc, $assigned_to, $status, $completed_at, $ticket_id])) {
            header("Location: ../index.php");
            exit;
        }
    } 
    // --- SCENARIO B: ASSIGNEE UPDATE ---
    elseif ($is_assignee) {
        // SQL: Added 'updated_at = NOW()' here too
        $sql = "UPDATE tickets SET status=?, completed_at=?, updated_at=NOW() WHERE id=?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$status, $completed_at, $ticket_id])) {
            header("Location: ../index.php");
            exit;
        }
    }
}

// 4. Fetch All Users (for Dropdown)
$users_stmt = $pdo->query("SELECT id, name FROM users");
$all_users = $users_stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Ticket #<?php echo $ticket['id']; ?></title>
    <style>
        body { font-family: sans-serif; padding: 40px; background: #f9f9f9; }
        .container { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        label { font-weight: bold; display: block; margin-top: 15px; }
        input, textarea, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { margin-top: 20px; padding: 12px 20px; background: #28a745; color: white; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; width: 100%; }
        button:hover { background: #218838; }
        .disabled-field { background: #e9ecef; color: #6c757d; pointer-events: none; }
        .btn-cancel { display:block; text-align:center; margin-top:15px; text-decoration: none; color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Ticket #<?php echo $ticket['id']; ?></h2>
        
        <form method="POST">
            
            <label>Subject:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($ticket['name']); ?>" 
                   <?php echo $is_author ? '' : 'readonly class="disabled-field"'; ?>>

            <label>Description:</label>
            <textarea name="description" rows="5" <?php echo $is_author ? '' : 'readonly class="disabled-field"'; ?>><?php echo htmlspecialchars($ticket['description']); ?></textarea>

            <label>Assign To:</label>
            <select name="assigned_to" <?php echo $is_author ? '' : 'disabled class="disabled-field"'; ?>>
                <option value="">-- Unassigned --</option>
                <?php foreach($all_users as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo ($ticket['assigned_to'] == $u['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($u['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Status:</label>
            <select name="status">
                <option value="pending" <?php echo ($ticket['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="inprogress" <?php echo ($ticket['status'] == 'inprogress') ? 'selected' : ''; ?>>In Progress</option>
                <option value="completed" <?php echo ($ticket['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                <option value="onhold" <?php echo ($ticket['status'] == 'onhold') ? 'selected' : ''; ?>>On Hold</option>
            </select>

            <br>
            <button type="submit">Save Changes</button>
            <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>
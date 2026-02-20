<?php
// pages/create_ticket.php
session_start();
require '../config/db.php';

// 1. Basic Security: Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// 2. Role Security: Block Admins and Staff from accessing this page directly
$role_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$role_stmt->execute([$_SESSION['user_id']]);
$user_role = $role_stmt->fetchColumn();

if ($user_role === 'admin' || $user_role === 'staff') {
    die("Access Denied: Only regular users (clients) can create tickets.");
}

// 3. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $created_by = $_SESSION['user_id'];
    $file_path = null;

    // Handle the File Upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $upload_dir = '../uploads/';
        
        // Generate a unique name so files with the same name don't overwrite each other
        $file_name = time() . '_' . basename($_FILES['attachment']['name']);
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_path)) {
            $file_path = $file_name; // Save the file name to insert into the database
        }
    }

    // Insert into DB
    $sql = "INSERT INTO tickets (name, description, created_by, file_path) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $description, $created_by, $file_path]);

    header("Location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Ticket - CRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; padding: 40px; }
        .container { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 15px; font-weight: 600; color: #374151; }
        input[type="text"], textarea, input[type="file"] { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; }
        button { margin-top: 25px; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; width: 100%; font-weight: 600; }
        button:hover { background: #1d4ed8; }
        .back { display: block; text-align: center; margin-top: 20px; color: #6b7280; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Raise a New Ticket</h2>
    
    <form method="POST" enctype="multipart/form-data">
        <label>Subject</label>
        <input type="text" name="name" required placeholder="Short description of the issue">

        <label>Details</label>
        <textarea name="description" rows="5" required placeholder="Provide more details..."></textarea>

        <label>Attach a File or Screenshot (Optional)</label>
        <input type="file" name="attachment">

        <button type="submit">Submit Ticket</button>
    </form>
    
    <a href="../index.php" class="back">← Cancel</a>
</div>

</body>
</html>
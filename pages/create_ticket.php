<?php
// pages/create_ticket.php
session_start();
require '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];

    // Insert into Database
    $sql = "INSERT INTO tickets (name, description, status, created_by) VALUES (?, ?, 'pending', ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$name, $description, $user_id])) {
        // Redirect back to dashboard on success
        header("Location: ../index.php");
        exit;
    } else {
        $message = "Error creating ticket.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Ticket</title>
    <style>
        body { font-family: sans-serif; padding: 40px; background: #f4f4f4; }
        .container { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;}
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 4px;}
        .back-link { display: inline-block; margin-top: 15px; color: #555; text-decoration: none;}
    </style>
</head>
<body>
    <div class="container">
        <h2>Submit a Support Ticket</h2>
        <p style="color:red;"><?php echo $message; ?></p>
        
        <form method="POST" action="">
            <label><strong>Subject:</strong></label>
            <input type="text" name="name" required placeholder="Brief summary of the issue">
            
            <label><strong>Description:</strong></label>
            <textarea name="description" rows="6" required placeholder="Explain the details..."></textarea>
            
            <button type="submit">Submit Ticket</button>
        </form>

        <a href="../index.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
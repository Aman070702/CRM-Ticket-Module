<?php
// auth/register.php
session_start();
require '../config/db.php'; // Connect to the database

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $message = "<p style='color:red;'>Error: Email already exists!</p>";
    } else {
        // 2. Hash the password (Security Best Practice)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 3. Insert user into Database
        $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$name, $email, $hashed_password])) {
            $message = "<p style='color:green;'>Success! <a href='login.php'>Click here to Login</a></p>";
        } else {
            $message = "<p style='color:red;'>Error: Could not register user.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - CRM Ticket</title>
    <style>
        body { font-family: sans-serif; padding: 40px; background: #f4f4f4; }
        .container { max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; cursor: pointer; }
        button:hover { background: #218838; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Create Account</h2>
        <?php echo $message; ?>
        <form method="POST" action="">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
<?php
// auth/login.php
session_start();
require '../config/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Find the user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 2. Verify the password
    if ($user && password_verify($password, $user['password'])) {
        // Success! Save user info in Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        // Redirect to Dashboard (Home)
        header("Location: ../index.php");
        exit;
    } else {
        $error = "<p style='color:red;'>Invalid email or password!</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - CRM Ticket</title>
    <style>
        body { font-family: sans-serif; padding: 40px; background: #f4f4f4; }
        .container { max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php echo $error; ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p>No account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>
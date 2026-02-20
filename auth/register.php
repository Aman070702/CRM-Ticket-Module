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
        
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; font-size: 16px; }
        button:hover { background: #218838; }

        /* Password Wrapper Styles */
        .password-wrapper { position: relative; display: flex; align-items: center; width: 100%; margin: 10px 0; }
        .password-wrapper input { margin: 0; padding-right: 40px; /* Make space for the icon */ }
        
        .eye-btn {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            background: transparent; border: none; padding: 0; margin: 0;
            cursor: pointer; color: #6c757d; width: auto; display: flex; align-items: center;
        }
        .eye-btn:hover { background: transparent; color: #28a745; }
        .eye-btn svg { width: 20px; height: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Create Account</h2>
        <?php echo $message; ?>
        
        <form method="POST" action="">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <button type="button" id="togglePassword" class="eye-btn" aria-label="Toggle password visibility">
                    <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
            </div>

            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');
        const eyeIcon = document.querySelector('#eye-icon');

        togglePassword.addEventListener('click', function () {
            // Toggle the type attribute
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Toggle the icon (Eye vs. Eye-slash)
            if (type === 'password') {
                // Normal Eye Icon
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                `;
            } else {
                // Slashed Eye Icon
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                `;
            }
        });
    </script>
</body>
</html>
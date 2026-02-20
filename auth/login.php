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
        $error = "<div class='error-msg'>Invalid email or password!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CRM System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            /* Modern smooth gradient background */
            background: linear-gradient(135deg, #f0f9ff 0%, #c7d2fe 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            padding: 20px;
        }

        .container { 
            width: 100%; 
            max-width: 420px; 
            background: #ffffff; 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05), 0 20px 48px rgba(0, 0, 0, 0.05); 
            text-align: center;
        }
        
        .brand { font-size: 28px; margin-bottom: 10px; }
        
        h2 { color: #111827; font-weight: 700; font-size: 24px; margin-bottom: 8px; }
        .subtitle { color: #6b7280; font-size: 14px; margin-bottom: 30px; }

        .error-msg { 
            background: #fef2f2; color: #991b1b; padding: 12px; 
            border-radius: 8px; font-size: 14px; margin-bottom: 20px; 
            border: 1px solid #fecaca; font-weight: 500;
        }

        .input-group { text-align: left; margin-bottom: 20px; }
        .input-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        
        input { 
            width: 100%; padding: 14px 16px; 
            background: #f9fafb; border: 1px solid #e5e7eb; 
            border-radius: 8px; font-size: 15px; color: #1f2937;
            transition: all 0.3s ease;
        }
        input:focus { 
            outline: none; border-color: #6366f1; 
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15); 
            background: #ffffff;
        }

        /* Password Wrapper Styles */
        .password-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
        .password-wrapper input { padding-right: 45px; }
        
        .eye-btn {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: transparent; border: none; padding: 0; margin: 0;
            cursor: pointer; color: #9ca3af; display: flex; align-items: center;
            transition: color 0.2s;
        }
        .eye-btn:hover { color: #4f46e5; }
        .eye-btn svg { width: 22px; height: 22px; }

        button[type="submit"] { 
            width: 100%; padding: 14px; margin-top: 10px;
            /* Gradient button */
            background: linear-gradient(to right, #4f46e5, #3b82f6); 
            color: white; border: none; border-radius: 8px; 
            font-size: 16px; font-weight: 600; cursor: pointer; 
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button[type="submit"]:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3); 
        }

        .footer-text { margin-top: 25px; font-size: 14px; color: #6b7280; }
        .footer-text a { color: #4f46e5; text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .footer-text a:hover { color: #3730a3; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="container">
        <div class="brand">🚀</div>
        <h2>Welcome Back</h2>
        <p class="subtitle">Enter your credentials to access the CRM.</p>

        <?php echo $error; ?>
        
        <form method="POST" action="">
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="123@gmail.com" required>
            </div>
            
            <div class="input-group">
                <label>Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                    <button type="button" id="togglePassword" class="eye-btn" aria-label="Toggle password visibility">
                        <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit">Sign In</button>
        </form>
        
        <p class="footer-text">Don't have an account? <a href="register.php">Register here</a></p>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');
        const eyeIcon = document.querySelector('#eye-icon');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            if (type === 'password') {
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                `;
            } else {
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                `;
            }
        });
    </script>
</body>
</html>
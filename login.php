<?php
session_start();
require_once 'config/database.php';
require_once 'admin/auth.php';

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if(isLoggedIn()) {
    header('Location: admin.php');
    exit();
}

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if(login($email, $password)) {
        header('Location: admin.php');
        exit();
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - SRMS</title>
    <link rel="icon" type="image/x-icon" href="assets/fabicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-attachment: fixed;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
        }
        .login-card {
            background: linear-gradient(145deg, #1e1e2e 0%, #2a2a40 100%);
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(138, 43, 226, 0.3), 0 5px 15px rgba(0, 0, 0, 0.4);
            padding: 3rem 2.5rem;
            border: 1px solid rgba(102, 126, 234, 0.2);
            backdrop-filter: blur(10px);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .login-logo i {
            font-size: 4rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
        }
        .login-logo h4 {
            color: #e8e8e8;
            margin-top: 1rem;
            font-weight: 600;
        }
        .login-logo p {
            color: #9a9aa0;
            font-size: 0.95rem;
        }
        .form-label {
            color: #e8e8e8;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .form-control {
            background: linear-gradient(145deg, #2a2a40, #1e1e2e);
            border: 2px solid rgba(102, 126, 234, 0.3);
            color: #e8e8e8;
            padding: 0.85rem 1.2rem;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            background: linear-gradient(145deg, #2a2a40, #1e1e2e);
            border-color: #667eea;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25), 0 0 20px rgba(102, 126, 234, 0.3);
            transform: translateY(-2px);
        }
        .form-control::placeholder {
            color: #9a9aa0;
        }
        .input-group-text {
            background: linear-gradient(145deg, #2a2a40, #1e1e2e);
            border: 2px solid rgba(102, 126, 234, 0.3);
            color: #667eea;
            border-right: none;
        }
        .input-group .form-control {
            border-left: none;
        }
        .btn-login {
            padding: 0.85rem 1.2rem;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6), 0 0 20px rgba(118, 75, 162, 0.4);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
        .alert {
            background: linear-gradient(145deg, rgba(220, 53, 69, 0.15), rgba(220, 53, 69, 0.1));
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
            border-radius: 10px;
            padding: 1rem;
        }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .back-link a:hover {
            color: #764ba2;
            text-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
        }
        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }
        .forgot-password a {
            color: #9a9aa0;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .forgot-password a:hover {
            color: #667eea;
        }
        .demo-credentials {
            background: linear-gradient(145deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1.5rem;
        }
        .demo-credentials h6 {
            color: #667eea;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .demo-credentials p {
            color: #c0c0c0;
            font-size: 0.8rem;
            margin: 0.25rem 0;
            text-align: center;
        }
        .demo-credentials code {
            color: #764ba2;
            background: rgba(102, 126, 234, 0.1);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
        }
        .new-account {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(102, 126, 234, 0.2);
        }
        .new-account p {
            color: #9a9aa0;
            font-size: 0.9rem;
            margin: 0;
        }
        @media (max-width: 576px) {
            .login-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <i class="bi bi-shield-lock-fill"></i>
                <h4>Admin Login</h4>
                <p>Student Result Management System</p>
            </div>

            <?php if($error): ?>
            <div class="alert" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-envelope-fill me-2"></i>Email Address
                    </label>
                    <input type="email" class="form-control" name="email" id="email" placeholder="admin@srms.edu" required autocomplete="email">
                </div>
                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-key-fill me-2"></i>Password
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password" required autocomplete="current-password">
                        <span class="input-group-text" onclick="togglePassword()" style="cursor: pointer;">
                            <i class="bi bi-eye-fill" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>

            <div class="forgot-password">
                <a href="#" onclick="showForgotPasswordMessage(event)">
                    <i class="bi bi-question-circle me-1"></i>Forgot Password?
                </a>
            </div>

            <div class="demo-credentials">
                <h6><i class="bi bi-info-circle me-2"></i>Demo Credentials</h6>
                <p><strong>Email:</strong> <code>admin@srms.edu</code></p>
                <p><strong>Password:</strong> <code>admin123</code></p>
            </div>

            <div class="new-account">
                <p>
                    <i class="bi bi-person-plus-fill me-2"></i>Need an account?
                    <a href="#" onclick="showRegistrationMessage(event)" style="color: #667eea;">Contact Administrator</a>
                </p>
            </div>

            <div class="back-link">
                <a href="index.php">
                    <i class="bi bi-arrow-left me-2"></i>Back to Results
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye-fill');
                toggleIcon.classList.add('bi-eye-slash-fill');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash-fill');
                toggleIcon.classList.add('bi-eye-fill');
            }
        }

        function showForgotPasswordMessage(event) {
            event.preventDefault();
            alert('Please contact the administrator at admin@srms.edu to reset your password.');
        }

        function showRegistrationMessage(event) {
            event.preventDefault();
            alert('Please contact the administrator at admin@srms.edu to request a new account.');
        }

        // Auto-focus email input on page load
        document.getElementById('email').focus();
    </script>
</body>
</html>

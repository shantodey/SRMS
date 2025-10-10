<?php
session_start();
require_once 'config/database.php';
require_once 'admin/auth.php';

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo img {
            width: 80px;
            height: 80px;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
        }
        .btn-login {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            background: #1a73e8;
            border: none;
            font-weight: 500;
        }
        .btn-login:hover {
            background: #1557b0;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <img src="assets/admin.png" alt="SRMS Logo">
            <h4 class="mt-3">Admin Login</h4>
            <p class="text-muted">Student Result Management System</p>
        </div>
        
        <?php if($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <input type="email" class="form-control" name="email" placeholder="Email address" required>
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-login w-100">Sign In</button>
            <p class="text-center mt-3">
                <a href="index.php" class="text-decoration-none">Back to Results</a>
            </p>
        </form>
    </div>
</body>
</html>

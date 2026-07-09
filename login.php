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
$success = '';

// Check if user just logged out
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $success = 'You have been logged out successfully!';
}

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
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <i class="bi bi-shield-lock-fill"></i>
                <h4>Admin Login</h4>
                <p>Student Result Management System</p>
            </div>

            <?php if($success): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

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
                    <div class="password-wrapper">
                        <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <i class="bi bi-eye-fill" id="toggleIcon"></i>
                        </button>
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

            <div class="new-teacher-section">
                <p>Are you a new teacher?</p>
                <button type="button" class="btn-new-teacher" onclick="showContactModal()">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Request Teacher Account</span>
                </button>
            </div>

            <div class="back-link">
                <a href="index.php">
                    <i class="bi bi-arrow-left me-2"></i>Back to Results
                </a>
            </div>
        </div>
    </div>

    <!-- Contact Administrator Modal -->
    <div class="modal-overlay" id="contactModal" onclick="closeModalOnOverlay(event)">
        <div class="modal-content">
            <div class="modal-header">
                <i class="bi bi-envelope-heart-fill"></i>
                <h5>Request Teacher Account</h5>
                <p>Contact the administrator to get started</p>
            </div>
            <div class="modal-body">
                <div class="contact-info">
                    <p><strong><i class="bi bi-envelope-fill me-2"></i>Email:</strong></p>
                    <p><a href="mailto:admin@srms.edu">admin@srms.edu</a></p>
                    <p style="margin-top: 1rem;"><strong><i class="bi bi-info-circle-fill me-2"></i>Instructions:</strong></p>
                    <p style="font-size: 0.9rem; color: #64748b;">Send an email to the administrator with your details (Name, Department, Contact) to request a teacher account.</p>
                </div>
            </div>
            <button class="modal-close-btn" onclick="closeContactModal()">
                <i class="bi bi-check-circle-fill me-2"></i>Got it!
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/login.js"></script>
</body>
</html>

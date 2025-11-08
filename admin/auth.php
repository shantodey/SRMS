<?php
// Don't start session here - it should be started by the calling script
// session_start() is called in login.php and admin.php before including this file

// Include database config only if not already included
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/database.php';
}

function login($email, $password) {
    global $conn;

    // Sanitize inputs
    $email = trim($email);
    $password = trim($password);

    if (empty($email) || empty($password)) {
        return false;
    }

    // First check admin table
    $query = "SELECT * FROM admin WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        error_log("Login error: " . $conn->error);
        return false;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if(password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['user_name'] = $admin['name'];
            $_SESSION['user_type'] = 'admin';
            $_SESSION['profile_picture'] = null; // Admin doesn't have profile picture

            // Keep old session keys for backward compatibility
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['name'];
            return true;
        }
    }

    // If not found in admin, check teachers table (if exists)
    $table_check = $conn->query("SHOW TABLES LIKE 'teachers'");

    if ($table_check && $table_check->num_rows > 0) {
        $query = "SELECT * FROM teachers WHERE email = ? AND status = 'active' LIMIT 1";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result->num_rows === 1) {
                $teacher = $result->fetch_assoc();
                if(password_verify($password, $teacher['password'])) {
                    $_SESSION['user_id'] = $teacher['id'];
                    $_SESSION['user_email'] = $teacher['email'];
                    $_SESSION['user_name'] = $teacher['first_name'] . ' ' . $teacher['last_name'];
                    $_SESSION['user_type'] = 'teacher';
                    $_SESSION['profile_picture'] = $teacher['profile_picture'];
                    return true;
                }
            }
        }
    }

    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isTeacher() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher';
}

function logout() {
    session_destroy();
    header('Location: ../index.php');
    exit();
}
?>

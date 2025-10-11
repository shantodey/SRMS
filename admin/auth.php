<?php
// Don't start session here - it should be started by the calling script
// session_start() is called in login.php and admin.php before including this file

// Include database config only if not already included
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/database.php';
}

function login($email, $password) {
    global $conn;

    $email = mysqli_real_escape_string($conn, $email);
    $password = mysqli_real_escape_string($conn, $password);

    $query = "SELECT * FROM admin WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if(password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['name'];
            return true;
        }
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function logout() {
    session_destroy();
    header('Location: ../index.php');
    exit();
}
?>

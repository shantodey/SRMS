<?php
session_start();
require_once '../config/database.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit();
    }

    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit();
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit();
    }

    // Check if email already exists in admin or teachers table
    $check_admin = $conn->prepare("SELECT id FROM admin WHERE email = ?");
    $check_admin->bind_param("s", $email);
    $check_admin->execute();
    if ($check_admin->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }

    $check_teacher = $conn->prepare("SELECT id FROM teachers WHERE email = ?");
    $check_teacher->bind_param("s", $email);
    $check_teacher->execute();
    if ($check_teacher->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }

    // Handle profile picture upload
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];

        if (!in_array($file_type, $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image format. Only JPG, PNG, and GIF allowed']);
            exit();
        }

        // Check file size (max 2MB)
        if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Image size must be less than 2MB']);
            exit();
        }

        // Generate unique filename
        $extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('teacher_') . '_' . time() . '.' . $extension;
        $upload_path = '../uploads/teacher_profiles/' . $filename;

        // Resize and compress image
        $resized = resizeImage($_FILES['profile_picture']['tmp_name'], $upload_path, 200, 200);

        if ($resized) {
            $profile_picture = $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            exit();
        }
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert teacher
    $stmt = $conn->prepare("INSERT INTO teachers (first_name, last_name, email, password, profile_picture, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("sssss", $first_name, $last_name, $email, $hashed_password, $profile_picture);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Teacher account created successfully',
            'teacher' => [
                'id' => $stmt->insert_id,
                'name' => $first_name . ' ' . $last_name,
                'email' => $email,
                'profile_picture' => $profile_picture
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create teacher account']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// Function to resize and compress image
function resizeImage($source, $destination, $max_width, $max_height) {
    $imageInfo = getimagesize($source);
    if (!$imageInfo) {
        return false;
    }

    list($width, $height, $type) = $imageInfo;

    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);

    // Create image from source
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }

    // Create new image
    $new_image = imagecreatetruecolor($new_width, $new_height);

    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }

    // Resize
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Save compressed image
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($new_image, $destination, 85); // 85% quality
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($new_image, $destination, 8); // Compression level 8
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($new_image, $destination);
            break;
        default:
            $result = false;
    }

    imagedestroy($image);
    imagedestroy($new_image);

    return $result;
}
?>

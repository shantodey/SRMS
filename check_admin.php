<?php
require_once 'config/database.php';

echo "<pre>";
echo "=== ADMIN TABLE CHECK ===\n\n";

// Check if admin table exists
$result = $conn->query("SHOW TABLES LIKE 'admin'");
if ($result->num_rows > 0) {
    echo "✓ Admin table EXISTS\n\n";

    // Check for admin accounts
    $admins = $conn->query("SELECT id, name, email, created_at FROM admin");
    echo "Admin accounts found: " . $admins->num_rows . "\n\n";

    if ($admins->num_rows > 0) {
        echo "Existing admin accounts:\n";
        echo str_repeat("-", 60) . "\n";
        while ($admin = $admins->fetch_assoc()) {
            echo "ID: " . $admin['id'] . "\n";
            echo "Name: " . $admin['name'] . "\n";
            echo "Email: " . $admin['email'] . "\n";
            echo "Created: " . $admin['created_at'] . "\n";
            echo str_repeat("-", 60) . "\n";
        }
    } else {
        echo "⚠️ No admin accounts found!\n";
        echo "You need to run: http://localhost/SRMS/setup_admin_account.php\n";
    }
} else {
    echo "✗ Admin table DOES NOT EXIST\n\n";
    echo "You need to run: http://localhost/SRMS/setup_admin_account.php\n";
}

echo "\n</pre>";
?>

<?php
session_start();

echo "<pre>";
echo "=== SESSION DEBUG ===\n\n";

echo "Session ID: " . session_id() . "\n\n";

echo "Session Data:\n";
if (empty($_SESSION)) {
    echo "  ✓ Session is EMPTY (logged out)\n";
} else {
    echo "  ✗ Session contains data (still logged in):\n";
    print_r($_SESSION);
}

echo "\n\nSession Variables:\n";
echo "  admin_id: " . (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'NOT SET') . "\n";
echo "  admin_email: " . (isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : 'NOT SET') . "\n";
echo "  admin_name: " . (isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'NOT SET') . "\n";

echo "\n\nCookies:\n";
echo "  Session Cookie: " . (isset($_COOKIE[session_name()]) ? 'EXISTS' : 'NOT SET') . "\n";

echo "\n</pre>";

echo '<a href="login.php">Go to Login</a> | ';
echo '<a href="admin.php">Go to Admin</a> | ';
echo '<a href="admin/logout.php">Logout</a>';
?>

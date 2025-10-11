<?php
// Start the session
session_start();

// Store session ID for debugging
$old_session_id = session_id();

// Unset all of the session variables
$_SESSION = array();

// Delete the session cookie with all possible paths
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    // Try multiple paths to ensure cookie is deleted
    setcookie(session_name(), '', time() - 86400, '/');
    setcookie(session_name(), '', time() - 86400, $params["path"]);
    setcookie(session_name(), '', time() - 86400, '/SRMS/');

    // Also try with domain
    if ($params["domain"]) {
        setcookie(session_name(), '', time() - 86400, '/', $params["domain"]);
    }
}

// Regenerate session ID to ensure old session is invalid
session_regenerate_id(true);

// Destroy the session
session_destroy();

// Start a new clean session to prevent any residual data
session_start();
session_destroy();

// Output buffer handling
while (ob_get_level()) {
    ob_end_clean();
}

// Prevent any caching
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Use JavaScript redirect with cache busting
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
    <script>
        // Clear browser cache
        if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
            window.location.reload();
        }

        // Redirect with timestamp to prevent caching
        window.location.replace('../index.php?logout=1&t=' + new Date().getTime());
    </script>
</head>
<body>
    <p>Logging out... Please wait.</p>
    <noscript>
        <meta http-equiv="refresh" content="0; url=../index.php?logout=1">
    </noscript>
</body>
</html>
<?php
exit();
?>

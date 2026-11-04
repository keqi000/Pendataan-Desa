<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';

// Force destroy session completely
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Clear all session data
$_SESSION = array();

// Delete session cookie manually
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear any other cookies
setcookie('PENDATAAN_DESA_SESSION', '', time() - 42000, '/');

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Redirect to login
header('Location: ' . BASE_URL . 'pages/auth/login.php');
exit();
?>
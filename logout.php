<?php
// logout.php - Fixed version (no Guzzle required)
include 'db.php';

// Clear session
$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

session_destroy();
header("Location: index.php");
exit();
?>
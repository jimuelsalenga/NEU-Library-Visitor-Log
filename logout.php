<?php
include 'db.php';

// Revoke Google token if exists
if (isset($_SESSION['access_token'])) {
    try {
        $provider = getGoogleProvider();
        // Attempt to revoke token
        $httpClient = new \GuzzleHttp\Client();
        $httpClient->post('https://oauth2.googleapis.com/revoke', [
            'form_params' => ['token' => $_SESSION['access_token']],
            'http_errors' => false
        ]);
    } catch (Exception $e) {
        // Log error but continue logout
        error_log("Token revocation failed: " . $e->getMessage());
    }
}

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
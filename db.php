<?php
$conn = new mysqli("localhost", "root", "", "neu_library");
if($conn->connect_error) die("DB Error");

// 1. SET session security settings FIRST
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// 2. THEN start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
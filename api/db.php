<?php
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Live Credentials from your screenshot
$host = 'sql310.infinityfree.com'; 
$user = 'if0_41458553';
$pass = '0YDDBJ8yMEEd'; // Replace with your actual password
$dbname = 'if0_41458553_neu_library';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Admin protection helper functions
function requireAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: index.php?error=unauthorized");
        exit();
    }
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?error=login_required");
        exit();
    }
}
?>
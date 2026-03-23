<?php
// db.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "neu_library";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper function for protected pages
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}
?>
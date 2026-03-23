<?php
// db.php - Refined Session & Connection Logic

// 1. SESSION SECURITY (Must come before session_start)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', false); // Set to true if using HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// 2. Load .env file manually
function loadEnv($path) {
    if (!file_exists($path)) return;
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes
        if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
            (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
            $value = substr($value, 1, -1);
        }
        
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

loadEnv(__DIR__ . '/.env');

// 3. Database connection
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';
$dbName = $_ENV['DB_NAME'] ?? 'neu_library';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// 4. Helper Functions
function hasRole($role) {
    return isset($_SESSION['user_roles']) && is_array($_SESSION['user_roles']) && in_array($role, $_SESSION['user_roles']);
}

function isAdmin() {
    return (isset($_SESSION['admin']) && $_SESSION['admin'] === true) || hasRole('admin');
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: admin_login.php");
        exit();
    }
}

// Placeholder for Google Provider
function getGoogleProvider() {
    throw new Exception("Google OAuth requires Composer. Run: composer require google/apiclient");
}
?>
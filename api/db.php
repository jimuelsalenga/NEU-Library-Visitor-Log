<?php
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * DATABASE CONNECTION
 * Pulling credentials from Vercel Environment Variables
 */
$host = getenv('DB_HOST');
$port = "5432"; // Default Supabase port
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');

try {
    // Supabase uses PostgreSQL, so we use the 'pgsql' driver
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    
    // Create a PDO connection
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Corrected line
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // For compatibility with your existing code
    $conn = $pdo; 

} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Database connection error. Please check back later.");
}

/**
 * AUTHENTICATION HELPERS
 */
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
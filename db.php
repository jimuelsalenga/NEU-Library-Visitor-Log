<?php
// db.php - Complete fixed version
require_once 'vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

// PHP 8.0+ polyfill for str_ends_with (for older PHP versions)
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

// Session security MUST be set BEFORE session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection with error handling
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';
$dbName = $_ENV['DB_NAME'] ?? 'neu_library';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    error_log("Database Connection Failed: " . $conn->connect_error);
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}

$conn->set_charset("utf8mb4");

/**
 * RBAC Helper Functions
 */
function hasRole($role) {
    return isset($_SESSION['user_roles']) && is_array($_SESSION['user_roles']) && in_array($role, $_SESSION['user_roles']);
}

function isAdmin() {
    // Support both old admin session and new RBAC system
    return (isset($_SESSION['admin']) && $_SESSION['admin'] === true) || 
           hasRole('admin');
}

function isUser() {
    return hasRole('user') || isset($_SESSION['user_id']);
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

/**
 * Get or refresh valid access token
 */
function getValidAccessToken($provider) {
    if (!isset($_SESSION['access_token'])) {
        return null;
    }
    
    try {
        $token = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => $_SESSION['access_token'],
            'refresh_token' => $_SESSION['refresh_token'] ?? null,
            'expires' => $_SESSION['token_expires'] ?? (time() - 1)
        ]);
        
        if ($token->hasExpired() && isset($_SESSION['refresh_token'])) {
            $newToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $_SESSION['refresh_token']
            ]);
            
            $_SESSION['access_token'] = $newToken->getToken();
            if ($newToken->getRefreshToken()) {
                $_SESSION['refresh_token'] = $newToken->getRefreshToken();
            }
            $_SESSION['token_expires'] = $newToken->getExpires();
            
            return $newToken;
        }
        
        return $token;
    } catch (Exception $e) {
        error_log("Token error: " . $e->getMessage());
        return null;
    }
}

/**
 * Initialize Google OAuth Provider
 */
function getGoogleProvider() {
    $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
    $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
    $redirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? 'http://localhost/google-callback.php';
    
    if (empty($clientId) || empty($clientSecret)) {
        throw new Exception("Google OAuth credentials not configured");
    }
    
    return new \League\OAuth2\Client\Provider\Google([
        'clientId'     => $clientId,
        'clientSecret' => $clientSecret,
        'redirectUri'  => $redirectUri,
        'accessType'   => 'offline',
        'prompt'       => 'consent select_account'
    ]);
}
?>
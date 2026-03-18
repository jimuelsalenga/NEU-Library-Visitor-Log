<?php
// PHP 8.0+ polyfill for str_ends_with (for older PHP versions)
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

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

// Database connection
$conn = new mysqli(
    $_ENV['DB_HOST'], 
    $_ENV['DB_USER'], 
    $_ENV['DB_PASS'], 
    $_ENV['DB_NAME']
);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/**
 * RBAC Helper Functions
 */
function hasRole($role) {
    return isset($_SESSION['user_roles']) && in_array($role, $_SESSION['user_roles']);
}

function isAdmin() {
    return hasRole('admin');
}

function isUser() {
    return hasRole('user');
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

function requireAdmin() {
    // Support both old admin session and new RBAC system
    $isAdmin = (isset($_SESSION['admin']) && $_SESSION['admin'] === true) || 
               (isset($_SESSION['user_roles']) && in_array('admin', $_SESSION['user_roles']));
    
    if (!$isAdmin) {
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
    
    $token = new \League\OAuth2\Client\Token\AccessToken([
        'access_token' => $_SESSION['access_token'],
        'refresh_token' => $_SESSION['refresh_token'] ?? null,
        'expires' => $_SESSION['token_expires'] ?? time() - 1
    ]);
    
    if ($token->hasExpired() && isset($_SESSION['refresh_token'])) {
        try {
            $newToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $_SESSION['refresh_token']
            ]);
            
            $_SESSION['access_token'] = $newToken->getToken();
            if ($newToken->getRefreshToken()) {
                $_SESSION['refresh_token'] = $newToken->getRefreshToken();
            }
            $_SESSION['token_expires'] = $newToken->getExpires();
            
            return $newToken;
        } catch (Exception $e) {
            // Token refresh failed, require re-login
            return null;
        }
    }
    
    return $token;
}

/**
 * Initialize Google OAuth Provider
 */
function getGoogleProvider() {
    return new \League\OAuth2\Client\Provider\Google([
        'clientId'     => $_ENV['GOOGLE_CLIENT_ID'],
        'clientSecret' => $_ENV['GOOGLE_CLIENT_SECRET'],
        'redirectUri'  => $_ENV['GOOGLE_REDIRECT_URI'],
        'accessType'   => 'offline',
        'prompt'       => 'consent select_account'
    ]);
}
?>
<?php
// admin_login.php
require_once 'db.php';

// Pull credentials from Vercel Environment Variables
$google_client_id = getenv('GOOGLE_CLIENT_ID') ?: '';
$allowed_domain = "neu.edu.ph"; 

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit();
}

$error = '';

// --- 1. HANDLE GOOGLE LOGIN ---
if (isset($_POST['credential'])) {
    $id_token = $_POST['credential'];
    
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    $response = @file_get_contents($url);
    $user_info = json_decode($response, true);

    if ($user_info && isset($user_info['email'])) {
        $email = strtolower($user_info['email']);
        $domain = substr(strrchr($email, "@"), 1);

        if ($domain !== $allowed_domain) {
            $error = "Access Denied: Use your @$allowed_domain account.";
        } else {
            // Check database using PDO
            $stmt = $conn->prepare("SELECT id, username FROM admins WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $admin = $stmt->fetch();

            if ($admin) {
                // Update stats
                $update_stmt = $conn->prepare("UPDATE admins SET login_count = login_count + 1, last_login = NOW() WHERE id = :id");
                $update_stmt->execute(['id' => $admin['id']]);

                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['role'] = 'admin';
                $_SESSION['admin_user'] = $admin['username'];
                header("Location: admin.php");
                exit();
            } else {
                $error = "This NEU account is not registered as an Admin.";
            }
        }
    } else {
        $error = "Google Authentication Failed.";
    }
}

// --- 2. HANDLE MANUAL LOGIN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['u'])) {
    $u = trim($_POST['u']);
    $p = $_POST['p'];

    // Database Check
    $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = :u");
    $stmt->execute(['u' => $u]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Checking both direct match (for your demo) and hashed passwords
        if ($p === $admin['password'] || password_verify($p, $admin['password'])) {
            $update_stmt = $conn->prepare("UPDATE admins SET login_count = login_count + 1, last_login = NOW() WHERE id = :id");
            $update_stmt->execute(['id' => $admin['id']]);
        
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['admin_user'] = $admin['username'];
            header("Location: admin.php");
            exit();
        }
    }
    $error = "Invalid username or password.";
}
?>
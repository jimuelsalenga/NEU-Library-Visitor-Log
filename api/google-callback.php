<?php
// google-callback.php
require_once 'vendor/autoload.php';
require_once 'db.php'; // This already has session_start()

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    // If .env is missing, we use hardcoded values or exit gracefully
}

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? 'YOUR_CLIENT_ID');
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? 'YOUR_CLIENT_SECRET');
$client->setRedirectUri('http://localhost/neu-library/google-callback.php');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (isset($token['error'])) {
        header("Location: index.php?error=auth_failed");
        exit();
    }

    $client->setAccessToken($token);
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    
    $email = strtolower(trim($google_account_info->email));
    $name = $google_account_info->name;
    $google_id = $google_account_info->id;

    // --- 1. DOMAIN LOCKDOWN ---
    $allowed_domain = 'neu.edu.ph';
    $user_domain = substr(strrchr($email, "@"), 1);

    if ($user_domain !== $allowed_domain) {
        session_unset();
        session_destroy();
        header("Location: index.php?error=invalid_domain");
        exit();
    }

    // --- 2. SESSION REFRESH ---
    session_regenerate_id(true); 

    // --- 3. DATABASE CHECK ---
    $stmt = $conn->prepare("SELECT program, college, role FROM users WHERE google_id = ?");
    $stmt->bind_param("s", $google_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();

    // --- 4. ROLE & REDIRECTION LOGIC ---
    $admin_emails = ['admin@neu.edu.ph', 'jcesperanza@neu.edu.ph']; 

    if (in_array($email, $admin_emails)) {
        // ADMIN FLOW
        $_SESSION['user_id'] = $google_id;
        $_SESSION['role'] = 'admin';
        $_SESSION['admin'] = true; // Match admin_login.php key
        $_SESSION['admin_user'] = $name; 
        header("Location: admin.php?msg=welcome_admin");
        exit();
    } else {
        // STUDENT FLOW
        $_SESSION['user_id'] = $google_id;
        $_SESSION['role'] = 'student';
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        
        if (!$user_data) {
            $ins = $conn->prepare("INSERT INTO users (google_id, name, email, role) VALUES (?, ?, ?, 'student')");
            $ins->bind_param("sss", $google_id, $name, $email);
            $ins->execute();
            header("Location: user-details.php?msg=new_account");
        } elseif (empty($user_data['program']) || empty($user_data['college'])) {
            header("Location: user-details.php");
        } else {
            header("Location: dashboard.php?msg=welcome_user");
        }
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
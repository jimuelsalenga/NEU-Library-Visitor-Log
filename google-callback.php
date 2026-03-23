<?php
require_once 'db.php';
require_once 'vendor/autoload.php';

// 1. LOAD ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 2. GOOGLE SETUP
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri('http://localhost/neu-library/google-callback.php');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    
    $email = strtolower($google_account_info->email);
    $name = $google_account_info->name;
    $google_id = $google_account_info->id;

    // --- CRITICAL: CLEAR OLD DATA ---
    session_unset(); 

    // 3. SET NEW SESSION
    $_SESSION['user_id'] = $google_id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;

    // 4. DEFINE ADMINS
    $admin_emails = ['admin@neu.edu.ph', 'jimuel.salenga@neu.edu.ph']; 

    // 5. DATABASE CHECK
    $stmt = $conn->prepare("SELECT program, college, role FROM users WHERE google_id = ?");
    $stmt->bind_param("s", $google_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();

    // 6. THE REDIRECT "FORK"
    if (in_array($email, $admin_emails)) {
        $_SESSION['role'] = 'admin';
        header("Location: admin.php");
        exit();
    } else {
        $_SESSION['role'] = 'student';
        
        // If they don't exist in DB, insert them
        if (!$user_data) {
            $ins = $conn->prepare("INSERT INTO users (google_id, name, email, role) VALUES (?, ?, ?, 'student')");
            $ins->bind_param("sss", $google_id, $name, $email);
            $ins->execute();
            header("Location: user-details.php");
            exit();
        }

        // Check if profile is incomplete
        if (empty($user_data['program']) || empty($user_data['college'])) {
            header("Location: user-details.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    }
}
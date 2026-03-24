<?php
// google-callback.php
require_once 'vendor/autoload.php';
require_once 'db.php'; // Ensure db.php uses PDO as previously updated

/**
 * CONFIGURATION
 * Pulling from Vercel Environment Variables using getenv()
 */
$clientId     = getenv('GOOGLE_CLIENT_ID');
$clientSecret = getenv('GOOGLE_CLIENT_SECRET'); 
$redirectUri  = 'https://neu-library-visitor-log.vercel.app/api/google-callback.php';

$client = new Google_Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

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

    // --- 1. DOMAIN LOCKDOWN (@neu.edu.ph) ---
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

    // --- 3. DATABASE CHECK (Using PDO for Supabase) ---
    $stmt = $conn->prepare("SELECT program, college, role FROM users WHERE google_id = :gid");
    $stmt->execute(['gid' => $google_id]);
    $user_data = $stmt->fetch();

    // --- 4. ROLE & REDIRECTION LOGIC ---
    $admin_emails = ['admin@neu.edu.ph', 'jcesperanza@neu.edu.ph']; 

    if (in_array($email, $admin_emails)) {
        // ADMIN FLOW
        $_SESSION['user_id'] = $google_id;
        $_SESSION['role'] = 'admin';
        $_SESSION['admin'] = true; 
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
            // New Student - INSERT using PDO
            $ins = $conn->prepare("INSERT INTO users (google_id, name, email, role) VALUES (:gid, :name, :email, 'student')");
            $ins->execute([
                'gid'   => $google_id,
                'name'  => $name,
                'email' => $email
            ]);
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
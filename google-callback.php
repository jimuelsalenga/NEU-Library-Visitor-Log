<?php
include 'db.php';

if (!isset($_GET['code'])) {
    header("Location: index.php?error=oauth_failed");
    exit();
}

try {
    $provider = getGoogleProvider();
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);
    
    // Get user details
    $googleUser = $provider->getResourceOwner($token);
    $email = $googleUser->getEmail();
    $name = $googleUser->getName();
    $googleId = $googleUser->getId();
    
    // Verify email domain (NEU only)
    if (!str_ends_with($email, '@neu.edu.ph')) {
        header("Location: index.php?error=invalid_domain");
        exit();
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name, is_blocked FROM users WHERE email = ? OR google_id = ?");
    $stmt->bind_param("ss", $email, $googleId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        
        if ($user['is_blocked']) {
            header("Location: index.php?error=account_blocked");
            exit();
        }
        
        $userId = $user['id'];
        
        // Update Google ID if not set
        $update = $conn->prepare("UPDATE users SET google_id = ?, last_login = NOW() WHERE id = ?");
        $update->bind_param("si", $googleId, $userId);
        $update->execute();
        
    } else {
        // Create new user
        // Determine if admin based on email
        $isAdmin = ($email === $_ENV['ADMIN_EMAIL']) ? 1 : 0;
        $defaultRole = $isAdmin ? 'admin' : 'user';
        
        $insert = $conn->prepare("INSERT INTO users (email, name, google_id, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $insert->bind_param("ssss", $email, $name, $googleId, $defaultRole);
        $insert->execute();
        $userId = $conn->insert_id;
    }
    
    // Get user roles
    $roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $roleStmt->bind_param("i", $userId);
    $roleStmt->execute();
    $roleRes = $roleStmt->get_result();
    $userData = $roleRes->fetch_assoc();
    
    // Set session
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_roles'] = [$userData['role']]; // Can be extended for multiple roles
    
    // Store tokens securely
    $_SESSION['access_token'] = $token->getToken();
    $_SESSION['refresh_token'] = $token->getRefreshToken();
    $_SESSION['token_expires'] = $token->getExpires();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Redirect based on role
    if (isAdmin()) {
        header("Location: admin.php");
    } else {
        header("Location: user-dashboard.php");
    }
    exit();
    
} catch (Exception $e) {
    error_log("Google OAuth Error: " . $e->getMessage());
    header("Location: index.php?error=oauth_error");
    exit();
}
?>
<?php
include 'db.php';

$provider = getGoogleProvider();

// Check for errors
if (isset($_GET['error'])) {
    header("Location: index.php?error=oauth_error&message=" . urlencode($_GET['error']));
    exit();
}

// Validate state to prevent CSRF
if (empty($_GET['state']) || ($_GET['state'] !== ($_SESSION['oauth2state'] ?? ''))) {
    unset($_SESSION['oauth2state']);
    header("Location: index.php?error=oauth_error&message=Invalid state");
    exit();
}

try {
    // Exchange authorization code for access token
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);
    
    // Get user details from Google
    $googleUser = $provider->getResourceOwner($token);
    $email = $googleUser->getEmail();
    $name = $googleUser->getName();
    $googleId = $googleUser->getId();
    
    // Verify email domain (only @neu.edu.ph allowed)
    if (!str_ends_with($email, '@neu.edu.ph')) {
        header("Location: index.php?error=invalid_domain");
        exit();
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR google_id = ?");
    $stmt->bind_param("ss", $email, $googleId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        
        if ($user['is_blocked']) {
            header("Location: index.php?error=account_blocked");
            exit();
        }
        
        // Update Google ID if not set
        if (empty($user['google_id'])) {
            $update = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            $update->bind_param("si", $googleId, $user['id']);
            $update->execute();
        }
        
        $userId = $user['id'];
    } else {
        // Create new user
        $insert = $conn->prepare("INSERT INTO users (name, email, google_id, is_employee) VALUES (?, ?, ?, 0)");
        $insert->bind_param("sss", $name, $email, $googleId);
        $insert->execute();
        $userId = $conn->insert_id;
    }
    
    // Set session variables
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_roles'] = ['user'];
    
    // Store token info
    $_SESSION['access_token'] = $token->getToken();
    $_SESSION['refresh_token'] = $token->getRefreshToken();
    $_SESSION['token_expires'] = $token->getExpires();
    
    // Check if admin
    $adminCheck = $conn->prepare("SELECT id FROM admins WHERE user_id = ?");
    $adminCheck->bind_param("i", $userId);
    $adminCheck->execute();
    if ($adminCheck->get_result()->num_rows > 0) {
        $_SESSION['user_roles'][] = 'admin';
        header("Location: admin.php");
    } else {
        header("Location: user-dashboard.php");
    }
    exit();
    
} catch (Exception $e) {
    error_log("OAuth Error: " . $e->getMessage());
    header("Location: index.php?error=oauth_failed");
    exit();
}
?>
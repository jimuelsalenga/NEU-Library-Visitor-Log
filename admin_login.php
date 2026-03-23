<?php
// admin_login.php
require_once 'db.php';

// Pull credentials from .env
$google_client_id = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
$allowed_domain = "neu.edu.ph"; 

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit();
}

$error = '';

// --- 1. HANDLE GOOGLE LOGIN ---
if (isset($_POST['credential'])) {
    $id_token = $_POST['credential'];
    
    // Using the ID Token to get user info
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    $response = @file_get_contents($url);
    $user = json_decode($response, true);

    if ($user && isset($user['email'])) {
        $email = $user['email'];
        $domain = substr(strrchr($email, "@"), 1);

        if ($domain !== $allowed_domain) {
            $error = "Access Denied: Use your @$allowed_domain account.";
        } else {
            // Check database for this NEU email
            $stmt = $conn->prepare("SELECT id, username FROM admins WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                $admin = $res->fetch_assoc();
                $admin_id = $admin['id'];

                // UPDATE LOGIN COUNT AND TIME FOR GOOGLE USERS
                $update_stmt = $conn->prepare("UPDATE admins SET login_count = login_count + 1, last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $admin_id);
                $update_stmt->execute();

                $_SESSION['user_id'] = $admin_id;
                $_SESSION['role'] = 'admin';
                $_SESSION['admin_user'] = $admin['username'];
                header("Location: admin.php");
                exit();
            } else {
                $error = "This NEU account is not registered as an Admin.";
            }
        }
    } else {
        $error = "Google Authentication Failed. Please try again.";
    }
}

// --- 2. HANDLE MANUAL LOGIN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['u'])) {
    $u = trim($_POST['u']);
    $p = $_POST['p'];

    // Demo Account Logic (Static check)
    if ($u === 'admin' && $p === 'admin321') {
        $_SESSION['user_id'] = 'demo';
        $_SESSION['role'] = 'admin';
        $_SESSION['admin_user'] = 'Demo Admin';
        header("Location: admin.php");
        exit();
    } 
    
    // Database Check for registered admins
    $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $admin = $res->fetch_assoc();
        
        // Note: In production, use password_verify($p, $admin['password'])
        // For now, using direct check to match your current DB setup
        if ($p === $admin['password'] || password_verify($p, $admin['password'])) {
            $admin_id = $admin['id'];
            
            // UPDATE LOGIN COUNT AND TIME FOR MANUAL USERS
            $update_stmt = $conn->prepare("UPDATE admins SET login_count = login_count + 1, last_login = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $admin_id);
            $update_stmt->execute();
        
            $_SESSION['user_id'] = $admin_id;
            $_SESSION['role'] = 'admin';
            $_SESSION['admin_user'] = $admin['username'];
            header("Location: admin.php");
            exit();
        }
    }
    $error = "Invalid username or password.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEU Admin Login</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f0f2f5; margin: 0; }
        .login-card { background: white; padding: 2.5rem; border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 360px; text-align: center; }
        .logo-box { background: #0B5D3B; color: white; width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 18px; margin: 0 auto 20px; font-size: 1.8rem; }
        .error { color: #e74c3c; background: #fdeaea; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid #fadbd8; }
        .divider { margin: 25px 0; border-top: 1px solid #eee; position: relative; }
        .divider span { position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: white; padding: 0 15px; color: #aaa; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.2px; }
        input { width: 100%; padding: 14px; margin: 10px 0; border: 1px solid #e1e8ed; border-radius: 12px; box-sizing: border-box; font-size: 1rem; transition: 0.2s; }
        input:focus { outline: none; border-color: #0B5D3B; box-shadow: 0 0 0 4px rgba(11, 93, 59, 0.1); }
        button { width: 100%; padding: 14px; background: #0B5D3B; color: white; border: none; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 1rem; margin-top: 15px; transition: 0.3s; }
        button:hover { background: #084a2f; transform: translateY(-1px); }
        .demo-info { margin-top: 30px; padding: 18px; background: #f8f9fa; border-radius: 15px; border: 1px dashed #cbd5e0; text-align: left; }
        .demo-info h4 { margin: 0 0 10px 0; font-size: 0.75rem; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; }
        .demo-info p { margin: 6px 0; font-size: 0.9rem; color: #2d3748; }
        .demo-label { color: #a0aec0; width: 85px; display: inline-block; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-box">🏛️</div>
        <h2 style="margin: 0; color: #1a202c;">Admin Portal</h2>
        <p style="color: #718096; font-size: 0.95rem; margin: 8px 0 25px;">NEU Library Management</p>

        <?php if($error): ?> <div class="error"><?= htmlspecialchars($error) ?></div> <?php endif; ?>

        <div id="g_id_onload"
             data-client_id="<?= htmlspecialchars($google_client_id) ?>"
             data-context="signin"
             data-ux_mode="popup"
             data-login_uri="http://localhost/neu-library/admin_login.php"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin" data-type="standard" data-shape="pill" data-theme="outline" data-text="signin_with" data-size="large" data-logo_alignment="left" data-width="360"></div>

        <div class="divider"><span>Or continue with username</span></div>

        <form method="POST" action="admin_login.php">
            <input type="text" name="u" placeholder="Username" required>
            <input type="password" name="p" placeholder="Password" required>
            <button type="submit">Sign In to Dashboard</button>
        </form>

        <div class="demo-info">
            <h4>Developer Credentials:</h4>
            <p><span class="demo-label">Username:</span> <strong>admin</strong></p>
            <p><span class="demo-label">Password:</span> <strong>admin321</strong></p>
        </div>
    </div>
</body>
</html>
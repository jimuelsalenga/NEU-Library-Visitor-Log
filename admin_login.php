<?php
// admin_login.php
require_once 'db.php';

// CONFIGURATION
$google_client_id = "YOUR_CLIENT_ID_HERE.apps.googleusercontent.com";
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
    $user = json_decode($response, true);

    if ($user && isset($user['email'])) {
        $email = $user['email'];
        $domain = substr(strrchr($email, "@"), 1);

        if ($domain !== $allowed_domain) {
            $error = "Access Denied: Use your @$allowed_domain account.";
        } else {
            $stmt = $conn->prepare("SELECT id, username FROM admins WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                $admin = $res->fetch_assoc();
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['role'] = 'admin';
                $_SESSION['admin_user'] = $admin['username'];
                header("Location: admin.php");
                exit();
            } else {
                $error = "Google account not authorized as Admin.";
            }
        }
    }
}

// --- 2. HANDLE MANUAL LOGIN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['u'])) {
    $u = trim($_POST['u']);
    $p = $_POST['p'];

    // Demo Account Logic
    if ($u === 'admin' && $p === 'admin321') {
        $_SESSION['user_id'] = 'demo';
        $_SESSION['role'] = 'admin';
        $_SESSION['admin_user'] = 'Demo Admin';
        header("Location: admin.php");
        exit();
    } 
    
    // Database Check for other admins
    $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $admin = $res->fetch_assoc();
        if (password_verify($p, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['admin_user'] = $admin['username'];
            header("Location: admin.php");
            exit();
        }
    }
    $error = "Invalid credentials.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>NEU Admin Login</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f0f2f5; margin: 0; }
        .login-card { background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 340px; text-align: center; }
        .logo-box { background: #0B5D3B; color: white; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 15px; margin: 0 auto 15px; font-size: 1.5rem; }
        .error { color: #e74c3c; background: #fdeaea; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.85rem; border: 1px solid #fadbd8; }
        .divider { margin: 25px 0; border-top: 1px solid #eee; position: relative; }
        .divider span { position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: white; padding: 0 12px; color: #999; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; }
        input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #e1e8ed; border-radius: 10px; box-sizing: border-box; font-size: 0.95rem; }
        input:focus { outline: none; border-color: #0B5D3B; box-shadow: 0 0 0 3px rgba(11, 93, 59, 0.1); }
        button { width: 100%; padding: 13px; background: #0B5D3B; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; margin-top: 10px; transition: 0.3s; }
        button:hover { background: #084a2f; }
        
        /* Demo Account Styling */
        .demo-info { margin-top: 25px; padding: 15px; background: #f8f9fa; border-radius: 12px; border: 1px dashed #cbd5e0; text-align: left; }
        .demo-info h4 { margin: 0 0 8px 0; font-size: 0.8rem; color: #4a5568; text-transform: uppercase; }
        .demo-info p { margin: 4px 0; font-size: 0.85rem; color: #2d3748; font-family: monospace; }
        .demo-label { color: #718096; width: 80px; display: inline-block; font-family: 'Segoe UI', sans-serif; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-box">🔒</div>
        <h2 style="margin-bottom: 5px; color: #2c3e50;">Admin Portal</h2>
        <p style="color: #7f8c8d; font-size: 0.9rem; margin-bottom: 25px;">Authorized Access Only</p>

        <?php if($error): ?> <div class="error"><?= $error ?></div> <?php endif; ?>

        <div id="g_id_onload"
             data-client_id="<?= $google_client_id ?>"
             data-context="signin"
             data-ux_mode="popup"
             data-login_uri="http://localhost/neu-library/admin_login.php"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin" data-type="standard" data-shape="pill" data-width="340"></div>

        <div class="divider"><span>Or use credentials</span></div>

        <form method="POST">
            <input type="text" name="u" placeholder="Username" required autocomplete="username">
            <input type="password" name="p" placeholder="Password" required autocomplete="current-password">
            <button type="submit">Sign In</button>
        </form>

        <div class="demo-info">
            <h4>Demo Account:</h4>
            <p><span class="demo-label">User:</span> <strong>admin</strong></p>
            <p><span class="demo-label">Password:</span> <strong>admin321</strong></p>
        </div>
    </div>
</body>
</html>
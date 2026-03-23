<?php
// admin_login.php
require_once 'db.php';

// Redirect if already logged in
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $u = trim($_POST['u'] ?? '');
    $p = $_POST['p'] ?? '';

    // Hardcoded Demo Login
    if ($u === 'admin' && $p === 'admin321') {
        $_SESSION['user_id'] = 'demo_admin';
        $_SESSION['role'] = 'admin';
        $_SESSION['admin_user'] = 'System Admin';
        header("Location: admin.php");
        exit();
    }

    // Database Check
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
    $error = "Invalid credentials";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; }
        .login-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 300px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #0B5D3B; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; font-size: 0.8rem; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Admin Login</h2>
        <?php if($error): ?> <div class="error"><?php echo $error; ?></div> <?php endif; ?>
        <form method="POST">
            <input type="text" name="u" placeholder="Username" required>
            <input type="password" name="p" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
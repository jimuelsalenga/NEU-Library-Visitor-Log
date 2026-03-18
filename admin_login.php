<?php
// admin_login.php - Fixed version
include 'db.php';

// If already logged in, redirect
if(isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header("Location: admin.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $u = trim($_POST['u'] ?? '');
    $p = $_POST['p'] ?? '';
    
    if(empty($u) || empty($p)) {
        $error = "Please enter username and password";
    } else {
        // Simple query without user_id
        $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $u);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if($res->num_rows > 0){
            $admin = $res->fetch_assoc();
            
            // Check password (supports both hashed and plaintext)
            $passwordValid = false;
            
            if (password_verify($p, $admin['password'])) {
                $passwordValid = true;
            } elseif (strlen($admin['password']) < 60 && $p === $admin['password']) {
                // Plain text password - verify and rehash
                $passwordValid = true;
                $newHash = password_hash($p, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $updateStmt->bind_param("si", $newHash, $admin['id']);
                $updateStmt->execute();
            }
            
            if ($passwordValid) {
                $_SESSION['admin'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_user'] = $admin['username'];
                $_SESSION['user_name'] = $admin['username'];
                $_SESSION['user_roles'] = ['admin'];
                
                session_regenerate_id(true);
                
                header("Location: admin.php");
                exit();
            } else {
                $error = "Invalid credentials";
            }
        } else {
            $error = "Invalid credentials";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Admin Login | NEU Library</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', system-ui, sans-serif;
            position: relative;
            overflow: hidden;
        }
        
        .bg-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            animation: float 20s infinite ease-in-out;
        }
        .shape-1 { width: 400px; height: 400px; background: #ff6b6b; top: -100px; left: -100px; }
        .shape-2 { width: 300px; height: 300px; background: #4ecdc4; bottom: -50px; right: -50px; animation-delay: 5s; }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }
        
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .brand-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-icon {
            width: 70px;
            height: 70px;
            background: #0B5D3B;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 28px;
            box-shadow: 0 10px 30px rgba(11, 93, 59, 0.3);
        }
        
        h2 { color: #0B5D3B; font-size: 1.8rem; margin-bottom: 8px; }
        .subtitle { color: #666; font-size: 0.9rem; }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #c33;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        input {
            width: 100%;
            padding: 14px 16px 14px 45px;
            border: 2px solid #e1e1e1;
            border-radius: 14px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #fafafa;
        }
        
        input:focus {
            outline: none;
            border-color: #0B5D3B;
            background: white;
            box-shadow: 0 0 0 4px rgba(11, 93, 59, 0.1);
        }
        
        .primary-btn {
            width: 100%;
            padding: 16px;
            background: #0B5D3B;
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .primary-btn:hover {
            background: #1a7a52;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(11, 93, 59, 0.3);
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-link:hover {
            color: #0B5D3B;
        }
    </style>
</head>
<body>
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="brand-header">
                <div class="logo-icon">
                    <i class="fas fa-book-reader"></i>
                </div>
                <h2>Admin Access</h2>
                <p class="subtitle">NEU Library Management System</p>
            </div>
            
            <?php if($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="u" placeholder="Enter username" required autocomplete="username">
                    </div>
                </div>
                
                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="p" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                </div>
                
                <button type="submit" class="primary-btn">
                    <span>Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Check-in
            </a>
        </div>
    </div>
</body>
</html>
<?php
// index.php - NEU Library Visitor Log (Refined Version)
session_start(); // CRITICAL: Required for checking login status and storing user data
include 'db.php';

// Check if already logged in - redirects to dashboard if session exists
if (isset($_SESSION['user_id'])) {
    // Assuming isAdmin() is defined in your db.php or a functions.php
    if (function_exists('isAdmin') && isAdmin()) {
        header("Location: admin.php");
    } else {
        header("Location: user-dashboard.php");
    }
    exit();
}

// Check if Google OAuth library is present in the vendor folder
$googleAvailable = file_exists(__DIR__ . '/vendor/autoload.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEU Library | Visitor Check-In</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #0B5D3B;
            --primary-light: #1a7a52;
            --accent: #667eea;
            --bg: #f0f2f5;
            --card: #ffffff;
            --text: #2c3e50;
            --text-light: #7f8c8d;
            --google-blue: #4285F4;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated Background Bubbles */
        .bg-bubbles {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 0;
        }
        .bubble {
            position: absolute;
            bottom: -100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: rise 15s infinite ease-in;
        }
        @keyframes rise {
            0% { bottom: -100px; transform: translateX(0); }
            50% { transform: translateX(100px); }
            100% { bottom: 1080px; transform: translateX(-200px); }
        }
        
        .app-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            width: 100%;
            max-width: 480px;
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .status-badge {
            background: linear-gradient(135deg, #ffeaa7, #fdcb6e);
            color: #2d3436;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.75rem;
            margin-bottom: 25px;
        }

        h1 { font-size: 2rem; color: var(--text); margin-bottom: 8px; font-weight: 800; }
        .subtitle { color: var(--text-light); margin-bottom: 30px; font-size: 0.95rem; }
        
        /* Buttons & Inputs */
        .google-btn {
            width: 100%;
            background: white;
            color: #757575;
            border: 2px solid #e1e8ed;
            padding: 16px;
            border-radius: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .google-btn:hover { border-color: var(--google-blue); transform: translateY(-2px); }

        .divider { display: flex; align-items: center; margin: 20px 0; color: var(--text-light); font-size: 0.85rem; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e1e8ed; }
        .divider span { padding: 0 15px; }

        .mode-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
        .mode-box { border: 2px solid #e1e8ed; padding: 15px; border-radius: 16px; cursor: pointer; text-align: center; transition: all 0.3s; }
        .mode-box.active { border-color: var(--primary); background: #f1f9f6; color: var(--primary); }

        .input-wrapper { position: relative; margin-bottom: 20px; }
        .input-wrapper i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-light); }
        input { width: 100%; padding: 16px 16px 16px 50px; border: 2px solid #e1e8ed; border-radius: 16px; font-size: 1rem; }
        input:focus { outline: none; border-color: var(--primary); }

        .reason-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px; }
        .reason-btn { background: white; border: 2px solid #e1e8ed; padding: 14px; border-radius: 14px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .reason-btn.selected { background: var(--primary); color: white; border-color: var(--primary); }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 18px;
            border-radius: 16px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            margin-top: 25px;
            box-shadow: 0 8px 25px rgba(11, 93, 59, 0.3);
        }

        .loading-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.8);
            display: none; align-items: center; justify-content: center; z-index: 1000;
        }
        .loading-overlay.active { display: flex; }
        .spinner { width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid var(--primary); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="bg-bubbles">
        <?php for($i=0; $i<6; $i++): ?>
            <div class="bubble" style="left: <?= $i*15 ?>%; width: <?= rand(30,60) ?>px; height: <?= rand(30,60) ?>px; animation-delay: <?= $i ?>s;"></div>
        <?php endfor; ?>
    </div>

    <div class="app-card">
        <div class="status-badge">CAMPUS ACCESS ACTIVE</div>
        <h1>NEU Library</h1>
        <p class="subtitle">Sign in to access library services</p>

        <?php if ($googleAvailable): ?>
            <a href="google-auth.php" class="google-btn">
                <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" width="20" alt="G">
                Sign in with Google
            </a>
            <div class="divider"><span>OR SCAN ID</span></div>
        <?php else: ?>
            <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 10px; font-size: 0.8rem; margin-bottom: 15px;">
                <i class="fas fa-exclamation-triangle"></i> Google Login disabled. Run <code>composer install</code>.
            </div>
        <?php endif; ?>

        <div class="mode-selector">
            <div id="rfid" class="mode-box active" onclick="setMode('rfid')"><i class="fas fa-id-card"></i><br>RFID</div>
            <div id="manual" class="mode-box" onclick="setMode('manual')"><i class="fas fa-keyboard"></i><br>Manual</div>
        </div>

        <div class="input-wrapper">
            <i class="fas fa-id-card" id="inputIcon"></i>
            <input type="text" id="userInput" placeholder="Scan ID or Enter Student ID" autofocus>
        </div>

        <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-light);">REASON FOR VISIT</label>
        <div class="reason-grid">
            <button class="reason-btn" onclick="setReason(this, 'Reading')">Reading</button>
            <button class="reason-btn" onclick="setReason(this, 'Researching')">Research</button>
            <button class="reason-btn" onclick="setReason(this, 'Computer Use')">Computer</button>
            <button class="reason-btn" onclick="setReason(this, 'Meeting')">Meeting</button>
        </div>

        <button class="btn-submit" onclick="doCheckin()" id="submitBtn">
            Check In Now <i class="fas fa-arrow-right"></i>
        </button>

        <div style="text-align: center; margin-top: 20px;">
            <a href="admin_login.php" style="color: #666; text-decoration: none; font-size: 0.85rem;">Admin Access</a>
        </div>
    </div>

    <div class="loading-overlay" id="loading"><div class="spinner"></div></div>

    <script>
        let currentReason = "";
        let currentMode = "rfid";

        function setMode(mode) {
            currentMode = mode;
            document.querySelectorAll('.mode-box').forEach(b => b.classList.remove('active'));
            document.getElementById(mode).classList.add('active');
            document.getElementById("userInput").focus();
        }

        function setReason(btn, reason) {
            document.querySelectorAll('.reason-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            currentReason = reason;
        }

        async function doCheckin() {
            const val = document.getElementById("userInput").value.trim();
            if (!val || !currentReason) {
                return Swal.fire({ icon: 'warning', title: 'Wait!', text: 'Please provide your ID and a reason.' });
            }

            document.getElementById('loading').classList.add('active');

            try {
                // 1. Verify User
                const loginRes = await fetch("login.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `value=${encodeURIComponent(val)}`
                });
                const userData = await loginRes.json();
                if (userData.status !== "ok") throw new Error(userData.message);

                // 2. Log Visit
                const logRes = await fetch("log.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `user_id=${encodeURIComponent(userData.id)}&reason=${encodeURIComponent(currentReason)}`
                });
                const logData = await logRes.json();
                if (logData.status !== "success") throw new Error(logData.message);

                await Swal.fire({ icon: 'success', title: 'Welcome!', text: `Check-in successful for ${userData.name}`, timer: 2000, showConfirmButton: false });
                window.location.href = 'user-dashboard.php';

            } catch (error) {
                document.getElementById('loading').classList.remove('active');
                Swal.fire({ icon: 'error', title: 'Access Denied', text: error.message });
            }
        }

        // Handle Enter Key
        document.getElementById("userInput").addEventListener("keypress", (e) => {
            if (e.key === "Enter") doCheckin();
        });
    </script>
</body>
</html>
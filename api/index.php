<?php
// index.php
session_start();

// 1. DATABASE CONNECTION
// We keep this in a separate file (db.php) for security.
// Ensure your db.php contains your sql310.infinityfree.com credentials.
include 'db.php'; 

// 2. SESSION CHECK - Redirect if already logged in
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: dashboard.php"); 
    }
    exit();
}

// 3. GOOGLE AUTH SETUP
// Ensure this matches your google-auth.php filename
$googleAuthUrl = "google-auth.php"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="google-site-verification" content="unf_MwoyAcOp6ShiLQoeqlhiUHEZR_FOsLPZ-VbSNaM" />
    
    <title>NEU Library | Visitor Check-In</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    
    <style>
        :root {
            --primary-green: #1a7a52;
            --bg-purple: #7b7cf1;
            --text-dark: #2c3e50;
            --text-muted: #7f8c8d;
        }

        body {
            margin: 0; padding: 0;
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: var(--bg-purple);
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh;
        }

        .app-card {
            background: white;
            width: 100%;
            max-width: 440px;
            padding: 40px;
            border-radius: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            text-align: center;
        }

        .status-pill {
            background: #ffeaa7;
            color: #2d3436;
            display: inline-flex;
            align-items: center;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 25px;
        }

        h1 { font-size: 2rem; color: var(--text-dark); margin: 0; font-weight: 800; }
        .subtitle { color: var(--text-muted); font-size: 0.95rem; margin-top: 5px; margin-bottom: 30px; }

        .google-btn {
            width: 100%;
            border: 1px solid #eee;
            background: white;
            padding: 14px;
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            gap: 10px; text-decoration: none; color: #555;
            font-weight: 600; margin-bottom: 20px;
            transition: 0.2s;
            box-sizing: border-box;
        }
        .google-btn:hover { background: #f9f9f9; transform: translateY(-1px); }

        .divider { display: flex; align-items: center; margin: 20px 0; color: #ccc; font-size: 11px; text-transform: uppercase; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #eee; }
        .divider span { padding: 0 10px; }

        .mode-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .mode-btn {
            border: 2px solid #eee; border-radius: 15px; padding: 20px 10px;
            cursor: pointer; transition: 0.2s; background: white;
        }
        .mode-btn i { font-size: 24px; color: var(--primary-green); margin-bottom: 8px; display: block; }
        .mode-btn span { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); }
        .mode-btn.active { border-color: var(--primary-green); background: #f0faf5; }
        .mode-btn.active span { color: var(--primary-green); }

        .input-box {
            background: white; border: 2px solid #eee; border-radius: 15px;
            padding: 15px; display: flex; align-items: center; gap: 15px;
            margin-bottom: 25px;
        }
        .input-box i { color: #ccc; font-size: 18px; }
        .input-box input { border: none; outline: none; width: 100%; font-size: 14px; font-weight: 600; }

        #reader { width: 100%; border-radius: 15px; overflow: hidden; display: none; margin-bottom: 15px; }

        .section-label { text-align: left; font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px; }
        .reason-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .reason-btn {
            border: 1px solid #eee; border-radius: 12px; padding: 12px;
            background: white; cursor: pointer; display: flex; align-items: center;
            gap: 10px; font-size: 13px; font-weight: 600; color: var(--text-dark);
            transition: 0.2s;
        }
        .reason-btn i { color: var(--primary-green); }
        .reason-btn.selected { border-color: var(--primary-green); background: #f0faf5; }

        .btn-checkin {
            width: 100%; background: var(--primary-green); color: white;
            border: none; border-radius: 15px; padding: 18px;
            font-weight: 700; margin-top: 25px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            font-size: 15px; box-shadow: 0 4px 15px rgba(26, 122, 82, 0.3);
        }

        .admin-link { 
            display: inline-block; 
            margin-top: 25px; 
            color: #777; 
            text-decoration: none; 
            font-size: 12px; 
            font-weight: 600; 
            padding: 8px 15px; 
            border: 1px solid #eee; 
            border-radius: 10px; 
            transition: 0.2s;
        }
        .admin-link:hover { background: #eee; color: #333; }
        
        .demo-bypass {
            display: block; margin-top: 10px; color: #aaa; text-decoration: none; font-size: 10px;
        }
    </style>
</head>
<body>

<div class="app-card">
    <div class="status-pill">● CAMPUS ACCESS ACTIVE</div>
    <h1>NEU Library</h1>
    <p class="subtitle">Sign in to access library services</p>

    <a href="<?= $googleAuthUrl ?>" class="google-btn">
        <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" width="18" alt="G">
        Sign in with @neu.edu.ph
    </a>

    <div class="divider"><span>OR VISITOR LOG</span></div>

    <div class="mode-grid">
        <div class="mode-btn active" id="qr-tab" onclick="switchMode('qr')">
            <i class="fas fa-id-card"></i>
            <span>RFID SCAN</span>
        </div>
        <div class="mode-btn" id="manual-tab" onclick="switchMode('manual')">
            <i class="fas fa-keyboard"></i>
            <span>MANUAL ENTRY</span>
        </div>
    </div>

    <div id="reader"></div>

    <div class="input-box">
        <i class="fas fa-id-card"></i>
        <input type="text" id="userInput" placeholder="Scan ID or Enter Student ID" autofocus>
    </div>

    <div class="section-label">REASON FOR VISIT</div>
    <div class="reason-grid">
        <div class="reason-btn" onclick="selectReason(this, 'Reading')"><i class="fas fa-book"></i> Reading</div>
        <div class="reason-btn" onclick="selectReason(this, 'Researching')"><i class="fas fa-search"></i> Researching</div>
        <div class="reason-btn" onclick="selectReason(this, 'Computer Use')"><i class="fas fa-desktop"></i> Computer Use</div>
        <div class="reason-btn" onclick="selectReason(this, 'Meeting')"><i class="fas fa-users"></i> Meeting</div>
    </div>

    <button class="btn-checkin" onclick="processCheckin()">
        Check In Now <i class="fas fa-arrow-right"></i>
    </button>

    <a href="admin_login.php" class="admin-link"><i class="fas fa-lock"></i> Admin Access</a>
    
    <a href="admin.php?bypass=true" class="demo-bypass">Demo Login (Bypass API)</a>
</div>

<script>
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.get('error') === 'invalid_domain') {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Account',
                text: 'Please use your official @neu.edu.ph email to sign in.',
                confirmButtonColor: '#1a7a52'
            });
        } else if (urlParams.get('error') === 'unauthorized') {
            Swal.fire({
                icon: 'warning',
                title: 'Access Denied',
                text: 'You do not have permission to view that page.',
                confirmButtonColor: '#1a7a52'
            });
        }

        if (urlParams.get('msg') === 'logged_out') {
            Swal.fire({
                icon: 'success',
                title: 'Logged Out',
                text: 'You have been successfully signed out.',
                timer: 2000,
                showConfirmButton: false
            });
        }
    };

    let currentReason = "";
    let scannerActive = false;
    let html5QrCode = new Html5Qrcode("reader");

    function switchMode(mode) {
        document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
        const qrTab = document.getElementById('qr-tab');
        const manualTab = document.getElementById('manual-tab');
        const readerDiv = document.getElementById('reader');

        if (mode === 'qr') {
            qrTab.classList.add('active');
            readerDiv.style.display = "block";
            startScanner();
        } else {
            manualTab.classList.add('active');
            readerDiv.style.display = "none";
            stopScanner();
            document.getElementById('userInput').focus();
        }
    }

    function selectReason(element, reason) {
        document.querySelectorAll('.reason-btn').forEach(b => b.classList.remove('selected'));
        element.classList.add('selected');
        currentReason = reason;
    }

    async function startScanner() {
        if (scannerActive) return;
        try {
            await html5QrCode.start(
                { facingMode: "user" }, 
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    document.getElementById('userInput').value = decodedText;
                    stopScanner();
                    Swal.fire({ icon: 'success', title: 'ID Detected', timer: 1000, showConfirmButton: false });
                }
            );
            scannerActive = true;
        } catch (err) { console.error(err); }
    }

    function stopScanner() {
        if (scannerActive) {
            html5QrCode.stop();
            scannerActive = false;
        }
    }

    async function processCheckin() {
        const idVal = document.getElementById('userInput').value.trim();
        if (!idVal || !currentReason) {
            return Swal.fire({ icon: 'warning', text: 'Please scan/enter ID and select a reason.' });
        }

        try {
            const response = await fetch("process-checkin.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id=${encodeURIComponent(idVal)}&reason=${encodeURIComponent(currentReason)}`
            });
            const data = await response.json();
            if (data.status === "success") {
                Swal.fire({ icon: 'success', title: 'Check-in Successful', text: data.message });
                document.getElementById('userInput').value = "";
            } else {
                Swal.fire({ icon: 'error', text: data.message });
            }
        } catch (err) {
            Swal.fire({ icon: 'error', text: 'Server error. Please try again.' });
        }
    }
</script>

</body>
</html>
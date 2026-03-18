<!-- Enhanced index.php with Google OAuth -->
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
            --success: #27ae60;
            --warning: #f1c40f;
            --google-blue: #4285F4;
            --google-red: #EA4335;
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
        
        .bg-bubbles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
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
            border: 1px solid rgba(255,255,255,0.5);
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
            box-shadow: 0 4px 15px rgba(253, 203, 110, 0.3);
        }
        
        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #00b894;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        h1 {
            font-size: 2rem;
            color: var(--text);
            margin-bottom: 8px;
            font-weight: 800;
        }
        
        .subtitle {
            color: var(--text-light);
            margin-bottom: 30px;
            font-size: 0.95rem;
        }
        
        /* Google Sign-In Button */
        .google-btn {
            width: 100%;
            background: white;
            color: #757575;
            border: 2px solid #e1e8ed;
            padding: 16px;
            border-radius: 16px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 25px;
            text-decoration: none;
        }
        
        .google-btn:hover {
            border-color: var(--google-blue);
            box-shadow: 0 4px 15px rgba(66, 133, 244, 0.2);
            transform: translateY(-2px);
        }
        
        .google-icon {
            width: 20px;
            height: 20px;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
            color: var(--text-light);
            font-size: 0.85rem;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e1e8ed;
        }
        
        .divider span {
            padding: 0 15px;
        }
        
        .mode-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .mode-box {
            border: 2px solid #e1e8ed;
            padding: 20px;
            border-radius: 16px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            background: white;
        }
        
        .mode-box i {
            font-size: 1.8rem;
            margin-bottom: 8px;
            display: block;
            color: var(--text-light);
            transition: color 0.3s;
        }
        
        .mode-box span {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-light);
        }
        
        .mode-box:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(11, 93, 59, 0.1);
        }
        
        .mode-box.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, #f1f9f6, #e8f5e9);
        }
        
        .mode-box.active i,
        .mode-box.active span {
            color: var(--primary);
        }
        
        .input-group {
            margin-bottom: 25px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
        }
        
        input {
            width: 100%;
            padding: 16px 16px 16px 50px;
            border: 2px solid #e1e8ed;
            border-radius: 16px;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
        }
        
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(11, 93, 59, 0.1);
        }
        
        .reason-section {
            margin-bottom: 25px;
        }
        
        .reason-section label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }
        
        .reason-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .reason-btn {
            background: white;
            border: 2px solid #e1e8ed;
            padding: 16px;
            border-radius: 14px;
            cursor: pointer;
            text-align: left;
            font-weight: 600;
            color: var(--text);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }
        
        .reason-btn i {
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .reason-btn:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .reason-btn.selected {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 8px 20px rgba(11, 93, 59, 0.3);
        }
        
        .reason-btn.selected i {
            color: white;
        }
        
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 18px;
            border-radius: 16px;
            border: none;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 25px rgba(11, 93, 59, 0.3);
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(11, 93, 59, 0.4);
        }
        
        .btn-submit:active {
            transform: translateY(-1px);
        }
        
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #e1e8ed;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 480px) {
            .app-card {
                padding: 30px 20px;
            }
            
            .reason-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="bg-bubbles">
        <div class="bubble" style="left: 10%; width: 40px; height: 40px; animation-delay: 0s;"></div>
        <div class="bubble" style="left: 20%; width: 60px; height: 60px; animation-delay: 2s;"></div>
        <div class="bubble" style="left: 35%; width: 30px; height: 30px; animation-delay: 4s;"></div>
        <div class="bubble" style="left: 50%; width: 50px; height: 50px; animation-delay: 1s;"></div>
        <div class="bubble" style="left: 65%; width: 35px; height: 35px; animation-delay: 3s;"></div>
        <div class="bubble" style="left: 80%; width: 45px; height: 45px; animation-delay: 5s;"></div>
    </div>

    <div class="app-card">
        <div class="status-badge">CAMPUS ACCESS ACTIVE</div>
        
        <h1>NEU Library</h1>
        <p class="subtitle">Sign in to access library services</p>

        <!-- Google Sign-In Button -->
        <a href="google-auth.php" class="google-btn">
            <svg class="google-icon" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Sign in with Google
        </a>

        <div class="divider"><span>OR</span></div>

        <div class="mode-selector">
            <div id="rfid" class="mode-box active" onclick="setMode('rfid')">
                <i class="fas fa-id-card"></i>
                <span>RFID SCAN</span>
            </div>
            <div id="manual" class="mode-box" onclick="setMode('manual')">
                <i class="fas fa-keyboard"></i>
                <span>MANUAL ENTRY</span>
            </div>
        </div>

        <div class="input-group">
            <div class="input-wrapper">
                <i class="fas fa-id-card" id="inputIcon"></i>
                <input type="text" id="userInput" placeholder="Scan ID or Enter Student ID" autocomplete="off">
            </div>
        </div>

        <div class="reason-section">
            <label>Reason for Visit</label>
            <div class="reason-grid">
                <button class="reason-btn" onclick="setReason(this, 'Reading')">
                    <i class="fas fa-book-open"></i> Reading
                </button>
                <button class="reason-btn" onclick="setReason(this, 'Researching')">
                    <i class="fas fa-search"></i> Researching
                </button>
                <button class="reason-btn" onclick="setReason(this, 'Computer Use')">
                    <i class="fas fa-desktop"></i> Computer Use
                </button>
                <button class="reason-btn" onclick="setReason(this, 'Meeting')">
                    <i class="fas fa-users"></i> Meeting
                </button>
            </div>
        </div>

        <button class="btn-submit" onclick="doCheckin()" id="submitBtn">
            <span>Check In Now</span>
            <i class="fas fa-arrow-right"></i>
        </button>
    </div>

    <div class="loading-overlay" id="loading">
        <div class="spinner"></div>
    </div>

    <script>
        let currentReason = "";
        let currentMode = "rfid";
        let isProcessing = false;

        function setMode(mode) {
            currentMode = mode;
            document.querySelectorAll('.mode-box').forEach(b => b.classList.remove('active'));
            document.getElementById(mode).classList.add('active');
            
            const input = document.getElementById("userInput");
            const icon = document.getElementById("inputIcon");
            
            if (mode === 'rfid') {
                input.placeholder = "Scan ID or Enter Student ID";
                icon.className = "fas fa-id-card";
            } else {
                input.placeholder = "Enter Student ID or Email";
                icon.className = "fas fa-user";
            }
            input.focus();
        }

        function setReason(btn, reason) {
            document.querySelectorAll('.reason-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            currentReason = reason;
        }

        async function doCheckin() {
            if (isProcessing) return;
            
            const inputField = document.getElementById("userInput");
            const val = inputField.value.trim();
            const submitBtn = document.getElementById("submitBtn");

            if (!val) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please enter your ID.',
                    confirmButtonColor: '#0B5D3B'
                });
                inputField.focus();
                return;
            }
            
            if (!currentReason) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Reason',
                    text: 'Please select a reason for your visit.',
                    confirmButtonColor: '#0B5D3B'
                });
                return;
            }

            isProcessing = true;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            document.getElementById('loading').classList.add('active');

            try {
                const loginRes = await fetch("login.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `value=${encodeURIComponent(val)}`
                });
                
                if (!loginRes.ok) throw new Error('Network error');
                const userData = await loginRes.json();
                
                if (userData.status !== "ok") {
                    throw new Error(userData.message || "Access Denied");
                }

                const logRes = await fetch("log.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `user_id=${encodeURIComponent(userData.id)}&reason=${encodeURIComponent(currentReason)}`
                });
                
                if (!logRes.ok) throw new Error('Failed to log visit');

                await Swal.fire({
                    title: 'Welcome to NEU Library!',
                    html: `<div style="text-align: left;">
                        <strong style="font-size: 1.2rem; color: #0B5D3B;">${escapeHtml(userData.name)}</strong><br>
                        <span style="color: #666;">${escapeHtml(userData.program)}</span><br>
                        <small style="color: #999;">Check-in successful at ${new Date().toLocaleTimeString()}</small>
                    </div>`,
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false,
                    backdrop: 'rgba(11, 93, 59, 0.2)'
                });
                
                location.reload();

            } catch (error) {
                document.getElementById('loading').classList.remove('active');
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: error.message || "Could not complete check-in",
                    confirmButtonColor: '#0B5D3B'
                });
                isProcessing = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span>Check In Now</span><i class="fas fa-arrow-right"></i>';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.getElementById("userInput").addEventListener("keypress", function(event) {
            if (event.key === "Enter" && !isProcessing) {
                doCheckin();
            }
        });

        window.addEventListener('load', () => {
            document.getElementById("userInput").focus();
        });
        
        // Check for errors in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('error')) {
            const error = urlParams.get('error');
            let message = "An error occurred";
            switch(error) {
                case 'invalid_domain': message = "Only @neu.edu.ph emails are allowed"; break;
                case 'account_blocked': message = "Your account has been blocked"; break;
                case 'oauth_failed': message = "Google sign-in failed"; break;
                case 'oauth_error': message = "Authentication error occurred"; break;
            }
            Swal.fire({
                icon: 'error',
                title: 'Sign In Failed',
                text: message,
                confirmButtonColor: '#0B5D3B'
            });
        }
    </script>
</body>
</html>
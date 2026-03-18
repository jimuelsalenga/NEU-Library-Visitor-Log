<?php
// user-dashboard.php - Complete fixed version
include 'db.php';
requireAuth();

// Get user stats
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT COUNT(*) as visits FROM visitor_log WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$visitCount = $stmt->get_result()->fetch_assoc()['visits'];

// Get recent visits
$historyStmt = $conn->prepare("SELECT reason, timestamp FROM visitor_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10");
$historyStmt->bind_param("i", $userId);
$historyStmt->execute();
$history = $historyStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Library Account | NEU Library</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0B5D3B;
            --primary-light: #1a7a52;
            --bg: #f0f2f5;
            --card-bg: #ffffff;
            --text: #2c3e50;
            --text-muted: #7f8c8d;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            min-height: 100vh;
        }
        
        .header {
            background: var(--card-bg);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-box {
            background: var(--primary);
            color: white;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 700;
            color: var(--text);
        }
        
        .user-email {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .logout-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: var(--primary-light);
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 40px;
            border-radius: 24px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .welcome-card h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .stat-value {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .stat-label {
            color: var(--text-muted);
            margin-top: 10px;
        }
        
        .history-section {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            margin-top: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .history-section h2 {
            margin-bottom: 20px;
            color: var(--text);
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .history-table th {
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid var(--bg);
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .history-table td {
            padding: 12px;
            border-bottom: 1px solid var(--bg);
        }
        
        .history-table tr:hover {
            background: var(--bg);
        }
        
        .no-data {
            color: var(--text-muted);
            text-align: center;
            padding: 40px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand">
            <div class="logo-box">
                <i class="fas fa-book-reader"></i>
            </div>
            <h2>NEU Library</h2>
        </div>
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
                <div class="user-email"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></div>
            </div>
            <button class="logout-btn" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </header>

    <div class="container">
        <div class="welcome-card">
            <h1>Welcome to NEU Library!</h1>
            <p>We're glad to have you here. Enjoy your study session!</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $visitCount ?></div>
                <div class="stat-label">Total Visits</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= date('M j') ?></div>
                <div class="stat-label">Today</div>
            </div>
        </div>

        <div class="history-section">
            <h2><i class="fas fa-history"></i> Recent Activity</h2>
            <?php if ($history->num_rows > 0): ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reason</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $history->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('M j, Y', strtotime($row['timestamp'])) ?></td>
                        <td><?= htmlspecialchars($row['reason']) ?></td>
                        <td><?= date('g:i A', strtotime($row['timestamp'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="no-data">No visits recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>
<?php
// user-dashboard.php - Improved Version
require_once 'db.php';
requireAuth();

$userId = $_SESSION['user_id'];

// Get user stats
$stmt = $conn->prepare("SELECT COUNT(*) as visits FROM visitor_log WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$visitCount = $stmt->get_result()->fetch_assoc()['visits'];

// Get recent visits (Improved: joining with programs if you store them)
$historyStmt = $conn->prepare("SELECT reason, timestamp, program FROM visitor_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10");
$historyStmt->bind_param("i", $userId);
$historyStmt->execute();
$history = $historyStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | NEU Library</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0B5D3B; --primary-light: #1a7a52; --bg: #f0f2f5; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; }
        .navbar { background: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .welcome-hero { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; padding: 40px; border-radius: 24px; text-align: center; margin-bottom: 30px; }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 20px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .stat-value { font-size: 2.5rem; font-weight: 800; color: var(--primary); }
        .history-card { background: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; color: #7f8c8d; font-size: 0.8rem; text-transform: uppercase; padding: 12px; border-bottom: 2px solid #f0f2f5; }
        td { padding: 15px 12px; border-bottom: 1px solid #f0f2f5; font-size: 0.95rem; }
        .badge { background: #e7f5ee; color: var(--primary); padding: 5px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
    </style>
</head>
<body>

<nav class="navbar">
    <div style="display:flex; align-items:center; gap:10px; color:var(--primary); font-weight:bold;">
        <i class="fas fa-book-reader fa-lg"></i> NEU LIBRARY
    </div>
    <div style="display:flex; align-items:center; gap:15px;">
        <span style="font-weight:600;"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="logout.php" style="color:#d9534f; text-decoration:none;"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</nav>

<div class="container">
    <div class="welcome-hero">
        <h1>Hello, <?= explode(' ', $_SESSION['user_name'])[0] ?>!</h1>
        <p>Your digital library pass is active.</p>
        <a href="user-details.php" style="display:inline-block; margin-top:15px; background:#fff; color:var(--primary); padding:10px 25px; border-radius:12px; text-decoration:none; font-weight:bold;">
            <i class="fas fa-plus"></i> New Visit
        </a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $visitCount ?></div>
            <div style="color:#7f8c8d;">Total Library Visits</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= date('d') ?></div>
            <div style="color:#7f8c8d;"><?= date('F Y') ?></div>
        </div>
    </div>

    <div class="history-card">
        <h3><i class="fas fa-history" style="color:var(--primary);"></i> Recent Activity</h3>
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Program</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $history->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;"><?= date('M j, Y', strtotime($row['timestamp'])) ?></div>
                        <div style="font-size:0.8rem; color:#7f8c8d;"><?= date('g:i A', strtotime($row['timestamp'])) ?></div>
                    </td>
                    <td><span style="font-size:0.85rem; color:#2c3e50;"><?= htmlspecialchars($row['program'] ?? 'N/A') ?></span></td>
                    <td><span class="badge"><?= htmlspecialchars($row['reason']) ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
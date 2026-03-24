<?php
// user-dashboard.php - PDO Version
require_once 'db.php';
// Custom function to ensure user is logged in
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$userId = $_SESSION['user_id'];

// Get user stats using PDO fetchColumn()
$stmt = $conn->prepare("SELECT COUNT(*) FROM visitor_log WHERE user_id = :uid");
$stmt->execute(['uid' => $userId]);
$visitCount = $stmt->fetchColumn();

// Get recent visits
$historyStmt = $conn->prepare("SELECT reason, created_at as timestamp, program FROM visitor_log WHERE user_id = :uid ORDER BY created_at DESC LIMIT 10");
$historyStmt->execute(['uid' => $userId]);
$history = $historyStmt->fetchAll(); // fetchAll() replaces get_result() loops
?>
<!DOCTYPE html>
<html lang="en">
<head>
    </head>
<body>

<div class="container">
    <div class="welcome-hero">
        <h1>Hello, <?= explode(' ', $_SESSION['user_name'])[0] ?>!</h1>
        <p>Your digital library pass is active.</p>
        <a href="user-details.php" class="btn-new-visit">
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
                <?php foreach($history as $row): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;"><?= date('M j, Y', strtotime($row['timestamp'])) ?></div>
                        <div style="font-size:0.8rem; color:#7f8c8d;"><?= date('g:i A', strtotime($row['timestamp'])) ?></div>
                    </td>
                    <td><span style="font-size:0.85rem; color:#2c3e50;"><?= htmlspecialchars($row['program'] ?? 'N/A') ?></span></td>
                    <td><span class="badge"><?= htmlspecialchars($row['reason']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
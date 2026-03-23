<?php
include 'db.php';

// Check if user is logged in as admin
// Uncomment these lines when you are ready to secure the page
/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}
*/

/** * Formats a raw ID into NEU style: 23-12345-678 
 */
function formatNEUID($id) {
    // Handles the old error where IDs were cut off by the INT limit
    if ($id == "2147483647") return "Fix DB Column";
    
    $clean = preg_replace('/[^0-9]/', '', $id);
    if (strlen($clean) >= 10) {
        return substr($clean, 0, 2) . '-' . substr($clean, 2, 5) . '-' . substr($clean, 7, 3);
    }
    return $id;
}

// Statistics Queries for Dashboard Cards
$todayRes = $conn->query("SELECT COUNT(*) as total FROM visitor_log WHERE DATE(timestamp) = CURDATE()");
$totalToday = $todayRes->fetch_assoc()['total'] ?? 0;

$weekRes = $conn->query("SELECT COUNT(*) as total FROM visitor_log WHERE YEARWEEK(timestamp) = YEARWEEK(NOW())");
$totalWeek = $weekRes->fetch_assoc()['total'] ?? 0;

$monthRes = $conn->query("SELECT COUNT(*) as total FROM visitor_log WHERE MONTH(timestamp) = MONTH(NOW()) AND YEAR(timestamp) = YEAR(NOW())");
$totalMonth = $monthRes->fetch_assoc()['total'] ?? 0;

$allTimeRes = $conn->query("SELECT COUNT(*) as total FROM visitor_log");
$totalAllTime = $allTimeRes->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NEU Library Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --neu-green: #0B5D3B; --bg: #f4f7f6; --text: #333; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); margin: 0; }
        
        /* Navigation Tabs Style */
        .nav-container { background: white; padding: 10px 40px; border-bottom: 1px solid #ddd; display: flex; gap: 10px; }
        .nav-tab { padding: 10px 20px; border-radius: 8px; text-decoration: none; color: #666; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .nav-tab.active { background: #e8f5e9; color: var(--neu-green); border: 1px solid var(--neu-green); }
        .nav-tab:hover:not(.active) { background: #f0f0f0; }

        header { background: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 2rem; max-width: 1400px; margin: 0 auto; }

        /* Stats Grid - 4 Columns */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; border-left: 6px solid var(--neu-green); box-shadow: 0 4px 6px rgba(0,0,0,0.05); position: relative; }
        .stat-card h3 { margin: 0; color: #777; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card p { margin: 10px 0 5px; font-size: 2.2rem; font-weight: bold; color: var(--neu-green); }
        .stat-card small { color: #999; font-size: 0.75rem; }
        .stat-icon { position: absolute; top: 1.5rem; right: 1.5rem; font-size: 1.5rem; color: #eee; }

        .table-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th { text-align: left; padding: 12px; background: #fafafa; color: #666; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #eee; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        .id-badge { background: #e8f5e9; color: var(--neu-green); padding: 4px 8px; border-radius: 4px; font-weight: bold; font-family: monospace; }
    </style>
</head>
<body>

<header>
    <h2 style="margin:0; color: var(--neu-green);">NEU Library Admin</h2>
    <a href="logout.php" style="color:#d32f2f; text-decoration:none; font-weight:bold;">Logout</a>
</header>

<div class="nav-container">
    <a href="admin.php" class="nav-tab active"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="visitor_log.php" class="nav-tab"><i class="fas fa-list-alt"></i> Visitor Log</a>
    <a href="blocked_users.php" class="nav-tab"><i class="fas fa-ban"></i> Blocked Users</a>
    <a href="student_records.php" class="nav-tab"><i class="fas fa-users"></i> Student Records</a>
</div>

<div class="container">
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-users stat-icon"></i>
            <h3>Today's Visitors</h3>
            <p><?= $totalToday ?></p>
            <small>Total logged in today</small>
        </div>
        <div class="stat-card" style="border-left-color: #f1c40f;">
            <i class="fas fa-calendar-week stat-icon"></i>
            <h3>This Week</h3>
            <p><?= $totalWeek ?></p>
            <small>Last 7 days</small>
        </div>
        <div class="stat-card" style="border-left-color: #27ae60;">
            <i class="fas fa-calendar-alt stat-icon"></i>
            <h3>This Month</h3>
            <p><?= $totalMonth ?></p>
            <small><?= date('F Y') ?></small>
        </div>
        <div class="stat-card" style="border-left-color: #9b59b6;">
            <i class="fas fa-database stat-icon"></i>
            <h3>Total All Time</h3>
            <p><?= $totalAllTime ?></p>
            <small>Cumulative visitors</small>
        </div>
    </div>

    <div class="table-card">
        <h3>Recent Visitor Logs</h3>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Course / Program</th>
                    <th>Reason</th>
                    <th>Time In</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // LEFT JOIN links visitor_log (v) with users (u)
                $sql = "SELECT v.user_id, v.reason, v.timestamp, u.name as student_name, u.college 
                FROM visitor_log v 
                LEFT JOIN users u ON v.user_id = u.id 
                ORDER BY v.timestamp DESC LIMIT 10";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0):
                    while($row = $result->fetch_assoc()):
                        // Fallback text if the student isn't in the 'users' table yet
                        $displayName = $row['student_name'] ?? "New Student";
                        $program = $row['college'] ?? "N/A";
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($displayName) ?></strong></td>
                    <td><span class="id-badge"><?= htmlspecialchars(formatNEUID($row['user_id'])) ?></span></td>
                    <td><?= htmlspecialchars($program) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td><?= date('M d, h:i A', strtotime($row['timestamp'])) ?></td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px;">No logs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
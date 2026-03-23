<?php
include 'db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // header("Location: admin_login.php");
    // exit();
}

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get filter parameters
$dateRange = $_GET['range'] ?? 'today';
$customStart = $_GET['start'] ?? '';
$customEnd = $_GET['end'] ?? '';
$filterReason = $_GET['reason'] ?? '';

// Build statistics query
function buildStatsQuery($conn, $dateRange, $customStart, $customEnd, $filterReason) {
    $whereConditions = ["1=1"];
    $params = [];
    $types = "";
    
    switch($dateRange) {
        case 'today':
            $whereConditions[] = "DATE(v.timestamp) = CURDATE()";
            break;
        case 'week':
            $whereConditions[] = "YEARWEEK(v.timestamp) = YEARWEEK(NOW())";
            break;
        case 'month':
            $whereConditions[] = "MONTH(v.timestamp) = MONTH(NOW()) AND YEAR(v.timestamp) = YEAR(NOW())";
            break;
        case 'custom':
            if ($customStart && $customEnd) {
                $whereConditions[] = "DATE(v.timestamp) BETWEEN ? AND ?";
                $params[] = $customStart;
                $params[] = $customEnd;
                $types .= "ss";
            }
            break;
    }
    
    if ($filterReason) { $whereConditions[] = "v.reason = ?"; $params[] = $filterReason; $types .= "s"; }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    $countQuery = "SELECT COUNT(*) as total FROM visitor_log v WHERE $whereClause";
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];

    return ['total' => $total];
}

$stats = buildStatsQuery($conn, $dateRange, $customStart, $customEnd, $filterReason);

// Real-time Active (last 30m)
$activeQuery = $conn->query("SELECT COUNT(*) as active FROM visitor_log WHERE timestamp > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
$activeCount = $activeQuery->fetch_assoc()['active'] ?? 0;

// Unique Visitors
$uniqueQuery = $conn->query("SELECT COUNT(DISTINCT user_id) as unique_v FROM visitor_log");
$uniqueCount = $uniqueQuery->fetch_assoc()['unique_v'] ?? 0;

// Get dropdown options
$reasons = $conn->query("SELECT DISTINCT reason FROM visitor_log ORDER BY reason");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NEU Library Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0B5D3B; --bg: #f0f2f5; --card-bg: #ffffff; --text: #2c3e50; --border: #e1e8ed; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); }
        .admin-header { background: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); height: 70px; }
        .dashboard-content { padding: 30px 40px; max-width: 1400px; margin: 0 auto; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: var(--card-bg); padding: 25px; border-radius: 15px; border-left: 5px solid var(--primary); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .table-section { background: var(--card-bg); border-radius: 15px; padding: 20px; border: 1px solid var(--border); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 12px; background: #f8f9fa; color: #7f8c8d; font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
    </style>
</head>
<body>

<header class="admin-header">
    <h2>NEU Library Admin</h2>
    <a href="logout.php" style="text-decoration:none; color:#e74c3c; font-weight:bold;">Logout</a>
</header>

<main class="dashboard-content">
    <div class="stats-row">
        <div class="stat-card">
            <span style="color:#7f8c8d;">Total Visitors Today</span>
            <div style="font-size: 2.5rem; font-weight: 800;"><?= number_format($stats['total']) ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #3498db;">
            <span style="color:#7f8c8d;">Active Now</span>
            <div style="font-size: 2.5rem; font-weight: 800;"><?= $activeCount ?></div>
        </div>
    </div>

    <div class="table-section">
        <h3>Recent Logs</h3>
        <table>
            <thead>
                <tr>
                    <th>Visitor Name</th> <th>Reason</th>
                    <th>Time In</th>
                </tr>
            </thead>
            <tbody>
                <?php
                /**
                 * JOIN LOGIC:
                 * We join 'visitor_log' with 'users' to get the name.
                 * Using COALESCE to show the ID if the name is missing in the database.
                 */
                $listQuery = "SELECT v.timestamp, v.reason, v.user_id, u.full_name 
                              FROM visitor_log v 
                              LEFT JOIN users u ON v.user_id = u.id 
                              ORDER BY v.timestamp DESC LIMIT 10";
                
                $listRes = $conn->query($listQuery);
                if ($listRes && $listRes->num_rows > 0):
                    while($row = $listRes->fetch_assoc()):
                        // Use full name if it exists, otherwise show the ID
                        $displayName = !empty($row['full_name']) ? $row['full_name'] : "Unknown (" . $row['user_id'] . ")";
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($displayName) ?></strong></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td><?= date('M d, h:i A', strtotime($row['timestamp'])) ?></td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="3" style="text-align:center;">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>
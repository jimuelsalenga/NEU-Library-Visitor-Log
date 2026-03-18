<?php
include 'db.php';
requireAdmin();

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get filter parameters
$dateRange = $_GET['range'] ?? 'today';
$customStart = $_GET['start'] ?? '';
$customEnd = $_GET['end'] ?? '';
$filterReason = $_GET['reason'] ?? '';
$filterCollege = $_GET['college'] ?? '';
$filterEmployee = isset($_GET['employee']) ? true : false;

// Build statistics query based on filters
function buildStatsQuery($conn, $dateRange, $customStart, $customEnd, $filterReason, $filterCollege, $filterEmployee) {
    $whereConditions = ["1=1"];
    $params = [];
    $types = "";
    
    // Date filtering
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
    
    // Reason filter
    if ($filterReason) {
        $whereConditions[] = "v.reason = ?";
        $params[] = $filterReason;
        $types .= "s";
    }
    
    // College filter
    if ($filterCollege) {
        $whereConditions[] = "u.college = ?";
        $params[] = $filterCollege;
        $types .= "s";
    }
    
    // Employee filter
    if ($filterEmployee) {
        $whereConditions[] = "u.is_employee = 1";
    }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM visitor_log v JOIN users u ON v.user_id = u.id WHERE $whereClause";
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get breakdown by reason
    $reasonQuery = "SELECT v.reason, COUNT(*) as count FROM visitor_log v JOIN users u ON v.user_id = u.id WHERE $whereClause GROUP BY v.reason";
    $stmt = $conn->prepare($reasonQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $reasonStats = $stmt->get_result();
    
    // Get breakdown by college
    $collegeQuery = "SELECT u.college, COUNT(*) as count FROM visitor_log v JOIN users u ON v.user_id = u.id WHERE $whereClause AND u.college IS NOT NULL GROUP BY u.college";
    $stmt = $conn->prepare($collegeQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $collegeStats = $stmt->get_result();
    
    return [
        'total' => $total,
        'reasons' => $reasonStats,
        'colleges' => $collegeStats
    ];
}

$stats = buildStatsQuery($conn, $dateRange, $customStart, $customEnd, $filterReason, $filterCollege, $filterEmployee);

// Get unique reasons and colleges for filters
$reasons = $conn->query("SELECT DISTINCT reason FROM visitor_log ORDER BY reason");
$colleges = $conn->query("SELECT DISTINCT college FROM users WHERE college IS NOT NULL ORDER BY college");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NEU Library Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0B5D3B;
            --primary-light: #1a7a52;
            --danger: #e74c3c;
            --success: #27ae60;
            --warning: #f1c40f;
            --info: #3498db;
            --bg: #f0f2f5;
            --card-bg: #ffffff;
            --text: #2c3e50;
            --text-muted: #7f8c8d;
            --border: #e1e8ed;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        
        .admin-header {
            background: var(--card-bg);
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            height: 70px;
            position: sticky;
            top: 0;
            z-index: 100;
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
            font-size: 1.3rem;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 25px;
            transition: background 0.3s;
        }
        
        .admin-profile:hover { background: var(--bg); }
        
        .avatar-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        
        .dashboard-content { padding: 30px 40px; max-width: 1600px; margin: 0 auto; }
        
        /* Filter Section */
        .filter-section {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filter-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(11, 93, 59, 0.1);
        }
        
        .filter-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: var(--bg);
            color: var(--text);
            border: 2px solid var(--border);
        }
        
        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s;
            border: 1px solid var(--border);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
        }
        
        .stat-card.warning::before { background: var(--warning); }
        .stat-card.info::before { background: var(--info); }
        .stat-card.success::before { background: var(--success); }
        
        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text);
            margin: 10px 0;
        }
        
        /* Breakdown Sections */
        .breakdown-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .breakdown-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .breakdown-card h3 {
            margin-bottom: 20px;
            color: var(--text);
            font-size: 1.1rem;
        }
        
        .breakdown-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }
        
        .breakdown-item:last-child {
            border-bottom: none;
        }
        
        .breakdown-label {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .breakdown-bar {
            width: 100px;
            height: 8px;
            background: var(--bg);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .breakdown-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        /* Table Section */
        .table-section {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .table-ctrl {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .table-wrapper {
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid var(--border);
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.95rem;
        }
        
        th {
            background: var(--bg);
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
        }
        
        tr:hover td {
            background: #f8f9fa;
        }
        
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
        }
        
        .pill {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        
        .pill-green { background: #d4edda; color: #155724; }
        .pill-red { background: #f8d7da; color: #721c24; }
        
        .btn-action {
            padding: 8px 16px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-action.block {
            background: transparent;
            color: var(--danger);
            border: 2px solid var(--danger);
        }
        
        .btn-action.block:hover {
            background: var(--danger);
            color: white;
        }
        
        /* Custom Date Range */
        .custom-range {
            display: none;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        
        .custom-range.active {
            display: grid;
        }
    </style>
</head>
<body>

<header class="admin-header">
    <div class="brand">
        <div class="logo-box"><i class="fas fa-book-reader"></i></div>
        <h2>NEU Library Admin Dashboard</h2>
    </div>
    <div class="header-actions">
        <div class="admin-profile" onclick="logout()">
            <div class="avatar-sm"><?= strtoupper(substr($_SESSION['user_name'], 0, 2)) ?></div>
            <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <i class="fas fa-chevron-down" style="color: var(--text-muted);"></i>
        </div>
    </div>
</header>

<main class="dashboard-content">
    
    <!-- Filter Section -->
    <div class="filter-section">
        <h3 style="margin-bottom: 20px;"><i class="fas fa-filter"></i> Filter Statistics</h3>
        <form method="GET" action="">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Date Range</label>
                    <select name="range" id="rangeSelect" onchange="toggleCustomRange()">
                        <option value="today" <?= $dateRange == 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $dateRange == 'week' ? 'selected' : '' ?>>This Week</option>
                        <option value="month" <?= $dateRange == 'month' ? 'selected' : '' ?>>This Month</option>
                        <option value="custom" <?= $dateRange == 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Reason for Visit</label>
                    <select name="reason">
                        <option value="">All Reasons</option>
                        <?php while($r = $reasons->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($r['reason']) ?>" <?= $filterReason == $r['reason'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['reason']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>College/Department</label>
                    <select name="college">
                        <option value="">All Colleges</option>
                        <?php while($c = $colleges->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($c['college']) ?>" <?= $filterCollege == $c['college'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['college']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group" style="display: flex; align-items: flex-end;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="employee" <?= $filterEmployee ? 'checked' : '' ?> style="width: auto;">
                        Employees Only
                    </label>
                </div>
            </div>
            
            <div class="custom-range <?= $dateRange == 'custom' ? 'active' : '' ?>" id="customRange">
                <div class="filter-group">
                    <label>Start Date</label>
                    <input type="date" name="start" value="<?= htmlspecialchars($customStart) ?>">
                </div>
                <div class="filter-group">
                    <label>End Date</label>
                    <input type="date" name="end" value="<?= htmlspecialchars($customEnd) ?>">
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
                <a href="admin.php" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Main Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total Visitors</span>
                <i class="fas fa-users" style="color: var(--primary);"></i>
            </div>
            <div class="stat-value"><?= number_format($stats['total']) ?></div>
            <div style="color: var(--text-muted); font-size: 0.9rem;">
                <?= ucfirst($dateRange) ?> period
            </div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-top">
                <span class="stat-label">Active Now</span>
                <i class="fas fa-signal" style="color: var(--info);"></i>
            </div>
            <div class="stat-value">--</div>
            <div style="color: var(--text-muted); font-size: 0.9rem;">Real-time</div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-top">
                <span class="stat-label">Unique Visitors</span>
                <i class="fas fa-user-check" style="color: var(--success);"></i>
            </div>
            <div class="stat-value">--</div>
            <div style="color: var(--text-muted); font-size: 0.9rem;">Distinct count</div>
        </div>
    </div>

    <!-- Breakdown Stats -->
    <div class="breakdown-grid">
        <div class="breakdown-card">
            <h3><i class="fas fa-chart-pie"></i> By Reason</h3>
            <?php 
            $maxReason = 0;
            $reasonData = [];
            while($r = $stats['reasons']->fetch_assoc()) {
                $reasonData[] = $r;
                if ($r['count'] > $maxReason) $maxReason = $r['count'];
            }
            foreach($reasonData as $r): 
                $percentage = $maxReason > 0 ? ($r['count'] / $maxReason * 100) : 0;
            ?>
            <div class="breakdown-item">
                <div class="breakdown-label">
                    <i class="fas fa-circle" style="color: var(--primary); font-size: 0.5rem;"></i>
                    <?= htmlspecialchars($r['reason']) ?>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="breakdown-bar">
                        <div class="breakdown-fill" style="width: <?= $percentage ?>%"></div>
                    </div>
                    <strong><?= $r['count'] ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($reasonData)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">No data available</p>
            <?php endif; ?>
        </div>

        <div class="breakdown-card">
            <h3><i class="fas fa-university"></i> By College</h3>
            <?php 
            $maxCollege = 0;
            $collegeData = [];
            while($c = $stats['colleges']->fetch_assoc()) {
                $collegeData[] = $c;
                if ($c['count'] > $maxCollege) $maxCollege = $c['count'];
            }
            foreach($collegeData as $c): 
                $percentage = $maxCollege > 0 ? ($c['count'] / $maxCollege * 100) : 0;
            ?>
            <div class="breakdown-item">
                <div class="breakdown-label">
                    <i class="fas fa-circle" style="color: var(--info); font-size: 0.5rem;"></i>
                    <?= htmlspecialchars($c['college']) ?>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="breakdown-bar">
                        <div class="breakdown-fill" style="width: <?= $percentage ?>%; background: var(--info);"></div>
                    </div>
                    <strong><?= $c['count'] ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($collegeData)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">No data available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Visitor Table -->
    <div class="table-section">
        <div class="table-ctrl">
            <div>
                <h3>Visitor Records</h3>
                <p style="color: var(--text-muted);">Showing filtered results</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <button class="btn btn-secondary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button class="btn btn-primary" onclick="exportData()">
                    <i class="fas fa-file-export"></i> Export
                </button>
            </div>
        </div>

        <div class="table-wrapper">
            <table id="visitorTable">
                <thead>
                    <tr>
                        <th>Visitor</th>
                        <th>Program/College</th>
                        <th>Reason</th>
                        <th>Type</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Build query with same filters
                    $whereConditions = ["1=1"];
                    $params = [];
                    $types = "";
                    
                    switch($dateRange) {
                        case 'today': $whereConditions[] = "DATE(v.timestamp) = CURDATE()"; break;
                        case 'week': $whereConditions[] = "YEARWEEK(v.timestamp) = YEARWEEK(NOW())"; break;
                        case 'month': $whereConditions[] = "MONTH(v.timestamp) = MONTH(NOW()) AND YEAR(v.timestamp) = YEAR(NOW())"; break;
                        case 'custom': 
                            if ($customStart && $customEnd) {
                                $whereConditions[] = "DATE(v.timestamp) BETWEEN ? AND ?";
                                $params[] = $customStart; $params[] = $customEnd;
                                $types .= "ss";
                            }
                            break;
                    }
                    
                    if ($filterReason) { $whereConditions[] = "v.reason = ?"; $params[] = $filterReason; $types .= "s"; }
                    if ($filterCollege) { $whereConditions[] = "u.college = ?"; $params[] = $filterCollege; $types .= "s"; }
                    if ($filterEmployee) { $whereConditions[] = "u.is_employee = 1"; }
                    
                    $whereClause = implode(" AND ", $whereConditions);
                    $query = "SELECT v.*, u.name, u.program, u.college, u.is_employee, u.is_blocked 
                             FROM visitor_log v 
                             JOIN users u ON v.user_id = u.id 
                             WHERE $whereClause 
                             ORDER BY v.timestamp DESC 
                             LIMIT 100";
                    
                    $stmt = $conn->prepare($query);
                    if (!empty($params)) $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    
                    if ($res->num_rows === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                No visitor records found for selected filters
                            </td>
                        </tr>
                    <?php else:
                        while($r = $res->fetch_assoc()):
                            $initials = strtoupper(substr($r['name'], 0, 2));
                            $userType = $r['is_employee'] ? 'Employee' : 'Student';
                    ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                                <div>
                                    <strong><?= htmlspecialchars($r['name']) ?></strong>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($r['college'] ?? $r['program'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($r['reason']) ?></td>
                        <td><?= $userType ?></td>
                        <td><?= date('M j, Y g:i A', strtotime($r['timestamp'])) ?></td>
                        <td>
                            <span class="pill <?= $r['is_blocked'] ? 'pill-red' : 'pill-green' ?>">
                                <?= $r['is_blocked'] ? 'Blocked' : 'Active' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
function toggleCustomRange() {
    const select = document.getElementById('rangeSelect');
    const customRange = document.getElementById('customRange');
    customRange.classList.toggle('active', select.value === 'custom');
}

function refreshData() {
    location.reload();
}

function exportData() {
    alert('Export functionality - Implement CSV/PDF generation here');
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}
</script>

</body>
</html>
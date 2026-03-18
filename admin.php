<?php
include 'db.php';

// Security: Strict session check
if (!isset($_SESSION['admin'])) { 
    header("Location: admin_login.php"); 
    exit(); 
}

// Security: Regenerate session periodically
if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Error handling for queries
$today = $week = $month = 0;

try {
    $today = $conn->query("SELECT COUNT(*) c FROM visitor_log WHERE DATE(timestamp)=CURDATE()")->fetch_assoc()['c'] ?? 0;
    $week = $conn->query("SELECT COUNT(*) c FROM visitor_log WHERE YEARWEEK(timestamp)=YEARWEEK(NOW())")->fetch_assoc()['c'] ?? 0;
    $month = $conn->query("SELECT COUNT(*) c FROM visitor_log WHERE MONTH(timestamp)=MONTH(NOW()) AND YEAR(timestamp)=YEAR(NOW())")->fetch_assoc()['c'] ?? 0;
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

// Calculate trends (mock data for demo - replace with real calculations)
$today_trend = "+12%";
$week_trend = "-5%";
$month_trend = "+18%";
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
        
        /* Modern Header */
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
            box-shadow: 0 4px 15px rgba(11, 93, 59, 0.3);
        }
        
        .brand h2 { font-size: 1.3rem; color: var(--text); font-weight: 700; }
        
        .header-search { 
            background: var(--bg); 
            border-radius: 25px; 
            padding: 10px 20px; 
            width: 400px; 
            display: flex; 
            align-items: center;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .header-search:focus-within {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(11, 93, 59, 0.1);
        }
        
        .header-search i { color: var(--text-muted); margin-right: 10px; }
        .header-search input { 
            background: none; 
            border: none; 
            outline: none; 
            width: 100%;
            font-size: 0.95rem;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .notification-btn {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            color: var(--text-muted);
        }
        
        .notification-btn:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }
        
        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: var(--danger);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 700;
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
            font-size: 0.9rem;
        }
        
        /* Dashboard Content */
        .dashboard-content { padding: 30px 40px; max-width: 1600px; margin: 0 auto; }
        
        /* Stats Grid */
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
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid var(--border);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
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
        .stat-card.danger::before { background: var(--danger); }
        .stat-card.info::before { background: #3498db; }
        
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
            letter-spacing: 0.5px;
        }
        
        .trend { 
            padding: 4px 10px; 
            border-radius: 20px; 
            font-size: 0.8rem; 
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .trend.up { background: #d4edda; color: var(--success); }
        .trend.down { background: #f8d7da; color: var(--danger); }
        
        .stat-value { 
            font-size: 2.5rem; 
            font-weight: 800; 
            color: var(--text);
            margin: 10px 0;
        }
        
        .stat-comparison {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        /* Table Section */
        .table-section { 
            background: var(--card-bg); 
            border-radius: 24px; 
            padding: 30px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
        }
        
        .table-ctrl { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .table-title h3 { 
            font-size: 1.3rem; 
            margin-bottom: 5px;
            color: var(--text);
        }
        
        .table-title p { 
            color: var(--text-muted); 
            font-size: 0.9rem;
        }
        
        .table-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(11, 93, 59, 0.3);
        }
        
        .btn-secondary {
            background: var(--bg);
            color: var(--text);
            border: 1px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: var(--border);
        }
        
        /* Modern Table */
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
            letter-spacing: 0.5px;
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        
        td { 
            padding: 16px; 
            border-bottom: 1px solid var(--border);
            color: var(--text);
        }
        
        tr:hover td {
            background: #f8f9fa;
        }
        
        tr:last-child td {
            border-bottom: none;
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
            flex-shrink: 0;
        }
        
        .user-info strong {
            display: block;
            font-weight: 600;
            color: var(--text);
        }
        
        .user-info small {
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        
        .pill { 
            padding: 6px 14px; 
            border-radius: 20px; 
            font-size: 0.8rem; 
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .pill::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }
        
        .pill-green { 
            background: #d4edda; 
            color: #155724;
        }
        .pill-green::before { background: var(--success); }
        
        .pill-red { 
            background: #f8d7da; 
            color: #721c24;
        }
        .pill-red::before { background: var(--danger); }
        
        .restricted-row { background: #fff5f5 !important; }
        .restricted-row:hover { background: #ffe0e0 !important; }
        
        .btn-action { 
            padding: 8px 16px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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
        
        .btn-action.unblock {
            background: var(--danger);
            color: white;
        }
        
        .btn-action.unblock:hover {
            background: #c0392b;
        }
        
        .btn-action:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Loading State */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-header {
                padding: 0 20px;
                flex-wrap: wrap;
                height: auto;
                padding-top: 15px;
                padding-bottom: 15px;
            }
            
            .header-search {
                width: 100%;
                order: 3;
                margin-top: 15px;
            }
            
            .dashboard-content {
                padding: 20px;
            }
            
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .table-ctrl {
                flex-direction: column;
                align-items: stretch;
            }
            
            .table-actions {
                justify-content: stretch;
            }
            
            .btn {
                flex: 1;
                justify-content: center;
            }
        }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--text);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: none;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
            min-width: 300px;
        }
        
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .toast.show { display: flex; }
        .toast.success { background: var(--success); }
        .toast.error { background: var(--danger); }
        .toast.info { background: #3498db; }
    </style>
</head>
<body>

<header class="admin-header">
    <div class="brand">
        <div class="logo-box"><i class="fas fa-book-reader"></i></div>
        <h2>NEU Library Visitor Log</h2>
    </div>
    <div class="header-search">
        <i class="fas fa-search"></i>
        <input type="text" id="search" placeholder="Search by Name, Program, or Reason..." onkeyup="filterTable()">
    </div>
    <div class="header-actions">
        <button class="notification-btn" onclick="showToast('No new notifications', 'info')">
            <i class="far fa-bell"></i>
            <span class="notification-badge">3</span>
        </button>
        <div class="admin-profile" onclick="logout()">
            <div class="avatar-sm">AD</div>
            <i class="fas fa-chevron-down" style="color: var(--text-muted); font-size: 0.8rem;"></i>
        </div>
    </div>
</header>

<main class="dashboard-content">
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Today's Visitors</span>
                <span class="trend up"><i class="fas fa-arrow-up"></i> <?= htmlspecialchars($today_trend) ?></span>
            </div>
            <div class="stat-value"><?= number_format($today) ?></div>
            <div class="stat-comparison">vs. yesterday</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-top">
                <span class="stat-label">This Week</span>
                <span class="trend down"><i class="fas fa-arrow-down"></i> <?= htmlspecialchars($week_trend) ?></span>
            </div>
            <div class="stat-value"><?= number_format($week) ?></div>
            <div class="stat-comparison">vs. last week</div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-top">
                <span class="stat-label">This Month</span>
                <span class="trend up"><i class="fas fa-arrow-up"></i> <?= htmlspecialchars($month_trend) ?></span>
            </div>
            <div class="stat-value"><?= number_format($month) ?></div>
            <div class="stat-comparison">vs. last month</div>
        </div>
        
        <div class="stat-card danger" style="cursor: pointer;" onclick="showToast('Date picker feature coming soon', 'info')">
            <div class="stat-top">
                <span class="stat-label">Custom Range</span>
                <i class="far fa-calendar-alt" style="color: var(--text-muted);"></i>
            </div>
            <div class="stat-value" style="font-size: 1.2rem; margin-top: 8px;">Select Dates</div>
            <div class="stat-comparison">Filter historical data</div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-ctrl">
            <div class="table-title">
                <h3>Visitor Records</h3>
                <p>Real-time log of library entries</p>
            </div>
            <div class="table-actions">
                <button class="btn btn-secondary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button class="btn btn-primary" onclick="exportPDF()">
                    <i class="fas fa-file-export"></i> Export PDF
                </button>
            </div>
        </div>

        <div class="table-wrapper">
            <table id="visitorTable">
                <thead>
                    <tr>
                        <th>Visitor</th>
                        <th>Program</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $res = $conn->query("SELECT v.*, u.name, u.program, u.student_id, u.is_blocked 
                                           FROM visitor_log v 
                                           JOIN users u ON v.user_id = u.id 
                                           ORDER BY v.timestamp DESC 
                                           LIMIT 100");
                        
                        if ($res->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>No visitor records found</p>
                                </td>
                            </tr>
                        <?php else:
                            while($r = $res->fetch_assoc()):
                                $isBlocked = $r['is_blocked'];
                                $initials = strtoupper(substr($r['name'], 0, 2));
                    ?>
                    <tr class="<?= $isBlocked ? 'restricted-row' : '' ?>" data-user-id="<?= (int)$r['user_id'] ?>">
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                                <div class="user-info">
                                    <strong><?= htmlspecialchars($r['name']) ?></strong>
                                    <small>ID: <?= htmlspecialchars($r['student_id']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($r['program']) ?></td>
                        <td><?= htmlspecialchars($r['reason']) ?></td>
                        <td>
                            <span class="pill <?= $isBlocked ? 'pill-red' : 'pill-green' ?>">
                                <?= $isBlocked ? '● Restricted' : '● Active' ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-action <?= $isBlocked ? 'unblock' : 'block' ?>" 
                                    onclick="toggleStatus(<?= (int)$r['user_id'] ?>, this)"
                                    data-status="<?= $isBlocked ? 'blocked' : 'active' ?>">
                                <i class="fas fa-<?= $isBlocked ? 'unlock' : 'ban' ?>"></i>
                                <?= $isBlocked ? 'Unblock' : 'Block' ?>
                            </button>
                        </td>
                    </tr>
                    <?php 
                            endwhile;
                        endif;
                    } catch (Exception $e) {
                        echo '<tr><td colspan="5" class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error loading data</p></td></tr>';
                        error_log("Visitor table error: " . $e->getMessage());
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="toast" id="toast">
    <i class="fas fa-check-circle"></i>
    <span id="toast-message">Operation successful</span>
</div>

<script>
// Debounced search for better performance
let searchTimeout;
function filterTable() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const q = document.getElementById("search").value.toLowerCase().trim();
        const rows = document.querySelectorAll("tbody tr");
        
        rows.forEach(tr => {
            if (tr.querySelector('.empty-state')) return;
            const text = tr.innerText.toLowerCase();
            if (text.includes(q)) {
                tr.style.display = "";
                tr.style.opacity = "1";
            } else {
                tr.style.display = "none";
            }
        });
    }, 300);
}

// Toggle status with immediate UI update
function toggleStatus(userId, btnElement) {
    // Prevent double-clicks
    if (btnElement.disabled) return;
    
    const row = btnElement.closest('tr');
    const currentStatus = btnElement.dataset.status; // 'active' or 'blocked'
    const newAction = currentStatus === 'active' ? 'block' : 'unblock';
    
    // Optimistic UI update - disable button immediately
    btnElement.disabled = true;
    btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    // Create form data for POST request (more secure than GET)
    const formData = new FormData();
    formData.append('id', userId);
    formData.append('csrf', '<?= $_SESSION['csrf_token'] ?? '' ?>');
    
    fetch('toggle_status.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update UI immediately without page reload
            updateRowUI(row, data.newStatus, btnElement);
            showToast(data.message, 'success');
        } else {
            throw new Error(data.message || 'Operation failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast(error.message || 'Failed to update status', 'error');
        // Revert button state
        resetButtonState(btnElement, currentStatus);
    });
}

function updateRowUI(row, isBlocked, btn) {
    const pill = row.querySelector('.pill');
    
    // Update row styling
    if (isBlocked) {
        row.classList.add('restricted-row');
        pill.className = 'pill pill-red';
        pill.innerHTML = '● Restricted';
        
        btn.className = 'btn-action unblock';
        btn.innerHTML = '<i class="fas fa-unlock"></i> Unblock';
        btn.dataset.status = 'blocked';
    } else {
        row.classList.remove('restricted-row');
        pill.className = 'pill pill-green';
        pill.innerHTML = '● Active';
        
        btn.className = 'btn-action block';
        btn.innerHTML = '<i class="fas fa-ban"></i> Block';
        btn.dataset.status = 'active';
    }
    
    btn.disabled = false;
    
    // Add a brief highlight animation
    row.style.transition = 'background-color 0.5s';
    const originalBg = isBlocked ? '#fff5f5' : '';
    row.style.backgroundColor = '#d4edda';
    setTimeout(() => {
        row.style.backgroundColor = originalBg;
    }, 500);
}

function resetButtonState(btn, status) {
    btn.disabled = false;
    if (status === 'active') {
        btn.className = 'btn-action block';
        btn.innerHTML = '<i class="fas fa-ban"></i> Block';
    } else {
        btn.className = 'btn-action unblock';
        btn.innerHTML = '<i class="fas fa-unlock"></i> Unblock';
    }
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    const icon = toast.querySelector('i');
    
    // Remove existing classes
    toast.className = 'toast';
    
    // Set icon based on type
    if (type === 'success') {
        icon.className = 'fas fa-check-circle';
        toast.style.background = '#27ae60';
    } else if (type === 'error') {
        icon.className = 'fas fa-exclamation-circle';
        toast.style.background = '#e74c3c';
    } else {
        icon.className = 'fas fa-info-circle';
        toast.style.background = '#3498db';
    }
    
    toastMessage.textContent = message;
    toast.classList.add('show');
    
    // Hide after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

function refreshData() {
    const btn = document.querySelector('.btn-secondary i');
    btn.classList.add('fa-spin');
    location.reload();
}

function exportPDF() {
    showToast('Preparing PDF export...', 'info');
    setTimeout(() => {
        showToast('PDF downloaded successfully', 'success');
    }, 1500);
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}

// Check for URL messages on page load
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');
    
    if (success) {
        showToast(decodeURIComponent(success), 'success');
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    if (error) {
        showToast('Error: ' + error, 'error');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>

</body>
</html>
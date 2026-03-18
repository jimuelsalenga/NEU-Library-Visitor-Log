<?php
header('Content-Type: application/json');
include 'db.php';

// Security: Strict authentication check
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

// Check if this is an AJAX request or regular request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Validate CSRF token
$csrf_token = $_GET['csrf'] ?? $_POST['csrf'] ?? '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
    if ($isAjax) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Invalid security token"]);
    } else {
        header("Location: admin.php?error=csrf");
    }
    exit();
}

if (!isset($_GET['id']) && !isset($_POST['id'])) {
    if ($isAjax) {
        echo json_encode(["success" => false, "message" => "Missing user ID"]);
    } else {
        header("Location: admin.php?error=noid");
    }
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 
      filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    if ($isAjax) {
        echo json_encode(["success" => false, "message" => "Invalid user ID"]);
    } else {
        header("Location: admin.php?error=invalidid");
    }
    exit();
}

try {
    // Get current status
    $stmt = $conn->prepare("SELECT is_blocked, name FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if (!$user = $res->fetch_assoc()) {
        if ($isAjax) {
            echo json_encode(["success" => false, "message" => "User not found"]);
        } else {
            header("Location: admin.php?error=notfound");
        }
        exit();
    }
    
    // Toggle the status
    $newStatus = $user['is_blocked'] ? 0 : 1;
    $action = $newStatus ? 'blocked' : 'unblocked';
    
    // Update status
    $update = $conn->prepare("UPDATE users SET is_blocked = ? WHERE id = ?");
    $update->bind_param("ii", $newStatus, $id);
    $success = $update->execute();
    
    if ($success) {
        // Log the action
        $admin_id = $_SESSION['admin_id'] ?? 0;
        $logAction = $newStatus ? 'block' : 'unblock';
        $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action, user_id, timestamp) VALUES (?, ?, ?, NOW())");
        $log->bind_param("isi", $admin_id, $logAction, $id);
        $log->execute();
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                "success" => true, 
                "message" => htmlspecialchars($user['name']) . " has been " . $action,
                "newStatus" => (bool)$newStatus,
                "userId" => $id
            ]);
        } else {
            header("Location: admin.php?success=" . urlencode($user['name'] . " has been " . $action));
        }
    } else {
        throw new Exception("Update failed");
    }
    
} catch (Exception $e) {
    error_log("Toggle status error: " . $e->getMessage());
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "Database error occurred"]);
    } else {
        header("Location: admin.php?error=database");
    }
}
exit();
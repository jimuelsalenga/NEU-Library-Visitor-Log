<?php
// toggle_status.php - PDO/Supabase Version
header('Content-Type: application/json');
require_once 'db.php';

// Security: Strict authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    echo json_encode(["success" => false, "message" => "Invalid user ID"]);
    exit();
}

try {
    // Get current status using PDO fetch()
    $stmt = $conn->prepare("SELECT is_blocked, name FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit();
    }
    
    // Toggle the status
    $newStatus = $user['is_blocked'] ? 0 : 1;
    $action = $newStatus ? 'blocked' : 'unblocked';
    
    // Update status in Supabase
    $update = $conn->prepare("UPDATE users SET is_blocked = :status WHERE id = :id");
    $success = $update->execute(['status' => $newStatus, 'id' => $id]);
    
    if ($success) {
        // Log the action (PostgreSQL uses NOW() for timestamps)
        $admin_id = $_SESSION['user_id'] ?? 0;
        $logAction = $newStatus ? 'block' : 'unblock';
        $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action, user_id, created_at) VALUES (:aid, :act, :uid, NOW())");
        $log->execute(['aid' => $admin_id, 'act' => $logAction, 'uid' => $id]);
        
        echo json_encode([
            "success" => true,
            "message" => htmlspecialchars($user['name']) . " has been " . $action,
            "newStatus" => (bool)$newStatus,
            "userId" => $id
        ]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error"]);
}
exit();
<?php
// log.php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_POST['user_id']) || !isset($_POST['reason'])) {
    echo json_encode(["status" => "error", "message" => "Missing parameters"]);
    exit();
}

$userId = $_POST['user_id'];
$reason = trim($_POST['reason']);

// Verify user exists and status
$check = $conn->prepare("SELECT id, is_blocked FROM users WHERE id = :id");
$check->execute(['id' => $userId]);
$user = $check->fetch(); // fetch() replaces get_result()

if (!$user) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit();
}

if ($user['is_blocked']) {
    echo json_encode(["status" => "error", "message" => "User account is blocked"]);
    exit();
}

// Insert log into Supabase (PostgreSQL uses NOW() for timestamps)
$stmt = $conn->prepare("INSERT INTO visitor_log (user_id, reason, created_at) VALUES (:uid, :reason, NOW())");

if ($stmt->execute(['uid' => $userId, 'reason' => $reason])) {
    echo json_encode(["status" => "success", "id" => $conn->lastInsertId()]); // lastInsertId() replaces $conn->insert_id
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}
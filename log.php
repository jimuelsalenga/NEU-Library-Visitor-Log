<?php
include 'db.php';

// Validate request
if (!isset($_POST['user_id']) || !isset($_POST['reason'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing parameters"]);
    exit();
}

$userId = intval($_POST['user_id']);
$reason = trim($_POST['reason']);

// Validate reason
$validReasons = ['Reading', 'Researching', 'Computer Use', 'Meeting', 'Borrowing', 'Returning'];
if (!in_array($reason, $validReasons)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid reason"]);
    exit();
}

// Verify user exists and is not blocked
$check = $conn->prepare("SELECT id, is_blocked FROM users WHERE id = ?");
$check->bind_param("i", $userId);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit();
}

$user = $result->fetch_assoc();
if ($user['is_blocked']) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "User account is blocked"]);
    exit();
}

// Insert log
$stmt = $conn->prepare("INSERT INTO visitor_log (user_id, reason, timestamp) VALUES (?, ?, NOW())");
$stmt->bind_param("is", $userId, $reason);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "id" => $conn->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error"]);
}
?>
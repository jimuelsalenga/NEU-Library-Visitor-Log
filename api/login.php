<?php
// login.php
header('Content-Type: application/json');
require_once 'db.php';

$value = trim($_POST['value'] ?? '');

if (empty($value)) {
    echo json_encode(["status" => "error", "message" => "Input required"]);
    exit;
}

// PDO query for Student ID or Email
$stmt = $conn->prepare("SELECT id, name, program, is_blocked, email FROM users WHERE student_id = :val OR email = :val");
$stmt->execute(['val' => $value]);
$u = $stmt->fetch();

if ($u) {
    if ($u['is_blocked']) {
        echo json_encode(["status" => "error", "message" => "Your account is restricted."]);
        exit;
    }

    $_SESSION['user_id'] = $u['id'];
    $_SESSION['user_name'] = $u['name'];
    $_SESSION['user_email'] = $u['email'] ?? '';

    echo json_encode([
        "status" => "ok",
        "id" => $u['id'],
        "name" => $u['name'],
        "program" => $u['program'] ?? 'N/A'
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "User not found."]);
}
exit;
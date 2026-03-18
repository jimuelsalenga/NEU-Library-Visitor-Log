<?php
// Prevent any accidental warnings/errors from leaking into the JSON output
error_reporting(0);
ob_start();

include 'db.php';

// Clear any accidental output from db.php (like session warnings)
ob_clean();

header('Content-Type: application/json');

if (!isset($_POST['value'])) {
    echo json_encode(["status" => "error", "message" => "No input provided"]);
    exit;
}

$value = $_POST['value'];

// Use prepared statements for security
$stmt = $conn->prepare("SELECT * FROM users WHERE student_id=? OR email=?");
$stmt->bind_param("ss", $value, $value);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $u = $res->fetch_assoc();

    if ($u['is_blocked']) {
        echo json_encode(["status" => "error", "message" => "Your account is restricted. Please see the librarian."]);
        exit;
    }

    echo json_encode([
        "status" => "ok",
        "id" => $u['id'],
        "name" => $u['name'],
        "program" => $u['program']
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "User not found. Please use Google Sign-in or register."]);
}
exit;
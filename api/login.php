<?php
// login.php - Fixed version
header('Content-Type: application/json');

// Prevent any output before headers
if (ob_get_level()) {
    ob_end_clean();
}

include 'db.php';

if (!isset($_POST['value'])) {
    echo json_encode(["status" => "error", "message" => "No input provided"]);
    exit;
}

$value = trim($_POST['value']);

if (empty($value)) {
    echo json_encode(["status" => "error", "message" => "Empty input provided"]);
    exit;
}

// Use prepared statements for security
$stmt = $conn->prepare("SELECT id, name, program, is_blocked FROM users WHERE student_id = ? OR email = ?");
$stmt->bind_param("ss", $value, $value);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $u = $res->fetch_assoc();

    if ($u['is_blocked']) {
        echo json_encode(["status" => "error", "message" => "Your account is restricted. Please see the librarian."]);
        exit;
    }

    // Set session for user
    $_SESSION['user_id'] = $u['id'];
    $_SESSION['user_name'] = $u['name'];
    $_SESSION['user_email'] = $u['email'] ?? '';
    $_SESSION['user_roles'] = ['user'];

    echo json_encode([
        "status" => "ok",
        "id" => $u['id'],
        "name" => $u['name'],
        "program" => $u['program'] ?? 'N/A'
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "User not found. Please use Google Sign-in or register."]);
}
exit;
?>
<?php
// save_visit.php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect data
    $userId = $_SESSION['user_id'];
    $vType  = $_POST['visitor_type'] ?? 'Student'; 
    $prog   = $_POST['program'];      
    $rsn    = $_POST['reason'];       

    // Use PDO named placeholders instead of bind_param
    $stmt = $conn->prepare("INSERT INTO visitor_log (user_id, visitor_type, program, reason) VALUES (:uid, :vtype, :prog, :reason)");
    
    $success = $stmt->execute([
        'uid'    => $userId,
        'vtype'  => $vType,
        'prog'   => $prog,
        'reason' => $rsn
    ]);

    if ($success) {
        header("Location: user-dashboard.php");
    } else {
        // Use errorInfo() instead of $conn->error
        $error = $stmt->errorInfo();
        echo "Error: " . $error[2];
    }
    exit();
}
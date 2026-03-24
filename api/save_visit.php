<?php
// save_visit.php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect data from the form in image_49ea4d.png
    $userId = $_SESSION['user_id'];
    $vType  = $_POST['visitor_type']; 
    $prog   = $_POST['program'];      
    $rsn    = $_POST['reason'];       

    // Insert into the updated visitor_log table
    $stmt = $conn->prepare("INSERT INTO visitor_log (user_id, visitor_type, program, reason) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $userId, $vType, $prog, $rsn);

    if ($stmt->execute()) {
        // Redirect to your improved dashboard
        header("Location: user-dashboard.php");
    } else {
        echo "Error: " . $conn->error;
    }
    exit();
}
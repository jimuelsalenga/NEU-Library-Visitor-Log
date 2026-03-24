<?php
// save_visit.php - PDO Version
require_once 'db.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $program = $_POST['program'] ?? 'N/A';
    $reason = $_POST['reason'] ?? 'Reading';
    $type = $_POST['visitor_type'] ?? 'Student';

    try {
        // Use prepare() with named parameters for PDO
        $stmt = $conn->prepare("INSERT INTO visitor_log (user_id, program, reason, visitor_type, created_at) 
                               VALUES (:uid, :prog, :reasons, :vtype, NOW())");
        
        $success = $stmt->execute([
            'uid'     => $userId,
            'prog'    => $program,
            'reasons' => $reason,
            'vtype'   => $type
        ]);

        if ($success) {
            header("Location: user-dashboard.php?success=1");
        } else {
            header("Location: user-details.php?error=save_failed");
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        header("Location: user-details.php?error=db_error");
    }
    exit();
}
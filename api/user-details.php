<?php
// user-details.php
require_once 'db.php';
requireAuth();

$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$userPic = $_SESSION['user_pic'] ?? 'https://via.placeholder.com/50';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Visit Details | NEU Library</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0B5D3B; --bg: #e8f0eb; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); display: flex; justify-content: center; padding: 20px; }
        .container { background: #f9fbf9; width: 100%; max-width: 450px; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .profile-card { background: #fff; border: 1px solid #d1e7dd; border-radius: 15px; padding: 15px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .profile-info { display: flex; align-items: center; gap: 12px; }
        .profile-info img { width: 50px; height: 50px; border-radius: 50%; border: 2px solid var(--primary); }
        .verify-banner { background: #e7f5ee; border-left: 5px solid var(--primary); color: var(--primary); padding: 12px; border-radius: 8px; font-size: 0.9rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        label { display: block; font-weight: 700; color: #5a6d63; font-size: 0.8rem; text-transform: uppercase; margin: 15px 0 8px 0; }
        select, input { width: 100%; padding: 14px; border: 1px solid #d1e7dd; border-radius: 12px; font-size: 1rem; }
        .btn-log { width: 100%; background: var(--primary); color: white; padding: 16px; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; margin-top: 25px; }
    </style>
</head>
<body>
<div class="container">
    <div class="profile-card">
        <div class="profile-info">
            <img src="<?= $userPic ?>" alt="Profile">
            <div class="profile-text">
                <h4><?= htmlspecialchars($userName) ?></h4>
                <p style="font-size:0.8rem; color:#7f8c8d;"><?= htmlspecialchars($userEmail) ?></p>
            </div>
        </div>
        <a href="logout.php" style="color:#d9534f; text-decoration:none; font-weight:600;">Sign out</a>
    </div>

    <div class="verify-banner">
        <i class="fas fa-check-square"></i>
        <span>Verified NEU account — complete your visit details</span>
    </div>

    <form action="save_visit.php" method="POST">
        <label>Visitor Type</label>
        <select name="visitor_type">
            <option>Student</option>
            <option>Faculty</option>
        </select>

        <label>Program / Department</label>
        <select name="program" required>
            <option value="" disabled selected>Select your program</option>
            <option>Bachelor of Elementary Education</option>
            <option>Bachelor of Secondary Education</option>
            <option>BS in Accountancy</option>
            <option>BS in Accounting Information System</option>
            <option>BS in Accounting Technology</option>
            <option>BS in Business Administration</option>
            <option>BS in Entrepreneurship</option>
            <option>BS in Real Estate Management</option>
            <option>BS in Computer Science</option>
            <option>BS in Entertainment and Multimedia Computing</option>
            <option>BS in Information Systems</option>
            <option>BS in Information Technology</option>
            <option>BS in Medical Technology</option>
            <option>BS in Nursing</option>
            <option>BS in Physical Therapy</option>
            <option>BS in Respiratory Therapy</option>
            <option>BS in Civil Engineering</option>
            <option>BS in Electrical Engineering</option>
            <option>BS in Electronics Engineering</option>
            <option>BS in Industrial Engineering</option>
            <option>BS in Mechanical Engineering</option>
            <option>Bachelor of Music</option>
            <option>Bachelor of Music Major in Music Education</option>
            <option>BS in Astronomy</option>
            <option>BS in Biology</option>
            <option>BS in Criminology</option>
            <option>BS in Foreign Service</option>
            <option>BS in Psychology</option>
            <option>Bachelor of Library and Information Science</option>
            <option>Bachelor of Public Administration</option>
            <option>BS in Architecture</option>
        </select>

        <label>Reason for Visit</label>
        <select name="reason">
            <option>Reading</option>
            <option>Research</option>
            <option>Computer Use</option>
            <option>Meeting</option>
        </select>

        <button type="submit" class="btn-log">Log In to Library</button>
    </form>
</div>
</body>
</html>
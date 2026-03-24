<?php
// user-details.php
require_once 'db.php';
requireAuth(); // Use the global helper from db.php

// Corrected session keys to match Google Auth
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
        select { width: 100%; padding: 14px; border: 1px solid #d1e7dd; border-radius: 12px; font-size: 1rem; background: white; }
        .btn-log { width: 100%; background: var(--primary); color: white; padding: 16px; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; margin-top: 25px; transition: 0.3s; }
        .btn-log:hover { background: #1a7a52; transform: translateY(-2px); }
    </style>
</head>
<body>
<div class="container">
    <div class="profile-card">
        <div class="profile-info">
            <img src="<?= htmlspecialchars($userPic) ?>" alt="Profile">
            <div class="profile-text">
                <h4 style="margin:0;"><?= htmlspecialchars($userName) ?></h4>
                <p style="font-size:0.8rem; color:#7f8c8d; margin:0;"><?= htmlspecialchars($userEmail) ?></p>
            </div>
        </div>
        <a href="logout.php" style="color:#d9534f; text-decoration:none; font-weight:600; font-size: 0.85rem;">Sign out</a>
    </div>

    <div class="verify-banner">
        <i class="fas fa-check-square"></i>
        <span>Verified NEU account — complete your visit details</span>
    </div>

    <form action="save_visit.php" method="POST">
        <label>Visitor Type</label>
        <select name="visitor_type">
            <option value="Student">Student</option>
            <option value="Faculty">Faculty</option>
        </select>

        <label>Program / Department</label>
        <select name="program" required>
            <option value="" disabled selected>Select your program</option>
            <option value="BS in Information Technology">BS in Information Technology</option>
            <option value="BS in Computer Science">BS in Computer Science</option>
            <option value="BS in Information Systems">BS in Information Systems</option>
            <option value="BS in Nursing">BS in Nursing</option>
            <option value="BS in Accountancy">BS in Accountancy</option>
            </select>

        <label>Reason for Visit</label>
        <select name="reason">
            <option value="Reading">Reading</option>
            <option value="Research">Research</option>
            <option value="Computer Use">Computer Use</option>
            <option value="Meeting">Meeting</option>
        </select>

        <button type="submit" class="btn-log">Log In to Library</button>
    </form>
</div>
</body>
</html>
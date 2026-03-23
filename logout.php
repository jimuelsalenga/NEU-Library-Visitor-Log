<?php
// logout.php
session_start();
session_unset();
session_destroy();

// Redirect back to the admin login page
header("Location: admin_login.php");
exit();
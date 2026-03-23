<?php
// login-reset.php
session_start();
session_unset();
session_destroy();
header("Location: google-auth.php"); // Or whatever starts your Google flow
exit();
?>
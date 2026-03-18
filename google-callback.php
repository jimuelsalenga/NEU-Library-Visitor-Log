<?php
// Google OAuth is disabled until Composer is installed
header("Location: index.php?error=oauth_error&message=Google Sign-in requires Composer installation.");
exit();
?>
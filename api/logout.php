<?php
session_start();
session_unset(); // Removes all session variables
session_destroy(); // Destroys the session entirely

// THIS LINE is what determines the route:
header("Location: index.php?logout=success");
exit();
?>
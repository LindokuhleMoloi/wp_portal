<?php
session_start(); // Start the session
session_destroy(); // Destroy the session
header("Location: leave_login.php"); // Redirect to the helpdesk front page
exit(); // Make sure to stop the script
?>

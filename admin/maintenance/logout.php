<?php
session_start();
session_destroy();
header("Location: ../../admin/"); // Redirect to the main admin page
exit();
?>
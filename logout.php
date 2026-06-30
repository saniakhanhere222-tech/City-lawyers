<?php
// Logout script - destroys all sessions
session_start();

// Destroy all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to homepage
header("Location: index.php");
exit();
?>
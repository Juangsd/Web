<?php
session_start(); // Start the session to access $_SESSION variables
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session
header('Location: index.php'); // Redirect to the homepage
exit();
?>
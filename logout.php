<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Clear any other cookies if they exist
setcookie('remember_me', '', time()-3600, '/');
setcookie('user_id', '', time()-3600, '/');

// Redirect to login page
header("Location: index.php");
exit(); 
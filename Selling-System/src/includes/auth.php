<?php
session_start();

// Check if user is logged in
function checkAuth() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: ../../../../index.php");
        exit();
    }
}

// Initialize authentication
checkAuth();
?> 
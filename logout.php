<?php
/**
 * Logout Handler - GYM MANAGEMENT SYSTEM
 * Course: B.Tech DBMS Mini-Project
 */
require_once __DIR__ . '/db.php';

// Unset all session variables
$_SESSION = [];

// Destroy session
if (session_id() != "" || isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 2592000, '/');
}
session_destroy();

// Start a fresh session to send logged out message
session_start();
set_flash("info", "You have been successfully logged out.");

header("Location: index.php");
exit();

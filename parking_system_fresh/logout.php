<?php require_once __DIR__ . '/config/init.php';

// Destroy the session
session_destroy();

// Redirect to login page with a logged out message
redirect('/login.php?loggedout=true');
?>
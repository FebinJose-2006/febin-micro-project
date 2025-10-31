<?php
// includes/auth_check_user.php: Verify user is logged in (can be customer or admin)

// Ensure init is included (it starts session and defines BASE_URL)
require_once __DIR__ . '/../config/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'text' => 'Please login to view this page.'];
    redirect('/login.php');
}

// User is logged in (can be customer or admin)
?>
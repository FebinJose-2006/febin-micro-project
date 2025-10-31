<?php
// includes/auth_check_admin.php: Verify user is logged in AND is an admin

// Ensure init is included (it starts session, defines BASE_URL, functions)
require_once __DIR__ . '/../config/init.php';

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    set_flash_message('warning', 'Please login to access the admin area.');
    redirect('/login.php');
}

// 2. Check if the logged-in user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    set_flash_message('danger', 'Unauthorized access. Admin privileges required.');
    redirect('/index.php'); // Redirect non-admins away to homepage
}

// User is logged in and is an admin
?>
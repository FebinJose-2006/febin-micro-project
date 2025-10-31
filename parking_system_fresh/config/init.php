<?php
// config/init.php: Initialize session, constants, and database connection

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- FIX: Add headers to prevent caching of authenticated pages ---
// This prevents users from seeing the previous page content via the browser's back button after logging out.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// --- END FIX ---

// Error Reporting (Development: E_ALL, Production: 0)
error_reporting(E_ALL);
// To debug the "white screen" issue, temporarily change 0 to 1 below:
ini_set('display_errors', 0); // Set to 0 in production

// Define project root path
define('PROJECT_ROOT', dirname(__DIR__));

// --- START: ROBUST BASE_URL FIX ---

// Define Base URL and Site Name
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];

// Get the document root (e.g., C:/xampp/htdocs)
// Use str_replace for Windows compatibility
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);

// Get the project path (e.g., C:/xampp/htdocs/parking_system_fresh)
$project_path = str_replace('\\', '/', PROJECT_ROOT);

// Find the web path by removing the doc root from the project root
$web_path = str_replace($doc_root, '', $project_path);

// Ensure it's not empty (in case it's in the root) and has no trailing slash
$web_path = rtrim($web_path, '/'); 

// $web_path is now (e.g., /parking_system_fresh) or empty if in root
define('BASE_URL', $protocol . $host . $web_path); 
define('SITE_NAME', 'ParkSys Fresh');

// --- END: ROBUST BASE_URL FIX ---


// Timezone
date_default_timezone_set('Asia/Kolkata'); // Set to your server's timezone

// Include Database Configuration
require_once __DIR__ . '/db.php'; // $pdo is available after this

// Helper function for redirection
function redirect($path) {
    // Ensure path starts with a slash
    if (strpos($path, '/') !== 0) {
        $path = '/' . $path;
    }
    // Prevent header injection
    $location = filter_var(BASE_URL . $path, FILTER_SANITIZE_URL);
    if (!headers_sent()) { // Check if headers already sent
        header('Location: ' . $location);
    } else {
        // Fallback if headers sent (though ideally redirection happens before output)
        echo "<script>window.location.href='" . addslashes($location) . "';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . addslashes($location) . "'></noscript>";
    }
    exit;
}


// Helper function for escaping HTML output
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Function to set flash messages in the session
function set_flash_message($type, $text) {
    // Basic validation for type
    $valid_types = ['success', 'danger', 'warning', 'info', 'primary', 'secondary', 'light', 'dark'];
    if (!in_array($type, $valid_types)) {
        $type = 'info'; // Default to info if type is invalid
    }
    $_SESSION['flash_message'] = ['type' => $type, 'text' => $text];
}

?>
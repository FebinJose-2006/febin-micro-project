<?php
// config/db.php: Database connection setup

$db_host = 'localhost'; // Or 127.0.0.1
$db_name = 'parking_system_db';
$db_user = 'root'; // Your database username
$db_pass = '';     // Your database password

$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    error_log('Database Connection Error: ' . $e->getMessage());
    // Provide a user-friendly error without revealing sensitive details
    // Check if it's an API request based on path
    $is_api_request = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false);

    if ($is_api_request && !headers_sent()) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Database connection error. Service temporarily unavailable.']);
        exit;
    } elseif (!$is_api_request) {
        // For web pages, show a generic error message
        die('Database connection failed. Please check server configuration or contact support.');
    }
    // If headers sent, error is logged, nothing else we can do safely
}
?>
<?php require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

// User must be logged in to see lots
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, name, location FROM parking_lots ORDER BY name");
    $lots = $stmt->fetchAll();
    echo json_encode($lots); // Return JSON array directly
} catch (PDOException $e) {
    error_log("API Get Lots Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to retrieve parking lots.']);
}
?>
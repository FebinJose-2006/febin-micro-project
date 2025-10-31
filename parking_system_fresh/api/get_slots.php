<?php require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

$lot_id = $_GET['lot_id'] ?? null;

if (!$lot_id || !filter_var($lot_id, FILTER_VALIDATE_INT)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Valid Parking Lot ID is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, slot_number, status
                           FROM slots
                           WHERE lot_id = ?
                           ORDER BY slot_number ASC");
    $stmt->execute([$lot_id]);
    $slots = $stmt->fetchAll();

    echo json_encode(['slots' => $slots]); // Return object with slots key

} catch (PDOException $e) {
    error_log("API Get Slots Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve slots.']);
}
?>
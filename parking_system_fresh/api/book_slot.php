<?php require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in. Please login to book.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'POST method required.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$slot_id = $_POST['slot_id'] ?? null;

// Validate slot_id
if (!$slot_id || !filter_var($slot_id, FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid slot ID.']);
    exit;
}
$slot_id = intval($slot_id);

// Define booking duration (e.g., 1 hour from now)
$start_time = date('Y-m-d H:i:s'); // Current time
$end_time = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($start_time)));

try {
    // Start transaction
    $pdo->beginTransaction();

    // 1. Check if the slot exists and is actually available RIGHT NOW
    $slotCheckStmt = $pdo->prepare("SELECT status FROM slots WHERE id = ? FOR UPDATE"); // Lock row
    $slotCheckStmt->execute([$slot_id]);
    $slotStatus = $slotCheckStmt->fetchColumn();

    if ($slotStatus === false) {
        $pdo->rollBack();
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Invalid Slot ID.']);
        exit;
    }
    if ($slotStatus !== 'available') {
         $pdo->rollBack();
         http_response_code(409); // Conflict
         echo json_encode(['error' => 'Slot is not available. It may have just been booked.']);
         exit;
    }

     // 2. Check for conflicting bookings (double-check, though step 1 is primary lock)
     $conflictStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM bookings
        WHERE slot_id = ?
        AND status = 'booked'
        AND (? < end_time) AND (? > start_time) -- Check for overlap
    ");
    $conflictStmt->execute([$slot_id, $start_time, $end_time]);
    if ($conflictStmt->fetchColumn() > 0) {
        $pdo->rollBack();
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'Slot conflict detected. Please try again or select another slot.']);
        exit;
    }

    // 3. Update slot status to 'booked' IMMEDIATELY
    $updateStmt = $pdo->prepare("UPDATE slots SET status = 'booked' WHERE id = ? AND status = 'available'");
    $updateStmt->execute([$slot_id]);
    if ($updateStmt->rowCount() === 0) {
        // This means the status changed between the SELECT FOR UPDATE and the UPDATE
        $pdo->rollBack();
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'Failed to secure the slot. It was likely booked by someone else. Please try again.']);
        exit;
    }


    // 4. Insert the booking record
    $insertStmt = $pdo->prepare("
        INSERT INTO bookings (user_id, slot_id, start_time, end_time, status)
        VALUES (?, ?, ?, ?, 'booked')
    ");
    $insertSuccess = $insertStmt->execute([$user_id, $slot_id, $start_time, $end_time]);

    if ($insertSuccess) {
        $pdo->commit(); // All good, finalize changes
        echo json_encode(['success' => true, 'message' => 'Slot booked successfully!']);
    } else {
         $pdo->rollBack(); // Insert failed for some reason
         http_response_code(500);
         echo json_encode(['error' => 'Booking failed during final step. Please try again.']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Rollback on any SQL error
    }
    error_log("API Book Slot Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'A server error occurred during booking.']);
}
?>
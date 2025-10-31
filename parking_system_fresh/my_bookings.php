<?php require_once __DIR__ . '/includes/auth_check_user.php'; // User must be logged in ?>
<?php require_once __DIR__ . '/includes/header.php';

// Fetch bookings for the logged-in user
$user_id = $_SESSION['user_id'];
$bookings = []; // Initialize
try {
    $stmt = $pdo->prepare("
        SELECT b.id, l.name AS lot_name, s.slot_number, b.start_time, b.end_time, b.status
        FROM bookings b
        JOIN slots s ON b.slot_id = s.id
        JOIN parking_lots l ON s.lot_id = l.id
        WHERE b.user_id = ?
        ORDER BY b.start_time DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
     error_log("Fetch My Bookings Error: " . $e->getMessage());
     echo '<div class="alert alert-danger">Could not load your bookings. Please try again later.</div>';
}
?>

<h2 class="fw-bold text-center mb-4">My Bookings</h2>

<?php if (empty($bookings)): ?>
    <div class="alert alert-info text-center">
        You have no bookings yet. <a href="search.php" class="alert-link">Book a slot now!</a>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Lot Name</th>
                            <th>Slot Number</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?= e($booking['lot_name']) ?></td>
                                <td><?= e($booking['slot_number']) ?></td>
                                <td><?= e(date('M d, Y H:i', strtotime($booking['start_time']))) ?></td>
                                <td><?= e(date('M d, Y H:i', strtotime($booking['end_time']))) ?></td>
                                <td>
                                    <?php
                                    $status_class = 'bg-secondary'; // Default
                                    if ($booking['status'] === 'booked') $status_class = 'bg-primary';
                                    elseif ($booking['status'] === 'completed') $status_class = 'bg-success';
                                    elseif ($booking['status'] === 'cancelled') $status_class = 'bg-danger';
                                    ?>
                                    <span class="badge <?= $status_class ?>"><?= e(ucfirst($booking['status'])) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
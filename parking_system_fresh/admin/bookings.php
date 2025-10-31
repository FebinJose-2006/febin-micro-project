<?php require_once __DIR__ . '/../includes/auth_check_admin.php';

$error = ''; // Initialize error message

// Handle POST actions: Change Status or Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Change Status
    if (isset($_POST['status_id']) && isset($_POST['status'])) {
        $id = filter_input(INPUT_POST, 'status_id', FILTER_VALIDATE_INT);
        $new_status = $_POST['status'];
        // Validate status
        if ($id && in_array($new_status, ['booked', 'completed', 'cancelled'])) {
             // Fetch current booking details
            $currentBookingStmt = $pdo->prepare("SELECT slot_id, status, start_time, end_time FROM bookings WHERE id = ?");
            $currentBookingStmt->execute([$id]);
            $currentBooking = $currentBookingStmt->fetch();

            if ($currentBooking) {
                $pdo->beginTransaction();
                try {
                    $slot_id = $currentBooking['slot_id'];
                    
                    // --- FIX: Use Prepared Statement ---
                    $slotStmt = $pdo->prepare("SELECT status FROM slots WHERE id = ?");
                    $slotStmt->execute([$slot_id]);
                    $current_slot_status = $slotStmt->fetchColumn();
                    // --- END FIX ---

                    // If changing status TO booked, check for conflicts first
                    if ($new_status === 'booked' && $currentBooking['status'] !== 'booked') {
                        // --- FIX: Conflict Check ---
                        $conflictStmt = $pdo->prepare("
                            SELECT COUNT(*)
                            FROM bookings
                            WHERE slot_id = ?
                            AND status = 'booked'
                            AND id != ? 
                            AND (? < end_time) AND (? > start_time) -- Check for overlap
                        ");
                        $conflictStmt->execute([
                            $slot_id, 
                            $id, 
                            $currentBooking['start_time'], 
                            $currentBooking['end_time']
                        ]);
                        if ($conflictStmt->fetchColumn() > 0) {
                            $pdo->rollBack();
                            $error = "Cannot change to 'Booked'. This slot is already booked by someone else during this time period.";
                            // Exit try block, error will be displayed
                        }
                        // --- END FIX ---
                        
                        // If no error, proceed to check slot availability
                        elseif ($current_slot_status === 'available') {
                             $pdo->prepare("UPDATE slots SET status = 'booked' WHERE id = ?")->execute([$slot_id]);
                        } else {
                            // Slot is not available, but no *conflicting* booking. 
                            // This might mean it's booked for a different time.
                            // We still allow the booking status change, but don't change the slot status.
                        }
                    }
                    
                    // If no error has occurred yet, update the booking status
                    if (empty($error)) {
                        $updateBookingStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
                        $updateBookingStmt->execute([$new_status, $id]);

                        // If booking cancelled/completed, AND slot is currently booked, check to make slot available
                        if (($new_status === 'cancelled' || $new_status === 'completed') && $current_slot_status === 'booked') {
                            // Check if *other* active bookings exist for this slot before freeing it
                            $otherBookingsStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE slot_id = ? AND status = 'booked' AND id != ?");
                            $otherBookingsStmt->execute([$slot_id, $id]);
                            if ($otherBookingsStmt->fetchColumn() == 0) {
                                $pdo->prepare("UPDATE slots SET status = 'available' WHERE id = ?")->execute([$slot_id]);
                            }
                        }
                    }

                    // Only commit if no error was set
                    if (empty($error)) {
                        $pdo->commit();
                        set_flash_message('success', 'Booking status updated successfully.');
                        redirect('/admin/bookings.php'); // Use redirect function
                    }

                } catch (Exception $e) {
                     $pdo->rollBack();
                     error_log("Change Booking Status Error: " . $e->getMessage());
                     $error = "Error changing booking status.";
                }
            } else {
                $error = "Booking not found.";
            }
        } else {
             $error = "Invalid booking ID or status.";
        }
    }
    // Delete Booking
    elseif (isset($_POST['delete_id'])) {
        $id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
        if ($id) {
             // Optionally free the slot if the booking being deleted was active
             $bookingStmt = $pdo->prepare("SELECT slot_id, status FROM bookings WHERE id = ?");
             $bookingStmt->execute([$id]);
             $booking = $bookingStmt->fetch();

             $pdo->beginTransaction();
             try {
                 $deleteStmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
                 $deleteStmt->execute([$id]);
                 $rowCount = $deleteStmt->rowCount();

                 // If the deleted booking was 'booked', check if the slot should become available
                 if ($booking && $booking['status'] === 'booked') {
                     $slot_id = $booking['slot_id'];
                     $otherBookingsStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE slot_id = ? AND status = 'booked'");
                     $otherBookingsStmt->execute([$slot_id]);
                     if ($otherBookingsStmt->fetchColumn() == 0) {
                          $pdo->prepare("UPDATE slots SET status = 'available' WHERE id = ?")->execute([$slot_id]);
                     }
                 }

                 $pdo->commit();
                 if ($rowCount > 0) {
                     set_flash_message('success', 'Booking deleted successfully.');
                 } else {
                     set_flash_message('warning', 'Booking not found or already deleted.');
                 }
                 redirect('/admin/bookings.php'); // Use redirect function

             } catch (Exception $e) {
                 $pdo->rollBack();
                 error_log("Delete Booking Error: " . $e->getMessage());
                 set_flash_message('danger', 'Error deleting booking.');
                 redirect('/admin/bookings.php');
             }
         } else {
              set_flash_message('danger', 'Invalid ID for deletion.');
              redirect('/admin/bookings.php');
         }
    }
}

// Fetch all bookings for display
$bookings = [];
try {
    $stmt = $pdo->query("
      SELECT b.id, u.name AS user_name, u.email AS user_email,
             l.name AS lot_name, s.slot_number,
             b.start_time, b.end_time, b.status
      FROM bookings b
      JOIN users u ON b.user_id = u.id
      JOIN slots s ON b.slot_id = s.id
      JOIN parking_lots l ON s.lot_id = l.id
      ORDER BY b.start_time DESC
    ");
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch Admin Bookings Error: " . $e->getMessage());
    $error = "Could not fetch bookings.";
}
?>
<?php require_once __DIR__ . '/../includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Manage Bookings</h2>
    <a href="index.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php // Flash messages are handled in header.php ?>

<div class="card shadow-sm">
    <div class="card-header">
        <i class="fa-solid fa-calendar-days"></i> All Bookings
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
              <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Lot</th>
                    <th>Slot</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($bookings)): ?>
                    <tr><td colspan="8" class="text-center text-muted fst-italic">No bookings found.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $row): ?>
                    <tr>
                      <td><?= e($row['id']) ?></td>
                      <td><?= e($row['user_name']) ?> <small class="text-muted d-block"><?= e($row['user_email']) ?></small></td>
                      <td><?= e($row['lot_name']) ?></td>
                      <td><?= e($row['slot_number']) ?></td>
                       <td><?= e(date('M d, Y H:i', strtotime($row['start_time']))) ?></td>
                       <td><?= e(date('M d, Y H:i', strtotime($row['end_time']))) ?></td>
                      <td>
                          <?php
                            $status_class = 'bg-secondary'; // Default
                            if ($row['status'] === 'booked') $status_class = 'bg-primary';
                            elseif ($row['status'] === 'completed') $status_class = 'bg-success';
                            elseif ($row['status'] === 'cancelled') $status_class = 'bg-danger';
                          ?>
                          <span class="badge <?= $status_class ?>"><?= e(ucfirst($row['status'])) ?></span>
                      </td>
                      <td>
                        <form method="POST" action="bookings.php" style="display:inline-block" class="me-1">
                          <input type="hidden" name="status_id" value="<?= e($row['id']) ?>">
                          <select aria-label="Change Status" id="status_select_<?= e($row['id']) ?>" name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="if(confirm('Change status to ' + this.value + '?')) { this.form.submit(); } else { this.value = '<?= e($row['status']) ?>'; }">
                            <option value="booked" <?= $row['status'] === 'booked' ? 'selected' : '' ?>>Booked</option>
                            <option value="completed" <?= $row['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $row['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                          </select>
                        </form>
                        <form method="POST" action="bookings.php" style="display:inline-block" onsubmit="return confirm('Are you sure you want to permanently delete booking ID <?= e($row['id']) ?>?');">
                          <input type="hidden" name="delete_id" value="<?= e($row['id']) ?>">
                          <button type="submit" name="delete_booking_button" class="btn btn-sm btn-danger" title="Delete Booking">
                             <i class="fa-solid fa-trash-alt"></i> Delete
                          </button>
                        </form>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
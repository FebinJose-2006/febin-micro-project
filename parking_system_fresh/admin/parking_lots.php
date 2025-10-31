<?php require_once __DIR__ . '/../includes/auth_check_admin.php';
// Database ($pdo) is included via init.php in auth_check

$error = '';
$success = '';

// Handle POST actions: add / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Lot
    if (isset($_POST['action']) && $_POST['action'] === 'add_lot') {
        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        if ($name && $location) {
             try {
                // Check if lot name/location combo already exists (optional)
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM parking_lots WHERE name = ? AND location = ?");
                $checkStmt->execute([$name, $location]);
                if ($checkStmt->fetchColumn() > 0) {
                     $error = "A parking lot with the same name and location already exists.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO parking_lots (name, location) VALUES (?, ?)");
                    $stmt->execute([$name, $location]);
                    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Parking lot "' . e($name) . '" added successfully.'];
                    redirect('/admin/parking_lots.php');
                }
             } catch (PDOException $e) {
                 error_log("Add Lot Error: " . $e->getMessage());
                 $error = "Database error adding lot.";
             }
        } else {
             $error = "Name and location are required.";
        }
    }
    // Delete Lot
    elseif (isset($_POST['delete_id'])) {
        $id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                 $pdo->beginTransaction();
                 // ON DELETE CASCADE in schema handles related slots and bookings
                 $deleteStmt = $pdo->prepare("DELETE FROM parking_lots WHERE id = ?");
                 $deleteStmt->execute([$id]);
                 $rowCount = $deleteStmt->rowCount();
                 $pdo->commit();

                 if ($rowCount > 0) {
                     $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Parking lot deleted successfully.'];
                 } else {
                      $_SESSION['flash_message'] = ['type' => 'warning', 'text' => 'Parking lot not found or already deleted.'];
                 }
                 redirect('/admin/parking_lots.php');

            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Delete Lot Error: " . $e->getMessage());
                 $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Error deleting lot. Check for dependencies if cascade is not set.'];
                 redirect('/admin/parking_lots.php'); // Redirect back even on error
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Invalid ID for deletion.'];
            redirect('/admin/parking_lots.php');
        }
    }
}

// Fetch existing lots for display
$lots = [];
try {
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.location, COUNT(s.id) AS slot_count
        FROM parking_lots p
        LEFT JOIN slots s ON p.id = s.lot_id
        GROUP BY p.id
        ORDER BY p.name ASC
    ");
    $lots = $stmt->fetchAll();
} catch (PDOException $e) {
     error_log("Fetch Lots Error: " . $e->getMessage());
     $error = "Could not fetch parking lots."; // Display error on page
}
?>
<?php require_once __DIR__ . '/../includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Manage Parking Lots</h2>
    <a href="index.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php // Flash messages are handled in header.php ?>

<div class="card mb-4 shadow-sm">
  <div class="card-header">
    <i class="fa-solid fa-plus"></i> Add New Parking Lot
  </div>
  <div class="card-body">
    <form method="POST" action="parking_lots.php" class="row g-3 align-items-end">
      <div class="col-md-5">
          <label for="lotName" class="form-label">Lot Name</label>
          <input type="text" id="lotName" name="name" class="form-control" placeholder="e.g., Main Street Lot" required>
      </div>
      <div class="col-md-5">
          <label for="lotLocation" class="form-label">Location / Address</label>
          <input type="text" id="lotLocation" name="location" class="form-control" placeholder="e.g., 123 Main St" required>
      </div>
      <div class="col-md-2">
          <button type="submit" name="action" value="add_lot" class="btn btn-primary w-100">Add Lot</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
 <div class="card-header">
    <i class="fa-solid fa-warehouse"></i> Existing Parking Lots
 </div>
 <div class="card-body">
    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered">
          <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Location</th>
                <th>Total Slots</th>
                <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($lots)): ?>
            <tr><td colspan="5" class="text-center text-muted">No parking lots found. Add one above.</td></tr>
          <?php else: ?>
              <?php foreach ($lots as $lot): ?>
                <tr>
                  <td><?= e($lot['id']) ?></td>
                  <td><?= e($lot['name']) ?></td>
                  <td><?= e($lot['location']) ?></td>
                  <td><?= e($lot['slot_count']) ?></td>
                  <td>
                    <a class="btn btn-info btn-sm" href="slots.php?lot_id=<?= e($lot['id']) ?>">
                       <i class="fa-solid fa-list"></i> View/Edit Slots (<?= e($lot['slot_count']) ?>)
                    </a>
                    <form method="POST" action="parking_lots.php" style="display:inline-block" onsubmit="return confirm('Are you sure you want to delete this lot? All associated slots and bookings will be permanently removed.');">
                      <input type="hidden" name="delete_id" value="<?= e($lot['id']) ?>">
                      <button type="submit" name="delete_button" class="btn btn-danger btn-sm">
                         <i class="fa-solid fa-trash-alt"></i> Delete Lot
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
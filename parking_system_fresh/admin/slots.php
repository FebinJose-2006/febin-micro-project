<?php require_once __DIR__ . '/../includes/auth_check_admin.php';
// Database ($pdo) is included via init.php in auth_check

$filter_lot_id = filter_input(INPUT_GET, 'lot_id', FILTER_VALIDATE_INT) ?: 0; // Get lot_id from URL if present

$error = ''; // Initialize error message

// POST handlers: add slot, change status, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- FIX: Add /admin/ to the redirect URL ---
    $redirect_url = "/admin/slots.php" . ($filter_lot_id ? "?lot_id={$filter_lot_id}" : ""); // Base redirect URL
    // --- END FIX ---

    // Add Slot
    if (isset($_POST['action']) && $_POST['action'] === 'add_slot') {
        $lot_id = filter_input(INPUT_POST, 'lot_id', FILTER_VALIDATE_INT);
        $slot_number = trim($_POST['slot_number'] ?? '');

        if ($lot_id && $slot_number) {
            try {
                // Check if slot number already exists for this lot
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM slots WHERE lot_id = ? AND slot_number = ?");
                $checkStmt->execute([$lot_id, $slot_number]);
                if ($checkStmt->fetchColumn() > 0) {
                    $error = "Slot number '" . e($slot_number) . "' already exists for this lot.";
                } else {
                    $pdo->prepare("INSERT INTO slots (lot_id, slot_number, status) VALUES (?, ?, 'available')")->execute([$lot_id, $slot_number]);
                    set_flash_message('success', 'Slot "' . e($slot_number) . '" added successfully.');
                    redirect($redirect_url); // Use redirect function
                }
            } catch (PDOException $e) {
                error_log("Add Slot Error: " . $e->getMessage());
                $error = "Database error adding slot.";
            }
        } else {
             $error = "Lot ID and Slot Number are required.";
        }
    }
    // Toggle Status
    elseif (isset($_POST['toggle_id'])) {
        $id = filter_input(INPUT_POST, 'toggle_id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                $cur = $pdo->prepare("SELECT status, lot_id FROM slots WHERE id = ?");
                $cur->execute([$id]);
                $r = $cur->fetch();
                if ($r) {
                    $new_status = ($r['status'] === 'available') ? 'booked' : 'available';
                    $pdo->prepare("UPDATE slots SET status = ? WHERE id = ?")->execute([$new_status, $id]);
                    set_flash_message('success', 'Slot status updated.');
                    redirect($redirect_url); // Use redirect function
                } else {
                     $error = "Slot not found for toggling.";
                }
            } catch (PDOException $e) {
                 error_log("Toggle Slot Status Error: " . $e->getMessage());
                 $error = "Database error updating slot status.";
            }
        } else {
            $error = "Invalid Slot ID for toggling.";
        }
    }
    // Delete Slot
    elseif (isset($_POST['delete_id'])) {
        $id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                 $pdo->beginTransaction();
                 // ON DELETE CASCADE handles related bookings in the schema
                 $deleteStmt = $pdo->prepare("DELETE FROM slots WHERE id = ?");
                 $deleteStmt->execute([$id]);
                 $rowCount = $deleteStmt->rowCount();
                 $pdo->commit();

                 if ($rowCount > 0) {
                     set_flash_message('success', 'Slot deleted successfully.');
                 } else {
                     set_flash_message('warning', 'Slot not found or already deleted.');
                 }
                 redirect($redirect_url); // Use redirect function

            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Delete Slot Error: " . $e->getMessage());
                set_flash_message('danger', 'Error deleting slot. Check dependencies if cascade failed.');
                redirect($redirect_url); // Redirect back even on error
            }
        } else {
             set_flash_message('danger', 'Invalid ID for deletion.');
             redirect($redirect_url);
        }
    }
}

// Fetch parking lots for dropdowns
$lots = [];
$current_lot_name = '';
try {
    $lots = $pdo->query("SELECT id, name, location FROM parking_lots ORDER BY name")->fetchAll();
    if ($filter_lot_id) {
        foreach ($lots as $l) {
            if ($l['id'] == $filter_lot_id) {
                $current_lot_name = " for " . e($l['name']);
                break;
            }
        }
    }
} catch (PDOException $e) {
    error_log("Fetch Lots (Admin Slots): " . $e->getMessage());
    $error = "Could not fetch parking lots for selection.";
}

// Fetch slots based on filter
$slots = [];
try {
    $sql = "SELECT s.id, s.slot_number, s.status, l.name AS lot_name, l.location
            FROM slots s JOIN parking_lots l ON s.lot_id = l.id";
    if ($filter_lot_id) {
        $sql .= " WHERE l.id = :lot_id";
    }
    $sql .= " ORDER BY l.name ASC, s.slot_number ASC";
    $stmt = $pdo->prepare($sql);
    if ($filter_lot_id) {
        $stmt->bindParam(':lot_id', $filter_lot_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    $slots = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch Slots Error: " . $e->getMessage());
    $error = "Could not fetch slots."; // Display error on page
}

?>
<?php require_once __DIR__ . '/../includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Manage Slots<?= $current_lot_name ?></h2>
    <a href="index.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php // Flash messages are handled in header.php ?>

<div class="card mb-4 shadow-sm">
    <div class="card-header">
        <i class="fa-solid fa-filter me-1"></i>Filter / <i class="fa-solid fa-plus me-1"></i>Add Slots
    </div>
    <div class="card-body">
        <form method="GET" action="slots.php" class="row g-2 align-items-center mb-3 border-bottom pb-3">
            <div class="col-auto"><label for="filterLotSelect" class="form-label fw-bold">Filter by Lot:</label></div>
            <div class="col-md-5">
              <select id="filterLotSelect" name="lot_id" class="form-select" onchange="this.form.submit()">
                <option value="">Show All Lots</option>
                <?php
                foreach($lots as $l) {
                    $sel = ($filter_lot_id && $filter_lot_id == $l['id']) ? 'selected' : '';
                    echo "<option value=\"{$l['id']}\" $sel>".e($l['name'])." (".e($l['location']).")</option>";
                }
                ?>
              </select>
            </div>
            <?php if ($filter_lot_id): ?>
             <div class="col-auto">
                 <a href="slots.php" class="btn btn-outline-secondary btn-sm">Clear Filter</a>
             </div>
            <?php endif; ?>
        </form>

        <form method="POST" action="slots.php<?= $filter_lot_id ? '?lot_id='.$filter_lot_id : '' ?>" class="row g-2 align-items-end">
          <input type="hidden" name="action" value="add_slot">
          <div class="col-md-4">
            <label for="addLotSelect" class="form-label fw-bold">Add to Lot:</label>
            <select id="addLotSelect" name="lot_id" class="form-select" required>
              <option value="" disabled <?= !$filter_lot_id ? 'selected' : '' ?>>Select Lot</option>
              <?php foreach($lots as $l) {
                    $sel = ($filter_lot_id && $filter_lot_id == $l['id']) ? 'selected' : ''; // Pre-select filtered lot
                    echo "<option value=\"{$l['id']}\" $sel>".e($l['name'])."</option>";
                } ?>
            </select>
          </div>
          <div class="col-md-4">
              <label for="addSlotNumber" class="form-label fw-bold">Slot Number:</label>
              <input type="text" id="addSlotNumber" name="slot_number" class="form-control" placeholder="e.g., A1, B12" required>
          </div>
          <div class="col-md-4">
              <button type="submit" name="add_slot_button" class="btn btn-primary w-100">
                 <i class="fa-solid fa-plus"></i> Add Slot
              </button>
          </div>
        </form>
    </div>
</div>


<div class="card shadow-sm">
    <div class="card-header">
        <i class="fa-solid fa-list-ol"></i> Existing Slots<?= $current_lot_name ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
              <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Lot</th>
                    <th>Slot Number</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($slots)): ?>
                    <tr><td colspan="5" class="text-center text-muted fst-italic">No slots found<?= $current_lot_name ? '' : ' matching this filter' ?>. Add one above.</td></tr>
                <?php else: ?>
                    <?php foreach ($slots as $slot): ?>
                    <tr>
                      <td><?= e($slot['id']) ?></td>
                      <td><?= e($slot['lot_name']) ?> <small class="text-muted">(<?= e($slot['location']) ?>)</small></td>
                      <td><?= e($slot['slot_number']) ?></td>
                      <td>
                          <?php
                            $status_class = $slot['status'] === 'available' ? 'bg-success' : 'bg-danger';
                          ?>
                          <span class="badge <?= $status_class ?>"><?= e(ucfirst($slot['status'])) ?></span>
                      </td>
                      <td>
                        <form method="POST" action="slots.php<?= $filter_lot_id ? '?lot_id='.$filter_lot_id : '' ?>" style="display:inline-block" class="me-1">
                          <input type="hidden" name="toggle_id" value="<?= e($slot['id']) ?>">
                          <button type="submit" name="toggle_status_button" class="btn btn-sm btn-warning" title="Toggle Status">
                              Mark <?= $slot['status'] === 'available' ? 'Booked' : 'Available' ?>
                          </button>
                        </form>
                        <form method="POST" action="slots.php<?= $filter_lot_id ? '?lot_id='.$filter_lot_id : '' ?>" style="display:inline-block" onsubmit="return confirm('Are you sure you want to delete Slot <?= e($slot['slot_number']) ?>? This cannot be undone.');">
                          <input type="hidden" name="delete_id" value="<?= e($slot['id']) ?>">
                          <button type="submit" name="delete_slot_button" class="btn btn-sm btn-danger" title="Delete Slot">
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
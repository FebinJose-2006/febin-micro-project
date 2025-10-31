<?php require_once __DIR__ . '/../includes/auth_check_admin.php';

$error = ''; // Initialize error message

// Handle POST actions: Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);

    if ($id) {
        // Prevent deleting own account
        if ($id === $_SESSION['user_id']) {
             $error = "You cannot delete your own administrator account.";
        } else {
             try {
                 // Check if it's the last admin (optional safety)
                 $adminCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                 $adminCheckStmt->execute();
                 $adminCount = $adminCheckStmt->fetchColumn();

                 $userToDeleteStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                 $userToDeleteStmt->execute([$id]);
                 $userRole = $userToDeleteStmt->fetchColumn();

                 if ($userRole === 'admin' && $adminCount <= 1) {
                     $error = "Cannot delete the only remaining administrator account.";
                 } else {
                     $pdo->beginTransaction();
                     // ON DELETE CASCADE handles related bookings in schema
                     $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                     $deleteStmt->execute([$id]);
                     $rowCount = $deleteStmt->rowCount();
                     $pdo->commit();

                     if ($rowCount > 0) {
                         set_flash_message('success', 'User deleted successfully.');
                         redirect('/admin/users.php');
                     } else {
                         set_flash_message('warning', 'User not found or already deleted.');
                         redirect('/admin/users.php');
                     }
                 }
             } catch (PDOException $e) {
                 if ($pdo->inTransaction()) $pdo->rollBack();
                 error_log("Delete User Error: " . $e->getMessage());
                 $error = "Error deleting user. Please try again.";
             }
        }
    } else {
         $error = "Invalid User ID for deletion.";
    }
}

// Fetch users for display
$users = [];
try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch Users Error: " . $e->getMessage());
    $error = "Could not fetch users.";
}

?>
<?php require_once __DIR__ . '/../includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Manage Users</h2>
    <a href="index.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php // Flash messages handled in header ?>

<div class="card shadow-sm">
    <div class="card-header">
        <i class="fa-solid fa-users"></i> User List
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
              <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="text-center text-muted fst-italic">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                      <td><?= e($user['id']) ?></td>
                      <td><?= e($user['name']) ?></td>
                      <td><?= e($user['email']) ?></td>
                      <td><span class="badge <?= $user['role'] === 'admin' ? 'bg-warning text-dark' : 'bg-info text-dark' ?>"><?= e(ucfirst($user['role'])) ?></span></td>
                      <td><?= e(date('M d, Y H:i', strtotime($user['created_at']))) ?></td>
                      <td>
                        <?php if ($user['id'] !== $_SESSION['user_id']): // Prevent deleting self ?>
                        <form method="POST" action="users.php" onsubmit="return confirm('Are you sure you want to permanently delete user <?= e($user['name']) ?>? This cannot be undone.');" style="display:inline-block">
                          <input type="hidden" name="delete_id" value="<?= e($user['id']) ?>">
                          <button type="submit" name="delete_user_button" class="btn btn-sm btn-danger" title="Delete User">
                             <i class="fa-solid fa-user-times"></i> Delete
                          </button>
                        </form>
                        <?php else: ?>
                         <button class="btn btn-sm btn-secondary" disabled title="Cannot delete your own account">Cannot Delete Self</button>
                        <?php endif; ?>
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
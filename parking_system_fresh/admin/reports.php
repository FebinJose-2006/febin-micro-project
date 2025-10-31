<?php require_once __DIR__ . '/../includes/auth_check_admin.php';
// Database ($pdo) included via init.php

$error = '';
$daily_bookings = [];
$top_lots = [];
$availability = [];

try {
    // --- Daily Bookings (Last 7 Days) ---
    $stmt_daily = $pdo->query("
      SELECT DATE(created_at) AS day, COUNT(*) AS total
      FROM bookings
      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
      GROUP BY DATE(created_at)
      ORDER BY day ASC
    ");
    $daily_bookings = $stmt_daily->fetchAll();

    // --- Top Parking Lots (by Total Bookings) ---
    $stmt_top_lots = $pdo->query("
      SELECT l.id, l.name, l.location, COUNT(b.id) as booking_count
      FROM parking_lots l
      LEFT JOIN slots s ON l.id = s.lot_id
      LEFT JOIN bookings b ON s.id = b.slot_id
      GROUP BY l.id, l.name, l.location /* Added group by all non-aggregated columns */
      ORDER BY booking_count DESC
      LIMIT 10
    ");
    $top_lots = $stmt_top_lots->fetchAll();

    // --- Slot Availability Overview ---
    $stmt_availability = $pdo->query("
        SELECT
            l.name AS lot_name,
            l.location,
            COUNT(s.id) AS total_slots,
            SUM(CASE WHEN s.status = 'available' THEN 1 ELSE 0 END) AS available_slots
        FROM parking_lots l
        LEFT JOIN slots s ON l.id = s.lot_id
        GROUP BY l.id, l.name, l.location /* Added group by all non-aggregated columns */
        ORDER BY l.name ASC
    ");
    $availability = $stmt_availability->fetchAll();

} catch (PDOException $e) {
    error_log("Admin Reports Error: " . $e->getMessage());
    $error = "Could not load report data.";
}
?>
<?php require_once __DIR__ . '/../includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Reports</h2>
    <a href="index.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100 shadow-sm">
          <div class="card-header"><i class="fa-solid fa-chart-bar me-1"></i>Bookings - Last 7 Days</div>
          <div class="card-body">
            <?php if (empty($daily_bookings)): ?>
                <p class="text-muted text-center fst-italic">No bookings recorded in the last 7 days.</p>
            <?php else: ?>
                <table class="table table-sm table-striped">
                  <thead class="table-light"><tr><th>Date</th><th>Total Bookings</th></tr></thead>
                  <tbody>
                  <?php
                  $total_week = 0;
                  // Ensure we show all 7 days, even if 0 bookings
                  $dates = [];
                  for ($i = 6; $i >= 0; $i--) {
                      $dates[date('Y-m-d', strtotime("-$i days"))] = 0;
                  }
                  foreach ($daily_bookings as $d) {
                      if (isset($dates[$d['day']])) {
                          $dates[$d['day']] = $d['total'];
                          $total_week += $d['total'];
                      }
                  }
                  foreach ($dates as $day => $total):
                  ?>
                    <tr><td><?= e(date('M d, Y', strtotime($day))) ?></td><td><?= e($total) ?></td></tr>
                  <?php endforeach; ?>
                   <tr class="table-group-divider fw-bold"><td >Total (Last 7 Days)</td><td><?= e($total_week) ?></td></tr>
                  </tbody>
                </table>
             <?php endif; ?>
          </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100 shadow-sm">
          <div class="card-header"><i class="fa-solid fa-trophy me-1"></i>Top Parking Lots (by Total Bookings)</div>
          <div class="card-body">
             <?php if (empty($top_lots) || $top_lots[0]['booking_count'] == 0): // Check if any bookings exist ?>
                <p class="text-muted text-center fst-italic">No bookings recorded yet to rank lots.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                  <?php foreach($top_lots as $t): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <div>
                        <?= e($t['name']) ?>
                        <small class="text-muted d-block"><?= e($t['location']) ?></small>
                      </div>
                      <span class="badge bg-primary rounded-pill"><?= e($t['booking_count']) ?> bookings</span>
                    </li>
                  <?php endforeach; ?>
                </ul>
             <?php endif; ?>
          </div>
        </div>
    </div>

     <div class="col-12 mb-4">
        <div class="card shadow-sm">
          <div class="card-header"><i class="fa-solid fa-square-parking me-1"></i>Slot Availability Overview</div>
          <div class="card-body">
            <?php if (empty($availability)): ?>
                <p class="text-muted text-center fst-italic">No parking lots or slots found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered">
                      <thead class="table-light">
                        <tr>
                          <th>Lot Name</th>
                          <th>Location</th>
                          <th>Total Slots</th>
                          <th>Available</th>
                          <th>Occupied</th>
                          <th>Occupancy Rate</th>
                        </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($availability as $a):
                        $total = intval($a['total_slots']);
                        $available = intval($a['available_slots']);
                        $occupied = $total - $available;
                        $occupancy_percent = $total > 0 ? round(($occupied / $total) * 100) : 0;
                        $progress_class = $occupancy_percent >= 90 ? 'bg-danger' : ($occupancy_percent >= 60 ? 'bg-warning' : 'bg-success');
                      ?>
                        <tr>
                          <td><?= e($a['lot_name']) ?></td>
                          <td><?= e($a['location']) ?></td>
                          <td><?= e($total) ?></td>
                          <td><?= e($available) ?></td>
                          <td><?= e($occupied) ?></td>
                           <td>
                               <div class="progress" style="height: 20px;" title="<?= e($occupied) ?> occupied / <?= e($total) ?> total">
                                 <div class="progress-bar <?= $progress_class ?>" role="progressbar" style="width: <?= $occupancy_percent ?>%;" aria-valuenow="<?= $occupancy_percent ?>" aria-valuemin="0" aria-valuemax="100">
                                     <?= $occupancy_percent ?>%
                                 </div>
                               </div>
                           </td>
                        </tr>
                      <?php endforeach; ?>
                      </tbody>
                    </table>
                </div>
             <?php endif; ?>
          </div>
        </div>
    </div>
</div> 
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
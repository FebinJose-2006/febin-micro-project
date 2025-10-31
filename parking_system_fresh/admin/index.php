<?php require_once __DIR__ . '/../includes/auth_check_admin.php'; ?>
<?php require_once __DIR__ . '/../includes/admin_header.php'; ?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Admin Dashboard - <?= e(SITE_NAME) ?></h2>
    <p class="text-center text-muted">Manage parking lots, slots, bookings, and users.</p>
    <hr class="my-4">

    <div class="row">
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="fa-solid fa-warehouse fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Manage Lots</h5>
                    <p class="card-text">Add, edit, or remove parking lot locations.</p>
                    <a href="parking_lots.php" class="btn btn-primary">Go to Lots</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100 shadow-sm">
                 <div class="card-body text-center">
                    <i class="fa-solid fa-square-parking fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Manage Slots</h5>
                    <p class="card-text">Add, edit, or delete individual parking slots.</p>
                    <a href="slots.php" class="btn btn-primary">Go to Slots</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-3">
             <div class="card h-100 shadow-sm">
                 <div class="card-body text-center">
                    <i class="fa-solid fa-calendar-days fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">View Bookings</h5>
                    <p class="card-text">Monitor current and past booking records.</p>
                    <a href="bookings.php" class="btn btn-primary">Go to Bookings</a>
                </div>
            </div>
        </div>
         <div class="col-md-6 col-lg-4 mb-3">
             <div class="card h-100 shadow-sm">
                 <div class="card-body text-center">
                     <i class="fa-solid fa-users fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Manage Users</h5>
                    <p class="card-text">View and manage registered user accounts.</p>
                    <a href="users.php" class="btn btn-primary">Go to Users</a>
                </div>
            </div>
        </div>
         <div class="col-md-6 col-lg-4 mb-3">
             <div class="card h-100 shadow-sm">
                 <div class="card-body text-center">
                     <i class="fa-solid fa-chart-line fa-3x text-secondary mb-3"></i>
                    <h5 class="card-title">View Reports</h5>
                    <p class="card-text">See usage statistics and booking trends.</p>
                    <a href="reports.php" class="btn btn-secondary">Go to Reports</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="text-center p-5 mb-4 bg-light rounded-3 shadow-sm">
    <h1 class="display-5 fw-bold"><?= e(SITE_NAME) ?></h1>
    <p class="fs-4 text-muted">Your smart solution for effortless parking.</p>
    <hr class="my-4">
    <p>Quickly find and book available parking slots near you.</p>
    <a href="search.php" class="btn btn-primary btn-lg px-4 gap-3">
        <i class="fa-solid fa-magnifying-glass me-1"></i> Find Parking Now
    </a>
    <?php if (!isset($_SESSION['user_id'])): ?>
    <a href="register.php" class="btn btn-outline-secondary btn-lg px-4">
        <i class="fa-solid fa-user-plus me-1"></i> Register
    </a>
    <?php endif; ?>
</div>

<div class="row text-center mt-5">
    <div class="col-md-4 mb-3">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <i class="fa-solid fa-map-location-dot fa-3x text-primary mb-3"></i>
                <h4 class="card-title">Search Locations</h4>
                <p class="card-text text-muted">Easily find parking lots in your desired area.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
         <div class="card h-100 shadow-sm">
            <div class="card-body">
                <i class="fa-solid fa-car-side fa-3x text-primary mb-3"></i>
                <h4 class="card-title">View Availability</h4>
                <p class="card-text text-muted">See real-time available slots before you arrive.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
         <div class="card h-100 shadow-sm">
            <div class="card-body">
                <i class="fa-solid fa-calendar-check fa-3x text-primary mb-3"></i>
                <h4 class="card-title">Instant Booking</h4>
                <p class="card-text text-muted">Secure your spot with just one click.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
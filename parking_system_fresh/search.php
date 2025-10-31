<?php require_once __DIR__ . '/includes/auth_check_user.php'; // User must be logged in ?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<h2 class="fw-bold mb-4 text-center">Search Parking Slots</h2>

<div class="row justify-content-center mb-4">
    <div class="col-md-8 col-lg-6">
        <div class="input-group shadow-sm">
            <label for="lotSelect" class="input-group-text"><i class="fa-solid fa-warehouse me-1"></i> Lot:</label>
            <select id="lotSelect" class="form-select">
                <option value="" disabled selected>Loading lots...</option>
                </select>
            <button id="loadSlots" class="btn btn-primary" type="button">
                <i class="fa-solid fa-arrows-rotate"></i> Load Slots
            </button>
        </div>
    </div>
</div>

<div id="slotsContainer" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
    <div id="slotsPlaceholder" class="col-12 text-center text-muted fst-italic py-5">
        Please select a lot and click "Load Slots".
    </div>
</div>

<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-3 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="bookingModalLabel">Confirm Booking</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
         <form id="bookingForm">
          <input type="hidden" id="slotId" name="slot_id">
          <p id="modalSlotInfo" class="text-center fs-5 mb-3">Book Slot <span class="fw-bold">...</span>?</p>
          <p class="text-center text-muted small">
              This will book the slot immediately using the current time. The default booking duration is 1 hour.
          </p>
          <div id="bookingError" class="alert alert-danger d-none mt-3" role="alert"></div>
          <button type="submit" id="confirmBookingBtn" class="btn btn-primary w-100 mt-3">
             <i class="fa-solid fa-check"></i> Confirm Booking
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
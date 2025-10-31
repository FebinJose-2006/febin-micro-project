document.addEventListener('DOMContentLoaded', () => {
    console.log('Main JS Loaded - Minimal Version');

    // --- Modal Logic ---
    const bookingModalElement = document.getElementById('bookingModal');
    const bookingModal = bookingModalElement ? new bootstrap.Modal(bookingModalElement) : null;
    const modalSlotInfo = document.getElementById('modalSlotInfo'); // Element to show slot info
    const confirmBookingBtn = document.getElementById('confirmBookingBtn');
    const bookingErrorDiv = document.getElementById('bookingError');

    if (bookingModalElement) {
        bookingModalElement.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            if (!button) return;

            const slotId = button.getAttribute('data-id');
            const slotCard = button.closest('.slot-card'); // Go up to the card
            const slotTitleElement = slotCard ? slotCard.querySelector('.card-title') : null;
            const slotTitle = slotTitleElement ? slotTitleElement.textContent.trim() : 'Selected Slot';

            // Set hidden input value
            const slotIdInput = document.getElementById('slotId');
            if (slotIdInput) slotIdInput.value = slotId || '';

            // Set modal title (using span inside paragraph for slot name)
            if (modalSlotInfo) {
                const slotNameSpan = modalSlotInfo.querySelector('span');
                if (slotNameSpan) slotNameSpan.textContent = slotTitle;
            }

             // Reset button state and error message
            if (confirmBookingBtn) {
                 confirmBookingBtn.disabled = false;
                 confirmBookingBtn.innerHTML = '<i class="fa-solid fa-check"></i> Confirm Booking';
            }
             if(bookingErrorDiv) {
                 bookingErrorDiv.classList.add('d-none');
                 bookingErrorDiv.textContent = '';
             }

            console.log(`Modal opening for slot ID: ${slotId}`);
        });
    }

    // --- Card Hover Logic ---
    document.querySelectorAll('.card.slot-card').forEach(card => {
        card.addEventListener('mouseenter', () => card.classList.add('shadow-lg'));
        card.addEventListener('mouseleave', () => card.classList.remove('shadow-lg'));
    });

    // --- API: Load Parking Lots ---
    const lotSelect = document.getElementById('lotSelect');
    if (lotSelect) {
        fetch('api/get_lots.php') // Relative path from public folder
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                return response.json();
             })
            .then(lots => {
                if (!Array.isArray(lots)) throw new Error('Invalid data format received for lots.');

                lotSelect.innerHTML = ''; // Clear "Loading..."
                const placeholderOpt = document.createElement('option');
                placeholderOpt.value = "";
                placeholderOpt.textContent = "Select a Parking Lot";
                placeholderOpt.disabled = true;
                placeholderOpt.selected = true;
                lotSelect.appendChild(placeholderOpt);

                lots.forEach(lot => {
                    const opt = document.createElement('option');
                    opt.value = lot.id;
                    opt.textContent = `${lot.name} - ${lot.location}`;
                    lotSelect.appendChild(opt);
                });
            })
            .catch(err => {
                console.error('Error fetching parking lots:', err);
                lotSelect.innerHTML = '<option value="" disabled selected>Error loading lots</option>';
            });
    }

    // --- API: Load Slots ---
    const loadSlotsButton = document.getElementById('loadSlots');
    const slotsContainer = document.getElementById('slotsContainer');
    const slotsPlaceholder = document.getElementById('slotsPlaceholder');

    if (loadSlotsButton && lotSelect && slotsContainer && slotsPlaceholder) {
        loadSlotsButton.addEventListener('click', () => {
            const lotId = lotSelect.value;
            if (!lotId) {
                alert('Please select a parking lot first.');
                return;
            }

            slotsPlaceholder.textContent = 'Loading slots...'; // Update placeholder text
            slotsContainer.innerHTML = ''; // Clear previous slots

            fetch(`api/get_slots.php?lot_id=${encodeURIComponent(lotId)}`)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    slotsPlaceholder.classList.add('d-none'); // Hide placeholder
                    if (data.error) throw new Error(data.error);

                    const slots = data.slots;
                    if (!Array.isArray(slots) || slots.length === 0) {
                        slotsContainer.innerHTML = '<p class="text-center text-muted col-12">No slots found or available for this location.</p>';
                        return;
                    }

                    slots.forEach(slot => {
                        const col = document.createElement('div');
                        // Use Bootstrap column classes directly here
                        col.className = 'col'; // Let the parent row handle sizing

                        const card = document.createElement('div');
                        card.className = `card slot-card h-100 ${slot.status}`; // Use h-100 for equal height cards in row

                        card.innerHTML = `
                            <img src="assets/img/slot_placeholder.png" class="card-img-top" alt="Parking Slot Image">
                            <div class="card-body d-flex flex-column text-center">
                                <h5 class="card-title">Slot ${e(slot.slot_number)}</h5>
                                <p class="card-text text-muted mb-2">${slot.status.toUpperCase()}</p>
                                <div class="mt-auto"> ${slot.status === 'available'
                                    ? `<button class="btn btn-success bookBtn w-100" data-id="${e(slot.id)}" data-bs-toggle="modal" data-bs-target="#bookingModal">Book Now</button>`
                                    : `<button class="btn btn-secondary w-100" disabled>Booked</button>`}
                                </div>
                            </div>`;
                        col.appendChild(card);
                        slotsContainer.appendChild(col);
                    });
                })
                .catch(err => {
                    console.error('Error fetching slots:', err);
                    slotsPlaceholder.classList.add('d-none'); // Hide placeholder
                    slotsContainer.innerHTML = `<p class="text-center text-danger col-12">Error loading slots: ${e(err.message)}</p>`;
                });
        });
    }

    // --- API: Handle Booking Form (Instant Booking Version) ---
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (!confirmBookingBtn) return; // Should exist, but safety check

            confirmBookingBtn.disabled = true;
            confirmBookingBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Booking...';
             if(bookingErrorDiv) bookingErrorDiv.classList.add('d-none'); // Hide previous errors

            const slotId = document.getElementById('slotId').value;
            const formData = new FormData();
            formData.append('slot_id', slotId);

            fetch('api/book_slot.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json().then(data => ({ ok: response.ok, status: response.status, data })))
            .then(result => {
                if (result.ok && result.data.success) {
                    // Success: show message, close modal, reload slots
                    alert(result.data.message || 'Booking successful!');
                    if (bookingModal) bookingModal.hide();
                    // Trigger click on loadSlots button to refresh the list
                    if (loadSlotsButton) loadSlotsButton.click();
                } else {
                    // Failure: Show error in modal
                     const errorMsg = result.data.error || `Booking failed (Status: ${result.status})`;
                     if(bookingErrorDiv) {
                         bookingErrorDiv.textContent = errorMsg;
                         bookingErrorDiv.classList.remove('d-none');
                     } else {
                         alert(errorMsg); // Fallback if error div is missing
                     }
                     console.error('Booking failed:', result.data);
                }
            })
            .catch(err => {
                console.error('Network or parsing error during booking:', err);
                 if(bookingErrorDiv) {
                     bookingErrorDiv.textContent = 'A network error occurred. Please try again.';
                     bookingErrorDiv.classList.remove('d-none');
                 } else {
                    alert('A network error occurred. Please check your connection and try again.');
                 }
            })
            .finally(() => {
                 // Re-enable button regardless of outcome
                 if (confirmBookingBtn) {
                     confirmBookingBtn.disabled = false;
                     confirmBookingBtn.innerHTML = '<i class="fa-solid fa-check"></i> Confirm Booking';
                 }
            });
        });
    }

    // Helper function to escape HTML (simple version)
    function e(str) {
        if (!str) return '';
        return str.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

}); // End DOMContentLoaded
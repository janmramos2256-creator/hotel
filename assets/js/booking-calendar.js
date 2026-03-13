// ============================================
// BOOKING PAGE CALENDAR FUNCTIONALITY
// ============================================

let bookingCurrentMonth = new Date().getMonth();
let bookingCurrentYear = new Date().getFullYear();
let bookingSelectedDates = {
    checkin: null,
    checkout: null
};
let bookingRoomAvailability = {};
let bookingSelectedRoomType = ''; // Filter for room type
let bookingSelectedGuestCapacity = ''; // Filter for guest capacity

// Toggle calendar modal
function toggleCalendarModal() {
    // Check if room type, capacity, and room number are selected
    const roomType = document.getElementById('selectedRoomType').value;
    const capacity = document.getElementById('selectedCapacity').value;
    const roomNumber = document.getElementById('selectedRoomNumber').value;
    
    if (!roomType || !capacity || !roomNumber) {
        alert('Please select Room Type, Guest Capacity, and Room Number before selecting dates.');
        return;
    }
    
    const modal = document.getElementById('bookingCalendarModal');
    if (modal) {
        modal.classList.add('active');
        
        // Get room type and capacity from dropdowns
        const roomTypeSelect = document.getElementById('roomTypeSelect');
        const capacitySelect = document.getElementById('capacitySelect');
        
        if (roomTypeSelect && roomTypeSelect.value) {
            bookingSelectedRoomType = roomTypeSelect.value;
        }
        
        if (capacitySelect && capacitySelect.value) {
            bookingSelectedGuestCapacity = capacitySelect.value;
        }
        
        fetchBookingAvailability();
        renderBookingCalendar();
    }
}

// Close calendar modal
function closeBookingCalendarModal() {
    const modal = document.getElementById('bookingCalendarModal');
    if (modal) {
        modal.classList.remove('active');
        resetBookingCalendarSelection();
    }
}

// Reset calendar selection
function resetBookingCalendarSelection() {
    bookingSelectedDates = { checkin: null, checkout: null };
    document.getElementById('bookingCalendarSummary').style.display = 'none';
    document.getElementById('confirmBookingDatesBtn').disabled = true;
}

// Fetch availability for booking calendar
function fetchBookingAvailability() {
    const roomTypeParam = bookingSelectedRoomType ? `&room_type=${bookingSelectedRoomType}` : '';
    const guestCapacityParam = bookingSelectedGuestCapacity ? `&guests=${bookingSelectedGuestCapacity}` : '';
    fetch(`api/get_availability.php?month=${bookingCurrentMonth + 1}&year=${bookingCurrentYear}${roomTypeParam}${guestCapacityParam}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bookingRoomAvailability = data.availability;
                renderBookingCalendar();
            }
        })
        .catch(error => {
            console.error('Error fetching availability:', error);
            bookingRoomAvailability = {};
        });
}

// Render booking calendar
function renderBookingCalendar() {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'];
    
    document.getElementById('bookingCalendarMonthYear').textContent = `${monthNames[bookingCurrentMonth]} ${bookingCurrentYear}`;
    
    const firstDay = new Date(bookingCurrentYear, bookingCurrentMonth, 1).getDay();
    const daysInMonth = new Date(bookingCurrentYear, bookingCurrentMonth + 1, 0).getDate();
    const daysInPrevMonth = new Date(bookingCurrentYear, bookingCurrentMonth, 0).getDate();
    
    const calendarDays = document.getElementById('bookingCalendarDays');
    calendarDays.innerHTML = '';
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Previous month days
    for (let i = firstDay - 1; i >= 0; i--) {
        const day = daysInPrevMonth - i;
        const dayEl = document.createElement('div');
        dayEl.className = 'calendar-day other-month';
        dayEl.innerHTML = `<span class="calendar-day-number">${day}</span>`;
        calendarDays.appendChild(dayEl);
    }
    
    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(bookingCurrentYear, bookingCurrentMonth, day);
        const dateStr = formatBookingDate(date);
        const isPast = date < today;
        const isAvailable = bookingRoomAvailability[dateStr] === true;
        const isBooked = bookingRoomAvailability[dateStr] === false;
        const isCheckin = bookingSelectedDates.checkin && formatBookingDate(bookingSelectedDates.checkin) === dateStr;
        const isCheckout = bookingSelectedDates.checkout && formatBookingDate(bookingSelectedDates.checkout) === dateStr;
        const isInRange = bookingSelectedDates.checkin && bookingSelectedDates.checkout && 
                         date > bookingSelectedDates.checkin && date < bookingSelectedDates.checkout;
        
        let classes = 'calendar-day';
        let status = '';
        
        if (isPast) {
            classes += ' disabled';
            status = 'Past';
        } else if (isBooked) {
            classes += ' booked';
            status = 'Booked';
        } else if (isCheckin) {
            classes += ' selected';
            status = 'Check-in';
        } else if (isCheckout) {
            classes += ' selected';
            status = 'Check-out';
        } else if (isInRange) {
            classes += ' in-range';
        } else if (isAvailable) {
            classes += ' available';
        }
        
        const dayEl = document.createElement('div');
        dayEl.className = classes;
        dayEl.dataset.date = dateStr;
        dayEl.style.cursor = (isPast || isBooked) ? 'not-allowed' : 'pointer';
        dayEl.innerHTML = `
            <span class="calendar-day-number">${day}</span>
            ${status ? `<span class="calendar-day-status">${status}</span>` : ''}
        `;
        
        // Add click event listener
        if (!isPast && !isBooked) {
            dayEl.addEventListener('click', function(e) {
                e.stopPropagation();
                selectBookingDate(dateStr);
            });
        }
        
        calendarDays.appendChild(dayEl);
    }
    
    // Next month days
    const totalCells = calendarDays.children.length;
    const remainingCells = 42 - totalCells;
    for (let day = 1; day <= remainingCells; day++) {
        const dayEl = document.createElement('div');
        dayEl.className = 'calendar-day other-month';
        dayEl.innerHTML = `<span class="calendar-day-number">${day}</span>`;
        calendarDays.appendChild(dayEl);
    }
}

// Select date in booking calendar
function selectBookingDate(dateStr) {
    // Check if room type, capacity, and room number are selected
    const roomType = document.getElementById('selectedRoomType').value;
    const capacity = document.getElementById('selectedCapacity').value;
    const roomNumber = document.getElementById('selectedRoomNumber').value;
    
    if (!roomType || !capacity || !roomNumber) {
        alert('Please select Room Type, Guest Capacity, and Room Number before selecting dates.');
        return;
    }
    
    const date = new Date(dateStr + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (date < today) {
        return;
    }
    
    if (bookingRoomAvailability[dateStr] === false) {
        return;
    }
    
    // If no check-in selected, select it
    if (!bookingSelectedDates.checkin) {
        bookingSelectedDates.checkin = date;
        // Auto-set checkout to next day
        const nextDay = new Date(date);
        nextDay.setDate(nextDay.getDate() + 1);
        bookingSelectedDates.checkout = nextDay;
        updateBookingCalendarSummary();
    } 
    // If check-in is selected but not checkout, or if clicking same date
    else if (!bookingSelectedDates.checkout || formatBookingDate(date) === formatBookingDate(bookingSelectedDates.checkin)) {
        // Auto-set checkout to next day
        const nextDay = new Date(date);
        nextDay.setDate(nextDay.getDate() + 1);
        bookingSelectedDates.checkout = nextDay;
        updateBookingCalendarSummary();
    }
    // If both are selected and clicking a new date
    else if (date > bookingSelectedDates.checkin) {
        // Set as checkout
        bookingSelectedDates.checkout = date;
        updateBookingCalendarSummary();
    } 
    // If clicking before check-in, reset
    else {
        bookingSelectedDates.checkin = date;
        const nextDay = new Date(date);
        nextDay.setDate(nextDay.getDate() + 1);
        bookingSelectedDates.checkout = nextDay;
        updateBookingCalendarSummary();
    }
    
    renderBookingCalendar();
}

// Update booking calendar summary
function updateBookingCalendarSummary() {
    if (!bookingSelectedDates.checkin || !bookingSelectedDates.checkout) {
        document.getElementById('bookingCalendarSummary').style.display = 'none';
        document.getElementById('confirmBookingDatesBtn').disabled = true;
        return;
    }
    
    const nights = Math.ceil((bookingSelectedDates.checkout - bookingSelectedDates.checkin) / (1000 * 60 * 60 * 24));
    
    // Get capacity label
    let capacityLabel = '-';
    if (bookingSelectedGuestCapacity) {
        if (bookingSelectedGuestCapacity === '2') capacityLabel = '2 Guests';
        else if (bookingSelectedGuestCapacity === '8') capacityLabel = '4-8 Guests';
        else if (bookingSelectedGuestCapacity === '20') capacityLabel = '10-20 Guests';
    }
    
    document.getElementById('bookingSummaryRoomType').textContent = bookingSelectedRoomType || '-';
    document.getElementById('bookingSummaryCapacity').textContent = capacityLabel;
    document.getElementById('bookingSummaryCheckin').textContent = formatDisplayBookingDate(bookingSelectedDates.checkin);
    document.getElementById('bookingSummaryCheckout').textContent = formatDisplayBookingDate(bookingSelectedDates.checkout);
    document.getElementById('bookingSummaryNights').textContent = `${nights} night${nights > 1 ? 's' : ''}`;
    
    document.getElementById('bookingCalendarSummary').style.display = 'block';
    document.getElementById('confirmBookingDatesBtn').disabled = false;
}

// Navigate to previous month
function bookingPreviousMonth() {
    bookingCurrentMonth--;
    if (bookingCurrentMonth < 0) {
        bookingCurrentMonth = 11;
        bookingCurrentYear--;
    }
    fetchBookingAvailability();
}

// Navigate to next month
function bookingNextMonth() {
    bookingCurrentMonth++;
    if (bookingCurrentMonth > 11) {
        bookingCurrentMonth = 0;
        bookingCurrentYear++;
    }
    fetchBookingAvailability();
}

// Confirm booking dates
function confirmBookingDates() {
    if (!bookingSelectedDates.checkin || !bookingSelectedDates.checkout) {
        alert('Please select check-in and check-out dates');
        return;
    }
    
    // Fill in the date fields
    const checkinStr = formatBookingDate(bookingSelectedDates.checkin);
    const checkoutStr = formatBookingDate(bookingSelectedDates.checkout);
    
    document.getElementById('checkin').value = checkinStr;
    document.getElementById('checkout').value = checkoutStr;
    
    // Update display
    const nights = Math.ceil((bookingSelectedDates.checkout - bookingSelectedDates.checkin) / (1000 * 60 * 60 * 24));
    document.getElementById('displayCheckin').textContent = formatDisplayBookingDate(bookingSelectedDates.checkin);
    document.getElementById('displayCheckout').textContent = formatDisplayBookingDate(bookingSelectedDates.checkout);
    document.getElementById('displayNights').textContent = `${nights} night${nights > 1 ? 's' : ''}`;
    document.getElementById('selectedDatesDisplay').style.display = 'block';
    
    // Trigger price calculation
    updatePriceCalculation();
    
    // Close modal
    closeBookingCalendarModal();
}

// Format date as YYYY-MM-DD
function formatBookingDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Format date for display
function formatDisplayBookingDate(date) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('bookingCalendarModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            // Only close if clicking on the overlay itself, not on modal content
            if (e.target === this) {
                closeBookingCalendarModal();
            }
        });
    }
});

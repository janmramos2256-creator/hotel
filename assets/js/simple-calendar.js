// Simple Calendar Modal - Fresh Implementation
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let selectedCheckin = null;
let selectedCheckout = null;

function openSimpleCalendar() {
    // Validation
    const roomType = document.getElementById('selectedRoomType').value;
    const capacity = document.getElementById('selectedCapacity').value;
    const roomNumber = document.getElementById('selectedRoomNumber').value;
    
    if (!roomType || !capacity || !roomNumber) {
        alert('Please select Room Type, Guest Capacity, and Room Number first');
        return;
    }
    
    const modal = document.getElementById('simpleCalendarModal');
    if (modal) {
        modal.style.display = 'flex';
        renderSimpleCalendar();
    }
}

function closeSimpleCalendar() {
    const modal = document.getElementById('simpleCalendarModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function renderSimpleCalendar() {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'];
    
    // Update header
    document.getElementById('simpleCalendarMonth').textContent = `${monthNames[currentMonth]} ${currentYear}`;
    
    // Get first day and number of days
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    
    // Clear calendar
    const calendarGrid = document.getElementById('simpleCalendarGrid');
    calendarGrid.innerHTML = '';
    
    // Add empty cells for days before month starts
    for (let i = 0; i < firstDay; i++) {
        const emptyCell = document.createElement('div');
        emptyCell.className = 'simple-calendar-day empty';
        calendarGrid.appendChild(emptyCell);
    }
    
    // Add days of month
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(currentYear, currentMonth, day);
        const dateStr = formatDate(date);
        
        const dayCell = document.createElement('div');
        dayCell.className = 'simple-calendar-day';
        dayCell.textContent = day;
        
        // Check if past
        if (date < today) {
            dayCell.classList.add('past');
            dayCell.style.cursor = 'not-allowed';
        } else {
            // Check if selected
            if (selectedCheckin && formatDate(selectedCheckin) === dateStr) {
                dayCell.classList.add('selected-checkin');
            } else if (selectedCheckout && formatDate(selectedCheckout) === dateStr) {
                dayCell.classList.add('selected-checkout');
            } else if (selectedCheckin && selectedCheckout && date > selectedCheckin && date < selectedCheckout) {
                dayCell.classList.add('in-range');
            } else {
                dayCell.classList.add('available');
            }
            
            dayCell.style.cursor = 'pointer';
            dayCell.addEventListener('click', function() {
                selectSimpleDate(date);
            });
        }
        
        calendarGrid.appendChild(dayCell);
    }
    
    // Update display
    updateSimpleCalendarDisplay();
}

function selectSimpleDate(date) {
    if (!selectedCheckin) {
        selectedCheckin = new Date(date);
        // Auto-set checkout to next day
        selectedCheckout = new Date(date);
        selectedCheckout.setDate(selectedCheckout.getDate() + 1);
    } else if (!selectedCheckout || date <= selectedCheckin) {
        selectedCheckin = new Date(date);
        selectedCheckout = new Date(date);
        selectedCheckout.setDate(selectedCheckout.getDate() + 1);
    } else {
        selectedCheckout = new Date(date);
    }
    
    renderSimpleCalendar();
}

function updateSimpleCalendarDisplay() {
    const display = document.getElementById('simpleCalendarDisplay');
    
    if (selectedCheckin && selectedCheckout) {
        const nights = Math.ceil((selectedCheckout - selectedCheckin) / (1000 * 60 * 60 * 24));
        display.innerHTML = `
            <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                <div style="color: #999; font-size: 0.9rem; margin-bottom: 0.5rem;">Selected Dates</div>
                <div style="color: #2C3E50; font-weight: 700; font-size: 1rem;">
                    ${formatDisplayDate(selectedCheckin)} to ${formatDisplayDate(selectedCheckout)}
                </div>
                <div style="color: #C9A961; font-size: 0.9rem; margin-top: 0.5rem;">
                    ${nights} night${nights > 1 ? 's' : ''}
                </div>
            </div>
        `;
    } else {
        display.innerHTML = '';
    }
}

function confirmSimpleCalendar() {
    if (!selectedCheckin || !selectedCheckout) {
        alert('Please select check-in and check-out dates');
        return;
    }
    
    // Set hidden fields
    document.getElementById('checkin').value = formatDate(selectedCheckin);
    document.getElementById('checkout').value = formatDate(selectedCheckout);
    
    // Update display
    const nights = Math.ceil((selectedCheckout - selectedCheckin) / (1000 * 60 * 60 * 24));
    document.getElementById('displayCheckin').textContent = formatDisplayDate(selectedCheckin);
    document.getElementById('displayCheckout').textContent = formatDisplayDate(selectedCheckout);
    document.getElementById('displayNights').textContent = `${nights} night${nights > 1 ? 's' : ''}`;
    document.getElementById('selectedDatesDisplay').style.display = 'block';
    
    // Trigger price calculation
    updatePriceCalculation();
    
    // Close modal
    closeSimpleCalendar();
    
    // Scroll back to booking form
    setTimeout(() => {
        document.querySelector('.booking-form-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
}

function previousMonth() {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    renderSimpleCalendar();
}

function nextMonth() {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    renderSimpleCalendar();
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatDisplayDate(date) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('simpleCalendarModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeSimpleCalendar();
            }
        });
    }
});

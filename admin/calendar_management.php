<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/notification_service.php';

// Require admin login
requireAdminLogin();

// Handle calendar actions (move, cancel, confirm)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $reservationId = intval($_POST['reservation_id'] ?? 0);
    
    try {
        $conn = getDBConnection();
        
        if ($action === 'cancel') {
            $reason = $_POST['cancel_reason'] ?? 'Cancelled by admin';
            $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
            $stmt->bind_param("i", $reservationId);
            $stmt->execute();
            $stmt->close();
            
            // Send notification
            NotificationService::notifyCancellation($reservationId, $reason);
            
        } elseif ($action === 'move') {
            $newCheckin = $_POST['new_checkin'] ?? '';
            $newCheckout = $_POST['new_checkout'] ?? '';
            $moveReason = $_POST['move_reason'] ?? 'Rescheduled by admin';
            
            if ($newCheckin && $newCheckout) {
                $stmt = $conn->prepare("UPDATE reservations SET checkin_date = ?, checkout_date = ?, status = 'pending' WHERE id = ?");
                $stmt->bind_param("ssi", $newCheckin, $newCheckout, $reservationId);
                $stmt->execute();
                $stmt->close();
                
                // Send notification
                NotificationService::notifyReschedule($reservationId, $newCheckin, $newCheckout, $moveReason);
            }
        }
        
        $conn->close();
        $_SESSION['success_message'] = 'Reservation updated successfully and user notified';
        
    } catch (Exception $e) {
        error_log("Calendar action error: " . $e->getMessage());
        $_SESSION['error_message'] = 'Error updating reservation';
    }
}

// Get all reservations for calendar
try {
    $conn = getDBConnection();
    
    $sql = "SELECT r.*, u.email, u.full_name as user_name 
            FROM reservations r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.status IN ('pending', 'confirmed')
            ORDER BY r.checkin_date ASC";
    
    $result = $conn->query($sql);
    $reservations = [];
    
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Calendar fetch error: " . $e->getMessage());
    $reservations = [];
}

$pageTitle = 'Calendar Management';
$currentPage = 'calendar';
?>
<?php include 'template_header.php'; ?>

<style>
.calendar-container {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
    margin-bottom: 2rem;
}

.calendar-main {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.calendar-sidebar {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    height: fit-content;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.calendar-header h2 {
    color: #2C3E50;
    font-size: 1.5rem;
}

.calendar-nav {
    display: flex;
    gap: 1rem;
}

.calendar-nav button {
    background: #C9A961;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.calendar-nav button:hover {
    background: #8B7355;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.5rem;
    margin-bottom: 2rem;
}

.calendar-day-header {
    text-align: center;
    font-weight: 700;
    color: #C9A961;
    padding: 1rem 0;
    border-bottom: 2px solid #C9A961;
}

.calendar-day {
    aspect-ratio: 1;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 0.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    background: white;
}

.calendar-day:hover {
    background: #f8f9fa;
    border-color: #C9A961;
}

.calendar-day.other-month {
    color: #ccc;
    background: #f8f9fa;
}

.calendar-day.today {
    background: #C9A961;
    color: white;
    font-weight: 700;
}

.calendar-day.has-booking {
    background: #e8f4f8;
    border-color: #2196F3;
}

.booking-count {
    font-size: 0.75rem;
    background: #2196F3;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: 2px;
    right: 2px;
}

.reservations-list {
    max-height: 600px;
    overflow-y: auto;
}

.reservation-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border-left: 4px solid #C9A961;
    cursor: pointer;
    transition: all 0.3s ease;
}

.reservation-item:hover {
    background: #e8f4f8;
    transform: translateX(5px);
}

.reservation-item.selected {
    background: #C9A961;
    color: white;
    border-left-color: #8B7355;
}

.reservation-guest {
    font-weight: 700;
    color: #2C3E50;
    margin-bottom: 0.25rem;
}

.reservation-item.selected .reservation-guest {
    color: white;
}

.reservation-dates {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.reservation-item.selected .reservation-dates {
    color: rgba(255, 255, 255, 0.9);
}

.reservation-room {
    font-size: 0.85rem;
    color: #999;
}

.reservation-item.selected .reservation-room {
    color: rgba(255, 255, 255, 0.8);
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.modal-header {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2C3E50;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #2C3E50;
    margin-bottom: 0.5rem;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 1rem;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #C9A961;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #C9A961;
    color: white;
}

.btn-primary:hover {
    background: #8B7355;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-secondary {
    background: #e0e0e0;
    color: #2C3E50;
}

.btn-secondary:hover {
    background: #d0d0d0;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 1024px) {
    .calendar-container {
        grid-template-columns: 1fr;
    }
    
    .calendar-sidebar {
        max-height: 400px;
    }
}
</style>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="fas fa-calendar-alt"></i> Calendar Management</h1>
    <p>Manage bookings, reschedule dates, and cancel reservations</p>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<div class="calendar-container">
    <!-- Calendar View -->
    <div class="calendar-main">
        <div class="calendar-header">
            <div>
                <h2><span id="monthYear"></span></h2>
                <div style="margin-top: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label for="roomTypeFilter" style="font-weight: 600; color: #2C3E50; display: block; margin-bottom: 0.5rem;">Room Type:</label>
                        <select id="roomTypeFilter" style="padding: 0.5rem; border: 2px solid #C9A961; border-radius: 8px; cursor: pointer; font-weight: 600; width: 100%;">
                            <option value="">All Types</option>
                            <option value="Regular">Regular</option>
                            <option value="Deluxe">Deluxe</option>
                            <option value="VIP">VIP</option>
                        </select>
                    </div>
                    <div>
                        <label for="guestCapacityFilter" style="font-weight: 600; color: #2C3E50; display: block; margin-bottom: 0.5rem;">Guest Capacity:</label>
                        <select id="guestCapacityFilter" style="padding: 0.5rem; border: 2px solid #C9A961; border-radius: 8px; cursor: pointer; font-weight: 600; width: 100%;">
                            <option value="">All Capacities</option>
                            <option value="2">2 Guests</option>
                            <option value="8">4-8 Guests</option>
                            <option value="20">10-20 Guests</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="calendar-nav">
                <button onclick="previousMonth()"><i class="fas fa-chevron-left"></i></button>
                <button onclick="currentMonth()">Today</button>
                <button onclick="nextMonth()"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
        
        <div class="calendar-grid" id="calendarGrid"></div>
    </div>
    
    <!-- Sidebar with Reservations -->
    <div class="calendar-sidebar">
        <h3 style="margin-bottom: 1rem; color: #2C3E50;">
            <i class="fas fa-list"></i> Reservations
        </h3>
        
        <!-- Reservation Details -->
        <div id="reservationDetails" style="display: none; margin-bottom: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #C9A961;">
            <h4 style="color: #2C3E50; margin-bottom: 0.75rem;">Selected Reservation</h4>
            <div style="font-size: 0.9rem; color: #666;">
                <div style="margin-bottom: 0.5rem;"><strong>Guest:</strong> <span id="detailGuest">-</span></div>
                <div style="margin-bottom: 0.5rem;"><strong>Room:</strong> <span id="detailRoom">-</span></div>
                <div style="margin-bottom: 0.5rem;"><strong>Guests:</strong> <span id="detailGuests">-</span></div>
                <div style="margin-bottom: 0.5rem;"><strong>Check-in:</strong> <span id="detailCheckin">-</span></div>
                <div style="margin-bottom: 0.5rem;"><strong>Check-out:</strong> <span id="detailCheckout">-</span></div>
                <div style="margin-bottom: 0.5rem;"><strong>Email:</strong> <span id="detailEmail">-</span></div>
            </div>
        </div>
        
        <div class="reservations-list" id="reservationsList"></div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal" id="cancelModal">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-times-circle"></i> Cancel Reservation
        </div>
        <form id="cancelForm" method="POST">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="reservation_id" id="cancelReservationId">
            
            <div class="form-group">
                <label>Guest Name</label>
                <input type="text" id="cancelGuestName" readonly>
            </div>
            
            <div class="form-group">
                <label>Reason for Cancellation</label>
                <textarea name="cancel_reason" placeholder="Enter reason..." required></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('cancelModal')">Close</button>
                <button type="submit" class="btn btn-danger">Cancel Reservation</button>
            </div>
        </form>
    </div>
</div>

<!-- Move/Reschedule Modal -->
<div class="modal" id="moveModal">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-calendar-check"></i> Reschedule Reservation
        </div>
        <form id="moveForm" method="POST">
            <input type="hidden" name="action" value="move">
            <input type="hidden" name="reservation_id" id="moveReservationId">
            
            <div class="form-group">
                <label>Guest Name</label>
                <input type="text" id="moveGuestName" readonly>
            </div>
            
            <div class="form-group">
                <label>Number of Guests</label>
                <input type="text" id="moveGuestCount" readonly>
            </div>
            
            <div class="form-group">
                <label>Current Dates</label>
                <input type="text" id="moveCurrentDates" readonly>
            </div>
            
            <div class="form-group">
                <label>New Check-in Date</label>
                <input type="date" name="new_checkin" required>
            </div>
            
            <div class="form-group">
                <label>New Check-out Date</label>
                <input type="date" name="new_checkout" required>
            </div>
            
            <div class="form-group">
                <label>Reason for Rescheduling</label>
                <textarea name="move_reason" placeholder="Enter reason..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('moveModal')">Close</button>
                <button type="submit" class="btn btn-primary">Reschedule</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentDate = new Date();
let reservations = <?php echo json_encode($reservations); ?>;
let selectedReservation = null;
let selectedRoomType = ''; // Filter for room type
let selectedGuestCapacity = ''; // Filter for guest capacity

// Add event listeners for filters
document.addEventListener('DOMContentLoaded', function() {
    const roomTypeFilter = document.getElementById('roomTypeFilter');
    const guestCapacityFilter = document.getElementById('guestCapacityFilter');
    
    if (roomTypeFilter) {
        roomTypeFilter.addEventListener('change', function() {
            selectedRoomType = this.value;
            renderCalendar();
            renderReservations();
        });
    }
    
    if (guestCapacityFilter) {
        guestCapacityFilter.addEventListener('change', function() {
            selectedGuestCapacity = this.value;
            renderCalendar();
            renderReservations();
        });
    }
});

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    // Update header
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('monthYear').textContent = monthNames[month] + ' ' + year;
    
    // Get first day and number of days
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();
    
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '';
    
    // Day headers
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayNames.forEach(day => {
        const header = document.createElement('div');
        header.className = 'calendar-day-header';
        header.textContent = day;
        grid.appendChild(header);
    });
    
    // Previous month days
    for (let i = firstDay - 1; i >= 0; i--) {
        const day = document.createElement('div');
        day.className = 'calendar-day other-month';
        day.textContent = daysInPrevMonth - i;
        grid.appendChild(day);
    }
    
    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
        const dayEl = document.createElement('div');
        dayEl.className = 'calendar-day';
        dayEl.textContent = day;
        
        const date = new Date(year, month, day);
        const today = new Date();
        
        if (date.toDateString() === today.toDateString()) {
            dayEl.classList.add('today');
        }
        
        // Check for bookings on this date (filtered by room type and guest capacity if selected)
        const bookingsOnDate = reservations.filter(r => {
            const checkin = new Date(r.checkin_date + 'T00:00:00');
            const checkout = new Date(r.checkout_date + 'T00:00:00');
            const dateInRange = date >= checkin && date < checkout;
            
            // Apply room type filter
            if (selectedRoomType && r.room_type !== selectedRoomType) {
                return false;
            }
            
            // Apply guest capacity filter
            if (selectedGuestCapacity && r.guests !== selectedGuestCapacity) {
                return false;
            }
            
            return dateInRange;
        });
        
        if (bookingsOnDate.length > 0) {
            dayEl.classList.add('has-booking');
            const count = document.createElement('div');
            count.className = 'booking-count';
            count.textContent = bookingsOnDate.length;
            dayEl.appendChild(count);
        }
        
        grid.appendChild(dayEl);
    }
    
    // Next month days
    const totalCells = grid.children.length - 7; // Subtract day headers
    const remainingCells = 42 - totalCells; // 6 rows * 7 days
    for (let day = 1; day <= remainingCells; day++) {
        const dayEl = document.createElement('div');
        dayEl.className = 'calendar-day other-month';
        dayEl.textContent = day;
        grid.appendChild(dayEl);
    }
    
    renderReservations();
}

function renderReservations() {
    const list = document.getElementById('reservationsList');
    list.innerHTML = '';
    
    // Filter reservations by room type and guest capacity if selected
    const filteredReservations = reservations.filter(r => {
        if (selectedRoomType && r.room_type !== selectedRoomType) {
            return false;
        }
        if (selectedGuestCapacity && r.guests !== selectedGuestCapacity) {
            return false;
        }
        return true;
    });
    
    if (filteredReservations.length === 0) {
        list.innerHTML = '<p style="color: #999; text-align: center;">No active reservations</p>';
        return;
    }
    
    filteredReservations.forEach(r => {
        const item = document.createElement('div');
        item.className = 'reservation-item' + (selectedReservation?.id === r.id ? ' selected' : '');
        item.onclick = () => selectReservation(r);
        
        const checkin = new Date(r.checkin_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const checkout = new Date(r.checkout_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        
        item.innerHTML = `
            <div class="reservation-guest">${r.guest_name}</div>
            <div class="reservation-dates">${checkin} - ${checkout}</div>
            <div class="reservation-room">${r.room_type} • ${r.guests} guests</div>
        `;
        
        list.appendChild(item);
    });
}

function selectReservation(reservation) {
    selectedReservation = reservation;
    renderReservations();
    showReservationActions();
    
    // Enable buttons when a reservation is selected
    const moveBtn = document.getElementById('moveBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    if (moveBtn) moveBtn.disabled = false;
    if (cancelBtn) cancelBtn.disabled = false;
}

function showReservationActions() {
    if (!selectedReservation) {
        document.getElementById('reservationDetails').style.display = 'none';
        return;
    }
    
    // Show reservation details
    const detailsDiv = document.getElementById('reservationDetails');
    if (detailsDiv) {
        detailsDiv.style.display = 'block';
        
        const checkin = new Date(selectedReservation.checkin_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const checkout = new Date(selectedReservation.checkout_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        
        document.getElementById('detailGuest').textContent = selectedReservation.guest_name || '-';
        document.getElementById('detailRoom').textContent = selectedReservation.room_type || '-';
        document.getElementById('detailGuests').textContent = selectedReservation.guests || '-';
        document.getElementById('detailCheckin').textContent = checkin;
        document.getElementById('detailCheckout').textContent = checkout;
        document.getElementById('detailEmail').textContent = selectedReservation.email || '-';
    }
}

function openCancelModal() {
    if (!selectedReservation) return;
    document.getElementById('cancelReservationId').value = selectedReservation.id;
    document.getElementById('cancelGuestName').value = selectedReservation.guest_name;
    document.getElementById('cancelModal').classList.add('active');
}

function openMoveModal() {
    if (!selectedReservation) return;
    const checkin = new Date(selectedReservation.checkin_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    const checkout = new Date(selectedReservation.checkout_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    
    document.getElementById('moveReservationId').value = selectedReservation.id;
    document.getElementById('moveGuestName').value = selectedReservation.guest_name;
    document.getElementById('moveGuestCount').value = selectedReservation.guests + ' guests';
    document.getElementById('moveCurrentDates').value = checkin + ' - ' + checkout;
    document.getElementById('moveModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function previousMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
}

function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
}

function currentMonth() {
    currentDate = new Date();
    renderCalendar();
}

// Add action buttons to sidebar
document.addEventListener('DOMContentLoaded', function() {
    renderCalendar();
    
    // Add action buttons after reservations list
    const sidebar = document.querySelector('.calendar-sidebar');
    const actions = document.createElement('div');
    actions.style.marginTop = '1.5rem';
    actions.style.display = 'flex';
    actions.style.gap = '0.5rem';
    actions.innerHTML = `
        <button class="btn btn-primary" style="flex: 1;" onclick="openMoveModal()" id="moveBtn" disabled>
            <i class="fas fa-calendar-check"></i> Reschedule
        </button>
        <button class="btn btn-danger" style="flex: 1;" onclick="openCancelModal()" id="cancelBtn" disabled>
            <i class="fas fa-times"></i> Cancel
        </button>
    `;
    sidebar.appendChild(actions);
    
    // Handle form submissions
    const cancelForm = document.getElementById('cancelForm');
    if (cancelForm) {
        cancelForm.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(this)
            }).then(() => {
                location.reload();
            });
        });
    }
    
    const moveForm = document.getElementById('moveForm');
    if (moveForm) {
        moveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(this)
            }).then(() => {
                location.reload();
            });
        });
    }
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});
</script>

<?php include 'template_footer.php'; ?>

<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

// Handle booking actions (cancel, adjust)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bookingId = intval($_POST['booking_id'] ?? 0);
    $action = $_POST['action'];
    
    if ($bookingId > 0) {
        try {
            $conn = getDBConnection();
            
            if ($action === 'cancel') {
                $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
                $stmt->bind_param("i", $bookingId);
                $stmt->execute();
                $stmt->close();
                $message = "Booking cancelled successfully";
            } elseif ($action === 'adjust') {
                $newCheckin = $_POST['new_checkin'] ?? '';
                $newCheckout = $_POST['new_checkout'] ?? '';
                
                if ($newCheckin && $newCheckout) {
                    $stmt = $conn->prepare("UPDATE reservations SET checkin_date = ?, checkout_date = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $newCheckin, $newCheckout, $bookingId);
                    $stmt->execute();
                    $stmt->close();
                    $message = "Booking dates adjusted successfully";
                }
            }
            
            $conn->close();
            
        } catch (Exception $e) {
            error_log("Booking action error: " . $e->getMessage());
            $error = "Failed to process action";
        }
    }
}

// Get current month and year
$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));

// Set page variables
$pageTitle = 'Calendar Bookings';
$currentPage = 'calendar';
?>

<?php include 'template_header.php'; ?>

<link rel="stylesheet" href="../assets/css/calendar-booking.css">
<style>
.admin-calendar-container {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.calendar-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f0f0f0;
    flex-wrap: wrap;
    gap: 1rem;
}

.calendar-controls h2 {
    color: #2C3E50;
    font-size: 1.8rem;
    font-weight: 700;
}

.month-nav {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.month-nav button {
    background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.month-nav button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(201, 169, 97, 0.3);
}

.month-display {
    font-size: 1.2rem;
    font-weight: 700;
    color: #2C3E50;
    min-width: 200px;
    text-align: center;
}

.bookings-list {
    margin-top: 2rem;
}

.booking-item {
    background: #f8f9fa;
    border-left: 4px solid #C9A961;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 10px;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 1rem;
    align-items: center;
}

.booking-info h4 {
    color: #2C3E50;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.booking-info p {
    color: #666;
    font-size: 0.9rem;
    margin: 0.25rem 0;
}

.booking-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.btn-adjust, .btn-cancel {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-adjust {
    background: #28a745;
    color: white;
}

.btn-adjust:hover {
    background: #218838;
}

.btn-cancel {
    background: #dc3545;
    color: white;
}

.btn-cancel:hover {
    background: #c82333;
}

.room-status-container {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 10px;
}

.room-status-container h3 {
    color: #2C3E50;
    margin-bottom: 1rem;
    font-weight: 700;
}

.room-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
}

.room-status-card {
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
    border: 2px solid #ddd;
    transition: all 0.3s ease;
}

.room-status-card.available {
    background: #d4edda;
    border-color: #28a745;
    color: #155724;
}

.room-status-card.occupied {
    background: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}

.room-status-card .room-number {
    font-size: 1.2rem;
    display: block;
    margin-bottom: 0.5rem;
}

.room-status-card .room-type {
    font-size: 0.8rem;
    opacity: 0.8;
}
</style>

<div class="content-container">
    <div class="page-header">
        <h1><i class="fas fa-calendar-alt"></i> Calendar Bookings</h1>
        <p>Manage room bookings by calendar view</p>
    </div>

    <?php if (isset($message)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <div class="admin-calendar-container">
        <div class="calendar-controls">
            <h2 style="width: 100%; margin-bottom: 1rem;">Booking Calendar</h2>
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; width: 100%;">
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <label style="font-weight: 600; color: #2C3E50;">Room Type:</label>
                    <select id="roomTypeFilter" onchange="filterByRoomType()" style="padding: 0.5rem; border: 2px solid #e0e0e0; border-radius: 8px; font-weight: 600;">
                        <option value="">All Types</option>
                        <option value="Regular">Regular</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="VIP">VIP</option>
                    </select>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <label style="font-weight: 600; color: #2C3E50;">Room Number:</label>
                    <select id="roomNumberFilter" onchange="filterByRoomNumber()" style="padding: 0.5rem; border: 2px solid #e0e0e0; border-radius: 8px; font-weight: 600;">
                        <option value="">All Rooms</option>
                        <option value="101">101 (Regular)</option>
                        <option value="102">102 (Regular)</option>
                        <option value="103">103 (Regular)</option>
                        <option value="104">104 (Regular)</option>
                        <option value="105">105 (Regular)</option>
                        <option value="106">106 (Regular)</option>
                        <option value="201">201 (Deluxe)</option>
                        <option value="202">202 (Deluxe)</option>
                        <option value="203">203 (Deluxe)</option>
                        <option value="204">204 (Deluxe)</option>
                        <option value="205">205 (Deluxe)</option>
                        <option value="206">206 (Deluxe)</option>
                        <option value="301">301 (VIP)</option>
                        <option value="302">302 (VIP)</option>
                        <option value="303">303 (VIP)</option>
                        <option value="304">304 (VIP)</option>
                        <option value="305">305 (VIP)</option>
                        <option value="306">306 (VIP)</option>
                    </select>
                </div>
                <div class="month-nav">
                    <button onclick="navigateMonth(-1)"><i class="fas fa-chevron-left"></i> Previous</button>
                    <div class="month-display" id="monthDisplay"></div>
                    <button onclick="navigateMonth(1)">Next <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>

        <div class="calendar-grid-container">
            <div class="calendar-weekdays">
                <div class="calendar-weekday">Sun</div>
                <div class="calendar-weekday">Mon</div>
                <div class="calendar-weekday">Tue</div>
                <div class="calendar-weekday">Wed</div>
                <div class="calendar-weekday">Thu</div>
                <div class="calendar-weekday">Fri</div>
                <div class="calendar-weekday">Sat</div>
            </div>
            <div class="calendar-days" id="adminCalendarDays"></div>
        </div>

        <div class="bookings-list" id="bookingsList"></div>

        <div class="room-status-container">
            <h3>Room Occupancy Status</h3>
            <div class="room-status-grid" id="roomStatusGrid"></div>
        </div>
    </div>
</div>

<script>
let currentMonth = <?php echo $month - 1; ?>;
let currentYear = <?php echo $year; ?>;

function renderAdminCalendar() {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'];
    
    document.getElementById('monthDisplay').textContent = `${monthNames[currentMonth]} ${currentYear}`;
    
    // Fetch bookings for this month
    fetch(`../api/get_availability.php?month=${currentMonth + 1}&year=${currentYear}&admin=1`)
        .then(response => response.json())
        .then(data => {
            renderCalendarDays(data.bookings || {});
            renderBookingsList(data.bookings || {});
            renderRoomStatus(data.bookings || {});
        });
}

function renderCalendarDays(bookings) {
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const calendarDays = document.getElementById('adminCalendarDays');
    calendarDays.innerHTML = '';
    
    // Get selected filters
    const selectedRoom = document.getElementById('roomNumberFilter').value;
    const selectedType = document.getElementById('roomTypeFilter').value;
    
    // Previous month days
    for (let i = 0; i < firstDay; i++) {
        calendarDays.innerHTML += '<div class="calendar-day other-month"></div>';
    }
    
    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        let bookingsForDate = bookings[dateStr] || [];
        
        // Filter by room number if selected
        if (selectedRoom) {
            bookingsForDate = bookingsForDate.filter(b => b.room_number == selectedRoom);
        }
        
        // Filter by room type if selected
        if (selectedType) {
            bookingsForDate = bookingsForDate.filter(b => b.room_type === selectedType);
        }
        
        const hasBooking = bookingsForDate.length > 0;
        
        calendarDays.innerHTML += `
            <div class="calendar-day ${hasBooking ? 'booked' : 'available'}">
                <span class="calendar-day-number">${day}</span>
                ${hasBooking ? `<span class="calendar-day-status">${bookingsForDate.length} booking(s)</span>` : ''}
            </div>
        `;
    }
}

function renderBookingsList(bookings) {
    const bookingsList = document.getElementById('bookingsList');
    const allBookings = [];
    
    Object.keys(bookings).forEach(date => {
        bookings[date].forEach(booking => {
            allBookings.push({ ...booking, date });
        });
    });
    
    // Apply filters
    const selectedRoom = document.getElementById('roomNumberFilter').value;
    const selectedType = document.getElementById('roomTypeFilter').value;
    let filteredBookings = allBookings;
    
    if (selectedRoom) {
        filteredBookings = filteredBookings.filter(booking => booking.room_number == selectedRoom);
    }
    
    if (selectedType) {
        filteredBookings = filteredBookings.filter(booking => booking.room_type === selectedType);
    }
    
    if (filteredBookings.length === 0) {
        bookingsList.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem;">No bookings for this month</p>';
        return;
    }
    
    bookingsList.innerHTML = '<h3 style="margin-bottom: 1rem; color: #2C3E50;">Bookings This Month</h3>' +
        filteredBookings.map(booking => `
            <div class="booking-item">
                <div class="booking-info">
                    <h4>${booking.guest_name}</h4>
                    <p><i class="fas fa-bed"></i> Room ${booking.room_number} (${booking.room_type})</p>
                    <p><i class="fas fa-calendar"></i> ${booking.checkin_date} to ${booking.checkout_date}</p>
                    <p><i class="fas fa-envelope"></i> ${booking.email}</p>
                </div>
                <div>
                    <span class="status-badge status-${booking.status}">${booking.status}</span>
                </div>
                <div class="booking-actions">
                    <button class="btn-adjust" onclick="adjustBooking(${booking.id})">
                        <i class="fas fa-edit"></i> Adjust
                    </button>
                    <button class="btn-cancel" onclick="cancelBooking(${booking.id})">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        `).join('');
}

function navigateMonth(direction) {
    currentMonth += direction;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    } else if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    renderAdminCalendar();
}

function filterByRoomNumber() {
    renderAdminCalendar();
}

function filterByRoomType() {
    renderAdminCalendar();
}

function renderRoomStatus(bookings) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Define all rooms
    const rooms = [
        { number: '101', type: 'Regular' },
        { number: '102', type: 'Regular' },
        { number: '103', type: 'Regular' },
        { number: '104', type: 'Regular' },
        { number: '105', type: 'Regular' },
        { number: '106', type: 'Regular' },
        { number: '201', type: 'Deluxe' },
        { number: '202', type: 'Deluxe' },
        { number: '203', type: 'Deluxe' },
        { number: '204', type: 'Deluxe' },
        { number: '205', type: 'Deluxe' },
        { number: '206', type: 'Deluxe' },
        { number: '301', type: 'VIP' },
        { number: '302', type: 'VIP' },
        { number: '303', type: 'VIP' },
        { number: '304', type: 'VIP' },
        { number: '305', type: 'VIP' },
        { number: '306', type: 'VIP' }
    ];
    
    const roomStatusGrid = document.getElementById('roomStatusGrid');
    roomStatusGrid.innerHTML = '';
    
    rooms.forEach(room => {
        // Check if room is occupied today
        const todayStr = today.toISOString().split('T')[0];
        const todayBookings = bookings[todayStr] || [];
        const isOccupied = todayBookings.some(b => b.room_number == room.number);
        
        const card = document.createElement('div');
        card.className = `room-status-card ${isOccupied ? 'occupied' : 'available'}`;
        card.innerHTML = `
            <span class="room-number">${room.number}</span>
            <span class="room-type">${room.type}</span>
            <span style="font-size: 0.75rem; display: block; margin-top: 0.5rem;">
                ${isOccupied ? '🔴 Occupied' : '🟢 Available'}
            </span>
        `;
        roomStatusGrid.appendChild(card);
    });
}

function adjustBooking(id) {
    const newCheckin = prompt('Enter new check-in date (YYYY-MM-DD):');
    const newCheckout = prompt('Enter new check-out date (YYYY-MM-DD):');
    
    if (newCheckin && newCheckout) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="adjust">
            <input type="hidden" name="booking_id" value="${id}">
            <input type="hidden" name="new_checkin" value="${newCheckin}">
            <input type="hidden" name="new_checkout" value="${newCheckout}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function cancelBooking(id) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="booking_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Initialize
renderAdminCalendar();
</script>

<?php include 'template_footer.php'; ?>

<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'includes/photo_functions.php';

// Pre-fill from calendar selection
$prefilledCheckin = $_GET['checkin'] ?? '';
$prefilledCheckout = $_GET['checkout'] ?? '';

// Get current user's information for auto-fill if logged in
$userInfo = null;
if (isLoggedIn()) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $userInfo = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $userInfo = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en" id="top">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Stay - Paradise Hotel & Resort</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/booking.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/simple-calendar.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="booking-page">
    <!-- Header -->
    <header class="booking-header">
        <div class="header-container">
            <div class="header-left">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Home</span>
                </a>
            </div>
            <div class="header-center">
                <div class="hotel-logo">
                    <i class="fas fa-hotel"></i>
                    <span>Paradise Hotel & Resort</span>
                </div>
            </div>
            <div class="header-right">
                <?php if (isLoggedIn()): ?>
                    <div class="user-info">
                        <a href="profile.php" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: #2C3E50;">
                            <i class="fas fa-user-circle"></i>
                            <span>Hello, <?php echo htmlspecialchars(getFirstName() ?? getUsername()); ?></span>
                        </a>
                        <a href="logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="auth-links">
                        <a href="login.php" class="auth-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="register.php" class="auth-link">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="booking-container">
        <!-- Booking Form -->
        <div class="booking-form-section">
            <div class="booking-card">
                <div class="booking-header">
                    <h1><i class="fas fa-calendar-check"></i> Book Your Stay</h1>
                    <p>Complete your reservation details below</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <form id="bookingForm" action="process.php" method="POST" class="booking-form">
                    <!-- Room Type Selection -->
                    <div class="form-section">
                        <h3><i class="fas fa-bed"></i> Room Type</h3>
                        <div class="form-group">
                            <select id="roomTypeSelect" name="room_type" onchange="selectRoomType(this.value)" required>
                                <option value="">-- Select Room Type --</option>
                                <option value="Regular">Regular - ₱1,500/night</option>
                                <option value="Deluxe">Deluxe - ₱2,500/night</option>
                                <option value="VIP">VIP - ₱4,000/night</option>
                            </select>
                        </div>
                        <input type="hidden" id="selectedRoomType" name="room_type_hidden" value="">
                    </div>

                    <!-- Guest Capacity Selection -->
                    <div class="form-section">
                        <h3><i class="fas fa-users"></i> Guest Capacity</h3>
                        <div class="form-group">
                            <select id="capacitySelect" name="capacity" onchange="selectCapacity(this.value)" required>
                                <option value="">-- Select Capacity --</option>
                                <option value="2">2 Guests (Couple/Single)</option>
                                <option value="8">4-8 Guests (Small Family)</option>
                                <option value="20">10-20 Guests (Large Group)</option>
                            </select>
                        </div>
                        <input type="hidden" id="selectedCapacity" name="capacity_hidden" value="">
                    </div>

                    <!-- Room Number Selection -->
                    <div class="form-section" id="roomNumberSection" style="display: none;">
                        <h3><i class="fas fa-door-open"></i> Select Specific Room</h3>
                        <div class="room-number-options" id="roomNumberOptions">
                            <p class="instruction-text">Select room type and capacity first</p>
                        </div>
                        <input type="hidden" id="selectedRoomNumber" name="room_number" value="">
                    </div>

                    <!-- Guest Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Guest Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" required placeholder="Enter your full name" 
                                       value="<?php echo htmlspecialchars($userInfo['full_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required placeholder="Enter your email"
                                       value="<?php echo htmlspecialchars($userInfo['email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" required placeholder="Enter your phone number"
                                       value="<?php echo htmlspecialchars($userInfo['phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="form-section">
                        <h3><i class="fas fa-calendar"></i> Booking Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Select Dates *</label>
                                <button type="button" class="btn-calendar-toggle" onclick="openSimpleCalendar()">
                                    <i class="fas fa-calendar-alt"></i> Select Check-in & Check-out
                                </button>
                                <div id="selectedDatesDisplay" style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; text-align: center; display: none;">
                                    <div style="color: #999; font-size: 0.9rem; margin-bottom: 0.5rem;">Selected Dates</div>
                                    <div style="color: #2C3E50; font-weight: 700; font-size: 1.1rem;">
                                        <span id="displayCheckin">-</span> to <span id="displayCheckout">-</span>
                                    </div>
                                    <div id="displayNights" style="color: #C9A961; font-size: 0.9rem; margin-top: 0.5rem;"></div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="checkin" name="checkin" value="">
                        <input type="hidden" id="checkout" name="checkout" value="">
                        <input type="hidden" id="nights" name="nights" value="">
                    </div>

                    <!-- Special Requests -->
                    <div class="form-section">
                        <h3><i class="fas fa-comment"></i> Special Requests</h3>
                        <div class="form-group">
                            <label for="specialRequests">Additional Information</label>
                            <textarea id="specialRequests" name="special_requests" rows="4" placeholder="Any special requests or requirements..."></textarea>
                        </div>
                    </div>

                    <!-- Price Summary -->
                    <div class="form-section">
                        <h3><i class="fas fa-receipt"></i> Price Summary</h3>
                        <div class="price-summary">
                            <div class="price-row">
                                <span>Room Rate:</span>
                                <span id="roomRate">₱0</span>
                            </div>
                            <div class="price-row">
                                <span>Number of Nights:</span>
                                <span id="nightsCount">0</span>
                            </div>
                            <div class="price-row total">
                                <span>Total Amount:</span>
                                <span id="totalAmount">₱0</span>
                            </div>
                            <input type="hidden" id="price" name="price" value="0">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-actions">
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-credit-card"></i> Complete Booking
                        </button>
                        <p class="form-note">
                            <i class="fas fa-info-circle"></i>
                            You will be redirected to payment options after clicking Complete Booking
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Room Preview -->
        <div class="room-preview-section">
            <div class="preview-card">
                <h3><i class="fas fa-images"></i> Room Gallery</h3>
                <div id="roomPreview" class="room-preview">
                    <div class="preview-placeholder">
                        <i class="fas fa-bed"></i>
                        <p>Select a room type to see preview</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple Calendar Modal -->
    <div id="simpleCalendarModal">
        <div class="simple-calendar-container">
            <div class="simple-calendar-header">
                <div>
                    <h2 id="simpleCalendarMonth">January 2026</h2>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div class="simple-calendar-nav">
                        <button onclick="previousMonth()">←</button>
                        <button onclick="nextMonth()">→</button>
                    </div>
                    <button class="simple-calendar-close" onclick="closeSimpleCalendar()">✕</button>
                </div>
            </div>
            
            <div class="simple-calendar-weekdays">
                <div class="simple-calendar-weekday">Sun</div>
                <div class="simple-calendar-weekday">Mon</div>
                <div class="simple-calendar-weekday">Tue</div>
                <div class="simple-calendar-weekday">Wed</div>
                <div class="simple-calendar-weekday">Thu</div>
                <div class="simple-calendar-weekday">Fri</div>
                <div class="simple-calendar-weekday">Sat</div>
            </div>
            
            <div class="simple-calendar-grid" id="simpleCalendarGrid"></div>
            
            <div id="simpleCalendarDisplay"></div>
            
            <div class="simple-calendar-footer">
                <button type="button" class="simple-calendar-btn simple-calendar-btn-secondary" onclick="closeSimpleCalendar()">
                    Cancel
                </button>
                <button type="button" class="simple-calendar-btn simple-calendar-btn-primary" onclick="confirmSimpleCalendar()">
                    Confirm Dates
                </button>
            </div>
        </div>
    </div>

    <script src="assets/js/booking.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/simple-calendar.js?v=<?php echo time(); ?>"></script>
</body>
</html>
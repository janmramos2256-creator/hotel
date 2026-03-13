// ============================================
// BOOKING PAGE JAVASCRIPT - NEW LAYOUT
// ============================================

// Global variables
let selectedRoomType = null;
let selectedCapacity = null;
let selectedRoomNumber = null;

// Room prices
const roomPriceList = {
    'Regular': { 2: 1500, 8: 3000, 20: 6000 },
    'Deluxe': { 2: 2500, 8: 4500, 20: 8500 },
    'VIP': { 2: 4000, 8: 7000, 20: 12000 }
};

// Select room type
function selectRoomType(type, element) {
    selectedRoomType = type;
    document.getElementById('selectedRoomType').value = type;
    
    // Update UI
    document.querySelectorAll('.room-option').forEach(card => {
        card.classList.remove('selected');
    });
    element.classList.add('selected');
    
    // Load room gallery
    loadRoomGallery(type);
    
    // Show room number section if capacity is selected
    if (selectedCapacity) {
        loadRoomNumbers(type, selectedCapacity);
        updatePriceCalculation();
    }
}

// Select guest capacity
function selectCapacity(capacity, element) {
    selectedCapacity = capacity;
    document.getElementById('selectedCapacity').value = capacity;
    
    // Update UI
    document.querySelectorAll('.capacity-option').forEach(card => {
        card.classList.remove('selected');
    });
    element.classList.add('selected');
    
    // Show room number section if room type is selected
    if (selectedRoomType) {
        loadRoomNumbers(selectedRoomType, capacity);
        updatePriceCalculation();
    }
}

// Load room gallery
function loadRoomGallery(roomType) {
    const galleryContainer = document.getElementById('roomPreview');
    
    fetch(`api/get_room_images.php?room_type=${roomType}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.photos.length > 0) {
                galleryContainer.innerHTML = data.photos.map(photo => `
                    <div class="gallery-image">
                        <img src="${photo.file_path}" alt="${photo.original_name}">
                    </div>
                `).join('');
            } else {
                galleryContainer.innerHTML = `
                    <div class="preview-placeholder">
                        <i class="fas fa-image"></i>
                        <p>No images available for this room type</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading gallery:', error);
            galleryContainer.innerHTML = `
                <div class="preview-placeholder">
                    <i class="fas fa-image"></i>
                    <p>Unable to load room images</p>
                </div>
            `;
        });
}

// Load room numbers
function loadRoomNumbers(roomType, capacity) {
    const roomNumberSection = document.getElementById('roomNumberSection');
    const roomNumberOptions = document.getElementById('roomNumberOptions');
    
    // Room inventory
    const roomInventory = {
        'Regular': {
            '2': ['101', '102'],
            '8': ['103', '104'],
            '20': ['105', '106']
        },
        'Deluxe': {
            '2': ['201', '202'],
            '8': ['203', '204'],
            '20': ['205', '206']
        },
        'VIP': {
            '2': ['301', '302'],
            '8': ['303', '304'],
            '20': ['305', '306']
        }
    };
    
    const rooms = roomInventory[roomType]?.[capacity] || [];
    
    if (rooms.length === 0) {
        roomNumberOptions.innerHTML = '<p class="instruction-text">No rooms available</p>';
        roomNumberSection.style.display = 'none';
        return;
    }
    
    roomNumberOptions.innerHTML = rooms.map(roomNum => `
        <div class="room-number-option" onclick="selectRoomNumber('${roomNum}', this)">
            <div class="room-number-badge">
                <i class="fas fa-door-open"></i>
                <span>Room ${roomNum}</span>
            </div>
            <div class="room-status">
                <i class="fas fa-check-circle"></i>
                <span>Available</span>
            </div>
        </div>
    `).join('');
    
    roomNumberSection.style.display = 'block';
}

// Select room number
function selectRoomNumber(roomNumber, element) {
    selectedRoomNumber = roomNumber;
    document.getElementById('selectedRoomNumber').value = roomNumber;
    
    // Update UI
    document.querySelectorAll('.room-number-option').forEach(card => {
        card.classList.remove('selected');
    });
    element.classList.add('selected');
}

// Update price calculation
function updatePriceCalculation() {
    const checkin = document.getElementById('checkin').value;
    const checkout = document.getElementById('checkout').value;
    
    if (!checkin || !checkout || !selectedRoomType || !selectedCapacity) {
        return;
    }
    
    const checkinDate = new Date(checkin);
    const checkoutDate = new Date(checkout);
    const nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
    
    if (nights <= 0) {
        return;
    }
    
    const pricePerNight = roomPriceList[selectedRoomType][selectedCapacity];
    const totalPrice = pricePerNight * nights;
    
    // Update display
    document.getElementById('roomRate').textContent = `₱${pricePerNight.toLocaleString()}`;
    document.getElementById('nightsCount').textContent = nights;
    document.getElementById('totalAmount').textContent = `₱${totalPrice.toLocaleString()}`;
    document.getElementById('price').value = totalPrice;
    document.getElementById('nights').value = nights;
    
    // Show nights badge
    if (nights > 0) {
        document.getElementById('nightsBadge').style.display = 'inline-flex';
        document.getElementById('nightsBadgeCount').textContent = nights;
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for date changes
    document.getElementById('checkin').addEventListener('change', updatePriceCalculation);
    document.getElementById('checkout').addEventListener('change', updatePriceCalculation);
    
    // Auto-populate from URL if available
    const params = new URLSearchParams(window.location.search);
    const checkin = params.get('checkin');
    const checkout = params.get('checkout');
    
    if (checkin) {
        document.getElementById('checkin').value = checkin;
    }
    if (checkout) {
        document.getElementById('checkout').value = checkout;
    }
    
    if (checkin && checkout) {
        updatePriceCalculation();
    }
});

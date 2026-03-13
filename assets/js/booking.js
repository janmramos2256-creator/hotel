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
function selectRoomType(type) {
    if (!type) {
        selectedRoomType = null;
        document.getElementById('selectedRoomType').value = '';
        document.getElementById('roomNumberSection').style.display = 'none';
        return;
    }
    
    selectedRoomType = type;
    document.getElementById('selectedRoomType').value = type;
    
    // Load room gallery
    loadRoomGallery(type);
    
    // Show room number section if capacity is selected
    if (selectedCapacity) {
        loadRoomNumbers(type, selectedCapacity);
        updatePriceCalculation();
    }
}

// Select guest capacity
function selectCapacity(capacity) {
    if (!capacity) {
        selectedCapacity = null;
        document.getElementById('selectedCapacity').value = '';
        document.getElementById('roomNumberSection').style.display = 'none';
        return;
    }
    
    selectedCapacity = capacity;
    document.getElementById('selectedCapacity').value = capacity;
    
    // Show room number section if room type is selected
    if (selectedRoomType) {
        loadRoomNumbers(selectedRoomType, capacity);
        updatePriceCalculation();
    }
}

// Room details data
const roomDetails = {
    'Regular': {
        description: 'Comfortable room with essential amenities. Perfect for budget-conscious travelers.',
        amenities: [
            { icon: 'fas fa-bed', text: 'Queen Size Bed with Quality Linens' },
            { icon: 'fas fa-wifi', text: 'High-Speed WiFi' },
            { icon: 'fas fa-bath', text: 'Private Bathroom with Shower' },
            { icon: 'fas fa-tv', text: '32-inch TV with Cable' },
            { icon: 'fas fa-snowflake', text: 'Air Conditioning' },
            { icon: 'fas fa-phone', text: 'Direct Dial Telephone' }
        ],
        services: [
            { icon: 'fas fa-check-circle', text: 'Welcome drink & snacks' },
            { icon: 'fas fa-check-circle', text: 'Quality toiletries' },
            { icon: 'fas fa-check-circle', text: '24/7 front desk' },
            { icon: 'fas fa-check-circle', text: 'Free parking' },
            { icon: 'fas fa-check-circle', text: 'Daily housekeeping' },
            { icon: 'fas fa-check-circle', text: 'Pool access' }
        ]
    },
    'Deluxe': {
        description: 'Comfortable room with better amenities and nicer furnishings. Good choice for couples who want more comfort and space.',
        amenities: [
            { icon: 'fas fa-bed', text: 'King Size Bed with Quality Linens' },
            { icon: 'fas fa-wifi', text: 'High-Speed WiFi' },
            { icon: 'fas fa-bath', text: 'Private Bathroom with Bathtub' },
            { icon: 'fas fa-tv', text: '40-inch Smart TV with Cable' },
            { icon: 'fas fa-snowflake', text: 'Inverter Air Conditioning' },
            { icon: 'fas fa-coffee', text: 'Coffee & Tea Making Facilities' },
            { icon: 'fas fa-couch', text: 'Small Sitting Area' },
            { icon: 'fas fa-window-maximize', text: 'Balcony with Garden View' }
        ],
        services: [
            { icon: 'fas fa-check-circle', text: 'Welcome drink & snacks' },
            { icon: 'fas fa-check-circle', text: 'Quality toiletries' },
            { icon: 'fas fa-check-circle', text: '24/7 front desk' },
            { icon: 'fas fa-check-circle', text: 'Free parking' },
            { icon: 'fas fa-check-circle', text: 'Daily housekeeping' },
            { icon: 'fas fa-check-circle', text: 'Pool & gym access' },
            { icon: 'fas fa-check-circle', text: 'Room service' },
            { icon: 'fas fa-check-circle', text: 'Concierge service' }
        ]
    },
    'VIP': {
        description: 'Luxury suite with premium amenities, exclusive services, and personalized attention. The ultimate experience for discerning guests.',
        amenities: [
            { icon: 'fas fa-bed', text: 'King Size Bed with Premium Linens' },
            { icon: 'fas fa-wifi', text: 'Ultra High-Speed WiFi' },
            { icon: 'fas fa-bath', text: 'Luxury Bathroom with Jacuzzi' },
            { icon: 'fas fa-tv', text: '55-inch Smart TV with Premium Cable' },
            { icon: 'fas fa-snowflake', text: 'Smart Climate Control' },
            { icon: 'fas fa-coffee', text: 'Premium Coffee & Tea Facilities' },
            { icon: 'fas fa-couch', text: 'Spacious Living Area' },
            { icon: 'fas fa-window-maximize', text: 'Private Balcony with Ocean View' },
            { icon: 'fas fa-wine-glass', text: 'Mini Bar with Premium Selection' },
            { icon: 'fas fa-phone', text: 'Direct Dial & Mobile Phone' }
        ],
        services: [
            { icon: 'fas fa-check-circle', text: 'Welcome champagne & gourmet snacks' },
            { icon: 'fas fa-check-circle', text: 'Premium toiletries' },
            { icon: 'fas fa-check-circle', text: '24/7 concierge' },
            { icon: 'fas fa-check-circle', text: 'Complimentary parking' },
            { icon: 'fas fa-check-circle', text: 'Twice daily housekeeping' },
            { icon: 'fas fa-check-circle', text: 'Premium pool & spa access' },
            { icon: 'fas fa-check-circle', text: 'Priority room service' },
            { icon: 'fas fa-check-circle', text: 'Personal butler service' },
            { icon: 'fas fa-check-circle', text: 'Airport transfers' },
            { icon: 'fas fa-check-circle', text: 'Dining reservations' }
        ]
    }
};

// Load room gallery and details
function loadRoomGallery(roomType) {
    const galleryContainer = document.getElementById('roomPreview');
    
    // Load gallery images
    fetch(`api/get_room_images.php?room_type=${roomType}`)
        .then(response => response.json())
        .then(data => {
            let galleryHTML = '';
            
            if (data.success && data.photos.length > 0) {
                galleryHTML = data.photos.map(photo => `
                    <div class="gallery-image">
                        <img src="${photo.file_path}" alt="${photo.original_name}">
                    </div>
                `).join('');
            } else {
                galleryHTML = `
                    <div class="room-image-placeholder">
                        <i class="fas fa-image"></i>
                        <p>Room ${roomType} - No images uploaded yet</p>
                    </div>
                `;
            }
            
            // Add room details
            const details = roomDetails[roomType];
            if (details) {
                galleryHTML += `
                    <div class="room-details-preview">
                        <div class="room-details-header">
                            <div class="room-details-title">${roomType} Room</div>
                            <div class="room-details-description">${details.description}</div>
                        </div>
                        
                        <div class="amenities-section">
                            <div class="amenities-title">
                                <i class="fas fa-star"></i> Amenities
                            </div>
                            <div class="amenities-grid">
                                ${details.amenities.map(amenity => `
                                    <div class="amenity-item">
                                        <i class="${amenity.icon}"></i>
                                        <span class="amenity-text">${amenity.text}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        
                        <div class="services-section">
                            <div class="services-title">
                                <i class="fas fa-gift"></i> Included Services
                            </div>
                            <div class="services-grid">
                                ${details.services.map(service => `
                                    <div class="service-item">
                                        <i class="${service.icon}"></i>
                                        <span>${service.text}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            galleryContainer.innerHTML = galleryHTML;
        })
        .catch(error => {
            console.error('Error loading gallery:', error);
            galleryContainer.innerHTML = `
                <div class="preview-placeholder">
                    <i class="fas fa-image"></i>
                    <p>Unable to load room information</p>
                </div>
            `;
        });
}

// Load room numbers
function loadRoomNumbers(roomType, capacity) {
    const roomNumberSection = document.getElementById('roomNumberSection');
    const roomNumberOptions = document.getElementById('roomNumberOptions');
    
    // Room inventory - 6 rooms each type
    const roomInventory = {
        'Regular': ['101', '102', '103', '104', '105', '106'],
        'Deluxe': ['201', '202', '203', '204', '205', '206'],
        'VIP': ['301', '302', '303', '304', '305', '306']
    };
    
    const rooms = roomInventory[roomType] || [];
    
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
    
    // Add form submit handler
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate all required fields
            const roomType = document.getElementById('roomTypeSelect').value;
            const capacity = document.getElementById('capacitySelect').value;
            const roomNumber = document.getElementById('selectedRoomNumber').value;
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const checkin = document.getElementById('checkin').value;
            const checkout = document.getElementById('checkout').value;
            const price = document.getElementById('price').value;
            
            // Check all validations
            const errors = [];
            
            if (!roomType) errors.push('Please select a room type');
            if (!capacity) errors.push('Please select guest capacity');
            if (!roomNumber) errors.push('Please select a room number');
            if (!name) errors.push('Please enter your full name');
            if (!email) errors.push('Please enter your email');
            if (!phone) errors.push('Please enter your phone number');
            if (!checkin) errors.push('Please select check-in date');
            if (!checkout) errors.push('Please select check-out date');
            if (!price || price <= 0) errors.push('Invalid price calculation');
            
            if (errors.length > 0) {
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
                return false;
            }
            
            // All validations passed, submit the form
            this.submit();
        });
    }
});

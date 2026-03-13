<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$roomType = $_GET['room_type'] ?? '';
$guestCapacity = $_GET['guests'] ?? '';
$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));
$isAdmin = isset($_GET['admin']);

try {
    $conn = getDBConnection();
    
    // Get first and last day of month
    $firstDay = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $lastDay = date('Y-m-t', strtotime($firstDay));
    
    // Initialize availability array with all dates as available
    $availability = [];
    $currentDate = new DateTime($firstDay);
    $endDate = new DateTime($lastDay);
    $endDate->modify('+1 day');
    
    while ($currentDate < $endDate) {
        $dateStr = $currentDate->format('Y-m-d');
        $availability[$dateStr] = true; // Default to available
        $currentDate->modify('+1 day');
    }
    
    if ($isAdmin) {
        // Admin view - get all bookings
        $sql = "SELECT id, guest_name, email, room_type, room_number, checkin_date, checkout_date, status 
                FROM reservations 
                WHERE (checkin_date BETWEEN ? AND ? OR checkout_date BETWEEN ? AND ?)
                AND status != 'cancelled'
                ORDER BY checkin_date";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $firstDay, $lastDay, $firstDay, $lastDay);
    } else {
        // User view - get booked dates for specific room type/capacity or all
        $whereConditions = [];
        $params = [];
        $paramTypes = '';
        
        $whereConditions[] = "(checkin_date BETWEEN ? AND ? OR checkout_date BETWEEN ? AND ?)";
        $whereConditions[] = "status IN ('pending', 'confirmed')";
        $params = [$firstDay, $lastDay, $firstDay, $lastDay];
        $paramTypes = 'ssss';
        
        if ($roomType) {
            $whereConditions[] = "room_type = ?";
            $params[] = $roomType;
            $paramTypes .= 's';
        }
        
        if ($guestCapacity) {
            $whereConditions[] = "guests = ?";
            $params[] = $guestCapacity;
            $paramTypes .= 's';
        }
        
        $sql = "SELECT checkin_date, checkout_date, room_type
                FROM reservations 
                WHERE " . implode(' AND ', $whereConditions) . "
                ORDER BY checkin_date";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($paramTypes, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    
    while ($row = $result->fetch_assoc()) {
        if ($isAdmin) {
            $date = $row['checkin_date'];
            if (!isset($bookings[$date])) {
                $bookings[$date] = [];
            }
            $bookings[$date][] = $row;
        } else {
            // Mark dates as unavailable
            $checkin = new DateTime($row['checkin_date']);
            $checkout = new DateTime($row['checkout_date']);
            
            while ($checkin < $checkout) {
                $dateStr = $checkin->format('Y-m-d');
                $availability[$dateStr] = false; // Mark as booked
                $checkin->modify('+1 day');
            }
        }
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'availability' => $availability,
        'bookings' => $bookings
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

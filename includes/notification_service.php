<?php
/**
 * Notification Service
 * Handles sending notifications to users about booking changes
 */

require_once dirname(__DIR__) . '/config/database.php';

class NotificationService {
    
    /**
     * Send cancellation notification
     */
    public static function notifyCancellation($reservationId, $reason = '') {
        try {
            $conn = getDBConnection();
            
            // Get reservation details
            $stmt = $conn->prepare("SELECT r.*, u.email, u.full_name FROM reservations r 
                                   LEFT JOIN users u ON r.user_id = u.id 
                                   WHERE r.id = ?");
            $stmt->bind_param("i", $reservationId);
            $stmt->execute();
            $result = $stmt->get_result();
            $reservation = $result->fetch_assoc();
            $stmt->close();
            
            if (!$reservation) {
                return false;
            }
            
            // Create notification record
            $message = "Your reservation for " . $reservation['room_type'] . " has been cancelled.";
            if ($reason) {
                $message .= " Reason: " . $reason;
            }
            
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, reservation_id, type, message) 
                                   VALUES (?, ?, 'cancellation', ?)");
            $stmt->bind_param("iis", $reservation['user_id'], $reservationId, $message);
            $stmt->execute();
            $stmt->close();
            
            // Send email if user has email
            if ($reservation['email']) {
                self::sendCancellationEmail($reservation, $reason);
            }
            
            $conn->close();
            return true;
            
        } catch (Exception $e) {
            error_log("Notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send rescheduling notification
     */
    public static function notifyReschedule($reservationId, $newCheckin, $newCheckout, $reason = '') {
        try {
            $conn = getDBConnection();
            
            // Get reservation details
            $stmt = $conn->prepare("SELECT r.*, u.email, u.full_name FROM reservations r 
                                   LEFT JOIN users u ON r.user_id = u.id 
                                   WHERE r.id = ?");
            $stmt->bind_param("i", $reservationId);
            $stmt->execute();
            $result = $stmt->get_result();
            $reservation = $result->fetch_assoc();
            $stmt->close();
            
            if (!$reservation) {
                return false;
            }
            
            // Create notification record
            $checkinFormatted = date('M d, Y', strtotime($newCheckin));
            $checkoutFormatted = date('M d, Y', strtotime($newCheckout));
            $message = "Your reservation has been rescheduled to " . $checkinFormatted . " - " . $checkoutFormatted . ".";
            if ($reason) {
                $message .= " Reason: " . $reason;
            }
            
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, reservation_id, type, message) 
                                   VALUES (?, ?, 'rescheduled', ?)");
            $stmt->bind_param("iis", $reservation['user_id'], $reservationId, $message);
            $stmt->execute();
            $stmt->close();
            
            // Send email if user has email
            if ($reservation['email']) {
                self::sendRescheduleEmail($reservation, $newCheckin, $newCheckout, $reason);
            }
            
            $conn->close();
            return true;
            
        } catch (Exception $e) {
            error_log("Notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send cancellation email
     */
    private static function sendCancellationEmail($reservation, $reason = '') {
        $to = $reservation['email'];
        $subject = "Reservation Cancelled - Paradise Hotel & Resort";
        
        $checkin = date('M d, Y', strtotime($reservation['checkin_date']));
        $checkout = date('M d, Y', strtotime($reservation['checkout_date']));
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #C9A961; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 5px; }
                .footer { margin-top: 20px; font-size: 12px; color: #999; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Reservation Cancelled</h2>
                </div>
                <div class='content'>
                    <p>Dear " . htmlspecialchars($reservation['full_name'] ?? $reservation['guest_name']) . ",</p>
                    <p>We regret to inform you that your reservation has been cancelled.</p>
                    <p><strong>Reservation Details:</strong></p>
                    <ul>
                        <li>Room Type: " . htmlspecialchars($reservation['room_type']) . "</li>
                        <li>Check-in: " . $checkin . "</li>
                        <li>Check-out: " . $checkout . "</li>
                        <li>Guests: " . $reservation['guests'] . "</li>
                    </ul>";
        
        if ($reason) {
            $body .= "<p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>";
        }
        
        $body .= "
                    <p>If you have any questions, please contact us at info@paradisehotel.com</p>
                </div>
                <div class='footer'>
                    <p>Paradise Hotel & Resort</p>
                </div>
            </div>
        </body>
        </html>";
        
        self::sendEmail($to, $subject, $body);
    }
    
    /**
     * Send reschedule email
     */
    private static function sendRescheduleEmail($reservation, $newCheckin, $newCheckout, $reason = '') {
        $to = $reservation['email'];
        $subject = "Reservation Rescheduled - Paradise Hotel & Resort";
        
        $oldCheckin = date('M d, Y', strtotime($reservation['checkin_date']));
        $oldCheckout = date('M d, Y', strtotime($reservation['checkout_date']));
        $newCheckinFormatted = date('M d, Y', strtotime($newCheckin));
        $newCheckoutFormatted = date('M d, Y', strtotime($newCheckout));
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #C9A961; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 5px; }
                .footer { margin-top: 20px; font-size: 12px; color: #999; }
                .dates { background: white; padding: 15px; border-left: 4px solid #C9A961; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Reservation Rescheduled</h2>
                </div>
                <div class='content'>
                    <p>Dear " . htmlspecialchars($reservation['full_name'] ?? $reservation['guest_name']) . ",</p>
                    <p>Your reservation has been rescheduled to new dates.</p>
                    
                    <div class='dates'>
                        <p><strong>Previous Dates:</strong><br>" . $oldCheckin . " - " . $oldCheckout . "</p>
                        <p><strong>New Dates:</strong><br>" . $newCheckinFormatted . " - " . $newCheckoutFormatted . "</p>
                    </div>
                    
                    <p><strong>Reservation Details:</strong></p>
                    <ul>
                        <li>Room Type: " . htmlspecialchars($reservation['room_type']) . "</li>
                        <li>Guests: " . $reservation['guests'] . "</li>
                    </ul>";
        
        if ($reason) {
            $body .= "<p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>";
        }
        
        $body .= "
                    <p>If you have any questions or concerns, please contact us at info@paradisehotel.com</p>
                </div>
                <div class='footer'>
                    <p>Paradise Hotel & Resort</p>
                </div>
            </div>
        </body>
        </html>";
        
        self::sendEmail($to, $subject, $body);
    }
    
    /**
     * Send email helper
     */
    private static function sendEmail($to, $subject, $body) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@paradisehotel.com" . "\r\n";
        
        mail($to, $subject, $body, $headers);
    }
    
    /**
     * Get user notifications
     */
    public static function getUserNotifications($userId, $limit = 10) {
        try {
            $conn = getDBConnection();
            
            $stmt = $conn->prepare("SELECT * FROM notifications 
                                   WHERE user_id = ? 
                                   ORDER BY created_at DESC 
                                   LIMIT ?");
            $stmt->bind_param("ii", $userId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $notifications = [];
            
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
            
            $stmt->close();
            $conn->close();
            
            return $notifications;
            
        } catch (Exception $e) {
            error_log("Notification fetch error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark notification as read
     */
    public static function markAsRead($notificationId) {
        try {
            $conn = getDBConnection();
            
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
            $stmt->bind_param("i", $notificationId);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Notification update error: " . $e->getMessage());
            return false;
        }
    }
}
?>

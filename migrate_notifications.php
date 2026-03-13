<?php
/**
 * Database Migration: Add Notifications Table
 * Run this once to add notification support to your database
 */

require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    // Create notifications table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NULL,
        reservation_id INT(11) NOT NULL,
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "✅ Notifications table created successfully!<br>";
    } else {
        echo "⚠️ Notifications table already exists or error occurred.<br>";
    }
    
    // Create notification_emails table for tracking sent emails
    $sql2 = "CREATE TABLE IF NOT EXISTS notification_emails (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        notification_id INT(11) NOT NULL,
        email VARCHAR(100) NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql2)) {
        echo "✅ Notification emails table created successfully!<br>";
    } else {
        echo "⚠️ Notification emails table already exists or error occurred.<br>";
    }
    
    $conn->close();
    echo "<br><strong>✅ Migration completed!</strong>";
    
} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
}
?>

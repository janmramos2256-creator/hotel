<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'includes/notification_service.php';

// Require user login
requireLogin();

$userId = getUserId();
$notifications = NotificationService::getUserNotifications($userId, 50);

// Mark as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    NotificationService::markAsRead(intval($_GET['mark_read']));
    header('Location: notifications.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications - Paradise Hotel & Resort</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Montserrat', sans-serif;
        }

        .notifications-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .notifications-header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notifications-header h1 {
            color: #2C3E50;
            font-size: 1.8rem;
            margin: 0;
        }

        .notification-item {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #C9A961;
            transition: all 0.3s ease;
        }

        .notification-item:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .notification-item.unread {
            background: #f0f8ff;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.75rem;
        }

        .notification-type {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            color: #2C3E50;
            font-size: 1.1rem;
        }

        .notification-type.cancellation {
            color: #dc3545;
        }

        .notification-type.rescheduled {
            color: #2196F3;
        }

        .notification-time {
            font-size: 0.85rem;
            color: #999;
        }

        .notification-message {
            color: #555;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .notification-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #C9A961;
            color: white;
        }

        .btn-primary:hover {
            background: #8B7355;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #2C3E50;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .empty-state {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 3rem;
            color: #C9A961;
            margin-bottom: 1rem;
        }

        .empty-state h2 {
            color: #2C3E50;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #999;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #C9A961;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            gap: 1rem;
        }

        .icon-cancellation {
            color: #dc3545;
        }

        .icon-rescheduled {
            color: #2196F3;
        }
    </style>
</head>
<body>
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
                <div class="user-info">
                    <a href="profile.php" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: #2C3E50;">
                        <i class="fas fa-user-circle"></i>
                        <span>Hello, <?php echo htmlspecialchars(getFirstName() ?? getUsername()); ?></span>
                    </a>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="notifications-container">
        <div class="notifications-header">
            <h1><i class="fas fa-bell"></i> My Notifications</h1>
            <span style="color: #999; font-size: 0.9rem;">
                <?php echo count($notifications); ?> notification<?php echo count($notifications) !== 1 ? 's' : ''; ?>
            </span>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h2>No Notifications</h2>
                <p>You don't have any booking notifications at this time.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                    <div class="notification-header">
                        <div class="notification-type <?php echo $notification['type']; ?>">
                            <?php if ($notification['type'] === 'cancellation'): ?>
                                <i class="fas fa-times-circle icon-cancellation"></i>
                                Reservation Cancelled
                            <?php elseif ($notification['type'] === 'rescheduled'): ?>
                                <i class="fas fa-calendar-check icon-rescheduled"></i>
                                Reservation Rescheduled
                            <?php else: ?>
                                <i class="fas fa-info-circle"></i>
                                Booking Update
                            <?php endif; ?>
                        </div>
                        <div class="notification-time">
                            <?php 
                            $time = strtotime($notification['created_at']);
                            $now = time();
                            $diff = $now - $time;
                            
                            if ($diff < 60) {
                                echo 'Just now';
                            } elseif ($diff < 3600) {
                                echo floor($diff / 60) . ' minutes ago';
                            } elseif ($diff < 86400) {
                                echo floor($diff / 3600) . ' hours ago';
                            } else {
                                echo date('M d, Y', $time);
                            }
                            ?>
                        </div>
                    </div>

                    <div class="notification-message">
                        <?php echo htmlspecialchars($notification['message']); ?>
                    </div>

                    <div class="notification-actions">
                        <?php if (!$notification['is_read']): ?>
                            <a href="?mark_read=<?php echo $notification['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-check"></i> Mark as Read
                            </a>
                        <?php endif; ?>
                        <a href="booking.php" class="btn btn-primary">
                            <i class="fas fa-calendar"></i> View Bookings
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <style>
        .booking-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left, .header-right {
            flex: 1;
        }

        .header-center {
            flex: 1;
            text-align: center;
        }

        .hotel-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            color: #2C3E50;
            font-size: 1.1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .logout-btn {
            background: #C9A961;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #8B7355;
        }
    </style>
</body>
</html>

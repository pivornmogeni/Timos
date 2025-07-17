<?php
require_once 'connect_db.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    log_activity('admin_logout', 'Admin logout', $_SESSION['admin_id']);
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_booking_status') {
        $booking_id = $_POST['booking_id'];
        $new_status = $_POST['status'];
        
        try {
            $stmt = $conn->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $booking_id]);
            
            if ($stmt->rowCount() > 0) {
                // Get booking details for notification
                $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
                $stmt->execute([$booking_id]);
                $booking = $stmt->fetch();
                
                if ($booking) {
                    log_activity('booking_status_updated', "Booking {$booking['booking_ref']} status changed to $new_status", $_SESSION['admin_id']);
                    
                    // Send notification email based on status
                    if ($new_status === 'confirmed') {
                        $subject = "Booking Confirmed - TIMO'S Spa";
                        $message = "Your booking ({$booking['booking_ref']}) has been confirmed for {$booking['date']} at {$booking['time']}.";
                    } elseif ($new_status === 'cancelled') {
                        $subject = "Booking Cancelled - TIMO'S Spa";
                        $message = "Your booking ({$booking['booking_ref']}) has been cancelled. Please contact us for rescheduling.";
                    }
                    
                    if (isset($subject)) {
                        send_email($booking['email'], $subject, $message);
                        send_sms($booking['phone'], $message);
                    }
                }
                
                $success_message = "Booking status updated successfully";
            }
        } catch(PDOException $e) {
            $error_message = "Error updating booking status";
        }
    }
    
    if ($_POST['action'] === 'mark_message_read') {
        $message_id = $_POST['message_id'];
        
        try {
            $stmt = $conn->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
            $stmt->execute([$message_id]);
            
            if ($stmt->rowCount() > 0) {
                log_activity('message_marked_read', "Message $message_id marked as read", $_SESSION['admin_id']);
            }
        } catch(PDOException $e) {
            $error_message = "Error updating message status";
        }
    }
}

// Get statistics
try {
    // Today's bookings
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE date = CURDATE()");
    $stmt->execute();
    $today_bookings = $stmt->fetch()['count'];
    
    // Pending bookings
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
    $stmt->execute();
    $pending_bookings = $stmt->fetch()['count'];
    
    // Unread messages
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'");
    $stmt->execute();
    $unread_messages = $stmt->fetch()['count'];
    
    // Total bookings this month
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $stmt->execute();
    $monthly_bookings = $stmt->fetch()['count'];
    
} catch(PDOException $e) {
    $error_message = "Error loading statistics";
}

// Get recent bookings
try {
    $stmt = $conn->prepare("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $recent_bookings = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error_message = "Error loading bookings";
}

// Get unread messages
try {
    $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE status = 'unread' ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $unread_messages_data = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error_message = "Error loading messages";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TIMO'S Makeup & Nails Spa</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
            color: #D4AF37;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .admin-header .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logout-btn {
            background: #D4AF37;
            color: #1a1a1a;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #B8860B;
            transform: translateY(-1px);
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #D4AF37;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }
        
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        .stat-card .stat-icon {
            float: right;
            font-size: 2rem;
            color: #D4AF37;
            opacity: 0.7;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: #1a1a1a;
            color: #D4AF37;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .booking-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .booking-item:last-child {
            border-bottom: none;
        }
        
        .booking-info h4 {
            color: #1a1a1a;
            margin-bottom: 0.25rem;
        }
        
        .booking-info p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .booking-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .booking-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #D4AF37;
            color: #1a1a1a;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .message-item {
            border-bottom: 1px solid #eee;
            padding: 1rem;
        }
        
        .message-item:last-child {
            border-bottom: none;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .message-from {
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .message-time {
            color: #666;
            font-size: 0.8rem;
        }
        
        .message-subject {
            font-weight: 500;
            color: #D4AF37;
            margin-bottom: 0.25rem;
        }
        
        .message-preview {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
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
        
        .no-data {
            text-align: center;
            color: #666;
            padding: 2rem;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1><i class="fas fa-tachometer-alt"></i> TIMO'S Admin Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="?logout=1" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <div class="dashboard-container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-calendar-day stat-icon"></i>
                <h3>Today's Bookings</h3>
                <div class="stat-number"><?php echo $today_bookings ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock stat-icon"></i>
                <h3>Pending Bookings</h3>
                <div class="stat-number"><?php echo $pending_bookings ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-envelope stat-icon"></i>
                <h3>Unread Messages</h3>
                <div class="stat-number"><?php echo $unread_messages ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-chart-line stat-icon"></i>
                <h3>Monthly Bookings</h3>
                <div class="stat-number"><?php echo $monthly_bookings ?? 0; ?></div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Bookings -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar-alt"></i> Recent Bookings
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_bookings)): ?>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <div class="booking-item">
                                <div class="booking-info">
                                    <h4><?php echo htmlspecialchars($booking['name']); ?></h4>
                                    <p>
                                        <i class="fas fa-cut"></i> <?php echo htmlspecialchars($booking['service']); ?><br>
                                        <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($booking['date'])); ?> at <?php echo $booking['time']; ?><br>
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['phone']); ?>
                                    </p>
                                </div>
                                <div class="booking-actions">
                                    <span class="booking-status status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_booking_status">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_booking_status">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-calendar-times"></i>
                            <p>No recent bookings</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Unread Messages -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-envelope"></i> Unread Messages
                </div>
                <div class="card-body">
                    <?php if (!empty($unread_messages_data)): ?>
                        <?php foreach ($unread_messages_data as $message): ?>
                            <div class="message-item">
                                <div class="message-header">
                                    <span class="message-from"><?php echo htmlspecialchars($message['name']); ?></span>
                                    <span class="message-time"><?php echo date('M j, H:i', strtotime($message['created_at'])); ?></span>
                                </div>
                                <div class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></div>
                                <div class="message-preview">
                                    <?php echo htmlspecialchars(substr($message['message'], 0, 100)); ?>...
                                </div>
                                <form method="POST" style="margin-top: 0.5rem;">
                                    <input type="hidden" name="action" value="mark_message_read">
                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check"></i> Mark as Read
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-envelope-open"></i>
                            <p>No unread messages</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
        
        // Confirm status changes
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const action = this.querySelector('input[name="action"]')?.value;
                if (action === 'update_booking_status') {
                    const status = this.querySelector('input[name="status"]')?.value;
                    if (status === 'cancelled') {
                        if (!confirm('Are you sure you want to cancel this booking?')) {
                            e.preventDefault();
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>

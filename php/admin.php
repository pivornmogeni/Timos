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
    $stmt = $

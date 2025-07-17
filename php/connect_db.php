<?php
// Database connection configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "timos_spa";

// Create connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone number (Kenya format)
function validate_phone($phone) {
    // Remove spaces and special characters
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Check if it's a valid Kenyan phone number
    if (preg_match('/^(\+254|0)[7-9][0-9]{8}$/', $phone)) {
        return true;
    }
    return false;
}

// Function to format phone number
function format_phone($phone) {
    // Remove spaces and special characters
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Convert to international format
    if (substr($phone, 0, 1) === '0') {
        $phone = '+254' . substr($phone, 1);
    }
    
    return $phone;
}

// Function to generate booking reference
function generate_booking_ref() {
    return 'TMS' . date('Ymd') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

// Function to log activities
function log_activity($action, $details = '', $user_id = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO activity_logs (action, details, user_id, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->execute([$action, $details, $user_id, $ip_address]);
    } catch(PDOException $e) {
        error_log("Log activity error: " . $e->getMessage());
    }
}

// Function to send email (placeholder - replace with actual email service)
function send_email($to, $subject, $message, $from = 'noreply@timosspa.com') {
    $headers = "From: " . $from . "\r\n";
    $headers .= "Reply-To: " . $from . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // In production, use a proper email service like PHPMailer or SwiftMailer
    return mail($to, $subject, $message, $headers);
}

// Function to send SMS (placeholder - integrate with SMS service)
function send_sms($phone, $message) {
    // Integrate with SMS service like Africa's Talking or Twilio
    // This is a placeholder function
    error_log("SMS to $phone: $message");
    return true;
}

// Set timezone
date_default_timezone_set('Africa/Nairobi');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

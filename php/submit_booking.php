<?php
require_once 'connect_db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get and sanitize form data
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $service = sanitize_input($_POST['service'] ?? '');
    $date = sanitize_input($_POST['date'] ?? '');
    $time = sanitize_input($_POST['time'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email) || !validate_email($email)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($phone) || !validate_phone($phone)) {
        $errors[] = 'Valid phone number is required';
    }
    
    if (empty($service)) {
        $errors[] = 'Service selection is required';
    }
    
    if (empty($date)) {
        $errors[] = 'Date is required';
    } else {
        // Check if date is not in the past
        $selected_date = new DateTime($date);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($selected_date < $today) {
            $errors[] = 'Please select a future date';
        }
    }
    
    if (empty($time)) {
        $errors[] = 'Time is required';
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    
    // Format phone number
    $phone = format_phone($phone);
    
    // Generate booking reference
    $booking_ref = generate_booking_ref();
    
    // Check for existing booking at the same time
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE date = ? AND time = ? AND status != 'cancelled'");
    $stmt->execute([$date, $time]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'This time slot is already booked. Please choose another time.']);
        exit;
    }
    
    // Insert booking into database
    $stmt = $conn->prepare("
        INSERT INTO bookings (booking_ref, name, email, phone, service, date, time, notes, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([$booking_ref, $name, $email, $phone, $service, $date, $time, $notes]);
    
    if ($stmt->rowCount() > 0) {
        // Log the booking
        log_activity('booking_created', "Booking created: $booking_ref for $name");
        
        // Send confirmation email
        $email_subject = "Booking Confirmation - TIMO'S Makeup & Nails Spa";
        $email_message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #D4AF37; color: #1a1a1a; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .details { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; }
                .footer { background: #1a1a1a; color: #D4AF37; padding: 15px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>TIMO'S MAKEUP & NAILS SPA</h2>
                <p>Booking Confirmation</p>
            </div>
            <div class='content'>
                <p>Dear $name,</p>
                <p>Thank you for booking with TIMO'S Makeup & Nails Spa. Your booking has been received and is pending confirmation.</p>
                
                <div class='details'>
                    <h3>Booking Details:</h3>
                    <p><strong>Booking Reference:</strong> $booking_ref</p>
                    <p><strong>Service:</strong> $service</p>
                    <p><strong>Date:</strong> " . date('l, F j, Y', strtotime($date)) . "</p>
                    <p><strong>Time:</strong> $time</p>
                    <p><strong>Phone:</strong> $phone</p>
                    " . (!empty($notes) ? "<p><strong>Notes:</strong> $notes</p>" : "") . "
                </div>
                
                <p>We will contact you shortly to confirm your appointment. If you need to make any changes, please call us at +254 714 109550.</p>
                
                <p>We look forward to serving you!</p>
                
                <p>Best regards,<br>
                TIMO'S Makeup & Nails Spa Team</p>
            </div>
            <div class='footer'>
                <p>Safaricom House, Eldoret - Basement | +254 714 109550 | @timos.makeupnails</p>
            </div>
        </body>
        </html>
        ";
        
        send_email($email, $email_subject, $email_message);
        
        // Send SMS notification
        $sms_message = "Hi $name! Your booking at TIMO'S Spa has been received. Ref: $booking_ref. Date: $date at $time. We'll call to confirm. Thanks!";
        send_sms($phone, $sms_message);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Booking submitted successfully! You will receive a confirmation email shortly.',
            'booking_ref' => $booking_ref
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create booking. Please try again.']);
    }
    
} catch(PDOException $e) {
    error_log("Booking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again later.']);
} catch(Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
}
?>

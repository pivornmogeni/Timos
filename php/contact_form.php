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
    $subject = sanitize_input($_POST['subject'] ?? '');
    $message = sanitize_input($_POST['message'] ?? '');
    
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
    
    if (empty($subject)) {
        $errors[] = 'Subject is required';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    
    // Format phone number
    $phone = format_phone($phone);
    
    // Insert contact message into database
    $stmt = $conn->prepare("
        INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'unread', NOW())
    ");
    
    $stmt->execute([$name, $email, $phone, $subject, $message]);
    
    if ($stmt->rowCount() > 0) {
        // Log the contact
        log_activity('contact_message', "New contact message from $name: $subject");
        
        // Send confirmation email to customer
        $customer_email_subject = "Message Received - TIMO'S Makeup & Nails Spa";
        $customer_email_message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #D4AF37; color: #1a1a1a; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .message-box { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; }
                .footer { background: #1a1a1a; color: #D4AF37; padding: 15px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>TIMO'S MAKEUP & NAILS SPA</h2>
                <p>Message Received</p>
            </div>
            <div class='content'>
                <p>Dear $name,</p>
                <p>Thank you for contacting TIMO'S Makeup & Nails Spa. We have received your message and will respond within 24 hours.</p>
                
                <div class='message-box'>
                    <h3>Your Message:</h3>
                    <p><strong>Subject:</strong> $subject</p>
                    <p><strong>Message:</strong> $message</p>
                </div>
                
                <p>If you need immediate assistance, please call us at +254 714 109550.</p>
                
                <p>Best regards,<br>
                TIMO'S Makeup & Nails Spa Team</p>
            </div>
            <div class='footer'>
                <p>Safaricom House, Eldoret - Basement | +254 714 109550 | @timos.makeupnails</p>
            </div>
        </body>
        </html>
        ";
        
        send_email($email, $customer_email_subject, $customer_email_message);
        
        // Send notification email to admin
        $admin_email = 'admin@timosspa.com'; // Replace with actual admin email
        $admin_subject = "New Contact Message - TIMO'S Spa";
        $admin_message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #D4AF37; color: #1a1a1a; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .details { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>New Contact Message</h2>
            </div>
            <div class='content'>
                <div class='details'>
                    <p><strong>Name:</strong> $name</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Phone:</strong> $phone</p>
                    <p><strong>Subject:</strong> $subject</p>
                    <p><strong>Message:</strong> $message</p>
                    <p><strong>Received:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        send_email($admin_email, $admin_subject, $admin_message);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Thank you for your message! We will get back to you within 24 hours.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message. Please try again.']);
    }
    
} catch(PDOException $e) {
    error_log("Contact form error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again later.']);
} catch(Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
}
?>

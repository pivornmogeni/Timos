-- TIMO'S Makeup & Nails Spa Complete Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS timos_spa;
USE timos_spa;

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_ref VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    service VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    notes TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_status (status),
    INDEX idx_booking_ref (booking_ref)
);

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    user_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Services table (for managing available services)
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2),
    duration INT, -- in minutes
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Gallery images table
CREATE TABLE IF NOT EXISTS gallery_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100),
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    category VARCHAR(50),
    is_featured BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_featured (is_featured)
);

-- Testimonials table
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(100) NOT NULL,
    client_image VARCHAR(255),
    rating INT CHECK (rating >= 1 AND rating <= 5),
    review TEXT NOT NULL,
    service_received VARCHAR(100),
    is_approved BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_approved (is_approved),
    INDEX idx_featured (is_featured)
);

-- Business settings table
CREATE TABLE IF NOT EXISTS business_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Newsletter subscribers table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_email (email)
);

-- Appointment reminders table
CREATE TABLE IF NOT EXISTS appointment_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    reminder_type ENUM('email', 'sms') NOT NULL,
    reminder_time DATETIME NOT NULL,
    sent_at TIMESTAMP NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_booking_id (booking_id),
    INDEX idx_reminder_time (reminder_time),
    INDEX idx_status (status),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@timosspa.com');

-- Insert default services
INSERT INTO services (name, description, price, duration) VALUES
('Manicure & Pedicure', 'Complete nail care with professional products and relaxing treatments', 2500.00, 90),
('Braiding', 'Expert braiding services for all hair types and styles', 1500.00, 120),
('Installing Locks (Dreadlocks)', 'Professional dreadlock installation and maintenance', 3000.00, 180),
('Makeup & Glam Sessions', 'Professional makeup application for special occasions', 2000.00, 60),
('Nail Art & Extensions', 'Creative nail art designs and professional extensions', 1800.00, 75),
('Facials & Skincare Treatments', 'Rejuvenating facial treatments for healthy, glowing skin', 2200.00, 60),
('Retouching Locks', 'Maintenance and retouching of existing dreadlocks', 1200.00, 90),
('Waxing Services', 'Professional waxing services for smooth skin', 1000.00, 45);

-- Insert default business settings
INSERT INTO business_settings (setting_key, setting_value, description) VALUES
('business_name', 'TIMO\'S Makeup & Nails Spa', 'Business name'),
('business_address', 'Safaricom House, Eldoret - Basement', 'Business address'),
('business_phone', '+254 714 109550', 'Business phone number'),
('business_email', 'info@timosspa.com', 'Business email address'),
('business_instagram', '@timos.makeupnails', 'Instagram handle'),
('opening_hours', '{"monday": "9:00-18:00", "tuesday": "9:00-18:00", "wednesday": "9:00-18:00", "thursday": "9:00-18:00", "friday": "9:00-18:00", "saturday": "9:00-17:00", "sunday": "closed"}', 'Business operating hours'),
('booking_advance_days', '30', 'Number of days in advance bookings can be made'),
('booking_buffer_hours', '2', 'Minimum hours before booking can be made'),
('email_notifications', 'true', 'Enable email notifications'),
('sms_notifications', 'true', 'Enable SMS notifications');

-- Insert sample gallery images
INSERT INTO gallery_images (title, image_url, alt_text, category, is_featured) VALUES
('Elegant Nail Art', 'https://i.pinimg.com/564x/2b/3c/4d/2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e.jpg', 'Beautiful nail art design', 'nails', TRUE),
('Professional Braiding', 'https://i.pinimg.com/564x/7c/8d/9e/7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f.jpg', 'Expert braiding service', 'braiding', TRUE),
('Glamorous Makeup', 'https://i.pinimg.com/564x/1f/2a/3b/1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c.jpg', 'Professional makeup application', 'makeup', TRUE),
('Relaxing Facial', 'https://i.pinimg.com/564x/5e/6f/7a/5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b.jpg', 'Rejuvenating facial treatment', 'skincare', TRUE),
('Dreadlock Installation', 'https://i.pinimg.com/564x/4a/5b/6c/4a5b6c7d8e9f0a1b2c3d4e5f6a7b8c9d.jpg', 'Professional dreadlock service', 'dreadlocks', FALSE),
('Pedicure Service', 'https://i.pinimg.com/564x/6d/7e/8f/6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a.jpg', 'Relaxing pedicure treatment', 'nails', FALSE),
('Bridal Makeup', 'https://i.pinimg.com/564x/3c/4d/5e/3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f.jpg', 'Beautiful bridal makeup', 'makeup', FALSE),
('Gel Nails', 'https://i.pinimg.com/564x/8f/9a/0b/8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c.jpg', 'Professional gel nail service', 'nails', FALSE);

-- Insert sample testimonials
INSERT INTO testimonials (client_name, client_image, rating, review, service_received, is_approved, is_featured) VALUES
('Sarah M.', 'https://i.pinimg.com/564x/3c/4d/5e/3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f.jpg', 5, 'Amazing service! My nails have never looked better. The staff is professional and the atmosphere is so relaxing.', 'Manicure & Pedicure', TRUE, TRUE),
('Grace K.', 'https://i.pinimg.com/564x/8f/9a/0b/8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c.jpg', 5, 'I love my new braids! The team at TIMO\'S is incredibly skilled and made me feel so comfortable.', 'Braiding', TRUE, TRUE),
('Mary J.', 'https://i.pinimg.com/564x/1f/2a/3b/1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c.jpg', 5, 'Best makeup experience ever! I felt like a queen on my wedding day. Highly recommend!', 'Makeup & Glam Sessions', TRUE, TRUE),
('Joyce W.', 'https://i.pinimg.com/564x/5e/6f/7a/5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b.jpg', 5, 'The facial treatment was absolutely divine. My skin is glowing and I feel so refreshed.', 'Facials & Skincare Treatments', TRUE, FALSE),
('Evelyn N.', 'https://i.pinimg.com/564x/2b/3c/4d/2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e.jpg', 5, 'Professional dreadlock installation. Very clean environment and excellent customer service.', 'Installing Locks (Dreadlocks)', TRUE, FALSE),
('Ruth M.', 'https://i.pinimg.com/564x/7c/8d/9e/7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f.jpg', 5, 'The nail art is incredible! So creative and long-lasting. Will definitely be back.', 'Nail Art & Extensions', TRUE, FALSE);

-- Insert sample newsletter subscribers
INSERT INTO newsletter_subscribers (email, name) VALUES
('sarah.m@email.com', 'Sarah M.'),
('grace.k@email.com', 'Grace K.'),
('mary.j@email.com', 'Mary J.'),
('joyce.w@email.com', 'Joyce W.'),
('evelyn.n@email.com', 'Evelyn N.');

-- Create views for better data management
CREATE VIEW booking_summary AS
SELECT 
    b.id,
    b.booking_ref,
    b.name,
    b.email,
    b.phone,
    b.service,
    b.date,
    b.time,
    b.status,
    b.created_at,
    s.price,
    s.duration
FROM bookings b
LEFT JOIN services s ON b.service = s.name;

CREATE VIEW monthly_booking_stats AS
SELECT 
    DATE_FORMAT(date, '%Y-%m') as month,
    COUNT(*) as total_bookings,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
FROM bookings 
GROUP BY DATE_FORMAT(date, '%Y-%m')
ORDER BY month DESC;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE GetBookingsByDateRange(IN start_date DATE, IN end_date DATE)
BEGIN
    SELECT * FROM booking_summary 
    WHERE date BETWEEN start_date AND end_date
    ORDER BY date, time;
END //

CREATE PROCEDURE GenerateBookingRef(OUT ref_number VARCHAR(20))
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE temp_ref VARCHAR(20);
    DECLARE ref_exists INT DEFAULT 1;
    
    WHILE ref_exists > 0 DO
        SET temp_ref = CONCAT('TMS', YEAR(NOW()), LPAD(FLOOR(RAND() * 10000), 4, '0'));
        SELECT COUNT(*) INTO ref_exists FROM bookings WHERE booking_ref = temp_ref;
    END WHILE;
    
    SET ref_number = temp_ref;
END //

CREATE PROCEDURE UpdateBookingStatus(IN booking_id INT, IN new_status VARCHAR(20))
BEGIN
    UPDATE bookings 
    SET status = new_status, updated_at = NOW() 
    WHERE id = booking_id;
    
    INSERT INTO activity_logs (action, details, ip_address) 
    VALUES ('booking_status_update', CONCAT('Booking ID: ', booking_id, ' Status: ', new_status), '127.0.0.1');
END //

DELIMITER ;

-- Create triggers for automatic operations
DELIMITER //

CREATE TRIGGER booking_activity_log 
AFTER INSERT ON bookings
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (action, details, ip_address) 
    VALUES ('new_booking', CONCAT('New booking created: ', NEW.booking_ref), '127.0.0.1');
END //

CREATE TRIGGER contact_message_log 
AFTER INSERT ON contact_messages
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (action, details, ip_address) 
    VALUES ('new_contact_message', CONCAT('New message from: ', NEW.name, ' (', NEW.email, ')'), '127.0.0.1');
END //

DELIMITER ;

-- Create indexes for better performance
CREATE INDEX idx_bookings_date_status ON bookings(date, status);
CREATE INDEX idx_bookings_created_at ON bookings(created_at);
CREATE INDEX idx_contact_messages_created_at ON contact_messages(created_at);
CREATE INDEX idx_testimonials_approved_featured ON testimonials(is_approved, is_featured);
CREATE INDEX idx_gallery_category_featured ON gallery_images(category, is_featured);

-- Grant permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE ON timos_spa.* TO 'spa_user'@'localhost';
-- GRANT ALL PRIVILEGES ON timos_spa.* TO 'spa_admin'@'localhost';

-- Show table structure for verification
SHOW TABLES;

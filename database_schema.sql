-- TIMO'S Makeup & Nails Spa Database Schema
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
('Pedicure Service', 'https://i.pinimg.com/564x/6d/7e/8f/6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a.jpg', 'Relaxing pedicure treatment', 'nails', FALSE);

-- Insert sample testimonials
INSERT INTO testimonials (client_name, client_image, rating, review, service_received, is_approved, is_featured) VALUES
('Sarah M.', 'https://i.pinimg.com/564x/3c/4d/5e/3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f.jpg', 5, 'Amazing service! My nails have never looked better. The staff is professional and the atmosphere is so relaxing.', 'Manicure & Pedicure', TRUE, TRUE),
('Grace K.', 'https://i.pinimg.com/564x/8f/9a/0b/8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c.jpg', 5, 'I love

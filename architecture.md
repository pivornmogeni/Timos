# TIMO'S Makeup & Nails Spa Website

## Project Structure
```
timos-spa-website/
├── index.html
├── about.html
├── services.html
├── gallery.html
├── booking.html
├── testimonials.html
├── contact.html
├── css/
│   └── style.css
├── js/
│   └── script.js
├── php/
│   ├── connect_db.php
│   ├── submit_booking.php
│   ├── admin_login.php
│   └── admin.php
├── images/
│   └── (image placeholders)
└── database/
    └── database_schema.sql
```

## XAMPP Setup Instructions

1. **Install XAMPP** and start Apache + MySQL services
2. **Database Setup:**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create database: `timos_spa`
   - Import the SQL schema from `database/database_schema.sql`
3. **File Placement:**
   - Place all files in `C:\xampp\htdocs\timos-spa\`
4. **Access Website:**
   - Visit: `http://localhost/timos-spa/`

## Features Implemented
- ✅ Responsive design with mobile-first approach
- ✅ Modern gold/black color scheme
- ✅ PHP backend for form handling
- ✅ MySQL database integration
- ✅ Admin panel for viewing bookings
- ✅ Form validation (client & server-side)
- ✅ Smooth scrolling and animations
- ✅ SEO-friendly structure
- ✅ Cross-browser compatibility

## Pages Overview
1. **Home** - Hero section with booking CTA
2. **About** - Brand story and values
3. **Services** - Detailed service listings
4. **Gallery** - Portfolio showcase
5. **Booking** - Appointment form with database storage
6. **Testimonials** - Client reviews
7. **Contact** - Contact form and location info
8. **Admin** - Secure booking management panel

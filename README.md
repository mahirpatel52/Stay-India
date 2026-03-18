# Hotel Booking Website (PHP + MySQL)

## Stack
- Frontend: HTML, CSS, JavaScript, Bootstrap 5 CDN
- Backend: PHP (procedural `mysqli`)
- Database: MySQL (`hotel_booking`)

## Payment System
- Mock payment gateway added for project demo.
- After booking, user is redirected to a payment page.
- Payment details are stored in `payments` table with:
  - method
  - transaction ID
  - status (`Paid` / `Pending`)
- User can pay from `My Bookings` if payment is pending.
- Admin can view payment status and transaction in bookings list.

## Setup
1. Create/import DB schema from `database/hotel_booking.sql` in phpMyAdmin.
2. Keep project in: `c:\xampp\htdocs\hotel_booking`
3. Start Apache + MySQL in XAMPP.
4. Open `http://localhost/hotel_booking/`

## Default Admin Login
- Email: `mahirfaldu1.yt@gmail.com`
- Password: `mahir123`

You can change these in `config/db.php`.

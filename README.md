# finchskill

A full-fledged educational institute management web portal featuring course programs, dynamic admission inquiries, fee payments, candidate registration with OTP email verification, student portal & ID cards, Razorpay seat reservation fees, and an admin dashboard.

---

## 🚀 Key Features

### Candidate & Student Portal
- **Candidate Registration (`register.php`):**
  - Full Name, Mobile (WhatsApp), Email, 12-digit Aadhaar Card, Passport Photo upload.
  - Password with show/hide eye toggle preview.
  - 6-digit OTP verification sent to candidate's email via PHPMailer (SMTP).
  - Unique Student ID auto-generation (`FS-YYYY-XXXX`).
  - Automated Welcome Email with credentials & Student ID.
- **Seat Reservation & Fees (`seat-reservation.php`):**
  - Integrated with **Razorpay** payment gateway for online seat reservation fees.
  - "Skip & Pay Later" option so registration flow is never blocked.
  - Automated payment verification (`verify-payment.php`) and email receipt dispatch.
- **Student Dashboard (`student-profile.php`):**
  - Candidate profile card, seat confirmation badge, fee transaction details.
  - Printable Provisional Registration & ID slip (`window.print()`).
- **Student Authentication (`student-login.php`, `forgot-password.php`, `student-logout.php`):**
  - Login via Student ID or Email + Password (with eye toggle).
  - Password recovery with 6-digit email OTP.

### Website Front-End
- Responsive modern UI matching brand orange (`#fe7c03`) and navy (`#0e1e2e`).
- Course catalog & dynamic course detail pages.
- Dynamic header: Displays candidate passport photo avatar + dropdown when logged in; Login & Register buttons when logged out.
- Admission form, fee submission form, contact inquiries.

### Admin Dashboard (`/admin`)
- **Dashboard KPI Cards:** Admissions, Fees, Contact Inquiries, Active Courses, Registered Candidates.
- **Manage Candidates (`admin/students.php`):**
  - Search & filter by student ID, name, email, phone, Aadhaar, seat status, and payment status.
  - Profile modal, 1-click WhatsApp link, seat confirmation toggle, payment toggle, delete.
- **Course & Category Management (`admin/courses.php`, `admin/course-add.php`).
- **Payment Gateway Setup (`admin/payment-settings.php`):**
  - Configure Razorpay Test & Live API keys, mode switch, and seat booking fee amount.
- **Email & SMTP Settings (`admin/smtp-settings.php`):**
  - Configure custom Webmail or Gmail SMTP (Host, Port, Security, Username, Password with eye toggle).
  - Quick 1-click presets for Gmail, Hostinger, and cPanel Webmail.
  - Live connection testing tool.
- **Brand & Website Settings (`admin/settings.php`).**

---

## 🛠️ Database Setup (Localhost / New PC)

1. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Create a new database named: **`finchskills_db`**.
3. Click the **Import** tab.
4. Choose the bundled **`finchskills_db.sql`** file located in the root directory.
5. Click **Import**.

### Default Admin Credentials
- **URL:** `http://localhost:8000/admin/login.php` (or `http://localhost/admin/login.php`)
- **Username:** `admin`
- **Password:** `admin123`

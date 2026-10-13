<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    if (ini_get('session.cookie_path') !== '/') {
        @ini_set('session.cookie_path', '/');
    }
    @session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail-helper.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$payment_id = trim($data['razorpay_payment_id'] ?? '');
$name = trim($data['name'] ?? '');
$email = strtolower(trim($data['email'] ?? ''));
$phone = preg_replace('/[^0-9]/', '', trim($data['phone'] ?? ''));
$aadhaar = preg_replace('/[^0-9]/', '', trim($data['aadhaar'] ?? ''));
$password = $data['password'] ?? '';
$course_id = (int)($data['course_id'] ?? 0);
$course_title = trim($data['course_title'] ?? '');
$photo_path = trim($data['photo'] ?? ($_SESSION['reg_uploaded_photo'] ?? ''));

// Validate Payment ID
if (empty($payment_id)) {
    echo json_encode(['success' => false, 'message' => 'Payment ID is missing or invalid.']);
    exit;
}

// Validate Email Verification
$is_verified_in_session = !empty($_SESSION['email_verified']) && $_SESSION['email_verified'] === true && !empty($_SESSION['verified_email']) && $_SESSION['verified_email'] === $email;
if (!$is_verified_in_session) {
    // If not in session, check if existing verified student
    $chk_ver = $pdo->prepare("SELECT id, is_verified FROM students WHERE email = ? LIMIT 1");
    $chk_ver->execute([$email]);
    $existing_student = $chk_ver->fetch();
    if (!$existing_student || (int)$existing_student['is_verified'] !== 1) {
        echo json_encode(['success' => false, 'message' => 'Email verification required before seat reservation.']);
        exit;
    }
}

// Basic Validations
if (empty($name) || strlen($phone) !== 10 || strlen($aadhaar) !== 12 || empty($course_id)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields correctly.']);
    exit;
}

// Fetch Course Title from DB if not passed
if (empty($course_title) && $course_id > 0) {
    $c_stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ? LIMIT 1");
    $c_stmt->execute([$course_id]);
    $course_title = $c_stmt->fetchColumn() ?: 'Selected Course';
}

// Fetch dynamic fee amount from site_settings
$fee_stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'seat_booking_fee' LIMIT 1");
$admin_fee = (float)($fee_stmt->fetchColumn() ?: 999.00);
$amount = isset($data['amount']) && (float)$data['amount'] > 0 ? (float)$data['amount'] : $admin_fee;

// Determine 7-day seat validity
$val_stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'seat_validity_days' LIMIT 1");
$validity_days = (int)($val_stmt->fetchColumn() ?: 7);
if ($validity_days <= 0) $validity_days = 7;
$valid_until = date('Y-m-d H:i:s', strtotime("+{$validity_days} days"));

// Hash password or generate fallback
if (!empty($password) && strlen($password) >= 6) {
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
} else {
    $hashed_password = password_hash(substr(md5(uniqid()), 0, 8), PASSWORD_BCRYPT);
}

try {
    // Check if student already exists
    $s_chk = $pdo->prepare("SELECT * FROM students WHERE email = ? LIMIT 1");
    $s_chk->execute([$email]);
    $student = $s_chk->fetch();

    if ($student) {
        $student_db_id = (int)$student['id'];
        $student_code = $student['student_id'];
        $registration_id = !empty($student['registration_id']) ? $student['registration_id'] : '';
        if (empty($registration_id)) {
            $registration_id = 'REG-' . date('Y') . '-' . mt_rand(1000, 9999);
        }

        // If existing record was unverified or updating
        $upd = $pdo->prepare("UPDATE students SET 
            registration_id = ?,
            name = ?, 
            phone = ?, 
            aadhaar = ?, 
            photo = IFNULL(NULLIF(?, ''), photo), 
            password = IFNULL(NULLIF(?, ''), password), 
            is_verified = 1,
            seat_status = 'Confirmed',
            booked_course_id = ?,
            booked_course = ?,
            payment_status = 'Paid',
            payment_id = ?,
            payment_amount = ?,
            payment_date = NOW(),
            seat_valid_until = ?,
            seat_reminder_day2_at = NULL,
            seat_reminder_day4_at = NULL,
            seat_reminder_final_at = NULL,
            seat_cancelled_at = NULL
            WHERE id = ?");
        $upd->execute([
            $registration_id,
            $name,
            $phone,
            $aadhaar,
            $photo_path,
            $hashed_password,
            $course_id,
            $course_title,
            $payment_id,
            $amount,
            $valid_until,
            $student_db_id
        ]);
    } else {
        // Generate Unique Student ID: FS-YYYY-XXXX
        do {
            $student_code = 'FS-' . date('Y') . '-' . mt_rand(1000, 9999);
            $id_chk = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
            $id_chk->execute([$student_code]);
        } while ($id_chk->fetch());

        // Generate Unique Registration ID: REG-YYYY-XXXX
        do {
            $registration_id = 'REG-' . date('Y') . '-' . mt_rand(1000, 9999);
            $reg_chk = $pdo->prepare("SELECT id FROM students WHERE registration_id = ?");
            $reg_chk->execute([$registration_id]);
        } while ($reg_chk->fetch());

        // Insert new confirmed student record
        $ins = $pdo->prepare("INSERT INTO students 
            (student_id, registration_id, name, email, phone, aadhaar, photo, password, is_verified, 
             seat_status, booked_course_id, booked_course, payment_status, payment_id, 
             payment_amount, payment_date, seat_valid_until, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'Confirmed', ?, ?, 'Paid', ?, ?, NOW(), ?, NOW())");
        $ins->execute([
            $student_code,
            $registration_id,
            $name,
            $email,
            $phone,
            $aadhaar,
            $photo_path,
            $hashed_password,
            $course_id,
            $course_title,
            $payment_id,
            $amount,
            $valid_until
        ]);
        $student_db_id = (int)$pdo->lastInsertId();
    }

    // Insert into fee_submissions for Admin consistency
    $ref_code = 'REC-' . strtoupper(substr(md5($payment_id), 0, 8));
    $fee_ins = $pdo->prepare("INSERT INTO fee_submissions 
        (name, contact, email, fee_type, purpose, course, receipt, status, is_read, created_at) 
        VALUES (?, ?, ?, 'Registration Fee', 'Seat Reservation (Razorpay Online)', ?, ?, 'Verified', 0, NOW())");
    $fee_ins->execute([
        $name,
        $phone,
        $email,
        $course_title,
        'Razorpay ID: ' . $payment_id . ' | ' . $ref_code
    ]);

    // Fetch refreshed student data
    $st_ref = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $st_ref->execute([$student_db_id]);
    $student_record = $st_ref->fetch();

    // Send Detailed Confirmation Email to Candidate
    sendPaymentReceiptEmail($email, $name, $student_code, $payment_id, $amount, $course_title, $valid_until, $registration_id);

    // Send Alert to Admin
    sendAdminSeatBookingAlertEmail($student_record, $course_title, $payment_id, $amount, $valid_until);

    // Auto-login into session
    $_SESSION['student_logged_in'] = true;
    $_SESSION['student_db_id'] = $student_db_id;
    $_SESSION['student_id'] = $student_code;
    $_SESSION['student_registration_id'] = $registration_id;
    $_SESSION['student_name'] = $name;
    $_SESSION['student_email'] = $email;
    $_SESSION['student_photo'] = $student_record['photo'] ?? $photo_path;

    // Save details in session for the Success Page
    $_SESSION['last_confirmation'] = [
        'student_id'      => $student_code,
        'registration_id' => $registration_id,
        'student_db_id'   => $student_db_id,
        'name'            => $name,
        'email'           => $email,
        'phone'           => $phone,
        'aadhaar'         => $aadhaar,
        'photo'           => $student_record['photo'] ?? $photo_path,
        'course_id'       => $course_id,
        'course_title'    => $course_title,
        'amount'          => $amount,
        'payment_id'      => $payment_id,
        'ref_code'        => $ref_code,
        'valid_until'     => $valid_until,
        'timestamp'       => date('Y-m-d H:i:s')
    ];

    // Clear registration temporary session data
    unset($_SESSION['reg_otp']);
    unset($_SESSION['reg_email']);
    unset($_SESSION['email_verified']);
    unset($_SESSION['verified_email']);
    unset($_SESSION['reg_uploaded_photo']);

    echo json_encode([
        'success' => true,
        'message' => 'Seat reserved and registration completed successfully!',
        'redirect' => 'registration-success.php?ref=' . urlencode($ref_code)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

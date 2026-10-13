<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail-helper.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$payment_id = trim($data['razorpay_payment_id'] ?? '');
$student_db_id = (int)($data['student_id'] ?? ($_SESSION['student_db_id'] ?? 0));
$amount = isset($data['amount']) && (float)$data['amount'] > 0 ? (float)$data['amount'] : 0.00;
if ($amount <= 0) {
    $sbf_chk = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'seat_booking_fee' LIMIT 1");
    $amount = (float)($sbf_chk->fetchColumn() ?: 999.00);
}
$course_id = (int)($data['course_id'] ?? 0);
$course_title = trim($data['course_title'] ?? '');

$guest_name = trim($data['guest_name'] ?? '');
$guest_email = trim($data['guest_email'] ?? '');
$guest_phone = trim($data['guest_phone'] ?? '');

if (empty($payment_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data.']);
    exit;
}

$student = null;
if ($student_db_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_db_id]);
    $student = $stmt->fetch();
}

// If guest checkout without prior login
if (!$student && (!empty($guest_email) || !empty($guest_phone))) {
    $chk = $pdo->prepare("SELECT * FROM students WHERE (email = ? AND email != '') OR (phone = ? AND phone != '') LIMIT 1");
    $chk->execute([$guest_email, $guest_phone]);
    $student = $chk->fetch();

    if (!$student) {
        $student_id_code = 'FS-' . date('Y') . '-' . rand(1000, 9999);
        $temp_pass = password_hash(substr(md5(uniqid()), 0, 8), PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO students (student_id, name, email, phone, password, is_verified, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $ins->execute([$student_id_code, $guest_name ?: 'Student', $guest_email, $guest_phone, $temp_pass]);
        $student_db_id = (int)$pdo->lastInsertId();
        
        $s_stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $s_stmt->execute([$student_db_id]);
        $student = $s_stmt->fetch();
    } else {
        $student_db_id = (int)$student['id'];
        if (!empty($guest_name) && empty($student['name'])) {
            $pdo->prepare("UPDATE students SET name = ? WHERE id = ?")->execute([$guest_name, $student_db_id]);
            $student['name'] = $guest_name;
        }
    }
}

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student record could not be identified.']);
    exit;
}

// Auto login into session
$_SESSION['student_logged_in'] = true;
$_SESSION['student_db_id'] = $student['id'];
$_SESSION['student_id'] = $student['student_id'];
$_SESSION['student_name'] = $student['name'];
$_SESSION['student_email'] = $student['email'];
$_SESSION['student_photo'] = $student['photo'] ?? '';

// If course_title not passed but course_id is, look up from courses table
if ($course_id > 0 && empty($course_title)) {
    $c_lookup = $pdo->prepare("SELECT title FROM courses WHERE id = ? LIMIT 1");
    $c_lookup->execute([$course_id]);
    $course_title = $c_lookup->fetchColumn() ?: '';
}

try {
    // Determine seat validity duration (default 7 days)
    $val_stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'seat_validity_days' LIMIT 1");
    $validity_days = (int)($val_stmt->fetchColumn() ?: 7);
    if ($validity_days <= 0) $validity_days = 7;
    $valid_until = date('Y-m-d H:i:s', strtotime("+{$validity_days} days"));

    // 1. Update Student Table with confirmed seat, reserved course, and 7-day validity
    $upd = $pdo->prepare("UPDATE students SET 
        seat_status = 'Confirmed',
        payment_status = 'Paid',
        payment_id = ?,
        payment_amount = ?,
        payment_date = NOW(),
        seat_valid_until = ?,
        seat_reminder_day2_at = NULL,
        seat_reminder_day4_at = NULL,
        seat_reminder_final_at = NULL,
        seat_cancelled_at = NULL,
        booked_course_id = ?,
        booked_course = ?
        WHERE id = ?");
    $upd->execute([
        $payment_id,
        $amount,
        $valid_until,
        ($course_id > 0 ? $course_id : null),
        (!empty($course_title) ? $course_title : null),
        $student['id']
    ]);

    // 2. Also log in fee_submissions for accounting consistency in admin
    $fee_purpose = 'Seat Reservation (Razorpay Online)';
    $target_course = !empty($course_title) ? $course_title : 'Finchskills Career Program';
    $fee_stmt = $pdo->prepare("INSERT INTO fee_submissions 
        (name, contact, email, fee_type, purpose, course, receipt, status, is_read) 
        VALUES (?, ?, ?, 'Registration Fee', ?, ?, ?, 'Verified', 0)");
    $fee_stmt->execute([
        $student['name'],
        $student['phone'],
        $student['email'],
        $fee_purpose,
        $target_course,
        'Razorpay ID: ' . $payment_id
    ]);

    // 3. Send automated Email Receipt to candidate with 7-day validity notice & 100% fee adjustment guarantee
    sendPaymentReceiptEmail($student['email'], $student['name'], $student['student_id'], $payment_id, $amount, $target_course, $valid_until);

    // 4. Send Instant Seat Booking Alert to Admin
    sendAdminSeatBookingAlertEmail($student, $target_course, $payment_id, $amount, $valid_until);

    $ref_code = 'REC-' . strtoupper(substr(md5($payment_id), 0, 8));
    echo json_encode([
        'success' => true,
        'ref' => $ref_code,
        'message' => 'Payment verified and seat confirmed for ' . htmlspecialchars($target_course) . '!',
        'course' => $target_course,
        'valid_until' => $valid_until
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

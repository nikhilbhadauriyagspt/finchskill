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

$action = trim($data['action'] ?? '');
$email = strtolower(trim($data['email'] ?? ''));
$name = trim($data['name'] ?? 'Student');

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Action required.']);
    exit;
}

// -------------------------------------------------------------
// ACTION: SEND OTP
// -------------------------------------------------------------
if ($action === 'send_otp' || $action === 'resend_otp') {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }

    // Check if email already verified and registered
    $chk = $pdo->prepare("SELECT id, is_verified FROM students WHERE email = ? LIMIT 1");
    $chk->execute([$email]);
    $existing = $chk->fetch();

    if ($existing && (int)$existing['is_verified'] === 1) {
        echo json_encode([
            'success' => false,
            'message' => 'An account with this email already exists. Please log in to your account.'
        ]);
        exit;
    }

    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(100000, 999999));
    $_SESSION['reg_otp'] = $otp;
    $_SESSION['reg_email'] = $email;
    $_SESSION['reg_otp_expiry'] = time() + 900; // 15 mins validity
    $_SESSION['email_verified'] = false;

    // Send OTP via email
    $mailSent = sendRegistrationOtp($email, $name, $otp);

    if ($mailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'A 6-digit OTP has been sent to ' . htmlspecialchars($email) . '. Please check your inbox.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send OTP email. Please verify your email address or try again.'
        ]);
    }
    exit;
}

// -------------------------------------------------------------
// ACTION: VERIFY OTP
// -------------------------------------------------------------
if ($action === 'verify_otp') {
    $entered_otp = trim($data['otp'] ?? '');

    if (empty($entered_otp) || strlen($entered_otp) !== 6) {
        echo json_encode(['success' => false, 'message' => 'Please enter the 6-digit OTP received on your email.']);
        exit;
    }

    $stored_otp = $_SESSION['reg_otp'] ?? '';
    $stored_email = $_SESSION['reg_email'] ?? '';
    $expiry = (int)($_SESSION['reg_otp_expiry'] ?? 0);

    if (empty($stored_otp) || empty($stored_email) || $stored_email !== $email) {
        echo json_encode(['success' => false, 'message' => 'Please request an OTP for this email first.']);
        exit;
    }

    if (time() > $expiry) {
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please click Resend OTP.']);
        exit;
    }

    if ($entered_otp !== $stored_otp) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please check and try again.']);
        exit;
    }

    // OTP matched successfully!
    $_SESSION['email_verified'] = true;
    $_SESSION['verified_email'] = $email;
    unset($_SESSION['reg_otp']);

    echo json_encode([
        'success' => true,
        'message' => 'Email verified successfully!'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);

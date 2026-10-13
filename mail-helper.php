<?php
// Set Indian Standard Time (IST) default timezone
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Kolkata');
}

/**
 * Finchskills Institute Central Email Dispatcher (PHPMailer + Dynamic SMTP)
 * Reusable across the entire website (OTP, Admissions, Fees, Contact, Notifications)
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    if (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    }
}

/**
 * Fetch dynamic SMTP settings from database site_settings table
 */
function getSmtpConfig() {
    global $pdo;
    if (!isset($pdo) || $pdo === null) {
        require_once __DIR__ . '/db.php';
    }

    // Default Fallback Settings
    $settings = [
        'smtp_enabled'    => '1',
        'smtp_host'       => 'smtp.gmail.com',
        'smtp_port'       => '587',
        'smtp_secure'     => 'tls',
        'smtp_username'   => 'finchskillsinstitute@gmail.com',
        'smtp_password'   => 'kzsiuskuxqjjaglk',
        'smtp_from_name'  => 'Finchskills Institute',
        'smtp_from_email' => 'finchskillsinstitute@gmail.com',
        'smtp_reply_to'   => 'hello@finchskills.com'
    ];

    try {
        if ($pdo) {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'smtp_%'");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    } catch (Exception $e) {
        // Fallback to array defaults
    }

    return $settings;
}

/**
 * Helper to dynamically determine the absolute site URL with correct protocol (http/https) and host.
 * Guarantees that emails sent to candidates and admin always have fully-qualified clickable links.
 */
function getSiteBaseUrl() {
    if (!empty($_SERVER['HTTP_HOST'])) {
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                    (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
                    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                    (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
        $protocol = $is_https ? 'https://' : 'http://';
        
        $subfolder = '';
        if (!empty($_SERVER['SCRIPT_NAME'])) {
            $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            $dir = preg_replace('#/(admin|api|uploads)$#i', '', $dir);
            if ($dir !== '/' && $dir !== '.' && !empty($dir)) {
                $subfolder = rtrim($dir, '/');
            }
        }
        
        return $protocol . $_SERVER['HTTP_HOST'] . $subfolder;
    }
    
    return 'https://finchskills.com';
}

/**
 * Dynamic Helpline Phone Number Helper for Email Footers
 */
function getMailHelpline() {
    global $pdo;
    if (!isset($pdo) || $pdo === null) {
        @require_once __DIR__ . '/db.php';
    }
    if (function_exists('getSetting')) {
        return getSetting('phone_1', '+91 96503 86711');
    }
    return '+91 96503 86711';
}

/**
 * Core Universal Email Dispatcher
 * Can be used anywhere on the website
 * 
 * @param string|array $to Single email or array of emails
 * @param string $subject Email subject line
 * @param string $htmlMessage Full HTML email content
 * @param array $options Optional: ['reply_to' => '', 'reply_name' => '', 'attachments' => [], 'throw_error' => false]
 * @return array ['success' => bool, 'message' => string]
 */
function sendFinchMail($to, $subject, $htmlMessage, $options = []) {
    $cfg = getSmtpConfig();

    // 1. Audit / Debug Log locally in uploads/email_logs.txt
    $log_dir = __DIR__ . '/uploads';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0777, true);
    }
    $to_str = is_array($to) ? implode(', ', $to) : $to;
    $log_entry = "[" . date('Y-m-d H:i:s') . "] TO: {$to_str} | SUBJECT: {$subject}\n" . strip_tags($htmlMessage) . "\n----------------------------------------\n";
    @file_put_contents($log_dir . '/email_logs.txt', $log_entry, FILE_APPEND);

    // 2. Real Send via PHPMailer with dynamic SMTP
    $smtp_enabled = ($cfg['smtp_enabled'] ?? '1') === '1';
    if ($smtp_enabled && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = !empty($cfg['smtp_host']) ? trim($cfg['smtp_host']) : 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = trim($cfg['smtp_username'] ?? '');
            $mail->Password   = trim($cfg['smtp_password'] ?? '');
            $mail->Port       = (int)(!empty($cfg['smtp_port']) ? $cfg['smtp_port'] : 587);

            // Security Encryption
            $sec = strtolower(trim($cfg['smtp_secure'] ?? 'tls'));
            if ($sec === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($sec === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPAutoTLS = false;
                $mail->SMTPSecure  = false;
            }

            $mail->CharSet = 'UTF-8';
            $mail->Timeout = 15;

            // Allow self-signed certificates for custom webmail domains
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];

            // From header
            $fromEmail = !empty($cfg['smtp_from_email']) ? trim($cfg['smtp_from_email']) : $cfg['smtp_username'];
            $fromName  = !empty($cfg['smtp_from_name']) ? trim($cfg['smtp_from_name']) : 'Finchskills Institute';
            $mail->setFrom($fromEmail, $fromName);

            // Recipients
            if (is_array($to)) {
                foreach ($to as $recipient) {
                    if (!empty($recipient)) $mail->addAddress(trim($recipient));
                }
            } else {
                $mail->addAddress(trim($to));
            }

            // Reply-To
            $replyTo = $options['reply_to'] ?? ($cfg['smtp_reply_to'] ?: $fromEmail);
            $replyName = $options['reply_name'] ?? $fromName;
            if (!empty($replyTo)) {
                $mail->addReplyTo($replyTo, $replyName);
            }

            // CC / BCC
            if (!empty($options['cc'])) {
                $mail->addCC($options['cc']);
            }
            if (!empty($options['bcc'])) {
                $mail->addBCC($options['bcc']);
            }

            // Attachments
            if (!empty($options['attachments']) && is_array($options['attachments'])) {
                foreach ($options['attachments'] as $att) {
                    if (is_array($att) && !empty($att['path']) && file_exists($att['path'])) {
                        $mail->addAttachment($att['path'], $att['name'] ?? '');
                    } elseif (is_string($att) && file_exists($att)) {
                        $mail->addAttachment($att);
                    }
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlMessage;

            $mail->send();
            return ['success' => true, 'message' => 'Email sent successfully via PHPMailer.'];
        } catch (Exception $e) {
            $err = $mail->ErrorInfo ?: $e->getMessage();
            @file_put_contents($log_dir . '/email_logs.txt', "[SMTP Error] " . $err . "\n", FILE_APPEND);

            if (!empty($options['throw_error'])) {
                return ['success' => false, 'message' => $err];
            }
        }
    }

    // 3. Fallback to PHP native mail() if PHPMailer is disabled or fails
    $fromEmail = !empty($cfg['smtp_from_email']) ? $cfg['smtp_from_email'] : 'no-reply@finchskills.com';
    $fromName  = !empty($cfg['smtp_from_name']) ? $cfg['smtp_from_name'] : 'Finchskills Institute';
    $headers = [
        "MIME-Version: 1.0",
        "Content-type: text/html; charset=UTF-8",
        "From: {$fromName} <{$fromEmail}>",
        "Reply-To: " . ($cfg['smtp_reply_to'] ?? $fromEmail),
        "X-Mailer: PHP/" . phpversion()
    ];
    @mail($to_str, $subject, $htmlMessage, implode("\r\n", $headers));
    return ['success' => true, 'message' => 'Email dispatched via fallback mail()'];
}

/**
 * 1. Send Registration Verification OTP
 */
function sendRegistrationOtp($email, $name, $otp) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['debug_latest_otp'])) unset($_SESSION['debug_latest_otp']);

    $subject = "Your Verification OTP - Finchskills Institute";
    $html = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><style>body{font-family:Inter,Arial,sans-serif;background:#f8fafc;margin:0;padding:20px;color:#334155;}.card{max-width:520px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;}.header{background:#0e1e2e;padding:24px;text-align:center;color:#ffffff;}.body{padding:28px;}.otp-box{background:#fff7ed;border:1.5px dashed #fe7c03;border-radius:8px;padding:16px;text-align:center;font-size:30px;font-weight:800;letter-spacing:6px;color:#fe7c03;margin:20px 0;}.footer{background:#f8fafc;padding:16px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;}</style></head>
    <body>
        <div class="card">
            <div class="header">
                <h2 style="margin:0;font-size:20px;letter-spacing:0.5px;color:#ffffff;">FINCHSKILLS INSTITUTE</h2>
                <p style="margin:4px 0 0;font-size:13px;color:#fe7c03;font-weight:600;">Student Email Verification</p>
            </div>
            <div class="body">
                <p style="font-size:15px;margin-top:0;">Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <p style="font-size:14px;line-height:1.6;color:#475569;">Thank you for registering with Finchskills Institute. Please enter the following 6-digit One-Time Password (OTP) on the verification screen:</p>
                <div class="otp-box">' . htmlspecialchars($otp) . '</div>
                <p style="font-size:13px;color:#64748b;line-height:1.5;">This OTP is valid for <strong>15 minutes</strong>. For your security, please do not share this code with anyone.</p>
            </div>
            <div class="footer">
                &copy; ' . date('Y') . ' Finchskills Institute. All rights reserved.
            </div>
        </div>
    </body>
    </html>';

    return sendFinchMail($email, $subject, $html);
}

/**
 * 2. Send Welcome & Unique Student ID Email
 */
function sendWelcomeEmail($email, $name, $studentId) {
    $subject = "Welcome to Finchskills! Your Student ID: {$studentId}";
    $base_url = getSiteBaseUrl();
    $login_url = $base_url . "/student-login.php";
    $profile_url = $base_url . "/student-profile.php";

    $html = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><style>body{font-family:Inter,Arial,sans-serif;background:#f8fafc;margin:0;padding:20px;color:#334155;}.card{max-width:540px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;}.header{background:#0e1e2e;padding:26px;text-align:center;color:#ffffff;}.body{padding:30px;}.id-card{background:#f8fafc;border:1.5px solid #fe7c03;border-radius:10px;padding:18px;margin:20px 0;}.row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e2e8f0;font-size:13.5px;}.row:last-child{border-bottom:none;}.btn{display:inline-block;background:#fe7c03;color:#ffffff !important;text-decoration:none;padding:12px 26px;border-radius:6px;font-weight:700;font-size:14px;margin-top:10px;}.footer{background:#f8fafc;padding:16px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;}</style></head>
    <body>
        <div class="card">
            <div class="header">
                <h2 style="margin:0;font-size:22px;letter-spacing:0.5px;color:#ffffff;">FINCHSKILLS INSTITUTE</h2>
                <p style="margin:4px 0 0;font-size:13px;color:#fe7c03;font-weight:600;">Registration Confirmed</p>
            </div>
            <div class="body">
                <h3 style="margin-top:0;color:#0f172a;">Congratulations, ' . htmlspecialchars($name) . '!</h3>
                <p style="font-size:14px;line-height:1.6;color:#475569;">Your student registration with Finchskills Institute is officially verified and confirmed. Here are your credentials:</p>
                <div class="id-card">
                    <div class="row">
                        <span style="color:#64748b;">Unique Student ID:</span>
                        <strong style="color:#fe7c03;font-size:16px;letter-spacing:1px;">' . htmlspecialchars($studentId) . '</strong>
                    </div>
                    <div class="row">
                        <span style="color:#64748b;">Full Name:</span>
                        <strong style="color:#0f172a;">' . htmlspecialchars($name) . '</strong>
                    </div>
                    <div class="row">
                        <span style="color:#64748b;">Registered Email:</span>
                        <strong style="color:#0f172a;">' . htmlspecialchars($email) . '</strong>
                    </div>
                </div>
                <p style="font-size:13.5px;color:#475569;line-height:1.5;">You can use your Student ID or Email along with your chosen password to log in to your Student Portal anytime.</p>
                <div style="text-align:center;margin-top:24px;">
                    <a href="' . $login_url . '" class="btn">Log In to Student Portal &rarr;</a>
                </div>
                <p style="text-align:center;font-size:12px;color:#64748b;margin-top:14px;">
                    Dashboard URL: <a href="' . $profile_url . '" style="color:#fe7c03;">' . $profile_url . '</a>
                </p>
            </div>
            <div class="footer">
                Need help? Contact helpline at ' . getMailHelpline() . ' | &copy; ' . date('Y') . ' Finchskills Institute.
            </div>
        </div>
    </body>
    </html>';

    return sendFinchMail($email, $subject, $html);
}

/**
 * 3. Send Forgot Password Reset OTP
 */
function sendForgotPasswordOtp($email, $name, $otp) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['debug_latest_otp'])) unset($_SESSION['debug_latest_otp']);

    $subject = "Password Reset OTP - Finchskills Institute";
    $html = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><style>body{font-family:Inter,Arial,sans-serif;background:#f8fafc;margin:0;padding:20px;color:#334155;}.card{max-width:520px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;}.header{background:#0e1e2e;padding:24px;text-align:center;color:#ffffff;}.body{padding:28px;}.otp-box{background:#fef2f2;border:1.5px dashed #ef4444;border-radius:8px;padding:16px;text-align:center;font-size:30px;font-weight:800;letter-spacing:6px;color:#ef4444;margin:20px 0;}.footer{background:#f8fafc;padding:16px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;}</style></head>
    <body>
        <div class="card">
            <div class="header">
                <h2 style="margin:0;font-size:20px;color:#ffffff;">FINCHSKILLS INSTITUTE</h2>
                <p style="margin:4px 0 0;font-size:13px;color:#f87171;font-weight:600;">Password Reset Request</p>
            </div>
            <div class="body">
                <p style="font-size:15px;margin-top:0;">Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <p style="font-size:14px;line-height:1.6;color:#475569;">We received a request to reset your student portal account password. Use this 6-digit OTP code:</p>
                <div class="otp-box">' . htmlspecialchars($otp) . '</div>
                <p style="font-size:13px;color:#64748b;line-height:1.5;">This OTP is valid for <strong>15 minutes</strong>. If you did not initiate this request, please ignore this email.</p>
            </div>
            <div class="footer">
                &copy; ' . date('Y') . ' Finchskills Institute. All rights reserved.
            </div>
        </div>
    </body>
    </html>';

    return sendFinchMail($email, $subject, $html);
}

/**
 * Alias for sendForgotPasswordOtp
 */
function sendPasswordResetOtp($email, $name, $otp) {
    return sendForgotPasswordOtp($email, $name, $otp);
}

/**
 * 4. Send Payment / Seat Reservation Confirmation Receipt
 */
function sendPaymentReceiptEmail($email, $name, $studentId, $paymentId, $amount, $courseTitle = '', $validUntil = null, $registrationId = '') {
    global $pdo;
    if (!isset($pdo) || $pdo === null) {
        require_once __DIR__ . '/db.php';
    }

    if (empty($registrationId) && !empty($studentId) && $pdo) {
        try {
            $reg_stmt = $pdo->prepare("SELECT registration_id FROM students WHERE student_id = ? OR email = ? LIMIT 1");
            $reg_stmt->execute([$studentId, $email]);
            $registrationId = $reg_stmt->fetchColumn() ?: '';
        } catch (\Exception $e) {}
    }

    $subject = "Registration Confirmed" . (!empty($courseTitle) ? " - {$courseTitle}" : "") . " - Finchskills Institute";
    $formatted_amount = "₹" . number_format((float)$amount, 2);
    $base_url = getSiteBaseUrl();
    $receipt_url = $base_url . "/receipt.php?pay_id=" . urlencode($paymentId);
    $profile_url = $base_url . "/student-profile.php";
    $admission_url = $base_url . "/form-submission.php" . (!empty($courseTitle) ? ("?course=" . urlencode($courseTitle)) : "");

    // Dynamically look up Course Fee from DB so email receipt reflects actual Total Course Fee & Remaining Balance
    $total_course_fee = 0.00;
    if (!empty($courseTitle) && $pdo) {
        try {
            $c_stmt = $pdo->prepare("SELECT fee FROM courses WHERE title = ? LIMIT 1");
            $c_stmt->execute([$courseTitle]);
            $c_fee = $c_stmt->fetchColumn();
            if ($c_fee) {
                $raw_fee = preg_replace('/[^0-9]/', '', $c_fee);
                if (!empty($raw_fee)) {
                    $total_course_fee = (float)$raw_fee;
                }
            }
        } catch (\Exception $e) {}
    }
    $pending_balance = max(0, $total_course_fee - (float)$amount);

    if (empty($validUntil)) {
        $validUntil = date('d M Y, h:i A', strtotime('+7 days'));
    } else {
        $validUntil = date('d M Y, h:i A', strtotime($validUntil));
    }

    $html = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><style>body{font-family:Inter,Arial,sans-serif;background:#f8fafc;margin:0;padding:20px;color:#334155;}.card{max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;}.header{background:#0e1e2e;padding:26px;text-align:center;color:#ffffff;border-bottom:3px solid #fe7c03;}.body{padding:28px 24px;}.receipt-box{background:#f0fdf4;border:1.5px solid #22c55e;border-radius:10px;padding:18px 20px;margin:18px 0;}.row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid #dcfce7;font-size:13.5px;}.row:last-child{border-bottom:none;}.badge{background:#22c55e;color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}.footer{background:#f8fafc;padding:16px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;}.info-alert{background:#fff8f3;border:1.5px solid #fed7aa;border-radius:8px;padding:14px 16px;font-size:13px;color:#9a3412;margin:18px 0;line-height:1.55;}.validity-box{background:#eff6ff;border:1.5px solid #93c5fd;border-radius:8px;padding:14px 16px;margin:18px 0;text-align:center;}</style></head>
    <body>
        <div class="card">
            <div class="header">
                <h2 style="margin:0;font-size:22px;color:#ffffff;">FINCHSKILLS INSTITUTE</h2>
                <p style="margin:4px 0 0;font-size:13px;color:#fe7c03;font-weight:700;">Seat Reservation & Payment Receipt</p>
            </div>
            <div class="body">
                <h3 style="margin-top:0;color:#0f172a;">Congratulations, ' . htmlspecialchars($name) . '!</h3>
                <p style="font-size:14px;line-height:1.6;color:#475569;">Your registration for the <strong>' . htmlspecialchars($courseTitle ?: 'Career Program') . '</strong> has been successfully completed, and your registration fee has been submitted successfully. Please find your registration details below:</p>
                
                <div class="receipt-box">
                    <div class="row">
                        <span style="color:#166534;">Payment Status:</span>
                        <span class="badge">CONFIRMED</span>
                    </div>' .
                    (!empty($registrationId) ? '
                    <div class="row">
                        <span style="color:#166534;">Registration ID:</span>
                        <strong style="color:#0f172a;font-family:monospace;letter-spacing:0.5px;">' . htmlspecialchars($registrationId) . '</strong>
                    </div>' : '') . '
                    <div class="row">
                        <span style="color:#166534;">Student ID:</span>
                        <strong style="color:#0f172a;font-family:monospace;">' . htmlspecialchars($studentId) . '</strong>
                    </div>' .
                    (!empty($courseTitle) ? '
                    <div class="row">
                        <span style="color:#166534;">Reserved Course:</span>
                        <strong style="color:#0f172a;">' . htmlspecialchars($courseTitle) . '</strong>
                    </div>' : '') . 
                    ($total_course_fee > 0 ? '
                    <div class="row">
                        <span style="color:#166534;">Total Course Tuition Fee:</span>
                        <strong style="color:#0f172a;">₹' . number_format($total_course_fee, 2) . '</strong>
                    </div>' : '') . '
                    <div class="row" style="background:#dcfce7;margin:0 -20px;padding:8px 20px;">
                        <span style="color:#166534;font-weight:700;">Seat Reservation Fee Paid:</span>
                        <strong style="color:#166534;font-size:16px;">' . htmlspecialchars($formatted_amount) . '</strong>
                    </div>' .
                    ($total_course_fee > 0 ? '
                    <div class="row">
                        <span style="color:#166534;">Remaining Course Balance:</span>
                        <strong style="color:#b91c1c;font-size:15px;">₹' . number_format($pending_balance, 2) . '</strong>
                    </div>' : '') . '
                    <div class="row">
                        <span style="color:#166534;">Razorpay Payment ID:</span>
                        <span style="font-family:monospace;font-size:12px;color:#0f172a;">' . htmlspecialchars($paymentId) . '</span>
                    </div>
                    <div class="row">
                        <span style="color:#166534;">Registration Date:</span>
                        <span style="color:#0f172a;">' . date('d M Y, h:i A') . '</span>
                    </div>
                </div>

                <div class="validity-box" style="background:#eff6ff;border:1.5px solid #93c5fd;border-radius:8px;padding:14px 16px;margin:18px 0;text-align:left;">
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #1e40af; font-weight: 700; margin-bottom: 4px;">Important: 7-Day Registration Validity</div>
                    <div style="font-size: 15px; font-weight: 800; color: #1e3a8a; margin-bottom: 6px;">Valid Until: ' . $validUntil . '</div>
                    <p style="margin: 0; font-size: 12.5px; color: #1e3a8a; line-height: 1.55;">
                        Your registration remains valid for 7 days from the date of submission. If the admission process is not completed within this period, your registration will be automatically cancelled. Please note that the ₹2,750 registration fee is strictly non-refundable under all circumstances.
                    </p>
                </div>

                <div class="info-alert" style="background:#fff8f3;border:1.5px solid #fed7aa;border-radius:8px;padding:14px 16px;font-size:13px;color:#9a3412;margin:18px 0;line-height:1.55;">
                    <strong style="color: #c2410c;">✓ 100% Fee Adjustment Guarantee:</strong><br>
                    This <strong>' . htmlspecialchars($formatted_amount) . '</strong> registration fee will be <strong>directly deducted</strong> from your total course fees during final admission.
                </div>

                <div style="text-align:center;margin:24px 0 16px;">
                    <a href="' . $admission_url . '" style="display:inline-block;background:linear-gradient(135deg, #fe7c03 0%, #ff5200 100%);color:#ffffff !important;text-decoration:none;padding:13px 28px;border-radius:50px;font-weight:700;font-size:14px;margin:5px;box-shadow:0 4px 12px rgba(254,124,3,0.25);">
                        Complete Admission Form Now &rarr;
                    </a>
                </div>

                <div style="text-align:center;margin:10px 0 14px;">
                    <a href="' . $receipt_url . '" style="display:inline-block;background:#f1f5f9;color:#0e1e2e !important;text-decoration:none;padding:9px 18px;border-radius:6px;font-weight:600;font-size:12.5px;margin:3px;border:1px solid #cbd5e1;">
                        Download Official Receipt (PDF)
                    </a>
                    <a href="' . $profile_url . '" style="display:inline-block;background:#0e1e2e;color:#ffffff !important;text-decoration:none;padding:9px 18px;border-radius:6px;font-weight:600;font-size:12.5px;margin:3px;">
                        Student Dashboard
                    </a>
                </div>

                <p style="text-align:center;font-size:12px;color:#64748b;margin-top:16px;">
                    Direct Admission Link: <a href="' . $admission_url . '" style="color:#fe7c03;word-break:break-all;">' . $admission_url . '</a>
                </p>
            </div>
            <div class="footer">
                Questions or batch queries? Contact hello@finchskills.com | ' . getMailHelpline() . ' &bull; Finchskills Institute
            </div>
        </div>
    </body>
    </html>';

    return sendFinchMail($email, $subject, $html);
}

/**
 * 5. Send Detailed Course Admission Fee & Installment Receipt Email
 */
function sendCourseFeeReceiptEmail($email, $name, $studentId, $courseTitle, $receiptDetails) {
    $subject = "Official Fee Receipt & Admission Confirmed - {$courseTitle} (Receipt #{$receiptDetails['receipt_no']})";
    $paid_amt = "₹" . number_format((float)$receiptDetails['paid_amount'], 2);
    $total_fee = "₹" . number_format((float)$receiptDetails['total_fee'], 2);
    $pending_balance = "₹" . number_format((float)$receiptDetails['pending_balance'], 2);

    $base_url = getSiteBaseUrl();
    $profile_url = $base_url . "/student-profile.php";
    $receipt_ref = $receiptDetails['receipt_no'] ?? ($receiptDetails['payment_id'] ?? '');
    $receipt_url = $base_url . "/receipt.php?ref=" . urlencode($receipt_ref);

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: Inter, Arial, sans-serif; background: #f8fafc; margin: 0; padding: 24px 12px; color: #334155; }
            .card { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
            .header { background: #0e1e2e; padding: 26px 20px; text-align: center; color: #ffffff; }
            .body { padding: 26px 24px; }
            .receipt-box { background: #f8fafc; border: 1.5px solid #0f172a; border-radius: 10px; padding: 20px; margin: 20px 0; }
            .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; font-size: 13.5px; }
            .row:last-child { border-bottom: none; }
            .badge-success { background: #15803d; color: #fff; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; }
            .next-due-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 16px; margin-top: 18px; font-size: 13px; color: #92400e; }
            .footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="header">
                <h2 style="margin: 0; font-size: 22px; color: #ffffff; letter-spacing: 0.5px;">FINCHSKILLS INSTITUTE</h2>
                <p style="margin: 4px 0 0; font-size: 13px; color: #fe7c03; font-weight: 700;">Official Admission &amp; Course Fee Receipt</p>
            </div>
            <div class="body">
                <h3 style="margin-top: 0; color: #0f172a; font-size: 18px;">Congratulations, ' . htmlspecialchars($name) . '!</h3>
                <p style="font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 12px;">
                    Your admission to the <strong>' . htmlspecialchars($courseTitle) . '</strong> has been confirmed, and your admission fee has been submitted successfully.
                </p>
                <p style="font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 12px;">
                    Please find your admission details below.
                </p>
                <p style="font-size: 14px; line-height: 1.6; color: #fe7c03; font-weight: 600; margin-bottom: 18px;">
                    Wishing you all the best for your future!
                </p>

                <div class="receipt-box">
                    <div class="row">
                        <span style="color: #64748b;">Receipt Number:</span>
                        <strong style="color: #0f172a; font-family: monospace;">' . htmlspecialchars($receiptDetails['receipt_no']) . '</strong>
                    </div>' .
                    (!empty($receiptDetails['enrollment_no']) ? '
                    <div class="row">
                        <span style="color: #64748b;">Enrollment Number:</span>
                        <strong style="color: #0f172a; font-family: monospace; color: #fe7c03;">' . htmlspecialchars($receiptDetails['enrollment_no']) . '</strong>
                    </div>' : '') . '
                    <div class="row">
                        <span style="color: #64748b;">Student ID:</span>
                        <strong style="color: #0f172a; font-family: monospace;">' . htmlspecialchars($studentId) . '</strong>
                    </div>
                    <div class="row">
                        <span style="color: #64748b;">Payment Plan:</span>
                        <strong style="color: #0f172a; text-transform: capitalize;">' . htmlspecialchars($receiptDetails['plan_title']) . '</strong>
                    </div>
                    <div class="row">
                        <span style="color: #64748b;">Total Course Fee:</span>
                        <span style="color: #0f172a;">' . htmlspecialchars($total_fee) . '</span>
                    </div>' .
                    (!empty($receiptDetails['seat_fee_adjusted']) ? '
                    <div class="row">
                        <span style="color: #15803d;">Seat Booking Fee Adjusted:</span>
                        <strong style="color: #15803d;">- ₹' . number_format((float)$receiptDetails['seat_fee_adjusted'], 2) . '</strong>
                    </div>' : '') . 
                    (!empty($receiptDetails['discount_amount']) ? '
                    <div class="row">
                        <span style="color: #15803d;">Full Payment Discount:</span>
                        <strong style="color: #15803d;">- ₹' . number_format((float)$receiptDetails['discount_amount'], 2) . '</strong>
                    </div>' : '') . '
                    <div class="row" style="background: #f0fdf4; margin: 0 -20px; padding: 10px 20px;">
                        <span style="color: #166534; font-weight: bold;">Amount Paid (Now):</span>
                        <strong style="color: #15803d; font-size: 16px;">' . htmlspecialchars($paid_amt) . '</strong>
                    </div>
                    <div class="row">
                        <span style="color: #64748b;">Transaction / Payment ID:</span>
                        <span style="font-family: monospace; font-size: 12px; color: #0f172a;">' . htmlspecialchars($receiptDetails['payment_id']) . '</span>
                    </div>
                    <div class="row">
                        <span style="color: #64748b;">Remaining Course Balance:</span>
                        <strong style="color: ' . ((float)$receiptDetails['pending_balance'] > 0 ? '#b91c1c' : '#15803d') . ';">' . htmlspecialchars($pending_balance) . '</strong>
                    </div>
                </div>

                ' . (!empty($receiptDetails['next_due_date']) ? '
                <div class="next-due-box">
                    <strong><i class="fa-solid fa-clock me-1"></i> Next Installment Schedule:</strong><br>
                    <span>Your next installment of <strong>₹' . number_format((float)$receiptDetails['next_due_amount'], 2) . '</strong> is due on <strong>' . date('d M Y', strtotime($receiptDetails['next_due_date'])) . '</strong>. You will receive an email reminder before this date.</span>
                </div>' : '<div style="padding: 10px; background: #ecfdf5; border-radius: 6px; color: #065f46; font-size: 13px; font-weight: 600; text-align: center;">✓ Course fee paid in full. No further payments due.</div>') . '

                <div style="text-align: center; margin: 24px 0 16px;">
                    <a href="' . $receipt_url . '" style="display: inline-block; background: #fe7c03; color: #ffffff !important; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 700; font-size: 13.5px; margin: 4px;">
                        View / Print Official Receipt &rarr;
                    </a>
                    <a href="' . $profile_url . '" style="display: inline-block; background: #0e1e2e; color: #ffffff !important; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 700; font-size: 13.5px; margin: 4px;">
                        Access Student Dashboard &rarr;
                    </a>
                </div>

                <p style="font-size: 13px; color: #64748b; margin-top: 18px; line-height: 1.6; text-align: center;">
                    You can log in to your <a href="' . $profile_url . '" style="color: #fe7c03; font-weight: 600;">Student Dashboard</a> anytime to track your enrolled courses, view upcoming installment dates, and download fee receipts.<br>
                    <span style="font-size: 11.5px; color: #94a3b8; word-break: break-all;">Dashboard URL: ' . $profile_url . '</span>
                </p>
            </div>
            <div class="footer">
                Helpline: ' . getMailHelpline() . ' | accounts@finchskills.com &bull; Finchskills Institute
            </div>
        </div>
    </body>
    </html>';

    return sendFinchMail($email, $subject, $html);
}

/**
 * 6. Send Comprehensive Payment Alert Notification to Admin
 */
function sendAdminFeePaymentNotificationEmail($studentData, $courseData, $paymentDetails) {
    $cfg = getSmtpConfig();
    $admin_email = !empty($cfg['smtp_admin_email']) ? $cfg['smtp_admin_email'] : (!empty($cfg['smtp_username']) ? $cfg['smtp_username'] : 'finchskillsinstitute@gmail.com');

    $studentName = $studentData['name'] ?? 'Student';
    $studentId   = $studentData['student_id'] ?? 'N/A';
    $mobile      = $studentData['mobile'] ?? 'N/A';
    $email       = $studentData['email'] ?? 'N/A';

    $courseTitle = $courseData['title'] ?? 'Selected Course';
    $duration    = $courseData['duration'] ?? 'N/A';

    $subject = "💳 New Fee Received: ₹" . number_format((float)$paymentDetails['paid_amount'], 2) . " from {$studentName} ({$studentId})";

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: Inter, Arial, sans-serif; background: #0f172a; margin: 0; padding: 24px 12px; color: #334155; }
            .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
            .header { background: #0e1e2e; padding: 24px 20px; border-bottom: 3px solid #fe7c03; }
            .body { padding: 24px; }
            .badge-plan { display: inline-block; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 12px; text-transform: uppercase; }
            .info-table { width: 100%; border-collapse: collapse; margin-top: 16px; margin-bottom: 18px; }
            .info-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; }
            .info-table tr:last-child td { border-bottom: none; }
            .label-td { color: #64748b; width: 40%; }
            .val-td { color: #0f172a; font-weight: 600; }
            .alert-box { background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 14px 16px; margin: 16px 0; }
            .btn-admin { display: inline-block; background: #fe7c03; color: #ffffff !important; text-decoration: none; padding: 11px 22px; border-radius: 6px; font-weight: 700; font-size: 13.5px; }
            .footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="header">
                <h2 style="margin: 0; font-size: 20px; color: #ffffff;">FINCHSKILLS INSTITUTE</h2>
                <p style="margin: 4px 0 0; font-size: 13px; color: #fe7c03; font-weight: 700;">Admin Fee Payment Notification Alert</p>
            </div>
            <div class="body">
                <div class="alert-box">
                    <span style="color: #166534; font-size: 15px; font-weight: 700;">✓ Payment Successfully Received &amp; Processed</span>
                    <div style="font-size: 13px; color: #15803d; margin-top: 4px;">
                        A student has just submitted course fees through the portal.
                    </div>
                </div>

                <h4 style="margin: 16px 0 8px; color: #0f172a; font-size: 15px; text-transform: uppercase; letter-spacing: 0.5px;">Student Details</h4>
                <table class="info-table">
                    <tr>
                        <td class="label-td">Student Name:</td>
                        <td class="val-td">' . htmlspecialchars($studentName) . '</td>
                    </tr>
                    <tr>
                        <td class="label-td">Student ID:</td>
                        <td class="val-td" style="color: #fe7c03; font-family: monospace; font-size: 15px;">' . htmlspecialchars($studentId) . '</td>
                    </tr>
                    <tr>
                        <td class="label-td">Mobile Contact:</td>
                        <td class="val-td">' . htmlspecialchars($mobile) . '</td>
                    </tr>
                    <tr>
                        <td class="label-td">Email Address:</td>
                        <td class="val-td">' . htmlspecialchars($email) . '</td>
                    </tr>
                </table>

                <h4 style="margin: 20px 0 8px; color: #0f172a; font-size: 15px; text-transform: uppercase; letter-spacing: 0.5px;">Course &amp; Fee Breakdown</h4>
                <table class="info-table">
                    <tr>
                        <td class="label-td">Enrolled Course:</td>
                        <td class="val-td">' . htmlspecialchars($courseTitle) . '</td>
                    </tr>
                    <tr>
                        <td class="label-td">Course Duration:</td>
                        <td class="val-td">' . htmlspecialchars($duration) . '</td>
                    </tr>
                    <tr>
                        <td class="label-td">Payment Mode / Plan:</td>
                        <td class="val-td"><span class="badge-plan">' . htmlspecialchars($paymentDetails['plan_title']) . '</span></td>
                    </tr>
                    <tr>
                        <td class="label-td">Total Course Fee:</td>
                        <td class="val-td">₹' . number_format((float)$paymentDetails['total_fee'], 2) . '</td>
                    </tr>
                    ' . (!empty($paymentDetails['discount_amount']) ? '
                    <tr>
                        <td class="label-td" style="color: #15803d;">Full Pay Discount Applied:</td>
                        <td class="val-td" style="color: #15803d;">- ₹' . number_format((float)$paymentDetails['discount_amount'], 2) . '</td>
                    </tr>' : '') . '
                    <tr style="background: #f8fafc;">
                        <td class="label-td" style="font-weight: 700; color: #0f172a;">Amount Paid (Now):</td>
                        <td class="val-td" style="color: #16a34a; font-size: 16px;">₹' . number_format((float)$paymentDetails['paid_amount'], 2) . '</td>
                    </tr>
                    <tr>
                        <td class="label-td">Transaction / Payment ID:</td>
                        <td class="val-td" style="font-family: monospace;">' . htmlspecialchars($paymentDetails['payment_id']) . '</td>
                    </tr>
                    <tr>
                        <td class="label-td">Receipt Number:</td>
                        <td class="val-td" style="font-family: monospace;">' . htmlspecialchars($paymentDetails['receipt_no']) . '</td>
                    </tr>
                    <tr>
                        <td class="label-td">Remaining Fee Balance:</td>
                        <td class="val-td" style="color: ' . ((float)$paymentDetails['pending_balance'] > 0 ? '#b91c1c' : '#15803d') . ';">₹' . number_format((float)$paymentDetails['pending_balance'], 2) . '</td>
                    </tr>
                    ' . (!empty($paymentDetails['next_due_date']) ? '
                    <tr style="background: #fffbeb;">
                        <td class="label-td" style="color: #92400e; font-weight: 700;">Next Installment Due:</td>
                        <td class="val-td" style="color: #92400e;">₹' . number_format((float)$paymentDetails['next_due_amount'], 2) . ' on ' . date('d M Y', strtotime($paymentDetails['next_due_date'])) . '</td>
                    </tr>' : '') . '
                </table>

                <div style="text-align: center; margin-top: 24px;">
                    <a href="' . (getSiteBaseUrl() . '/admin/students.php?search=' . urlencode($studentId)) . '" class="btn-admin" style="display:inline-block;background:#fe7c03;color:#ffffff !important;text-decoration:none;padding:11px 20px;border-radius:6px;font-weight:700;font-size:13px;margin:4px 5px;">View Student Ledger &rarr;</a>
                    <a href="' . (getSiteBaseUrl() . '/admin/receipt.php?ref=' . urlencode($paymentDetails['receipt_no'])) . '" class="btn-admin" style="display:inline-block;background:#0e1e2e;color:#ffffff !important;text-decoration:none;padding:11px 20px;border-radius:6px;font-weight:700;font-size:13px;margin:4px 5px;">View Receipt &rarr;</a>
                    <a href="' . (getSiteBaseUrl() . '/admin/admissions.php') . '" class="btn-admin" style="display:inline-block;background:#64748b;color:#ffffff !important;text-decoration:none;padding:11px 20px;border-radius:6px;font-weight:700;font-size:13px;margin:4px 5px;">All Admissions &rarr;</a>
                </div>
            </div>
            <div class="footer">
                Finchskills Institute &bull; Automated Payment Notification System
            </div>
        </div>
    </body>
    </html>';

    return sendFinchMail($admin_email, $subject, $html);
}

/**
 * 7. Send Admin Notification when Seat is Booked (with 7-Day Expiry details)
 */
function sendAdminSeatBookingAlertEmail($student, $courseTitle, $paymentId, $amount, $validUntil) {
    $cfg = getSmtpConfig();
    $admin_email = !empty($cfg['smtp_admin_email']) ? $cfg['smtp_admin_email'] : (!empty($cfg['smtp_username']) ? $cfg['smtp_username'] : 'finchskillsinstitute@gmail.com');

    $studentName = $student['name'] ?? 'Student';
    $studentId   = $student['student_id'] ?? 'N/A';
    $phone       = $student['phone'] ?? 'N/A';
    $email       = $student['email'] ?? 'N/A';
    $formatted_amount = "₹" . number_format((float)$amount, 2);
    $validUntilStr = date('d M Y, h:i A', strtotime($validUntil));
    $base_url = getSiteBaseUrl();

    $subject = "🪑 New Seat Reserved: {$studentName} ({$formatted_amount}) - Valid 7 Days";

    $html = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><style>body{font-family:Inter,Arial,sans-serif;background:#0f172a;margin:0;padding:20px;color:#334155;}.card{max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;}.header{background:#0e1e2e;padding:22px;border-bottom:3px solid #fe7c03;color:#ffffff;}.body{padding:24px;}.table{width:100%;border-collapse:collapse;margin:16px 0;}.table td{padding:9px 12px;border-bottom:1px solid #f1f5f9;font-size:13.5px;}.label{color:#64748b;width:38%;}.val{color:#0f172a;font-weight:600;}.alert{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 14px;color:#1e40af;font-size:13px;margin-bottom:16px;}.footer{background:#f8fafc;padding:14px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;}</style></head>
    <body>
        <div class="card">
            <div class="header">
                <h3 style="margin:0;font-size:18px;color:#ffffff;">FINCHSKILLS INSTITUTE</h3>
                <p style="margin:3px 0 0;font-size:13px;color:#fe7c03;font-weight:700;">Seat Reservation Alert (7-Day Validity Active)</p>
            </div>
            <div class="body">
                <div class="alert">
                    <strong>✓ New Seat Booking Confirmed</strong><br>
                    Student has booked a seat online. Under institute policy, the student has <strong>7 days</strong> to submit their admission form.
                </div>
                <table class="table">
                    <tr><td class="label">Student Name:</td><td class="val">' . htmlspecialchars($studentName) . '</td></tr>
                    <tr><td class="label">Student ID:</td><td class="val" style="color:#fe7c03;font-family:monospace;">' . htmlspecialchars($studentId) . '</td></tr>
                    <tr><td class="label">Reserved Course:</td><td class="val">' . htmlspecialchars($courseTitle) . '</td></tr>
                    <tr><td class="label">Amount Received:</td><td class="val" style="color:#16a34a;font-size:15px;">' . htmlspecialchars($formatted_amount) . ' (100% Adjusted)</td></tr>
                    <tr><td class="label">Payment ID:</td><td class="val" style="font-family:monospace;font-size:12px;">' . htmlspecialchars($paymentId) . '</td></tr>
                    <tr><td class="label">Mobile Phone:</td><td class="val">' . htmlspecialchars($phone) . '</td></tr>
                    <tr><td class="label">Email Address:</td><td class="val">' . htmlspecialchars($email) . '</td></tr>
                    <tr style="background:#fff7ed;"><td class="label" style="color:#c2410c;font-weight:700;">Validity Expires On:</td><td class="val" style="color:#c2410c;font-weight:700;">' . $validUntilStr . ' (7 Days)</td></tr>
                </table>
                <div style="text-align:center;margin-top:20px;">
                    <a href="' . $base_url . '/admin/students.php?search=' . urlencode($studentId) . '" style="display:inline-block;background:#fe7c03;color:#ffffff !important;text-decoration:none;padding:10px 20px;border-radius:6px;font-weight:700;font-size:13px;margin:3px;">View Student in Admin &rarr;</a>
                </div>
            </div>
            <div class="footer">Finchskills Institute &bull; Automated Seat Tracking</div>
        </div>
    </body>
    </html>';

    return sendFinchMail($admin_email, $subject, $html);
}

/**
 * 8. Day 2 Reminder Email (5 Days Left)
 */
function sendSeatReminderDay2Email($student, $daysLeft = 5) {
    $email = $student['email'];
    $name = $student['name'] ?? 'Student';
    $courseTitle = $student['booked_course'] ?? 'Career Program';
    $validUntilStr = !empty($student['seat_valid_until']) ? date('d M Y, h:i A', strtotime($student['seat_valid_until'])) : 'in 5 days';
    $base_url = getSiteBaseUrl();
    $admission_url = $base_url . "/form-submission.php?course=" . urlencode($courseTitle);

    $subject = "Friendly Reminder: Complete Your Admission for {$courseTitle} (5 Days Remaining) - Finchskills";

    $html = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><style>body{font-family:Inter,Arial,sans-serif;background:#f8fafc;margin:0;padding:20px;color:#334155;}.card{max-width:540px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;}.header{background:#0e1e2e;padding:24px;text-align:center;color:#ffffff;border-bottom:3px solid #fe7c03;}.body{padding:26px 22px;}.countdown-box{background:#fff8f3;border:1.5px solid #fed7aa;border-radius:10px;padding:16px;text-align:center;margin:18px 0;}.footer{background:#f8fafc;padding:14px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;}</style></head>
    <body>
        <div class="card">
            <div class="header">
                <h2 style="margin:0;font-size:20px;color:#ffffff;">FINCHSKILLS INSTITUTE</h2>
                <p style="margin:4px 0 0;font-size:13px;color:#fe7c03;font-weight:700;">Seat Reservation Reminder</p>
            </div>
            <div class="body">
                <h3 style="margin-top:0;color:#0f172a;">Dear ' . htmlspecialchars($name) . ',</h3>
                <p style="font-size:14px;line-height:1.6;color:#475569;">
                    We hope you are excited to begin your professional journey! Your seat in <strong>' . htmlspecialchars($courseTitle) . '</strong> is currently reserved, but we noticed that you have not yet completed your online admission form.
                </p>

                <div class="countdown-box">
                    <span style="display:inline-block;background:#fe7c03;color:#ffffff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;">Validity Notice</span>
                    <h3 style="margin:10px 0 4px;font-size:20px;color:#0e1e2e;">5 Days Remaining</h3>
                    <p style="margin:0;font-size:12.5px;color:#64748b;">Reservation Valid Until: <strong>' . $validUntilStr . '</strong></p>
                    <p style="margin:8px 0 0;font-size:11.5px;color:#9a3412;line-height:1.5;">
                        Your registration remains valid for 7 days from the date of submission. If the admission process is not completed within this period, your registration will be automatically cancelled. Please note that the ₹2,750 registration fee is strictly non-refundable under all circumstances.
                    </p>
                </div>

                <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px 14px;font-size:13px;color:#166534;margin-bottom:20px;">
                    <strong>✓ 100% Fee Adjustment:</strong> Your registration fee payment is safely recorded and will be directly adjusted into your total course fees.
                </div>

                <div style="text-align:center;margin:24px 0 16px;">
                    <a href="' . $admission_url . '" style="display:inline-block;background:linear-gradient(135deg, #fe7c03 0%, #ff5200 100%);color:#ffffff !important;text-decoration:none;padding:12px 28px;border-radius:50px;font-weight:700;font-size:14px;">
                        Complete Admission Application &rarr;
                    </a>
                </div>

                <p style="text-align:center;font-size:12px;color:#64748b;">
                    Need guidance? Reply to this email or call our counselor at ' . getMailHelpline() . '.
                </p>
            </div>
            <div class="footer">&copy; ' . date('Y') . ' Finchskills Institute &bull; www.finchskills.com</div>
        </div>
    </body>
    </html>';

    return sendFinchMail($email, $subject, $html);
}

/**
 * 9. Day 4 Urgent Reminder Email (3 Days Left)
 */
function sendSeatReminderDay4Email($student, $daysLeft = 3) {
    $email = $student['email'];
    $name = $student['name'] ?? 'Student';
    $courseTitle = $student['booked_course'] ?? 'Career Program';
    $validUntilStr = !empty($student['seat_valid_until']) ? date('d M Y, h:i A', strtotime($student['seat_valid_until'])) : 'in 3 days';
    $base_url = getSiteBaseUrl();
    $admission_url = $base_url . "/form-submission.php?course=" . urlencode($courseTitle);

    $subject = "Action Required: 3 Days Left to Finalize Your Seat for {$courseTitle} - Finchskills";

    $html = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><style>body{font-family:Inter,Arial,sans-serif;background:#f8fafc;margin:0;padding:20px;color:#334155;}.card{max-width:540px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;}.header{background:#0e1e2e;padding:24px;text-align:center;color:#ffffff;border-bottom:3px solid #ff5200;}.body{padding:26px 22px;}.urgent-box{background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:18px;text-align:center;margin:18px 0;}.footer{background:#f8fafc;padding:14px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;}</style></head>
    <body>
        <div class="card">
            <div class="header">
                <h2 style="margin:0;font-size:20px;color:#ffffff;">FINCHSKILLS INSTITUTE</h2>
                <p style="margin:4px 0 0;font-size:13px;color:#f87171;font-weight:700;">Urgent Enrollment Action Required</p>
            </div>
            <div class="body">
                <h3 style="margin-top:0;color:#0f172a;">Dear ' . htmlspecialchars($name) . ',</h3>
                <p style="font-size:14px;line-height:1.6;color:#475569;">
                    Your provisional seat reservation in <strong>' . htmlspecialchars($courseTitle) . '</strong> is closing soon! Batches have limited capacity, and we want to ensure you do not miss your spot.
                </p>

                <div class="urgent-box">
                    <span style="display:inline-block;background:#ef4444;color:#ffffff;font-size:11px;font-weight:700;padding:3px 12px;border-radius:20px;text-transform:uppercase;">Time Critical</span>
                    <h3 style="margin:10px 0 4px;font-size:22px;color:#991b1b;">Only 3 Days Left!</h3>
                    <p style="margin:0;font-size:13px;color:#b91c1c;">Reservation Expires: <strong>' . $validUntilStr . '</strong></p>
                    <p style="margin:8px 0 0;font-size:12px;color:#7f1d1d;line-height:1.5;">
                        Your registration remains valid for 7 days from the date of submission. If the admission process is not completed within this period, your registration will be automatically cancelled. Please note that the ₹2,750 registration fee is strictly non-refundable under all circumstances.
                    </p>
                </div>

                <div style="text-align:center;margin:24px 0 16px;">
                    <a href="' . $admission_url . '" style="display:inline-block;background:linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);color:#ffffff !important;text-decoration:none;padding:13px 30px;border-radius:50px;font-weight:700;font-size:14px;box-shadow:0 4px 12px rgba(239,68,68,0.25);">
                        Submit Admission Form Now &rarr;
                    </a>
                </div>

                <p style="text-align:center;font-size:12px;color:#64748b;">
                    Your seat booking fee is 100% adjusted into your final course fee upon form submission.
                </p>
            </div>
            <div class="footer">&copy; ' . date('Y') . ' Finchskills Institute &bull; Helpline: ' . getMailHelpline() . '</div>
        </div>
    </body>
    </html>';

    return sendFinchMail($email, $subject, $html);
}

/**
 * 10. Final Day High-Urgency Notice with Countdown Alert (Last 24 Hours)
 */
function sendSeatReminderFinalDayEmail($student, $hoursLeft = 24) {
    $email = $student['email'];
    $name = $student['name'] ?? 'Student';
    $courseTitle = $student['booked_course'] ?? 'Career Program';
    $validUntilStr = !empty($student['seat_valid_until']) ? date('d M Y, h:i A', strtotime($student['seat_valid_until'])) : 'TODAY';
    $base_url = getSiteBaseUrl();
    $admission_url = $base_url . "/form-submission.php?course=" . urlencode($courseTitle);

    $subject = "🚨 FINAL NOTICE: Your Seat Reservation for {$courseTitle} Expires Today! - Finchskills";

    $html = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><style>body{font-family:Inter,Arial,sans-serif;background:#0f172a;margin:0;padding:20px;color:#334155;}.card{max-width:540px;margin:0 auto;background:#ffffff;border-radius:12px;border:2px solid #ef4444;overflow:hidden;}.header{background:#7f1d1d;padding:26px;text-align:center;color:#ffffff;border-bottom:4px solid #ef4444;}.body{padding:26px 22px;}.danger-box{background:#fef2f2;border:2px dashed #dc2626;border-radius:10px;padding:20px;text-align:center;margin:18px 0;}.timer-badge{display:inline-block;background:#dc2626;color:#ffffff;font-size:13px;font-weight:900;padding:5px 16px;border-radius:50px;letter-spacing:0.5px;}.footer{background:#f8fafc;padding:14px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;}</style></head>
    <body>
        <div class="card">
            <div class="header">
                <div style="font-size:28px;margin-bottom:6px;">⚠️</div>
                <h2 style="margin:0;font-size:22px;color:#ffffff;letter-spacing:0.3px;">FINAL EXPIRATION NOTICE</h2>
                <p style="margin:4px 0 0;font-size:13px;color:#fca5a5;font-weight:700;">Seat Reservation Lapses Today</p>
            </div>
            <div class="body">
                <h3 style="margin-top:0;color:#0f172a;">Dear ' . htmlspecialchars($name) . ',</h3>
                <p style="font-size:14px;line-height:1.6;color:#475569;">
                    This is your <strong>final alert</strong>. The 7-day validity window for your seat reservation in <strong>' . htmlspecialchars($courseTitle) . '</strong> reaches its limit today.
                </p>

                <div class="danger-box">
                    <span class="timer-badge">EXPIRES TODAY</span>
                    <h3 style="margin:12px 0 4px;font-size:24px;color:#991b1b;font-weight:900;">Last Day to Finalize</h3>
                    <p style="margin:0;font-size:14px;color:#b91c1c;font-weight:700;">Deadline: ' . $validUntilStr . '</p>
                    <p style="margin:10px 0 0;font-size:12.5px;color:#7f1d1d;line-height:1.5;">
                        Your registration remains valid for 7 days from the date of submission. If the admission process is not completed within this period, your registration will be automatically cancelled. Please note that the ₹2,750 registration fee is strictly non-refundable under all circumstances. If you do not submit your online admission form before this deadline, the system will automatically <strong>cancel</strong> your reserved seat and release the batch slot to waitlisted students.
                    </p>
                </div>

                <div style="text-align:center;margin:26px 0 18px;">
                    <a href="' . $admission_url . '" style="display:inline-block;background:linear-gradient(135deg, #dc2626 0%, #991b1b 100%);color:#ffffff !important;text-decoration:none;padding:14px 34px;border-radius:50px;font-weight:800;font-size:15px;letter-spacing:0.3px;box-shadow:0 6px 16px rgba(220,38,38,0.35);">
                        Lock My Seat &amp; Submit Admission &rarr;
                    </a>
                </div>

                <p style="text-align:center;font-size:12.5px;color:#64748b;">
                    It takes less than 3 minutes to submit the form. Your booking payment will be 100% credited.
                </p>
            </div>
            <div class="footer">Finchskills Institute &bull; Urgent Helpline: ' . getMailHelpline() . '</div>
        </div>
    </body>
    </html>';

    return sendFinchMail($email, $subject, $html);
}

/**
 * 11. Seat Cancelled / Expired Notification Email
 */
function sendSeatCancelledEmail($student) {
    $email = $student['email'];
    $name = $student['name'] ?? 'Student';
    $courseTitle = $student['booked_course'] ?? 'Career Program';
    $base_url = getSiteBaseUrl();
    $courses_url = $base_url . "/courses.php";

    $subject = "Notice: Seat Reservation Expired for {$courseTitle} - Finchskills Institute";

    $html = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><style>body{font-family:Inter,Arial,sans-serif;background:#f8fafc;margin:0;padding:20px;color:#334155;}.card{max-width:540px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #cbd5e1;overflow:hidden;}.header{background:#334155;padding:24px;text-align:center;color:#ffffff;}.body{padding:26px 22px;}.info-box{background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;padding:16px;margin:18px 0;font-size:13px;color:#475569;line-height:1.6;}.footer{background:#f8fafc;padding:14px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;}</style></head>
    <body>
        <div class="card">
            <div class="header">
                <h2 style="margin:0;font-size:20px;color:#ffffff;">FINCHSKILLS INSTITUTE</h2>
                <p style="margin:4px 0 0;font-size:13px;color:#cbd5e1;">Seat Reservation Status Update</p>
            </div>
            <div class="body">
                <h3 style="margin-top:0;color:#0f172a;">Dear ' . htmlspecialchars($name) . ',</h3>
                <p style="font-size:14px;line-height:1.6;color:#475569;">
                    We are writing to inform you that the 7-day validity period for your reserved seat in <strong>' . htmlspecialchars($courseTitle) . '</strong> has expired. Because no admission form was received within the stipulated timeline, the reservation has been cancelled in our system.
                </p>

                <p style="font-size:13px;color:#64748b;line-height:1.5;">
                    Please note that under institute policy, the ₹2,750 registration fee is strictly non-refundable under all circumstances.
                </p>

                <div class="info-box">
                    <strong style="color:#0f172a;">Would you still like to join this batch?</strong><br>
                    If you faced any difficulty or still wish to enroll, you may contact our admissions administration. Our team retains the authority to review and re-activate your reservation upon request.
                </div>

                <div style="text-align:center;margin:22px 0 14px;">
                    <a href="tel:' . preg_replace('/[^0-9+]/', '', getMailHelpline()) . '" style="display:inline-block;background:#0e1e2e;color:#ffffff !important;text-decoration:none;padding:11px 24px;border-radius:6px;font-weight:700;font-size:13px;margin:3px;">
                        Call Admissions Helpline (' . getMailHelpline() . ')
                    </a>
                    <a href="' . $courses_url . '" style="display:inline-block;background:#fe7c03;color:#ffffff !important;text-decoration:none;padding:11px 24px;border-radius:6px;font-weight:700;font-size:13px;margin:3px;">
                        Browse All Courses
                    </a>
                </div>
            </div>
            <div class="footer">&copy; ' . date('Y') . ' Finchskills Institute &bull; hello@finchskills.com</div>
        </div>
    </body>
    </html>';

    return sendFinchMail($email, $subject, $html);
}

/**
 * 12. Seat Re-activated by Admin Email (Extension Granted)
 */
function sendSeatReactivatedEmail($student, $newValidUntil) {
    $email = $student['email'];
    $name = $student['name'] ?? 'Student';
    $courseTitle = $student['booked_course'] ?? 'Career Program';
    $validUntilStr = date('d M Y, h:i A', strtotime($newValidUntil));
    $base_url = getSiteBaseUrl();
    $admission_url = $base_url . "/form-submission.php?course=" . urlencode($courseTitle);

    $subject = "Good News: Your Seat Reservation has been Re-activated for {$courseTitle}! - Finchskills";

    $html = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><style>body{font-family:Inter,Arial,sans-serif;background:#f8fafc;margin:0;padding:20px;color:#334155;}.card{max-width:540px;margin:0 auto;background:#ffffff;border-radius:12px;border:1.5px solid #22c55e;overflow:hidden;}.header{background:#0e1e2e;padding:24px;text-align:center;color:#ffffff;border-bottom:3px solid #22c55e;}.body{padding:26px 22px;}.success-box{background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:18px;text-align:center;margin:18px 0;}.footer{background:#f8fafc;padding:14px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;}</style></head>
    <body>
        <div class="card">
            <div class="header">
                <h2 style="margin:0;font-size:20px;color:#ffffff;">FINCHSKILLS INSTITUTE</h2>
                <p style="margin:4px 0 0;font-size:13px;color:#4ade80;font-weight:700;">Seat Reservation Re-activated</p>
            </div>
            <div class="body">
                <h3 style="margin-top:0;color:#0f172a;">Great News, ' . htmlspecialchars($name) . '!</h3>
                <p style="font-size:14px;line-height:1.6;color:#475569;">
                    The Finchskills Administration has reviewed and <strong>re-activated your seat reservation</strong> for <strong>' . htmlspecialchars($courseTitle) . '</strong>!
                </p>

                <div class="success-box">
                    <span style="display:inline-block;background:#22c55e;color:#ffffff;font-size:11px;font-weight:700;padding:3px 12px;border-radius:20px;text-transform:uppercase;">Special Extension Granted</span>
                    <h3 style="margin:10px 0 4px;font-size:20px;color:#166534;">Reservation is Now Active</h3>
                    <p style="margin:0;font-size:13.5px;color:#15803d;">New Deadline: <strong>' . $validUntilStr . '</strong></p>
                    <p style="margin:8px 0 0;font-size:12.5px;color:#166534;">
                        Your previous booking fee remains <strong>100% credited and adjusted</strong> into your course fee.
                    </p>
                </div>

                <div style="text-align:center;margin:24px 0 16px;">
                    <a href="' . $admission_url . '" style="display:inline-block;background:linear-gradient(135deg, #fe7c03 0%, #ff5200 100%);color:#ffffff !important;text-decoration:none;padding:13px 30px;border-radius:50px;font-weight:700;font-size:14px;box-shadow:0 4px 12px rgba(254,124,3,0.25);">
                        Complete Admission Form Now &rarr;
                    </a>
                </div>

                <p style="text-align:center;font-size:12px;color:#64748b;">
                    Please make sure to finalize your admission before the new deadline.
                </p>
            </div>
            <div class="footer">&copy; ' . date('Y') . ' Finchskills Institute &bull; Helpline: ' . getMailHelpline() . '</div>
        </div>
    </body>
    </html>';

    return sendFinchMail($email, $subject, $html);
}



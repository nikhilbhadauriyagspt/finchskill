<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail-helper.php';

$error = '';
$success = '';
$step = 1; // 1 = Enter Email, 2 = Enter OTP and New Password
$reset_email = $_SESSION['reset_temp_email'] ?? '';

// Handle Step 1: Send Reset OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_send_reset_otp'])) {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid registered email address.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? AND is_verified = 1 LIMIT 1");
        $stmt->execute([$email]);
        $student = $stmt->fetch();

        if ($student) {
            $otp = sprintf("%06d", mt_rand(100000, 999999));
            $otp_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $upd = $pdo->prepare("UPDATE students SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
            $upd->execute([$otp, $otp_expires, $student['id']]);

            sendPasswordResetOtp($student['email'], $student['name'], $otp);
            $_SESSION['reset_temp_email'] = $email;
            $reset_email = $email;
            $step = 2;
            $success = "A 6-digit password reset code has been sent to your email.";
        } else {
            $error = "No verified student account was found with this email.";
        }
    }
}

// Handle Step 2: Verify OTP and Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_reset_password'])) {
    $entered_otp = trim($_POST['otp'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';
    $verify_email = trim($_POST['verify_email'] ?? $reset_email);

    $step = 2;

    if (empty($entered_otp) || strlen($entered_otp) !== 6) {
        $error = "Please enter the 6-digit OTP code sent to your email.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_new_password) {
        $error = "New password and confirm password do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? AND is_verified = 1 LIMIT 1");
        $stmt->execute([$verify_email]);
        $student = $stmt->fetch();

        if ($student && $student['otp_code'] === $entered_otp && strtotime($student['otp_expires_at']) >= time()) {
            $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE students SET password = ?, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
            $upd->execute([$hashed_new_password, $student['id']]);

            unset($_SESSION['reset_temp_email']);
            header("Location: student-login.php?msg=password_reset");
            exit;
        } else {
            $error = "Invalid or expired OTP. Please check the code or request a new one.";
        }
    }
}

$page_title = "Forgot Password - Finchskills Institute";
include "header.php";
?>

<style>
.login-section {
    padding: 60px 0 90px;
    background: #fafbfc;
    min-height: 75vh;
    display: flex;
    align-items: center;
}
.login-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 38px 32px;
    box-shadow: none !important;
    max-width: 440px;
    margin: 0 auto;
    width: 100%;
}
.login-title {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.3px;
}
.login-label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.login-input {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 14px;
}
.login-input:focus {
    border-color: #fe7c03;
    box-shadow: 0 0 0 3px rgba(254, 124, 3, 0.1);
}
.password-wrap {
    position: relative;
}
.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    font-size: 15px;
}
.password-toggle:hover {
    color: #0f172a;
}
.login-btn {
    background: #fe7c03;
    color: #ffffff;
    border: none;
    border-radius: 9px;
    padding: 12px 20px;
    font-size: 14.5px;
    font-weight: 700;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}
.login-btn:hover {
    background: #e66f00;
    color: #ffffff;
}
</style>

<section class="login-section">
    <div class="container">
        <div class="login-card">

            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center mb-2 rounded-circle" style="width: 48px; height: 48px; background: #fff7ed; color: #fe7c03;">
                    <i class="fa-solid fa-key fs-4"></i>
                </div>
                <h2 class="login-title mb-1">Reset Password</h2>
                <p class="text-muted small mb-0">
                    <?= $step === 1 ? 'Enter your registered email to receive a reset code.' : 'Enter the code and set your new password.' ?>
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-3" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2 fs-6"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success py-2 px-3 small d-flex align-items-center mb-3" role="alert">
                    <i class="fa-solid fa-circle-check me-2 fs-6"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <!-- STEP 1: ENTER EMAIL -->
                <form method="POST" action="forgot-password.php">
                    <input type="hidden" name="action_send_reset_otp" value="1">

                    <div class="mb-3">
                        <label class="login-label">Registered Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-regular fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control login-input border-start-0" placeholder="e.g. rahul@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                        </div>
                    </div>

                    <button type="submit" class="login-btn mt-4">
                        <span>Send Reset Code</span>
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>

                    <div class="text-center mt-3 small text-muted">
                        Remember your password? <a href="student-login.php" class="fw-bold text-decoration-none" style="color: #fe7c03;">Log In Here</a>
                    </div>
                </form>

            <?php else: ?>
                <!-- STEP 2: ENTER OTP & NEW PASSWORD -->
                <form method="POST" action="forgot-password.php">
                    <input type="hidden" name="action_reset_password" value="1">
                    <input type="hidden" name="verify_email" value="<?= htmlspecialchars($reset_email) ?>">



                    <div class="mb-3">
                        <label class="login-label">6-Digit Verification Code <span class="text-danger">*</span></label>
                        <input type="text" name="otp" maxlength="6" class="form-control login-input text-center font-monospace fs-5 fw-bold" placeholder="------" required autofocus>
                    </div>

                    <!-- NEW PASSWORD WITH EYE TOGGLE -->
                    <div class="mb-3">
                        <label class="login-label">New Password <span class="text-danger">*</span></label>
                        <div class="password-wrap">
                            <input type="password" name="new_password" id="newPassword" class="form-control login-input pe-5" placeholder="Minimum 6 characters" required>
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('newPassword', this)" title="Show/Hide Password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- CONFIRM NEW PASSWORD WITH EYE TOGGLE -->
                    <div class="mb-3">
                        <label class="login-label">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="password-wrap">
                            <input type="password" name="confirm_new_password" id="confirmNewPassword" class="form-control login-input pe-5" placeholder="Re-enter new password" required>
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirmNewPassword', this)" title="Show/Hide Password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="login-btn mt-4">
                        <span>Save New Password &amp; Log In</span>
                        <i class="fa-solid fa-circle-check"></i>
                    </button>

                    <div class="text-center mt-3 small text-muted">
                        <a href="forgot-password.php" class="text-secondary text-decoration-none">Change Email</a>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
</section>

<script>
function togglePasswordVisibility(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const icon = btn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php include "footer.php"; ?>

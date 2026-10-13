<?php
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

// If student is already logged in and seat paid, redirect to profile
if (isset($_SESSION['student_logged_in']) && $_SESSION['student_logged_in'] === true) {
    header("Location: student-profile.php");
    exit;
}

// Fetch Dynamic Admin Settings
$settings = [];
$s_stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'razorpay_%' OR setting_key IN ('seat_booking_fee', 'site_logo', 'phone_1')");
while ($r = $s_stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$r['setting_key']] = $r['setting_value'];
}

$mode = $settings['razorpay_mode'] ?? 'test';
$key_id = ($mode === 'live') ? ($settings['razorpay_live_key_id'] ?? '') : ($settings['razorpay_test_key_id'] ?? 'rzp_test_TgVRbl41f2FFmR');
$seat_fee = (float)($settings['seat_booking_fee'] ?? 999.00);
$amount_paise = $seat_fee * 100;
$company_name = $settings['razorpay_company_name'] ?? 'Finchskills Institute';
$site_logo = $settings['site_logo'] ?? 'img/logo.png';
$counselor_phone = $settings['phone_1'] ?? '+91 87508 60860';

// Fetch Active Courses for Dropdown (WITHOUT any fee shown)
$courses_stmt = $pdo->query("SELECT id, title FROM courses WHERE status = 'active' ORDER BY title ASC");
$active_courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Course category metadata helper for premium visual styling
function getCourseMeta($title) {
    $t = strtolower($title);
    if (strpos($t, 'airport') !== false) {
        return ['icon' => 'fa-plane-departure', 'badge' => 'Airport Handling', 'color' => '#0284c7'];
    } elseif (strpos($t, 'ticket') !== false || strpos($t, 'travel') !== false) {
        return ['icon' => 'fa-ticket', 'badge' => 'Travel & Air Ticketing', 'color' => '#fe7c03'];
    } elseif (strpos($t, 'hospitality') !== false || strpos($t, 'hotel') !== false) {
        return ['icon' => 'fa-hotel', 'badge' => 'Hospitality & Luxury', 'color' => '#8b5cf6'];
    } elseif (strpos($t, 'cruise') !== false || strpos($t, 'ship') !== false) {
        return ['icon' => 'fa-ship', 'badge' => 'Cruise Management', 'color' => '#06b6d4'];
    } elseif (strpos($t, 'hostess') !== false || strpos($t, 'cabin') !== false) {
        return ['icon' => 'fa-plane-up', 'badge' => 'Cabin Crew & Air Hostess', 'color' => '#ec4899'];
    }
    return ['icon' => 'fa-graduation-cap', 'badge' => 'Career Track', 'color' => '#fe7c03'];
}

// Preselected course if passed via URL (?course=... or ?course_id=...)
$preselected_id = (int)($_GET['course_id'] ?? 0);
$preselected_name = trim($_GET['course'] ?? '');

$page_title = "Student Registration & Seat Reservation - " . htmlspecialchars($company_name);
include "header.php";
?>

<style>
.reg-section {
    padding: 35px 0 80px;
    background: #fafbfc;
    min-height: 85vh;
}
.reg-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 34px 30px;
    box-shadow: none !important;
}
.reg-banner-card {
    background: linear-gradient(145deg, #0e1e2e 0%, #162a3f 100%);
    border-radius: 16px;
    padding: 36px 30px;
    color: #ffffff;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
/* Step 2 Mobile Optimization: Hide left brand banner on mobile so Fee Submission is directly at top */
@media (max-width: 991.98px) {
    #regMainRow.step2-active #regLeftBannerCol {
        display: none !important;
    }
    #regMainRow.step2-active #regRightFormCol {
        width: 100% !important;
    }
    #regMainRow.step2-active .reg-card {
        padding: 24px 18px !important;
    }
}
.reg-title {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.5px;
}
.reg-label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.reg-input {
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 14px;
    transition: all 0.2s ease;
    background-color: #ffffff;
}
.reg-input:focus {
    border-color: #fe7c03;
    box-shadow: 0 0 0 3px rgba(254, 124, 3, 0.12);
    outline: none;
}

/* ======================================================== */
/* PREMIUM APP-LIKE COURSE PICKER TRIGGER (MOBILE & DESKTOP) */
/* ======================================================== */
.course-picker-box {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    padding: 10px 14px;
    cursor: pointer;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 54px;
    user-select: none;
}
.course-picker-box:hover, .course-picker-box:focus {
    border-color: #fe7c03;
    box-shadow: 0 0 0 3px rgba(254, 124, 3, 0.12);
    outline: none;
}
.course-picker-box.is-selected {
    border-color: #22c55e !important;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12) !important;
    background: #fcfdfc;
}
.course-picker-icon-wrap {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(254, 124, 3, 0.12);
    color: #fe7c03;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    transition: all 0.25s ease;
}
.course-picker-box.is-selected .course-picker-icon-wrap {
    background: #ecfdf5;
    color: #059669;
}
.course-picker-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    line-height: 1.2;
}
.course-picker-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
}
.course-picker-status-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
}
.course-picker-box.is-selected .course-picker-status-badge {
    background: #ecfdf5;
    color: #059669;
    border-color: #a7f3d0;
}

/* ======================================================== */
/* MODERN MOBILE BOTTOM SHEET / MODAL STYLING              */
/* ======================================================== */
.course-sheet-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 999999;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    transition: opacity 0.25s ease;
}
@media (min-width: 768px) {
    .course-sheet-overlay {
        align-items: center;
    }
}
.course-sheet-container {
    background: #ffffff;
    width: 100%;
    max-width: 580px;
    border-radius: 22px 22px 0 0;
    padding: 16px 20px 24px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    animation: slideUpSheet 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.2);
}
@media (min-width: 768px) {
    .course-sheet-container {
        border-radius: 18px;
        padding: 24px 24px 28px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        animation: popModal 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
}
@keyframes slideUpSheet {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}
@keyframes popModal {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.course-sheet-handle {
    width: 44px;
    height: 4.5px;
    background: #cbd5e1;
    border-radius: 4px;
    margin: 0 auto 12px;
}
.course-sheet-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 6px 12px;
    border-bottom: 1px solid #f1f5f9;
}
.course-sheet-close-btn {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: none;
    background: #f1f5f9;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s;
}
.course-sheet-close-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.course-sheet-list {
    overflow-y: auto;
    padding: 10px 4px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    -webkit-overflow-scrolling: touch;
}
.course-card-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 15px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.2s ease;
}
.course-card-option:hover {
    border-color: #fe7c03;
    background: #fffbf7;
}
.course-card-option.selected {
    border-color: #10b981 !important;
    background: #f0fdf4 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}
.course-card-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}
.course-card-title {
    font-size: 14.5px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
}
.course-card-desc {
    font-size: 11.5px;
    color: #64748b;
    display: block;
    margin-top: 2px;
}
.course-card-radio {
    font-size: 18px;
    padding-left: 10px;
}

/* ======================================================== */
/* OTHER CONTROLS & STEPPER                                */
/* ======================================================== */
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
.photo-upload-box {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #f8fafc;
    border: 1.5px dashed #cbd5e1;
    border-radius: 12px;
    padding: 12px 16px;
}
.photo-avatar-preview {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    border: 2px solid #fe7c03;
    object-fit: cover;
    background: #ffffff;
    flex-shrink: 0;
}
.reg-submit-btn {
    background: #fe7c03;
    color: #ffffff;
    border: none;
    border-radius: 9px;
    padding: 13px 20px;
    font-size: 15px;
    font-weight: 700;
    width: 100%;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.reg-submit-btn:hover {
    background: #e66f00;
    color: #ffffff;
}

/* Benefit Items on Left Banner */
.benefit-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 18px;
}
.benefit-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba(254, 124, 3, 0.15);
    color: #fe7c03;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 14px;
}

/* Inline OTP Card */
.otp-inline-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 14px 16px;
    margin-top: 10px;
}
.verified-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
}

/* Step 2 Styles */
.summary-strip {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 20px;
}
.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 7px 0;
    font-size: 13.5px;
    border-bottom: 1px solid #f1f5f9;
}
.summary-row:last-child {
    border-bottom: none;
}
.fee-card-box {
    background: #fafbfc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 18px;
    margin-bottom: 20px;
}
.btn-pay-now {
    background: #10b981;
    color: #ffffff;
    font-weight: 700;
    padding: 14px 22px;
    border-radius: 8px;
    border: none;
    width: 100%;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}
.btn-pay-now:hover {
    background: #059669;
    color: #ffffff;
}
.btn-back {
    background: transparent;
    color: #64748b;
    border: 1px solid #cbd5e1;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13.5px;
    width: 100%;
    margin-top: 10px;
}
.btn-back:hover {
    background: #f1f5f9;
    color: #334155;
}

/* Mobile Polish */
@media (max-width: 767.98px) {
    .reg-card {
        padding: 22px 16px !important;
        border-radius: 14px;
    }
    .reg-title {
        font-size: 20px !important;
    }
    .reg-input {
        min-height: 44px;
        font-size: 14px;
    }
    .course-picker-box {
        min-height: 52px;
        padding: 9px 12px;
    }
    .course-picker-name {
        font-size: 13.5px;
    }
    .photo-upload-box {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
</style>

<!-- ================= REGISTRATION SECTION ================= -->
<section class="reg-section">
    <div class="container">

        <!-- BREADCRUMB -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-secondary"><i class="fa-solid fa-house me-1"></i>Home</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Student Registration</li>
            </ol>
        </nav>

        <div class="row g-4 g-lg-5 align-items-stretch" id="regMainRow">

            <!-- ================= LEFT: BRAND INFO & TRUST PILLARS ================= -->
            <div class="col-lg-5" id="regLeftBannerCol">
                <div class="reg-banner-card">
                    <div>
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3" style="background: rgba(254, 124, 3, 0.2); color: #fe7c03; font-size: 12px; font-weight: 700;">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span>STUDENT ENROLLMENT PORTAL</span>
                        </div>
                        <h2 class="fw-bold text-white mb-3" style="font-size: 26px; line-height: 1.3;">
                            Start Your Career in Aviation, Hospitality &amp; Cruise Lines
                        </h2>
                        <p class="text-white-50 small mb-4" style="line-height: 1.7;">
                            Register as a verified Student to receive your official Finchskills Student ID, reserve your preferred batch timing, and access practical job-oriented training.
                        </p>

                        <!-- Benefit 1: Unique Student ID -->
                        <div class="benefit-item">
                            <div class="benefit-icon"><i class="fa-solid fa-id-card"></i></div>
                            <div>
                                <h6 class="fw-bold text-white mb-1" style="font-size: 14px;">Instant Unique Student ID</h6>
                                <p class="text-white-50 small mb-0">Get your recognized registration number immediately upon email verification.</p>
                            </div>
                        </div>

                        <!-- Benefit 2: Direct Seat Reservation -->
                        <div class="benefit-item">
                            <div class="benefit-icon"><i class="fa-solid fa-chair"></i></div>
                            <div>
                                <h6 class="fw-bold text-white mb-1" style="font-size: 14px;">Direct Batch Seat Reservation</h6>
                                <p class="text-white-50 small mb-0">Reserve your spot and take the first step toward your dream career.</p>
                            </div>
                        </div>

                        <!-- Benefit 3: Easy Monthly Installments (EMI) -->
                        <div class="benefit-item">
                            <div class="benefit-icon"><i class="fa-solid fa-wallet"></i></div>
                            <div>
                                <h6 class="fw-bold text-white mb-1" style="font-size: 14px;">Easy Monthly Installments (EMI)</h6>
                                <p class="text-white-50 small mb-0">Flexible zero-interest payment plans available during course admission.</p>
                            </div>
                        </div>

                        <!-- Benefit 4: 100% Seat Fee Adjusted -->
                        <div class="benefit-item">
                            <div class="benefit-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                            <div>
                                <h6 class="fw-bold text-white mb-1" style="font-size: 14px;">100% Seat Fee Adjusted</h6>
                                <p class="text-white-50 small mb-0">Your ₹<?= number_format($seat_fee, 0) ?> reservation fee is completely deducted from your total course tuition fees.</p>
                            </div>
                        </div>

                        <!-- Benefit 5: 100% Placement Assistance -->
                        <div class="benefit-item">
                            <div class="benefit-icon"><i class="fa-solid fa-briefcase"></i></div>
                            <div>
                                <h6 class="fw-bold text-white mb-1" style="font-size: 14px;">100% Placement Assistance</h6>
                                <p class="text-white-50 small mb-0">Dedicated placement support with leading airlines, luxury airport handling &amp; hotels.</p>
                            </div>
                        </div>

                        <!-- Benefit 6: Lifetime Access -->
                        <div class="benefit-item">
                            <div class="benefit-icon"><i class="fa-solid fa-user-shield"></i></div>
                            <div>
                                <h6 class="fw-bold text-white mb-1" style="font-size: 14px;">Lifetime Portal Access</h6>
                                <p class="text-white-50 small mb-0">Access interview alerts, course materials, and verified credentials at any time.</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-top border-secondary border-opacity-25 mt-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-white p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="fa-solid fa-headset text-dark fs-5"></i>
                            </div>
                            <div>
                                <span class="d-block small text-white-50">Need Help Registering?</span>
                                <strong class="text-white small">Call Counselor: <a href="tel:<?= preg_replace('/\s+/', '', htmlspecialchars($counselor_phone)) ?>" class="text-white text-decoration-none"><?= htmlspecialchars($counselor_phone) ?></a></strong>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ================= RIGHT: REGISTRATION FORM ================= -->
            <div class="col-lg-7" id="regRightFormCol">
                <div class="reg-card">

                    <!-- Header & Mini Stepper Indicator -->
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                        <div>
                            <h3 class="reg-title mb-1">Student Registration</h3>
                            <p class="text-muted small mb-0">Create your Student profile to book batch seats and track your admissions.</p>
                        </div>
                        <div class="d-none d-sm-flex align-items-center gap-2">
                            <span class="badge px-3 py-2 rounded-pill fw-semibold" id="stepBadge1" style="background: #fff7ed; color: #fe7c03; border: 1px solid #fed7aa; font-size: 12px;">
                                <i class="fa-solid fa-circle-dot me-1"></i> Step 1: Details
                            </span>
                            <i class="fa-solid fa-angle-right text-muted small"></i>
                            <span class="badge px-3 py-2 rounded-pill fw-semibold text-muted bg-light border" id="stepBadge2" style="font-size: 12px;">
                                Step 2: Seat
                            </span>
                        </div>
                    </div>

                    <div id="step1Alert" class="alert alert-danger p-2.5 small mb-3" style="display: none;"></div>

                    <!-- ========================================== -->
                    <!-- STEP 1: Student DETAILS & COURSE SELECTION -->
                    <!-- ========================================== -->
                    <div id="step1Container">
                        <form id="step1Form" novalidate>

                            <!-- FULL NAME -->
                            <div class="mb-3">
                                <label class="reg-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" id="candName" class="form-control reg-input" placeholder="" required>
                                <div id="nameFeedback" class="form-text small mt-1" style="font-size: 11px;">Student's legal name as per Aadhaar</div>
                            </div>

                            <!-- MOBILE & EMAIL IN 2 COLUMNS -->
                            <div class="row g-3 mb-3">
                                <!-- MOBILE NUMBER -->
                                <div class="col-md-6">
                                    <label class="reg-label">Mobile Number (WhatsApp) <span class="text-danger">*</span></label>
                                    <input type="tel" id="candPhone" maxlength="10" class="form-control reg-input font-monospace" placeholder="" required autocomplete="tel">
                                    <div id="phoneFeedback" class="form-text small mt-1" style="font-size: 11px;">10-digit mobile number required</div>
                                </div>

                                <!-- EMAIL ADDRESS -->
                                <div class="col-md-6">
                                    <label class="reg-label d-flex justify-content-between align-items-center">
                                        <span>Email Address <span class="text-danger">*</span></span>
                                        <span id="emailVerifiedBadge" style="display: none;" class="verified-chip">
                                            <i class="fa-solid fa-circle-check"></i> Verified
                                        </span>
                                    </label>
                                    <div class="input-group">
                                        <input type="email" id="candEmail" class="form-control reg-input" placeholder="" required autocomplete="email">
                                        <button type="button" id="btnSendOtp" class="btn btn-outline-primary" style="font-size: 12.5px; font-weight: 600; border-radius: 0 8px 8px 0; border-color: #cbd5e1; white-space: nowrap;">
                                            <span>Verify Email</span>
                                        </button>
                                    </div>
                                    <div id="emailFeedback" class="form-text small mt-1" style="font-size: 11px;">Must be a valid @gmail.com address (OTP will be sent here).</div>

                                    <!-- Inline OTP Verification Box -->
                                    <div id="otpBox" class="otp-inline-card" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-bold text-dark"><i class="fa-solid fa-key text-warning me-1"></i> Enter 6-Digit OTP</span>
                                            <span id="otpTimer" class="small text-muted font-monospace"></span>
                                        </div>
                                        <div class="input-group mb-2">
                                            <input type="text" id="otpInput" class="form-control reg-input text-center font-monospace" placeholder="" maxlength="6" style="letter-spacing: 4px; font-weight: 700;">
                                            <button type="button" id="btnVerifyOtp" class="btn btn-success" style="font-size: 13px; font-weight: 600;">
                                                <span>Verify OTP</span>
                                            </button>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div id="otpMsg" class="small text-danger" style="display: none;"></div>
                                            <button type="button" id="btnResendOtp" class="btn btn-link p-0 small text-decoration-none" style="font-size: 12px; display: none;">Resend OTP</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- AADHAAR NUMBER -->
                            <div class="mb-3">
                                <label class="reg-label">Aadhaar Number (12 Digits) <span class="text-danger">*</span></label>
                                <input type="text" id="candAadhaar" maxlength="14" class="form-control reg-input font-monospace" placeholder="" required autocomplete="off">
                                <div id="aadhaarFeedback" class="form-text small mt-1" style="font-size: 11px;">12 digits required (formatted in groups of 4)</div>
                            </div>

                            <!-- ======================================================== -->
                            <!-- COURSE PROGRAM SELECTION (PREMIUM APP-LIKE CARD PICKER)  -->
                            <!-- ======================================================== -->
                            <div class="mb-3">
                                <label class="reg-label d-flex justify-content-between align-items-center">
                                    <span>Select Course Program <span class="text-danger">*</span></span>
                                    <span class="text-muted small" style="font-size: 11px;"><i class="fa-solid fa-certificate text-warning me-1"></i>Official Program</span>
                                </label>

                                <!-- Hidden Native Select for complete system & JS compatibility -->
                                <select id="candCourseSelect" name="course_id" style="position: absolute; opacity: 0; pointer-events: none; width: 1px; height: 1px;" required>
                                    <option value="">-- Select Your Desired Course Program --</option>
                                    <?php foreach ($active_courses as $c): ?>
                                        <?php
                                        $is_sel = ($preselected_id > 0 && $preselected_id === (int)$c['id']) || 
                                                  (!empty($preselected_name) && strcasecmp($c['title'], $preselected_name) === 0);
                                        ?>
                                        <option value="<?= $c['id'] ?>" data-title="<?= htmlspecialchars($c['title']) ?>" <?= $is_sel ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <!-- Interactive Modern Trigger Card -->
                                <div class="course-picker-box" id="coursePickerBtn" tabindex="0" role="button" aria-haspopup="dialog" aria-label="Choose Career Program">
                                    <div class="d-flex align-items-center gap-3 overflow-hidden">
                                        <div class="course-picker-icon-wrap" id="pickerIconWrap">
                                            <i class="fa-solid fa-graduation-cap" id="pickerIcon"></i>
                                        </div>
                                        <div class="text-truncate">
                                            <div class="course-picker-label" id="pickerSubText">Tap to choose program</div>
                                            <div class="course-picker-name text-truncate" id="pickerTitleText">-- Choose Course Program --</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-2">
                                        <span class="course-picker-status-badge" id="pickerStatusBadge">Choose &rarr;</span>
                                        <i class="fa-solid fa-chevron-down text-muted" id="pickerChevron"></i>
                                    </div>
                                </div>

                                <div id="courseFeedback" class="form-text small mt-1" style="font-size: 11px;">Lock batch timing &amp; seat allocation for your chosen program</div>
                            </div>

                            <!-- PASSWORD & CONFIRM PASSWORD -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="reg-label">Create Password <span class="text-danger">*</span></label>
                                    <div class="password-wrap">
                                        <input type="password" id="candPassword" class="form-control reg-input pe-5" placeholder="" required autocomplete="new-password">
                                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('candPassword', this)" title="Show/Hide Password">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                    <div id="passwordFeedback" class="form-text small mt-1" style="font-size: 11px;">Minimum 6 characters required</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="reg-label">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="password-wrap">
                                        <input type="password" id="candConfirmPassword" class="form-control reg-input pe-5" placeholder="" required autocomplete="new-password">
                                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('candConfirmPassword', this)" title="Show/Hide Password">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                    <div id="confirmPasswordFeedback" class="form-text small mt-1" style="font-size: 11px;">Type password again to match</div>
                                </div>
                            </div>

                            <!-- PASSPORT PHOTO UPLOAD -->
                            <div class="mb-4">
                                <label class="reg-label">Passport Size Photograph <span class="text-muted fw-normal">(Optional / Recommended)</span></label>
                                <div class="photo-upload-box">
                                    <img src="img/avatar-placeholder.png" alt="Avatar Preview" class="photo-avatar-preview" id="avatarPreview" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23cbd5e1\'%3E%3Cpath d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/%3E%3C/svg%3E';">
                                    <div class="flex-grow-1">
                                        <input type="file" id="candPhotoInput" class="form-control form-control-sm" accept="image/png, image/jpeg, image/jpg, image/webp">
                                        <input type="hidden" id="candPhotoPath" value="">
                                        <div class="form-text small mt-1" id="photoStatusText" style="font-size: 11px;">
                                            Upload a clear front-facing passport style photo (JPG/PNG/WEBP, Max 5MB).
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TERMS CHECKBOX -->
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="agreeTerms" required checked>
                                <label class="form-check-label small text-secondary" for="agreeTerms">
                                    I confirm that all details provided are accurate and agree to Finchskills <a href="terms-conditions.php" class="text-decoration-none" style="color: #fe7c03;">Terms &amp; Conditions</a>.
                                </label>
                            </div>

                            <!-- CONTINUE TO STEP 2 BUTTON -->
                            <button type="button" id="btnContinueToStep2" class="reg-submit-btn">
                                <span>Continue to Seat Booking</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>

                            <div class="text-center mt-3 small text-muted">
                                Already have an account? <a href="student-login.php" class="fw-bold text-decoration-none" style="color: #fe7c03;">Log In Here &rarr;</a>
                            </div>

                        </form>
                    </div>


                    <!-- ========================================== -->
                    <!-- STEP 2: SEAT BOOKING AMOUNT & PAYMENT      -->
                    <!-- ========================================== -->
                    <div id="step2Container" style="display: none;">
                        <div class="text-center mb-4">
                            <div class="mb-2" style="font-size: 32px; color: #10b981;">
                                <i class="fa-solid fa-chair"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-1" style="font-size: 20px;">Confirm &amp; Reserve Your Seat</h4>
                            <p class="text-muted small mb-0">Reserve Your Seat &amp; Begin Your Journey to Success.</p>
                        </div>

                        <!-- Student SUMMARY CARD -->
                        <div class="summary-strip">
                            <div class="summary-row">
                                <span class="text-muted">Student Name:</span>
                                <strong class="text-dark" id="sumName">-</strong>
                            </div>
                            <div class="summary-row">
                                <span class="text-muted">Mobile &amp; Email:</span>
                                <span class="text-dark" id="sumContact">-</span>
                            </div>
                            <div class="summary-row">
                                <span class="text-muted">Aadhaar Card:</span>
                                <span class="text-dark font-monospace" id="sumAadhaar">-</span>
                            </div>
                            <div class="summary-row">
                                <span class="text-muted">Selected Program:</span>
                                <strong class="text-primary" id="sumCourse">-</strong>
                            </div>
                        </div>

                        <!-- DYNAMIC FEE BOX (FROM ADMIN) -->
                        <div class="fee-card-box">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary small">Seat Reservation Fee:</span>
                                <strong class="text-dark">₹<?= number_format($seat_fee, 2) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary small">Adjustment in Final Fee:</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">- ₹<?= number_format($seat_fee, 2) ?> (100% Adjusted)</span>
                            </div>
                            <hr class="my-2 border-dashed">
                            <div class="d-flex justify-content-between align-items-center pt-1">
                                <div>
                                    <strong class="text-dark d-block">Total Payable Now</strong>
                                    <small class="text-muted" style="font-size: 11px;">Secures guaranteed admission</small>
                                </div>
                                <h4 class="fw-bold text-success mb-0">₹<?= number_format($seat_fee, 2) ?></h4>
                            </div>
                        </div>

                        <!-- 7 DAYS VALIDITY & FEE ADJUSTMENT NOTE -->
                        <div class="alert alert-warning border-warning-subtle small p-2.5 rounded-3 mb-3" style="font-size: 12px; line-height: 1.55;">
                            <i class="fa-solid fa-circle-info text-warning me-1"></i>
                            <strong>Important Policy:</strong> Your registration remains valid for 7 days from the date of submission. If the admission process is not completed within this period, your registration will be automatically cancelled. Please note that the ₹2,750 registration fee is strictly non-refundable under all circumstances.
                        </div>

                        <!-- PAY NOW & EDIT BUTTONS -->
                        <button type="button" id="btnPayNow" class="btn-pay-now">
                            <i class="fa-solid fa-lock"></i>
                            <span>Pay ₹<?= number_format($seat_fee, 0) ?> &amp; Confirm Seat</span>
                        </button>

                        <button type="button" id="btnBackToStep1" class="btn-back">
                            <i class="fa-solid fa-arrow-left me-1"></i> Edit Details
                        </button>

                        <div class="text-center mt-3 text-muted" style="font-size: 11px;">
                            <i class="fa-solid fa-shield-halved text-success me-1"></i> 256-Bit Encrypted Secure Razorpay Gateway (UPI, Cards, Netbanking)
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<!-- ======================================================== -->
<!-- MODERN COURSE SELECTION BOTTOM SHEET / MODAL             -->
<!-- ======================================================== -->
<div class="course-sheet-overlay" id="courseSheetOverlay" style="display: none;">
    <div class="course-sheet-container" id="courseSheetContainer">
        
        <!-- Sheet Drag Handle (Mobile) -->
        <div class="course-sheet-handle d-md-none"></div>

        <!-- Sheet Header -->
        <div class="course-sheet-header">
            <div>
                <span class="badge px-2.5 py-1 rounded-pill mb-1" style="background: rgba(254, 124, 3, 0.12); color: #fe7c03; font-size: 11px; font-weight: 700;">
                    <i class="fa-solid fa-graduation-cap me-1"></i> OFFICIAL CAREER PROGRAMS
                </span>
                <h5 class="fw-bold text-dark mb-0" style="font-size: 18px;">Choose Your Program</h5>
            </div>
            <button type="button" class="course-sheet-close-btn" id="courseSheetCloseBtn" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <p class="text-muted small px-1 pt-2 mb-2" style="font-size: 12px;">
            Select your preferred training track to lock batch slot &amp; practical training kit:
        </p>

        <!-- Course Cards List -->
        <div class="course-sheet-list">
            <?php foreach ($active_courses as $c): ?>
                <?php
                $meta = getCourseMeta($c['title']);
                $is_sel = ($preselected_id > 0 && $preselected_id === (int)$c['id']) || 
                          (!empty($preselected_name) && strcasecmp($c['title'], $preselected_name) === 0);
                ?>
                <div class="course-card-option <?= $is_sel ? 'selected' : '' ?>" 
                     data-id="<?= $c['id'] ?>" 
                     data-title="<?= htmlspecialchars($c['title']) ?>"
                     data-icon="<?= $meta['icon'] ?>"
                     data-badge="<?= $meta['badge'] ?>">
                    
                    <div class="d-flex align-items-center gap-3 flex-grow-1 overflow-hidden">
                        <div class="course-card-icon" style="background: <?= $meta['color'] ?>18; color: <?= $meta['color'] ?>;">
                            <i class="fa-solid <?= $meta['icon'] ?>"></i>
                        </div>
                        <div class="text-truncate">
                            <div class="d-flex align-items-center gap-2 mb-0.5">
                                <span class="badge rounded-pill fw-semibold" style="background: #f1f5f9; color: #475569; font-size: 10px;">
                                    <?= htmlspecialchars($meta['badge']) ?>
                                </span>
                            </div>
                            <h6 class="course-card-title mb-0 text-truncate"><?= htmlspecialchars($c['title']) ?></h6>
                            <span class="course-card-desc">Practical Training • 100% Placement Support</span>
                        </div>
                    </div>

                    <div class="course-card-radio flex-shrink-0">
                        <i class="fa-solid <?= $is_sel ? 'fa-circle-check text-success' : 'fa-circle text-muted' ?>"></i>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- Razorpay Official Checkout Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
// Toggle Password Visibility
function togglePasswordVisibility(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // State flags
    var isEmailVerified = false;
    var verifiedEmail = '';
    var uploadedPhotoPath = '';
    var otpTimerInterval = null;

    // Elements
    var step1Container = document.getElementById('step1Container');
    var step2Container = document.getElementById('step2Container');
    var stepBadge1 = document.getElementById('stepBadge1');
    var stepBadge2 = document.getElementById('stepBadge2');
    var step1Alert = document.getElementById('step1Alert');

    var candName = document.getElementById('candName');
    var nameFeedback = document.getElementById('nameFeedback');
    var candPhone = document.getElementById('candPhone');
    var phoneFeedback = document.getElementById('phoneFeedback');
    var candEmail = document.getElementById('candEmail');
    var emailFeedback = document.getElementById('emailFeedback');
    var emailVerifiedBadge = document.getElementById('emailVerifiedBadge');
    var btnSendOtp = document.getElementById('btnSendOtp');
    var otpBox = document.getElementById('otpBox');
    var otpInput = document.getElementById('otpInput');
    var btnVerifyOtp = document.getElementById('btnVerifyOtp');
    var otpTimer = document.getElementById('otpTimer');
    var otpMsg = document.getElementById('otpMsg');
    var btnResendOtp = document.getElementById('btnResendOtp');

    var candAadhaar = document.getElementById('candAadhaar');
    var aadhaarFeedback = document.getElementById('aadhaarFeedback');

    // Modern Course Picker Elements
    var candCourseSelect = document.getElementById('candCourseSelect');
    var courseFeedback = document.getElementById('courseFeedback');
    var coursePickerBtn = document.getElementById('coursePickerBtn');
    var pickerIcon = document.getElementById('pickerIcon');
    var pickerSubText = document.getElementById('pickerSubText');
    var pickerTitleText = document.getElementById('pickerTitleText');
    var pickerStatusBadge = document.getElementById('pickerStatusBadge');
    var courseSheetOverlay = document.getElementById('courseSheetOverlay');
    var courseSheetCloseBtn = document.getElementById('courseSheetCloseBtn');
    var courseOptions = document.querySelectorAll('.course-card-option');

    var candPassword = document.getElementById('candPassword');
    var passwordFeedback = document.getElementById('passwordFeedback');
    var candConfirmPassword = document.getElementById('candConfirmPassword');
    var confirmPasswordFeedback = document.getElementById('confirmPasswordFeedback');

    var candPhotoInput = document.getElementById('candPhotoInput');
    var avatarPreview = document.getElementById('avatarPreview');
    var photoStatusText = document.getElementById('photoStatusText');
    var candPhotoPath = document.getElementById('candPhotoPath');

    var agreeTerms = document.getElementById('agreeTerms');
    var btnContinueToStep2 = document.getElementById('btnContinueToStep2');
    var btnBackToStep1 = document.getElementById('btnBackToStep1');
    var btnPayNow = document.getElementById('btnPayNow');

    // Summary Elements in Step 2
    var sumName = document.getElementById('sumName');
    var sumContact = document.getElementById('sumContact');
    var sumAadhaar = document.getElementById('sumAadhaar');
    var sumCourse = document.getElementById('sumCourse');

    // Helper: Restrict numeric keydown
    function restrictNumericKeydown(e, maxDigits) {
        const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'];
        if (allowedKeys.includes(e.key)) return;
        if (e.ctrlKey || e.metaKey) return;
        if (!/^[0-9]$/.test(e.key)) {
            e.preventDefault();
            return;
        }
        const currentDigits = (e.target.value || '').replace(/\D/g, '');
        if (currentDigits.length >= maxDigits && !window.getSelection().toString()) {
            e.preventDefault();
        }
    }

    // -------------------------------------------------------------
    // 1. FULL NAME VALIDATION
    // -------------------------------------------------------------
    function validateName() {
        if (!candName) return;
        var val = candName.value.trim();
        if (val.length >= 2) {
            candName.style.borderColor = '#22c55e';
            candName.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.12)';
            if (nameFeedback) nameFeedback.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Valid Name</span>';
        } else if (val.length > 0) {
            candName.style.borderColor = '#fe7c03';
            candName.style.boxShadow = '';
            if (nameFeedback) nameFeedback.innerHTML = '<span class="text-muted">Enter Student\'s full name</span>';
        } else {
            candName.style.borderColor = '';
            candName.style.boxShadow = '';
            if (nameFeedback) nameFeedback.innerHTML = '<span class="text-muted">Student\'s legal name as per Aadhaar</span>';
        }
    }
    if (candName) {
        candName.addEventListener('input', validateName);
    }

    // -------------------------------------------------------------
    // 2. PHONE NUMBER (Strict numeric, Max 10 digits & LIVE GREEN BORDER)
    // -------------------------------------------------------------
    function formatPhone() {
        if (!candPhone) return;
        var digits = candPhone.value.replace(/\D/g, '');
        if (digits.length > 10) {
            digits = digits.substring(0, 10);
        }
        candPhone.value = digits;

        if (!phoneFeedback) return;

        if (digits.length === 10) {
            candPhone.style.borderColor = '#22c55e';
            candPhone.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.12)';
            phoneFeedback.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Valid 10-digit Mobile Number</span>';
        } else if (digits.length > 0) {
            candPhone.style.borderColor = '#fe7c03';
            candPhone.style.boxShadow = '';
            phoneFeedback.innerHTML = '<span class="text-muted"><i class="fa-solid fa-phone me-1"></i> ' + digits.length + ' of 10 digits (' + (10 - digits.length) + ' remaining)</span>';
        } else {
            candPhone.style.borderColor = '';
            candPhone.style.boxShadow = '';
            phoneFeedback.innerHTML = '<span class="text-muted">10-digit mobile number required</span>';
        }
    }

    if (candPhone) {
        candPhone.addEventListener('keydown', function(e) { restrictNumericKeydown(e, 10); });
        candPhone.addEventListener('input', formatPhone);
        candPhone.addEventListener('paste', function() { setTimeout(formatPhone, 10); });
        formatPhone();
    }

    // -------------------------------------------------------------
    // 3. EMAIL VALIDATION (Must be valid @gmail.com & LIVE GREEN BORDER)
    // -------------------------------------------------------------
    const gmailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/i;

    function validateEmail() {
        if (!candEmail || !emailFeedback) return;
        var val = candEmail.value.trim().toLowerCase();

        if (val === '') {
            candEmail.style.borderColor = '';
            candEmail.style.boxShadow = '';
            emailFeedback.innerHTML = '<span class="text-muted">Must be a valid @gmail.com address (OTP will be sent here).</span>';
            return;
        }

        if (gmailRegex.test(val)) {
            candEmail.style.borderColor = '#22c55e';
            candEmail.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.12)';
            if (isEmailVerified && val === verifiedEmail) {
                emailFeedback.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Email Verified via OTP</span>';
            } else {
                emailFeedback.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Valid Gmail Address</span>';
            }
        } else {
            candEmail.style.borderColor = '#ef4444';
            candEmail.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.12)';
            if (!val.includes('@')) {
                emailFeedback.innerHTML = '<span class="text-danger fw-semibold"><i class="fa-solid fa-circle-xmark me-1"></i> Missing "@gmail.com" (e.g. rahul@gmail.com)</span>';
            } else if (!val.endsWith('@gmail.com')) {
                emailFeedback.innerHTML = '<span class="text-danger fw-semibold"><i class="fa-solid fa-circle-xmark me-1"></i> Only @gmail.com addresses are accepted</span>';
            } else {
                emailFeedback.innerHTML = '<span class="text-danger fw-semibold"><i class="fa-solid fa-circle-xmark me-1"></i> Please enter a valid username before @gmail.com</span>';
            }
        }
    }

    if (candEmail) {
        candEmail.addEventListener('input', function() {
            if (isEmailVerified && candEmail.value.trim().toLowerCase() !== verifiedEmail) {
                isEmailVerified = false;
                verifiedEmail = '';
                emailVerifiedBadge.style.display = 'none';
                btnSendOtp.style.display = 'inline-block';
                btnSendOtp.innerHTML = 'Verify Email';
            }
            validateEmail();
        });
        candEmail.addEventListener('change', validateEmail);
        validateEmail();
    }

    // -------------------------------------------------------------
    // 4. AADHAAR NUMBER FORMATTING (XXXX XXXX XXXX & LIVE GREEN BORDER)
    // -------------------------------------------------------------
    function formatAadhaar() {
        if (!candAadhaar) return;
        var digits = candAadhaar.value.replace(/\D/g, '');
        if (digits.length > 12) {
            digits = digits.substring(0, 12);
        }

        var chunks = [];
        for (var i = 0; i < digits.length; i += 4) {
            chunks.push(digits.substring(i, i + 4));
        }
        candAadhaar.value = chunks.join(' ');

        if (!aadhaarFeedback) return;

        if (digits.length === 12) {
            candAadhaar.style.borderColor = '#22c55e';
            candAadhaar.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.12)';
            aadhaarFeedback.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Valid 12-digit Aadhaar Card</span>';
        } else if (digits.length > 0) {
            candAadhaar.style.borderColor = '#fe7c03';
            candAadhaar.style.boxShadow = '';
            aadhaarFeedback.innerHTML = '<span class="text-muted"><i class="fa-solid fa-id-card me-1"></i> ' + digits.length + ' of 12 digits (' + (12 - digits.length) + ' remaining)</span>';
        } else {
            candAadhaar.style.borderColor = '';
            candAadhaar.style.boxShadow = '';
            aadhaarFeedback.innerHTML = '<span class="text-muted">12 digits required (formatted in groups of 4)</span>';
        }
    }

    if (candAadhaar) {
        candAadhaar.addEventListener('keydown', function(e) { restrictNumericKeydown(e, 12); });
        candAadhaar.addEventListener('input', formatAadhaar);
        candAadhaar.addEventListener('paste', function() { setTimeout(formatAadhaar, 10); });
        formatAadhaar();
    }

    // -------------------------------------------------------------
    // 5. MODERN COURSE PICKER & BOTTOM SHEET LOGIC
    // -------------------------------------------------------------
    function openCourseSheet() {
        if (!courseSheetOverlay) return;
        courseSheetOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeCourseSheet() {
        if (!courseSheetOverlay) return;
        courseSheetOverlay.style.display = 'none';
        document.body.style.overflow = '';
    }

    if (coursePickerBtn) {
        coursePickerBtn.addEventListener('click', openCourseSheet);
        coursePickerBtn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openCourseSheet();
            }
        });
    }

    if (courseSheetCloseBtn) {
        courseSheetCloseBtn.addEventListener('click', closeCourseSheet);
    }

    if (courseSheetOverlay) {
        courseSheetOverlay.addEventListener('click', function(e) {
            if (e.target === courseSheetOverlay) {
                closeCourseSheet();
            }
        });
    }

    function selectCourse(cardElem) {
        if (!cardElem) return;
        var courseId = cardElem.getAttribute('data-id');
        var courseTitle = cardElem.getAttribute('data-title');
        var courseIcon = cardElem.getAttribute('data-icon') || 'fa-graduation-cap';
        var courseBadge = cardElem.getAttribute('data-badge') || 'Career Track';

        // Update active state on options
        courseOptions.forEach(function(opt) {
            opt.classList.remove('selected');
            var r = opt.querySelector('.course-card-radio i');
            if (r) r.className = 'fa-solid fa-circle text-muted';
        });
        cardElem.classList.add('selected');
        var radio = cardElem.querySelector('.course-card-radio i');
        if (radio) radio.className = 'fa-solid fa-circle-check text-success';

        // Update Hidden Select
        if (candCourseSelect) {
            candCourseSelect.value = courseId;
            candCourseSelect.dispatchEvent(new Event('change'));
        }

        // Update Picker Trigger Box
        if (coursePickerBtn) {
            coursePickerBtn.classList.add('is-selected');
            coursePickerBtn.style.borderColor = '#22c55e';
            coursePickerBtn.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.12)';
        }
        if (pickerTitleText) pickerTitleText.textContent = courseTitle;
        if (pickerSubText) pickerSubText.textContent = courseBadge + ' • Selected';
        if (pickerStatusBadge) {
            pickerStatusBadge.textContent = 'Selected ✔';
            pickerStatusBadge.className = 'course-picker-status-badge text-success border-success-subtle bg-success-subtle';
        }
        if (pickerIcon) {
            pickerIcon.className = 'fa-solid ' + courseIcon;
        }

        if (courseFeedback) {
            courseFeedback.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> ' + courseTitle + ' Selected</span>';
        }

        // Close sheet
        closeCourseSheet();
    }

    courseOptions.forEach(function(opt) {
        opt.addEventListener('click', function() {
            selectCourse(this);
        });
    });

    // Check if preselected on page load
    if (candCourseSelect && candCourseSelect.value) {
        var preElem = document.querySelector('.course-card-option[data-id="' + candCourseSelect.value + '"]');
        if (preElem) {
            selectCourse(preElem);
        }
    }

    // -------------------------------------------------------------
    // 6. PASSWORD & CONFIRM PASSWORD MATCHING (LIVE GREEN BORDER)
    // -------------------------------------------------------------
    function checkPasswordMatch() {
        if (!candPassword || !candConfirmPassword) return;
        var pass = candPassword.value;
        var confirm = candConfirmPassword.value;

        // Check password length
        if (passwordFeedback) {
            if (pass.length === 0) {
                candPassword.style.borderColor = '';
                candPassword.style.boxShadow = '';
                passwordFeedback.innerHTML = '<span class="text-muted">Minimum 6 characters required</span>';
            } else if (pass.length < 6) {
                candPassword.style.borderColor = '#ef4444';
                candPassword.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.12)';
                passwordFeedback.innerHTML = '<span class="text-danger fw-semibold"><i class="fa-solid fa-circle-xmark me-1"></i> Too short (' + pass.length + '/6 characters required)</span>';
            } else {
                candPassword.style.borderColor = '#22c55e';
                candPassword.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.12)';
                passwordFeedback.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Password length OK (6+ characters)</span>';
            }
        }

        // Check confirm password match
        if (confirmPasswordFeedback) {
            if (confirm.length === 0) {
                candConfirmPassword.style.borderColor = '';
                candConfirmPassword.style.boxShadow = '';
                confirmPasswordFeedback.innerHTML = '<span class="text-muted">Type password again to match</span>';
            } else if (pass !== '' && confirm === pass) {
                candConfirmPassword.style.borderColor = '#22c55e';
                candConfirmPassword.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.15)';
                confirmPasswordFeedback.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Passwords matched</span>';
            } else {
                candConfirmPassword.style.borderColor = '#ef4444';
                candConfirmPassword.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.15)';
                confirmPasswordFeedback.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Passwords do not match (Mismatch)</span>';
            }
        }
    }

    if (candPassword && candConfirmPassword) {
        candPassword.addEventListener('input', checkPasswordMatch);
        candPassword.addEventListener('keyup', checkPasswordMatch);
        candConfirmPassword.addEventListener('input', checkPasswordMatch);
        candConfirmPassword.addEventListener('keyup', checkPasswordMatch);
        checkPasswordMatch();
    }

    // -------------------------------------------------------------
    // 7. PHOTO ASYNC UPLOAD & PREVIEW
    // -------------------------------------------------------------
    if (candPhotoInput) {
        candPhotoInput.addEventListener('change', function() {
            var file = this.files[0];
            if (!file) return;

            // Preview immediately
            var reader = new FileReader();
            reader.onload = function(e) {
                if (avatarPreview) avatarPreview.src = e.target.result;
            };
            reader.readAsDataURL(file);

            // Upload via AJAX
            if (photoStatusText) photoStatusText.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-primary"></i> Uploading photo...';
            var formData = new FormData();
            formData.append('photo', file);

            fetch('ajax-upload-photo.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    uploadedPhotoPath = data.file_path;
                    if (candPhotoPath) candPhotoPath.value = data.file_path;
                    if (photoStatusText) photoStatusText.innerHTML = '<span class="text-success"><i class="fa-solid fa-check"></i> Photo uploaded successfully</span>';
                } else {
                    if (photoStatusText) photoStatusText.innerHTML = '<span class="text-danger">' + (data.message || 'Upload failed') + '</span>';
                }
            })
            .catch(function(err) {
                console.error(err);
                if (photoStatusText) photoStatusText.innerHTML = '<span class="text-danger">Upload error.</span>';
            });
        });
    }

    // -------------------------------------------------------------
    // 8. EMAIL OTP SYSTEM
    // -------------------------------------------------------------
    function startOtpTimer(seconds) {
        if (otpTimerInterval) clearInterval(otpTimerInterval);
        var remaining = seconds;
        btnResendOtp.style.display = 'none';
        otpTimer.style.display = 'inline';

        otpTimerInterval = setInterval(function() {
            remaining--;
            if (remaining <= 0) {
                clearInterval(otpTimerInterval);
                otpTimer.style.display = 'none';
                btnResendOtp.style.display = 'inline';
            } else {
                var m = Math.floor(remaining / 60);
                var s = remaining % 60;
                otpTimer.textContent = (m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s);
            }
        }, 1000);
    }

    // Trigger Send OTP
    btnSendOtp.addEventListener('click', function() {
        var email = candEmail.value.trim().toLowerCase();
        var name = candName.value.trim() || 'Student';

        if (!email || !gmailRegex.test(email)) {
            alert('Please enter a valid @gmail.com address first.');
            candEmail.focus();
            return;
        }

        btnSendOtp.disabled = true;
        btnSendOtp.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';

        fetch('ajax-verify-email.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'send_otp', email: email, name: name })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            btnSendOtp.disabled = false;
            btnSendOtp.innerHTML = 'Resend';

            if (data.success) {
                otpBox.style.display = 'block';
                otpMsg.style.display = 'none';
                otpInput.value = '';
                otpInput.focus();
                startOtpTimer(60);
            } else {
                alert(data.message || 'Could not send OTP.');
            }
        })
        .catch(function(err) {
            console.error(err);
            btnSendOtp.disabled = false;
            btnSendOtp.innerHTML = 'Verify Email';
            alert('Error sending OTP. Please check your connection.');
        });
    });

    // Resend OTP
    btnResendOtp.addEventListener('click', function(e) {
        e.preventDefault();
        btnSendOtp.click();
    });

    // Trigger Verify OTP
    btnVerifyOtp.addEventListener('click', function() {
        var email = candEmail.value.trim().toLowerCase();
        var otp = otpInput.value.trim();

        if (!otp || otp.length !== 6) {
            otpMsg.textContent = 'Please enter the 6-digit OTP.';
            otpMsg.style.display = 'block';
            return;
        }

        btnVerifyOtp.disabled = true;
        btnVerifyOtp.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        fetch('ajax-verify-email.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'verify_otp', email: email, otp: otp })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            btnVerifyOtp.disabled = false;
            btnVerifyOtp.innerHTML = 'Verify OTP';

            if (data.success) {
                isEmailVerified = true;
                verifiedEmail = email;
                otpBox.style.display = 'none';
                btnSendOtp.style.display = 'none';
                candEmail.readOnly = true;
                emailVerifiedBadge.style.display = 'inline-flex';
                candEmail.style.borderColor = '#22c55e';
                candEmail.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.12)';
                if (emailFeedback) emailFeedback.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Email Verified via OTP</span>';
                if (otpTimerInterval) clearInterval(otpTimerInterval);
            } else {
                otpMsg.textContent = data.message || 'Invalid OTP.';
                otpMsg.style.display = 'block';
            }
        })
        .catch(function(err) {
            console.error(err);
            btnVerifyOtp.disabled = false;
            btnVerifyOtp.innerHTML = 'Verify OTP';
            otpMsg.textContent = 'Verification error. Try again.';
            otpMsg.style.display = 'block';
        });
    });

    // -------------------------------------------------------------
    // 9. STEP 1 VALIDATION & PROCEED TO STEP 2
    // -------------------------------------------------------------
    function showError(msg, elem) {
        step1Alert.textContent = msg;
        step1Alert.style.display = 'block';
        if (elem) elem.focus();
    }

    btnContinueToStep2.addEventListener('click', function() {
        step1Alert.style.display = 'none';

        var name = candName.value.trim();
        var phone = candPhone.value.replace(/\D/g, '');
        var email = candEmail.value.trim().toLowerCase();
        var aadhaar = candAadhaar.value.replace(/\D/g, '');
        var pass = candPassword.value;
        var confirmPass = candConfirmPassword.value;
        var courseId = candCourseSelect.value;
        var courseTitle = candCourseSelect.options[candCourseSelect.selectedIndex] ? candCourseSelect.options[candCourseSelect.selectedIndex].getAttribute('data-title') : '';

        // Validations
        if (!name) {
            showError('Please enter your full name.', candName);
            return;
        }
        if (phone.length !== 10) {
            showError('Mobile number must be exactly 10 digits.', candPhone);
            return;
        }
        if (!email || !gmailRegex.test(email)) {
            showError('Please enter a valid @gmail.com address.', candEmail);
            return;
        }
        if (!isEmailVerified) {
            showError('Please click "Verify Email" to verify your email via OTP before proceeding.', candEmail);
            return;
        }
        if (aadhaar.length !== 12) {
            showError('Aadhaar number must be exactly 12 digits.', candAadhaar);
            return;
        }
        if (!courseId) {
            if (coursePickerBtn) {
                coursePickerBtn.style.borderColor = '#ef4444';
                coursePickerBtn.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.15)';
            }
            showError('Please select a course program before proceeding.', coursePickerBtn);
            openCourseSheet();
            return;
        }
        if (!pass || pass.length < 6) {
            showError('Password must be at least 6 characters long.', candPassword);
            return;
        }
        if (pass !== confirmPass) {
            showError('Password and confirm password do not match.', candConfirmPassword);
            return;
        }
        if (!agreeTerms.checked) {
            showError('Please accept the Terms & Conditions.', agreeTerms);
            return;
        }

        // Fill Step 2 Summary
        sumName.textContent = name;
        sumContact.textContent = phone + ' | ' + email;
        sumAadhaar.textContent = 'XXXX-XXXX-' + aadhaar.slice(-4);
        sumCourse.textContent = courseTitle;

        // Transition to Step 2
        step1Container.style.display = 'none';
        step2Container.style.display = 'block';

        // Mobile optimization: Hide left banner on mobile so Fee Submission is immediately at the top
        var regMainRow = document.getElementById('regMainRow');
        if (regMainRow) {
            regMainRow.classList.add('step2-active');
        }

        if (stepBadge1 && stepBadge2) {
            stepBadge1.className = 'badge px-3 py-2 rounded-pill fw-semibold text-success bg-success-subtle border border-success-subtle';
            stepBadge1.innerHTML = '<i class="fa-solid fa-check me-1"></i> Details Done';
            stepBadge2.className = 'badge px-3 py-2 rounded-pill fw-semibold';
            stepBadge2.style.background = '#fff7ed';
            stepBadge2.style.color = '#fe7c03';
            stepBadge2.style.border = '1px solid #fed7aa';
            stepBadge2.innerHTML = '<i class="fa-solid fa-circle-dot me-1"></i> Step 2: Seat';
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Go back to Step 1
    btnBackToStep1.addEventListener('click', function() {
        step2Container.style.display = 'none';
        step1Container.style.display = 'block';

        // Restore left banner on mobile
        var regMainRow = document.getElementById('regMainRow');
        if (regMainRow) {
            regMainRow.classList.remove('step2-active');
        }

        if (stepBadge1 && stepBadge2) {
            stepBadge1.className = 'badge px-3 py-2 rounded-pill fw-semibold';
            stepBadge1.style.background = '#fff7ed';
            stepBadge1.style.color = '#fe7c03';
            stepBadge1.style.border = '1px solid #fed7aa';
            stepBadge1.innerHTML = '<i class="fa-solid fa-circle-dot me-1"></i> Step 1: Details';

            stepBadge2.className = 'badge px-3 py-2 rounded-pill fw-semibold text-muted bg-light border';
            stepBadge2.style.background = '';
            stepBadge2.style.color = '';
            stepBadge2.style.border = '';
            stepBadge2.innerHTML = 'Step 2: Seat';
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // -------------------------------------------------------------
    // 10. STEP 2: RAZORPAY CHECKOUT & FINAL REGISTRATION
    // -------------------------------------------------------------
    btnPayNow.addEventListener('click', function() {
        var name = candName.value.trim();
        var phone = candPhone.value.replace(/\D/g, '');
        var email = candEmail.value.trim().toLowerCase();
        var aadhaar = candAadhaar.value.replace(/\D/g, '');
        var pass = candPassword.value;
        var courseId = candCourseSelect.value;
        var courseTitle = candCourseSelect.options[candCourseSelect.selectedIndex] ? candCourseSelect.options[candCourseSelect.selectedIndex].getAttribute('data-title') : '';
        var photoPath = uploadedPhotoPath || candPhotoPath.value;

        var options = {
            "key": "<?= htmlspecialchars($key_id) ?>",
            "amount": "<?= $amount_paise ?>",
            "currency": "INR",
            "name": "<?= htmlspecialchars($company_name) ?>",
            "description": "Seat Reservation - " + courseTitle,
            "image": "<?= htmlspecialchars($site_logo) ?>",
            "prefill": {
                "name": name,
                "email": email,
                "contact": phone
            },
            "theme": {
                "color": "#fe7c03"
            },
            "handler": function (response) {
                // Post payment and complete registration
                btnPayNow.disabled = true;
                btnPayNow.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Confirming Seat &amp; Registering...</span>';

                fetch('process-seat-registration.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        razorpay_payment_id: response.razorpay_payment_id,
                        name: name,
                        email: email,
                        phone: phone,
                        aadhaar: aadhaar,
                        password: pass,
                        course_id: courseId,
                        course_title: courseTitle,
                        photo: photoPath,
                        amount: "<?= $seat_fee ?>"
                    })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        window.location.href = data.redirect || 'registration-success.php';
                    } else {
                        alert('Payment completed, but error confirming: ' + (data.message || 'Please contact support.'));
                        btnPayNow.disabled = false;
                        btnPayNow.innerHTML = '<i class="fa-solid fa-lock"></i> <span>Pay ₹<?= number_format($seat_fee, 0) ?> &amp; Confirm Seat</span>';
                    }
                })
                .catch(function(err) {
                    console.error(err);
                    alert('Network error while recording registration. Please check your connection.');
                    btnPayNow.disabled = false;
                    btnPayNow.innerHTML = '<i class="fa-solid fa-lock"></i> <span>Pay ₹<?= number_format($seat_fee, 0) ?> &amp; Confirm Seat</span>';
                });
            }
        };

        var rzp = new Razorpay(options);
        rzp.on('payment.failed', function (resp) {
            alert("Payment failed: " + resp.error.description);
        });
        rzp.open();
    });

});
</script>

<?php include "footer.php"; ?>

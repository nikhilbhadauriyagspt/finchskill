<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

// Require Student Login
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header("Location: student-login.php");
    exit;
}

$student_id = (int)($_SESSION['student_db_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: student-logout.php");
    exit;
}

// Fetch Enrolled Admissions (Only Verified & Fee-Paid)
$adm_stmt = $pdo->prepare("SELECT * FROM admissions WHERE (student_id = ? OR email = ?) AND (status = 'Approved' OR payment_status IN ('Fully Paid', 'Partial Paid')) ORDER BY id DESC");
$adm_stmt->execute([$student['student_id'], $student['email']]);
$my_admissions = $adm_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Installments and Payment History
$ins_stmt = $pdo->prepare("SELECT * FROM student_installments WHERE student_db_id = ? OR student_id = ? ORDER BY due_date ASC, id ASC");
$ins_stmt->execute([$student_id, $student['student_id']]);
$my_installments = $ins_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Next Due Installment
$next_due_stmt = $pdo->prepare("SELECT * FROM student_installments WHERE (student_db_id = ? OR student_id = ?) AND status = 'Pending' ORDER BY due_date ASC LIMIT 1");
$next_due_stmt->execute([$student_id, $student['student_id']]);
$next_due = $next_due_stmt->fetch(PDO::FETCH_ASSOC);

// Razorpay & Fee Settings
$s_stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'razorpay_%' OR setting_key = 'seat_booking_fee'");
$gateway_settings = $s_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$razorpay_mode = $gateway_settings['razorpay_mode'] ?? 'test';
$razorpay_key = ($razorpay_mode === 'live') ? ($gateway_settings['razorpay_live_key_id'] ?? '') : ($gateway_settings['razorpay_test_key_id'] ?? 'rzp_test_1DP5mmOlF5G5ag');
$admin_seat_fee = (float)($gateway_settings['seat_booking_fee'] ?? 999.00);
$display_seat_fee = (float)($student['payment_amount'] ?? 0) > 0 ? (float)$student['payment_amount'] : $admin_seat_fee;

$page_title = "Student Dashboard - " . htmlspecialchars($student['name']);
include "header.php";
?>

<style>
.profile-section {
    padding: 30px 0 65px;
    background: #f8fafc;
    min-height: 85vh;
}
.profile-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02) !important;
}
.profile-header-strip {
    background: #0f172a;
    border-radius: 10px;
    padding: 18px 20px;
    color: #ffffff;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 22px;
}
.profile-avatar-lg {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    object-fit: cover;
    border: 2.5px solid #fe7c03;
    background: #ffffff;
    flex-shrink: 0;
}
.id-chip {
    background: rgba(254, 124, 3, 0.15);
    color: #fe7c03;
    border: 1px solid rgba(254, 124, 3, 0.35);
    padding: 2.5px 8px;
    border-radius: 5px;
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.3px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.info-tile {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 14px;
    height: 100%;
}
.info-tile-label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #64748b;
    margin-bottom: 3px;
    display: block;
}
.info-tile-val {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    word-break: break-word;
}
.due-alert-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

/* RESPONSIVE MOBILE CARDS FOR ADMISSIONS & INSTALLMENTS */
.admission-mobile-card,
.installment-mobile-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.admission-mobile-card:hover,
.installment-mobile-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.metric-grid-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 10px;
    text-align: center;
}
.metric-grid-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #64748b;
    display: block;
    margin-bottom: 2px;
}
.metric-grid-val {
    font-size: 13px;
    font-weight: 700;
    font-family: var(--bs-font-monospace);
    color: #0f172a;
}

/* MEDIA QUERIES FOR MOBILE (<768px and <576px) */
@media (max-width: 767.98px) {
    .profile-section {
        padding: 18px 0 50px;
    }
    .profile-card {
        padding: 16px 12px;
        border-radius: 12px;
    }
    .profile-header-strip {
        padding: 14px 14px;
        gap: 12px;
    }
    .profile-avatar-lg {
        width: 48px;
        height: 48px;
    }
    .profile-header-strip h4 {
        font-size: 16px !important;
    }
    .header-action-btns {
        width: 100%;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
        margin-top: 4px;
    }
    .header-action-btns .btn {
        flex: 1;
        text-align: center;
        justify-content: center;
    }
    .due-alert-box {
        flex-direction: column;
        align-items: stretch;
        padding: 12px 14px;
    }
    .due-alert-box button {
        width: 100%;
        padding: 9px 14px;
        font-size: 13px;
        margin-top: 4px;
    }
    .seat-status-box {
        flex-direction: column;
        align-items: stretch !important;
    }
    .seat-status-box .btn {
        width: 100% !important;
        text-align: center;
        justify-content: center;
        margin-top: 8px;
        padding: 10px 14px;
        font-size: 13px;
    }
}

@media (max-width: 575.98px) {
    .info-tile {
        padding: 10px 11px;
    }
    .info-tile-label {
        font-size: 10px;
    }
    .info-tile-val {
        font-size: 12.5px;
    }
    #printableSlip {
        padding: 12px !important;
    }
    #printableSlip .table td {
        display: block;
        width: 100% !important;
        padding: 2px 0;
    }
    #printableSlip .table td:first-child {
        font-weight: 600;
        color: #64748b;
        font-size: 11px;
    }
    #printableSlip .table td:last-child {
        margin-bottom: 6px;
    }
}

@media print {
    body * { visibility: hidden; }
    #printableSlip, #printableSlip * { visibility: visible; }
    #printableSlip { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
}
</style>

<section class="profile-section">
    <div class="container">

        <!-- BREADCRUMB -->
        <nav aria-label="breadcrumb" class="mb-3 no-print">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-secondary"><i class="fa-solid fa-house me-1"></i>Home</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Student Dashboard</li>
            </ol>
        </nav>

        <!-- SUCCESS NOTIFICATION ALERTS -->
        <?php if (isset($_GET['payment']) && $_GET['payment'] === 'success'): ?>
            <?php $success_ref = trim($_GET['ref'] ?? ''); ?>
            <div class="alert alert-success alert-dismissible fade show mb-3 rounded-3 shadow-sm border border-success-subtle no-print p-3" role="alert">
                <div class="d-flex align-items-start gap-2.5">
                    <div class="d-flex align-items-center justify-content-center bg-success text-white rounded-circle flex-shrink-0 mt-0.5" style="width: 34px; height: 34px;">
                        <i class="fa-solid fa-circle-check fs-6"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold text-success mb-1" style="font-size: 14.5px;">Payment Confirmed Successfully!</h6>
                        <p class="mb-2 text-secondary small" style="line-height: 1.4;">
                            Your fee payment has been verified and recorded in your student account. An official confirmation email with receipt has been dispatched.
                            <?php if (!empty($success_ref)): ?>
                                <span class="d-block mt-1">Receipt Reference: <strong class="text-dark font-monospace"><?= htmlspecialchars($success_ref) ?></strong></span>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($success_ref)): ?>
                            <a href="receipt.php?ref=<?= urlencode($success_ref) ?>" target="_blank" class="btn btn-sm btn-success px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5" style="border-radius: 6px; font-size: 12px;">
                                <i class="fa-solid fa-receipt"></i>
                                <span>View / Download Official Receipt</span>
                                <i class="fa-solid fa-arrow-up-right-from-square small ms-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn-close btn-sm shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- UPCOMING DUE DATE ALERT NOTIFICATION -->
        <?php if ($next_due): ?>
            <?php
            $today = new DateTime();
            $due_dt = new DateTime($next_due['due_date']);
            $days_left = (int)$today->diff($due_dt)->format("%r%a");
            $is_overdue = ($days_left < 0);
            ?>
            <div class="due-alert-box no-print" style="<?= $is_overdue ? 'background:#fef2f2; border-color:#fecaca;' : '' ?>">
                <div class="d-flex align-items-center gap-3">
                    <i class="fa-solid <?= $is_overdue ? 'fa-triangle-exclamation text-danger' : 'fa-bell text-warning' ?> fs-4"></i>
                    <div>
                        <strong class="<?= $is_overdue ? 'text-danger' : 'text-dark' ?>" style="font-size: 13.5px;">
                            <?= $is_overdue ? 'Overdue Installment Reminder' : 'Upcoming Fee Installment Alert' ?>: <?= htmlspecialchars($next_due['installment_title']) ?>
                        </strong>
                        <div class="text-muted" style="font-size: 12px;">
                            Course: <strong><?= htmlspecialchars($next_due['course_title']) ?></strong> &bull; Amount: <strong class="text-dark font-monospace">₹<?= number_format($next_due['installment_amount'], 2) ?></strong> &bull; 
                            Due Date: <strong class="<?= $is_overdue ? 'text-danger' : 'text-dark' ?>"><?= date('d M Y', strtotime($next_due['due_date'])) ?></strong> 
                            (<?= $is_overdue ? abs($days_left) . ' days overdue' : ($days_left === 0 ? 'Due Today' : 'in ' . $days_left . ' days') ?>)
                        </div>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-success px-3 py-1.5 fw-semibold" onclick="paySingleInstallment(<?= $next_due['id'] ?>, <?= $next_due['installment_amount'] ?>, '<?= addslashes($next_due['installment_title']) ?>', '<?= addslashes($next_due['course_title']) ?>')">
                        <i class="fa-solid fa-lock me-1"></i> Pay ₹<?= number_format($next_due['installment_amount'], 0) ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="profile-card">

            <!-- HEADER BANNER -->
            <div class="profile-header-strip">
                <div class="d-flex align-items-center gap-3">
                    <?php if (!empty($student['photo']) && file_exists(__DIR__ . '/' . $student['photo'])): ?>
                        <img src="<?= htmlspecialchars($student['photo']) ?>" alt="Passport Photo" class="profile-avatar-lg">
                    <?php else: ?>
                        <div class="profile-avatar-lg d-flex align-items-center justify-content-center text-dark bg-white">
                            <i class="fa-solid fa-user fs-4"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h4 class="fw-bold text-white mb-0.5" style="font-size: 18px;"><?= htmlspecialchars($student['name']) ?></h4>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <?php if (!empty($student['registration_id'])): ?>
                                <span class="badge border font-monospace text-white px-2 py-1 rounded-1" style="background: rgba(254, 124, 3, 0.35); border-color: rgba(254, 124, 3, 0.6) !important; font-size: 11px;">
                                    <i class="fa-solid fa-hashtag me-0.5"></i> <?= htmlspecialchars($student['registration_id']) ?>
                                </span>
                            <?php endif; ?>
                            <span class="id-chip font-monospace">
                                <i class="fa-solid fa-id-badge"></i> <?= htmlspecialchars($student['student_id']) ?>
                            </span>
                            <span class="badge bg-success text-white px-2 py-0.5 rounded-1" style="font-size:10px;">
                                <i class="fa-solid fa-circle-check me-0.5"></i> Verified Student
                            </span>
                        </div>
                    </div>
                </div>

                <div class="header-action-btns no-print">
                    <button type="button" onclick="window.print()" class="btn btn-light btn-sm px-2.5 py-1.5 fw-semibold" style="font-size: 12px; border-radius: 6px;">
                        <i class="fa-solid fa-print me-1"></i> Print ID Slip
                    </button>
                    <a href="student-logout.php" class="btn btn-outline-light btn-sm px-2.5 py-1.5 fw-semibold" style="font-size: 12px; border-radius: 6px;">
                        <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Logout
                    </a>
                </div>
            </div>

            <!-- KEY INFO TILES (2x2 Grid on Mobile) -->
            <div class="row g-2 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="info-tile">
                        <span class="info-tile-label"><i class="fa-solid fa-phone me-1"></i> Phone / WhatsApp</span>
                        <div class="info-tile-val font-monospace"><?= htmlspecialchars($student['phone']) ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="info-tile">
                        <span class="info-tile-label"><i class="fa-solid fa-envelope me-1"></i> Email</span>
                        <div class="info-tile-val" style="font-size: 12px; word-break: break-all;"><?= htmlspecialchars($student['email']) ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="info-tile">
                        <span class="info-tile-label"><i class="fa-solid fa-fingerprint me-1"></i> Aadhaar (Last 4)</span>
                        <div class="info-tile-val font-monospace">XXXX-XXXX-<?= substr($student['aadhaar'], -4) ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="info-tile">
                        <span class="info-tile-label"><i class="fa-solid fa-calendar me-1"></i> Registered On</span>
                        <div class="info-tile-val"><?= date('d M Y', strtotime($student['created_at'])) ?></div>
                    </div>
                </div>
            </div>

            <!-- 0. SEAT RESERVATION & PREFERRED PROGRAM STATUS -->
            <div class="mb-4">
                <?php 
                $has_admission = !empty($my_admissions);
                $is_cancelled = in_array($student['seat_status'], ['Cancelled', 'Expired']);
                $is_paid_seat = ($student['payment_status'] === 'Paid' || in_array($student['seat_status'], ['Confirmed', 'Reserved']));
                $valid_ts = !empty($student['seat_valid_until']) ? strtotime($student['seat_valid_until']) : null;
                $now_ts = time();
                $is_expired_by_time = ($valid_ts !== null && $valid_ts <= $now_ts && !$has_admission);

                // Auto-sync if time has passed
                if ($is_paid_seat && $is_expired_by_time && !$is_cancelled) {
                    $pdo->prepare("UPDATE students SET seat_status = 'Cancelled', seat_cancelled_at = NOW() WHERE id = ?")->execute([$student['id']]);
                    $student['seat_status'] = 'Cancelled';
                    $is_cancelled = true;
                    $is_paid_seat = false;
                }
                ?>

                <?php if ($has_admission): ?>
                    <!-- Admission Completed & Active -->
                    <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between flex-wrap gap-3 seat-status-box" style="background: #f0fdf4; border-color: #bbf7d0 !important;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center text-white rounded-circle flex-shrink-0" style="width: 44px; height: 44px; background: #16a34a;">
                                <i class="fa-solid fa-graduation-cap fs-5"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <strong class="text-dark" style="font-size: 14.5px;">Course Admission Active &amp; Enrolled</strong>
                                    <span class="badge bg-success text-white px-2 py-0.5 rounded-pill" style="font-size: 10px;">
                                        <i class="fa-solid fa-circle-check me-0.5"></i> 100% Seat Fee Adjusted
                                    </span>
                                </div>
                                <div class="text-muted small mt-0.5">
                                    Enrolled Course: <strong class="text-dark"><?= htmlspecialchars($student['booked_course'] ?: ($my_admissions[0]['course'] ?? 'Active Program')) ?></strong>
                                    <?php if ((float)$student['payment_amount'] > 0): ?>
                                        &bull; Seat Fee of ₹<?= number_format($student['payment_amount'], 0) ?> has been deducted from your admission fee.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="no-print w-100 w-md-auto d-flex flex-wrap gap-2">
                            <?php if (!empty($my_admissions)): ?>
                                <a href="enrollment-slip.php?id=<?= $my_admissions[0]['id'] ?>" class="btn btn-sm btn-success px-3 py-1.5 fw-semibold" style="font-size: 12px; border-radius: 6px;">
                                    <i class="fa-solid fa-file-pdf me-1"></i> Download Enrollment Slip (PDF)
                                </a>
                            <?php elseif (!empty($student['payment_id'])): ?>
                                <a href="registration-success.php" class="btn btn-sm btn-outline-success px-3 py-1.5 fw-semibold" style="font-size: 12px; border-radius: 6px;">
                                    <i class="fa-solid fa-file-pdf me-1"></i> View Seat Slip (PDF)
                                </a>
                            <?php endif; ?>
                            <span class="badge bg-white text-success border border-success px-3 py-2 fw-semibold" style="font-size: 12px; border-radius: 6px;">
                                <i class="fa-solid fa-shield-check me-1"></i> Seat Confirmed
                            </span>
                        </div>
                    </div>

                <?php elseif ($is_cancelled): ?>
                    <!-- Seat Expired / Cancelled -->
                    <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between flex-wrap gap-3 seat-status-box" style="background: #fef2f2; border-color: #fecaca !important;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center text-white rounded-circle flex-shrink-0" style="width: 44px; height: 44px; background: #dc2626;">
                                <i class="fa-solid fa-clock-rotate-left fs-5"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <strong class="text-danger" style="font-size: 14.5px;">Seat Reservation Expired / Cancelled</strong>
                                    <span class="badge bg-danger text-white px-2 py-0.5 rounded-pill" style="font-size: 10px;">
                                        7-Day Validity Ended
                                    </span>
                                </div>
                                <div class="text-muted small mt-1">
                                    Your 7-day seat reservation for <strong class="text-dark"><?= htmlspecialchars($student['booked_course'] ?: 'Selected Course') ?></strong> expired on <span class="fw-semibold text-danger"><?= !empty($student['seat_valid_until']) ? date('d M Y, h:i A', strtotime($student['seat_valid_until'])) : 'due date' ?></span> because admission wasn't finalized.
                                    <br><span class="text-secondary">To reactivate your seat and claim your 100% fee adjustment, please contact our admission counselor.</span>
                                </div>
                            </div>
                        </div>
                        <div class="no-print w-100 w-md-auto d-flex gap-2">
                            <a href="contact-us.php" class="btn btn-sm btn-outline-danger px-3 py-2 fw-semibold" style="font-size: 12px; border-radius: 6px;">
                                <i class="fa-solid fa-headset me-1"></i> Contact Counselor
                            </a>
                            <a href="seat-reservation.php" class="btn btn-sm btn-danger px-3 py-2 fw-bold text-white" style="background: #dc2626; border-color: #dc2626; font-size: 12px; border-radius: 6px;">
                                <i class="fa-solid fa-rotate-right me-1"></i> Re-book Seat
                            </a>
                        </div>
                    </div>

                <?php elseif ($is_paid_seat): ?>
                    <!-- Active Seat Reservation within 7 Days -->
                    <?php 
                    $seconds_left = $valid_ts ? max(0, $valid_ts - $now_ts) : 0;
                    $days_left = max(0, ceil($seconds_left / 86400));
                    $hours_left = max(0, round($seconds_left / 3600));
                    $is_urgent = ($seconds_left <= 86400);
                    ?>
                    <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between flex-wrap gap-3 seat-status-box" style="background: <?= $is_urgent ? '#fff7ed' : '#f0fdf4' ?>; border-color: <?= $is_urgent ? '#fed7aa' : '#bbf7d0' ?> !important;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center text-white rounded-circle flex-shrink-0" style="width: 44px; height: 44px; background: <?= $is_urgent ? '#ea580c' : '#16a34a' ?>;">
                                <i class="fa-solid fa-chair fs-5"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <strong class="text-dark" style="font-size: 14.5px;">Seat Reserved &bull; ₹<?= number_format($display_seat_fee, 0) ?> Paid</strong>
                                    <?php if ($is_urgent): ?>
                                        <span class="badge bg-danger text-white px-2 py-0.5 rounded-pill animate-pulse" style="font-size: 10px;">
                                            <i class="fa-solid fa-triangle-exclamation me-0.5"></i> Expires in <?= $hours_left ?>h (Today!)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success text-white px-2 py-0.5 rounded-pill" style="font-size: 10px;">
                                            <i class="fa-solid fa-clock me-0.5"></i> <?= $days_left ?> Days Left
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge bg-light text-dark border px-2 py-0.5 rounded-pill" style="font-size: 10px;">
                                        100% Fee Deductible
                                    </span>
                                </div>
                                <div class="text-muted small mt-1">
                                    <?php if (!empty($student['booked_course'])): ?>
                                        Course: <strong class="text-dark"><?= htmlspecialchars($student['booked_course']) ?></strong> &bull; 
                                    <?php endif; ?>
                                    Valid Until: <strong class="<?= $is_urgent ? 'text-danger' : 'text-dark' ?>"><?= $valid_ts ? date('d M Y, h:i A', $valid_ts) : '7 Days' ?></strong>
                                    &bull; Txn: <span class="font-monospace text-secondary"><?= htmlspecialchars($student['payment_id'] ?: 'Online Verified') ?></span>
                                    <div class="text-secondary small mt-0.5">
                                        Complete your admission form before the 7-day validity expires to automatically adjust your ₹<?= number_format($display_seat_fee, 0) ?> fee into your course tuition!
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="no-print w-100 w-md-auto">
                            <a href="form-submission.php<?= !empty($student['booked_course']) ? '?course='.urlencode($student['booked_course']) : '' ?>" class="btn btn-sm btn-success px-3.5 py-2.5 fw-bold w-100 shadow-sm" style="background: #16a34a; border-color: #16a34a; font-size: 13px; border-radius: 6px;">
                                <i class="fa-solid fa-file-signature me-1.5"></i> Complete Admission Form (₹<?= number_format($display_seat_fee, 0) ?> Auto-Deducted)
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Unreserved -->
                    <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between flex-wrap gap-3 seat-status-box" style="background: #fffbeb; border-color: #fde68a !important;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center text-white rounded-circle flex-shrink-0" style="width: 44px; height: 44px; background: #fe7c03;">
                                <i class="fa-solid fa-chair fs-5"></i>
                            </div>
                            <div>
                                <strong class="text-dark" style="font-size: 14.5px;">Seat Not Yet Reserved</strong>
                                <div class="text-muted small mt-0.5">
                                    Reserve your seat for ₹<?= number_format($admin_seat_fee, 0) ?>. The ₹<?= number_format($admin_seat_fee, 0) ?> fee will be <strong>100% deducted</strong> from your total course admission fee!
                                </div>
                            </div>
                        </div>
                        <div class="no-print w-100 w-md-auto">
                            <a href="seat-reservation.php" class="btn btn-sm btn-primary px-3 py-2 fw-bold w-100" style="background: #fe7c03; border-color: #fe7c03; font-size: 12.5px; border-radius: 6px;">
                                <i class="fa-solid fa-chair me-1"></i> Reserve Your Seat Now (₹<?= number_format($admin_seat_fee, 0) ?>)
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 1. ENROLLED ADMISSIONS & COURSES SECTION -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2.5">
                    <h5 class="fw-bold text-dark mb-0" style="font-size: 15px;">
                        <i class="fa-solid fa-graduation-cap text-primary me-1.5"></i> My Enrolled Courses &amp; Applications
                    </h5>
                    <a href="form-submission.php" class="btn btn-sm btn-outline-primary py-1 px-2 no-print" style="font-size: 11.5px;">
                        <i class="fa-solid fa-plus me-1"></i> Apply for New Course
                    </a>
                </div>

                <?php if (!empty($my_admissions)): ?>
                    <!-- Desktop Table View -->
                    <div class="d-none d-md-block table-responsive border rounded-2">
                        <table class="table table-hover table-sm mb-0 align-middle" style="font-size: 12.5px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Course &amp; Ref</th>
                                    <th>Enrollment No.</th>
                                    <th>Course Fee</th>
                                    <th>Seat Fee Adjusted</th>
                                    <th>Fee Paid</th>
                                    <th>Balance Due</th>
                                    <th>Admission Status</th>
                                    <th>Applied On</th>
                                    <th>Official Slip</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_admissions as $adm): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-dark d-block"><?= htmlspecialchars($adm['course']) ?></strong>
                                            <span class="text-muted font-monospace" style="font-size: 11px;">Ref: ADM-<?= str_pad($adm['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($adm['enrollment_no'])): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-0.5" style="font-size: 11px;">
                                                    <?= htmlspecialchars($adm['enrollment_no']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">Pending Allocation</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="font-monospace">₹<?= number_format((float)$adm['fee_total'], 2) ?></td>
                                        <td class="font-monospace text-success fw-semibold">
                                            <?php if ((float)($adm['seat_fee_adjusted'] ?? 0) > 0): ?>
                                                - ₹<?= number_format((float)$adm['seat_fee_adjusted'], 2) ?>
                                            <?php else: ?>
                                                <span class="text-muted">₹0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="font-monospace text-success fw-bold">₹<?= number_format((float)$adm['fee_paid'], 2) ?></td>
                                        <td class="font-monospace <?= ((float)$adm['fee_pending'] > 0) ? 'text-danger fw-semibold' : 'text-success' ?>">
                                            ₹<?= number_format((float)$adm['fee_pending'], 2) ?>
                                        </td>
                                        <td>
                                            <?php if ($adm['status'] === 'Approved'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5">Enrolled / Confirmed</span>
                                            <?php elseif ($adm['status'] === 'Pending'): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-0.5">Pending Payment</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary px-2 py-0.5"><?= htmlspecialchars($adm['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted"><?= date('d M Y', strtotime($adm['created_at'])) ?></td>
                                        <td>
                                            <a href="enrollment-slip.php?id=<?= $adm['id'] ?>" class="btn btn-sm btn-outline-primary py-0.5 px-2 fw-semibold" style="font-size: 11px; border-radius: 5px;">
                                                <i class="fa-solid fa-file-pdf text-danger me-1"></i> Slip (PDF)
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards View for Admissions -->
                    <div class="d-md-none">
                        <?php foreach ($my_admissions as $adm): ?>
                            <div class="admission-mobile-card">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1" style="font-size: 14px;"><?= htmlspecialchars($adm['course']) ?></h6>
                                        <span class="badge bg-light text-secondary border font-monospace" style="font-size: 10.5px;">
                                            Ref: ADM-<?= str_pad($adm['id'], 5, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <?php if ($adm['status'] === 'Approved'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill" style="font-size: 11px;">
                                                <i class="fa-solid fa-circle-check me-1"></i>Enrolled
                                            </span>
                                        <?php elseif ($adm['status'] === 'Pending'): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill" style="font-size: 11px;">
                                                <i class="fa-solid fa-clock me-1"></i>Pending
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-2 py-1 rounded-pill" style="font-size: 11px;"><?= htmlspecialchars($adm['status']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($adm['enrollment_no'])): ?>
                                    <div class="mb-2 p-1.5 px-2 rounded-2 bg-primary-subtle text-primary border border-primary-subtle d-flex align-items-center justify-content-between" style="font-size: 11.5px;">
                                        <span class="small fw-semibold"><i class="fa-solid fa-id-badge me-1"></i> Enrollment No:</span>
                                        <strong class="font-monospace"><?= htmlspecialchars($adm['enrollment_no']) ?></strong>
                                    </div>
                                <?php endif; ?>

                                <!-- 2x2 Metric Matrix -->
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <div class="metric-grid-box">
                                            <span class="metric-grid-label">Total Fee</span>
                                            <span class="metric-grid-val text-dark">₹<?= number_format((float)$adm['fee_total'], 2) ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="metric-grid-box">
                                            <span class="metric-grid-label">Seat Adjusted</span>
                                            <span class="metric-grid-val text-success">
                                                <?= ((float)($adm['seat_fee_adjusted'] ?? 0) > 0) ? '- ₹' . number_format((float)$adm['seat_fee_adjusted'], 2) : '₹0.00' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="metric-grid-box bg-success-subtle border-success-subtle">
                                            <span class="metric-grid-label text-success">Fee Paid</span>
                                            <span class="metric-grid-val text-success">₹<?= number_format((float)$adm['fee_paid'], 2) ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="metric-grid-box <?= ((float)$adm['fee_pending'] > 0) ? 'bg-danger-subtle border-danger-subtle' : '' ?>">
                                            <span class="metric-grid-label <?= ((float)$adm['fee_pending'] > 0) ? 'text-danger' : '' ?>">Balance Due</span>
                                            <span class="metric-grid-val <?= ((float)$adm['fee_pending'] > 0) ? 'text-danger' : 'text-success' ?>">₹<?= number_format((float)$adm['fee_pending'], 2) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between pt-1 text-muted" style="font-size: 11px;">
                                    <span><i class="fa-regular fa-calendar me-1"></i> Applied: <?= date('d M Y', strtotime($adm['created_at'])) ?></span>
                                    <?php if ((float)$adm['fee_pending'] <= 0): ?>
                                        <span class="text-success fw-semibold"><i class="fa-solid fa-check-double me-1"></i> Fully Paid</span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-2 pt-2 border-top">
                                    <a href="enrollment-slip.php?id=<?= $adm['id'] ?>" class="btn btn-sm btn-outline-primary w-100 py-1.5 fw-semibold" style="font-size: 11.5px; border-radius: 6px;">
                                        <i class="fa-solid fa-file-pdf text-danger me-1"></i> Download Enrollment Slip (PDF)
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-3 bg-light rounded-2 border text-center text-muted small">
                        No active admission applications found. <a href="form-submission.php" class="text-primary fw-semibold">Click here to apply for a course</a>.
                    </div>
                <?php endif; ?>
            </div>

            <!-- 2. FEE INSTALLMENT SCHEDULE & PAYMENT HISTORY -->
            <div class="mb-4">
                <h5 class="fw-bold text-dark mb-2.5" style="font-size: 15px;">
                    <i class="fa-solid fa-receipt text-success me-1.5"></i> Fee Installments Schedule &amp; Receipts
                </h5>

                <?php if (!empty($my_installments)): ?>
                    <!-- Desktop Table View -->
                    <div class="d-none d-md-block table-responsive border rounded-2">
                        <table class="table table-hover table-sm mb-0 align-middle" style="font-size: 12.5px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Installment Title</th>
                                    <th>Course</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Paid Date</th>
                                    <th>Status</th>
                                    <th>Receipt / Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_installments as $ins): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-dark"><?= htmlspecialchars($ins['installment_title']) ?></strong>
                                            <?php if (!empty($ins['receipt_no'])): ?>
                                                <span class="d-block text-muted font-monospace" style="font-size: 10.5px;">#<?= htmlspecialchars($ins['receipt_no']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($ins['course_title']) ?>
                                            <?php if (!empty($ins['enrollment_no'])): ?>
                                                <div class="text-muted font-monospace" style="font-size: 10.5px;"><?= htmlspecialchars($ins['enrollment_no']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="font-monospace fw-bold">₹<?= number_format($ins['installment_amount'], 2) ?></td>
                                        <td class="text-muted"><?= date('d M Y', strtotime($ins['due_date'])) ?></td>
                                        <td class="text-muted"><?= $ins['paid_date'] ? date('d M Y, h:i A', strtotime($ins['paid_date'])) : '-' ?></td>
                                        <td>
                                            <?php if ($ins['status'] === 'Paid'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-0.5">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($ins['status'] === 'Paid'): ?>
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <span class="text-success small fw-semibold"><i class="fa-solid fa-circle-check me-1"></i> Verified</span>
                                                    <?php if (!empty($ins['receipt_no']) || !empty($ins['payment_id'])): ?>
                                                        <a href="receipt.php?ref=<?= urlencode($ins['receipt_no'] ?: $ins['payment_id']) ?>" target="_blank" class="btn btn-outline-success btn-sm py-0 px-2 text-decoration-none" style="font-size: 11px;">
                                                            <i class="fa-solid fa-receipt me-1"></i> Receipt
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-primary py-0.5 px-2 no-print" style="font-size: 11.5px; background: #fe7c03; border-color: #fe7c03;" onclick="paySingleInstallment(<?= $ins['id'] ?>, <?= $ins['installment_amount'] ?>, '<?= addslashes($ins['installment_title']) ?>', '<?= addslashes($ins['course_title']) ?>')">
                                                    Pay Now
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards View for Installments -->
                    <div class="d-md-none">
                        <?php foreach ($my_installments as $ins): ?>
                            <div class="installment-mobile-card">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                    <div>
                                        <strong class="text-dark d-block" style="font-size: 13.5px;"><?= htmlspecialchars($ins['installment_title']) ?></strong>
                                        <small class="text-secondary" style="font-size: 11.5px;"><?= htmlspecialchars($ins['course_title']) ?></small>
                                        <?php if (!empty($ins['enrollment_no'])): ?>
                                            <div class="text-muted font-monospace" style="font-size: 10.5px;">Enrollment: <?= htmlspecialchars($ins['enrollment_no']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <?php if ($ins['status'] === 'Paid'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill" style="font-size: 11px;">
                                                <i class="fa-solid fa-circle-check me-1"></i>Paid
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill" style="font-size: 11px;">
                                                <i class="fa-solid fa-clock me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-light border mb-2">
                                    <div>
                                        <span class="metric-grid-label">Amount</span>
                                        <strong class="font-monospace text-dark fs-6">₹<?= number_format($ins['installment_amount'], 2) ?></strong>
                                    </div>
                                    <div class="text-end">
                                        <span class="metric-grid-label">Due Date</span>
                                        <span class="small fw-semibold text-dark"><?= date('d M Y', strtotime($ins['due_date'])) ?></span>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between pt-1">
                                    <div class="text-muted" style="font-size: 11px;">
                                        <?php if ($ins['paid_date']): ?>
                                            <span><i class="fa-solid fa-calendar-check text-success me-1"></i> Paid: <?= date('d M Y', strtotime($ins['paid_date'])) ?></span>
                                        <?php elseif (!empty($ins['receipt_no'])): ?>
                                            <span class="font-monospace">#<?= htmlspecialchars($ins['receipt_no']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($ins['status'] === 'Paid'): ?>
                                            <?php if (!empty($ins['receipt_no']) || !empty($ins['payment_id'])): ?>
                                                <a href="receipt.php?ref=<?= urlencode($ins['receipt_no'] ?: $ins['payment_id']) ?>" target="_blank" class="btn btn-outline-success btn-sm py-1 px-2.5 text-decoration-none fw-semibold" style="font-size: 11.5px; border-radius: 6px;">
                                                    <i class="fa-solid fa-receipt me-1"></i> View Receipt
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-primary py-1 px-3 no-print fw-bold" style="font-size: 12px; background: #fe7c03; border-color: #fe7c03; border-radius: 6px;" onclick="paySingleInstallment(<?= $ins['id'] ?>, <?= $ins['installment_amount'] ?>, '<?= addslashes($ins['installment_title']) ?>', '<?= addslashes($ins['course_title']) ?>')">
                                                <i class="fa-solid fa-lock me-1"></i> Pay Now
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-3 bg-light rounded-2 border text-center text-muted small">
                        No installment records yet. Complete your course application to view scheduled fee installments.
                    </div>
                <?php endif; ?>
            </div>

            <!-- 3. PRINTABLE PROVISIONAL STUDENT CARD -->
            <div class="print-slip-box p-3 border rounded-2 bg-light" id="printableSlip">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <img src="img/logo.png" alt="Finchskills" style="height: 32px;" onerror="this.style.display='none';">
                        <div>
                            <strong class="text-dark d-block" style="font-size: 14.5px;">Finchskills Institute</strong>
                            <small class="text-muted" style="font-size: 10.5px;">Student Provisional ID &amp; Enrollment Slip</small>
                        </div>
                    </div>
                    <div class="text-end d-flex align-items-center gap-2">
                        <button type="button" onclick="downloadStudentCardPDF()" class="btn btn-sm btn-outline-dark py-0.5 px-2 no-print" style="font-size: 11px;">
                            <i class="fa-solid fa-file-pdf text-danger me-1"></i> Download ID Card (PDF)
                        </button>
                        <?php if (!empty($student['registration_id'])): ?>
                            <span class="badge border font-monospace px-2 py-1 rounded-1" style="background: #fff7ed; color: #ea580c; border-color: #ffedd5 !important; font-size: 11px;">
                                REG: <?= htmlspecialchars($student['registration_id']) ?>
                            </span>
                        <?php endif; ?>
                        <span class="badge bg-dark text-white font-monospace px-2 py-1 rounded-1" style="font-size: 11.5px;">
                            <?= htmlspecialchars($student['student_id']) ?>
                        </span>
                    </div>
                </div>

                <div class="row align-items-center g-2.5">
                    <div class="col-sm-3 text-center">
                        <?php if (!empty($student['photo']) && file_exists(__DIR__ . '/' . $student['photo'])): ?>
                            <img src="<?= htmlspecialchars($student['photo']) ?>" alt="Photo" class="rounded-2 border" style="width: 85px; height: 100px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-2 border bg-white d-flex align-items-center justify-content-center text-muted mx-auto" style="width: 85px; height: 100px;">
                                <i class="fa-solid fa-user fa-2x"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-9">
                        <table class="table table-sm table-borderless mb-0" style="font-size: 12.5px;">
                            <tr>
                                <td class="text-muted" style="width: 130px;">Student Name:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($student['name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Contact Phone:</td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($student['phone']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Email Address:</td>
                                <td class="text-dark"><?= htmlspecialchars($student['email']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Aadhaar (Last 4):</td>
                                <td class="font-monospace text-dark">XXXX-XXXX-<?= substr($student['aadhaar'], -4) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-center text-muted" style="font-size: 11px;">
                    <span><?= htmlspecialchars(getSetting('address', 'Orbit Plaza, Crossing Republik, Ghaziabad')) ?> &bull; Helpline: <?= htmlspecialchars(!empty($site_phone_1) ? $site_phone_1 : getSetting('phone_1', '+91 96503 86711')) ?></span>
                    <span>System Generated Record</span>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- Razorpay Script for dashboard single installment payments -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
function paySingleInstallment(installmentId, amount, title, courseTitle) {
    const amountPaise = Math.round(Number(amount) * 100);

    const options = {
        "key": "<?= htmlspecialchars($razorpay_key) ?>",
        "amount": amountPaise,
        "currency": "INR",
        "name": "Finchskills Institute",
        "description": title + " - " + courseTitle,
        "image": "img/favicon.png",
        "prefill": {
            "name": "<?= htmlspecialchars($student['name']) ?>",
            "email": "<?= htmlspecialchars($student['email']) ?>",
            "contact": "<?= htmlspecialchars($student['phone']) ?>"
        },
        "theme": {
            "color": "#fe7c03"
        },
        "handler": function (response) {
            fetch('process-fee-payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'verify_course_fee',
                    admission_id: "<?= !empty($my_admissions[0]['id']) ? $my_admissions[0]['id'] : 0 ?>",
                    razorpay_payment_id: response.razorpay_payment_id,
                    plan_type: 'installment',
                    paid_amount: amount
                })
            })
            .then(res => res.json())
            .then(data => {
                window.location.href = 'student-profile.php?payment=success';
            })
            .catch(err => {
                window.location.href = 'student-profile.php?payment=success';
            });
        }
    };

    const rzp = new Razorpay(options);
    rzp.open();
}

function downloadStudentCardPDF() {
    var element = document.getElementById('printableSlip');
    var sid = "<?= htmlspecialchars($student['student_id']) ?>";
    
    var prevScroll = window.pageYOffset || document.documentElement.scrollTop;
    window.scrollTo(0, 0);

    var opt = {
        margin:       [8, 8, 8, 8],
        filename:     'Student_ID_Card_' + sid + '.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { 
            scale: 2, 
            useCORS: true, 
            logging: false,
            scrollY: 0,
            scrollX: 0
        },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    setTimeout(function() {
        if (typeof html2pdf !== 'undefined') {
            html2pdf().set(opt).from(element).save().then(function() {
                window.scrollTo(0, prevScroll);
            }).catch(function(err) {
                console.error('html2pdf error:', err);
                window.scrollTo(0, prevScroll);
                window.print();
            });
        } else {
            window.scrollTo(0, prevScroll);
            window.print();
        }
    }, 120);
}
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<?php include "footer.php"; ?>

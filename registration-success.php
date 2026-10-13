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

$ref_param = trim($_GET['ref'] ?? '');
$conf = $_SESSION['last_confirmation'] ?? null;
$student = null;

// 1. If ref is provided in URL, prioritize looking up the exact record
if (!empty($ref_param)) {
    $f_stmt = $pdo->prepare("SELECT * FROM fee_submissions WHERE receipt LIKE ? ORDER BY id DESC LIMIT 1");
    $f_stmt->execute(['%' . $ref_param . '%']);
    $fee_row = $f_stmt->fetch();

    if ($fee_row) {
        $st_stmt = $pdo->prepare("SELECT * FROM students WHERE (email = ? AND email != '') OR (phone = ? AND phone != '') ORDER BY id DESC LIMIT 1");
        $st_stmt->execute([$fee_row['email'], $fee_row['contact']]);
        $student = $st_stmt->fetch();

        $pay_id = '';
        if (preg_match('/(pay_[a-zA-Z0-9]+)/', $fee_row['receipt'], $m)) {
            $pay_id = $m[1];
        }

        $conf = [
            'student_id'      => $student['student_id'] ?? 'FS-' . date('Y') . '-0001',
            'registration_id' => !empty($student['registration_id']) ? $student['registration_id'] : (isset($student['student_id']) ? str_replace('FS-', 'REG-', $student['student_id']) : ('REG-' . date('Y') . '-0001')),
            'student_db_id'   => $student['id'] ?? 0,
            'name'            => $student['name'] ?? $fee_row['name'],
            'email'           => $student['email'] ?? $fee_row['email'],
            'phone'           => $student['phone'] ?? $fee_row['contact'],
            'aadhaar'         => !empty($student['aadhaar']) ? $student['aadhaar'] : 'Verified Online',
            'photo'           => $student['photo'] ?? '',
            'course_id'       => $student['booked_course_id'] ?? 0,
            'course_title'    => $student['booked_course'] ?? $fee_row['course'],
            'amount'          => (float)(!empty($student['payment_amount']) ? $student['payment_amount'] : 999),
            'payment_id'      => !empty($student['payment_id']) ? $student['payment_id'] : ($pay_id ?: 'pay_online'),
            'ref_code'        => $ref_param,
            'valid_until'     => !empty($student['seat_valid_until']) ? $student['seat_valid_until'] : date('Y-m-d H:i:s', strtotime('+7 days')),
            'timestamp'       => !empty($student['payment_date']) ? $student['payment_date'] : ($fee_row['created_at'] ?? date('Y-m-d H:i:s'))
        ];
    }
}

// 2. Fallback to logged-in student record if conf is still null
if (!$conf && !empty($_SESSION['student_db_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['student_db_id']]);
    $student = $stmt->fetch();

    if ($student && $student['payment_status'] === 'Paid') {
        $ref_code = 'REC-' . strtoupper(substr(md5($student['payment_id'] ?? 'REC'), 0, 8));
        $conf = [
            'student_id'      => $student['student_id'],
            'registration_id' => !empty($student['registration_id']) ? $student['registration_id'] : (isset($student['student_id']) ? str_replace('FS-', 'REG-', $student['student_id']) : ('REG-' . date('Y') . '-0001')),
            'student_db_id'   => $student['id'],
            'name'            => $student['name'],
            'email'           => $student['email'],
            'phone'           => $student['phone'],
            'aadhaar'         => $student['aadhaar'],
            'photo'           => $student['photo'],
            'course_id'       => $student['booked_course_id'],
            'course_title'    => $student['booked_course'],
            'amount'          => (float)($student['payment_amount'] ?: 999),
            'payment_id'      => $student['payment_id'],
            'ref_code'        => $ref_code,
            'valid_until'     => $student['seat_valid_until'],
            'timestamp'       => $student['payment_date'] ?? date('Y-m-d H:i:s')
        ];
    }
}

// 3. If still no record found, redirect to register
if (!$conf) {
    header("Location: register.php");
    exit;
}

if (empty($conf['registration_id']) && !empty($conf['student_id'])) {
    $conf['registration_id'] = str_replace('FS-', 'REG-', $conf['student_id']);
}

// 4. Resolve Course Fee and Remaining Tuition Balance
$course_fee = 0.00;
if (!empty($conf['course_id']) || !empty($conf['course_title'])) {
    $c_stmt = $pdo->prepare("SELECT fee, title FROM courses WHERE id = ? OR title = ? LIMIT 1");
    $c_stmt->execute([(int)($conf['course_id'] ?? 0), $conf['course_title'] ?? '']);
    $c_row = $c_stmt->fetch(PDO::FETCH_ASSOC);
    if ($c_row) {
        $raw_fee = preg_replace('/[^0-9]/', '', $c_row['fee'] ?? '');
        if (!empty($raw_fee)) {
            $course_fee = (float)$raw_fee;
        }
        if (empty($conf['course_title'])) {
            $conf['course_title'] = $c_row['title'];
        }
    }
}
$conf['total_course_fee'] = $course_fee;
$conf['remaining_fee'] = max(0, $course_fee - (float)$conf['amount']);

// Dynamic Site Settings from Admin DB
$site_logo = getSetting('site_logo', 'img/logo.png');
$company_name = getSetting('razorpay_company_name', 'Finchskills Institute');
$phone_1 = getSetting('phone_1', '+91 96503 86711');
$email_1 = getSetting('email_1', 'hello@finchskills.com');
$address = getSetting('address', 'Orbit Plaza, Crossing Republik, NH-24, Ghaziabad, Uttar Pradesh, 201016');

$valid_until_ts = !empty($conf['valid_until']) ? strtotime($conf['valid_until']) : (time() + 7 * 86400);
$valid_until_formatted = date('d M Y, h:i A', $valid_until_ts);

$page_title = "Seat Confirmation Slip - " . htmlspecialchars($company_name);
include "header.php";
?>

<style>
.success-section {
    padding: 32px 15px 65px;
    background: #f8fafc;
    min-height: 85vh;
}
.success-container {
    max-width: 680px;
    margin: 0 auto;
}

/* TOP STATUS BANNER (COMPACT) */
.success-banner {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 10px;
    padding: 12px 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.success-banner-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #10b981;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}

/* CONFIRMATION SLIP CARD (AUTHENTIC INSTITUTIONAL FEEL) */
.confirmation-slip-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-top: 4px solid #fe7c03;
    border-radius: 12px;
    padding: 26px 26px 22px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
    position: relative;
    overflow: hidden;
}

/* SUBTLE WATERMARK */
.slip-watermark {
    position: absolute;
    top: 52%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-25deg);
    font-size: 58px;
    font-weight: 900;
    color: #0f172a;
    opacity: 0.025;
    letter-spacing: 6px;
    pointer-events: none;
    user-select: none;
    white-space: nowrap;
    z-index: 1;
}

/* HEADER: COMPACT TWO-COLUMN LAYOUT */
.slip-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 1.5px solid #f1f5f9;
    padding-bottom: 14px;
    margin-bottom: 18px;
    gap: 16px;
    position: relative;
    z-index: 2;
}
.slip-header-left {
    flex: 1;
    min-width: 250px;
}
.slip-brand-logo {
    max-height: 42px;
    max-width: 170px;
    object-fit: contain;
}
.slip-brand-fallback-title {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.2px;
}
.slip-inst-address {
    font-size: 11.5px;
    color: #475569;
    line-height: 1.35;
    margin-top: 4px;
    max-width: 360px;
}
.slip-inst-contact {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
}
.slip-header-right {
    text-align: right;
    flex-shrink: 0;
}
.slip-badge-official {
    display: inline-block;
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 4px;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.slip-receipt-num {
    font-size: 12.5px;
    color: #0f172a;
    font-family: var(--bs-font-monospace);
    font-weight: 700;
}
.slip-receipt-date {
    font-size: 11px;
    color: #64748b;
    margin-top: 1px;
}

/* STUDENT INFO STRIP (CLEAN & COMPACT) */
.slip-student-strip {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
    position: relative;
    z-index: 2;
}
.slip-student-avatar {
    width: 54px;
    height: 54px;
    border-radius: 6px;
    object-fit: cover;
    border: 1.5px solid #fe7c03;
    background: #ffffff;
    flex-shrink: 0;
}
.slip-student-avatar-placeholder {
    width: 54px;
    height: 54px;
    border-radius: 6px;
    background: #fff3e6;
    border: 1.5px dashed #fed7aa;
    color: #fe7c03;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.student-id-badge {
    display: inline-block;
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #ffedd5;
    font-family: var(--bs-font-monospace);
    font-weight: 700;
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 4px;
}

/* TABULAR DETAILS (TIGHT, CLEAN SPACING) */
.slip-details-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 16px;
    position: relative;
    z-index: 2;
}
.slip-details-table tr {
    border-bottom: 1px solid #f1f5f9;
}
.slip-details-table tr:last-child {
    border-bottom: none;
}
.slip-details-table td {
    padding: 8px 6px;
    font-size: 13px;
}
.slip-label {
    color: #64748b;
    font-weight: 500;
    width: 38%;
}
.slip-val {
    color: #0f172a;
    font-weight: 600;
    text-align: right;
}

/* POLICY & 7-DAY VALIDITY NOTICE */
.validity-alert-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 12px 14px;
    margin-bottom: 16px;
    position: relative;
    z-index: 2;
}

/* AUTHENTIC SEAL & STAMP */
.slip-footer-seal-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px dashed #cbd5e1;
    padding-top: 12px;
    margin-top: 8px;
    position: relative;
    z-index: 2;
}
.slip-auth-seal {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1.5px solid #047857;
    background: #f0fdf4;
    border-radius: 6px;
    padding: 4px 10px;
    text-align: left;
}
.seal-icon {
    font-size: 18px;
    color: #047857;
}
.seal-text-top {
    font-size: 9px;
    font-weight: 800;
    color: #047857;
    letter-spacing: 0.5px;
    line-height: 1;
}
.seal-text-sub {
    font-size: 10.5px;
    font-weight: 700;
    color: #065f46;
    line-height: 1.2;
}

/* ACTION BUTTONS (CLEAN, PROPORTIONAL FONT & SPACING) */
.action-btn-group {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 22px;
}
.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 9px 18px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 7px;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    cursor: pointer;
    line-height: 1.4;
    white-space: nowrap;
}
.btn-action-download {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
}
.btn-action-download:hover {
    background: #1e293b;
    color: #ffffff;
}
.btn-action-primary {
    background: #fe7c03;
    color: #ffffff;
    border-color: #fe7c03;
}
.btn-action-primary:hover {
    background: #e06d00;
    color: #ffffff;
}
.btn-action-secondary {
    background: #ffffff;
    color: #334155;
    border-color: #cbd5e1;
}
.btn-action-secondary:hover {
    background: #f8fafc;
    color: #0f172a;
}

/* PRINT & HTML2PDF STYLING */
@media print {
    body * {
        visibility: hidden;
    }
    .topbar, .navbar, footer, .no-print {
        display: none !important;
    }
    #printableSlipCard, #printableSlipCard * {
        visibility: visible;
    }
    #printableSlipCard {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .success-section {
        padding: 0 !important;
        background: #ffffff !important;
    }
}

@media (max-width: 575.98px) {
    .confirmation-slip-card {
        padding: 18px 14px;
    }
    .slip-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    .slip-header-right {
        text-align: left;
    }
    .action-btn-group {
        flex-direction: column;
        width: 100%;
    }
    .btn-action {
        width: 100%;
        padding: 10px 14px;
    }
}
</style>

<section class="success-section">
    <div class="container success-container">

        <!-- COMPACT SUCCESS BANNER (NO-PRINT) -->
        <div class="success-banner no-print">
            <div class="success-banner-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            <div>
                <strong class="text-success d-block" style="font-size: 14.5px;">Seat Reserved &amp; Registration Confirmed!</strong>
                <span class="text-secondary small" style="font-size: 12px;">Your official reservation slip has been generated below.</span>
            </div>
        </div>

        <!-- PRINTABLE & DOWNLOADABLE CONFIRMATION SLIP CARD -->
        <div class="confirmation-slip-card" id="printableSlipCard">

            <!-- WATERMARK EMBLEM -->
            <div class="slip-watermark">CONFIRMED SEAT</div>

            <!-- SLIP HEADER: LEFT LOGO/ADDRESS & RIGHT SLIP NO/DATE -->
            <div class="slip-header">
                <div class="slip-header-left">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <?php if (!empty($site_logo) && file_exists(__DIR__ . '/' . $site_logo)): ?>
                            <img src="<?= htmlspecialchars($site_logo) ?>" alt="<?= htmlspecialchars($company_name) ?>" class="slip-brand-logo">
                        <?php else: ?>
                            <h5 class="slip-brand-fallback-title"><?= htmlspecialchars($company_name) ?></h5>
                        <?php endif; ?>
                    </div>
                    <div class="slip-inst-address">
                        <?= htmlspecialchars($address) ?>
                    </div>
                    <div class="slip-inst-contact">
                        <span><i class="fa-solid fa-phone me-1"></i>Helpline: <?= htmlspecialchars($phone_1) ?></span>
                        <?php if (!empty($email_1)): ?>
                            <span class="mx-1.5">&bull;</span>
                            <span><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($email_1) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="slip-header-right">
                    <span class="slip-badge-official">
                        <i class="fa-solid fa-certificate me-1"></i> Official Seat Confirmation Slip
                    </span>
                    <div class="slip-receipt-num">
                        <?php if (!empty($conf['registration_id'])): ?>
                            <span class="text-muted fw-normal" style="font-size: 11px;">Reg ID:</span> <strong style="color: #fe7c03; font-family: monospace; font-size: 11.5px;"><?= htmlspecialchars($conf['registration_id']) ?></strong> &bull; 
                        <?php endif; ?>
                        <span class="text-muted fw-normal" style="font-size: 11px;">Receipt No:</span> <?= htmlspecialchars($conf['ref_code'] ?? 'REC-CONF') ?>
                    </div>
                    <div class="slip-receipt-date">
                        <span class="text-muted">Registration Date:</span> <?= date('d M Y, h:i A', strtotime($conf['timestamp'] ?? date('Y-m-d H:i:s'))) ?>
                    </div>
                </div>
            </div>

            <!-- STUDENT IDENTIFICATION STRIP -->
            <div class="slip-student-strip">
                <?php if (!empty($conf['photo']) && file_exists(__DIR__ . '/' . $conf['photo'])): ?>
                    <img src="<?= htmlspecialchars($conf['photo']) ?>" alt="Passport Photo" class="slip-student-avatar">
                <?php else: ?>
                    <div class="slip-student-avatar-placeholder">
                        <i class="fa-solid fa-user"></i>
                    </div>
                <?php endif; ?>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h6 class="fw-bold text-dark mb-0" style="font-size: 15px;"><?= htmlspecialchars($conf['name']) ?></h6>
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <?php if (!empty($conf['registration_id'])): ?>
                                <span class="badge border fw-bold" style="background: #fff7ed; color: #ea580c; border-color: #ffedd5 !important; font-size: 11px; padding: 4px 8px; font-family: monospace;">
                                    <i class="fa-solid fa-hashtag me-0.5"></i> <?= htmlspecialchars($conf['registration_id']) ?>
                                </span>
                            <?php endif; ?>
                            <span class="student-id-badge">ID: <?= htmlspecialchars($conf['student_id']) ?></span>
                        </div>
                    </div>
                    <div class="text-muted small mt-0.5" style="font-size: 12px;">
                        <i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($conf['phone']) ?> &bull; 
                        <i class="fa-regular fa-envelope me-1"></i> <?= htmlspecialchars($conf['email']) ?>
                    </div>
                </div>
            </div>

            <!-- DETAILS TABLE -->
            <table class="slip-details-table">
                <?php if (!empty($conf['registration_id'])): ?>
                <tr>
                    <td class="slip-label">Registration ID</td>
                    <td class="slip-val font-monospace fw-bold" style="color: #ea580c; font-size: 13.5px;">
                        <?= htmlspecialchars($conf['registration_id']) ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="slip-label">Student ID</td>
                    <td class="slip-val font-monospace fw-bold text-dark" style="font-size: 13px;">
                        <?= htmlspecialchars($conf['student_id']) ?>
                    </td>
                </tr>
                <tr>
                    <td class="slip-label">Reserved Course Program</td>
                    <td class="slip-val text-primary fw-bold" style="font-size: 13.5px;">
                        <?= htmlspecialchars($conf['course_title']) ?>
                    </td>
                </tr>
                <?php if (!empty($conf['total_course_fee']) && $conf['total_course_fee'] > 0): ?>
                <tr>
                    <td class="slip-label">Total Course Tuition Fee</td>
                    <td class="slip-val font-monospace fw-semibold text-dark">
                        ₹<?= number_format($conf['total_course_fee'], 2) ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="slip-label">Aadhaar Card Number</td>
                    <td class="slip-val font-monospace">
                        <?= htmlspecialchars($conf['aadhaar']) ?>
                    </td>
                </tr>
                <tr>
                    <td class="slip-label">Seat Reservation Fee Paid</td>
                    <td class="slip-val text-success fw-bold" style="font-size: 14.5px;">
                        ₹<?= number_format((float)$conf['amount'], 2) ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 9.5px; font-weight: 600;">PAID &bull; 100% ADJUSTABLE</span>
                    </td>
                </tr>
                <?php if (!empty($conf['total_course_fee']) && $conf['total_course_fee'] > 0): ?>
                <tr>
                    <td class="slip-label">Remaining Fee (At Admission)</td>
                    <td class="slip-val font-monospace fw-bold text-danger" style="font-size: 14px;">
                        ₹<?= number_format($conf['remaining_fee'], 2) ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="slip-label">Razorpay Transaction ID</td>
                    <td class="slip-val font-monospace" style="font-size: 12px;">
                        <?= htmlspecialchars($conf['payment_id']) ?>
                    </td>
                </tr>
                <tr>
                    <td class="slip-label">Registration Status</td>
                    <td class="slip-val">
                        <span class="badge bg-success text-white px-2 py-0.5 rounded-1" style="font-size: 10.5px;">
                            <i class="fa-solid fa-check me-1"></i> Verified &amp; Confirmed
                        </span>
                    </td>
                </tr>
                <tr style="background: #fffbeb;">
                    <td class="slip-label" style="color: #92400e; font-weight: 700;">
                        <i class="fa-regular fa-clock me-1"></i> Seat Validity Period
                    </td>
                    <td class="slip-val" style="color: #92400e; font-weight: 700;">
                        Valid Until: <?= $valid_until_formatted ?> (7 Days)
                    </td>
                </tr>
            </table>

            <!-- 7-DAY VALIDITY & NON-REFUNDABLE POLICY NOTICE -->
            <div class="validity-alert-box">
                <div class="d-flex align-items-start gap-2">
                    <i class="fa-solid fa-circle-info text-warning fs-6 mt-0.5"></i>
                    <div>
                        <strong class="text-dark d-block" style="font-size: 12.5px;">
                            Important Seat Validity &amp; Registration Policy:
                        </strong>
                        <p class="text-secondary small mb-1 mt-1" style="font-size: 12px; line-height: 1.55;">
                            Your registration remains valid for 7 days from the date of submission. If the admission process is not completed within this period, your registration will be automatically cancelled. Please note that the ₹2,750 registration fee is strictly non-refundable under all circumstances.
                        </p>
                        <div class="text-success small fw-semibold" style="font-size: 11.5px;">
                            <i class="fa-solid fa-check me-1"></i> 100% Fee Adjustment: This registration fee will be 100% deducted from your total course fees during final admission.
                        </div>
                    </div>
                </div>
            </div>

            <!-- OFFICIAL DIGITAL STAMP & FOOTER -->
            <div class="slip-footer-seal-row">
                <div class="text-muted" style="font-size: 10.5px; line-height: 1.4;">
                    Official computer-generated confirmation slip from <?= htmlspecialchars($company_name) ?>.<br>
                    Support: <?= htmlspecialchars($email_1) ?>
                </div>
                <div class="slip-auth-seal">
                    <div class="seal-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <div>
                        <div class="seal-text-top"><?= strtoupper(htmlspecialchars($company_name)) ?></div>
                        <div class="seal-text-sub">OFFICIALLY CONFIRMED</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- 3 ACTION BUTTONS (NEAT, COMPACT & PROPORTIONAL) -->
        <div class="action-btn-group no-print">
            <button type="button" id="btnDownloadPdf" onclick="downloadSlipPDF()" class="btn-action btn-action-download">
                <i class="fa-solid fa-file-arrow-down text-warning"></i>
                <span>Download PDF Slip</span>
            </button>
            <a href="form-submission.php?course=<?= urlencode($conf['course_title']) ?>" class="btn-action btn-action-primary">
                <i class="fa-solid fa-file-pen"></i>
                <span>Complete Admission</span>
            </a>
            <a href="student-profile.php" class="btn-action btn-action-secondary">
                <i class="fa-solid fa-address-card"></i>
                <span>Student Dashboard</span>
            </a>
        </div>

        <div class="text-center mt-2.5 no-print">
            <button type="button" onclick="window.print()" class="btn btn-link btn-sm text-muted text-decoration-none p-0" style="font-size: 11.5px;">
                <i class="fa-solid fa-print me-1"></i> Or click here to Print directly
            </button>
        </div>

    </div>
</section>

<!-- Include html2pdf.js for Client-side Direct PDF File Download -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
function downloadSlipPDF() {
    var element = document.getElementById('printableSlipCard');
    var btn = document.getElementById('btnDownloadPdf');
    var origHtml = btn.innerHTML;
    var refCode = "<?= !empty($conf['ref_code']) ? addslashes($conf['ref_code']) : 'REC' ?>";
    var regId = "<?= !empty($conf['registration_id']) ? addslashes($conf['registration_id']) : '' ?>";

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Generating PDF...</span>';

    // Save scroll position and reset to top to prevent html2canvas offset bug
    var prevScroll = window.pageYOffset || document.documentElement.scrollTop;
    window.scrollTo(0, 0);

    var opt = {
        margin:       [6, 6, 6, 6],
        filename:     'Registration_Slip_' + (regId || refCode) + '.pdf',
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
                btn.disabled = false;
                btn.innerHTML = origHtml;
                window.scrollTo(0, prevScroll);
            }).catch(function(err) {
                console.error('html2pdf error:', err);
                btn.disabled = false;
                btn.innerHTML = origHtml;
                window.scrollTo(0, prevScroll);
                window.print();
            });
        } else {
            btn.disabled = false;
            btn.innerHTML = origHtml;
            window.scrollTo(0, prevScroll);
            window.print();
        }
    }, 120);
}
</script>

<?php include "footer.php"; ?>

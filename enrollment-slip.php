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

// Detect viewer
$adm_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$enrollment_no = trim($_GET['enrollment_no'] ?? $_GET['enr'] ?? '');
$student_id_param = trim($_GET['student_id'] ?? '');

$admission = null;

// 1. Lookup by admission ID
if ($adm_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM admissions WHERE id = ? LIMIT 1");
    $stmt->execute([$adm_id]);
    $admission = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 2. Lookup by enrollment_no
if (!$admission && !empty($enrollment_no)) {
    $stmt = $pdo->prepare("SELECT * FROM admissions WHERE enrollment_no = ? LIMIT 1");
    $stmt->execute([$enrollment_no]);
    $admission = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 3. Lookup by student_id or session
if (!$admission) {
    $sid = !empty($_SESSION['student_id']) ? $_SESSION['student_id'] : $student_id_param;
    $s_email = $_SESSION['student_email'] ?? '';
    if (!empty($sid) || !empty($s_email)) {
        $stmt = $pdo->prepare("SELECT * FROM admissions WHERE (student_id = ? AND student_id != '') OR (email = ? AND email != '') ORDER BY id DESC LIMIT 1");
        $stmt->execute([$sid, $s_email]);
        $admission = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// 4. Fallback if candidate only has seat booking and no full admission form yet
if (!$admission && !empty($_SESSION['student_db_id'])) {
    header("Location: registration-success.php");
    exit;
}

if (!$admission) {
    header("Location: student-login.php");
    exit;
}

// Ensure admission is confirmed & fee has been paid
if (empty($admission['enrollment_no']) && $admission['status'] !== 'Approved') {
    header("Location: student-profile.php");
    exit;
}

// Fetch linked Student details (for photo and extra info)
$student = null;
if (!empty($admission['student_id'])) {
    $s_stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? LIMIT 1");
    $s_stmt->execute([$admission['student_id']]);
    $student = $s_stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch Installments for this admission if any
$installments = [];
if (!empty($admission['id'])) {
    $ins_stmt = $pdo->prepare("SELECT * FROM student_installments WHERE admission_id = ? OR student_id = ? ORDER BY due_date ASC, id ASC");
    $ins_stmt->execute([$admission['id'], $admission['student_id']]);
    $installments = $ins_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Dynamic Settings
$site_logo = getSetting('site_logo', 'img/logo.png');
$company_name = getSetting('razorpay_company_name', 'Finchskills Institute');
$phone_1 = getSetting('phone_1', '+91 96503 86711');
$email_1 = getSetting('email_1', 'hello@finchskills.com');
$address = getSetting('address', 'Orbit Plaza, Crossing Republik, NH-24, Ghaziabad, Uttar Pradesh, 201016');

// Photo resolution
$photo_url = '';
if (!empty($admission['photo']) && file_exists(__DIR__ . '/' . $admission['photo'])) {
    $photo_url = $admission['photo'];
} elseif (!empty($student['photo']) && file_exists(__DIR__ . '/' . $student['photo'])) {
    $photo_url = $student['photo'];
}

$ref_adm = 'ADM-' . str_pad($admission['id'], 5, '0', STR_PAD_LEFT);
$enr_code = !empty($admission['enrollment_no']) ? $admission['enrollment_no'] : $ref_adm;

$page_title = "Course Enrollment Slip - " . htmlspecialchars($admission['name']) . " (" . htmlspecialchars($enr_code) . ")";
include "header.php";
?>

<style>
.slip-page-section {
    padding: 30px 15px 70px;
    background: #f8fafc;
    min-height: 85vh;
}
.slip-page-container {
    max-width: 760px;
    margin: 0 auto;
}

/* ENROLLMENT SLIP CARD */
.enrollment-slip-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-top: 5px solid #fe7c03;
    border-radius: 12px;
    padding: 28px 28px 24px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
    position: relative;
    overflow: hidden;
}

/* HEADER */
.slip-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid #f1f5f9;
    padding-bottom: 14px;
    margin-bottom: 18px;
    gap: 16px;
}
.slip-header-left {
    flex: 1;
}
.slip-brand-logo {
    max-height: 42px;
    max-width: 175px;
    object-fit: contain;
}
.slip-brand-fallback {
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.2px;
}
.slip-inst-address {
    font-size: 11.5px;
    color: #475569;
    line-height: 1.35;
    margin-top: 4px;
    max-width: 380px;
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
.slip-enr-num {
    font-size: 13.5px;
    color: #0f172a;
    font-family: var(--bs-font-monospace);
    font-weight: 700;
}
.slip-date {
    font-size: 11px;
    color: #64748b;
    margin-top: 1px;
}

/* SECTION TITLES */
.slip-section-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #0f172a;
    background: #f8fafc;
    border-left: 3px solid #fe7c03;
    padding: 4px 8px;
    margin: 14px 0 10px;
}

/* STUDENT PROFILE STRIP */
.student-profile-strip {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #fbfcfe;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 14px;
}
.student-slip-photo {
    width: 64px;
    height: 64px;
    border-radius: 6px;
    object-fit: cover;
    border: 1.5px solid #fe7c03;
    background: #ffffff;
    flex-shrink: 0;
}
.student-slip-photo-empty {
    width: 64px;
    height: 64px;
    border-radius: 6px;
    background: #fff3e6;
    border: 1.5px dashed #fed7aa;
    color: #fe7c03;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

/* DATA TABLES */
.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
}
.data-table tr {
    border-bottom: 1px solid #f1f5f9;
}
.data-table tr:last-child {
    border-bottom: none;
}
.data-table td {
    padding: 6.5px 6px;
    font-size: 12.5px;
}
.data-table .label {
    color: #64748b;
    font-weight: 500;
    width: 25%;
}
.data-table .val {
    color: #0f172a;
    font-weight: 600;
    width: 25%;
}

/* FINANCIAL GRID */
.fee-summary-box {
    background: #fafbfc;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 14px;
    margin-bottom: 14px;
}
.fee-summary-row {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    font-size: 12.5px;
    border-bottom: 1px solid #f1f5f9;
}
.fee-summary-row:last-child {
    border-bottom: none;
    padding-top: 8px;
    margin-top: 4px;
    border-top: 1.5px dashed #cbd5e1;
}

/* POLICY NOTE */
.policy-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 11.5px;
    color: #92400e;
    line-height: 1.5;
    margin-bottom: 14px;
}

/* FOOTER SEAL */
.slip-footer-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px dashed #cbd5e1;
    padding-top: 12px;
    margin-top: 6px;
}
.auth-seal-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1.5px solid #047857;
    background: #f0fdf4;
    border-radius: 6px;
    padding: 4px 10px;
}

/* BUTTONS */
.action-btn-group {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 20px;
}
.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 7px;
    text-decoration: none;
    cursor: pointer;
    border: 1px solid transparent;
    white-space: nowrap;
}
.btn-action-download {
    background: #0f172a;
    color: #ffffff;
}
.btn-action-download:hover {
    background: #1e293b;
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

/* PRINT MEDIA */
@media print {
    body * { visibility: hidden; }
    .topbar, .navbar, footer, .no-print { display: none !important; }
    #printableEnrollmentCard, #printableEnrollmentCard * { visibility: visible; }
    #printableEnrollmentCard {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .slip-page-section { padding: 0 !important; background: #ffffff !important; }
}

@media (max-width: 575.98px) {
    .enrollment-slip-card { padding: 18px 14px; }
    .slip-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .slip-header-right { text-align: left; }
    .data-table .label, .data-table .val { width: 50%; }
    .action-btn-group { flex-direction: column; width: 100%; }
    .btn-action { width: 100%; }
}
</style>

<section class="slip-page-section">
    <div class="container slip-page-container">

        <!-- ENROLLMENT SLIP CARD -->
        <div class="enrollment-slip-card" id="printableEnrollmentCard">

            <!-- 1. INSTITUTIONAL HEADER -->
            <div class="slip-header">
                <div class="slip-header-left">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <?php if (!empty($site_logo) && file_exists(__DIR__ . '/' . $site_logo)): ?>
                            <img src="<?= htmlspecialchars($site_logo) ?>" alt="<?= htmlspecialchars($company_name) ?>" class="slip-brand-logo">
                        <?php else: ?>
                            <h5 class="slip-brand-fallback"><?= htmlspecialchars($company_name) ?></h5>
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
                        <i class="fa-solid fa-graduation-cap me-1"></i> COURSE ENROLLMENT LETTER
                    </span>
                    <div class="slip-enr-num">
                        <span class="text-muted fw-normal" style="font-size: 11px;">Enrollment No:</span> <?= htmlspecialchars($enr_code) ?>
                    </div>
                    <div class="slip-date">
                        <span class="text-muted">Admission Date:</span> <?= date('d M Y', strtotime($admission['created_at'])) ?>
                    </div>
                </div>
            </div>

            <!-- 2. STUDENT IDENTIFICATION STRIP -->
            <div class="student-profile-strip">
                <?php if (!empty($photo_url)): ?>
                    <img src="<?= htmlspecialchars($photo_url) ?>" alt="Student Photo" class="student-slip-photo">
                <?php else: ?>
                    <div class="student-slip-photo-empty"><i class="fa-solid fa-user"></i></div>
                <?php endif; ?>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="fw-bold text-dark mb-0" style="font-size: 16px;"><?= htmlspecialchars($admission['name']) ?></h6>
                        <span class="badge bg-dark text-white font-monospace px-2 py-1" style="font-size: 11.5px;">
                            ID: <?= htmlspecialchars($admission['student_id'] ?: ($student['student_id'] ?? 'N/A')) ?>
                        </span>
                    </div>
                    <div class="text-muted small mt-1" style="font-size: 12px;">
                        <i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($admission['mobile']) ?> &bull; 
                        <i class="fa-regular fa-envelope me-1"></i> <?= htmlspecialchars($admission['email']) ?>
                    </div>
                </div>
            </div>

            <!-- 3. PERSONAL & COMMUNICATION DETAILS -->
            <div class="slip-section-title">Personal &amp; Contact Details</div>
            <table class="data-table">
                <tr>
                    <td class="label">Father's Name:</td>
                    <td class="val"><?= htmlspecialchars($admission['father_name'] ?: 'N/A') ?></td>
                    <td class="label">Aadhaar Number:</td>
                    <td class="val font-monospace"><?= htmlspecialchars($admission['aadhaar'] ?: ($student['aadhaar'] ?? 'Verified')) ?></td>
                </tr>
                <tr>
                    <td class="label">Date of Birth:</td>
                    <td class="val"><?= !empty($admission['dob']) ? date('d M Y', strtotime($admission['dob'])) : 'N/A' ?></td>
                    <td class="label">Gender:</td>
                    <td class="val"><?= htmlspecialchars($admission['gender'] ?: 'N/A') ?></td>
                </tr>
                <tr>
                    <td class="label">Permanent Address:</td>
                    <td class="val" colspan="3">
                        <?= htmlspecialchars($admission['address'] ?: 'N/A') ?>
                        <?= !empty($admission['city']) ? (', ' . htmlspecialchars($admission['city'])) : '' ?>
                        <?= !empty($admission['state']) ? (', ' . htmlspecialchars($admission['state'])) : '' ?>
                        <?= !empty($admission['pincode']) ? (' - ' . htmlspecialchars($admission['pincode'])) : '' ?>
                    </td>
                </tr>
            </table>

            <!-- 4. ACADEMIC QUALIFICATIONS -->
            <div class="slip-section-title">Academic Background</div>
            <table class="data-table">
                <tr>
                    <td class="label">Qualification:</td>
                    <td class="val"><?= htmlspecialchars($admission['education'] ?: '12th Pass') ?></td>
                    <td class="label">Board / University:</td>
                    <td class="val"><?= htmlspecialchars($admission['board_university'] ?: 'Recognized Board') ?></td>
                </tr>
                <tr>
                    <td class="label">Passing Year:</td>
                    <td class="val"><?= htmlspecialchars($admission['passing_year'] ?: 'N/A') ?></td>
                    <td class="label">Percentage / Grade:</td>
                    <td class="val"><?= htmlspecialchars($admission['percentage'] ? ($admission['percentage'] . '%') : 'Qualified') ?></td>
                </tr>
            </table>

            <!-- 5. ENROLLED COURSE PROGRAM -->
            <div class="slip-section-title">Enrolled Career Program</div>
            <table class="data-table">
                <tr>
                    <td class="label">Program Title:</td>
                    <td class="val text-primary fw-bold" style="font-size: 13.5px;" colspan="3">
                        <?= htmlspecialchars($admission['course']) ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Admission Status:</td>
                    <td class="val">
                        <?php if ($admission['status'] === 'Approved'): ?>
                            <span class="badge bg-success text-white px-2 py-0.5 rounded-1" style="font-size: 10.5px;">
                                <i class="fa-solid fa-check me-1"></i> Approved &amp; Enrolled
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark px-2 py-0.5 rounded-1" style="font-size: 10.5px;">
                                <?= htmlspecialchars($admission['status']) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="label">Payment Plan:</td>
                    <td class="val text-capitalize">
                        <?= htmlspecialchars($admission['payment_plan'] ?: 'Installment Plan') ?>
                    </td>
                </tr>
            </table>

            <!-- 6. COMPLETE FINANCIAL & FEE SUMMARY -->
            <div class="slip-section-title">Course Tuition Fee &amp; Payment Summary</div>
            <div class="fee-summary-box">
                <div class="fee-summary-row">
                    <span class="text-secondary">Total Course Tuition Fee:</span>
                    <strong class="text-dark font-monospace">₹<?= number_format((float)$admission['fee_total'], 2) ?></strong>
                </div>
                <?php if ((float)($admission['seat_fee_adjusted'] ?? 0) > 0): ?>
                    <div class="fee-summary-row">
                        <span class="text-success">Seat Reservation Fee (100% Adjusted):</span>
                        <strong class="text-success font-monospace">- ₹<?= number_format((float)$admission['seat_fee_adjusted'], 2) ?></strong>
                    </div>
                <?php endif; ?>
                <div class="fee-summary-row">
                    <span class="text-secondary">Total Amount Paid to Date:</span>
                    <strong class="text-success font-monospace" style="font-size: 13.5px;">₹<?= number_format((float)$admission['fee_paid'], 2) ?></strong>
                </div>
                <div class="fee-summary-row">
                    <div>
                        <strong class="text-dark d-block">Remaining Balance Payable:</strong>
                        <small class="text-muted" style="font-size: 11px;">Payable via scheduled installments</small>
                    </div>
                    <strong class="font-monospace <?= ((float)$admission['fee_pending'] > 0) ? 'text-danger' : 'text-success' ?>" style="font-size: 14px;">
                        ₹<?= number_format((float)$admission['fee_pending'], 2) ?>
                    </strong>
                </div>
            </div>

            <!-- 7. INSTALLMENTS SCHEDULE (IF ANY) -->
            <?php if (!empty($installments)): ?>
                <div class="slip-section-title">Scheduled Fee Installments</div>
                <table class="table table-sm table-bordered mb-2" style="font-size: 11.5px;">
                    <thead class="table-light">
                        <tr>
                            <th>Installment</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($installments as $ins): ?>
                            <tr>
                                <td><?= htmlspecialchars($ins['installment_title']) ?></td>
                                <td><?= date('d M Y', strtotime($ins['due_date'])) ?></td>
                                <td class="font-monospace">₹<?= number_format((float)$ins['installment_amount'], 2) ?></td>
                                <td>
                                    <?php if ($ins['status'] === 'Paid'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Paid (<?= !empty($ins['paid_date']) ? date('d M Y', strtotime($ins['paid_date'])) : 'Done' ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- 8. INSTITUTIONAL POLICY & DECLARATION -->
            <div class="policy-box">
                <strong><i class="fa-solid fa-shield-halved me-1"></i> Institutional Guidelines &amp; Declarations:</strong>
                <div class="mt-1" style="font-size: 11px;">
                    1. <strong>Fee Policy:</strong> Scheduled installments must be cleared by the specified due date.<br>
                    2. <strong>Post-Course Assignment:</strong> Students must complete and submit the assigned project or assignment upon successful completion of the course.<br>
                    3. <strong>Placement Support:</strong> Dedicated career placement assistance is provided by the Institute Placement Cell upon successful curriculum completion.
                </div>
            </div>

            <!-- 9. OFFICIAL FOOTER SEAL & VERIFICATION -->
            <div class="slip-footer-row">
                <div class="text-muted" style="font-size: 10px; line-height: 1.35;">
                    Official computer-generated admission letter from <?= htmlspecialchars($company_name) ?>.<br>
                    Valid without physical signature. Verify via student portal.
                </div>
                <div class="auth-seal-badge">
                    <i class="fa-solid fa-shield-check text-success fs-5"></i>
                    <div>
                        <div style="font-size: 8.5px; font-weight: 800; color: #047857; letter-spacing: 0.5px;"><?= strtoupper(htmlspecialchars($company_name)) ?></div>
                        <div style="font-size: 10px; font-weight: 700; color: #065f46;">ADMISSION VERIFIED</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ACTION BUTTONS (NO-PRINT) -->
        <div class="action-btn-group no-print">
            <button type="button" id="btnDownloadEnrollmentPdf" onclick="downloadEnrollmentPDF()" class="btn-action btn-action-download">
                <i class="fa-solid fa-file-arrow-down text-warning"></i>
                <span>Download Enrollment Slip (PDF)</span>
            </button>
            <a href="student-profile.php" class="btn-action btn-action-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
            <button type="button" onclick="window.print()" class="btn-action btn-action-secondary">
                <i class="fa-solid fa-print"></i>
                <span>Print</span>
            </button>
        </div>

    </div>
</section>

<!-- Include html2pdf.js for Client-side Direct PDF File Download -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
function downloadEnrollmentPDF() {
    var element = document.getElementById('printableEnrollmentCard');
    var btn = document.getElementById('btnDownloadEnrollmentPdf');
    var origHtml = btn.innerHTML;
    var enrCode = "<?= !empty($enr_code) ? addslashes($enr_code) : 'ENR' ?>";

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Generating PDF...</span>';

    // Reset scroll position to top to eliminate blank space bug
    var prevScroll = window.pageYOffset || document.documentElement.scrollTop;
    window.scrollTo(0, 0);

    var opt = {
        margin:       [6, 6, 6, 6],
        filename:     'Enrollment_Letter_' + enrCode + '.pdf',
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

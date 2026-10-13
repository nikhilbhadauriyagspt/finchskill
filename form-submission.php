<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

// Check if candidate is logged in
$isStudentLoggedIn = isset($_SESSION['student_logged_in']) && $_SESSION['student_logged_in'] === true;
$student = null;

if ($isStudentLoggedIn && !empty($_SESSION['student_db_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['student_db_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all active courses for the dropdown
$courses_stmt = $pdo->query("SELECT id, title, slug, duration, study_mode, fee FROM courses WHERE status = 'active' ORDER BY title ASC");
$all_courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Preselected course from query string (?course=...) or student's booked course
$preselected_course = trim($_GET['course'] ?? ($student['booked_course'] ?? ''));

// Razorpay Settings
$settings = [];
$s_stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'razorpay_%' OR setting_key LIKE '%discount%'");
while ($r = $s_stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$r['setting_key']] = $r['setting_value'];
}
$mode = $settings['razorpay_mode'] ?? 'test';
$key_id = ($mode === 'live') ? ($settings['razorpay_live_key_id'] ?? '') : ($settings['razorpay_test_key_id'] ?? 'rzp_test_1DP5mmOlF5G5ag');
$full_pay_discount_pct = (float)($settings['full_payment_discount_percent'] ?? 10);

$page_title = "Online Course Admission Application - Finchskills Institute";
include "header.php";
?>

<style>
/* ===================================================
   MULTI-STEP APPLICATION WIZARD (FLAT, MINIMAL & CLEAN)
=================================================== */
.wizard-section {
    padding: 40px 0 80px;
    background: #f8fafc;
    min-height: 85vh;
}

.wizard-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: none !important;
    overflow: hidden;
    position: relative;
}

/* WIZARD PROGRESS BAR */
.wizard-progress-header {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 20px 16px 16px;
}

.wizard-stepper {
    display: flex;
    justify-content: space-between;
    position: relative;
    max-width: 820px;
    margin: 0 auto;
}

.wizard-stepper::before {
    content: '';
    position: absolute;
    top: 18px;
    left: 30px;
    right: 30px;
    height: 2px;
    background: #e2e8f0;
    z-index: 1;
}

.wizard-stepper-progress {
    position: absolute;
    top: 18px;
    left: 30px;
    height: 2px;
    background: #fe7c03;
    z-index: 2;
    transition: width 0.3s ease;
    width: 0%;
}

.step-item {
    position: relative;
    z-index: 3;
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    background: transparent;
    border: none;
    padding: 0;
    width: 90px;
}

.step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #ffffff;
    border: 2px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    transition: all 0.2s ease;
}

.step-item.active .step-circle {
    border-color: #fe7c03;
    background: #fe7c03;
    color: #ffffff;
}

.step-item.completed .step-circle {
    border-color: #10b981;
    background: #10b981;
    color: #ffffff;
}

.step-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    margin-top: 6px;
    text-align: center;
}

.step-item.active .step-label {
    color: #fe7c03;
    font-weight: 700;
}

.step-item.completed .step-label {
    color: #0f172a;
}

/* WIZARD CONTENT */
.wizard-body {
    padding: 28px 24px 32px;
    position: relative;
}

@media (max-width: 767px) {
    .wizard-body {
        padding: 20px 14px;
    }
    .wizard-stepper {
        overflow-x: auto;
        padding-bottom: 6px;
    }
    .step-item {
        width: 60px;
    }
    .step-circle {
        width: 30px;
        height: 30px;
        font-size: 11px;
    }
    .step-label {
        font-size: 9.5px;
    }
}

.wizard-step-pane {
    display: none;
}

.wizard-step-pane.active {
    display: block;
}

.step-heading {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.2px;
    margin-bottom: 4px;
}

.step-subheading {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 20px;
}

/* FORM FIELDS */
.form-label-custom {
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.form-label-custom .req {
    color: #ef4444;
}

.form-control-custom,
.form-select-custom {
    width: 100%;
    padding: 9px 12px;
    font-size: 13.5px;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    outline: none;
    box-shadow: none !important;
}

.form-control-custom:focus,
.form-select-custom:focus {
    border-color: #fe7c03;
}

.form-control-custom[readonly] {
    background: #f8fafc;
    color: #475569;
    cursor: not-allowed;
}

/* UPLOAD BOXES */
.file-dropzone {
    border: 1.5px dashed #cbd5e1;
    border-radius: 6px;
    padding: 16px;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    position: relative;
}

.file-dropzone:hover {
    border-color: #fe7c03;
    background: #fff8f3;
}

.file-dropzone input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

.file-dropzone-icon {
    font-size: 22px;
    color: #94a3b8;
    margin-bottom: 6px;
}

.file-chosen-name {
    font-size: 11.5px;
    font-weight: 600;
    color: #059669;
    margin-top: 4px;
    display: none;
}

/* WIZARD ACTION BUTTONS */
.wizard-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.btn-wizard-prev {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #475569;
    font-weight: 600;
    font-size: 13px;
    padding: 8px 18px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}

.btn-wizard-prev:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.btn-wizard-next,
.btn-wizard-submit {
    background: #fe7c03;
    border: 1px solid #fe7c03;
    color: #ffffff;
    font-weight: 600;
    font-size: 13.5px;
    padding: 9px 22px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    box-shadow: none !important;
}

.btn-wizard-next:hover,
.btn-wizard-submit:hover {
    background: #e66f00;
    border-color: #e66f00;
}

/* SUMMARY / REVIEW TILES */
.review-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 14px 16px;
    margin-bottom: 12px;
}

.review-card-title {
    font-size: 12.5px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.review-row {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 12.5px;
    border-bottom: 1px dashed #e2e8f0;
}

.review-row:last-child {
    border-bottom: none;
}

.review-label {
    color: #64748b;
}

.review-val {
    color: #0f172a;
    font-weight: 600;
    text-align: right;
}

/* FEE PAYMENT STEP 6 STYLES */
.plan-option-card {
    border: 1.5px solid #e2e8f0;
    border-radius: 6px;
    padding: 16px;
    background: #ffffff;
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
}

.plan-option-card:hover {
    border-color: #cbd5e1;
}

.plan-option-card.selected {
    border-color: #fe7c03;
    background: #fffaf5;
}

.plan-badge-disc {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 3px;
}

/* IN-FORM CLEAN LOADER OVERLAY */
.form-loading-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(2px);
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 100;
    border-radius: 8px;
}

.form-loading-spinner {
    width: 42px;
    height: 42px;
    border: 3.5px solid #e2e8f0;
    border-top: 3.5px solid #fe7c03;
    border-radius: 50%;
    animation: spinLoader 0.75s linear infinite;
    margin-bottom: 12px;
}

@keyframes spinLoader {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.form-loading-text {
    font-size: 13.5px;
    font-weight: 600;
    color: #0f172a;
}

.form-loading-sub {
    font-size: 11.5px;
    color: #64748b;
    margin-top: 2px;
}
</style>

<!-- SECTION WRAPPER -->
<section class="wizard-section">
    <div class="container">
        
        <div class="row justify-content-center">
            <div class="col-xl-9 col-lg-10">

                <!-- TOP GUEST OR STUDENT BADGE -->
                <?php if ($isStudentLoggedIn && $student): ?>
                    <div class="alert alert-light border border-slate-200 rounded-2 p-2 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!empty($student['photo']) && file_exists(__DIR__ . '/' . $student['photo'])): ?>
                                <img src="<?= htmlspecialchars($student['photo']) ?>" alt="Photo" class="rounded-circle border border-warning" style="width: 38px; height: 38px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-warning text-dark fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 14px;">
                                    <?= strtoupper(substr($student['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div class="fw-bold text-dark small d-flex align-items-center gap-1">
                                    <span><?= htmlspecialchars($student['name']) ?></span>
                                    <span class="badge bg-light text-dark border font-monospace" style="font-size: 10px;">
                                        <?= htmlspecialchars($student['student_id']) ?>
                                    </span>
                                </div>
                                <div class="text-muted" style="font-size: 11px;">
                                    <i class="fa-solid fa-circle-check text-success me-1"></i> Logged In - Information auto-filled!
                                </div>
                            </div>
                        </div>
                        <a href="student-profile.php" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 12px;">My Dashboard</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border border-slate-200 rounded-2 p-2 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 text-dark small">
                            <i class="fa-solid fa-circle-info text-primary fs-6"></i>
                            <span>Already registered? Log in to auto-fill your personal profile details.</span>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="student-login.php?redirect=form-submission.php<?= !empty($preselected_course) ? '?course=' . urlencode($preselected_course) : '' ?>" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 12px;">
                                <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Log In
                            </a>
                            <a href="register.php" class="btn btn-sm btn-primary py-1 px-2" style="background:#fe7c03; border-color:#fe7c03; font-size: 12px;">
                                <i class="fa-solid fa-user-plus me-1"></i> Register
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- WIZARD CONTAINER -->
                <div class="wizard-card" id="wizardCard">
                    
                    <!-- IN-FORM CLEAN LOADER -->
                    <div class="form-loading-overlay" id="formLoadingOverlay">
                        <div class="form-loading-spinner"></div>
                        <div class="form-loading-text" id="formLoadingTitle">Processing Application...</div>
                        <div class="form-loading-sub" id="formLoadingSubtitle">Please wait a moment</div>
                    </div>

                    <!-- STEPPER HEADER -->
                    <div class="wizard-progress-header">
                        <div class="wizard-stepper">
                            <div class="wizard-stepper-progress" id="stepperProgressBar"></div>

                            <!-- STEP 1 -->
                            <div class="step-item active" id="stepIndicator1" onclick="jumpToStep(1)">
                                <div class="step-circle"><i class="fa-solid fa-user"></i></div>
                                <span class="step-label">1. Student</span>
                            </div>

                            <!-- STEP 2 -->
                            <div class="step-item" id="stepIndicator2" onclick="jumpToStep(2)">
                                <div class="step-circle"><i class="fa-solid fa-graduation-cap"></i></div>
                                <span class="step-label">2. Course</span>
                            </div>

                            <!-- STEP 3 -->
                            <div class="step-item" id="stepIndicator3" onclick="jumpToStep(3)">
                                <div class="step-circle"><i class="fa-solid fa-map-location-dot"></i></div>
                                <span class="step-label">3. Address</span>
                            </div>

                            <!-- STEP 4 -->
                            <div class="step-item" id="stepIndicator4" onclick="jumpToStep(4)">
                                <div class="step-circle"><i class="fa-solid fa-file-arrow-up"></i></div>
                                <span class="step-label">4. Documents</span>
                            </div>

                            <!-- STEP 5 -->
                            <div class="step-item" id="stepIndicator5" onclick="jumpToStep(5)">
                                <div class="step-circle"><i class="fa-solid fa-clipboard-check"></i></div>
                                <span class="step-label">5. Review</span>
                            </div>

                            <!-- STEP 6 (FEE PAYMENT MANDATORY) -->
                            <div class="step-item" id="stepIndicator6">
                                <div class="step-circle"><i class="fa-solid fa-credit-card"></i></div>
                                <span class="step-label">6. Fee Payment</span>
                            </div>
                        </div>
                    </div>

                    <!-- WIZARD BODY -->
                    <div class="wizard-body">
                        
                        <form id="admissionForm" method="POST" enctype="multipart/form-data">
                            
                            <!-- Hidden Fields -->
                            <input type="hidden" name="ajax_submit" value="1">
                            <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['student_id'] ?? '') ?>">
                            <input type="hidden" name="existing_photo" value="<?= htmlspecialchars($student['photo'] ?? '') ?>">

                            <!-- ============================================ -->
                            <!-- STEP 1: STUDENT PERSONAL DETAILS -->
                            <!-- ============================================ -->
                            <div class="wizard-step-pane active" id="stepPane1">
                                <h3 class="step-heading">Step 1: Personal &amp; Identity Information</h3>
                                <p class="step-subheading">Please review your primary student information. Fields with <span class="text-danger">*</span> are mandatory.</p>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>Full Name <span class="req">*</span></span>
                                            <?php if ($isStudentLoggedIn): ?><span class="badge bg-success-subtle text-success py-0" style="font-size:10px;">Autofilled</span><?php endif; ?>
                                        </label>
                                        <input type="text" name="name" id="f_name" class="form-control-custom" value="<?= htmlspecialchars($student['name'] ?? '') ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>Father's / Guardian's Name <span class="req">*</span></span>
                                        </label>
                                        <input type="text" name="father_name" id="f_father_name" class="form-control-custom" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>Email Address <span class="req">*</span></span>
                                            <?php if ($isStudentLoggedIn): ?><span class="badge bg-success-subtle text-success py-0" style="font-size:10px;">Verified</span><?php endif; ?>
                                        </label>
                                        <input type="email" name="email" id="f_email" class="form-control-custom" value="<?= htmlspecialchars($student['email'] ?? '') ?>" required <?= $isStudentLoggedIn ? 'readonly' : '' ?>>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>WhatsApp / Mobile Number <span class="req">*</span></span>
                                            <?php if ($isStudentLoggedIn): ?><span class="badge bg-success-subtle text-success py-0" style="font-size:10px;">Autofilled</span><?php endif; ?>
                                        </label>
                                        <input type="tel" name="mobile" id="f_mobile" class="form-control-custom" value="<?= htmlspecialchars($student['phone'] ?? '') ?>" required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label-custom">
                                            <span>12-Digit Aadhaar Number <span class="req">*</span></span>
                                            <?php if ($isStudentLoggedIn): ?><span class="badge bg-success-subtle text-success py-0" style="font-size:10px;">Autofilled</span><?php endif; ?>
                                        </label>
                                        <input type="text" name="aadhaar" id="f_aadhaar" class="form-control-custom font-monospace" value="<?= htmlspecialchars($student['aadhaar'] ?? '') ?>" maxlength="14" required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label-custom">
                                            <span>Date of Birth <span class="req">*</span></span>
                                        </label>
                                        <input type="date" name="dob" id="f_dob" class="form-control-custom" required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label-custom">
                                            <span>Gender <span class="req">*</span></span>
                                        </label>
                                        <select name="gender" id="f_gender" class="form-select-custom" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="wizard-footer justify-content-end">
                                    <button type="button" class="btn-wizard-next" onclick="goToStep(2)">
                                        <span>Next: Course Selection</span>
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- STEP 2: COURSE & ACADEMIC BACKGROUND -->
                            <!-- ============================================ -->
                            <div class="wizard-step-pane" id="stepPane2">
                                <h3 class="step-heading">Step 2: Program Selection &amp; Academics</h3>
                                <p class="step-subheading">Choose your preferred training course and provide your academic qualifications.</p>

                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label-custom">
                                            <span>Desired Training Program / Course <span class="req">*</span></span>
                                        </label>
                                        <select name="course" id="f_course" class="form-select-custom" required onchange="updateCoursePreview()">
                                            <option value="">-- Choose Course Program --</option>
                                            <?php foreach ($all_courses as $ac): ?>
                                                <?php
                                                $is_selected = (!empty($preselected_course) && (
                                                    strtolower($ac['title']) === strtolower($preselected_course) || 
                                                    strtolower($ac['slug']) === strtolower($preselected_course)
                                                ));
                                                ?>
                                                <option value="<?= htmlspecialchars($ac['title']) ?>" 
                                                        data-duration="<?= htmlspecialchars($ac['duration']) ?>"
                                                        data-mode="<?= htmlspecialchars($ac['study_mode']) ?>"
                                                        data-fee="<?= htmlspecialchars($ac['fee'] ?? '') ?>"
                                                        <?= $is_selected ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($ac['title']) ?> (<?= htmlspecialchars($ac['duration']) ?> - <?= htmlspecialchars($ac['fee'] ?? '') ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <div id="courseInfoBadge" class="p-2 bg-light rounded-2 border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa-solid fa-award text-warning fs-5"></i>
                                                <div>
                                                    <div class="fw-bold text-dark small" id="previewCourseName">
                                                        <?= !empty($preselected_course) ? htmlspecialchars($preselected_course) : 'Please select a course above' ?>
                                                    </div>
                                                    <small class="text-muted" id="previewCourseMeta" style="font-size:11.5px;">Duration: Industry standard certification &bull; 100% Placement Support</small>
                                                </div>
                                            </div>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size:11px;">
                                                <i class="fa-solid fa-circle-check me-1"></i> Career Verified
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>Highest Qualification <span class="req">*</span></span>
                                        </label>
                                        <select name="education" id="f_education" class="form-select-custom" required>
                                            <option value="">Select Qualification</option>
                                            <option value="10th Pass">10th (Secondary)</option>
                                            <option value="12th Pass (Pursuing)">12th (Pursuing / Appearing)</option>
                                            <option value="12th Pass">12th (Senior Secondary)</option>
                                            <option value="Diploma Holder">Diploma / Polytechnic</option>
                                            <option value="Undergraduate (Pursuing)">Undergraduate (Pursuing Degree)</option>
                                            <option value="Graduate">Graduate (Any Stream)</option>
                                            <option value="Post Graduate">Post Graduate / Master's</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>Board / University Name <span class="req">*</span></span>
                                        </label>
                                        <input type="text" name="board_university" id="f_board" class="form-control-custom" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>Year of Passing <span class="req">*</span></span>
                                        </label>
                                        <input type="number" name="passing_year" id="f_passing_year" class="form-control-custom font-monospace" min="2000" max="2030" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>Percentage / CGPA <span class="req">*</span></span>
                                        </label>
                                        <input type="text" name="percentage" id="f_percentage" class="form-control-custom font-monospace" required>
                                    </div>
                                </div>

                                <div class="wizard-footer">
                                    <button type="button" class="btn-wizard-prev" onclick="goToStep(1)">
                                        <i class="fa-solid fa-arrow-left"></i>
                                        <span>Previous</span>
                                    </button>
                                    <button type="button" class="btn-wizard-next" onclick="goToStep(3)">
                                        <span>Next: Address Details</span>
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- STEP 3: CONTACT & COMMUNICATION ADDRESS -->
                            <!-- ============================================ -->
                            <div class="wizard-step-pane" id="stepPane3">
                                <h3 class="step-heading">Step 3: Permanent &amp; Communication Address</h3>
                                <p class="step-subheading">Enter your residential address for batch scheduling and official dispatch.</p>

                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label-custom">
                                            <span>Full House Address (Street / Landmark / House No.) <span class="req">*</span></span>
                                        </label>
                                        <textarea name="address" id="f_address" rows="2" class="form-control-custom" required></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>City / District <span class="req">*</span></span>
                                        </label>
                                        <input type="text" name="city" id="f_city" class="form-control-custom" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>State / Union Territory <span class="req">*</span></span>
                                        </label>
                                        <select name="state" id="f_state" class="form-select-custom" required>
                                            <option value="">Select State / UT</option>
                                            <optgroup label="States">
                                                <option value="Andhra Pradesh">Andhra Pradesh</option>
                                                <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                                                <option value="Assam">Assam</option>
                                                <option value="Bihar">Bihar</option>
                                                <option value="Chhattisgarh">Chhattisgarh</option>
                                                <option value="Goa">Goa</option>
                                                <option value="Gujarat">Gujarat</option>
                                                <option value="Haryana">Haryana</option>
                                                <option value="Himachal Pradesh">Himachal Pradesh</option>
                                                <option value="Jharkhand">Jharkhand</option>
                                                <option value="Karnataka">Karnataka</option>
                                                <option value="Kerala">Kerala</option>
                                                <option value="Madhya Pradesh">Madhya Pradesh</option>
                                                <option value="Maharashtra">Maharashtra</option>
                                                <option value="Manipur">Manipur</option>
                                                <option value="Meghalaya">Meghalaya</option>
                                                <option value="Mizoram">Mizoram</option>
                                                <option value="Nagaland">Nagaland</option>
                                                <option value="Odisha">Odisha</option>
                                                <option value="Punjab">Punjab</option>
                                                <option value="Rajasthan">Rajasthan</option>
                                                <option value="Sikkim">Sikkim</option>
                                                <option value="Tamil Nadu">Tamil Nadu</option>
                                                <option value="Telangana">Telangana</option>
                                                <option value="Tripura">Tripura</option>
                                                <option value="Uttar Pradesh">Uttar Pradesh</option>
                                                <option value="Uttarakhand">Uttarakhand</option>
                                                <option value="West Bengal">West Bengal</option>
                                            </optgroup>
                                            <optgroup label="Union Territories">
                                                <option value="Andaman and Nicobar Islands">Andaman and Nicobar Islands</option>
                                                <option value="Chandigarh">Chandigarh</option>
                                                <option value="Dadra and Nagar Haveli and Daman and Diu">Dadra and Nagar Haveli and Daman and Diu</option>
                                                <option value="Delhi">Delhi (NCT)</option>
                                                <option value="Jammu and Kashmir">Jammu and Kashmir</option>
                                                <option value="Ladakh">Ladakh</option>
                                                <option value="Lakshadweep">Lakshadweep</option>
                                                <option value="Puducherry">Puducherry</option>
                                            </optgroup>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>Post Office <span class="req">*</span></span>
                                        </label>
                                        <input type="text" name="post_office" id="f_post_office" class="form-control-custom" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>6-Digit Pin Code <span class="req">*</span></span>
                                        </label>
                                        <input type="text" name="pincode" id="f_pincode" class="form-control-custom font-monospace" maxlength="6" required>
                                    </div>
                                </div>

                                <div class="wizard-footer">
                                    <button type="button" class="btn-wizard-prev" onclick="goToStep(2)">
                                        <i class="fa-solid fa-arrow-left"></i>
                                        <span>Previous</span>
                                    </button>
                                    <button type="button" class="btn-wizard-next" onclick="goToStep(4)">
                                        <span>Next: Upload Documents</span>
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- STEP 4: DOCUMENT UPLOADS & ATTACHMENTS -->
                            <!-- ============================================ -->
                            <div class="wizard-step-pane" id="stepPane4">
                                <h3 class="step-heading">Step 4: Academic &amp; Identity Documents</h3>
                                <p class="step-subheading">Attach clear copies of your documents. Supported formats: JPG, PNG, PDF (Max: 5MB per file).</p>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>Passport Size Photo <span class="req">*</span></span>
                                            <?php if (!empty($student['photo'])): ?>
                                                <span class="badge bg-success-subtle text-success py-0" style="font-size:10px;">Already Saved</span>
                                            <?php endif; ?>
                                        </label>
                                        <div class="file-dropzone">
                                            <input type="file" name="photo" id="f_photo" accept="image/*" onchange="previewFileName(this)" <?= empty($student['photo']) ? 'required' : '' ?>>
                                            <i class="fa-solid fa-camera file-dropzone-icon d-block"></i>
                                            <div class="small fw-semibold text-dark">Click to select Passport Photo</div>
                                            <div class="file-chosen-name"><?= !empty($student['photo']) ? '✓ Current Photo Attached' : '' ?></div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>Aadhaar Card (Front &amp; Back PDF/Image) <span class="req">*</span></span>
                                        </label>
                                        <div class="file-dropzone">
                                            <input type="file" name="aadhaar_card" id="f_aadhaar_file" accept=".pdf,image/*" required onchange="previewFileName(this)">
                                            <i class="fa-solid fa-id-card file-dropzone-icon d-block"></i>
                                            <div class="small fw-semibold text-dark">Upload Aadhaar Card Copy</div>
                                            <div class="file-chosen-name"></div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>10th Marksheet / Certificate <span class="req">*</span></span>
                                        </label>
                                        <div class="file-dropzone">
                                            <input type="file" name="marksheet10" id="f_m10" accept=".pdf,image/*" required onchange="previewFileName(this)">
                                            <i class="fa-solid fa-file-lines file-dropzone-icon d-block"></i>
                                            <div class="small fw-semibold text-dark">Upload 10th Marksheet</div>
                                            <div class="file-chosen-name"></div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-custom">
                                            <span>12th Marksheet / Highest Certificate <span class="req">*</span></span>
                                        </label>
                                        <div class="file-dropzone">
                                            <input type="file" name="marksheet12" id="f_m12" accept=".pdf,image/*" required onchange="previewFileName(this)">
                                            <i class="fa-solid fa-graduation-cap file-dropzone-icon d-block"></i>
                                            <div class="small fw-semibold text-dark">Upload 12th / Degree Marksheet</div>
                                            <div class="file-chosen-name"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="wizard-footer">
                                    <button type="button" class="btn-wizard-prev" onclick="goToStep(3)">
                                        <i class="fa-solid fa-arrow-left"></i>
                                        <span>Previous</span>
                                    </button>
                                    <button type="button" class="btn-wizard-next" onclick="prepareReviewAndGo(5)">
                                        <span>Next: Review &amp; Verify</span>
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- STEP 5: REVIEW & SUMMARY -->
                            <!-- ============================================ -->
                            <div class="wizard-step-pane" id="stepPane5">
                                <h3 class="step-heading">Step 5: Review Application</h3>
                                <p class="step-subheading">Please review your submitted details. Click Proceed to select your payment option.</p>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="review-card">
                                            <div class="review-card-title">
                                                <span><i class="fa-solid fa-user me-1 text-primary"></i> Student Details</span>
                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="goToStep(1)">Edit</button>
                                            </div>
                                            <div class="review-row">
                                                <span class="review-label">Student Name</span>
                                                <span class="review-val" id="r_name">-</span>
                                            </div>
                                            <div class="review-row">
                                                <span class="review-label">Father's Name</span>
                                                <span class="review-val" id="r_father">-</span>
                                            </div>
                                            <div class="review-row">
                                                <span class="review-label">DOB &amp; Gender</span>
                                                <span class="review-val" id="r_dob_gender">-</span>
                                            </div>
                                            <div class="review-row">
                                                <span class="review-label">Aadhaar No.</span>
                                                <span class="review-val font-monospace" id="r_aadhaar">-</span>
                                            </div>
                                            <div class="review-row">
                                                <span class="review-label">Mobile</span>
                                                <span class="review-val" id="r_mobile">-</span>
                                            </div>
                                            <div class="review-row">
                                                <span class="review-label">Email</span>
                                                <span class="review-val" id="r_email">-</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="review-card">
                                            <div class="review-card-title">
                                                <span><i class="fa-solid fa-graduation-cap me-1 text-warning"></i> Course &amp; Academics</span>
                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="goToStep(2)">Edit</button>
                                            </div>
                                            <div class="review-row">
                                                <span class="review-label">Selected Course</span>
                                                <span class="review-val text-primary" id="r_course">-</span>
                                            </div>
                                            <div class="review-row">
                                                <span class="review-label">Qualification</span>
                                                <span class="review-val" id="r_education">-</span>
                                            </div>
                                            <div class="review-row">
                                                <span class="review-label">Board / Univ</span>
                                                <span class="review-val" id="r_board">-</span>
                                            </div>
                                            <div class="review-row">
                                                <span class="review-label">Passing &amp; Marks</span>
                                                <span class="review-val font-monospace" id="r_passing_marks">-</span>
                                            </div>
                                        </div>

                                        <div class="review-card">
                                            <div class="review-card-title">
                                                <span><i class="fa-solid fa-map-pin me-1 text-success"></i> Address Summary</span>
                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="goToStep(3)">Edit</button>
                                            </div>
                                            <div class="review-row">
                                                <span class="review-label">Full Address</span>
                                                <span class="review-val text-truncate" style="max-width: 200px;" id="r_address">-</span>
                                            </div>
                                            <div class="review-row">
                                                <span class="review-label">City &amp; State</span>
                                                <span class="review-val" id="r_location">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-2 bg-light rounded-2 border mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="declarationCheck" required>
                                        <label class="form-check-label small text-dark fw-semibold" for="declarationCheck">
                                            I declare all information submitted is true. I agree to proceed to fee submission to complete my admission.
                                        </label>
                                    </div>
                                </div>

                                <div class="wizard-footer">
                                    <button type="button" class="btn-wizard-prev" onclick="goToStep(4)">
                                        <i class="fa-solid fa-arrow-left"></i>
                                        <span>Back to Documents</span>
                                    </button>
                                    <button type="submit" class="btn-wizard-submit" id="submitBtn">
                                        <span>Proceed to Fee Payment</span>
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- STEP 6: MANDATORY COURSE FEE SUBMISSION -->
                            <!-- ============================================ -->
                            <div class="wizard-step-pane" id="stepPane6">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h3 class="step-heading mb-0">Step 6: Course Fee Submission</h3>
                                    <span class="badge bg-warning-subtle text-dark border font-monospace px-2 py-1" style="font-size:11px;" id="dispAppRef">
                                        Ref: ADM-PENDING
                                    </span>
                                </div>
                                <p class="step-subheading">
                                    Your application has been verified. Choose your payment plan below to complete admission and receive your official enrollment receipt.
                                </p>

                                <!-- 100% SEAT BOOKING FEE ADJUSTMENT CALLOUT -->
                                <div id="seatAdjustmentAlert" class="alert alert-success border-success-subtle d-flex align-items-center gap-2 p-2 rounded-2 mb-3" style="display:none;">
                                    <i class="fa-solid fa-circle-check text-success fs-5"></i>
                                    <div class="small text-dark">
                                        <strong class="text-success" id="dispSeatAdjHeading">Seat Reservation Fee Adjusted!</strong> Your booking fee (<span id="dispSeatAdjSubText">₹0</span>) has been directly deducted from your total course fee.
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <!-- Option A: Full Payment with Discount -->
                                    <div class="col-md-6">
                                        <div class="plan-option-card selected" id="optCardFull" onclick="selectPaymentPlan('full')">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <strong class="text-dark d-block">Option A: Full Payment</strong>
                                                    <span class="plan-badge-disc"><i class="fa-solid fa-tag me-1"></i> <?= $full_pay_discount_pct ?>% Instant Discount</span>
                                                </div>
                                                <input type="radio" name="plan_choice" value="full" checked class="form-check-input mt-1">
                                            </div>
                                            <p class="small text-muted mb-2">Pay full course fee at once and get direct fee reduction.</p>
                                            
                                            <div class="p-2 bg-light rounded-2 border">
                                                <div class="d-flex justify-content-between text-muted small">
                                                    <span>Course Total Fee:</span>
                                                    <span class="font-monospace" id="dispBaseFee">₹0</span>
                                                </div>
                                                <div class="d-flex justify-content-between text-success small" id="rowSeatAdjFull" style="display:none;">
                                                    <span>Seat Booking Fee:</span>
                                                    <strong id="dispSeatAdjFull">- ₹0</strong>
                                                </div>
                                                <div class="d-flex justify-content-between text-success small">
                                                    <span>Full Pay Discount:</span>
                                                    <strong id="dispDiscountAmt">- ₹0</strong>
                                                </div>
                                                <div class="d-flex justify-content-between text-dark fw-bold pt-1 border-top mt-1" style="font-size:14px;">
                                                    <span>Final Payable Now:</span>
                                                    <span class="text-success" id="dispFullPayFinal">₹0</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Option B: Part-wise Installments -->
                                    <div class="col-md-6">
                                        <div class="plan-option-card" id="optCardInst" onclick="selectPaymentPlan('installment')">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <strong class="text-dark d-block">Option B: Monthly Installments</strong>
                                                    <span class="badge bg-secondary-subtle text-dark border px-2 py-0.5" style="font-size:11px;" id="dispInstBadge">Monthly Parts</span>
                                                </div>
                                                <input type="radio" name="plan_choice" value="installment" class="form-check-input mt-1">
                                            </div>
                                            <p class="small text-muted mb-2">Split remaining course fees monthly across duration. Pay Part 1 today.</p>

                                            <div class="p-2 bg-light rounded-2 border">
                                                <div class="d-flex justify-content-between text-success small" id="rowSeatAdjInst" style="display:none;">
                                                    <span>Seat Booking Fee:</span>
                                                    <strong id="dispSeatAdjInst">- ₹0</strong>
                                                </div>
                                                <div class="d-flex justify-content-between text-muted small">
                                                    <span>Part 1 (Payable Today):</span>
                                                    <strong class="text-dark" id="dispPart1Amt">₹0</strong>
                                                </div>
                                                <div class="d-flex justify-content-between text-muted small">
                                                    <span>Remaining Parts:</span>
                                                    <span id="dispRemPartsText">Scheduled Monthly</span>
                                                </div>
                                                <div class="d-flex justify-content-between text-dark fw-bold pt-1 border-top mt-1" style="font-size:14px;">
                                                    <span>Net Balance Over Duration:</span>
                                                    <span class="text-primary" id="dispInstTotal">₹0</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Schedule Drawer (For Installment Option) -->
                                <div id="instScheduleDrawer" class="p-3 bg-light rounded-2 border mb-3" style="display: none;">
                                    <div class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-calendar-days text-primary"></i> Installment Breakdown Schedule:
                                    </div>
                                    <div id="instScheduleList" class="d-flex flex-column gap-1">
                                        <!-- Populated via JS -->
                                    </div>
                                </div>

                                <!-- Payment Action Box -->
                                <div class="p-3 bg-white border rounded-2 d-flex align-items-center justify-content-between flex-wrap gap-3">
                                    <div>
                                        <span class="text-muted small d-block">Amount to pay now:</span>
                                        <h4 class="fw-bold text-success mb-0" id="dispPayNowTotal">₹0.00</h4>
                                    </div>
                                    <div>
                                        <button type="button" class="btn-wizard-submit" id="btnPayCourseFee" onclick="initiateCourseFeePayment()">
                                            <i class="fa-solid fa-lock"></i>
                                            <span id="btnPayText">Pay Now  &amp; Confirm</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="text-center mt-2">
                                    <small class="text-muted" style="font-size: 11.5px;">
                                        <i class="fa-solid fa-shield-halved text-success me-1"></i> Official 256-Bit Encrypted Secure Gateway (UPI, Cards, Netbanking). Instant Receipt Emailed.
                                    </small>
                                </div>
                            </div>

                        </form>

                    </div>

                </div>

            </div>
        </div>

    </div>
</section>

<!-- Razorpay Official Checkout SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<!-- WIZARD STEPPER & PAYMENT JAVASCRIPT -->
<script>
let currentStep = 1;
const totalSteps = 6;
let submittedAdmissionId = 0;
let submittedDraftToken = '';
let feeCalculationData = null;
let selectedPlan = 'full';

function showFormLoader(title, subtitle) {
    const overlay = document.getElementById('formLoadingOverlay');
    if (overlay) {
        document.getElementById('formLoadingTitle').textContent = title || 'Processing...';
        document.getElementById('formLoadingSubtitle').textContent = subtitle || 'Please wait';
        overlay.style.display = 'flex';
    }
}

function hideFormLoader() {
    const overlay = document.getElementById('formLoadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function updateProgressBar(step) {
    const percentage = ((step - 1) / (totalSteps - 1)) * 100;
    const bar = document.getElementById('stepperProgressBar');
    if (bar) bar.style.width = percentage + '%';

    for (let i = 1; i <= totalSteps; i++) {
        const item = document.getElementById('stepIndicator' + i);
        if (item) {
            item.classList.remove('active', 'completed');
            if (i < step) {
                item.classList.add('completed');
                item.querySelector('.step-circle').innerHTML = '<i class="fa-solid fa-check"></i>';
            } else if (i === step) {
                item.classList.add('active');
                const icons = ['fa-user', 'fa-graduation-cap', 'fa-map-location-dot', 'fa-file-arrow-up', 'fa-clipboard-check', 'fa-credit-card'];
                item.querySelector('.step-circle').innerHTML = '<i class="fa-solid ' + icons[i - 1] + '"></i>';
            } else {
                const icons = ['fa-user', 'fa-graduation-cap', 'fa-map-location-dot', 'fa-file-arrow-up', 'fa-clipboard-check', 'fa-credit-card'];
                item.querySelector('.step-circle').innerHTML = '<i class="fa-solid ' + icons[i - 1] + '"></i>';
            }
        }
    }
}

function validateCurrentStep(step) {
    const pane = document.getElementById('stepPane' + step);
    if (!pane) return true;

    const inputs = pane.querySelectorAll('input[required], select[required], textarea[required]');
    for (let input of inputs) {
        if (!input.checkValidity() || input.value.trim() === '') {
            input.focus();
            input.classList.add('border-danger');
            setTimeout(() => input.classList.remove('border-danger'), 3000);
            return false;
        }
    }
    return true;
}

function goToStep(step) {
    if (step > currentStep && currentStep < 5) {
        if (!validateCurrentStep(currentStep)) return;
    }

    currentStep = step;

    document.querySelectorAll('.wizard-step-pane').forEach(p => p.classList.remove('active'));
    const targetPane = document.getElementById('stepPane' + step);
    if (targetPane) {
        targetPane.classList.add('active');
    }

    updateProgressBar(step);

    const card = document.getElementById('wizardCard');
    if (card) {
        const offset = card.getBoundingClientRect().top + window.scrollY - 70;
        window.scrollTo({ top: offset, behavior: 'smooth' });
    }
}

function jumpToStep(step) {
    if (step < currentStep && currentStep < 6) {
        goToStep(step);
    }
}

function prepareReviewAndGo(step) {
    if (!validateCurrentStep(currentStep)) return;

    document.getElementById('r_name').textContent = document.getElementById('f_name').value || '-';
    document.getElementById('r_father').textContent = document.getElementById('f_father_name').value || '-';
    document.getElementById('r_dob_gender').textContent = (document.getElementById('f_dob').value || '-') + ' (' + (document.getElementById('f_gender').value || '-') + ')';
    document.getElementById('r_aadhaar').textContent = document.getElementById('f_aadhaar').value || '-';
    document.getElementById('r_mobile').textContent = document.getElementById('f_mobile').value || '-';
    document.getElementById('r_email').textContent = document.getElementById('f_email').value || '-';

    document.getElementById('r_course').textContent = document.getElementById('f_course').value || '-';
    document.getElementById('r_education').textContent = document.getElementById('f_education').value || '-';
    document.getElementById('r_board').textContent = document.getElementById('f_board').value || '-';
    document.getElementById('r_passing_marks').textContent = (document.getElementById('f_passing_year').value || '-') + ' | ' + (document.getElementById('f_percentage').value || '-');

    document.getElementById('r_address').textContent = document.getElementById('f_address').value || '-';
    document.getElementById('r_location').textContent = (document.getElementById('f_city').value || '-') + ', ' + (document.getElementById('f_state').value || '-') + ' - ' + (document.getElementById('f_pincode').value || '-');

    goToStep(step);
}

function updateCoursePreview() {
    const select = document.getElementById('f_course');
    const selectedOption = select.options[select.selectedIndex];
    const previewName = document.getElementById('previewCourseName');
    const previewMeta = document.getElementById('previewCourseMeta');

    if (select.value) {
        previewName.textContent = select.value;
        const dur = selectedOption.dataset.duration || 'Flexible';
        const fee = selectedOption.dataset.fee || '';
        previewMeta.textContent = 'Duration: ' + dur + (fee ? (' • Fee: ' + fee) : '') + ' • 100% Placement Support';
    } else {
        previewName.textContent = 'Please select a course above';
        previewMeta.textContent = 'Duration: Industry standard certification • 100% Placement Support';
    }
}

function previewFileName(input) {
    const chosenDiv = input.parentElement.querySelector('.file-chosen-name');
    if (input.files && input.files[0]) {
        chosenDiv.textContent = '✓ ' + input.files[0].name;
        chosenDiv.style.display = 'block';
    }
}

function selectPaymentPlan(plan) {
    selectedPlan = plan;
    const cardFull = document.getElementById('optCardFull');
    const cardInst = document.getElementById('optCardInst');
    const drawer = document.getElementById('instScheduleDrawer');

    if (plan === 'full') {
        cardFull.classList.add('selected');
        cardInst.classList.remove('selected');
        drawer.style.display = 'none';
        if (feeCalculationData) {
            document.getElementById('dispPayNowTotal').textContent = feeCalculationData.full_payment.formatted_final;
            document.getElementById('btnPayText').textContent = 'Pay Full Fee (' + feeCalculationData.full_payment.formatted_final + ') & Confirm';
        }
    } else {
        cardInst.classList.add('selected');
        cardFull.classList.remove('selected');
        drawer.style.display = 'block';
        if (feeCalculationData) {
            document.getElementById('dispPayNowTotal').textContent = feeCalculationData.installment_payment.formatted_part_amount;
            document.getElementById('btnPayText').textContent = 'Pay Part 1 (' + feeCalculationData.installment_payment.formatted_part_amount + ') & Confirm';
        }
    }
}

// Fetch fee calculations from backend
function fetchFeeCalculations(courseTitle) {
    showFormLoader('Calculating Course Fees...', 'Fetching discount and installment plan');

    const studentIdInput = document.querySelector('input[name="student_id"]');
    const stdIdVal = studentIdInput ? studentIdInput.value : '';

    fetch('process-fee-payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: 'calculate_fee', 
            course: courseTitle,
            student_id: stdIdVal 
        })
    })
    .then(res => res.json())
    .then(data => {
        hideFormLoader();
        if (data.success) {
            feeCalculationData = data;
            
            // Base Fee
            document.getElementById('dispBaseFee').textContent = data.course.formatted_fee;

            // Seat Booking Fee Adjusted
            const seatAlert = document.getElementById('seatAdjustmentAlert');
            const rowSeatAdjFull = document.getElementById('rowSeatAdjFull');
            const rowSeatAdjInst = document.getElementById('rowSeatAdjInst');

            if (data.seat_fee_adjusted && data.seat_fee_adjusted > 0) {
                if (seatAlert) seatAlert.style.display = 'flex';
                const heading = document.getElementById('dispSeatAdjHeading');
                const subText = document.getElementById('dispSeatAdjSubText');
                if (heading) heading.textContent = data.formatted_seat_adjusted + ' Seat Reservation Fee Adjusted!';
                if (subText) subText.textContent = data.formatted_seat_adjusted;

                if (rowSeatAdjFull) {
                    rowSeatAdjFull.style.display = 'flex';
                    document.getElementById('dispSeatAdjFull').textContent = '- ' + data.formatted_seat_adjusted;
                }
                if (rowSeatAdjInst) {
                    rowSeatAdjInst.style.display = 'flex';
                    document.getElementById('dispSeatAdjInst').textContent = '- ' + data.formatted_seat_adjusted;
                }
            } else {
                if (seatAlert) seatAlert.style.display = 'none';
                if (rowSeatAdjFull) rowSeatAdjFull.style.display = 'none';
                if (rowSeatAdjInst) rowSeatAdjInst.style.display = 'none';
            }

            // Full Pay
            document.getElementById('dispDiscountAmt').textContent = '- ' + data.full_payment.formatted_discount;
            document.getElementById('dispFullPayFinal').textContent = data.full_payment.formatted_final;

            // Installment
            document.getElementById('dispInstBadge').textContent = data.installment_payment.total_parts + ' Monthly Parts';
            document.getElementById('dispPart1Amt').textContent = data.installment_payment.formatted_part_amount;
            document.getElementById('dispRemPartsText').textContent = (data.installment_payment.total_parts - 1) + ' Monthly Parts Remaining';
            document.getElementById('dispInstTotal').textContent = data.installment_payment.formatted_net_payable;

            // Render Schedule
            const list = document.getElementById('instScheduleList');
            list.innerHTML = '';
            data.installment_payment.schedule.forEach(item => {
                const row = document.createElement('div');
                row.className = 'd-flex justify-content-between align-items-center p-2 rounded border bg-white small';
                row.innerHTML = `
                    <div>
                        <strong class="${item.is_now ? 'text-success' : 'text-dark'}">${item.title}</strong>
                        <div class="text-muted" style="font-size:11px;">Due Date: <strong>${item.due_date}</strong> ${item.is_now ? '(Today)' : ''}</div>
                    </div>
                    <span class="fw-bold font-monospace ${item.is_now ? 'text-success' : 'text-dark'}">₹${Number(item.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</span>
                `;
                list.appendChild(row);
            });

            // Initial view
            selectPaymentPlan('full');
        } else {
            alert(data.message || 'Unable to load course fee data.');
        }
    })
    .catch(err => {
        hideFormLoader();
        console.error(err);
        alert('Network error while calculating fee details.');
    });
}

// FORM SUBMISSION (STEP 5 -> AJAX -> STEP 6)
document.getElementById('admissionForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const dec = document.getElementById('declarationCheck');
    if (!dec.checked) {
        alert('Please accept the declaration before proceeding.');
        dec.focus();
        return;
    }

    showFormLoader('Saving Application Documents...', 'Generating application reference number');
    
    const formData = new FormData(this);

    fetch('send-admission.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        hideFormLoader();
        if (data.success) {
            submittedDraftToken = data.draft_token || '';
            submittedAdmissionId = data.admission_id || 0;
            document.getElementById('dispAppRef').textContent = 'Ref: ' + data.app_ref;

            // Move to mandatory fee payment Step 6
            goToStep(6);

            // Fetch live fee and installment schedule
            fetchFeeCalculations(data.course);
        } else {
            alert(data.message || 'Application submission failed.');
        }
    })
    .catch(err => {
        hideFormLoader();
        console.error(err);
        alert('Error uploading application files. Please check file sizes and try again.');
    });
});

// INITIATE RAZORPAY PAYMENT
function initiateCourseFeePayment() {
    if (!feeCalculationData || (!submittedDraftToken && submittedAdmissionId <= 0)) {
        alert('Application data missing. Please review and re-submit your application.');
        return;
    }

    const payAmount = (selectedPlan === 'full') 
        ? feeCalculationData.full_payment.final_amount 
        : feeCalculationData.installment_payment.part_amount;
    
    const payAmountPaise = Math.round(payAmount * 100);

    const studentName = document.getElementById('f_name').value;
    const studentEmail = document.getElementById('f_email').value;
    const studentPhone = document.getElementById('f_mobile').value;

    const options = {
        "key": "<?= htmlspecialchars($key_id) ?>",
        "amount": payAmountPaise,
        "currency": "INR",
        "name": "Finchskills Institute",
        "description": "Course Fee (" + (selectedPlan === 'full' ? 'Full Payment' : 'Part 1 Installment') + ")",
        "image": "img/favicon.png",
        "prefill": {
            "name": studentName,
            "email": studentEmail,
            "contact": studentPhone
        },
        "theme": {
            "color": "#fe7c03"
        },
        "handler": function (response) {
            showFormLoader('Verifying Payment & Finalizing Admission...', 'Official receipt and enrollment generation');

            fetch('process-fee-payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'verify_course_fee',
                    draft_token: submittedDraftToken,
                    admission_id: submittedAdmissionId,
                    razorpay_payment_id: response.razorpay_payment_id,
                    plan_type: selectedPlan,
                    paid_amount: payAmount
                })
            })
            .then(res => res.json())
            .then(data => {
                hideFormLoader();
                if (data.success) {
                    window.location.href = 'student-profile.php?payment=success&ref=' + encodeURIComponent(data.receipt_no);
                } else {
                    alert('Payment recorded. ' + (data.message || 'Please check your dashboard.'));
                    window.location.href = 'student-profile.php';
                }
            })
            .catch(err => {
                hideFormLoader();
                console.error(err);
                window.location.href = 'student-profile.php?payment=done';
            });
        }
    };

    const rzp = new Razorpay(options);
    rzp.on('payment.failed', function (response) {
        alert("Payment failed: " + response.error.description);
    });
    rzp.open();
}
</script>

<?php include "footer.php"; ?>
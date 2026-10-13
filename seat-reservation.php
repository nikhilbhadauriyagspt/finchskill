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

// Require Student Registration / Login first
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header("Location: register?redirect=seat-reservation");
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

// Fetch Razorpay Settings
$settings = [];
$s_stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'razorpay_%' OR setting_key = 'seat_booking_fee'");
while ($r = $s_stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$r['setting_key']] = $r['setting_value'];
}

$mode = $settings['razorpay_mode'] ?? 'test';
$key_id = ($mode === 'live') ? ($settings['razorpay_live_key_id'] ?? '') : ($settings['razorpay_test_key_id'] ?? 'rzp_test_1DP5mmOlF5G5ag');
$fee_amount = (float)($settings['seat_booking_fee'] ?? 999);
$amount_paise = $fee_amount * 100;

// Fetch all active courses for seat reservation selection
$courses_stmt = $pdo->query("SELECT id, title, duration, study_mode, fee FROM courses WHERE status = 'active' ORDER BY title ASC");
$active_courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

$preselect_id = (int)($_GET['course_id'] ?? (!empty($student['booked_course_id']) ? $student['booked_course_id'] : 0));
$preselect_param = trim($_GET['course'] ?? (!empty($student['booked_course']) ? $student['booked_course'] : ''));

$selected_course = null;
if (!empty($active_courses)) {
    foreach ($active_courses as $c) {
        if (($preselect_id > 0 && (int)$c['id'] === $preselect_id) || 
            (!empty($preselect_param) && (strcasecmp($c['title'], $preselect_param) === 0))) {
            $selected_course = $c;
            break;
        }
    }
}

if (!function_exists('getCourseIconClass')) {
    function getCourseIconClass($title) {
        $t = strtolower($title);
        if (strpos($t, 'cabin') !== false || strpos($t, 'air hostess') !== false) return 'fa-solid fa-plane';
        if (strpos($t, 'airport') !== false || strpos($t, 'air ticketing') !== false) return 'fa-solid fa-plane-departure';
        if (strpos($t, 'cruise') !== false) return 'fa-solid fa-ship';
        if (strpos($t, 'hotel') !== false || strpos($t, 'hospitality') !== false || strpos($t, 'guest') !== false) return 'fa-solid fa-hotel';
        if (strpos($t, 'ground') !== false) return 'fa-solid fa-id-badge';
        if (strpos($t, 'personality') !== false) return 'fa-solid fa-user-tie';
        if (strpos($t, 'tourism') !== false || strpos($t, 'travel') !== false) return 'fa-solid fa-earth-americas';
        return 'fa-solid fa-graduation-cap';
    }
}

$page_title = "Reserve Your Seat - Finchskills Institute";
include "header.php";
?>

<style>
.seat-section {
    padding: 50px 0 80px;
    background: #fafbfc;
    min-height: 80vh;
}
.seat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 36px 30px;
    max-width: 620px;
    margin: 0 auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04) !important;
}
.seat-header-icon {
    width: 54px;
    height: 54px;
    border-radius: 14px;
    background: #fff7ed;
    border: 1px solid #ffedd5;
    color: #fe7c03;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 14px;
}
.student-badge-strip {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 22px;
}
.student-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fe7c03;
    background: #ffffff;
    flex-shrink: 0;
}
.fee-box {
    background: #fafbfc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 22px;
}
.fee-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}
.fee-row:last-child {
    border-bottom: none;
    padding-top: 14px;
    margin-top: 6px;
    border-top: 2px dashed #e2e8f0;
}
.pay-btn {
    background: #047857;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    padding: 14px 20px;
    font-size: 15px;
    font-weight: 700;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(4, 120, 87, 0.2);
}
.pay-btn:hover {
    background: #065f46;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(4, 120, 87, 0.25);
}

/* FANCY COURSE SELECTION TRIGGER */
.fancy-course-trigger {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    padding: 13px 15px;
    display: flex;
    align-items: center;
    gap: 13px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    user-select: none;
    outline: none;
    position: relative;
}
.fancy-course-trigger:hover,
.fancy-course-trigger:focus-visible {
    border-color: #fe7c03;
    background: #fffdfb;
    box-shadow: 0 4px 14px rgba(254, 124, 3, 0.12);
}
.fancy-course-trigger.active-selected {
    border-color: #fe7c03;
    background: linear-gradient(180deg, #ffffff 0%, #fffaf5 100%);
}
.trigger-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 11px;
    background: #fff7ed;
    border: 1px solid #ffedd5;
    color: #fe7c03;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 19px;
    transition: transform 0.2s ease;
}
.fancy-course-trigger:hover .trigger-icon-box {
    transform: scale(1.05);
}
.trigger-label {
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #64748b;
    display: block;
    margin-bottom: 2px;
}
.trigger-title {
    font-size: 14.5px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.35;
}
.trigger-title.placeholder-title {
    font-weight: 500;
    color: #94a3b8;
}
.trigger-meta {
    margin-top: 5px;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}
.trigger-meta .badge {
    font-size: 11px;
    font-weight: 600;
    padding: 3.5px 7px;
    border-radius: 6px;
}
.trigger-arrow {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}
.trigger-chevron {
    font-size: 13px;
    transition: transform 0.2s ease;
}
.fancy-course-trigger:hover .trigger-chevron {
    transform: translateX(2px);
    color: #fe7c03 !important;
}

/* COURSE MODAL & BOTTOM SHEET STYLING */
.course-modal-dialog {
    max-width: 520px;
    margin: 1.75rem auto;
}
.course-modal-content {
    border-radius: 18px;
    border: none;
    box-shadow: 0 20px 45px rgba(0,0,0,0.18);
    overflow: hidden;
}
.modal-header-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #fff7ed;
    color: #fe7c03;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.course-search-group {
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    overflow: hidden;
    transition: border-color 0.2s;
}
.course-search-group:focus-within {
    border-color: #fe7c03;
    box-shadow: 0 0 0 3px rgba(254, 124, 3, 0.15);
}
.course-search-group input {
    font-size: 14px;
    padding: 9px 12px;
}
.course-options-list {
    max-height: 360px;
    overflow-y: auto;
    padding-right: 4px;
}
.course-options-list::-webkit-scrollbar {
    width: 5px;
}
.course-options-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

/* INDIVIDUAL COURSE CARD IN LIST */
.course-opt-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 9px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}
.course-opt-card:hover {
    border-color: #fdba74;
    background: #fffbf7;
    transform: translateY(-1px);
}
.course-opt-card.active {
    border-color: #fe7c03;
    background: #fff7ed;
    box-shadow: 0 4px 12px rgba(254, 124, 3, 0.12);
}
.course-opt-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #f1f5f9;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    transition: all 0.2s;
}
.course-opt-card.active .course-opt-icon {
    background: #fe7c03;
    color: #ffffff;
}
.course-opt-title {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.35;
}
.course-opt-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
.course-tag-pill {
    background: #f8fafc;
    color: #334155;
    border: 1px solid #e2e8f0;
    font-size: 11px;
    font-weight: 600;
    border-radius: 6px;
    padding: 3px 6px;
}
.course-tag-mode {
    background: #f1f5f9;
    color: #64748b;
    font-size: 11px;
    font-weight: 500;
    border-radius: 6px;
    padding: 3px 6px;
}
.course-opt-card.active .course-tag-pill {
    background: #ffedd5;
    border-color: #fed7aa;
    color: #9a3412;
}
.course-opt-radio {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: transparent;
    flex-shrink: 0;
    transition: all 0.2s;
}
.course-opt-card.active .course-opt-radio {
    background: #fe7c03;
    border-color: #fe7c03;
    color: #ffffff;
}

/* MOBILE OPTIMIZATIONS (<768px / <576px) */
@media (max-width: 767.98px) {
    .course-modal-dialog {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        margin: 0;
        max-width: 100%;
        transform: translate(0, 100%);
        transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .modal.show .course-modal-dialog {
        transform: translate(0, 0) !important;
    }
    .course-modal-content {
        border-radius: 20px 20px 0 0;
        max-height: 85vh;
    }
    .modal-pull-handle {
        width: 44px;
        height: 5px;
        background: #cbd5e1;
        border-radius: 10px;
        margin: 0 auto;
    }
    .course-options-list {
        max-height: calc(85vh - 195px);
    }
}

@media (max-width: 575.98px) {
    .seat-section {
        padding: 24px 8px 60px;
    }
    .seat-card {
        padding: 20px 15px;
        border-radius: 14px;
    }
    .seat-header-icon {
        width: 46px;
        height: 46px;
        font-size: 19px;
        margin-bottom: 10px;
    }
    .seat-card h2 {
        font-size: 20px !important;
    }
    .student-badge-strip {
        padding: 12px 14px;
        gap: 12px;
        margin-bottom: 18px;
    }
    .student-avatar {
        width: 44px;
        height: 44px;
    }
    .fee-box {
        padding: 14px 14px;
        margin-bottom: 18px;
    }
    .pay-btn {
        padding: 13px 16px;
        font-size: 14.5px;
    }
    .fancy-course-trigger {
        padding: 12px 13px;
        gap: 11px;
    }
    .trigger-icon-box {
        width: 40px;
        height: 40px;
        font-size: 17px;
    }
    .trigger-title {
        font-size: 14px;
    }
}
</style>

<section class="seat-section">
    <div class="container">
        <div class="seat-card">

            <div class="text-center mb-4">
                <div class="seat-header-icon">
                    <i class="fa-solid fa-chair"></i>
                </div>
                <h2 class="fw-bold text-dark mb-1" style="font-size: 24px;">Reserve Your Program Seat</h2>
                <p class="text-muted small mb-0">Secure your seat for upcoming batch orientation &amp; practical labs.</p>
            </div>

            <?php if (!empty($student)): ?>
            <!-- CANDIDATE BADGE STRIP -->
            <div class="student-badge-strip">
                <?php if (!empty($student['photo']) && file_exists(__DIR__ . '/' . $student['photo'])): ?>
                    <img src="<?= htmlspecialchars($student['photo']) ?>" alt="Avatar" class="student-avatar">
                <?php else: ?>
                    <div class="student-avatar d-flex align-items-center justify-content-center text-secondary bg-white">
                        <i class="fa-solid fa-user fs-4"></i>
                    </div>
                <?php endif; ?>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between">
                        <strong class="text-dark" style="font-size: 15px;"><?= htmlspecialchars($student['name']) ?></strong>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small">
                            <i class="fa-solid fa-circle-check me-1"></i> Registered Student
                        </span>
                    </div>
                    <div class="text-muted small mt-0.5 font-monospace">ID: <strong class="text-primary"><?= htmlspecialchars($student['student_id']) ?></strong> &bull; <?= htmlspecialchars($student['phone']) ?></div>
                </div>
            </div>
            <?php else: ?>
            <!-- GUEST STUDENT DETAILS -->
            <div class="guest-details-box mb-4 p-3 bg-white rounded-3 border" style="box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 text-dark" style="font-size: 14px;"><i class="fa-solid fa-user-check text-warning me-2"></i>Student Information</h6>
                    <a href="student-login?redirect=seat-reservation" class="small text-decoration-none fw-semibold" style="color: #fe7c03;">Already registered? Log In</a>
                </div>
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary mb-1">Full Name *</label>
                        <input type="text" id="guestName" class="form-control" style="font-size: 13.5px; border-radius: 8px;" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary mb-1">Mobile Number *</label>
                        <input type="tel" id="guestPhone" class="form-control" style="font-size: 13.5px; border-radius: 8px;" maxlength="10" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary mb-1">Email Address *</label>
                        <input type="email" id="guestEmail" class="form-control" style="font-size: 13.5px; border-radius: 8px;" required>
                    </div>
                </div>
                <div id="guestError" class="text-danger small mt-2" style="display: none;"></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($student) && ($student['payment_status'] ?? '') === 'Paid'): ?>
                <!-- ALREADY PAID STATE -->
                <div class="alert alert-success d-flex align-items-start gap-3 p-3 rounded-3 mb-4">
                    <i class="fa-solid fa-circle-check fs-2 text-success mt-1"></i>
                    <div>
                        <h6 class="fw-bold text-success mb-1">Seat Confirmed!</h6>
                        <p class="mb-1 small text-secondary">
                            Your reservation fee payment (ID: <code><?= htmlspecialchars($student['payment_id']) ?></code>) has been successfully verified.
                        </p>
                        <?php if (!empty($student['booked_course'])): ?>
                            <div class="small text-dark mt-1">
                                <strong>Reserved Program:</strong> <span class="badge bg-primary text-white"><?= htmlspecialchars($student['booked_course']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="small text-success fw-semibold mt-2">
                            <i class="fa-solid fa-check-double me-1"></i> ₹<?= number_format((float)($student['payment_amount'] ?: $fee_amount), 0) ?> will be automatically deducted from your final course fee!
                        </div>
                    </div>
                </div>
                <div class="text-center d-flex flex-column gap-2">
                    <a href="form-submission.php<?= !empty($student['booked_course']) ? ('?course=' . urlencode($student['booked_course'])) : '' ?>" class="pay-btn" style="background: #fe7c03;">
                        <span>Apply For Course Now (₹<?= number_format((float)($student['payment_amount'] ?: $fee_amount), 0) ?> Auto-Deducted)</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    <a href="student-profile.php" class="btn btn-outline-secondary py-2" style="font-size: 14px; border-radius: 9px;">
                        <span>Go to My Profile &amp; Dashboard</span>
                    </a>
                </div>
            <?php else: ?>

                <!-- 100% FEE ADJUSTMENT GUARANTEE CALLOUT -->
                <div class="alert alert-warning border-warning-subtle d-flex align-items-start gap-3 p-3 rounded-3 mb-3">
                    <i class="fa-solid fa-shield-halved text-warning fs-3 mt-1"></i>
                    <div>
                        <strong class="text-dark d-block">100% Fee Adjustment Policy:</strong>
                        <span class="small text-secondary">
                            Your ₹<?= number_format($fee_amount, 0) ?> seat reservation fee will be <strong>directly deducted</strong> from your total course fee during final admission. You only pay the remaining balance!
                        </span>
                    </div>
                </div>

                <!-- COURSE SELECTION (FANCY & MOBILE-FIRST) -->
                <?php
                $has_selected = !empty($selected_course);
                $selected_course_id = $has_selected ? $selected_course['id'] : '';
                $selected_course_title = $has_selected ? $selected_course['title'] : '';
                $selected_course_duration = $has_selected ? $selected_course['duration'] : '';
                $selected_course_mode = $has_selected ? ($selected_course['study_mode'] ?? '') : '';
                $selected_course_icon = $has_selected ? getCourseIconClass($selected_course_title) : 'fa-graduation-cap';
                ?>
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark mb-1.5 d-flex align-items-center justify-content-between" style="font-size: 13.5px;">
                        <span><i class="fa-solid fa-graduation-cap text-warning me-1"></i> Select Course Program: <span class="text-danger">*</span></span>
                        <span class="badge bg-light text-secondary border fw-normal" style="font-size: 11px;">Tap below to choose</span>
                    </label>

                    <!-- Interactive Fancy Trigger Box -->
                    <div id="coursePickerTrigger" class="fancy-course-trigger <?= $has_selected ? 'active-selected' : '' ?>" tabindex="0" role="button" aria-haspopup="dialog" aria-expanded="false">
                        <div class="trigger-icon-box" id="triggerIconBox">
                            <i class="<?= $selected_course_icon ?>"></i>
                        </div>
                        <div class="trigger-info flex-grow-1">
                            <span class="trigger-label" id="triggerLabel">
                                <?= $has_selected ? '<i class="fa-solid fa-circle-check text-success me-1"></i> Selected Course Program' : 'Target Program' ?>
                            </span>
                            <div class="trigger-title <?= $has_selected ? 'selected' : 'placeholder-title' ?>" id="triggerTitle">
                                <?= $has_selected ? htmlspecialchars($selected_course_title) : 'Tap here to choose your course program...' ?>
                            </div>
                            <div class="trigger-meta" id="triggerMeta" style="<?= $has_selected ? 'display:flex;' : 'display:none;' ?>">
                                <span class="badge bg-light text-dark border duration-pill" id="triggerDuration">
                                    <i class="fa-regular fa-clock me-1 text-primary"></i> <?= htmlspecialchars($selected_course_duration) ?>
                                </span>
                                <span class="badge bg-light text-secondary border mode-pill" id="triggerMode" style="<?= !empty($selected_course_mode) ? 'display:inline-block;' : 'display:none;' ?>">
                                    <?= htmlspecialchars($selected_course_mode) ?>
                                </span>
                            </div>
                        </div>
                        <div class="trigger-arrow">
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 d-none d-sm-inline-flex align-items-center gap-1 small">
                                <span>Browse</span>
                            </span>
                            <i class="fa-solid fa-chevron-right text-muted trigger-chevron ms-1"></i>
                        </div>
                    </div>

                    <!-- Hidden native select element for seamless backend & Razorpay compatibility -->
                    <select id="courseSelect" class="d-none" required>
                        <option value="">-- Choose Course for Reservation --</option>
                        <?php foreach ($active_courses as $c): ?>
                            <?php
                            $is_selected = ($preselect_id === (int)$c['id']) || 
                                           (!empty($preselect_param) && (strcasecmp($c['title'], $preselect_param) === 0));
                            ?>
                            <option value="<?= $c['id'] ?>" 
                                    data-title="<?= htmlspecialchars($c['title']) ?>" 
                                    data-duration="<?= htmlspecialchars($c['duration']) ?>"
                                    data-mode="<?= htmlspecialchars($c['study_mode'] ?? '') ?>"
                                    <?= $is_selected ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['title']) ?><?= !empty($c['duration']) ? ' (' . htmlspecialchars($c['duration']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div id="courseSelectError" class="text-danger small mt-2 p-2 rounded-2 bg-danger-subtle border border-danger-subtle" style="display:none;">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> Please tap above and select a course program before proceeding to payment.
                    </div>
                    <div class="text-muted small mt-1.5 d-flex align-items-center justify-content-between" style="font-size: 11.5px;">
                        <span><i class="fa-solid fa-shield-heart text-success me-1"></i> 100% reservation fee adjusted in final admission</span>
                        <span><i class="fa-solid fa-list-check text-primary me-1"></i> <?= count($active_courses) ?> Programs</span>
                    </div>
                </div>

                <!-- FANCY COURSE SELECTION MODAL / BOTTOM SHEET -->
                <div class="modal fade" id="coursePickerModal" tabindex="-1" aria-labelledby="coursePickerModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable course-modal-dialog">
                        <div class="modal-content course-modal-content">
                            
                            <!-- Mobile pull handle -->
                            <div class="d-md-none text-center pt-2 pb-1">
                                <div class="modal-pull-handle"></div>
                            </div>

                            <div class="modal-header border-0 pb-2 px-3 pt-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="modal-header-icon">
                                        <i class="fa-solid fa-graduation-cap"></i>
                                    </div>
                                    <div>
                                        <h5 class="modal-title fw-bold text-dark mb-0" id="coursePickerModalLabel" style="font-size: 16.5px;">
                                            Select Course Program
                                        </h5>
                                        <p class="text-muted small mb-0" style="font-size: 11.5px;">Choose your course to reserve seat &amp; lock admission</p>
                                    </div>
                                </div>
                                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <!-- Instant Search Input -->
                            <div class="px-3 pt-1 pb-2">
                                <div class="input-group course-search-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input type="text" id="courseSearchInput" class="form-control border-start-0 ps-0 shadow-none" placeholder="Search course by name..." autocomplete="off">
                                    <button class="btn btn-outline-secondary border-start-0 bg-white text-muted d-none" type="button" id="clearSearchBtn">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="modal-body px-3 py-2">
                                <div class="course-options-list" id="courseOptionsList">
                                    <?php foreach ($active_courses as $c): ?>
                                        <?php
                                        $is_sel = ($preselect_id === (int)$c['id']) || 
                                                  (!empty($preselect_param) && (strcasecmp($c['title'], $preselect_param) === 0));
                                        $icon = getCourseIconClass($c['title']);
                                        ?>
                                        <div class="course-opt-card <?= $is_sel ? 'active' : '' ?>" 
                                             data-id="<?= $c['id'] ?>"
                                             data-title="<?= htmlspecialchars($c['title']) ?>"
                                             data-duration="<?= htmlspecialchars($c['duration']) ?>"
                                             data-mode="<?= htmlspecialchars($c['study_mode'] ?? '') ?>"
                                             data-icon="<?= $icon ?>">
                                            
                                            <div class="course-opt-icon">
                                                <i class="<?= $icon ?>"></i>
                                            </div>
                                            
                                            <div class="course-opt-content flex-grow-1">
                                                <h6 class="course-opt-title mb-1"><?= htmlspecialchars($c['title']) ?></h6>
                                                <div class="course-opt-tags">
                                                    <?php if (!empty($c['duration'])): ?>
                                                        <span class="badge course-tag-pill">
                                                            <i class="fa-regular fa-clock me-1 text-primary"></i><?= htmlspecialchars($c['duration']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($c['study_mode'])): ?>
                                                        <span class="badge course-tag-mode ms-1">
                                                            <i class="fa-solid fa-building-columns me-1 text-muted"></i><?= htmlspecialchars($c['study_mode']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="course-opt-radio">
                                                <i class="fa-solid fa-check"></i>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div id="noCoursesFound" class="text-center py-4 text-muted" style="display:none;">
                                    <i class="fa-solid fa-magnifying-glass fs-3 text-secondary opacity-50 mb-2"></i>
                                    <p class="mb-0 fw-semibold">No matching courses found</p>
                                    <small>Try a different keyword</small>
                                </div>
                            </div>

                            <div class="modal-footer border-0 px-3 py-2 bg-light rounded-bottom-4 d-flex justify-content-between">
                                <span class="text-muted small" style="font-size: 11.5px;">
                                    <i class="fa-solid fa-shield-halved text-success me-1"></i> Guaranteed Seat Allocation
                                </span>
                                <button type="button" class="btn btn-sm btn-secondary px-3 py-1.5 rounded-3" data-bs-dismiss="modal">
                                    Close
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- FEE DETAILS & BENEFITS -->
                <div class="fee-box">
                    <div class="fee-row">
                        <span class="text-secondary">Registration &amp; Batch Reservation Fee</span>
                        <strong class="text-dark">₹<?= number_format($fee_amount, 2) ?></strong>
                    </div>
                    <div class="fee-row">
                        <span class="text-secondary">Fee Adjustment in Final Course Fee</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">- ₹<?= number_format($fee_amount, 2) ?> (100% Adjusted)</span>
                    </div>
                    <div class="fee-row">
                        <span class="text-secondary">Study Material &amp; Induction Kit</span>
                        <span class="badge bg-light text-success border border-success-subtle">Included</span>
                    </div>
                    <div class="fee-row">
                        <span class="text-secondary">Dedicated Counselor Guidance</span>
                        <span class="badge bg-light text-success border border-success-subtle">Included</span>
                    </div>
                    <div class="fee-row">
                        <div>
                            <strong class="text-dark d-block">Total Payable Now</strong>
                            <small class="text-muted" style="font-size: 11.5px;">Secures guaranteed admission in next batch</small>
                        </div>
                        <h4 class="fw-bold mb-0" style="color: #047857;">₹<?= number_format($fee_amount, 2) ?></h4>
                    </div>
                </div>

                <!-- PAYMENT ACTIONS (NO SKIP) -->
                <div class="d-grid gap-2 mb-3">
                    <button type="button" id="payNowBtn" class="pay-btn">
                        <i class="fa-solid fa-lock"></i>
                        <span>Pay ₹<?= number_format($fee_amount, 0) ?>  &amp; Confirm Seat</span>
                    </button>
                </div>

                <div class="text-center">
                    <small class="text-muted" style="font-size: 11.5px;">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i> 256-Bit Encrypted Secure Razorpay Gateway (UPI, Cards, Netbanking)
                    </small>
                </div>

            <?php endif; ?>

        </div>
    </div>
</section>

<!-- Razorpay Official Checkout Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const payBtn = document.getElementById('payNowBtn');
    const courseSelect = document.getElementById('courseSelect');
    const courseError = document.getElementById('courseSelectError');
    const coursePickerTrigger = document.getElementById('coursePickerTrigger');
    const coursePickerModalEl = document.getElementById('coursePickerModal');
    
    // Modal instance helper
    function getCourseModal() {
        if (!coursePickerModalEl) return null;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            return bootstrap.Modal.getOrCreateInstance(coursePickerModalEl);
        }
        return null;
    }

    function openCourseModal() {
        const modal = getCourseModal();
        if (modal) {
            modal.show();
        } else if (typeof $ !== 'undefined' && $(coursePickerModalEl).modal) {
            $(coursePickerModalEl).modal('show');
        }
    }

    function closeCourseModal() {
        const modal = getCourseModal();
        if (modal) {
            modal.hide();
        } else if (typeof $ !== 'undefined' && $(coursePickerModalEl).modal) {
            $(coursePickerModalEl).modal('hide');
        }
    }

    if (coursePickerTrigger) {
        coursePickerTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            openCourseModal();
        });
        coursePickerTrigger.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openCourseModal();
            }
        });
    }

    // Elements inside trigger box to update
    const triggerIconBox = document.getElementById('triggerIconBox');
    const triggerLabel = document.getElementById('triggerLabel');
    const triggerTitle = document.getElementById('triggerTitle');
    const triggerMeta = document.getElementById('triggerMeta');
    const triggerDuration = document.getElementById('triggerDuration');
    const triggerMode = document.getElementById('triggerMode');
    const optionCards = document.querySelectorAll('.course-opt-card');

    optionCards.forEach(function(card) {
        card.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            const duration = this.getAttribute('data-duration');
            const mode = this.getAttribute('data-mode');
            const icon = this.getAttribute('data-icon') || 'fa-solid fa-graduation-cap';

            // Mark option active
            optionCards.forEach(function(c) { c.classList.remove('active'); });
            this.classList.add('active');

            // Sync hidden native select
            if (courseSelect) {
                courseSelect.value = id;
                courseSelect.dispatchEvent(new Event('change'));
            }

            // Update Trigger Box Display
            if (triggerTitle) {
                triggerTitle.textContent = title;
                triggerTitle.classList.remove('placeholder-title');
                triggerTitle.classList.add('selected');
            }
            if (triggerLabel) {
                triggerLabel.innerHTML = '<i class="fa-solid fa-circle-check text-success me-1"></i> Selected Course Program';
            }
            if (triggerIconBox) {
                triggerIconBox.innerHTML = '<i class="' + icon + '"></i>';
            }
            if (triggerDuration) {
                if (duration) {
                    triggerDuration.innerHTML = '<i class="fa-regular fa-clock me-1 text-primary"></i> ' + duration;
                    triggerDuration.style.display = 'inline-block';
                } else {
                    triggerDuration.style.display = 'none';
                }
            }
            if (triggerMode) {
                if (mode) {
                    triggerMode.textContent = mode;
                    triggerMode.style.display = 'inline-block';
                } else {
                    triggerMode.style.display = 'none';
                }
            }
            if (triggerMeta) {
                triggerMeta.style.display = 'flex';
            }
            if (coursePickerTrigger) {
                coursePickerTrigger.classList.add('active-selected');
            }
            if (courseError) {
                courseError.style.display = 'none';
            }

            // Brief tactile delay before closing modal
            setTimeout(function() {
                closeCourseModal();
            }, 180);
        });
    });

    // Instant Search filter
    const searchInput = document.getElementById('courseSearchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const noCoursesFound = document.getElementById('noCoursesFound');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            let visibleCount = 0;

            if (clearSearchBtn) {
                clearSearchBtn.classList.toggle('d-none', query.length === 0);
            }

            optionCards.forEach(function(card) {
                const title = (card.getAttribute('data-title') || '').toLowerCase();
                const duration = (card.getAttribute('data-duration') || '').toLowerCase();
                const mode = (card.getAttribute('data-mode') || '').toLowerCase();

                if (query === '' || title.includes(query) || duration.includes(query) || mode.includes(query)) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (noCoursesFound) {
                noCoursesFound.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        });

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            });
        }
    }

    if (courseSelect) {
        courseSelect.addEventListener('change', function() {
            if (this.value && courseError) {
                courseError.style.display = 'none';
            }
        });
    }

    if (!payBtn) return;

    payBtn.addEventListener('click', function() {
        if (courseSelect && !courseSelect.value) {
            if (courseError) courseError.style.display = 'block';
            openCourseModal();
            return;
        }

        const selOption = courseSelect.options[courseSelect.selectedIndex];
        const selectedCourseId = courseSelect.value;
        const selectedCourseTitle = selOption.getAttribute('data-title') || selOption.text;

        var studentDbId = <?= !empty($student['id']) ? (int)$student['id'] : 0 ?>;
        var studentCode = "<?= !empty($student['student_id']) ? addslashes($student['student_id']) : 'Guest' ?>";
        var payerName = "<?= !empty($student['name']) ? addslashes($student['name']) : '' ?>";
        var payerEmail = "<?= !empty($student['email']) ? addslashes($student['email']) : '' ?>";
        var payerPhone = "<?= !empty($student['phone']) ? addslashes($student['phone']) : '' ?>";

        if (!studentDbId) {
            var gName = document.getElementById('guestName') ? document.getElementById('guestName').value.trim() : '';
            var gPhone = document.getElementById('guestPhone') ? document.getElementById('guestPhone').value.trim() : '';
            var gEmail = document.getElementById('guestEmail') ? document.getElementById('guestEmail').value.trim() : '';
            var gErr = document.getElementById('guestError');

            if (!gName || !gPhone || !gEmail) {
                if (gErr) {
                    gErr.textContent = 'Please fill in your Name, 10-digit Mobile, and Email to reserve your seat.';
                    gErr.style.display = 'block';
                }
                var firstEmpty = !gName ? document.getElementById('guestName') : (!gPhone ? document.getElementById('guestPhone') : document.getElementById('guestEmail'));
                if (firstEmpty) firstEmpty.focus();
                return;
            }
            if (gPhone.replace(/\D/g, '').length < 10) {
                if (gErr) {
                    gErr.textContent = 'Please enter a valid 10-digit mobile number.';
                    gErr.style.display = 'block';
                }
                document.getElementById('guestPhone').focus();
                return;
            }
            if (gErr) gErr.style.display = 'none';

            payerName = gName;
            payerPhone = gPhone;
            payerEmail = gEmail;
        }

        const options = {
            "key": "<?= htmlspecialchars($key_id) ?>",
            "amount": "<?= $amount_paise ?>",
            "currency": "INR",
            "name": "Finchskills Institute",
            "description": "Seat Reservation - " + selectedCourseTitle + " (" + studentCode + ")",
            "image": "img/favicon.png",
            "prefill": {
                "name": payerName,
                "email": payerEmail,
                "contact": payerPhone
            },
            "theme": {
                "color": "#fe7c03"
            },
            "handler": function (response) {
                // Post payment verification to backend
                payBtn.disabled = true;
                payBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Verifying Payment &amp; Confirming Seat...</span>';

                fetch('verify-payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        razorpay_payment_id: response.razorpay_payment_id,
                        student_id: studentDbId,
                        guest_name: payerName,
                        guest_phone: payerPhone,
                        guest_email: payerEmail,
                        amount: "<?= $fee_amount ?>",
                        course_id: selectedCourseId,
                        course_title: selectedCourseTitle
                    })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        var targetUrl = 'student-profile.php?payment=success';
                        if (data.ref) targetUrl += '&ref=' + encodeURIComponent(data.ref);
                        window.location.href = targetUrl;
                    } else {
                        alert('Payment recorded. ' + (data.message || 'Please check your profile.'));
                        window.location.href = 'student-profile.php';
                    }
                })
                .catch(function(err) {
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
    });
});
</script>

<?php include "footer.php"; ?>

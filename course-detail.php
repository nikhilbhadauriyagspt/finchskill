<?php
require_once __DIR__ . '/db.php';

$slug = trim($_GET['slug'] ?? '');
$id = (int)($_GET['id'] ?? 0);

// Lookup course
$course = null;
if (!empty($slug) || $id > 0) {
    $stmt = $pdo->prepare("SELECT cr.*, cat.name as category_name, cat.slug as category_slug, cat.icon as category_icon 
                           FROM courses cr 
                           JOIN course_categories cat ON cr.category_id = cat.id 
                           WHERE (cr.slug = ? OR cr.id = ?) AND cr.status = 'active' 
                           LIMIT 1");
    $stmt->execute([$slug, $id]);
    $course = $stmt->fetch();
}

// Fetch Seat Booking Fee from site_settings (set by Admin)
$seat_booking_fee = (float)getSetting('seat_booking_fee', '999');

// Quick Inquiry Form Submission
$inquiry_success = false;
$inquiry_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_course_inquiry'])) {
    $c_name = trim($_POST['inquiry_name'] ?? '');
    $c_phone = trim($_POST['inquiry_phone'] ?? '');

    if (empty($c_name) || empty($c_phone)) {
        $inquiry_error = 'Please enter your name and phone number.';
    } else {
        $subject = 'Quick Callback: ' . ($course['title'] ?? 'Course Inquiry');
        $msg_body = "Course: " . ($course['title'] ?? 'General') . "\n";
        $msg_body .= "Student Name: " . $c_name . "\n";
        $msg_body .= "Phone: " . $c_phone . "\n";
        $msg_body .= "Source: Course Detail Page Quick Callback Box";

        try {
            $stmt = $pdo->prepare("INSERT INTO contact_inquiries (name, email, phone, subject, message, status, is_read) VALUES (?, '', ?, ?, ?, 'New', 0)");
            $stmt->execute([$c_name, $c_phone, $subject, $msg_body]);
            $inquiry_success = true;
        } catch (Exception $e) {
            $inquiry_error = 'Unable to submit right now. Please call our helpline directly.';
        }
    }
}

// Fetch related courses from same category
$related_courses = [];
if ($course) {
    $rel_stmt = $pdo->prepare("SELECT cr.*, cat.name as category_name, cat.icon as category_icon 
                               FROM courses cr 
                               JOIN course_categories cat ON cr.category_id = cat.id 
                               WHERE cr.category_id = ? AND cr.id != ? AND cr.status = 'active' 
                               LIMIT 3");
    $rel_stmt->execute([$course['category_id'], $course['id']]);
    $related_courses = $rel_stmt->fetchAll();

    // If fewer than 3 courses in same category, supplement with top other active courses
    if (count($related_courses) < 3) {
        $needed = 3 - count($related_courses);
        $exclude_ids = array_merge([$course['id']], !empty($related_courses) ? array_column($related_courses, 'id') : []);
        $placeholders = implode(',', array_fill(0, count($exclude_ids), '?'));
        $extra_stmt = $pdo->prepare("SELECT cr.*, cat.name as category_name, cat.icon as category_icon 
                                     FROM courses cr 
                                     JOIN course_categories cat ON cr.category_id = cat.id 
                                     WHERE cr.id NOT IN ($placeholders) AND cr.status = 'active' 
                                     ORDER BY cr.is_featured DESC, cr.id ASC 
                                     LIMIT $needed");
        $extra_stmt->execute($exclude_ids);
        $related_courses = array_merge($related_courses, $extra_stmt->fetchAll());
    }
}

$page_title = $course ? htmlspecialchars($course['title']) . " - Finchskills Institute" : "Course Not Found - Finchskills Institute";
include "header.php";
?>

<?php if (!$course): ?>
    <!-- 404 NOT FOUND STATE -->
    <section class="py-5 text-center" style="min-height: 55vh; display: flex; align-items: center; background: #f8fafc;">
        <div class="container py-5">
            <div class="card border p-5 max-w-600 mx-auto rounded-4 bg-white shadow-sm" style="max-width: 520px; border-color: #e2e8f0;">
                <i class="fa-solid fa-graduation-cap text-secondary opacity-25 fa-4x mb-3"></i>
                <h3 class="fw-bold text-dark mb-2">Course Not Found</h3>
                <p class="text-secondary small mb-4">The course you are looking for may have been updated or is currently inactive.</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="courses.php" class="cd-btn-primary" style="width: auto; padding: 10px 24px;">Explore All Courses</a>
                    <a href="index.php" class="btn btn-outline-secondary px-4 py-2" style="font-size: 13.5px; font-weight: 600;">Home</a>
                </div>
            </div>
        </div>
    </section>
<?php else: ?>

    <!-- ================= BREADCRUMBS ================= -->
    <div class="cd-breadcrumb-bar">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php"><i class="fa-solid fa-house me-1 small"></i>Home</a></li>
                    <li class="breadcrumb-item"><a href="courses.php">Courses</a></li>
                    <li class="breadcrumb-item"><a href="courses.php?category=<?= $course['category_id'] ?>"><?= htmlspecialchars($course['category_name']) ?></a></li>
                    <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?= htmlspecialchars($course['title']) ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- ================= HERO HEADER ================= -->
    <section class="cd-hero-section">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-12">
                    <!-- TAGS & STATUS -->
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="cd-cat-pill">
                            <i class="fa-solid <?= htmlspecialchars($course['category_icon']) ?>"></i>
                            <?= htmlspecialchars($course['category_name']) ?>
                        </span>
                        <span class="cd-status-pill">
                            <span class="cd-pulse-dot"></span>
                            Admissions Open &bull; Limited Batch Seats
                        </span>
                        <span class="cd-rating-pill">
                            <i class="fa-solid fa-star text-warning me-1"></i>
                            <span>4.9 (500+ Placed Students)</span>
                        </span>
                    </div>

                    <!-- COURSE TITLE -->
                    <h1 class="cd-course-title">
                        <?= htmlspecialchars($course['title']) ?>
                    </h1>

                    <!-- SHORT INTRO -->
                    <p class="cd-course-intro">
                        <?= htmlspecialchars($course['short_desc'] ?: 'Comprehensive, industry-aligned professional training program designed to prepare you for high-growth careers with hands-on practical skills and placement support.') ?>
                    </p>

                    <!-- HERO ACTION BUTTONS -->
                    <?php
                    $hero_seat_url = !empty($isStudentLoggedIn)
                        ? ("seat-reservation?course_id=" . $course['id'])
                        : ("register?redirect=" . urlencode("seat-reservation?course_id=" . $course['id']));
                    $hero_apply_url = !empty($isStudentLoggedIn)
                        ? ("form-submission?course=" . urlencode($course['title']))
                        : ("register?redirect=" . urlencode("form-submission?course=" . urlencode($course['title'])));
                    ?>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                        <a href="<?= $hero_seat_url ?>" class="btn px-4 py-2 fw-bold text-white shadow-sm d-inline-flex align-items-center gap-2" style="background: #fe7c03; border-radius: 8px; font-size: 14px;">
                            <i class="fa-solid fa-chair"></i>
                            <span>Book Your Seat</span>
                            <span class="badge bg-white text-dark ms-1" style="font-size: 10.5px;">100% Adjusted</span>
                        </a>
                        <a href="<?= $hero_apply_url ?>" class="btn px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 text-dark border-secondary-subtle bg-white" style="border-radius: 8px; font-size: 14px;">
                            <i class="fa-solid fa-file-signature text-primary"></i>
                            <span>Complete Admission Form</span>
                        </a>
                    </div>

                    <!-- METRIC HORIZONTAL QUICK SPECS (5 CLEAN CARDS) -->
                    <div class="cd-metric-strip">
                        <div class="cd-metric-item">
                            <div class="cd-metric-icon"><i class="fa-regular fa-clock"></i></div>
                            <div>
                                <span class="cd-metric-label">Duration</span>
                                <div class="cd-metric-val"><?= htmlspecialchars($course['duration'] ?: '12 Months') ?></div>
                            </div>
                        </div>

                        <?php if (!empty($course['fee'])): ?>
                            <div class="cd-metric-item cd-metric-fee">
                                <div class="cd-metric-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                                <div>
                                    <span class="cd-metric-label">Easy</span>
                                    <div class="cd-metric-val ">Installments</div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="cd-metric-item">
                            <div class="cd-metric-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                            <div>
                                <span class="cd-metric-label">Eligibility</span>
                                <div class="cd-metric-val"><?= htmlspecialchars($course['eligibility'] ?: '10+2 / Any Graduate') ?></div>
                            </div>
                        </div>

                        <div class="cd-metric-item">
                            <div class="cd-metric-icon"><i class="fa-solid fa-laptop-file"></i></div>
                            <div>
                                <span class="cd-metric-label">Training Mode</span>
                                <div class="cd-metric-val"><?= htmlspecialchars($course['study_mode'] ?: 'Classroom & Labs') ?></div>
                            </div>
                        </div>

                        <?php if (!empty($course['modules'])): ?>
                            <div class="cd-metric-item">
                                <div class="cd-metric-icon"><i class="fa-solid fa-book-open"></i></div>
                                <div>
                                    <span class="cd-metric-label">Curriculum</span>
                                    <div class="cd-metric-val"><?= htmlspecialchars($course['modules']) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="cd-metric-item">
                            <div class="cd-metric-icon"><i class="fa-solid fa-briefcase"></i></div>
                            <div>
                                <span class="cd-metric-label">Placement</span>
                                <div class="cd-metric-val text-primary">100% Support</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- ================= SUB-NAVIGATION BAR ================= -->
    <div class="cd-sticky-nav">
        <div class="container">
            <div class="cd-nav-links">
                <a href="#overview" class="cd-nav-link active">About Program</a>
                <?php if (!empty($course['what_you_learn'])): ?>
                    <a href="#curriculum" class="cd-nav-link">Curriculum</a>
                <?php endif; ?>
                <?php if (!empty($course['how_we_teach'])): ?>
                    <a href="#methodology" class="cd-nav-link">Practical Training</a>
                <?php endif; ?>
                <?php if (!empty($course['career_roles'])): ?>
                    <a href="#careers" class="cd-nav-link">Job Roles</a>
                <?php endif; ?>
                <a href="#eligibility" class="cd-nav-link">Eligibility &amp; Certificate</a>
                <a href="#faq" class="cd-nav-link">FAQs</a>
            </div>
        </div>
    </div>

    <!-- ================= MAIN CONTENT & SIDEBAR ================= -->
    <section class="cd-main-wrap">
        <div class="container">
            <div class="row g-4 g-xl-5">

                <!-- LEFT COLUMN: CONTENT CARDS -->
                <div class="col-lg-8">

                    <!-- 1. COURSE OVERVIEW -->
                    <div class="cd-sec-card" id="overview">
                        <span class="cd-sec-kicker"><i class="fa-solid fa-circle-info"></i> Course Overview</span>
                        <h2 class="cd-sec-title">About This Professional Program</h2>
                        <div class="cd-body-text">
                            <?php
                            $desc_content = $course['full_desc'] ?: $course['short_desc'];
                            if (strip_tags($desc_content) !== $desc_content) {
                                echo strip_tags($desc_content, '<p><br><strong><b><em><i><u><ul><ol><li><h3><h4><h5><h6><a><blockquote><span><div><table><thead><tbody><tr><th><td>');
                            } else {
                                echo nl2br(htmlspecialchars($desc_content));
                            }
                            ?>
                        </div>

                        <!-- 4 KEY HIGHLIGHT PILLARS -->
                        <div class="row g-3 mt-4">
                            <div class="col-sm-6">
                                <div class="cd-highlight-box">
                                    <div class="cd-hl-icon cd-icon-blue"><i class="fa-solid fa-flask-vial"></i></div>
                                    <h5>Simulation &amp; Roleplay Labs</h5>
                                    <p>Practice live terminal procedures, in-flight grooming standards, passenger check-in handling, and safety drills in realistic lab setups.</p>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="cd-highlight-box">
                                    <div class="cd-hl-icon cd-icon-emerald"><i class="fa-solid fa-book-open-reader"></i></div>
                                    <h5>Industry-Vetted Syllabus</h5>
                                    <p>Step-by-step curriculum aligned with current airline, airport, and luxury hospitality hiring requirements.</p>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="cd-highlight-box">
                                    <div class="cd-hl-icon cd-icon-amber"><i class="fa-solid fa-user-tie"></i></div>
                                    <h5>Executive Grooming &amp; Etiquette</h5>
                                    <p>Master communication skills, corporate body language, professional makeup, and interview confidence.</p>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="cd-highlight-box">
                                    <div class="cd-hl-icon cd-icon-indigo"><i class="fa-solid fa-handshake"></i></div>
                                    <h5>Dedicated Placement Cell</h5>
                                    <p>Resume review, 1-on-1 mock HR interviews, campus recruitment drives, and verified career assistance.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. CURRICULUM MODULES -->
                    <?php if (!empty($course['what_you_learn'])): ?>
                        <?php
                        $modules = array_filter(array_map('trim', explode("\n", $course['what_you_learn'])));
                        ?>
                        <div class="cd-sec-card" id="curriculum">
                            <span class="cd-sec-kicker"><i class="fa-solid fa-list-check"></i> What You Learn</span>
                            <h2 class="cd-sec-title">Course Curriculum &amp; Training Modules</h2>
                            <p class="cd-body-text mb-3">
                                Structured, practical modules designed to take you from foundational concepts to advanced operational excellence:
                            </p>

                            <div class="accordion cd-module-accordion" id="curriculumAccordion">
                                <?php foreach ($modules as $index => $mod):
                                    $mod_id = "mod_" . ($index + 1);
                                    $is_first = ($index === 0);
                                ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading_<?= $mod_id ?>">
                                            <button class="accordion-button <?= $is_first ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?= $mod_id ?>" aria-expanded="<?= $is_first ? 'true' : 'false' ?>" aria-controls="collapse_<?= $mod_id ?>">
                                                <span class="cd-mod-pill">Module <?= sprintf('%02d', $index + 1) ?></span>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($mod) ?></span>
                                            </button>
                                        </h2>
                                        <div id="collapse_<?= $mod_id ?>" class="accordion-collapse collapse <?= $is_first ? 'show' : '' ?>" aria-labelledby="heading_<?= $mod_id ?>" data-bs-parent="#curriculumAccordion">
                                            <div class="accordion-body">
                                                <div class="d-flex align-items-start gap-2 mb-2">
                                                    <i class="fa-solid fa-circle-check text-success mt-1"></i>
                                                    <span>Core concepts, practical demonstrations, standard operating guidelines, and interactive sessions.</span>
                                                </div>
                                                <div class="d-flex align-items-start gap-2">
                                                    <i class="fa-solid fa-circle-check text-success mt-1"></i>
                                                    <span>Practical roleplay assessments and real-world workplace scenario evaluations.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 3. TRAINING METHODOLOGY -->
                    <?php if (!empty($course['how_we_teach'])): ?>
                        <?php
                        $teach_methods = array_filter(array_map('trim', explode("\n", $course['how_we_teach'])));
                        ?>
                        <div class="cd-sec-card" id="methodology">
                            <span class="cd-sec-kicker"><i class="fa-solid fa-chalkboard-user"></i> Practical Approach</span>
                            <h2 class="cd-sec-title">How We Train &amp; Prepare You</h2>
                            <p class="cd-body-text mb-4">
                                Our training methodology emphasizes 70% practical experiential learning so you step into interviews with complete confidence:
                            </p>

                            <div class="row g-3">
                                <?php foreach ($teach_methods as $idx => $method): ?>
                                    <div class="col-sm-6">
                                        <div class="cd-step-card">
                                            <div class="cd-step-num"><?= sprintf('%02d', $idx + 1) ?></div>
                                            <div>
                                                <h5>Hands-On Focus</h5>
                                                <p><?= htmlspecialchars($method) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 4. CAREER ROLES & JOB OPPORTUNITIES -->
                    <?php if (!empty($course['career_roles'])): ?>
                        <?php
                        $roles = array_filter(array_map('trim', explode(',', $course['career_roles'])));
                        ?>
                        <div class="cd-sec-card" id="careers">
                            <span class="cd-sec-kicker"><i class="fa-solid fa-briefcase"></i> Placement Scope</span>
                            <h2 class="cd-sec-title">Target Job Profiles &amp; Career Opportunities</h2>
                            <p class="cd-body-text mb-3">
                                Graduates of this program become qualified for sought-after job positions across top airlines, airports, hotels, and travel companies:
                            </p>

                            <div class="d-flex flex-wrap gap-2 mb-4">
                                <?php foreach ($roles as $role): ?>
                                    <span class="cd-career-tag">
                                        <i class="fa-solid fa-check text-warning"></i>
                                        <?= htmlspecialchars($role) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>

                            <div class="p-3 bg-light rounded-3 border">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fa-solid fa-handshake-angle text-primary fs-6"></i>
                                    <strong class="text-dark small">Active Recruitment Drives &amp; Placement Guarantee</strong>
                                </div>
                                <p class="small text-muted mb-0">
                                    Our career cell organizes regular interview drives with leading aviation networks, international airlines, premium hotel properties, and global tourism agencies.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 5. ELIGIBILITY & CERTIFICATION -->
                    <div class="cd-sec-card" id="eligibility">
                        <span class="cd-sec-kicker"><i class="fa-solid fa-certificate"></i> Qualifications</span>
                        <h2 class="cd-sec-title">Eligibility Criteria &amp; Certification</h2>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="cd-info-card">
                                    <h6><i class="fa-solid fa-user-check text-primary"></i> Eligibility Requirements</h6>
                                    <p class="fw-semibold text-dark mb-2"><?= htmlspecialchars($course['eligibility'] ?: '10+2 / Any Graduate') ?></p>
                                    <ul class="list-unstyled small text-muted mt-2 mb-0 d-flex flex-column gap-1">
                                        <li><i class="fa-solid fa-check text-success me-1.5"></i> 10th / 12th pass or graduation from any recognized board/university</li>
                                        <li><i class="fa-solid fa-check text-success me-1.5"></i> Basic verbal communication skills &amp; professional grooming willingness</li>
                                        <li><i class="fa-solid fa-check text-success me-1.5"></i> Minimum age 17+ years</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="cd-info-card">
                                    <h6><i class="fa-solid fa-award text-warning"></i> Recognized Certification</h6>
                                    <p class="fw-semibold text-dark mb-2"><?= htmlspecialchars($course['certification'] ?: 'Industry Recognized Certification') ?></p>
                                    <ul class="list-unstyled small text-muted mt-2 mb-0 d-flex flex-column gap-1">
                                        <li><i class="fa-solid fa-check text-success me-1.5"></i> Industry-recognized verifiable certification</li>
                                        <li><i class="fa-solid fa-check text-success me-1.5"></i> Validated credentials for domestic &amp; global recruiters</li>
                                        <li><i class="fa-solid fa-check text-success me-1.5"></i> Includes lifetime placement assistance registration</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. FREQUENTLY ASKED QUESTIONS (FAQS) -->
                    <div class="cd-sec-card" id="faq">
                        <span class="cd-sec-kicker"><i class="fa-solid fa-circle-question"></i> Help &amp; FAQs</span>
                        <h2 class="cd-sec-title">Frequently Asked Questions</h2>

                        <div class="accordion cd-faq-accordion" id="courseFaqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faqH1">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqC1" aria-expanded="false" aria-controls="faqC1">
                                        What is the admission and enrollment process?
                                    </button>
                                </h2>
                                <div id="faqC1" class="accordion-collapse collapse" aria-labelledby="faqH1" data-bs-parent="#courseFaqAccordion">
                                    <div class="accordion-body">
                                        You can start by booking your seat or submitting the online admission form. Our admissions team will verify your eligibility, help you complete documentation, and assist you with batch onboarding.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faqH2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqC2" aria-expanded="false" aria-controls="faqC2">
                                        Who is eligible to enroll in this course?
                                    </button>
                                </h2>
                                <div id="faqC2" class="accordion-collapse collapse" aria-labelledby="faqH2" data-bs-parent="#courseFaqAccordion">
                                    <div class="accordion-body">
                                        Students who have completed 10th, 12th, or graduation in any stream with basic communication skills are eligible. No prior industry experience is required.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faqH3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqC3" aria-expanded="false" aria-controls="faqC3">
                                        Will I receive job placement support after completing the course?
                                    </button>
                                </h2>
                                <div id="faqC3" class="accordion-collapse collapse" aria-labelledby="faqH3" data-bs-parent="#courseFaqAccordion">
                                    <div class="accordion-body">
                                        Yes! Finchskills Institute provides 100% placement support including resume structuring, grooming classes, mock HR interview drills, and direct interview scheduling with airlines, airports, and hotels.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faqH4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqC4" aria-expanded="false" aria-controls="faqC4">
                                        What are the available batch timings and study modes?
                                    </button>
                                </h2>
                                <div id="faqC4" class="accordion-collapse collapse" aria-labelledby="faqH4" data-bs-parent="#courseFaqAccordion">
                                    <div class="accordion-body">
                                        We offer flexible weekday and weekend batches in Classroom / Lab Mode, Live Interactive Online Mode, and Hybrid combinations to suit your daily schedule.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: MINIMALIST STICKY SIDEBAR -->
                <div class="col-lg-4">
                    <div class="cd-sticky-sidebar" id="enrollSidebar">

                        <div class="cd-sidebar-card">
                            <!-- COURSE THUMBNAIL -->
                            <div class="cd-sidebar-thumb-wrap">
                                <?php if (!empty($course['image'])): ?>
                                    <img src="<?= htmlspecialchars($course['image']) ?>" alt="<?= htmlspecialchars($course['title']) ?>">
                                <?php else: ?>
                                    <div class="w-100 h-100 bg-dark d-flex align-items-center justify-content-center text-white">
                                        <i class="fa-solid <?= htmlspecialchars($course['category_icon']) ?> fa-3x text-warning"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="cd-thumb-badge">
                                    <i class="fa-solid <?= htmlspecialchars($course['category_icon']) ?>"></i>
                                    <?= htmlspecialchars($course['category_name']) ?>
                                </span>
                            </div>

                            <!-- SIDEBAR BODY -->
                            <div class="cd-sidebar-body">
                                <div class="cd-intake-badge">
                                    <span class="cd-pulse-dot"></span>
                                    <span>Admissions Open &bull; Limited Seats</span>
                                </div>

                                <h4 class="cd-sidebar-title"><?= htmlspecialchars($course['title']) ?></h4>
                                <p class="cd-sidebar-sub">Professional certified training designed for direct placement.</p>

                                <!-- PROMINENT FEE BOX -->
                                <div class="cd-fee-box">
                                    <div class="d-flex align-items-baseline justify-content-between mb-1">
                                        <span class="cd-fee-label">Flexible Payment Plans</span>
                                    </div>
                                    <div class="cd-fee-sub">
                                        <i class="fa-solid fa-circle-check text-success me-1"></i>
                                        <span>No hidden charges &bull; Easy monthly installments available</span>
                                    </div>
                                </div>

                                <!-- ACTION CTAS -->
                                <div class="d-grid gap-2 mb-2">
                                    <!-- 1. BOOK YOUR SEAT (100% ADJUSTABLE) -->
                                    <a href="<?= $hero_seat_url ?>" class="cd-btn-primary" style="background: linear-gradient(135deg, #fe7c03 0%, #d96800 100%); border: none;">
                                        <i class="fa-solid fa-chair me-1"></i>
                                        <span>Book Your Seat</span>
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                    <div class="text-center" style="font-size: 11.5px; color: #15803d; font-weight: 600; margin-top: -2px;">
                                        <i class="fa-solid fa-circle-check me-1"></i> Seat booking amount is 100% adjusted into course fee
                                    </div>

                                    <!-- 2. DIRECT ADMISSION FORM -->
                                    <a href="<?= $hero_apply_url ?>" class="btn btn-outline-dark fw-semibold py-2 d-flex align-items-center justify-content-center gap-2" style="border-radius: 8px; font-size: 13.5px;">
                                        <i class="fa-solid fa-file-signature text-primary"></i>
                                        <span>Complete Admission Form</span>
                                    </a>
                                </div>

                                <!-- SECONDARY ACTION: WHATSAPP COUNSELOR -->
                                <?php
                                $cd_wa = getSetting('whatsapp_number', getSetting('phone_1', '919650386711'));
                                $cd_wa_clean = preg_replace('/[^0-9]/', '', $cd_wa);
                                ?>
                                <a href="https://wa.me/<?= $cd_wa_clean ?>?text=<?= urlencode("Hello Finchskills, I would like to inquire about the " . $course['title'] . " course fee and batch schedule.") ?>" target="_blank" rel="noopener" class="cd-btn-whatsapp">
                                    <i class="fa-brands fa-whatsapp fs-5"></i>
                                    <span>Chat on WhatsApp</span>
                                </a>

                                <!-- COURSE FACTS LIST -->
                                <ul class="cd-facts-list">
                                    <li class="cd-fact-row">
                                        <span class="cd-fact-label"><i class="fa-regular fa-clock"></i> Duration</span>
                                        <span class="cd-fact-val"><?= htmlspecialchars($course['duration'] ?: '12 Months') ?></span>
                                    </li>
                                    <?php if (!empty($course['fee'])): ?>
                                        <li class="cd-fact-row">
                                            <span class="cd-fact-label"><i class="fa-solid fa-indian-rupee-sign"></i> Easy</span>
                                            <span class="cd-fact-val text-success">Installments</span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="cd-fact-row">
                                        <span class="cd-fact-label"><i class="fa-solid fa-graduation-cap"></i> Eligibility</span>
                                        <span class="cd-fact-val"><?= htmlspecialchars($course['eligibility'] ?: '10+2 Pass') ?></span>
                                    </li>
                                    <li class="cd-fact-row">
                                        <span class="cd-fact-label"><i class="fa-solid fa-laptop-file"></i> Study Mode</span>
                                        <span class="cd-fact-val"><?= htmlspecialchars($course['study_mode'] ?: 'Offline') ?></span>
                                    </li>
                                    <?php if (!empty($course['modules'])): ?>
                                        <li class="cd-fact-row">
                                            <span class="cd-fact-label"><i class="fa-solid fa-book-open"></i> Course Modules</span>
                                            <span class="cd-fact-val fw-semibold text-dark"><?= htmlspecialchars($course['modules']) ?></span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="cd-fact-row">
                                        <span class="cd-fact-label"><i class="fa-solid fa-certificate"></i> Certification</span>
                                        <span class="cd-fact-val">Recognized</span>
                                    </li>
                                    <li class="cd-fact-row">
                                        <span class="cd-fact-label"><i class="fa-solid fa-briefcase"></i> Placement</span>
                                        <span class="cd-fact-val text-success">100% Support</span>
                                    </li>
                                </ul>

                                <!-- QUICK CALLBACK BOX -->
                                <div class="cd-callback-box">
                                    <h6><i class="fa-solid fa-phone-volume text-primary"></i> Request Quick Callback</h6>
                                    <p>Get instant guidance on batch dates, eligibility, and fee discount plans.</p>

                                    <?php if ($inquiry_success): ?>
                                        <div class="alert alert-success py-2 px-2 small mb-0 rounded-2" role="alert">
                                            <i class="fa-solid fa-circle-check me-1"></i> Request received! Our counselor will call you shortly.
                                        </div>
                                    <?php else: ?>
                                        <?php if (!empty($inquiry_error)): ?>
                                            <div class="alert alert-danger py-1.5 px-2 small mb-2 rounded-2" role="alert">
                                                <?= htmlspecialchars($inquiry_error) ?>
                                            </div>
                                        <?php endif; ?>

                                        <form method="POST" action="course-detail.php?slug=<?= urlencode($course['slug']) ?>#enrollSidebar">
                                            <input type="hidden" name="submit_course_inquiry" value="1">
                                            <input type="text" name="inquiry_name" class="cd-callback-input" placeholder="Your Full Name" required>
                                            <input type="tel" name="inquiry_phone" class="cd-callback-input" placeholder="Mobile / WhatsApp Number" required>
                                            <button type="submit" class="cd-callback-btn">
                                                <span>Request Free Call</span>
                                                <i class="fa-solid fa-arrow-right ms-1"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <?php $cd_phone = getSetting('phone_1', '+919650386711'); ?>
                                <div class="cd-helpline-note">
                                    Direct Admissions Helpline: <a href="tel:<?= htmlspecialchars($cd_phone) ?>"><?= htmlspecialchars($cd_phone) ?></a>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ================= RELATED COURSES ================= -->
    <?php if (!empty($related_courses)): ?>
        <section class="py-5 bg-white border-top">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <div class="course-section-kicker">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span>EXPLORE SIMILAR</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">More Programs in <?= htmlspecialchars($course['category_name']) ?></h3>
                    </div>
                    <a href="courses.php?category=<?= $course['category_id'] ?>" class="view-btn">View All</a>
                </div>

                <div class="row g-4">
                    <?php foreach ($related_courses as $rc): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="course-card">
                                <div class="course-image">
                                    <?php if (!empty($rc['image'])): ?>
                                        <img src="<?= htmlspecialchars($rc['image']) ?>" alt="<?= htmlspecialchars($rc['title']) ?>">
                                    <?php else: ?>
                                        <div class="w-100 h-100 bg-dark d-flex align-items-center justify-content-center text-white">
                                            <i class="fa-solid <?= htmlspecialchars($rc['category_icon']) ?> fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="course-cat-badge">
                                        <i class="fa-solid <?= htmlspecialchars($rc['category_icon']) ?>"></i> <?= htmlspecialchars($rc['category_name']) ?>
                                    </span>
                                    <span class="course-duration-badge">
                                        <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($rc['duration']) ?>
                                    </span>
                                </div>
                                <div class="course-content">
                                    <h3><?= htmlspecialchars($rc['title']) ?></h3>
                                    <p><?= htmlspecialchars($rc['short_desc']) ?></p>

                                    <?php if (!empty($rc['fee'])): ?>
                                        <div class="course-meta mb-3">
                                            <span class="cm-item"><i class="fa-solid fa-indian-rupee-sign"></i> Easy <strong>Installments</strong></span>
                                            <span class="cm-item"><i class="fa-solid fa-briefcase"></i> 100% Placement</span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="course-card-footer">
                                        <?= renderEnrollBtn($rc['title']) ?>
                                        <a href="course-detail.php?slug=<?= urlencode($rc['slug']) ?>" class="course-details-link">
                                            <span>View Details</span>
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

<?php endif; ?>

<!-- Dynamic ScrollSpy script for sticky sub-nav -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.cd-nav-link');
        const sections = [];

        navLinks.forEach(link => {
            const targetId = link.getAttribute('href');
            if (targetId && targetId.startsWith('#')) {
                const el = document.querySelector(targetId);
                if (el) {
                    sections.push({
                        id: targetId,
                        element: el,
                        link: link
                    });
                }
            }
        });

        function updateActiveTab() {
            // Adjust for sticky header + sub-nav height (~150px)
            const scrollPosition = window.scrollY + 160;

            let activeSection = null;

            for (let i = 0; i < sections.length; i++) {
                const sec = sections[i];
                const top = sec.element.offsetTop;
                const height = sec.element.offsetHeight;

                if (scrollPosition >= top && scrollPosition < top + height) {
                    activeSection = sec;
                    break;
                } else if (scrollPosition >= top) {
                    activeSection = sec;
                }
            }

            if (activeSection) {
                navLinks.forEach(l => l.classList.remove('active'));
                activeSection.link.classList.add('active');

                // Scroll the horizontal nav strip smoothly if active tab goes out of view (on mobile)
                const navContainer = document.querySelector('.cd-nav-links');
                if (navContainer) {
                    const linkLeft = activeSection.link.offsetLeft;
                    const linkWidth = activeSection.link.offsetWidth;
                    const containerWidth = navContainer.offsetWidth;
                    if (linkLeft < navContainer.scrollLeft || (linkLeft + linkWidth) > (navContainer.scrollLeft + containerWidth)) {
                        navContainer.scrollTo({
                            left: linkLeft - (containerWidth / 2) + (linkWidth / 2),
                            behavior: 'smooth'
                        });
                    }
                }
            }
        }

        window.addEventListener('scroll', updateActiveTab, {
            passive: true
        });
        updateActiveTab(); // Initial check

        // Smooth click handler
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId && targetId.startsWith('#')) {
                    const targetEl = document.querySelector(targetId);
                    if (targetEl) {
                        e.preventDefault();
                        const targetTop = targetEl.offsetTop - 140;
                        window.scrollTo({
                            top: targetTop,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    });
</script>

<?php include "footer.php"; ?>
<?php 
$page_title = "Contact Us - Finchskills Institute | Aviation, Hospitality, Travel & Cruise Training";
include "header.php";

$page_address = getSetting('address', 'Orbit Plaza, Crossing Republik, NH-24, Ghaziabad, Uttar Pradesh, 201016');
$page_phone_1 = getSetting('phone_1', '+919650386711');
$page_phone_2 = getSetting('phone_2', '');
$page_email_1 = getSetting('email_1', 'hello@finchskills.com');
$page_email_2 = getSetting('email_2', 'admission@finchskills.com');
$timing_mf    = getSetting('timing_mon_fri', 'Monday - Friday: 10:00 - 05:00');
$timing_sat   = getSetting('timing_sat', 'Saturday: 10:00 - 02:00');
$map_url      = getSetting('map_iframe', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3501.9501409758395!2d77.43262847457278!3d28.63125638414035!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390cee300c363997%3A0xe53a3aaacf648c83!2sOrbit%20plaza%2C%20Crossings%20Republik%2C%20Ghaziabad%2C%20Uttar%20Pradesh%20201016!5e0!3m2!1sen!2sin!4v1784362846035!5m2!1sen!2sin');
?>

<style>
/* ================= CONTACT PAGE STYLES (HOMEPAGE MATCHING) ================= */
.contact-hero-section {
    background: url("img/contact-hero.png") no-repeat right center;
    background-size: cover;
    min-height: 480px;
    padding: 75px 0 80px;
    position: relative;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
}
@media (min-width: 992px) {
    .contact-hero-section::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to right, rgba(255, 251, 248, 0.94) 0%, rgba(255, 251, 248, 0.82) 42%, rgba(255, 251, 248, 0) 70%);
        pointer-events: none;
        z-index: 1;
    }
    .contact-hero-section .container {
        position: relative;
        z-index: 2;
    }
}
@media (max-width: 991px) {
    .contact-hero-section {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.88) 100%), url("img/contact-hero.png") no-repeat center right;
        background-size: cover;
        padding: 50px 0;
        min-height: auto;
    }
}
.contact-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #fff8f3;
    border: 1px solid #ffd8bf;
    padding: 6px 18px 6px 8px;
    border-radius: 50px;
    margin-bottom: 20px;
}
.contact-hero-badge-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #fe7c03 0%, #ff5200 100%);
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}
.contact-hero-badge-text {
    font-size: 12.5px;
    font-weight: 700;
    color: #9a3412;
    letter-spacing: 0.3px;
}
.contact-hero-title {
    font-size: 46px;
    line-height: 1.2;
    font-weight: 900;
    color: #0e1e2e;
    margin-bottom: 18px;
    letter-spacing: -0.5px;
}
.contact-hero-title .highlight {
    color: #fe7c03;
}
.contact-hero-text {
    font-size: 15.5px;
    line-height: 1.75;
    color: #475569;
    margin-bottom: 24px;
    max-width: 600px;
}
.contact-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    list-style: none;
    padding: 0;
    margin: 0;
    font-size: 13px;
    font-weight: 600;
}
.contact-breadcrumb li a {
    color: #64748b;
    text-decoration: none;
    transition: color 0.2s;
}
.contact-breadcrumb li a:hover {
    color: #fe7c03;
}
.contact-breadcrumb .separator {
    color: #cbd5e1;
    font-size: 10px;
}
.contact-breadcrumb .active {
    color: #fe7c03;
}


/* CONTACT INFO TILES */
.contact-tiles-section {
    padding: 60px 0 30px;
    background: #ffffff;
}
.contact-tile-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 28px 24px;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}
.contact-tile-card:hover {
    transform: translateY(-4px);
    border-color: #ffd8bf;
    box-shadow: 0 10px 25px rgba(254, 124, 3, 0.08);
}
.contact-tile-icon-wrap {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 18px;
    flex-shrink: 0;
}
.tile-icon-orange {
    background: rgba(254, 124, 3, 0.1);
    color: #fe7c03;
}
.tile-icon-green {
    background: rgba(22, 163, 74, 0.1);
    color: #16a34a;
}
.tile-icon-blue {
    background: rgba(2, 132, 199, 0.1);
    color: #0284c7;
}
.tile-icon-amber {
    background: rgba(217, 119, 6, 0.1);
    color: #d97706;
}
.contact-tile-card .tile-subtitle {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #94a3b8;
    margin-bottom: 4px;
}
.contact-tile-card h3 {
    font-size: 18px;
    font-weight: 800;
    color: #0e1e2e;
    margin-bottom: 10px;
}
.contact-tile-card p {
    font-size: 14px;
    color: #475569;
    line-height: 1.65;
    margin-bottom: 0;
}
.contact-tile-card a {
    color: #334155;
    text-decoration: none;
    transition: color 0.2s;
}
.contact-tile-card a:hover {
    color: #fe7c03;
}
.contact-tile-action {
    margin-top: auto;
    padding-top: 16px;
    border-top: 1px dashed #f1f5f9;
}
.contact-tile-action a {
    font-size: 12.5px;
    font-weight: 700;
    color: #fe7c03;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.contact-tile-action a:hover {
    color: #c25e00;
}

/* CONTACT FORM & INFO SECTION */
.contact-form-section {
    padding: 40px 0 70px;
    background: #f8fafc;
}
.contact-form-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 38px 34px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
}
.contact-form-badge {
    display: inline-flex;
    align-items: center;
    background: #fff8f3;
    color: #fe7c03;
    border: 1px solid #ffedd5;
    padding: 4px 14px;
    border-radius: 30px;
    font-size: 11.5px;
    font-weight: 700;
    margin-bottom: 12px;
}
.contact-form-card h2 {
    font-size: 26px;
    font-weight: 900;
    color: #0e1e2e;
    margin-bottom: 8px;
}
.contact-form-card .form-desc {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 26px;
    line-height: 1.6;
}
.contact-label {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
    display: block;
}
.contact-input, .contact-textarea {
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 11px 14px;
    font-size: 14px;
    color: #0f172a;
    transition: all 0.2s ease;
    background: #ffffff;
    width: 100%;
}
.contact-input:focus, .contact-textarea:focus {
    border-color: #fe7c03 !important;
    box-shadow: 0 0 0 3.5px rgba(254, 124, 3, 0.12) !important;
    outline: none;
    background: #ffffff;
}
.contact-submit-btn {
    background: linear-gradient(135deg, #fe7c03 0%, #ff5200 100%);
    border: none;
    color: #ffffff;
    font-size: 14.5px;
    font-weight: 700;
    padding: 13px 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 14px rgba(254, 124, 3, 0.28);
    cursor: pointer;
}
.contact-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(254, 124, 3, 0.38);
    color: #ffffff;
}

/* SIDE INFO CARDS */
.counseling-info-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 32px 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    margin-bottom: 22px;
}
.counseling-info-card h4 {
    font-size: 18px;
    font-weight: 800;
    color: #0e1e2e;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.counseling-feature-row {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid #f1f5f9;
}
.counseling-feature-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.counseling-feature-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #fff8f3;
    border: 1px solid #ffedd5;
    color: #fe7c03;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.counseling-feature-text h6 {
    font-size: 14px;
    font-weight: 700;
    color: #0e1e2e;
    margin-bottom: 3px;
}
.counseling-feature-text p {
    font-size: 12.5px;
    color: #64748b;
    line-height: 1.55;
    margin-bottom: 0;
}
.contact-immediate-card {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 18px;
    padding: 28px 26px;
    color: #ffffff;
}
.contact-immediate-card h5 {
    font-size: 17px;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 6px;
}
.contact-immediate-card p {
    font-size: 13px;
    color: #94a3b8;
    line-height: 1.6;
    margin-bottom: 18px;
}
.btn-brand-sm {
    background: #fe7c03;
    color: #ffffff;
    border: 1px solid #fe7c03;
    padding: 9px 18px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.btn-brand-sm:hover {
    background: #e06b00;
    color: #ffffff;
}
.btn-outline-white-sm {
    background: transparent;
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.25);
    padding: 9px 18px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.btn-outline-white-sm:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    border-color: #ffffff;
}

/* MAP SECTION */
.contact-map-section {
    padding: 30px 0 70px;
    background: #f8fafc;
}
.contact-map-wrap {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
}
.contact-map-header {
    padding: 20px 26px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.contact-map-header h5 {
    font-size: 16px;
    font-weight: 800;
    color: #0e1e2e;
    margin-bottom: 2px;
}
.contact-map-header p {
    font-size: 12.5px;
    color: #64748b;
    margin-bottom: 0;
}

/* RESPONSIVE */
@media (max-width: 991px) {
    .contact-hero-title {
        font-size: 34px;
    }
    .contact-form-card {
        padding: 26px 20px;
    }
}
@media (max-width: 576px) {
        .contact-hero-section {
        height: 44vh !important;
    }
    .contact-hero-title {
        font-size: 28px;
    }
    .contact-hero-text {
    font-size: 13.5px;
    line-height: 1.75;
    color: #475569;
    margin-bottom: 10px;
    max-width: 600px;
}
}
</style>

<!-- ================= 1. CONTACT HERO SECTION ================= -->
<section class="contact-hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7 col-xl-6">
                <!-- TOP BADGE -->
                <div class="contact-hero-badge">
                    <span class="contact-hero-badge-icon">
                        <i class="fa-solid fa-headset"></i>
                    </span>
                    <span class="contact-hero-badge-text">
                        Connect With Finchskills • Admissions &amp; Inquiries
                    </span>
                </div>

                <h1 class="contact-hero-title">
                    Let's Connect &amp; Shape Your <span class="highlight">Career</span>
                </h1>

                <p class="contact-hero-text">
                    Have questions about our job-oriented diploma programs, seat reservations, fee structure, or placement opportunities? Our expert academic advisors and admissions team are ready to guide you.
                </p>

                <!-- BREADCRUMB -->
                <nav aria-label="breadcrumb">
                    <ol class="contact-breadcrumb">
                        <li><a href="index.php"><i class="fa-solid fa-house-chimney me-1"></i> Home</a></li>
                        <li class="separator"><i class="fa-solid fa-chevron-right"></i></li>
                        <li class="active">Contact Us</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>

<!-- ================= 2. CONTACT INFO TILES ================= -->
<section class="contact-tiles-section">
    <div class="container">
        <div class="row g-4">

            <!-- TILE 1: LOCATION -->
            <div class="col-lg-3 col-md-6">
                <div class="contact-tile-card">
                    <div class="contact-tile-icon-wrap tile-icon-orange">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <span class="tile-subtitle">Campus Location</span>
                    <h3>Our Institute</h3>
                    <p><?= htmlspecialchars($page_address) ?></p>
                    <div class="contact-tile-action">
                        <a href="#campus-map"><i class="fa-solid fa-location-arrow"></i> View on Map</a>
                    </div>
                </div>
            </div>

            <!-- TILE 2: PHONE HELPLINE -->
            <div class="col-lg-3 col-md-6">
                <div class="contact-tile-card">
                    <div class="contact-tile-icon-wrap tile-icon-green">
                        <i class="fa-solid fa-phone-volume"></i>
                    </div>
                    <span class="tile-subtitle">Direct Lines</span>
                    <h3>Call Support</h3>
                    <?php if (!empty($page_phone_1)): ?>
                        <p class="mb-1">
                            <a href="tel:<?= htmlspecialchars($page_phone_1) ?>" class="fw-bold">
                                <?= htmlspecialchars($page_phone_1) ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($page_phone_2)): ?>
                        <p class="mb-0">
                            <a href="tel:<?= htmlspecialchars($page_phone_2) ?>">
                                <?= htmlspecialchars($page_phone_2) ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    <div class="contact-tile-action">
                        <?php if (!empty($page_phone_1)): ?>
                            <a href="tel:<?= htmlspecialchars($page_phone_1) ?>"><i class="fa-solid fa-phone"></i> Call Counselor</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TILE 3: EMAIL DESK -->
            <div class="col-lg-3 col-md-6">
                <div class="contact-tile-card">
                    <div class="contact-tile-icon-wrap tile-icon-blue">
                        <i class="fa-solid fa-envelope-open-text"></i>
                    </div>
                    <span class="tile-subtitle">Online Desk</span>
                    <h3>Email Us</h3>
                    <?php if (!empty($page_email_1)): ?>
                        <p class="mb-1">
                            <a href="mailto:<?= htmlspecialchars($page_email_1) ?>" class="fw-semibold">
                                <?= htmlspecialchars($page_email_1) ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($page_email_2)): ?>
                        <p class="mb-0">
                            <a href="mailto:<?= htmlspecialchars($page_email_2) ?>">
                                <?= htmlspecialchars($page_email_2) ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    <div class="contact-tile-action">
                        <a href="mailto:<?= htmlspecialchars($page_email_1) ?>"><i class="fa-solid fa-paper-plane"></i> Send Email</a>
                    </div>
                </div>
            </div>

            <!-- TILE 4: WORKING HOURS -->
            <div class="col-lg-3 col-md-6">
                <div class="contact-tile-card">
                    <div class="contact-tile-icon-wrap tile-icon-amber">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <span class="tile-subtitle">Visiting Hours</span>
                    <h3>Office Timings</h3>
                    <?php if (!empty($timing_mf)): ?>
                        <p class="mb-1 fw-semibold text-dark"><?= htmlspecialchars($timing_mf) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($timing_sat)): ?>
                        <p class="mb-0 text-muted small"><?= htmlspecialchars($timing_sat) ?></p>
                    <?php endif; ?>
                    <div class="contact-tile-action">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;">
                            <i class="fa-solid fa-circle-check me-1"></i> Open for Counseling
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ================= 3. CONTACT FORM & ADMISSION HIGHLIGHTS ================= -->
<section class="contact-form-section" id="contact-form">
    <div class="container">
        <div class="row g-4">

            <!-- LEFT: CONTACT FORM (NO PLACEHOLDERS) -->
            <div class="col-lg-7">
                <div class="contact-form-card">
                    <span class="contact-form-badge">
                        <i class="fa-solid fa-envelope me-1.5"></i> Send An Inquiry
                    </span>
                    <h2>Have a Query? Leave Us a Message</h2>
                    <p class="form-desc">
                        Fill in your details below and our academic admissions team will get in touch with you shortly.
                    </p>

                    <!-- SUCCESS ALERT -->
                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4 rounded-3 border-0 shadow-sm" style="background: #f0fdf4; border-left: 4px solid #16a34a !important;" role="alert">
                        <i class="fa-solid fa-circle-check text-success fs-4 me-3"></i>
                        <div>
                            <strong class="text-success d-block">Inquiry Submitted Successfully!</strong>
                            <span class="small text-muted">Thank you for reaching out. One of our course advisors will call or email you soon.</span>
                        </div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <!-- ERROR ALERT -->
                    <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4 rounded-3 border-0 shadow-sm" style="background: #fef2f2; border-left: 4px solid #dc2626 !important;" role="alert">
                        <i class="fa-solid fa-circle-exclamation text-danger fs-4 me-3"></i>
                        <div>
                            <strong class="text-danger d-block">Error Sending Message</strong>
                            <span class="small text-muted"><?= htmlspecialchars($_GET['error']) ?></span>
                        </div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <!-- FORM (PLACEHOLDERS COMPLETELY REMOVED) -->
                    <form action="send-contact.php" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="contact-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="contact-input" required>
                            </div>
                            <div class="col-md-6">
                                <label class="contact-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="contact-input" required>
                            </div>
                            <div class="col-md-6">
                                <label class="contact-label">Phone / Mobile Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="contact-input" maxlength="15" required>
                            </div>
                            <div class="col-md-6">
                                <label class="contact-label">Subject / Interested Course</label>
                                <input type="text" name="subject" class="contact-input">
                            </div>
                            <div class="col-12">
                                <label class="contact-label">Your Message or Inquiry <span class="text-danger">*</span></label>
                                <textarea name="message" class="contact-textarea" rows="4" required></textarea>
                            </div>
                            <div class="col-12 mt-3">
                                <button type="submit" class="contact-submit-btn">
                                    <span><i class="fa-solid fa-paper-plane me-1.5"></i> Submit Inquiry</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                                <p class="small text-muted mt-2 mb-0">
                                    <i class="fa-solid fa-lock text-secondary me-1"></i> Your details are confidential and securely protected.
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- RIGHT: COUNSELING BENEFITS & SHORTCUTS -->
            <div class="col-lg-5">
                <div class="counseling-info-card">
                    <h4>
                        <i class="fa-solid fa-award text-warning"></i> Why Speak With Us?
                    </h4>

                    <div class="counseling-feature-row">
                        <div class="counseling-feature-icon">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                        <div class="counseling-feature-text">
                            <h6>Expert Career Guidance</h6>
                            <p>Understand which diploma in Aviation, Hospitality, or Cruise matches your skillset and career aspirations.</p>
                        </div>
                    </div>

                    <div class="counseling-feature-row">
                        <div class="counseling-feature-icon">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                        <div class="counseling-feature-text">
                            <h6>Transparent Fee &amp; Scholarships</h6>
                            <p>Get detailed installment breakdown, full-payment discount benefits, and 100% seat reservation fee adjustments.</p>
                        </div>
                    </div>

                    <div class="counseling-feature-row">
                        <div class="counseling-feature-icon">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <div class="counseling-feature-text">
                            <h6>Placement &amp; Industry Exposure</h6>
                            <p>Learn about our dedicated placement cell, interview grooming, airline tie-ups, and live practical sessions.</p>
                        </div>
                    </div>
                </div>

                <!-- IMMEDIATE ASSISTANCE CALLOUT -->
                <div class="contact-immediate-card">
                    <h5>Need Immediate Admission Help?</h5>
                    <p>Speak directly with an academic counselor right away or reserve your seat online in under 2 minutes.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!empty($page_phone_1)): ?>
                            <a href="tel:<?= htmlspecialchars($page_phone_1) ?>" class="btn-brand-sm">
                                <i class="fa-solid fa-phone"></i> Call Now
                            </a>
                        <?php endif; ?>
                        <a href="seat-reservation" class="btn-outline-white-sm">
                            <i class="fa-solid fa-chair"></i> Reserve Seat
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ================= 4. MAP & CAMPUS LOCATION ================= -->
<section class="contact-map-section" id="campus-map">
    <div class="container">
        <div class="contact-map-wrap">
            <div class="contact-map-header">
                <div>
                    <h5><i class="fa-solid fa-map-location-dot text-warning me-2"></i>Visit Finchskills Campus</h5>
                    <p><?= htmlspecialchars($page_address) ?></p>
                </div>
                <a href="https://maps.google.com/?q=<?= urlencode($page_address) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-dark fw-bold rounded-pill px-3 py-1.5" style="font-size: 12.5px;">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open in Google Maps
                </a>
            </div>
            <div class="ratio ratio-21x9" style="min-height: 300px;">
                <iframe src="<?= htmlspecialchars($map_url) ?>" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<?php include "footer.php" ?>
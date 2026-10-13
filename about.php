<?php
$page_title = "About Us - Finchskills Institute | Aviation, Hospitality, Travel & Cruise Training";
include "header.php";
?>

<style>
/* ================= ABOUT US PAGE STYLES (HOMEPAGE MATCHING) ================= */
.abt-hero-section {
    background: linear-gradient(180deg, #fffbf8 0%, #ffffff 100%);
    padding: 60px 0 75px;
    position: relative;
    overflow: hidden;
    border-bottom: 1px solid #f1f5f9;
}
.abt-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #fff8f3;
    border: 1px solid #ffd8bf;
    padding: 6px 18px 6px 8px;
    border-radius: 50px;
    margin-bottom: 20px;
}
.abt-hero-badge-icon {
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
.abt-hero-badge-text {
    font-size: 12.5px;
    font-weight: 700;
    color: #9a3412;
    letter-spacing: 0.3px;
}
.abt-hero-title {
    font-size: 48px;
    line-height: 1.18;
    font-weight: 900;
    color: #0e1e2e;
    margin-bottom: 20px;
    letter-spacing: -0.5px;
}
.abt-hero-title .highlight {
    color: #fe7c03;
}
.abt-hero-text {
    font-size: 15.5px;
    line-height: 1.75;
    color: #475569;
    margin-bottom: 26px;
    max-width: 580px;
}
.abt-hero-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 30px;
}
.abt-tag-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    padding: 7px 15px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    transition: all 0.25s ease;
}
.abt-tag-pill:hover {
    border-color: #fe7c03;
    color: #fe7c03;
    transform: translateY(-2px);
}
.abt-tag-pill i {
    color: #fe7c03;
    font-size: 13.5px;
}
.abt-hero-image-wrap {
    position: relative;
    text-align: center;
}
.abt-hero-image-wrap img {
    border-radius: 22px;
    border: 1px solid #e2e8f0;
    width: 100%;
    max-width: 500px;
}
.abt-floating-badge {
    position: absolute;
    bottom: -18px;
    left: 20px;
    background: #ffffff;
    border: 1.5px solid #fed7aa;
    border-radius: 14px;
    padding: 12px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    text-align: left;
}
.abt-floating-badge i {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #fff7ed;
    color: #fe7c03;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

/* SECTION HEADINGS */
.abt-section-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff8f3;
    border: 1px solid #ffd8bf;
    padding: 5px 15px;
    border-radius: 50px;
    color: #fe7c03;
    font-size: 12.5px;
    font-weight: 700;
    margin-bottom: 14px;
}
.abt-section-title {
    font-size: 36px;
    font-weight: 900;
    color: #0e1e2e;
    line-height: 1.25;
    margin-bottom: 16px;
}
.abt-section-title .highlight {
    color: #fe7c03;
}
.abt-section-subtitle {
    font-size: 15.5px;
    color: #64748b;
    line-height: 1.7;
    max-width: 680px;
}

/* STORY / ABOUT SECTION */
.abt-story-section {
    padding: 85px 0;
    background: #ffffff;
}
.abt-story-img-wrap {
    position: relative;
}
.abt-story-img-wrap img {
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    width: 100%;
}
.abt-feature-check-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
}
.abt-check-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #ecfdf5;
    color: #059669;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
    margin-top: 2px;
}

/* VALUE PILLARS CARDS */
.abt-values-section {
    padding: 85px 0;
    background: #fafbfc;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
}
.abt-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 18px;
    padding: 34px 28px;
    height: 100%;
    transition: all 0.25s ease;
    box-shadow: none !important;
    position: relative;
    overflow: hidden;
}
.abt-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: transparent;
    transition: background 0.3s ease;
}
.abt-card:hover {
    transform: translateY(-4px);
    border-color: #ffd8bf;
    background: #fffcf9;
}
.abt-card:hover::before {
    background: linear-gradient(90deg, #fe7c03, #ff5200);
}
.abt-card-icon {
    width: 56px;
    height: 56px;
    border-radius: 15px;
    background: #fff7ed;
    border: 1px solid #ffedd5;
    color: #fe7c03;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}
.abt-card:hover .abt-card-icon {
    background: linear-gradient(135deg, #fe7c03 0%, #ff5200 100%);
    color: #ffffff;
    border-color: transparent;
    transform: scale(1.05);
}
.abt-card h4 {
    font-size: 19px;
    font-weight: 800;
    color: #0e1e2e;
    margin-bottom: 12px;
}
.abt-card p {
    font-size: 14px;
    line-height: 1.7;
    color: #64748b;
    margin-bottom: 0;
}

/* STATS COUNTER STRIP */
.abt-counter-section {
    background: #0e1e2e;
    padding: 55px 0;
    position: relative;
}
.abt-stat-box {
    text-align: center;
    padding: 15px 12px;
    border-right: 1px solid rgba(255, 255, 255, 0.08);
}
.abt-stat-box:last-child {
    border-right: none;
}
.abt-stat-number {
    font-size: 42px;
    font-weight: 900;
    color: #ffffff;
    margin-bottom: 4px;
    line-height: 1;
}
.abt-stat-number span {
    color: #fe7c03;
}
.abt-stat-label {
    font-size: 13.5px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0;
}

/* 4-STEP FINCHSKILLS ENROLLMENT & LEARNING FLOW */
.abt-process-section {
    padding: 85px 0;
    background: #ffffff;
}
.abt-process-grid {
    position: relative;
}
.abt-step-col {
    position: relative;
}
@media (min-width: 992px) {
    .abt-step-col:not(:last-child)::after {
        content: '\f054';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        right: -13px;
        top: 45%;
        transform: translateY(-50%);
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #ffffff;
        border: 1.5px solid #fed7aa;
        color: #fe7c03;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        z-index: 2;
    }
}
.abt-step-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    padding: 28px 20px;
    height: 100%;
    position: relative;
    transition: all 0.25s ease;
    box-shadow: none !important;
    display: flex;
    flex-direction: column;
}
.abt-step-card:hover {
    transform: translateY(-3px);
    border-color: #fe7c03;
    background: #fffcf9;
}
.abt-step-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.abt-step-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    background: #fff7ed;
    color: #fe7c03;
    font-weight: 800;
    font-size: 11.5px;
    border: 1px solid #fed7aa;
    letter-spacing: 0.5px;
}
.abt-step-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #fff7ed;
    color: #fe7c03;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: all 0.25s ease;
}
.abt-step-card:hover .abt-step-icon {
    background: linear-gradient(135deg, #fe7c03 0%, #ff5200 100%);
    color: #ffffff;
}
.abt-step-card h4 {
    font-size: 18px;
    font-weight: 800;
    color: #0e1e2e;
    margin-bottom: 10px;
}
.abt-step-card p {
    font-size: 13.5px;
    line-height: 1.65;
    color: #64748b;
    margin-bottom: 18px;
    flex-grow: 1;
}
.abt-step-action {
    margin-top: auto;
    padding-top: 14px;
    border-top: 1px dashed #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.abt-step-link {
    font-size: 13px;
    font-weight: 700;
    color: #fe7c03;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: gap 0.2s ease;
}
.abt-step-link:hover {
    color: #ff5200;
    gap: 9px;
}
.abt-step-subnote {
    font-size: 11px;
    font-weight: 600;
    color: #16a34a;
    background: #f0fdf4;
    padding: 2px 8px;
    border-radius: 4px;
    border: 1px solid #bbf7d0;
}

/* RECOGNITIONS */
.abt-recognition-section {
    padding: 80px 0;
    background: #fafbfc;
    border-top: 1px solid #f1f5f9;
}
.abt-recognition-box {
    background: #ffffff;
    border: 1.5px solid #fed7aa;
    border-radius: 20px;
    padding: 44px 38px;
    box-shadow: none !important;
}

@media (max-width: 991px) {
    .abt-hero-title {
        font-size: 36px;
    }
    .abt-hero-section {
        padding: 45px 0 55px;
    }
    .abt-stat-box {
        border-right: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 15px 10px;
    }
    .abt-stat-box:last-child {
        border-bottom: none;
    }
    .abt-floating-badge {
        position: static;
        margin-top: 18px;
    }
}
@media (max-width: 575px) {
    .abt-hero-title {
        font-size: 27px;
    }
    .abt-section-title {
        font-size: 26px;
    }
    .abt-stat-number {
        font-size: 32px;
    }
    .abt-card {
        padding: 24px 18px;
    }
    .abt-recognition-box {
        padding: 28px 20px;
    }
}
</style>

<!-- ================= 1. HERO SECTION ================= -->
<section class="abt-hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <!-- TOP BADGE -->
                <div class="abt-hero-badge">
                    <span class="abt-hero-badge-icon">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </span>
                    <span class="abt-hero-badge-text">
                        ABOUT FINCHSKILLS INSTITUTE <span class="badge-dot">•</span> EMPOWERING CAREERS
                    </span>
                </div>

                <h1 class="abt-hero-title">
                    Building Practical Skills. <br>
                    Shaping <span class="highlight">Job-Ready</span> Futures.
                </h1>

                <p class="abt-hero-text">
                    Finchskills Institute is a premier skill-development academy dedicated to preparing aspiring students for high-growth careers across <strong>Aviation</strong>, <strong>Hospitality</strong>, <strong>Travel &amp; Tourism</strong>, and <strong>Cruise Management</strong>. We don't just teach theory &mdash; we equip you with real, actionable competencies required by top employers.
                </p>

                <!-- TAG PILLS -->
                <div class="abt-hero-tags">
                    <span class="abt-tag-pill">
                        <i class="fa-solid fa-plane-departure"></i> Aviation Management
                    </span>
                    <span class="abt-tag-pill">
                        <i class="fa-solid fa-hotel"></i> Hospitality &amp; Guest Relations
                    </span>
                    <span class="abt-tag-pill">
                        <i class="fa-solid fa-earth-americas"></i> Travel &amp; Ticketing
                    </span>
                    <span class="abt-tag-pill">
                        <i class="fa-solid fa-ship"></i> Cruise Management
                    </span>
                </div>

                <!-- HERO BUTTONS MATCHING HOMEPAGE -->
                <div class="hero-btns">
                    <a href="courses.php" class="hero-btn hero-btn-register">
                        <span>Explore All Courses</span>
                        <i class="fa-solid fa-arrow-right ms-2 hero-btn-arrow"></i>
                    </a>
                    <?php if (!empty($isStudentLoggedIn)): ?>
                        <a href="seat-reservation.php" class="hero-btn hero-btn-fee">
                            <i class="fa-solid fa-chair me-2"></i>
                            <span>Reserve Your Seat</span>
                            <i class="fa-solid fa-chevron-right ms-2 hero-btn-arrow"></i>
                        </a>
                    <?php else: ?>
                        <a href="register.php?redirect=seat-reservation.php" class="hero-btn hero-btn-fee">
                            <i class="fa-solid fa-chair me-2"></i>
                            <span>Reserve Your Seat</span>
                            <i class="fa-solid fa-chevron-right ms-2 hero-btn-arrow"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="abt-hero-image-wrap">
                    <img src="img/about-hero.png" alt="About Finchskills Institute" class="img-fluid">
                    <div class="abt-floating-badge">
                        <i class="fa-solid fa-award"></i>
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 14px;">100% Practical Focused</div>
                            <div class="text-muted small">Simulation Labs &amp; Placement Support</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= 2. OUR STORY & PHILOSOPHY ================= -->
<section class="abt-story-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="abt-story-img-wrap">
                    <img src="img/about.png" alt="Practical Training at Finchskills">
                    <div class="abt-story-badge">
                        <i class="fa-solid fa-star me-1"></i> 6+ Years Excellence
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="abt-section-badge">
                    <i class="fa-solid fa-compass"></i>
                    <span>OUR PHILOSOPHY</span>
                </div>
                <h2 class="abt-section-title">
                    Real World Learning That Prepares You For <span class="highlight">Real Industry Careers</span>
                </h2>
                <p class="text-muted mb-4" style="line-height: 1.75; font-size: 15px;">
                    Finchskills Institute was founded with a singular conviction: skills matter far more than paper degrees alone. We observed a significant gap between traditional academic education and the actual operational expectations of airlines, luxury hotels, and international travel enterprises.
                </p>

                <div class="mb-4">
                    <div class="abt-feature-check-item">
                        <div class="abt-check-icon"><i class="fa-solid fa-check"></i></div>
                        <div>
                            <strong class="text-dark d-block">Industry-Aligned Practical Curriculum:</strong>
                            <span class="text-muted small">Curriculum developed in consultation with airline captains, ground operations heads, and luxury hospitality managers.</span>
                        </div>
                    </div>
                    <div class="abt-feature-check-item">
                        <div class="abt-check-icon"><i class="fa-solid fa-check"></i></div>
                        <div>
                            <strong class="text-dark d-block">Hands-on GDS &amp; Ticketing Systems:</strong>
                            <span class="text-muted small">Real computerized reservation training on Amadeus and Galileo software used by global travel agencies.</span>
                        </div>
                    </div>
                    <div class="abt-feature-check-item">
                        <div class="abt-check-icon"><i class="fa-solid fa-check"></i></div>
                        <div>
                            <strong class="text-dark d-block">Personality Grooming &amp; Fluency:</strong>
                            <span class="text-muted small">Extensive grooming sessions, corporate etiquette, body language mastering, and spoken English communication.</span>
                        </div>
                    </div>
                    <div class="abt-feature-check-item">
                        <div class="abt-check-icon"><i class="fa-solid fa-check"></i></div>
                        <div>
                            <strong class="text-dark d-block">Dedicated Career Placement Cell:</strong>
                            <span class="text-muted small">Continuous interview drives, resume preparation, mock airline interviews, and verified recruiter placement opportunities.</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <?php if (!empty($isStudentLoggedIn)): ?>
                        <a href="form-submission.php" class="why-primary-btn">
                            <i class="fa-solid fa-file-signature me-1"></i>
                            <span>Admission</span>
                            <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="why-primary-btn">
                            <i class="fa-solid fa-user-pen me-1"></i>
                            <span>Registration</span>
                            <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    <?php endif; ?>
                    <a href="courses.php" class="why-outline-btn">
                        <span>Explore Courses</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= 3. CORE VALUE PILLARS ================= -->
<section class="abt-values-section">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width: 700px;">
            <div class="abt-section-badge">
                <i class="fa-solid fa-bullseye"></i>
                <span>PURPOSE &amp; VALUES</span>
            </div>
            <h2 class="abt-section-title">
                Our Commitment to Every <span class="highlight">Student's Success</span>
            </h2>
            <p class="abt-section-subtitle mx-auto">
                We are committed to delivering honest mentorship, career-aligned curriculum, and supportive guidance every single step of your professional journey.
            </p>
        </div>

        <div class="row g-4">
            <!-- CARD 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="abt-card">
                    <div class="abt-card-icon">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                    <h4>Our Vision</h4>
                    <p>
                        To become India's foremost center of excellence for service-industry career training &mdash; empowering talented individuals with international-standard professional competencies, self-confidence, and guaranteed job readiness.
                    </p>
                </div>
            </div>

            <!-- CARD 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="abt-card">
                    <div class="abt-card-icon">
                        <i class="fa-solid fa-bullseye"></i>
                    </div>
                    <h4>Our Mission</h4>
                    <p>
                        To deliver accessible, practical, and results-driven training that bridges the gap between ambition and employment. We mentor every candidate with personalized attention, modern labs, and real industry exposure.
                    </p>
                </div>
            </div>

            <!-- CARD 3 -->
            <div class="col-lg-4 col-md-12">
                <div class="abt-card">
                    <div class="abt-card-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h4>Our Core Values</h4>
                    <p>
                        We stand firmly for integrity, practical learning, and transparent guidance. We treat every student's career aspirations as our own personal responsibility, providing genuine placement support and lifetime value.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= 4. NUMBERS COUNTER STRIP ================= -->
<section class="abt-counter-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-6 col-lg-3">
                <div class="abt-stat-box">
                    <div class="abt-stat-number">1000<span>+</span></div>
                    <p class="abt-stat-label">Students Trained</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="abt-stat-box">
                    <div class="abt-stat-number">6<span>+</span></div>
                    <p class="abt-stat-label">Years of Excellence</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="abt-stat-box">
                    <div class="abt-stat-number">15<span>+</span></div>
                    <p class="abt-stat-label">Career Programs</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="abt-stat-box">
                    <div class="abt-stat-number">98<span>%</span></div>
                    <p class="abt-stat-label">Placement Success Rate</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= 5. HOW LEARNING WORKS (OUR ADMISSION & LEARNING FLOW) ================= -->
<section class="abt-process-section">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width: 700px;">
            <div class="abt-section-badge">
                <i class="fa-solid fa-route"></i>
                <span>STEP-BY-STEP JOURNEY</span>
            </div>
            <h2 class="abt-section-title">
                How Learning Works at <span class="highlight">Finchskills</span>
            </h2>
            <p class="abt-section-subtitle mx-auto">
                A structured, outcome-based pathway designed to take you from initial registration to verified job-ready career placement.
            </p>
        </div>

        <div class="row g-4 abt-process-grid">
            <!-- STEP 1: REGISTRATION -->
            <div class="col-lg-3 col-md-6 abt-step-col">
                <div class="abt-step-card">
                    <div class="abt-step-header">
                        <span class="abt-step-badge">STEP 01</span>
                        <div class="abt-step-icon">
                            <i class="fa-solid fa-user-pen"></i>
                        </div>
                    </div>
                    <h4>Registration</h4>
                    <p>
                        Create your student account using your mobile number and OTP verification. Access your student dashboard and explore industry-aligned programs.
                    </p>
                    <div class="abt-step-action">
                        <?php if (!empty($isStudentLoggedIn)): ?>
                            <span class="abt-step-subnote"><i class="fa-solid fa-circle-check me-1"></i>Registered</span>
                            <a href="student-profile.php" class="abt-step-link">My Profile <i class="fa-solid fa-arrow-right"></i></a>
                        <?php else: ?>
                            <span class="text-muted small">New Student</span>
                            <a href="register.php" class="abt-step-link">Registration <i class="fa-solid fa-arrow-right"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- STEP 2: SEAT RESERVATION -->
            <div class="col-lg-3 col-md-6 abt-step-col">
                <div class="abt-step-card">
                    <div class="abt-step-header">
                        <span class="abt-step-badge">STEP 02</span>
                        <div class="abt-step-icon">
                            <i class="fa-solid fa-chair"></i>
                        </div>
                    </div>
                    <h4>Seat Reservation</h4>
                    <p>
                        Choose your target batch and secure your seat online. Your seat reservation fee is <strong>100% adjusted</strong> directly into your total course fee.
                    </p>
                    <div class="abt-step-action">
                        <span class="abt-step-subnote"><i class="fa-solid fa-shield-check me-1"></i>100% Adjusted</span>
                        <?php if (!empty($isStudentLoggedIn)): ?>
                            <a href="seat-reservation.php" class="abt-step-link">Book Your Seat <i class="fa-solid fa-arrow-right"></i></a>
                        <?php else: ?>
                            <a href="register.php?redirect=seat-reservation.php" class="abt-step-link">Book Your Seat <i class="fa-solid fa-arrow-right"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- STEP 3: ADMISSION -->
            <div class="col-lg-3 col-md-6 abt-step-col">
                <div class="abt-step-card">
                    <div class="abt-step-header">
                        <span class="abt-step-badge">STEP 03</span>
                        <div class="abt-step-icon">
                            <i class="fa-solid fa-file-signature"></i>
                        </div>
                    </div>
                    <h4>Admission</h4>
                    <p>
                        Complete the official online admission form, upload your educational documents (10th/12th marksheet &amp; ID), and confirm your batch schedule.
                    </p>
                    <div class="abt-step-action">
                        <span class="text-muted small">Official Enrollment</span>
                        <?php if (!empty($isStudentLoggedIn)): ?>
                            <a href="form-submission.php" class="abt-step-link">Admission Form <i class="fa-solid fa-arrow-right"></i></a>
                        <?php else: ?>
                            <a href="register.php?redirect=form-submission.php" class="abt-step-link">Admission <i class="fa-solid fa-arrow-right"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- STEP 4: TRAINING & PLACEMENT -->
            <div class="col-lg-3 col-md-6 abt-step-col">
                <div class="abt-step-card">
                    <div class="abt-step-header">
                        <span class="abt-step-badge">STEP 04</span>
                        <div class="abt-step-icon">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                    </div>
                    <h4>Training &amp; Placement</h4>
                    <p>
                        Undergo hands-on training with airline GDS systems, personality grooming, mock interviews, and receive 100% placement support for top companies.
                    </p>
                    <div class="abt-step-action">
                        <span class="abt-step-subnote"><i class="fa-solid fa-star me-1"></i>100% Placement</span>
                        <a href="courses.php" class="abt-step-link">Explore Courses <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= 6. RECOGNITION & ACCREDITATION ================= -->
<section class="abt-recognition-section">
    <div class="container">
        <div class="abt-recognition-box">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon" style="width: 50px; height: 50px; border-radius: 12px; background: #fff7ed; color: #fe7c03; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                            <i class="fa-solid fa-certificate"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark" style="font-size: 24px;">Government Registered &amp; Industry Aligned</h3>
                            <span class="text-muted small">Standards-Compliant Quality Vocational Education</span>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.75;">
                        Finchskills Institute is officially registered under the <strong>Ministry of Micro, Small &amp; Medium Enterprises (Government of India)</strong>. Our programs adhere strictly to global distribution systems (Amadeus &amp; Galileo GDS) and contemporary airline ground handling protocols, ensuring credentials that recruiters across India and abroad respect and value.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <?php if (!empty($isStudentLoggedIn)): ?>
                        <a href="seat-reservation.php" class="hero-btn hero-btn-register">
                            <i class="fa-solid fa-chair me-2"></i>
                            <span>Reserve Your Seat</span>
                        </a>
                    <?php else: ?>
                        <a href="register.php?redirect=seat-reservation.php" class="hero-btn hero-btn-register">
                            <i class="fa-solid fa-user-pen me-2"></i>
                            <span>Registration</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= 7. TESTIMONIALS (MATCHING HOMEPAGE) ================= -->
<section class="testimonial-section">
    <div class="container">
        <div class="testimonial-wrapper">
            <div class="row align-items-center g-4">
                <!-- LEFT -->
                <div class="col-lg-6">
                    <h2 class="section-title">What Our Learners Say</h2>
                    <p class="section-subtitle">
                        Hear from students who transformed their skills and achieved their career goals with Finchskills Institute.
                    </p>
                    <div class="user-group">
                        <div class="avatars">
                            <img src="img/womens-profile.png" class="testimonial-user active" alt="Priya Sharma" />
                            <img src="img/mens-profile.png" class="testimonial-user" alt="Rahul Verma" />
                            <img src="img/womens-profile.png" class="testimonial-user" alt="Sneha Patel" />
                            <img src="img/mens-profile.png" class="testimonial-user" alt="Ankit Singh" />
                            <img src="img/womens-profile.png" class="testimonial-user" alt="Ayesha Khan" />
                        </div>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="col-lg-6">
                    <div class="owl-carousel testimonial-carousel owl-theme">
                        <!-- ITEM 1 -->
                        <div class="item">
                            <div class="testimonial-box">
                                <div class="testimonial-author">
                                    <img src="img/womens-profile.png" alt="Priya Sharma" />
                                    <div class="author-content">
                                        <h4>Priya Sharma</h4>
                                        <span>Air Hostess</span>
                                    </div>
                                </div>
                                <p class="testimonial-text">
                                    "Finchskills Institute provided an excellent learning experience. The trainers were supportive, the classes were interactive, and the personality development sessions greatly improved my confidence and communication skills."
                                </p>
                            </div>
                        </div>

                        <!-- ITEM 2 -->
                        <div class="item">
                            <div class="testimonial-box">
                                <div class="testimonial-author">
                                    <img src="img/mens-profile.png" alt="Rahul Verma" />
                                    <div class="author-content">
                                        <h4>Rahul Verma</h4>
                                        <span>Airport Terminal Management</span>
                                    </div>
                                </div>
                                <p class="testimonial-text">
                                    "The course was well-organized and easy to follow. The faculty explained airport operations clearly, and the flexible classes made learning convenient. Finchskills Institute is a great choice for professional skill development."
                                </p>
                            </div>
                        </div>

                        <!-- ITEM 3 -->
                        <div class="item">
                            <div class="testimonial-box">
                                <div class="testimonial-author">
                                    <img src="img/womens-profile.png" alt="Sneha Patel" />
                                    <div class="author-content">
                                        <h4>Sneha Patel</h4>
                                        <span>Travel &amp; Tourism</span>
                                    </div>
                                </div>
                                <p class="testimonial-text">
                                    "I had a wonderful experience learning with Finchskills Institute. The study material was informative, the trainers were knowledgeable, and the sessions helped me build confidence and industry knowledge. Highly recommended!"
                                </p>
                            </div>
                        </div>

                        <!-- ITEM 4 -->
                        <div class="item">
                            <div class="testimonial-box">
                                <div class="testimonial-author">
                                    <img src="img/mens-profile.png" alt="Ankit Singh" />
                                    <div class="author-content">
                                        <h4>Ankit Singh</h4>
                                        <span>Hotel Management</span>
                                    </div>
                                </div>
                                <p class="testimonial-text">
                                    "Finchskills Institute made learning simple and engaging. The faculty was experienced, the course content was practical, and every session helped me improve my hospitality skills. It was a valuable learning experience."
                                </p>
                            </div>
                        </div>

                        <!-- ITEM 5 -->
                        <div class="item">
                            <div class="testimonial-box">
                                <div class="testimonial-author">
                                    <img src="img/womens-profile.png" alt="Ayesha Khan" />
                                    <div class="author-content">
                                        <h4>Ayesha Khan</h4>
                                        <span>Customer Service</span>
                                    </div>
                                </div>
                                <p class="testimonial-text">
                                    "The course at Finchskills Institute exceeded my expectations. The trainers explained every topic clearly, and the practical examples made learning easy. I gained valuable skills and felt more confident in my professional communication."
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= 8. CTA PRE-FOOTER BANNER ================= -->
<section class="instructor-section">
    <div class="container">
        <div class="instructor-content">
            <h2 class="instructor-title">
                Build Your Skills.<br>
                Shape Your Future.
            </h2>
            <p class="instructor-subtitle">
                Join expert-led courses, gain practical knowledge, and achieve your career goals with confidence.
            </p>
            <?php if (!empty($isStudentLoggedIn)): ?>
                <a href="form-submission.php" class="instructor-btn">
                    <i class="fa-solid fa-file-signature me-2"></i>
                    <span>Admission</span>
                    <i class="fa-solid fa-arrow-right ms-2 instructor-btn-arrow"></i>
                </a>
            <?php else: ?>
                <a href="register.php" class="instructor-btn">
                    <i class="fa-solid fa-user-pen me-2"></i>
                    <span>Registration</span>
                    <i class="fa-solid fa-arrow-right ms-2 instructor-btn-arrow"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FOOTER -->
<?php include "footer.php"; ?>

<script>
$(document).ready(function () {
    const users = document.querySelectorAll(".testimonial-user");
    const owl = $(".testimonial-carousel");

    users.forEach((user, index) => {
        user.addEventListener("click", () => {
            users.forEach(item => item.classList.remove("active"));
            user.classList.add("active");
            owl.trigger('to.owl.carousel', [index, 600]);
        });
    });

    owl.on('changed.owl.carousel', function (event) {
        const index = event.item.index - event.relatedTarget._clones.length / 2;
        const realIndex = index % users.length;
        users.forEach(item => item.classList.remove("active"));
        if (users[realIndex]) {
            users[realIndex].classList.add("active");
        }
    });

    owl.owlCarousel({
        items: 1,
        loop: true,
        autoplay: true,
        autoplayTimeout: 5000,
        autoplayHoverPause: true,
        smartSpeed: 800,
        dots: true,
        nav: false
    });
});
</script>

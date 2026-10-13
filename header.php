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
$site_phone_1 = getSetting('phone_1', '+919650386711');
$site_phone_2 = getSetting('phone_2', '');
$site_email_1 = getSetting('email_1', 'hello@finchskills.com');
$site_email_2 = getSetting('email_2', 'admission@finchskills.com');
$site_logo    = getSetting('site_logo', 'img/logo.png');
$site_favicon = getSetting('site_favicon', 'img/favicon.png');
$currentPage  = basename($_SERVER['PHP_SELF'] ?? '');

$isStudentLoggedIn = isset($_SESSION['student_logged_in']) && $_SESSION['student_logged_in'] === true;
$studentName = $_SESSION['student_name'] ?? 'Student';
$studentPhoto = $_SESSION['student_photo'] ?? '';
$studentCustomId = $_SESSION['student_id'] ?? '';

if (!function_exists('renderEnrollBtn')) {
    function renderEnrollBtn($courseTitle = '', $extraClass = '') {
        $isLoggedIn = !empty($_SESSION['student_logged_in']);
        $courseParam = $courseTitle ? '?course=' . urlencode($courseTitle) : '';
        if ($isLoggedIn) {
            return '<a href="form-submission.php' . $courseParam . '" class="enroll-btn ' . htmlspecialchars($extraClass) . '" title="Admission for ' . htmlspecialchars($courseTitle) . '">'
                . '<i class="fa-solid fa-file-signature"></i> '
                . '<span>Admission</span>'
                . '</a>';
        } else {
            $redirect = 'form-submission.php' . $courseParam;
            return '<a href="register.php?redirect=' . urlencode($redirect) . '" class="enroll-btn ' . htmlspecialchars($extraClass) . '" title="Register to Apply">'
                . '<i class="fa-solid fa-user-pen"></i> '
                . '<span>Registration</span>'
                . '</a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Finchskills Institute' ?></title>
    <link rel="icon" href="<?= htmlspecialchars($site_favicon) ?>" type="image/png">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Fontawesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Google Font (Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="style.css">

    <!-- Owl Carousel CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">

    <style>
        /* Student Header Avatar & Dropdown */
        .student-header-dropdown .dropdown-toggle::after {
            display: none !important;
        }
        .student-header-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 3px 12px 3px 4px !important;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 50px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: none !important;
        }
        .student-header-btn:hover,
        .student-header-btn:focus,
        .student-header-dropdown.show .student-header-btn {
            border-color: #fe7c03;
            background: #fff8f3;
        }
        .student-header-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fe7c03;
            background: #fff;
        }
        .student-header-avatar-placeholder {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fe7c03 0%, #ff5200 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
        }
        .student-header-name {
            font-size: 13.5px;
            font-weight: 600;
            color: #0e1e2e;
            max-width: 110px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .student-header-dropdown .dropdown-menu {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(14, 30, 46, 0.08);
            padding: 8px;
            min-width: 220px;
            margin-top: 8px !important;
        }
        .student-header-dropdown .dropdown-item {
            font-size: 13px;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 8px;
            color: #334155;
            transition: all 0.15s ease;
        }
        .student-header-dropdown .dropdown-item:hover {
            background: #fff8f3;
            color: #fe7c03;
        }
        .student-header-dropdown .dropdown-item i {
            width: 18px;
            text-align: center;
        }
    </style>

</head>

<body>

    <!-- ================= TOPBAR ================= -->

    <div class="topbar">

        <div class="container d-flex flex-wrap justify-content-between align-items-center">

            <div class="topbar-left d-flex align-items-center">
                <a href="tel:<?= htmlspecialchars($site_phone_1) ?>" class="topbar-link">
                    <span class="topbar-icon"><i class="fa-solid fa-phone"></i></span>
                    <span><?= htmlspecialchars($site_phone_1) ?></span>
                </a>
                <?php if (!empty($site_phone_2)): ?>
                <span class="topbar-divider d-none d-sm-inline-block"></span>
                <a href="tel:<?= htmlspecialchars($site_phone_2) ?>" class="topbar-link d-none d-sm-inline-flex">
                    <span class="topbar-icon"><i class="fa-solid fa-phone"></i></span>
                    <span><?= htmlspecialchars($site_phone_2) ?></span>
                </a>
                <?php endif; ?>
            </div>

            <div class="topbar-right d-flex align-items-center">
                <a href="mailto:<?= htmlspecialchars($site_email_1) ?>" class="topbar-link">
                    <span class="topbar-icon"><i class="fa-regular fa-envelope"></i></span>
                    <span><?= htmlspecialchars($site_email_1) ?></span>
                </a>
                <?php if (!empty($site_email_2)): ?>
                <span class="topbar-divider d-none d-md-inline-block"></span>
                <a href="mailto:<?= htmlspecialchars($site_email_2) ?>" class="topbar-link d-none d-md-inline-flex">
                    <span class="topbar-icon"><i class="fa-regular fa-envelope"></i></span>
                    <span><?= htmlspecialchars($site_email_2) ?></span>
                </a>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- ================= NAVBAR ================= -->

    <nav class="navbar navbar-expand-lg">

        <div class="container">

            <!-- LOGO -->

            <a class="edu-logo-brand" href="index.php">
                <div class="edu-logo-img-wrap">
                    <img src="<?= htmlspecialchars($site_logo) ?>" alt="Finchskills Logo">
                </div>
            </a>

            <!-- TOGGLE -->

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">

                <i class="fa-solid fa-bars"></i>

            </button>

            <!-- MENU -->

            <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">

                <ul class="navbar-nav align-items-lg-center">

                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'index.php' || $currentPage == '') ? 'active' : '' ?>" href="index.php">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'about.php') ? 'active' : '' ?>" href="about.php">ABOUT US</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'courses.php' || $currentPage == 'course-detail.php') ? 'active' : '' ?>" href="courses.php">COURSES</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'placement.php') ? 'active' : '' ?>" href="placement.php">PLACEMENT</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'contact.php') ? 'active' : '' ?>" href="contact.php">CONTACT US</a>
                    </li>

                </ul>

                <!-- RIGHT -->

                <div class="header-right">

                    <a href="courses.php" class="header-btn header-btn-outline">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>Explore Courses</span>
                    </a>

                    <?php if ($isStudentLoggedIn): ?>
                        <div class="dropdown student-header-dropdown">
                            <button class="btn student-header-btn dropdown-toggle" type="button" id="studentAccountDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php if (!empty($studentPhoto) && file_exists(__DIR__ . '/' . $studentPhoto)): ?>
                                    <img src="<?= htmlspecialchars($studentPhoto) ?>" alt="<?= htmlspecialchars($studentName) ?>" class="student-header-avatar">
                                <?php else: ?>
                                    <span class="student-header-avatar-placeholder">
                                        <?= strtoupper(substr(trim($studentName), 0, 1)) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="student-header-name"><?= htmlspecialchars(explode(' ', trim($studentName))[0]) ?></span>
                                <i class="fa-solid fa-chevron-down text-muted ms-1" style="font-size: 10px;"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="studentAccountDropdown">
                                <li class="px-3 py-2 border-bottom">
                                    <div class="fw-bold text-dark text-truncate" style="font-size: 13.5px;"><?= htmlspecialchars($studentName) ?></div>
                                    <div class="badge bg-light text-secondary border mt-1 font-monospace" style="font-size: 11px;">
                                        <i class="fa-solid fa-id-badge text-warning me-1"></i><?= htmlspecialchars($studentCustomId) ?>
                                    </div>
                                </li>
                                <li>
                                    <a class="dropdown-item mt-1" href="student-profile.php">
                                        <i class="fa-solid fa-address-card text-primary me-2"></i>My Profile & ID Slip
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="form-submission.php">
                                        <i class="fa-solid fa-file-signature text-warning me-2"></i>Admission
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="seat-reservation.php">
                                        <i class="fa-solid fa-chair text-success me-2"></i>Reserve Seat
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="student-logout.php">
                                        <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="student-login.php" class="header-btn header-btn-outline d-none d-sm-inline-flex" title="Student Portal Login">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i>
                            <span>Login</span>
                        </a>

                        <a href="register.php" class="header-btn header-btn-primary buy-btn">
                            <i class="fa-solid fa-user-pen"></i>
                            <span>Registration</span>
                        </a>
                    <?php endif; ?>

                </div>

            </div>

        </div>

    </nav>
<?php
require_once __DIR__ . '/db.php';

// Fetch Categories (only active with courses count)
$cat_stmt = $pdo->query("SELECT c.*, COUNT(cr.id) as course_count 
                         FROM course_categories c 
                         JOIN courses cr ON cr.category_id = c.id 
                         WHERE c.status = 'active' AND cr.status = 'active' 
                         GROUP BY c.id 
                         HAVING course_count > 0 
                         ORDER BY c.name ASC");
$all_categories = $cat_stmt->fetchAll();

// Category filter
$cat_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$c_query = "SELECT cr.*, cat.name as category_name, cat.icon as category_icon 
            FROM courses cr 
            JOIN course_categories cat ON cr.category_id = cat.id 
            WHERE cr.status = 'active'";
$params = [];
if ($cat_filter > 0) {
    $c_query .= " AND cr.category_id = ?";
    $params[] = $cat_filter;
}
$c_query .= " ORDER BY cr.id ASC";
$stmt = $pdo->prepare($c_query);
$stmt->execute($params);
$courses_list = $stmt->fetchAll();

// Total count
$total_all = $pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'active'")->fetchColumn();

$page_title = "All Courses - Finchskills Institute";
include "header.php";
?>

<!-- ===================================== -->
<!-- COURSES HERO SECTION (CLEAN & FLAT) -->
<!-- ===================================== -->

<section class="educrsv2-hero-section">
    <div class="container">
        <div class="row align-items-center gy-4">

            <!-- LEFT CONTENT -->
            <div class="col-md-6">
                <div class="educrsv2-content-wrap">

                    <!-- BADGE -->
                    <div class="educrsv2-top-badge">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>Learn From Industry Experts</span>
                    </div>

                    <!-- HEADING -->
                    <h1>
                        Upgrade Your Skills With Modern Career Focused Courses
                    </h1>

                    <!-- DESCRIPTION -->
                    <p>
                        Join practical learning programs designed for real-world careers with live training, mentorship, labs, and placement-focused guidance.
                    </p>

                    <!-- FEATURES -->
                    <div class="educrsv2-feature-wrap">
                        <div class="educrsv2-feature-item">
                            <div class="educrsv2-feature-icon">
                                <i class="fa-solid fa-laptop-code"></i>
                            </div>
                            <div>
                                <h6>Online &amp; Offline</h6>
                                <span>Practical Training</span>
                            </div>
                        </div>

                        <div class="educrsv2-feature-item">
                            <div class="educrsv2-feature-icon">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                            <div>
                                <h6>Expert Mentors</h6>
                                <span>Industry Trainers</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- RIGHT IMAGE -->
            <div class="col-md-6">
                <div class="educrsv2-image-wrapper">
                    <img src="img/course-hero.jpeg" alt="Courses">

                    <div class="course-responsive-box">
                        <!-- CARD 1 -->
                        <div class="educrsv2-floating-card educrsv2-card-one">
                            <h3><?= count($courses_list) ?>+</h3>
                            <p>Active Programs</p>
                        </div>

                        <!-- CARD 2 -->
                        <div class="educrsv2-floating-card educrsv2-card-two">
                            <i class="fa-solid fa-briefcase"></i>
                            <div>
                                <h5>Career Support</h5>
                                <span>100% Placement Help</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ================= COURSES LISTING SECTION ================= -->
<section class="cd-main-wrap" style="padding: 30px 0 60px;">
    <div class="container">

        <!-- CATEGORY FILTER PILLS -->
        <div class="d-flex flex-wrap align-items-center gap-2 mb-4 pb-2 border-bottom">
            <span class="small text-muted fw-semibold me-1"><i class="fa-solid fa-filter me-1"></i>Filter by:</span>
            
            <a href="courses.php" class="btn btn-sm <?= $cat_filter === 0 ? 'btn-dark' : 'btn-outline-secondary' ?>" style="border-radius: 4px; font-size: 12.5px; font-weight: 600; padding: 5px 12px;">
                All Programs (<?= $total_all ?>)
            </a>

            <?php foreach ($all_categories as $c_cat): ?>
                <a href="courses.php?category=<?= $c_cat['id'] ?>" class="btn btn-sm <?= $cat_filter === (int)$c_cat['id'] ? 'btn-dark' : 'btn-outline-secondary' ?>" style="border-radius: 4px; font-size: 12.5px; font-weight: 500; padding: 5px 12px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid <?= htmlspecialchars($c_cat['icon']) ?> <?= $cat_filter === (int)$c_cat['id'] ? 'text-warning' : 'text-primary' ?>"></i>
                    <span><?= htmlspecialchars($c_cat['name']) ?> (<?= $c_cat['course_count'] ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- COURSES GRID -->
        <?php if (!empty($courses_list)): ?>
            <div class="row g-3 g-lg-4">
                <?php foreach ($courses_list as $c): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="course-card" style="border-radius: 6px; box-shadow: none !important; background: #ffffff;">
                            <div class="course-image" style="height: 190px;">
                                <?php if (!empty($c['image'])): ?>
                                    <img src="<?= htmlspecialchars($c['image']) ?>" alt="<?= htmlspecialchars($c['title']) ?>">
                                <?php else: ?>
                                    <div class="w-100 h-100 bg-dark d-flex align-items-center justify-content-center text-white">
                                        <i class="fa-solid <?= htmlspecialchars($c['category_icon']) ?> fa-2x text-warning"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="course-cat-badge" style="border-radius: 4px; font-size: 11px; padding: 3px 8px;">
                                    <i class="fa-solid <?= htmlspecialchars($c['category_icon']) ?>"></i> <?= htmlspecialchars($c['category_name']) ?>
                                </span>
                                <span class="course-duration-badge" style="border-radius: 4px; font-size: 11px; padding: 3px 8px;">
                                    <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($c['duration']) ?>
                                </span>
                            </div>

                            <div class="course-content" style="padding: 16px;">
                                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 6px; line-height: 1.35;"><?= htmlspecialchars($c['title']) ?></h3>
                                <p style="font-size: 12.5px; color: #64748b; line-height: 1.55; margin-bottom: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($c['short_desc']) ?></p>

                                <div class="course-meta mb-3" style="padding: 8px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 6px 8px; font-size: 12px;">
                                    <?php if (!empty($c['fee'])): ?>
                                       
                                    <?php endif; ?>
                                    <span class="text-dark"><i class="fa-regular fa-clock text-muted"></i> <?= htmlspecialchars($c['duration']) ?></span>
                                    <span class="badge <?= (stripos($c['study_mode'], 'online') !== false) ? 'bg-success text-white' : 'bg-primary text-white' ?>" style="font-weight: 600; font-size: 11px; padding: 3px 7px; border-radius: 3px;">
                                        <i class="fa-solid <?= (stripos($c['study_mode'], 'online') !== false) ? 'fa-globe' : 'fa-building-columns' ?> me-1"></i><?= htmlspecialchars($c['study_mode']) ?>
                                    </span>
                                    <?php if (!empty($c['modules'])): ?>
                                        <span class="text-secondary fw-semibold" style="font-size: 11px;"><i class="fa-solid fa-book-open text-warning me-1"></i><?= htmlspecialchars($c['modules']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="course-card-footer" style="padding-top: 10px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                    <?= renderEnrollBtn($c['title'], 'btn-sm') ?>
                                    <a href="course-detail.php?slug=<?= urlencode($c['slug']) ?>" class="course-details-link" style="font-size: 12.5px; font-weight: 600; color: #0f172a; text-decoration: none;">
                                        <span>Details</span>
                                        <i class="fa-solid fa-chevron-right small ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 bg-white border rounded-2" style="border-color: #e2e8f0 !important;">
                <i class="fa-solid fa-graduation-cap text-muted opacity-25 fa-3x mb-3 d-block"></i>
                <h5 class="fw-bold text-dark mb-1">No courses found in this category</h5>
                <p class="text-muted small mb-3">Please check back soon or browse all courses.</p>
                <a href="courses.php" class="cd-btn-primary d-inline-flex" style="width: auto; padding: 8px 20px;">View All Courses</a>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- FOOTER -->
<?php include "footer.php"; ?>
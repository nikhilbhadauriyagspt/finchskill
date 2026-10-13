<?php
require_once __DIR__ . '/db.php';

$slug = trim($_GET['slug'] ?? 'terms-conditions');

$stmt = $pdo->prepare("SELECT * FROM site_pages WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page || ($page['status'] !== 'active' && empty($_SESSION['admin_logged_in']))) {
    http_response_code(404);
    $page_title = "Page Not Found - Finchskills Institute";
    include "header.php";
    ?>
    <div class="container py-5 my-5 text-center">
        <div class="py-5">
            <i class="fa-solid fa-file-circle-question text-muted mb-3" style="font-size: 60px; color: #cbd5e1 !important;"></i>
            <h2 class="fw-bold text-dark mb-2">Page Not Found</h2>
            <p class="text-muted small mb-4">The policy or page you are looking for does not exist or has been temporarily unpublished.</p>
            <a href="index.php" class="btn btn-warning px-4 py-2 text-white fw-bold" style="background:#fe7c03; border:none; border-radius:4px;">
                <i class="fa-solid fa-house me-1"></i> Back to Homepage
            </a>
        </div>
    </div>
    <?php
    include "footer.php";
    exit;
}

// Fetch all active policy pages for quick sidebar navigation
$all_pages_stmt = $pdo->query("SELECT slug, title, is_system FROM site_pages WHERE status = 'active' ORDER BY is_system DESC, id ASC");
$sidebar_pages = $all_pages_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = !empty($page['meta_title']) ? $page['meta_title'] : $page['title'] . " - Finchskills Institute";
$meta_desc = !empty($page['meta_description']) ? $page['meta_description'] : ($page['subtitle'] ?? 'Official document of Finchskills Institute.');

include "header.php";
?>

<style>
/* ==========================================================================
   CLEAN, SHADOWLESS POLICY & CUSTOM PAGE STYLES (PLUS JAKARTA SANS)
   ========================================================================== */
.policy-hero-banner {
    background: #0e1e2e;
    padding: 50px 0 45px;
    border-bottom: 3px solid #fe7c03;
    position: relative;
}

.policy-breadcrumbs {
    font-size: 12px;
    font-weight: 600;
    color: #94a3b8;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.policy-breadcrumbs a {
    color: #cbd5e1;
    text-decoration: none;
    transition: color 0.2s;
}

.policy-breadcrumbs a:hover {
    color: #fe7c03;
}

.policy-hero-title {
    font-size: 32px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.5px;
    margin: 0 0 8px;
    line-height: 1.2;
}

.policy-hero-subtitle {
    font-size: 14px;
    color: #cbd5e1;
    margin: 0;
    max-width: 650px;
    line-height: 1.6;
}

.policy-body-section {
    background: #f8fafc;
    padding: 45px 0 80px;
    min-height: 60vh;
}

/* MAIN CONTENT CONTAINER */
.policy-content-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 36px 40px;
    box-shadow: none !important;
}

@media (max-width: 768px) {
    .policy-content-card {
        padding: 22px 18px;
    }
    .policy-hero-title {
        font-size: 24px;
    }
}

/* RICH TEXT TYPOGRAPHY */
.rich-policy-content {
    font-size: 14.5px;
    line-height: 1.8;
    color: #334155;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.rich-policy-content h1,
.rich-policy-content h2,
.rich-policy-content h3,
.rich-policy-content h4 {
    color: #0f172a;
    font-weight: 700;
    margin-top: 28px;
    margin-bottom: 12px;
    letter-spacing: -0.3px;
}

.rich-policy-content h1 { font-size: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
.rich-policy-content h2 { font-size: 19px; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; }
.rich-policy-content h3 { font-size: 16.5px; }
.rich-policy-content h4 { font-size: 15px; }

.rich-policy-content p {
    margin-bottom: 16px;
}

.rich-policy-content ul,
.rich-policy-content ol {
    margin: 12px 0 20px 24px;
    padding: 0;
}

.rich-policy-content li {
    margin-bottom: 8px;
    line-height: 1.7;
}

.rich-policy-content blockquote {
    border-left: 3px solid #fe7c03;
    background: #fff8f3;
    padding: 14px 18px;
    margin: 20px 0;
    border-radius: 0 6px 6px 0;
    font-style: italic;
    color: #475569;
}

.rich-policy-content a {
    color: #fe7c03;
    text-decoration: underline;
    font-weight: 600;
}

.rich-policy-content a:hover {
    color: #c95e00;
}

.rich-policy-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.rich-policy-content table th,
.rich-policy-content table td {
    border: 1px solid #e2e8f0;
    padding: 10px 14px;
    font-size: 13.5px;
}

.rich-policy-content table th {
    background: #f8fafc;
    font-weight: 700;
    color: #0f172a;
}

/* SIDEBAR WIDGETS */
.policy-sidebar-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    box-shadow: none !important;
    margin-bottom: 20px;
}

.sidebar-title {
    font-size: 13px;
    font-weight: 700;
    color: #0e1e2e;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sidebar-nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-nav-list li {
    margin-bottom: 6px;
}

.sidebar-nav-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    text-decoration: none !important;
    border: 1px solid transparent;
    transition: all 0.2s;
}

.sidebar-nav-link:hover {
    color: #fe7c03;
    background: #fff8f3;
    border-color: #ffedd5;
}

.sidebar-nav-link.active {
    background: #fe7c03;
    color: #ffffff !important;
    font-weight: 700;
}

.help-box {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 6px;
    padding: 16px;
    text-align: center;
}
</style>

<!-- ================= HERO BANNER ================= -->
<div class="policy-hero-banner">
    <div class="container">
        <div class="policy-breadcrumbs">
            <a href="index.php">Home</a>
            <i class="fa-solid fa-angle-right" style="font-size:9px;"></i>
            <span>Information &amp; Policies</span>
            <i class="fa-solid fa-angle-right" style="font-size:9px;"></i>
            <span style="color:#fe7c03;"><?= htmlspecialchars($page['title']) ?></span>
        </div>
        <h1 class="policy-hero-title"><?= htmlspecialchars($page['title']) ?></h1>
        <?php if (!empty($page['subtitle'])): ?>
            <p class="policy-hero-subtitle"><?= htmlspecialchars($page['subtitle']) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- ================= MAIN BODY ================= -->
<section class="policy-body-section">
    <div class="container">
        <div class="row g-4">
            
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <div class="policy-content-card">
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                        <span class="text-muted small">
                            <i class="fa-regular fa-clock me-1 text-warning" style="color:#fe7c03 !important;"></i> Last Updated: <strong><?= date('d M Y', strtotime($page['updated_at'] ?? $page['created_at'])) ?></strong>
                        </span>
                        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-1" style="border-radius:4px; font-size:11.5px;">
                            <i class="fa-solid fa-print"></i> Print Document
                        </button>
                    </div>

                    <!-- Render Rich Content -->
                    <div class="rich-policy-content">
                        <?= $page['content'] ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar Navigation -->
            <div class="col-lg-4">
                
                <!-- Other Policy Pages List -->
                <div class="policy-sidebar-card">
                    <h4 class="sidebar-title">
                        <i class="fa-solid fa-shield-halved text-[#fe7c03]" style="color:#fe7c03;"></i>
                        <span>Institute Policies</span>
                    </h4>
                    <ul class="sidebar-nav-list">
                        <?php foreach ($sidebar_pages as $sp): ?>
                            <?php $is_curr = ($sp['slug'] === $page['slug']); ?>
                            <li>
                                <a href="page.php?slug=<?= urlencode($sp['slug']) ?>" class="sidebar-nav-link <?= $is_curr ? 'active' : '' ?>">
                                    <span><?= htmlspecialchars($sp['title']) ?></span>
                                    <i class="fa-solid fa-chevron-right" style="font-size:10px; opacity: <?= $is_curr ? '1' : '0.5' ?>;"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Support & Help Widget -->
                <div class="policy-sidebar-card">
                    <h4 class="sidebar-title">
                        <i class="fa-solid fa-headset text-[#fe7c03]" style="color:#fe7c03;"></i>
                        <span>Need Clarification?</span>
                    </h4>
                    <div class="help-box">
                        <p class="small text-slate-600 mb-3" style="font-size:13px; color:#475569;">
                            If you have questions regarding our terms, admission procedures, or policies, our helpdesk is here to assist.
                        </p>
                        <?php $p_phone = !empty($site_phone_1) ? $site_phone_1 : getSetting('phone_1', '+919650386711'); ?>
                        <?php $p_email = !empty($site_email_1) ? $site_email_1 : getSetting('email_1', 'hello@finchskills.com'); ?>
                        <a href="tel:<?= htmlspecialchars($p_phone) ?>" class="d-block fw-bold text-dark mb-1 text-decoration-none" style="font-size:14px;">
                            <i class="fa-solid fa-phone text-warning me-1.5" style="color:#fe7c03 !important;"></i> <?= htmlspecialchars($p_phone) ?>
                        </a>
                        <a href="mailto:<?= htmlspecialchars($p_email) ?>" class="small text-muted text-decoration-none d-block">
                            <i class="fa-regular fa-envelope me-1"></i> <?= htmlspecialchars($p_email) ?>
                        </a>
                    </div>
                    <div class="mt-3">
                        <a href="form-submission.php" class="btn w-100 text-white fw-bold py-2" style="background:#0e1e2e; border:none; border-radius:4px; font-size:13px;">
                            Apply for Admission &rarr;
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</section>

<?php include "footer.php"; ?>

<?php
if (!isset($pdo)) {
    @require_once __DIR__ . '/db.php';
}

$footer_courses = [];
if (isset($pdo)) {
    try {
        $fc_stmt = $pdo->query("SELECT id, title FROM courses WHERE status = 'active' ORDER BY id ASC LIMIT 5");
        $footer_courses = $fc_stmt ? $fc_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Exception $e) {}
}
?>

<!-- ================= FOOTER ================= -->
<footer class="footer-section">
    <div class="container">

        <!-- ================= MAIN FOOTER ================= -->
        <div class="footer-main">
            <div class="row g-4">

                <!-- LOGO + ABOUT -->
                <div class="col-lg-4 col-md-6">
                    <div class="footer-logo">
                        <a href="index.php">
                            <img src="<?= htmlspecialchars(getSetting('footer_logo', getSetting('site_logo', 'img/footer-logo.png'))) ?>" alt="Finchskills Logo">
                        </a>
                    </div>
                    <p class="footer-about-text">
                        Finchskills Institute is a leading career training academy offering industry-aligned professional programs across Aviation, Airport Handling, Hospitality, Travel, and Cruise Management with 100% placement support.
                    </p>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-3 col-6 footer-column">
                    <h3 class="footer-heading">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="courses.php">All Courses</a></li>
                        <li><a href="placement.php">Placements</a></li>
                        <li><a href="seat-reservation.php">Reserve Seat</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="privacy-policy.php">Privacy Policy</a></li>
                        <li><a href="terms-conditions.php">Terms &amp; Conditions</a></li>
                        <li><a href="refund-policy.php">Refund Policy</a></li>
                    </ul>
                </div>

                <!-- Career Courses -->
                <div class="col-lg-3 col-md-3 col-6 footer-column">
                    <h3 class="footer-heading">Career Courses</h3>
                    <ul class="footer-links">
                        <?php if (!empty($footer_courses)): ?>
                            <?php foreach ($footer_courses as $fc): ?>
                                <li><a href="course-detail.php?id=<?= (int)$fc['id'] ?>"><?= htmlspecialchars($fc['title']) ?></a></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><a href="courses.php">Airport Management</a></li>
                            <li><a href="courses.php">Air Hostess &amp; Cabin Crew</a></li>
                            <li><a href="courses.php">Travel &amp; Tourism</a></li>
                            <li><a href="courses.php">Hospitality &amp; Guest Relations</a></li>
                            <li><a href="courses.php">Cruise Management</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-3 col-md-12 footer-column">
                    <h3 class="footer-heading">Contact Info</h3>
                    <ul class="footer-contact">
                        <?php if (!empty($site_phone_1)): ?>
                        <li>
                            <i class="fa-solid fa-phone"></i>
                            <a href="tel:<?= htmlspecialchars($site_phone_1) ?>">
                                <?= htmlspecialchars($site_phone_1) ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($site_phone_2)): ?>
                        <li>
                            <i class="fa-solid fa-phone"></i>
                            <a href="tel:<?= htmlspecialchars($site_phone_2) ?>">
                                <?= htmlspecialchars($site_phone_2) ?>
                            </a>
                        </li>
                        <?php endif; ?>

                        <li>
                            <i class="fa-solid fa-location-dot"></i>
                            <span><?= htmlspecialchars(getSetting('address', 'Orbit Plaza, Crossing Republik, NH-24, Ghaziabad, Uttar Pradesh, 201016')) ?></span>
                        </li>

                        <?php if (!empty($site_email_1)): ?>
                        <li>
                            <i class="fa-regular fa-envelope"></i>
                            <a href="mailto:<?= htmlspecialchars($site_email_1) ?>">
                                <?= htmlspecialchars($site_email_1) ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($site_email_2)): ?>
                        <li>
                            <i class="fa-regular fa-envelope"></i>
                            <a href="mailto:<?= htmlspecialchars($site_email_2) ?>">
                                <?= htmlspecialchars($site_email_2) ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

            </div>
        </div>

        <!-- ================= BOTTOM ================= -->
        <div class="footer-bottom">
            <div class="row align-items-center g-3">
                <div class="col-md-6 text-center text-md-start">
                    <p class="copyright-text mb-0">
                        &copy; <?= date('Y') ?> Finchskills Institute. All Rights Reserved.
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="footer-social mt-0 justify-content-center justify-content-md-end">
                        <?php if ($fb = getSetting('facebook_url')): ?>
                        <a href="<?= htmlspecialchars($fb) ?>" target="_blank" rel="noopener noreferrer" title="Facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <?php endif; ?>

                        <?php if ($tw = getSetting('twitter_url')): ?>
                        <a href="<?= htmlspecialchars($tw) ?>" target="_blank" rel="noopener noreferrer" title="Twitter / X">
                            <i class="fa-brands fa-x-twitter"></i>
                        </a>
                        <?php endif; ?>

                        <?php if ($insta = getSetting('instagram_url')): ?>
                        <a href="<?= htmlspecialchars($insta) ?>" target="_blank" rel="noopener noreferrer" title="Instagram">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <?php endif; ?>

                        <?php if ($li = getSetting('linkedin_url')): ?>
                        <a href="<?= htmlspecialchars($li) ?>" target="_blank" rel="noopener noreferrer" title="LinkedIn">
                            <i class="fa-brands fa-linkedin-in"></i>
                        </a>
                        <?php endif; ?>

                        <?php if ($yt = getSetting('youtube_url')): ?>
                        <a href="<?= htmlspecialchars($yt) ?>" target="_blank" rel="noopener noreferrer" title="YouTube">
                            <i class="fa-brands fa-youtube"></i>
                        </a>
                        <?php endif; ?>

                        <?php if ($wa = getSetting('whatsapp_number')): ?>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $wa) ?>" target="_blank" rel="noopener noreferrer" title="WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</footer>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (required for Owl Carousel) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<!-- Owl Carousel JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

</body>
</html>
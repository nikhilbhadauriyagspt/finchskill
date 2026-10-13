<?php
$page_title = "General Settings";
require_once __DIR__ . '/includes/header.php';

$msg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings_keys = [
        'phone_1', 'phone_2', 'email_1', 'email_2', 
        'address', 'timing_mon_fri', 'timing_sat',
        'facebook_url', 'instagram_url', 'linkedin_url', 
        'twitter_url', 'youtube_url', 'whatsapp_number',
        'map_iframe'
    ];

    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    
    foreach ($settings_keys as $key) {
        $val = trim($_POST[$key] ?? '');
        $stmt->execute([$key, $val, $val]);
    }

    // Handle Logo & Favicon Uploads
    $upload_dir = __DIR__ . '/../uploads/site/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Site Main Logo
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
        $ext = pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
        $file_name = 'logo_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $upload_dir . $file_name)) {
            $logo_path = 'uploads/site/' . $file_name;
            $stmt->execute(['site_logo', $logo_path, $logo_path]);
        }
    }

    // Site Footer Logo
    if (isset($_FILES['footer_logo']) && $_FILES['footer_logo']['error'] == 0) {
        $ext = pathinfo($_FILES['footer_logo']['name'], PATHINFO_EXTENSION);
        $file_name = 'footer_logo_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['footer_logo']['tmp_name'], $upload_dir . $file_name)) {
            $footer_logo_path = 'uploads/site/' . $file_name;
            $stmt->execute(['footer_logo', $footer_logo_path, $footer_logo_path]);
        }
    }

    // Site Favicon
    if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] == 0) {
        $ext = pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION);
        $file_name = 'favicon_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $upload_dir . $file_name)) {
            $favicon_path = 'uploads/site/' . $file_name;
            $stmt->execute(['site_favicon', $favicon_path, $favicon_path]);
        }
    }

    $msg = 'Settings and brand assets updated successfully!';
}

// Reload fresh settings
$settings = getSiteSettings($pdo);
?>

<div class="space-y-4">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-md border border-slate-200">
        <div>
            <h2 class="text-base font-bold text-[#0e1e2e]">Website Brand &amp; Contact Settings</h2>
            <p class="text-xs text-slate-400 mt-0.5">Manage portal logos, contact phone numbers, support emails, location, and social media handles</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="smtp-settings.php" class="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:text-[#fe7c03] rounded-md transition-colors flex items-center gap-1 no-underline">
                <i class="fa-solid fa-paper-plane text-slate-400"></i>
                <span>Email &amp; SMTP</span>
            </a>
        </div>
    </div>

    <!-- ALERTS -->
    <?php if (!empty($msg)): ?>
        <div class="p-3 text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <form method="POST" action="settings.php" enctype="multipart/form-data" class="space-y-4">
        
        <!-- LOGOS & BRANDING CARD -->
        <div class="bg-white border border-slate-200 rounded-md p-4 space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                <span class="w-6 text-center text-[#fe7c03]"><i class="fa-solid fa-image text-sm"></i></span>
                <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Logos &amp; Favicon</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <!-- Header Logo -->
                <div class="space-y-2">
                    <label class="font-bold text-slate-700 block">Header Main Logo</label>
                    <div class="p-3 border border-slate-200 rounded-md bg-slate-50 flex items-center justify-center h-24">
                        <img src="../<?= htmlspecialchars($settings['site_logo'] ?? 'img/logo.png') ?>" alt="Header Logo" class="max-h-12 max-w-full object-contain">
                    </div>
                    <input type="file" name="site_logo" accept="image/*" class="w-full text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    <span class="text-[10px] text-slate-400 block">Recommended: Transparent PNG</span>
                </div>

                <!-- Footer Logo -->
                <div class="space-y-2">
                    <label class="font-bold text-slate-700 block">Footer Logo</label>
                    <div class="p-3 border border-slate-200 rounded-md bg-[#0e1e2e] flex items-center justify-center h-24">
                        <img src="../<?= htmlspecialchars($settings['footer_logo'] ?? $settings['site_logo'] ?? 'img/footer-logo.png') ?>" alt="Footer Logo" class="max-h-12 max-w-full object-contain">
                    </div>
                    <input type="file" name="footer_logo" accept="image/*" class="w-full text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    <span class="text-[10px] text-slate-400 block">Recommended: Light version on dark</span>
                </div>

                <!-- Favicon -->
                <div class="space-y-2">
                    <label class="font-bold text-slate-700 block">Browser Favicon</label>
                    <div class="p-3 border border-slate-200 rounded-md bg-slate-50 flex items-center justify-center h-24">
                        <img src="../<?= htmlspecialchars($settings['site_favicon'] ?? 'img/favicon.png') ?>" alt="Favicon" class="w-8 h-8 object-contain">
                    </div>
                    <input type="file" name="site_favicon" accept="image/*" class="w-full text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    <span class="text-[10px] text-slate-400 block">Recommended: 32x32 PNG/ICO</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            
            <!-- CONTACT NUMBERS & EMAILS -->
            <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
                <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                    <span class="w-6 text-center text-sky-600"><i class="fa-solid fa-phone text-sm"></i></span>
                    <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Contact Phone &amp; Email</h3>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Primary Phone Number</label>
                        <input type="text" name="phone_1" value="<?= htmlspecialchars($settings['phone_1'] ?? '') ?>" placeholder="+91 9650386711" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Secondary Phone Number</label>
                        <input type="text" name="phone_2" value="<?= htmlspecialchars($settings['phone_2'] ?? '') ?>" placeholder="+91 9876543210" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Support Email 1</label>
                        <input type="email" name="email_1" value="<?= htmlspecialchars($settings['email_1'] ?? '') ?>" placeholder="hello@yourgoodguide.com" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Admission Email 2</label>
                        <input type="email" name="email_2" value="<?= htmlspecialchars($settings['email_2'] ?? '') ?>" placeholder="admission@yourgoodguide.com" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                    </div>
                </div>
            </div>

            <!-- ADDRESS & TIMINGS -->
            <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
                <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                    <span class="w-6 text-center text-rose-500"><i class="fa-solid fa-map-location-dot text-sm"></i></span>
                    <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Location &amp; Office Timings</h3>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Full Institute Address</label>
                        <textarea name="address" rows="3" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]" placeholder="Enter physical street address..."><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Mon - Fri Working Hours</label>
                        <input type="text" name="timing_mon_fri" value="<?= htmlspecialchars($settings['timing_mon_fri'] ?? '') ?>" placeholder="Monday - Friday: 10:00 AM - 05:00 PM" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Saturday Working Hours</label>
                        <input type="text" name="timing_sat" value="<?= htmlspecialchars($settings['timing_sat'] ?? '') ?>" placeholder="Saturday: 10:00 AM - 02:00 PM" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                    </div>
                </div>
            </div>

            <!-- SOCIAL MEDIA & WHATSAPP -->
            <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
                <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                    <span class="w-6 text-center text-emerald-600"><i class="fa-solid fa-share-nodes text-sm"></i></span>
                    <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Social Media Handles</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <div class="sm:col-span-2">
                        <label class="font-bold text-slate-700 block mb-1">WhatsApp Contact Number</label>
                        <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($settings['whatsapp_number'] ?? '') ?>" placeholder="919650386711" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Facebook URL</label>
                        <input type="url" name="facebook_url" value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/..." class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Instagram URL</label>
                        <input type="url" name="instagram_url" value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/..." class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">LinkedIn URL</label>
                        <input type="url" name="linkedin_url" value="<?= htmlspecialchars($settings['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/..." class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">YouTube URL</label>
                        <input type="url" name="youtube_url" value="<?= htmlspecialchars($settings['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/..." class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="font-bold text-slate-700 block mb-1">Twitter / X URL</label>
                        <input type="url" name="twitter_url" value="<?= htmlspecialchars($settings['twitter_url'] ?? '') ?>" placeholder="https://twitter.com/..." class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                    </div>
                </div>
            </div>

            <!-- GOOGLE MAP -->
            <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
                <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                    <span class="w-6 text-center text-amber-500"><i class="fa-solid fa-map text-sm"></i></span>
                    <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Google Map Embed</h3>
                </div>

                <div class="space-y-2 text-xs">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Google Maps Embed URL (src attribute)</label>
                        <textarea name="map_iframe" rows="3" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]" placeholder="https://www.google.com/maps/embed?..."><?= htmlspecialchars($settings['map_iframe'] ?? '') ?></textarea>
                    </div>

                    <?php if (!empty($settings['map_iframe'])): ?>
                        <div>
                            <span class="text-[10px] text-slate-400 block uppercase font-bold mb-1">Map Live Preview</span>
                            <div class="h-32 border border-slate-200 rounded overflow-hidden">
                                <iframe src="<?= htmlspecialchars($settings['map_iframe']) ?>" width="100%" height="100%" style="border:0;" loading="lazy"></iframe>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- SAVE BUTTON -->
        <div class="text-right pt-2">
            <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors inline-flex items-center gap-1">
                <i class="fa-solid fa-floppy-disk text-[11px]"></i>
                <span>Save All Site Settings</span>
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

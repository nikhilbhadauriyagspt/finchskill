<?php
$page_title = "Email & SMTP";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../mail-helper.php';

$msg = '';
$err = '';
$test_result = null;

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Save Settings
    if (isset($_POST['action_save_smtp'])) {
        $keys = [
            'smtp_enabled',
            'smtp_host',
            'smtp_port',
            'smtp_secure',
            'smtp_username',
            'smtp_password',
            'smtp_from_name',
            'smtp_from_email',
            'smtp_reply_to'
        ];

        try {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            foreach ($keys as $k) {
                $val = trim($_POST[$k] ?? '');
                if ($k === 'smtp_enabled' && !isset($_POST['smtp_enabled'])) {
                    $val = '0';
                }
                $stmt->execute([$k, $val, $val]);
            }
            $msg = "SMTP & Email configuration saved successfully!";
        } catch (Exception $e) {
            $err = "Failed to save settings: " . $e->getMessage();
        }
    }

    // 2. Test Connection & Send Live Test Email
    if (isset($_POST['action_test_smtp'])) {
        $test_email = trim($_POST['test_email'] ?? '');
        if (empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            $err = "Please enter a valid email address to send the test message.";
        } else {
            $test_subject = "SMTP Test Verification - YourGoodGuide";
            $test_html = '
            <!DOCTYPE html>
            <html>
            <head><meta charset="utf-8"><style>body{font-family:Arial,sans-serif;background:#f8fafc;padding:20px;color:#334155;}.card{max-width:500px;margin:0 auto;background:#fff;border-radius:6px;border:1px solid #e2e8f0;padding:25px;}</style></head>
            <body>
                <div class="card">
                    <h3 style="color:#0e1e2e;margin-top:0;">SMTP Configuration Test</h3>
                    <p>This is a live test email sent from the Admin Portal.</p>
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;padding:12px;border-radius:4px;margin:15px 0;">
                        <span style="color:#166534;font-weight:bold;">SUCCESS!</span>
                        <p style="margin:5px 0 0;font-size:13px;color:#166534;">Your SMTP server, credentials, and port are working properly!</p>
                    </div>
                    <p style="font-size:11px;color:#64748b;">Timestamp: ' . date('d M Y, h:i:s A') . '</p>
                </div>
            </body>
            </html>';

            $res = sendFinchMail($test_email, $test_subject, $test_html, ['throw_error' => true]);
            if ($res['success']) {
                $test_result = [
                    'status'  => 'success',
                    'message' => "Success! Test email was successfully delivered to <strong>" . htmlspecialchars($test_email) . "</strong>."
                ];
            } else {
                $test_result = [
                    'status'  => 'danger',
                    'message' => "SMTP Error: " . htmlspecialchars($res['message'])
                ];
            }
        }
    }
}

// Fetch current SMTP Settings
$cfg = getSmtpConfig();
$enabled    = ($cfg['smtp_enabled'] ?? '1') === '1';
$host       = $cfg['smtp_host'] ?? 'smtp.gmail.com';
$port       = $cfg['smtp_port'] ?? '587';
$secure     = strtolower($cfg['smtp_secure'] ?? 'tls');
$username   = $cfg['smtp_username'] ?? '';
$password   = $cfg['smtp_password'] ?? '';
$from_name  = $cfg['smtp_from_name'] ?? 'Finchskills Institute';
$from_email = $cfg['smtp_from_email'] ?? '';
$reply_to   = $cfg['smtp_reply_to'] ?? '';
?>

<div class="space-y-4">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-md border border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-[#0e1e2e]">Email &amp; SMTP Configuration</h2>
                <span class="px-2 py-0.5 text-[11px] font-semibold rounded <?= $enabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                    <i class="fa-solid <?= $enabled ? 'fa-circle-check text-emerald-500' : 'fa-circle-pause' ?> text-[10px] mr-1"></i>
                    Mailer: <?= $enabled ? 'Active (PHPMailer)' : 'Disabled' ?>
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Manage SMTP credentials used for sending candidate OTPs, Student IDs, and Payment receipts</p>
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

    <?php if (!empty($err)): ?>
        <div class="p-3 text-xs bg-rose-50 text-rose-700 border border-rose-200 rounded-md flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                <span><?= htmlspecialchars($err) ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($test_result): ?>
        <div class="p-3 text-xs rounded-md border flex items-center justify-between <?= $test_result['status'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?>">
            <div class="flex items-center gap-2">
                <i class="fa-solid <?= $test_result['status'] === 'success' ? 'fa-circle-check text-emerald-500' : 'fa-triangle-exclamation text-rose-500' ?>"></i>
                <div><?= $test_result['message'] ?></div>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        
        <!-- Main Form (2 Cols) -->
        <div class="lg:col-span-2">
            <form method="POST" action="smtp-settings.php">
                <input type="hidden" name="action_save_smtp" value="1">

                <div class="bg-white border border-slate-200 rounded-md p-4 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-6 text-center text-[#fe7c03]"><i class="fa-solid fa-server text-sm"></i></span>
                            <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">SMTP Server Credentials</h3>
                        </div>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="smtp_enabled" value="1" <?= $enabled ? 'checked' : '' ?> class="rounded border-slate-300 text-[#fe7c03] focus:ring-[#fe7c03]">
                            <span class="text-xs font-semibold text-slate-700">Enable SMTP</span>
                        </label>
                    </div>

                    <!-- Quick Presets -->
                    <div class="p-2 bg-slate-50 border border-slate-200 rounded-md">
                        <span class="text-[10px] uppercase font-bold text-slate-500 block mb-1.5">
                            <i class="fa-solid fa-wand-magic-sparkles text-[#fe7c03] mr-1"></i> Auto-Fill Server Presets:
                        </span>
                        <div class="flex flex-wrap gap-1">
                            <button type="button" class="px-2 py-1 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:text-[#fe7c03] rounded transition-colors" onclick="applyPreset('gmail')">
                                <i class="fa-brands fa-google text-rose-500 mr-1"></i> Gmail
                            </button>
                            <button type="button" class="px-2 py-1 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:text-[#fe7c03] rounded transition-colors" onclick="applyPreset('hostinger')">
                                <i class="fa-solid fa-cloud text-sky-500 mr-1"></i> Hostinger Webmail
                            </button>
                            <button type="button" class="px-2 py-1 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:text-[#fe7c03] rounded transition-colors" onclick="applyPreset('cpanel')">
                                <i class="fa-solid fa-globe text-emerald-500 mr-1"></i> cPanel / Custom
                            </button>
                        </div>
                    </div>

                    <!-- Fields Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="sm:col-span-2">
                            <label class="font-bold text-slate-700 block mb-1">SMTP Host / Server <span class="text-rose-500">*</span></label>
                            <input type="text" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($host) ?>" placeholder="e.g. smtp.gmail.com or mail.yourdomain.com" required class="w-full font-mono text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Port <span class="text-rose-500">*</span></label>
                            <input type="number" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($port) ?>" placeholder="587 / 465" required class="w-full font-mono text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Security / Encryption <span class="text-rose-500">*</span></label>
                            <select id="smtp_secure" name="smtp_secure" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                                <option value="tls" <?= $secure === 'tls' ? 'selected' : '' ?>>TLS (Port 587)</option>
                                <option value="ssl" <?= $secure === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                                <option value="none" <?= $secure === 'none' ? 'selected' : '' ?>>None (Port 25)</option>
                            </select>
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">SMTP Username / Email <span class="text-rose-500">*</span></label>
                            <input type="text" id="smtp_username" name="smtp_username" value="<?= htmlspecialchars($username) ?>" placeholder="e.g. admission@yourdomain.com" required class="w-full text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">SMTP Password / App Password <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="password" id="smtp_password" name="smtp_password" value="<?= htmlspecialchars($password) ?>" placeholder="Password or App Password" required class="w-full font-mono text-xs px-3 py-2 pr-8 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                                <button type="button" onclick="togglePasswordVisibility()" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <i class="fa-solid fa-eye text-xs" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="sm:col-span-2 pt-2 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="font-bold text-slate-700 block mb-1">Sender "From" Name</label>
                                <input type="text" name="smtp_from_name" value="<?= htmlspecialchars($from_name) ?>" placeholder="YourGoodGuide" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                            </div>

                            <div>
                                <label class="font-bold text-slate-700 block mb-1">Sender "From" Email</label>
                                <input type="email" name="smtp_from_email" value="<?= htmlspecialchars($from_email) ?>" placeholder="e.g. info@yourdomain.com" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="font-bold text-slate-700 block mb-1">Reply-To Email</label>
                                <input type="email" name="smtp_reply_to" value="<?= htmlspecialchars($reply_to) ?>" placeholder="e.g. support@yourdomain.com" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 text-right">
                        <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors inline-flex items-center gap-1">
                            <i class="fa-solid fa-floppy-disk text-[11px]"></i>
                            <span>Save SMTP Settings</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar (1 Col) -->
        <div class="space-y-4">
            
            <!-- Live Test Tool -->
            <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
                <h4 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-1">
                    <i class="fa-solid fa-paper-plane text-emerald-500"></i> Send Live Test Email
                </h4>
                <p class="text-[11px] text-slate-400">Enter an email address to verify that emails are reaching actual inboxes.</p>
                <form method="POST" action="smtp-settings.php" class="space-y-2.5">
                    <input type="hidden" name="action_test_smtp" value="1">
                    <div>
                        <input type="email" name="test_email" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]" placeholder="your-email@gmail.com" required>
                    </div>
                    <button type="submit" class="w-full px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-md transition-colors inline-flex items-center justify-center gap-1">
                        <i class="fa-solid fa-bolt text-[10px]"></i>
                        <span>Test Connection &amp; Send</span>
                    </button>
                </form>
            </div>

            <!-- Email Triggers -->
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <h4 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-1">
                    <i class="fa-solid fa-list-check text-sky-500"></i> Dispatched Emails
                </h4>
                <ul class="text-xs text-slate-600 space-y-2 mt-3 divide-y divide-slate-100">
                    <li class="pt-1.5 flex items-start gap-2">
                        <i class="fa-solid fa-key text-amber-500 mt-0.5 text-[10px]"></i>
                        <div><strong>Candidate OTP:</strong> Verification on registration.</div>
                    </li>
                    <li class="pt-1.5 flex items-start gap-2">
                        <i class="fa-solid fa-id-card text-emerald-500 mt-0.5 text-[10px]"></i>
                        <div><strong>Student ID &amp; Password:</strong> Welcome credentials email.</div>
                    </li>
                    <li class="pt-1.5 flex items-start gap-2">
                        <i class="fa-solid fa-receipt text-sky-500 mt-0.5 text-[10px]"></i>
                        <div><strong>Payment Receipts:</strong> Razorpay fee confirmation.</div>
                    </li>
                </ul>
            </div>

        </div>

    </div>

</div>

<script>
function applyPreset(type) {
    const hostInput = document.getElementById('smtp_host');
    const portInput = document.getElementById('smtp_port');
    const secureSelect = document.getElementById('smtp_secure');

    if (type === 'gmail') {
        hostInput.value = 'smtp.gmail.com';
        portInput.value = '587';
        secureSelect.value = 'tls';
    } else if (type === 'hostinger') {
        hostInput.value = 'smtp.hostinger.com';
        portInput.value = '465';
        secureSelect.value = 'ssl';
    } else if (type === 'cpanel') {
        const domain = window.location.hostname;
        hostInput.value = (domain && domain !== 'localhost') ? ('mail.' + domain) : 'mail.yourgoodguide.com';
        portInput.value = '465';
        secureSelect.value = 'ssl';
    }
}

function togglePasswordVisibility() {
    const passField = document.getElementById('smtp_password');
    const icon = document.getElementById('togglePasswordIcon');
    if (passField.type === 'password') {
        passField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
$page_title = "Payment Gateway";
require_once __DIR__ . '/includes/header.php';

$msg = '';
$err = '';

// Handle POST Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = [
        'razorpay_enabled',
        'razorpay_mode',
        'razorpay_test_key_id',
        'razorpay_test_key_secret',
        'razorpay_live_key_id',
        'razorpay_live_key_secret',
        'seat_booking_fee',
        'full_payment_discount_percent',
        'razorpay_company_name'
    ];

    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        foreach ($keys as $k) {
            $val = trim($_POST[$k] ?? '');
            if ($k === 'razorpay_enabled' && !isset($_POST['razorpay_enabled'])) {
                $val = '0';
            }
            $stmt->execute([$k, $val, $val]);
        }
        $msg = "Razorpay payment settings saved successfully!";
    } catch (Exception $e) {
        $err = "Failed to save settings: " . $e->getMessage();
    }
}

// Fetch current settings
$settings = [];
$s_stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'razorpay_%' OR setting_key = 'seat_booking_fee' OR setting_key = 'full_payment_discount_percent'");
while ($row = $s_stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$enabled          = ($settings['razorpay_enabled'] ?? '1') === '1';
$mode             = $settings['razorpay_mode'] ?? 'test';
$test_key_id      = $settings['razorpay_test_key_id'] ?? 'rzp_test_1DP5mmOlF5G5ag';
$test_key_secret  = $settings['razorpay_test_key_secret'] ?? '';
$live_key_id      = $settings['razorpay_live_key_id'] ?? '';
$live_key_secret  = $settings['razorpay_live_key_secret'] ?? '';
$fee_amount       = $settings['seat_booking_fee'] ?? '999';
$company_name     = $settings['razorpay_company_name'] ?? 'Finchskills Institute';
?>

<div class="space-y-4">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-md border border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-[#0e1e2e]">Razorpay Gateway Settings</h2>
                <span class="px-2 py-0.5 text-[11px] font-semibold rounded <?= $mode === 'live' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                    <i class="fa-solid <?= $mode === 'live' ? 'fa-bolt' : 'fa-vial' ?> text-[10px] mr-1"></i>
                    <?= strtoupper($mode) ?> MODE
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Configure registration fees, test and live API keys, and checkout modal branding</p>
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

    <form method="POST" action="payment-settings.php">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            
            <!-- Main Column (2 Cols) -->
            <div class="lg:col-span-2 space-y-4">
                
                <!-- General Settings Card -->
                <div class="bg-white border border-slate-200 rounded-md p-4 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-6 text-center text-[#fe7c03]"><i class="fa-solid fa-sliders text-sm"></i></span>
                            <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Gateway Configuration</h3>
                        </div>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="razorpay_enabled" value="1" <?= $enabled ? 'checked' : '' ?> class="rounded border-slate-300 text-[#fe7c03] focus:ring-[#fe7c03]">
                            <span class="text-xs font-semibold text-slate-700">Enable Gateway</span>
                        </label>
                    </div>

                    <!-- Environment Mode Radios -->
                    <div>
                        <label class="text-xs font-bold text-slate-700 block mb-1.5">Environment Mode</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="p-3 border rounded-md cursor-pointer transition-colors flex items-start gap-2 <?= $mode === 'test' ? 'border-orange-300 bg-orange-50/50' : 'border-slate-200 hover:bg-slate-50' ?>">
                                <input type="radio" name="razorpay_mode" value="test" <?= $mode === 'test' ? 'checked' : '' ?> class="mt-0.5 text-[#fe7c03] focus:ring-[#fe7c03]">
                                <div>
                                    <div class="text-xs font-bold text-slate-800 flex items-center gap-1">
                                        <i class="fa-solid fa-vial text-amber-500"></i> Test Sandbox
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">Test simulated payments without real money</div>
                                </div>
                            </label>

                            <label class="p-3 border rounded-md cursor-pointer transition-colors flex items-start gap-2 <?= $mode === 'live' ? 'border-emerald-300 bg-emerald-50/50' : 'border-slate-200 hover:bg-slate-50' ?>">
                                <input type="radio" name="razorpay_mode" value="live" <?= $mode === 'live' ? 'checked' : '' ?> class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <div class="text-xs font-bold text-slate-800 flex items-center gap-1">
                                        <i class="fa-solid fa-bolt text-emerald-500"></i> Live Production
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">Collect real payments from students</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Booking Fee & Brand Name & Full Payment Discount -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                        <div>
                            <label class="text-xs font-bold text-slate-700 block mb-1">Seat Booking Fee (₹ INR) <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">₹</span>
                                <input type="number" step="1" min="0" name="seat_booking_fee" value="<?= htmlspecialchars($fee_amount) ?>" required class="w-full text-xs pl-7 pr-3 py-2 border border-slate-200 rounded-md font-bold text-slate-800 focus:outline-none focus:border-[#fe7c03]">
                            </div>
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Fixed seat reservation amount (No discount applies)</span>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-slate-700 block mb-1">Full Course Fee Discount (%) <span class="text-emerald-600 font-extrabold">*</span></label>
                            <div class="relative">
                                <input type="number" step="1" min="0" max="100" name="full_payment_discount_percent" value="<?= htmlspecialchars($settings['full_payment_discount_percent'] ?? '10') ?>" required class="w-full text-xs px-3 py-2 border border-slate-200 rounded-md font-bold text-emerald-700 focus:outline-none focus:border-[#fe7c03]">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                            </div>
                            <span class="text-[10px] text-emerald-600 font-semibold mt-0.5 block">Discount applied exclusively on total Course Fee</span>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-slate-700 block mb-1">Company / Brand Name</label>
                            <input type="text" name="razorpay_company_name" value="<?= htmlspecialchars($company_name) ?>" placeholder="Finchskills Institute" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-md text-slate-800 focus:outline-none focus:border-[#fe7c03]">
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Name on Razorpay checkout modal</span>
                        </div>
                    </div>
                </div>

                <!-- API Credentials Card -->
                <div class="bg-white border border-slate-200 rounded-md p-4 space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <span class="w-6 text-center text-[#fe7c03]"><i class="fa-solid fa-key text-sm"></i></span>
                        <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Razorpay API Credentials</h3>
                    </div>

                    <!-- TEST KEYS -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-md space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-1">
                                <i class="fa-solid fa-vial text-amber-500"></i> Test Mode Keys
                            </span>
                            <span class="text-[10px] font-semibold text-slate-400">Sandbox</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <label class="text-[10px] uppercase font-bold text-slate-500 block mb-0.5">Test Key ID</label>
                                <input type="text" name="razorpay_test_key_id" value="<?= htmlspecialchars($test_key_id) ?>" placeholder="rzp_test_..." class="w-full text-xs font-mono px-3 py-1.5 border border-slate-200 bg-white rounded-md focus:outline-none focus:border-[#fe7c03]">
                            </div>
                            <div>
                                <label class="text-[10px] uppercase font-bold text-slate-500 block mb-0.5">Test Key Secret</label>
                                <input type="password" name="razorpay_test_key_secret" value="<?= htmlspecialchars($test_key_secret) ?>" placeholder="Optional for standard checkout" class="w-full text-xs font-mono px-3 py-1.5 border border-slate-200 bg-white rounded-md focus:outline-none focus:border-[#fe7c03]">
                            </div>
                        </div>
                    </div>

                    <!-- LIVE KEYS -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-md space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-1">
                                <i class="fa-solid fa-bolt text-emerald-500"></i> Live Production Keys
                            </span>
                            <span class="text-[10px] font-semibold text-emerald-600">Production</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <label class="text-[10px] uppercase font-bold text-slate-500 block mb-0.5">Live Key ID</label>
                                <input type="text" name="razorpay_live_key_id" value="<?= htmlspecialchars($live_key_id) ?>" placeholder="rzp_live_..." class="w-full text-xs font-mono px-3 py-1.5 border border-slate-200 bg-white rounded-md focus:outline-none focus:border-[#fe7c03]">
                            </div>
                            <div>
                                <label class="text-[10px] uppercase font-bold text-slate-500 block mb-0.5">Live Key Secret</label>
                                <input type="password" name="razorpay_live_key_secret" value="<?= htmlspecialchars($live_key_secret) ?>" placeholder="Live secret key" class="w-full text-xs font-mono px-3 py-1.5 border border-slate-200 bg-white rounded-md focus:outline-none focus:border-[#fe7c03]">
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 text-right">
                        <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors inline-flex items-center gap-1">
                            <i class="fa-solid fa-floppy-disk text-[11px]"></i>
                            <span>Save Gateway Settings</span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- Side Help Column (1 Col) -->
            <div class="space-y-4">
                
                <!-- Status Card -->
                <div class="bg-white border border-slate-200 rounded-md p-4">
                    <h4 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-1">
                        <i class="fa-solid fa-circle-check text-emerald-500"></i> Current Status
                    </h4>
                    <div class="divide-y divide-slate-100 text-xs mt-2">
                        <div class="py-2 flex justify-between">
                            <span class="text-slate-400">Gateway:</span>
                            <span class="font-bold <?= $enabled ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $enabled ? 'Enabled' : 'Disabled' ?></span>
                        </div>
                        <div class="py-2 flex justify-between">
                            <span class="text-slate-400">Mode:</span>
                            <span class="font-bold text-slate-800 uppercase"><?= htmlspecialchars($mode) ?></span>
                        </div>
                        <div class="py-2 flex justify-between">
                            <span class="text-slate-400">Seat Booking Fee:</span>
                            <span class="font-bold text-[#fe7c03]">₹<?= number_format((float)$fee_amount, 2) ?></span>
                        </div>
                        <div class="py-2 flex justify-between">
                            <span class="text-slate-400">Active Key:</span>
                            <span class="font-mono text-[10px] text-slate-600 truncate max-w-[130px]">
                                <?= htmlspecialchars(($mode === 'live') ? ($live_key_id ?: 'Not Set') : ($test_key_id ?: 'Not Set')) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Instructions Card -->
                <div class="bg-white border border-slate-200 rounded-md p-4">
                    <h4 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-1">
                        <i class="fa-solid fa-circle-info text-sky-500"></i> How to Get Keys
                    </h4>
                    <ol class="text-xs text-slate-600 space-y-2 mt-3 list-decimal list-inside leading-relaxed">
                        <li>Log in to <a href="https://dashboard.razorpay.com" target="_blank" class="text-[#fe7c03] font-semibold hover:underline">Razorpay Dashboard</a>.</li>
                        <li>Switch to <strong>Test</strong> or <strong>Live</strong> mode from header.</li>
                        <li>Go to <strong>Settings</strong> &rarr; <strong>API Keys</strong>.</li>
                        <li>Click <strong>Generate Key</strong> to copy Key ID and Secret.</li>
                        <li>Paste them here and save.</li>
                    </ol>
                </div>

            </div>

        </div>
    </form>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

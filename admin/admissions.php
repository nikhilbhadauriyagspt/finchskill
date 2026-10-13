<?php
require_once __DIR__ . '/includes/auth.php';
checkAdminAuth();

$success_msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $success_msg = 'Admission application deleted successfully!';
    if ($_GET['msg'] === 'status_updated') $success_msg = 'Admission status updated successfully!';
}

// Handle Actions (Delete / Status Change) BEFORE any HTML output
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'delete') {
        // Clean up uploaded documents
        $chk = $pdo->prepare("SELECT marksheet10, marksheet12, aadhaar_card, photo FROM admissions WHERE id = ?");
        $chk->execute([$id]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            foreach (['marksheet10', 'marksheet12', 'aadhaar_card', 'photo'] as $f) {
                if (!empty($row[$f])) {
                    if (file_exists(__DIR__ . '/../uploads/admissions/' . $row[$f])) {
                        @unlink(__DIR__ . '/../uploads/admissions/' . $row[$f]);
                    } elseif (file_exists(__DIR__ . '/../' . $row[$f])) {
                        @unlink(__DIR__ . '/../' . $row[$f]);
                    }
                }
            }
        }

        // Clean up linked student installments
        $pdo->prepare("DELETE FROM student_installments WHERE admission_id = ?")->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM admissions WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admissions.php?msg=deleted");
        exit;
    }
    if ($_GET['action'] === 'mark_read') {
        $stmt = $pdo->prepare("UPDATE admissions SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admissions.php");
        exit;
    }
    if (in_array($_GET['action'], ['Pending', 'Approved', 'Rejected'])) {
        $stmt = $pdo->prepare("UPDATE admissions SET status = ? WHERE id = ?");
        $stmt->execute([$_GET['action'], $id]);
        header("Location: admissions.php?msg=status_updated");
        exit;
    }
}

// Helper: Resolve document URL safely across uploads/admissions and uploads/students
function getAdmissionDocUrl($file) {
    if (empty($file)) return '';
    $clean = trim($file);
    if (strpos($clean, 'uploads/') === 0) {
        return '../' . $clean;
    }
    if (file_exists(__DIR__ . '/../uploads/admissions/' . $clean)) {
        return '../uploads/admissions/' . $clean;
    }
    if (file_exists(__DIR__ . '/../uploads/students/' . $clean)) {
        return '../uploads/students/' . $clean;
    }
    if (file_exists(__DIR__ . '/../' . $clean)) {
        return '../' . $clean;
    }
    return '../uploads/admissions/' . $clean;
}

// Helper: Resolve document icon based on extension
function getAdmissionDocIcon($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'])) {
        return '<i class="fa-solid fa-file-image text-sky-500"></i>';
    }
    if ($ext === 'pdf') {
        return '<i class="fa-solid fa-file-pdf text-rose-500"></i>';
    }
    return '<i class="fa-solid fa-file-lines text-slate-500"></i>';
}

// Fetch KPI Summary Counts
$kpi_stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN payment_status = 'Fully Paid' THEN 1 ELSE 0 END) as fully_paid,
    SUM(CASE WHEN payment_status = 'Partial Paid' THEN 1 ELSE 0 END) as partial_paid
FROM admissions");
$kpi = $kpi_stmt->fetch(PDO::FETCH_ASSOC);

// Filters & Search
$search = trim($_GET['search'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$filter_course = trim($_GET['course'] ?? '');
$filter_payment = trim($_GET['payment_status'] ?? '');

$query = "SELECT a.*, 
    s.photo as student_photo, 
    s.aadhaar as student_aadhaar, 
    s.phone as student_phone,
    s.seat_status as student_seat_status,
    s.payment_amount as student_seat_fee
FROM admissions a 
LEFT JOIN students s ON (s.admission_id = a.id OR (s.student_id = a.student_id AND s.student_id != '') OR (s.email = a.email AND s.email != ''))
WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (a.name LIKE ? OR a.email LIKE ? OR a.mobile LIKE ? OR a.aadhaar LIKE ? OR a.student_id LIKE ? OR a.enrollment_no LIKE ? OR a.course LIKE ?)";
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term, $term, $term, $term, $term]);
}
if (!empty($filter_status)) {
    $query .= " AND a.status = ?";
    $params[] = $filter_status;
}
if (!empty($filter_payment)) {
    $query .= " AND a.payment_status = ?";
    $params[] = $filter_payment;
}
if (!empty($filter_course)) {
    $query .= " AND a.course LIKE ?";
    $params[] = "%$filter_course%";
}

$query .= " ORDER BY a.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark all as read when page is visited
$pdo->query("UPDATE admissions SET is_read = 1 WHERE is_read = 0");

$page_title = "Admission Applications";
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-4">

    <?php if (!empty($success_msg)): ?>
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-md flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                <span class="font-medium"><?= htmlspecialchars($success_msg) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fa-solid fa-xmark"></i></button>
        </div>
    <?php endif; ?>

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-md border border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-[#0e1e2e]">Admission Applications &amp; Verified Documents</h2>
                <span class="px-2 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-600 rounded border border-slate-200">
                    <?= count($admissions) ?> Applications Listed
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Inspect enrolled candidates, verified marksheets, Aadhaar cards, official enrollment slips, and fee payments.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="admission-add.php" class="px-3.5 py-1.5 text-xs font-bold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors flex items-center gap-1 no-underline shadow-sm">
                <i class="fa-solid fa-plus text-[11px]"></i>
                <span>+ Offline Admission</span>
            </a>
            <a href="../admission.php" target="_blank" class="px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-md transition-colors flex items-center gap-1 no-underline">
                <i class="fa-solid fa-file-signature text-[11px]"></i>
                <span>Public Form</span>
            </a>
        </div>
    </div>

    <!-- KPI STATS CARDS (5-METRIC GRID) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <!-- Total Applications -->
        <a href="admissions.php" class="bg-white border border-slate-200 hover:border-slate-300 rounded-md p-3.5 no-underline transition-all block">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-slate-400">Total Applied</span>
                <span class="w-7 h-7 rounded bg-orange-50 text-[#fe7c03] flex items-center justify-center text-xs"><i class="fa-solid fa-file-signature"></i></span>
            </div>
            <h4 class="text-xl font-bold text-[#0e1e2e]"><?= (int)($kpi['total'] ?? 0) ?></h4>
            <p class="text-[10px] text-slate-400 mt-1">All Enrolled Candidates</p>
        </a>

        <!-- Approved Admissions -->
        <a href="admissions.php?status=Approved" class="bg-white border <?= $filter_status === 'Approved' ? 'border-emerald-500 ring-1 ring-emerald-500' : 'border-slate-200 hover:border-emerald-300' ?> rounded-md p-3.5 no-underline transition-all block">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-slate-400">Approved</span>
                <span class="w-7 h-7 rounded bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs"><i class="fa-solid fa-circle-check"></i></span>
            </div>
            <h4 class="text-xl font-bold text-emerald-700"><?= (int)($kpi['approved'] ?? 0) ?></h4>
            <p class="text-[10px] text-emerald-600 font-medium mt-1">Confirmed &amp; Enrolled</p>
        </a>

        <!-- Pending Review -->
        <a href="admissions.php?status=Pending" class="bg-white border <?= $filter_status === 'Pending' ? 'border-amber-500 ring-1 ring-amber-500' : 'border-slate-200 hover:border-amber-300' ?> rounded-md p-3.5 no-underline transition-all block">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-amber-700">Pending Review</span>
                <span class="w-7 h-7 rounded bg-amber-50 text-amber-600 flex items-center justify-center text-xs"><i class="fa-solid fa-clock"></i></span>
            </div>
            <h4 class="text-xl font-bold text-amber-600"><?= (int)($kpi['pending'] ?? 0) ?></h4>
            <p class="text-[10px] text-amber-600 font-medium mt-1">Action Required</p>
        </a>

        <!-- Fully Paid -->
        <a href="admissions.php?payment_status=Fully Paid" class="bg-white border <?= $filter_payment === 'Fully Paid' ? 'border-sky-500 ring-1 ring-sky-500' : 'border-slate-200 hover:border-sky-300' ?> rounded-md p-3.5 no-underline transition-all block">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-slate-400">Fully Paid</span>
                <span class="w-7 h-7 rounded bg-sky-50 text-sky-600 flex items-center justify-center text-xs"><i class="fa-solid fa-receipt"></i></span>
            </div>
            <h4 class="text-xl font-bold text-sky-700"><?= (int)($kpi['fully_paid'] ?? 0) ?></h4>
            <p class="text-[10px] text-sky-600 font-medium mt-1">100% Fees Cleared</p>
        </a>

        <!-- Partial Paid -->
        <a href="admissions.php?payment_status=Partial Paid" class="bg-white border <?= $filter_payment === 'Partial Paid' ? 'border-purple-500 ring-1 ring-purple-500' : 'border-slate-200 hover:border-purple-300' ?> rounded-md p-3.5 no-underline transition-all block">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-slate-400">Installments</span>
                <span class="w-7 h-7 rounded bg-purple-50 text-purple-600 flex items-center justify-center text-xs"><i class="fa-solid fa-calendar-check"></i></span>
            </div>
            <h4 class="text-xl font-bold text-purple-700"><?= (int)($kpi['partial_paid'] ?? 0) ?></h4>
            <p class="text-[10px] text-purple-600 font-medium mt-1">Scheduled Payments</p>
        </a>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white border border-slate-200 rounded-md p-3">
        <form method="GET" action="admissions.php" class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <!-- Status Select -->
                <select name="status" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-md px-2 py-1.5 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                    <option value="">All Admission Statuses</option>
                    <option value="Approved" <?= $filter_status === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Rejected" <?= $filter_status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>

                <!-- Payment Status Select -->
                <select name="payment_status" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-md px-2 py-1.5 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                    <option value="">All Fee Payments</option>
                    <option value="Fully Paid" <?= $filter_payment === 'Fully Paid' ? 'selected' : '' ?>>Fully Paid</option>
                    <option value="Partial Paid" <?= $filter_payment === 'Partial Paid' ? 'selected' : '' ?>>Partial Paid</option>
                    <option value="Pending Payment" <?= $filter_payment === 'Pending Payment' ? 'selected' : '' ?>>Pending Payment</option>
                </select>

                <!-- Course Input -->
                <input type="text" name="course" placeholder="Filter course name..." value="<?= htmlspecialchars($filter_course) ?>" class="text-xs bg-white border border-slate-200 rounded-md px-2 py-1.5 text-slate-700 focus:outline-none focus:border-[#fe7c03]">

                <?php if (!empty($filter_status) || !empty($filter_payment) || !empty($filter_course) || !empty($search)): ?>
                    <a href="admissions.php" class="px-2 py-1 text-xs text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded transition-colors flex items-center gap-1 no-underline">
                        <i class="fa-solid fa-xmark text-[10px]"></i> Reset
                    </a>
                <?php endif; ?>
            </div>

            <!-- Global Search Input -->
            <div class="relative min-w-[280px] flex-1 max-w-md">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" placeholder="Search Candidate, Enrollment No, Student ID, Mobile..." value="<?= htmlspecialchars($search) ?>" class="w-full text-xs pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03] text-slate-800">
            </div>
        </form>
    </div>

    <!-- ADMISSIONS MASTER TABLE -->
    <div class="bg-white border border-slate-200 rounded-md overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[1260px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-4 py-3" style="width: 17%; min-width: 195px;">Candidate</th>
                        <th class="px-4 py-3" style="width: 18%; min-width: 205px;">Course &amp; Enrollment No.</th>
                        <th class="px-4 py-3" style="width: 13%; min-width: 145px;">Fee Summary</th>
                        <th class="px-4 py-3" style="width: 13%; min-width: 155px;">Contact Info</th>
                        <th class="px-4 py-3" style="width: 18%; min-width: 200px;">Attached Documents</th>
                        <th class="px-3 py-3" style="width: 8%; min-width: 95px;">Status</th>
                        <th class="px-3 py-3" style="width: 6%; min-width: 90px;">Applied Date</th>
                        <th class="px-4 py-3 text-right" style="width: 7%; min-width: 135px;">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($admissions)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                                <i class="fa-solid fa-id-card text-2xl mb-1 text-slate-300 block"></i>
                                <span class="font-semibold text-slate-700 block">No admission applications found</span>
                                <span class="text-[11px]">No applications match your current filters. Try resetting search criteria.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($admissions as $idx => $row): ?>
                            <?php 
                            // Resolve candidate avatar photo
                            $raw_photo = !empty($row['photo']) ? $row['photo'] : (!empty($row['student_photo']) ? $row['student_photo'] : '');
                            $photoSrc = getAdmissionDocUrl($raw_photo);

                            // Resolve documents
                            $doc10_url = getAdmissionDocUrl($row['marksheet10'] ?? '');
                            $doc12_url = getAdmissionDocUrl($row['marksheet12'] ?? '');
                            $docAadh_url = getAdmissionDocUrl($row['aadhaar_card'] ?? '');
                            $has_docs = (!empty($doc10_url) || !empty($doc12_url) || !empty($docAadh_url) || !empty($photoSrc));

                            $clean_mobile = preg_replace('/[^0-9]/', '', $row['mobile'] ?? '');
                            $wa_link = "https://wa.me/91" . substr($clean_mobile, -10);
                            ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                
                                <!-- Candidate Info -->
                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-start gap-2.5">
                                        <?php if (!empty($photoSrc)): ?>
                                            <img src="<?= htmlspecialchars($photoSrc) ?>" class="w-9 h-9 rounded-full object-cover border border-slate-200 shrink-0" alt="Avatar">
                                        <?php else: ?>
                                            <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-bold flex items-center justify-center text-xs shrink-0">
                                                <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <div class="font-bold text-[#0e1e2e] leading-snug truncate" title="<?= htmlspecialchars($row['name']) ?>">
                                                <?= htmlspecialchars($row['name']) ?>
                                            </div>
                                            <div class="flex items-center gap-1 flex-wrap mt-0.5">
                                                <?php if (!empty($row['student_id'])): ?>
                                                    <span class="font-mono text-[9.5px] px-1.5 py-0.2 rounded bg-orange-50 text-[#fe7c03] border border-orange-200 font-bold">
                                                        <?= htmlspecialchars($row['student_id']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="text-[10px] text-slate-400">#<?= $row['id'] ?></span>
                                            </div>
                                            <?php if (!empty($row['father_name'])): ?>
                                                <div class="text-[10.5px] text-slate-500 mt-0.5">
                                                    S/o: <?= htmlspecialchars($row['father_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Course & Official Enrollment No -->
                                <td class="px-4 py-3 align-top">
                                    <div class="font-bold text-slate-800 text-[11.5px] leading-snug mb-1" title="<?= htmlspecialchars($row['course'] ?? 'N/A') ?>">
                                        <?= htmlspecialchars($row['course'] ?? 'N/A') ?>
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <?php if (!empty($row['enrollment_no'])): ?>
                                            <a href="../enrollment-slip.php?id=<?= $row['id'] ?>" target="_blank" class="px-2 py-0.5 text-[10.5px] font-mono font-bold rounded bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 inline-flex items-center gap-1 no-underline transition-colors" title="View Official Enrollment Slip (PDF)">
                                                <i class="fa-solid fa-graduation-cap text-[9px]"></i> <?= htmlspecialchars($row['enrollment_no']) ?>
                                                <i class="fa-solid fa-arrow-up-right-from-square text-[8px] text-sky-400"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-amber-50 text-amber-700 border border-amber-200">
                                                Enrollment Pending
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($row['education'])): ?>
                                        <div class="text-[10.5px] text-slate-500 mt-1">
                                            Edu: <span class="font-medium text-slate-700"><?= htmlspecialchars($row['education']) ?></span>
                                            <?php if (!empty($row['percentage'])): ?> (<?= htmlspecialchars($row['percentage']) ?>)<?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Fee Summary -->
                                <td class="px-4 py-3 align-top font-mono">
                                    <?php if (!empty($row['fee_total']) && (float)$row['fee_total'] > 0): ?>
                                        <div class="text-slate-700 font-semibold text-[11px]">Fee: ₹<?= number_format((float)$row['fee_total'], 0) ?></div>
                                        <div class="text-emerald-700 font-bold text-[11px] mt-0.5">Paid: ₹<?= number_format((float)$row['fee_paid'], 0) ?></div>
                                        <?php if ((float)($row['seat_fee_adjusted'] ?? 0) > 0): ?>
                                            <div class="text-[10px] text-emerald-600 font-medium">Seat Adj: -₹<?= number_format((float)$row['seat_fee_adjusted'], 0) ?></div>
                                        <?php endif; ?>
                                        <?php if ((float)$row['fee_pending'] > 0): ?>
                                            <div class="text-rose-600 font-bold text-[11px] mt-0.5">Due: ₹<?= number_format((float)$row['fee_pending'], 0) ?></div>
                                        <?php else: ?>
                                            <div class="mt-0.5"><span class="px-1.5 py-0.2 text-[9px] bg-emerald-50 text-emerald-700 border border-emerald-200 rounded font-bold">Fully Cleared</span></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Contact Info -->
                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs">
                                        <a href="tel:<?= htmlspecialchars($row['mobile'] ?? '') ?>" class="hover:text-[#fe7c03] no-underline text-inherit">
                                            <?= htmlspecialchars($row['mobile'] ?? 'N/A') ?>
                                        </a>
                                        <?php if (!empty($clean_mobile)): ?>
                                            <a href="<?= $wa_link ?>" target="_blank" class="text-emerald-600 hover:text-emerald-700 text-sm" title="Chat on WhatsApp">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[11px] text-slate-500 truncate max-w-[150px] mt-0.5" title="<?= htmlspecialchars($row['email'] ?? 'N/A') ?>">
                                        <?= htmlspecialchars($row['email'] ?? 'N/A') ?>
                                    </div>
                                    <?php if (!empty($row['city']) || !empty($row['state'])): ?>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            <?= htmlspecialchars($row['city'] ?? '') ?><?= (!empty($row['city']) && !empty($row['state'])) ? ', ' : '' ?><?= htmlspecialchars($row['state'] ?? '') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Attached Verification Documents -->
                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <?php if (!empty($row['marksheet10'])): ?>
                                            <a href="<?= htmlspecialchars($doc10_url) ?>" target="_blank" class="px-2 py-1 text-[10.5px] font-bold rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 inline-flex items-center gap-1 no-underline transition-colors shadow-sm" title="View 10th Marksheet">
                                                <i class="fa-solid fa-file-pdf text-rose-600"></i> 10th Marksheet
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($row['marksheet12'])): ?>
                                            <a href="<?= htmlspecialchars($doc12_url) ?>" target="_blank" class="px-2 py-1 text-[10.5px] font-bold rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 inline-flex items-center gap-1 no-underline transition-colors shadow-sm" title="View 12th Marksheet">
                                                <i class="fa-solid fa-file-pdf text-rose-600"></i> 12th Marksheet
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($row['aadhaar_card'])): ?>
                                            <a href="<?= htmlspecialchars($docAadh_url) ?>" target="_blank" class="px-2 py-1 text-[10.5px] font-bold rounded bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 inline-flex items-center gap-1 no-underline transition-colors shadow-sm" title="View Aadhaar Card">
                                                <i class="fa-solid fa-id-card text-sky-600"></i> Aadhaar Card
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($photoSrc)): ?>
                                            <a href="<?= htmlspecialchars($photoSrc) ?>" target="_blank" class="px-2 py-1 text-[10.5px] font-bold rounded bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 inline-flex items-center gap-1 no-underline transition-colors shadow-sm" title="View Passport Photo">
                                                <i class="fa-solid fa-image text-amber-600"></i> Photo
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!$has_docs): ?>
                                            <span class="text-slate-400 italic text-[11px] inline-flex items-center gap-1">
                                                <i class="fa-solid fa-folder-open text-[10px]"></i> No files attached
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-3 py-3 align-top">
                                    <div class="dropdown inline-block">
                                        <button class="px-2 py-0.5 text-[10px] font-bold rounded border dropdown-toggle <?= $row['status'] === 'Approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($row['status'] === 'Rejected' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200') ?>" type="button" data-bs-toggle="dropdown">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </button>
                                        <ul class="dropdown-menu text-xs border border-slate-200 rounded-md py-1 shadow-md">
                                            <li><a class="dropdown-item py-1.5 font-semibold text-amber-600" href="admissions.php?action=Pending&id=<?= $row['id'] ?>"><i class="fa-regular fa-clock me-1"></i> Set Pending</a></li>
                                            <li><a class="dropdown-item py-1.5 font-semibold text-emerald-600" href="admissions.php?action=Approved&id=<?= $row['id'] ?>"><i class="fa-solid fa-check me-1"></i> Set Approved</a></li>
                                            <li><a class="dropdown-item py-1.5 font-semibold text-rose-600" href="admissions.php?action=Rejected&id=<?= $row['id'] ?>"><i class="fa-solid fa-ban me-1"></i> Set Rejected</a></li>
                                        </ul>
                                    </div>
                                    <?php if (!empty($row['payment_status'])): ?>
                                        <div class="mt-1">
                                            <span class="text-[9.5px] font-bold px-1.5 py-0.2 rounded inline-block <?= $row['payment_status'] === 'Fully Paid' ? 'bg-emerald-100 text-emerald-800' : ($row['payment_status'] === 'Partial Paid' ? 'bg-purple-100 text-purple-800' : 'bg-amber-100 text-amber-800') ?>">
                                                <?= htmlspecialchars($row['payment_status']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Applied Date -->
                                <td class="px-3 py-3 align-top text-slate-500 text-[11px] whitespace-nowrap">
                                    <div><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                                    <div class="text-[10px] text-slate-400"><?= date('h:i A', strtotime($row['created_at'])) ?></div>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 align-top text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        <!-- Official Enrollment Slip PDF -->
                                        <a href="../enrollment-slip.php?id=<?= $row['id'] ?>" target="_blank" class="px-2 py-1 text-[10.5px] font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 rounded inline-flex items-center gap-1 transition-colors no-underline shadow-sm" title="View & Download Official Enrollment Slip (PDF)">
                                            <i class="fa-solid fa-file-invoice text-sky-600 text-[10px]"></i>
                                            <span>Slip</span>
                                        </a>

                                        <!-- View Details Modal Button -->
                                        <button type="button" class="w-7 h-7 rounded border border-slate-200 bg-white text-slate-600 hover:text-[#fe7c03] hover:border-orange-200 flex items-center justify-center transition-colors shadow-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id'] ?>" title="View Full 360° Application">
                                            <i class="fa-solid fa-eye text-[10.5px]"></i>
                                        </button>

                                        <!-- Delete Application -->
                                        <a href="admissions.php?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Permanently delete admission application for \'<?= htmlspecialchars(addslashes($row['name'])) ?>\'? This will also remove associated documents.')" class="w-7 h-7 rounded border border-slate-200 bg-white text-slate-400 hover:text-rose-600 hover:border-rose-200 flex items-center justify-center transition-colors no-underline shadow-sm" title="Delete Application">
                                            <i class="fa-regular fa-trash-can text-[10px]"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- ============================================================
                                 FULL APPLICATION 360° DETAILS MODAL
                            ============================================================ -->
                            <div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content border border-slate-200 rounded-lg shadow-xl overflow-hidden">
                                        
                                        <!-- Modal Header -->
                                        <div class="modal-header border-b border-slate-200 py-3 px-4 bg-[#0e1e2e] text-white">
                                            <div class="flex items-center gap-2">
                                                <span class="w-7 text-center text-[#fe7c03] fs-5"><i class="fa-solid fa-graduation-cap"></i></span>
                                                <div>
                                                    <h5 class="modal-title text-sm font-bold text-white uppercase tracking-wider mb-0">Admission Application #<?= $row['id'] ?></h5>
                                                    <p class="text-[11px] text-slate-300 mb-0">Submitted: <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?> &bull; <?= htmlspecialchars($row['enrollment_no'] ?? 'Enrollment Pending') ?></p>
                                                </div>
                                            </div>
                                            <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body p-4 bg-slate-50/70 space-y-4">
                                            
                                            <!-- Top Identity Strip -->
                                            <div class="bg-white border border-slate-200 rounded-lg p-4 flex flex-col sm:flex-row items-center sm:items-start gap-4">
                                                <div class="shrink-0 text-center">
                                                    <?php if (!empty($photoSrc)): ?>
                                                        <img src="<?= htmlspecialchars($photoSrc) ?>" class="w-24 h-28 rounded object-cover border border-slate-200 shadow-sm mx-auto mb-1.5" alt="Candidate Photo">
                                                    <?php else: ?>
                                                        <div class="w-24 h-28 rounded bg-slate-100 border border-slate-200 text-slate-400 text-3xl flex items-center justify-center mx-auto mb-1.5">
                                                            <i class="fa-solid fa-user"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span class="text-[10px] text-slate-400 font-mono block">Passport Photo</span>
                                                </div>

                                                <div class="flex-1 min-w-0 space-y-2">
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <div>
                                                            <h4 class="text-base font-bold text-[#0e1e2e] mb-0"><?= htmlspecialchars($row['name']) ?></h4>
                                                            <div class="flex items-center gap-2 mt-0.5">
                                                                <?php if (!empty($row['student_id'])): ?>
                                                                    <span class="px-2 py-0.5 text-xs font-mono font-bold rounded bg-orange-100 text-[#fe7c03] border border-orange-200">
                                                                        <?= htmlspecialchars($row['student_id']) ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                <?php if (!empty($row['enrollment_no'])): ?>
                                                                    <span class="px-2 py-0.5 text-xs font-mono font-bold rounded bg-sky-100 text-sky-700 border border-sky-200">
                                                                        <?= htmlspecialchars($row['enrollment_no']) ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="text-right">
                                                            <span class="px-2.5 py-1 text-xs font-bold rounded <?= $row['status'] === 'Approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($row['status'] === 'Rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') ?>">
                                                                Status: <?= htmlspecialchars($row['status']) ?>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs pt-1">
                                                        <div>
                                                            <span class="text-[10.5px] text-slate-400 block uppercase">Selected Course Program</span>
                                                            <strong class="text-slate-800"><?= htmlspecialchars($row['course'] ?? 'N/A') ?></strong>
                                                        </div>
                                                        <div>
                                                            <span class="text-[10.5px] text-slate-400 block uppercase">Contact Details</span>
                                                            <strong class="text-slate-800"><?= htmlspecialchars($row['mobile'] ?? 'N/A') ?></strong> &bull; <span class="text-slate-500"><?= htmlspecialchars($row['email'] ?? 'N/A') ?></span>
                                                        </div>
                                                    </div>

                                                    <!-- Direct Slip Link Button in Modal -->
                                                    <?php if (!empty($row['enrollment_no'])): ?>
                                                        <div class="pt-1">
                                                            <a href="../enrollment-slip.php?id=<?= $row['id'] ?>" target="_blank" class="px-3 py-1.5 text-xs font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 rounded inline-flex items-center gap-1.5 no-underline transition-colors shadow-sm">
                                                                <i class="fa-solid fa-file-invoice text-sky-600"></i> Open Official Enrollment Slip (PDF)
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Personal & Academic Details Grid -->
                                            <div class="bg-white border border-slate-200 rounded-lg p-4 space-y-3">
                                                <h6 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider pb-1 border-b border-slate-100 flex items-center gap-1.5 mb-0">
                                                    <i class="fa-solid fa-user-graduate text-[#fe7c03]"></i> Personal &amp; Academic Credentials
                                                </h6>
                                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                                                    <div>
                                                        <span class="text-[10px] text-slate-400 block uppercase">Father's Name</span>
                                                        <span class="font-semibold text-slate-800"><?= htmlspecialchars($row['father_name'] ?? 'N/A') ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[10px] text-slate-400 block uppercase">Date of Birth</span>
                                                        <span class="font-semibold text-slate-800"><?= htmlspecialchars($row['dob'] ?? 'N/A') ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[10px] text-slate-400 block uppercase">Gender</span>
                                                        <span class="font-semibold text-slate-800"><?= htmlspecialchars($row['gender'] ?? 'N/A') ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[10px] text-slate-400 block uppercase">Aadhaar Number</span>
                                                        <span class="font-mono font-semibold text-slate-800"><?= htmlspecialchars($row['aadhaar'] ?? 'N/A') ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[10px] text-slate-400 block uppercase">Highest Education</span>
                                                        <span class="font-semibold text-slate-800"><?= htmlspecialchars($row['education'] ?? 'N/A') ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[10px] text-slate-400 block uppercase">Board / University</span>
                                                        <span class="font-semibold text-slate-800"><?= htmlspecialchars($row['board_university'] ?? 'N/A') ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[10px] text-slate-400 block uppercase">Passing Year &amp; Percentage</span>
                                                        <span class="font-semibold text-slate-800"><?= htmlspecialchars($row['passing_year'] ?? 'N/A') ?><?= !empty($row['percentage']) ? ' (' . htmlspecialchars($row['percentage']) . ')' : '' ?></span>
                                                    </div>
                                                    <div class="sm:col-span-2">
                                                        <span class="text-[10px] text-slate-400 block uppercase">Residential Address</span>
                                                        <span class="text-slate-800 font-medium">
                                                            <?= htmlspecialchars($row['address'] ?? '') ?>
                                                            <?php if (!empty($row['city'])): ?>, <?= htmlspecialchars($row['city']) ?><?php endif; ?>
                                                            <?php if (!empty($row['state'])): ?>, <?= htmlspecialchars($row['state']) ?><?php endif; ?>
                                                            <?php if (!empty($row['pincode'])): ?> - <?= htmlspecialchars($row['pincode']) ?><?php endif; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Financial Ledger Breakdown Box -->
                                            <?php if (!empty($row['fee_total']) && (float)$row['fee_total'] > 0): ?>
                                                <div class="bg-white border border-slate-200 rounded-lg p-4 space-y-2">
                                                    <h6 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider pb-1 border-b border-slate-100 flex items-center gap-1.5 mb-0">
                                                        <i class="fa-solid fa-receipt text-emerald-600"></i> Course Fee &amp; Accounting Ledger
                                                    </h6>
                                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs font-mono">
                                                        <div class="bg-slate-50 p-2.5 rounded border border-slate-200">
                                                            <span class="text-[10px] text-slate-400 block uppercase font-sans">Total Course Fee</span>
                                                            <span class="font-bold text-slate-800 text-sm">₹<?= number_format((float)$row['fee_total'], 2) ?></span>
                                                        </div>
                                                        <div class="bg-emerald-50/60 p-2.5 rounded border border-emerald-200">
                                                            <span class="text-[10px] text-emerald-700 block uppercase font-sans">Seat Fee Adjusted</span>
                                                            <span class="font-bold text-emerald-700 text-sm">- ₹<?= number_format((float)($row['seat_fee_adjusted'] ?? 0), 2) ?></span>
                                                        </div>
                                                        <div class="bg-emerald-50/60 p-2.5 rounded border border-emerald-200">
                                                            <span class="text-[10px] text-emerald-700 block uppercase font-sans">Total Paid</span>
                                                            <span class="font-bold text-emerald-700 text-sm">₹<?= number_format((float)$row['fee_paid'], 2) ?></span>
                                                        </div>
                                                        <div class="bg-rose-50/60 p-2.5 rounded border border-rose-200">
                                                            <span class="text-[10px] text-rose-700 block uppercase font-sans">Remaining Balance</span>
                                                            <span class="font-bold text-rose-700 text-sm">₹<?= number_format((float)$row['fee_pending'], 2) ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Attached Documents Section -->
                                            <div class="bg-white border border-slate-200 rounded-lg p-4 space-y-2.5">
                                                <h6 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider pb-1 border-b border-slate-100 flex items-center justify-between mb-0">
                                                    <span class="flex items-center gap-1.5">
                                                        <i class="fa-solid fa-folder-open text-[#fe7c03]"></i> Attached Verification Documents
                                                    </span>
                                                    <span class="text-[10px] text-slate-400 font-normal">Click any document to inspect or download</span>
                                                </h6>

                                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1">
                                                    
                                                    <!-- 10th Marksheet -->
                                                    <div class="border border-slate-200 rounded-md p-3 bg-slate-50 flex items-center justify-between">
                                                        <div>
                                                            <span class="text-[10px] text-slate-400 block uppercase font-bold">10th Marksheet</span>
                                                            <?php if (!empty($row['marksheet10'])): ?>
                                                                <span class="text-xs font-semibold text-slate-800 flex items-center gap-1 mt-0.5">
                                                                    <i class="fa-solid fa-circle-check text-emerald-600 text-[10px]"></i> Uploaded
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-xs text-slate-400 mt-0.5 block italic">Not provided</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($row['marksheet10'])): ?>
                                                            <a href="<?= htmlspecialchars($doc10_url) ?>" target="_blank" class="px-2.5 py-1 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded transition-colors no-underline inline-flex items-center gap-1 shadow-sm">
                                                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> View
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- 12th Marksheet -->
                                                    <div class="border border-slate-200 rounded-md p-3 bg-slate-50 flex items-center justify-between">
                                                        <div>
                                                            <span class="text-[10px] text-slate-400 block uppercase font-bold">12th Marksheet</span>
                                                            <?php if (!empty($row['marksheet12'])): ?>
                                                                <span class="text-xs font-semibold text-slate-800 flex items-center gap-1 mt-0.5">
                                                                    <i class="fa-solid fa-circle-check text-emerald-600 text-[10px]"></i> Uploaded
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-xs text-slate-400 mt-0.5 block italic">Not provided</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($row['marksheet12'])): ?>
                                                            <a href="<?= htmlspecialchars($doc12_url) ?>" target="_blank" class="px-2.5 py-1 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded transition-colors no-underline inline-flex items-center gap-1 shadow-sm">
                                                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> View
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Aadhaar Card -->
                                                    <div class="border border-slate-200 rounded-md p-3 bg-slate-50 flex items-center justify-between">
                                                        <div>
                                                            <span class="text-[10px] text-slate-400 block uppercase font-bold">Aadhaar Card</span>
                                                            <?php if (!empty($row['aadhaar_card'])): ?>
                                                                <span class="text-xs font-semibold text-slate-800 flex items-center gap-1 mt-0.5">
                                                                    <i class="fa-solid fa-circle-check text-emerald-600 text-[10px]"></i> Uploaded
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-xs text-slate-400 mt-0.5 block italic">Not provided</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($row['aadhaar_card'])): ?>
                                                            <a href="<?= htmlspecialchars($docAadh_url) ?>" target="_blank" class="px-2.5 py-1 text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 rounded transition-colors no-underline inline-flex items-center gap-1 shadow-sm">
                                                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> View
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>

                                                </div>
                                            </div>

                                        </div>

                                        <!-- Modal Footer -->
                                        <div class="modal-footer border-t border-slate-200 py-2.5 px-4 bg-slate-50 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <a href="mailto:<?= htmlspecialchars($row['email'] ?? '') ?>" class="px-3 py-1.5 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors no-underline inline-flex items-center gap-1 shadow-sm">
                                                    <i class="fa-solid fa-paper-plane text-[10px]"></i> Email Candidate
                                                </a>
                                                <?php if (!empty($clean_mobile)): ?>
                                                    <a href="<?= $wa_link ?>" target="_blank" class="px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-md transition-colors no-underline inline-flex items-center gap-1 shadow-sm">
                                                        <i class="fa-brands fa-whatsapp text-xs"></i> WhatsApp
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="px-3.5 py-1.5 text-xs text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-100 font-semibold" data-bs-dismiss="modal">Close</button>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

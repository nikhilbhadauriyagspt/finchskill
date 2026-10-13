<?php
$page_title = "Manual / Offline Admission Entry";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../mail-helper.php';

$success_msg = '';
$error_msg = '';
$created_admission_id = 0;
$created_receipt_no = '';

// Fetch all courses
$courses_stmt = $pdo->query("SELECT id, title, duration, fee FROM courses WHERE status = 'active' ORDER BY title ASC");
$all_courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all registered students for instant select/search
$students_stmt = $pdo->query("SELECT id, student_id, name, email, phone, aadhaar, photo FROM students ORDER BY id DESC");
$all_students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

// Map latest admission demographics by student_id or email
$admissions_map = [];
try {
    $adm_all_stmt = $pdo->query("SELECT student_id, email, father_name, dob, gender, address, pincode, education, board_university, passing_year, percentage FROM admissions ORDER BY id ASC");
    while ($adm_row = $adm_all_stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($adm_row['student_id'])) {
            $admissions_map[$adm_row['student_id']] = $adm_row;
        }
        if (!empty($adm_row['email'])) {
            $admissions_map[$adm_row['email']] = $adm_row;
        }
    }
} catch (Exception $e) {
    // Ignore if table has missing columns
}

foreach ($all_students as &$st) {
    $matched = $admissions_map[$st['student_id'] ?? ''] ?? $admissions_map[$st['email'] ?? ''] ?? [];
    $st['father_name'] = $matched['father_name'] ?? '';
    $st['dob'] = $matched['dob'] ?? '';
    $st['gender'] = !empty($matched['gender']) ? $matched['gender'] : 'Male';
    $st['address'] = $matched['address'] ?? '';
    $st['pincode'] = $matched['pincode'] ?? '';
    $st['education'] = $matched['education'] ?? '';
    $st['board_university'] = $matched['board_university'] ?? '';
    $st['passing_year'] = $matched['passing_year'] ?? '';
    $st['percentage'] = $matched['percentage'] ?? '';
}
unset($st);

// Fetch full payment discount percent
$disc_stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'full_payment_discount_percent'");
$disc_stmt->execute();
$full_payment_discount_percent = (float)($disc_stmt->fetchColumn() ?: 10);

// Process Offline Admission Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_offline_admission') {
    try {
        $student_db_id = (int)($_POST['student_db_id'] ?? 0);
        $student_id = trim($_POST['student_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $father_name = trim($_POST['father_name'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        $gender = trim($_POST['gender'] ?? 'Male');
        $aadhaar = trim($_POST['aadhaar'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $education = trim($_POST['education'] ?? '');
        $board_university = trim($_POST['board_university'] ?? '');
        $passing_year = trim($_POST['passing_year'] ?? '');
        $percentage = trim($_POST['percentage'] ?? '');

        $course_id = (int)($_POST['course_id'] ?? 0);
        $batch_time = trim($_POST['batch_time'] ?? 'Regular');
        $batch_type = trim($_POST['batch_type'] ?? 'Offline Classroom');
        $payment_plan = trim($_POST['payment_plan'] ?? 'full'); // full or installment
        $payment_mode = trim($_POST['payment_mode'] ?? 'Cash');
        $transaction_id = trim($_POST['transaction_id'] ?? '');
        $paid_amount = (float)($_POST['paid_amount'] ?? 0);
        $payment_notes = trim($_POST['payment_notes'] ?? '');

        if (empty($name) || empty($mobile) || empty($email) || $course_id <= 0) {
            throw new Exception("Please fill in all mandatory candidate and course fields.");
        }

        // Get course info
        $c_stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $c_stmt->execute([$course_id]);
        $course = $c_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$course) {
            throw new Exception("Selected course is invalid.");
        }

        $base_fee = (float)$course['fee'];
        if ($base_fee <= 0) $base_fee = 10000;

        // Auto create student_id if empty
        if (empty($student_id)) {
            $student_id = 'FS-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4));
        }

        // Check or create student record in `students`
        if ($student_db_id <= 0) {
            $chk_s = $pdo->prepare("SELECT id FROM students WHERE email = ? OR student_id = ?");
            $chk_s->execute([$email, $student_id]);
            $existing_s = $chk_s->fetch(PDO::FETCH_ASSOC);
            if ($existing_s) {
                $student_db_id = (int)$existing_s['id'];
            } else {
                $default_pass = password_hash('Student@123', PASSWORD_BCRYPT);
                $ins_std = $pdo->prepare("INSERT INTO students 
                    (student_id, name, email, phone, aadhaar, password, seat_status, payment_status, is_verified, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'Confirmed', 'Paid', 1, NOW())");
                $ins_std->execute([$student_id, $name, $email, $mobile, $aadhaar, $default_pass]);
                $student_db_id = (int)$pdo->lastInsertId();
            }
        }

        $pdo->beginTransaction();

        // Calculate fees
        $discount_amount = 0.00;
        $final_course_total = $base_fee;
        if ($payment_plan === 'full') {
            $discount_amount = round(($base_fee * $full_payment_discount_percent) / 100, 2);
            $final_course_total = $base_fee - $discount_amount;
        }

        $pending_balance = max(0, $final_course_total - $paid_amount);
        $p_status = ($pending_balance <= 0) ? 'Fully Paid' : 'Partial Paid';
        $receipt_no = 'RCP-MAN-' . strtoupper(substr(uniqid(), -6));
        $pay_ref = !empty($transaction_id) ? $payment_mode . ': ' . $transaction_id : $payment_mode . ' Receipt #' . $receipt_no;

        // Handle Document Uploads
        $upload_dir = __DIR__ . '/../uploads/admissions/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }

        $marksheet10_file = '';
        $marksheet12_file = '';
        $aadhaar_file = '';
        $photo_file = '';
        $signature_file = '';

        if (isset($_FILES['marksheet10']) && $_FILES['marksheet10']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['marksheet10']['name'], PATHINFO_EXTENSION));
            $marksheet10_file = 'm10_' . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['marksheet10']['tmp_name'], $upload_dir . $marksheet10_file);
        }

        if (isset($_FILES['marksheet12']) && $_FILES['marksheet12']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['marksheet12']['name'], PATHINFO_EXTENSION));
            $marksheet12_file = 'm12_' . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['marksheet12']['tmp_name'], $upload_dir . $marksheet12_file);
        }

        if (isset($_FILES['aadhaar_card']) && $_FILES['aadhaar_card']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['aadhaar_card']['name'], PATHINFO_EXTENSION));
            $aadhaar_file = 'aadh_' . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['aadhaar_card']['tmp_name'], $upload_dir . $aadhaar_file);
        }

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $photo_file = 'photo_' . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_file);

            if ($student_db_id > 0) {
                $upd_p = $pdo->prepare("UPDATE students SET photo = ? WHERE id = ?");
                $upd_p->execute(['uploads/admissions/' . $photo_file, $student_db_id]);
            }
        }

        if (isset($_FILES['signature']) && $_FILES['signature']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION));
            $signature_file = 'sign_' . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['signature']['tmp_name'], $upload_dir . $signature_file);
        }

        // 1. Insert into admissions table
        $ins_adm = $pdo->prepare("INSERT INTO admissions 
            (student_id, name, father_name, dob, gender, aadhaar, mobile, email, address, pincode, education, board_university, passing_year, percentage, course, batch_time, batch_type, marksheet10, marksheet12, aadhaar_card, photo, signature, status, fee_total, fee_paid, fee_pending, payment_plan, payment_status, created_at, is_read)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Approved', ?, ?, ?, ?, ?, NOW(), 1)");
        
        $ins_adm->execute([
            $student_id, $name, $father_name, $dob, $gender, $aadhaar, $mobile, $email, $address, $pincode,
            $education, $board_university, $passing_year, $percentage, $course['title'], $batch_time, $batch_type,
            $marksheet10_file, $marksheet12_file, $aadhaar_file, $photo_file, $signature_file,
            $final_course_total, $paid_amount, $pending_balance, $payment_plan, $p_status
        ]);
        $created_admission_id = (int)$pdo->lastInsertId();

        // 2. Insert installments records
        $next_due_date = null;
        $next_due_amount = 0;

        if ($payment_plan === 'full') {
            $ins_inst = $pdo->prepare("INSERT INTO student_installments 
                (student_db_id, student_id, admission_id, course_title, total_course_fee, discount_amount, final_payable, payment_type, total_parts, installment_no, installment_title, installment_amount, due_date, paid_date, status, payment_id, receipt_no)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'full', 1, 1, 'Full Course Fee Payment (Offline Entry)', ?, CURDATE(), NOW(), 'Paid', ?, ?)");
            $ins_inst->execute([
                $student_db_id, $student_id, $created_admission_id, $course['title'],
                $base_fee, $discount_amount, $final_course_total, $paid_amount, $pay_ref, $receipt_no
            ]);
        } else {
            // Determine parts from duration
            $dur_lower = strtolower($course['duration'] ?? '');
            $total_parts = 2;
            if (strpos($dur_lower, '12') !== false || strpos($dur_lower, '1 year') !== false) {
                $total_parts = 4;
            } elseif (strpos($dur_lower, '6') !== false) {
                $total_parts = 3;
            }

            $part_amount = round($base_fee / $total_parts, 2);
            $months_interval = max(1, (int)(12 / max(1, $total_parts)));
            if (strpos($dur_lower, '6') !== false) $months_interval = 2;
            if (strpos($dur_lower, '3') !== false || strpos($dur_lower, '2') !== false) $months_interval = 1;

            for ($i = 1; $i <= $total_parts; $i++) {
                $due_d = ($i === 1) ? date('Y-m-d') : date('Y-m-d', strtotime("+" . (($i - 1) * $months_interval) . " months"));
                $is_p1 = ($i === 1);
                $st = $is_p1 ? 'Paid' : 'Pending';
                $p_id = $is_p1 ? $pay_ref : null;
                $r_no = $is_p1 ? $receipt_no : null;
                $p_date = $is_p1 ? date('Y-m-d H:i:s') : null;

                if ($i === 2) {
                    $next_due_date = $due_d;
                    $next_due_amount = $part_amount;
                }

                $ins_inst = $pdo->prepare("INSERT INTO student_installments 
                    (student_db_id, student_id, admission_id, course_title, total_course_fee, discount_amount, final_payable, payment_type, total_parts, installment_no, installment_title, installment_amount, due_date, paid_date, status, payment_id, receipt_no) 
                    VALUES (?, ?, ?, ?, ?, 0, ?, 'installment', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $title_str = ($i === 1) ? '1st Installment (Admission Down-Payment)' : ($i . 'th Installment');
                $ins_inst->execute([
                    $student_db_id, $student_id, $created_admission_id, $course['title'],
                    $base_fee, $base_fee, $total_parts, $i, $title_str, $part_amount, $due_d, $p_date, $st, $p_id, $r_no
                ]);
            }
        }

        // 3. Log in fee_submissions table
        $fee_log = $pdo->prepare("INSERT INTO fee_submissions 
            (name, contact, email, fee_type, purpose, course, receipt, status, is_read) 
            VALUES (?, ?, ?, 'Course Admission Fee', ?, ?, ?, 'Verified', 1)");
        $purpose_txt = ($payment_plan === 'full') ? "Full Course Fee (Manual Offline Entry)" : "1st Installment (Manual Offline Entry)";
        $fee_log->execute([$name, $mobile, $email, $purpose_txt, $course['title'], "{$payment_mode}: {$pay_ref} | Receipt: {$receipt_no}"]);

        // 4. Update student record
        $upd_std = $pdo->prepare("UPDATE students SET 
            seat_status = 'Confirmed',
            payment_status = 'Paid',
            payment_id = ?,
            payment_amount = payment_amount + ?,
            payment_date = NOW()
            WHERE id = ?");
        $upd_std->execute([$pay_ref, $paid_amount, $student_db_id]);

        $pdo->commit();

        $created_receipt_no = $receipt_no;

        // 5. Send Student Receipt & Admin Notification Emails
        $receiptDetails = [
            'receipt_no'       => $receipt_no,
            'plan_title'       => ($payment_plan === 'full') ? "Full Course Fee ({$full_payment_discount_percent}% Discount Applied)" : 'Part-wise Installment Plan',
            'total_fee'        => $base_fee,
            'discount_amount'  => $discount_amount,
            'paid_amount'      => $paid_amount,
            'payment_id'       => $pay_ref,
            'pending_balance'  => $pending_balance,
            'next_due_date'    => $next_due_date,
            'next_due_amount'  => $next_due_amount
        ];

        sendCourseFeeReceiptEmail($email, $name, $student_id, $course['title'], $receiptDetails);

        sendAdminFeePaymentNotificationEmail(
            [
                'name'       => $name,
                'student_id' => $student_id,
                'mobile'     => $mobile,
                'email'      => $email
            ],
            [
                'title'    => $course['title'],
                'duration' => $course['duration'] ?? 'N/A'
            ],
            $receiptDetails
        );

        $success_msg = "Manual admission entry for <strong>" . htmlspecialchars($name) . "</strong> ({$student_id}) recorded successfully! Official Receipt #<strong>{$receipt_no}</strong> generated and emails sent.";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_msg = $e->getMessage();
    }
}
?>

<div class="space-y-5 max-w-5xl mx-auto pb-12">

    <!-- Top Action Breadcrumb Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <h2 class="text-lg font-bold text-[#0e1e2e] flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-[#fe7c03]"></i>
                <span>Manual / Offline Admission Entry</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Search an existing registered candidate or enroll a walk-in student with customized fee installments and offline payment tracking.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="admissions.php" class="px-3 py-1.5 rounded text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors no-underline">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Admissions
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if (!empty($success_msg)): ?>
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-md text-sm flex items-start gap-3">
            <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 text-base shrink-0"></i>
            <div class="flex-1">
                <?= $success_msg ?>
                <div class="mt-2.5 flex items-center gap-3">
                    <a href="admissions.php" class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-700 text-white rounded text-xs font-semibold hover:bg-emerald-800 no-underline">
                        View in Admissions List &rarr;
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-md text-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-exclamation text-rose-600 shrink-0"></i>
            <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Main Admission Form -->
    <form method="POST" id="offlineAdmissionForm" enctype="multipart/form-data" class="space-y-5">
        <input type="hidden" name="action" value="save_offline_admission">
        <input type="hidden" name="student_db_id" id="field_student_db_id" value="0">

        <!-- 1. Registered Student Search / Select Card -->
        <div class="bg-white border border-slate-200 rounded-md p-4 sm:p-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider flex items-center gap-2">
                    <span class="w-5 h-5 rounded bg-orange-100 text-[#fe7c03] flex items-center justify-center text-[10px] font-bold">1</span>
                    <span>Student Selection / Quick Auto-fill</span>
                </h3>
                <span class="text-[11px] text-slate-400">Select registered student or type details below</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Search Registered Student (By Name / Student ID / Mobile / Email)
                </label>
                <div class="relative">
                    <select id="studentSelectDropdown" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 bg-white focus:outline-none focus:border-[#fe7c03]">
                        <option value="">-- Or Select / Search from Registered Students --</option>
                        <?php foreach ($all_students as $st): ?>
                            <option value="<?= $st['id'] ?>" 
                                data-student-id="<?= htmlspecialchars($st['student_id'] ?? '') ?>"
                                data-name="<?= htmlspecialchars($st['name'] ?? '') ?>"
                                data-email="<?= htmlspecialchars($st['email'] ?? '') ?>"
                                data-mobile="<?= htmlspecialchars($st['phone'] ?? '') ?>"
                                data-father="<?= htmlspecialchars($st['father_name'] ?? '') ?>"
                                data-gender="<?= htmlspecialchars($st['gender'] ?? 'Male') ?>"
                                data-dob="<?= htmlspecialchars($st['dob'] ?? '') ?>"
                                data-aadhaar="<?= htmlspecialchars($st['aadhaar'] ?? '') ?>"
                                data-address="<?= htmlspecialchars($st['address'] ?? '') ?>"
                                data-pincode="<?= htmlspecialchars($st['pincode'] ?? '') ?>"
                                data-education="<?= htmlspecialchars($st['education'] ?? '') ?>"
                                data-board="<?= htmlspecialchars($st['board_university'] ?? '') ?>"
                                data-year="<?= htmlspecialchars($st['passing_year'] ?? '') ?>"
                                data-percentage="<?= htmlspecialchars($st['percentage'] ?? '') ?>">
                                <?= htmlspecialchars($st['name']) ?> | ID: <?= htmlspecialchars($st['student_id'] ?? 'N/A') ?> | Mob: <?= htmlspecialchars($st['phone'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">
                    Selecting a student will automatically fill all personal &amp; contact fields below.
                </p>
            </div>
        </div>

        <!-- 2. Candidate Personal Information -->
        <div class="bg-white border border-slate-200 rounded-md p-4 sm:p-5 space-y-4">
            <div class="pb-3 border-b border-slate-100">
                <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider flex items-center gap-2">
                    <span class="w-5 h-5 rounded bg-orange-100 text-[#fe7c03] flex items-center justify-center text-[10px] font-bold">2</span>
                    <span>Candidate Information</span>
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Student ID</label>
                    <input type="text" name="student_id" id="field_student_id" placeholder="Auto-generated if blank (e.g. FS-2026-XXXX)" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03] font-mono">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="field_name" required placeholder="Enter student full name" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mobile Contact <span class="text-rose-500">*</span></label>
                    <input type="tel" name="mobile" id="field_mobile" required placeholder="10-digit mobile number" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" id="field_email" required placeholder="student@gmail.com" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Father's Name</label>
                    <input type="text" name="father_name" id="field_father_name" placeholder="Father's full name" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Gender</label>
                    <select name="gender" id="field_gender" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 bg-white focus:outline-none focus:border-[#fe7c03]">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Date of Birth</label>
                    <input type="date" name="dob" id="field_dob" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Aadhaar Card Number</label>
                    <input type="text" name="aadhaar" id="field_aadhaar" placeholder="12-digit Aadhaar" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Address</label>
                    <input type="text" name="address" id="field_address" placeholder="Full residential address" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Pincode</label>
                    <input type="text" name="pincode" id="field_pincode" placeholder="e.g. 110001" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Highest Qualification</label>
                    <input type="text" name="education" id="field_education" placeholder="e.g. B.Tech / BCA / 12th" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Board / University</label>
                    <input type="text" name="board_university" id="field_board" placeholder="e.g. Delhi University / CBSE" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Passing Year &amp; %</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="passing_year" id="field_year" placeholder="Year" class="w-full text-xs rounded border border-slate-200 px-2 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                        <input type="text" name="percentage" id="field_percentage" placeholder="Marks %" class="w-full text-xs rounded border border-slate-200 px-2 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Course Allocation & Fee Calculation -->
        <div class="bg-white border border-slate-200 rounded-md p-4 sm:p-5 space-y-4">
            <div class="pb-3 border-b border-slate-100">
                <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider flex items-center gap-2">
                    <span class="w-5 h-5 rounded bg-orange-100 text-[#fe7c03] flex items-center justify-center text-[10px] font-bold">3</span>
                    <span>Course Allocation &amp; Payment Structure</span>
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select Course <span class="text-rose-500">*</span></label>
                    <select name="course_id" id="courseSelector" required class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 bg-white focus:outline-none focus:border-[#fe7c03]">
                        <option value="">-- Select Course --</option>
                        <?php foreach ($all_courses as $c): ?>
                            <option value="<?= $c['id'] ?>" 
                                data-fee="<?= (float)$c['fee'] ?>" 
                                data-duration="<?= htmlspecialchars($c['duration'] ?? '3 Months') ?>">
                                <?= htmlspecialchars($c['title']) ?> (<?= htmlspecialchars($c['duration'] ?? 'N/A') ?>) - ₹<?= number_format((float)$c['fee'], 2) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Batch Mode / Timing</label>
                    <select name="batch_type" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 bg-white focus:outline-none focus:border-[#fe7c03]">
                        <option value="Offline Classroom">Offline Classroom</option>
                        <option value="Live Online">Live Online</option>
                        <option value="Hybrid (Classroom + Online)">Hybrid (Classroom + Online)</option>
                        <option value="Weekend Special">Weekend Special</option>
                    </select>
                </div>
            </div>

            <!-- Live Fee Calculation Widget -->
            <div id="feeCalcCard" class="mt-3 bg-slate-50 border border-slate-200 rounded-md p-4 hidden">
                <div class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider mb-3 flex items-center justify-between">
                    <span>Fee Payment Structure</span>
                    <span id="courseDurationBadge" class="px-2 py-0.5 bg-slate-200 text-slate-700 text-[10px] rounded font-semibold">Duration: 3 Months</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                    <!-- Option 1: Full Payment -->
                    <label class="cursor-pointer border border-slate-200 bg-white rounded-md p-3 flex items-start gap-3 hover:border-[#fe7c03] transition-colors relative">
                        <input type="radio" name="payment_plan" value="full" checked class="mt-0.5 text-[#fe7c03] focus:ring-0">
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#0e1e2e]">Full Payment in Advance</span>
                                <span class="text-[10px] font-bold bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">
                                    <?= $full_payment_discount_percent ?>% OFF
                                </span>
                            </div>
                            <div class="mt-1">
                                <span class="text-base font-bold text-emerald-600" id="fullPriceDisplay">₹0</span>
                                <span class="text-[11px] text-slate-400 line-through ms-1.5" id="originalPriceDisplay">₹0</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">One-time payment. Zero further balance.</p>
                        </div>
                    </label>

                    <!-- Option 2: Part-wise Installments -->
                    <label class="cursor-pointer border border-slate-200 bg-white rounded-md p-3 flex items-start gap-3 hover:border-[#fe7c03] transition-colors relative">
                        <input type="radio" name="payment_plan" value="installment" class="mt-0.5 text-[#fe7c03] focus:ring-0">
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#0e1e2e]">Installment Plan</span>
                                <span class="text-[10px] font-bold bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded" id="installmentBadge">
                                    2 Parts
                                </span>
                            </div>
                            <div class="mt-1">
                                <span class="text-base font-bold text-blue-600" id="installmentFirstPayDisplay">₹0</span>
                                <span class="text-[11px] text-slate-500 ms-1">(1st Down-Payment)</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5" id="installmentDesc">Pay remaining in flexible parts.</p>
                        </div>
                    </label>
                </div>

                <!-- Installment Schedule Breakdown Table -->
                <div id="installmentScheduleBox" class="bg-white border border-slate-200 rounded p-3 text-xs hidden">
                    <p class="font-bold text-slate-700 uppercase tracking-wider text-[11px] mb-2">Calculated Installment Breakdown:</p>
                    <div id="scheduleRowsContainer" class="space-y-1.5 font-medium"></div>
                </div>
            </div>
        </div>

        <!-- 4. Candidate Document Uploads -->
        <div class="bg-white border border-slate-200 rounded-md p-4 sm:p-5 space-y-4">
            <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider flex items-center gap-2">
                    <span class="w-5 h-5 rounded bg-orange-100 text-[#fe7c03] flex items-center justify-center text-[10px] font-bold">4</span>
                    <span>Document Attachments &amp; Verification (Optional / Walk-in Uploads)</span>
                </h3>
                <span class="text-[11px] text-slate-400">PDF, JPG, PNG, WEBP</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Passport Photo -->
                <div class="border border-slate-200 rounded-md p-3 bg-slate-50/60">
                    <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center gap-1">
                        <i class="fa-solid fa-camera text-[#fe7c03]"></i>
                        <span>Candidate Passport Photo</span>
                    </label>
                    <input type="file" name="photo" accept="image/*" class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-[#fe7c03] file:text-white hover:file:bg-[#e06b00]">
                    <p class="text-[10px] text-slate-400 mt-1">Updates profile photo &amp; ID card badge</p>
                </div>

                <!-- Aadhaar Card -->
                <div class="border border-slate-200 rounded-md p-3 bg-slate-50/60">
                    <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center gap-1">
                        <i class="fa-solid fa-id-card text-sky-600"></i>
                        <span>Aadhaar Card Copy</span>
                    </label>
                    <input type="file" name="aadhaar_card" accept=".pdf,image/*" class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-slate-700 file:text-white hover:file:bg-slate-800">
                    <p class="text-[10px] text-slate-400 mt-1">Aadhaar card front/back or e-Aadhaar PDF</p>
                </div>

                <!-- 10th Marksheet -->
                <div class="border border-slate-200 rounded-md p-3 bg-slate-50/60">
                    <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center gap-1">
                        <i class="fa-solid fa-graduation-cap text-indigo-600"></i>
                        <span>10th / Secondary Marksheet</span>
                    </label>
                    <input type="file" name="marksheet10" accept=".pdf,image/*" class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-slate-700 file:text-white hover:file:bg-slate-800">
                    <p class="text-[10px] text-slate-400 mt-1">High school marksheet or certificate</p>
                </div>

                <!-- 12th / Graduation Marksheet -->
                <div class="border border-slate-200 rounded-md p-3 bg-slate-50/60">
                    <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center gap-1">
                        <i class="fa-solid fa-file-lines text-teal-600"></i>
                        <span>12th / Diploma / Degree Marksheet</span>
                    </label>
                    <input type="file" name="marksheet12" accept=".pdf,image/*" class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-slate-700 file:text-white hover:file:bg-slate-800">
                    <p class="text-[10px] text-slate-400 mt-1">Intermediate or higher qualification proof</p>
                </div>

                <!-- Candidate Signature -->
                <div class="border border-slate-200 rounded-md p-3 bg-slate-50/60">
                    <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center gap-1">
                        <i class="fa-solid fa-signature text-amber-600"></i>
                        <span>Candidate Signature</span>
                    </label>
                    <input type="file" name="signature" accept="image/*" class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-slate-700 file:text-white hover:file:bg-slate-800">
                    <p class="text-[10px] text-slate-400 mt-1">Official signature copy</p>
                </div>
            </div>
        </div>

        <!-- 5. Payment Collection Details -->
        <div class="bg-white border border-slate-200 rounded-md p-4 sm:p-5 space-y-4">
            <div class="pb-3 border-b border-slate-100">
                <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider flex items-center gap-2">
                    <span class="w-5 h-5 rounded bg-orange-100 text-[#fe7c03] flex items-center justify-center text-[10px] font-bold">5</span>
                    <span>Payment Collection &amp; Receipting</span>
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Mode <span class="text-rose-500">*</span></label>
                    <select name="payment_mode" required class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 bg-white focus:outline-none focus:border-[#fe7c03]">
                        <option value="Cash">Cash (In-person at Center)</option>
                        <option value="UPI / QR Scan">UPI / QR Scan (GPay / PhonePe / Paytm)</option>
                        <option value="Bank Transfer (NEFT/RTGS/IMPS)">Bank Transfer (NEFT / RTGS / IMPS)</option>
                        <option value="Cheque / DD">Cheque / Demand Draft</option>
                        <option value="Card Swipe / POS">Card Swipe / POS Machine</option>
                        <option value="Razorpay Payment Link">Razorpay Payment Link / Online</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Amount Paid Now (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="paid_amount" id="field_paid_amount" required placeholder="Amount received" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 font-bold focus:outline-none focus:border-[#fe7c03]">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Transaction Ref / UTR / Cheque No.</label>
                    <input type="text" name="transaction_id" placeholder="e.g. UTR / UPI Ref / Cheque #12345" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 font-mono focus:outline-none focus:border-[#fe7c03]">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Admin Accounting Notes (Optional)</label>
                    <input type="text" name="payment_notes" placeholder="e.g. Received by counselor Nikhil at Head Office" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                </div>
            </div>
        </div>

        <!-- Submit & Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="admissions.php" class="px-4 py-2 rounded text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors no-underline">
                Cancel
            </a>
            <button type="submit" id="submitAdmissionBtn" class="px-5 py-2 rounded bg-[#fe7c03] hover:bg-[#e06b00] text-white text-xs font-bold transition-colors inline-flex items-center gap-2">
                <i class="fa-solid fa-file-invoice"></i>
                <span>Complete Admission &amp; Generate Receipt</span>
            </button>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentDropdown = document.getElementById('studentSelectDropdown');
    const courseSelector = document.getElementById('courseSelector');
    const feeCalcCard = document.getElementById('feeCalcCard');
    const fullPriceDisplay = document.getElementById('fullPriceDisplay');
    const originalPriceDisplay = document.getElementById('originalPriceDisplay');
    const installmentFirstPayDisplay = document.getElementById('installmentFirstPayDisplay');
    const installmentBadge = document.getElementById('installmentBadge');
    const installmentDesc = document.getElementById('installmentDesc');
    const installmentScheduleBox = document.getElementById('installmentScheduleBox');
    const scheduleRowsContainer = document.getElementById('scheduleRowsContainer');
    const fieldPaidAmount = document.getElementById('field_paid_amount');
    const durationBadge = document.getElementById('courseDurationBadge');
    const discountPercent = <?= json_encode($full_payment_discount_percent) ?>;

    // Student Quick Selection Autofill
    studentDropdown.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) {
            document.getElementById('field_student_db_id').value = '0';
            return;
        }

        document.getElementById('field_student_db_id').value = opt.value;
        document.getElementById('field_student_id').value = opt.dataset.studentId || '';
        document.getElementById('field_name').value = opt.dataset.name || '';
        document.getElementById('field_email').value = opt.dataset.email || '';
        document.getElementById('field_mobile').value = opt.dataset.mobile || '';
        document.getElementById('field_father_name').value = opt.dataset.father || '';
        document.getElementById('field_gender').value = opt.dataset.gender || 'Male';
        document.getElementById('field_dob').value = opt.dataset.dob || '';
        document.getElementById('field_aadhaar').value = opt.dataset.aadhaar || '';
        document.getElementById('field_address').value = opt.dataset.address || '';
        document.getElementById('field_pincode').value = opt.dataset.pincode || '';
        document.getElementById('field_education').value = opt.dataset.education || '';
        document.getElementById('field_board').value = opt.dataset.board || '';
        document.getElementById('field_year').value = opt.dataset.year || '';
        document.getElementById('field_percentage').value = opt.dataset.percentage || '';
    });

    // Course Selection & Dynamic Plan Calculation
    function calculateFees() {
        const opt = courseSelector.options[courseSelector.selectedIndex];
        if (!opt || !opt.value) {
            feeCalcCard.classList.add('hidden');
            return;
        }

        feeCalcCard.classList.remove('hidden');
        const baseFee = parseFloat(opt.dataset.fee) || 10000;
        const duration = opt.dataset.duration || '3 Months';
        const durLower = duration.toLowerCase();

        durationBadge.textContent = 'Duration: ' + duration;

        // Discounted Full Pay
        const discountAmt = Math.round((baseFee * discountPercent) / 100);
        const fullPayTotal = baseFee - discountAmt;

        fullPriceDisplay.textContent = '₹' + fullPayTotal.toLocaleString('en-IN');
        originalPriceDisplay.textContent = '₹' + baseFee.toLocaleString('en-IN');

        // Installment calculation
        let parts = 2;
        let intervalMonths = 1;
        if (durLower.includes('12') || durLower.includes('1 year')) {
            parts = 4;
            intervalMonths = 3;
        } else if (durLower.includes('6')) {
            parts = 3;
            intervalMonths = 2;
        }

        const partAmt = Math.round(baseFee / parts);
        installmentBadge.textContent = parts + ' Parts (' + parts + ' Installments)';
        installmentFirstPayDisplay.textContent = '₹' + partAmt.toLocaleString('en-IN');
        installmentDesc.textContent = '₹' + partAmt.toLocaleString('en-IN') + ' x ' + parts + ' installments across course duration.';

        // Build Schedule Table HTML
        let scheduleHtml = '';
        for (let i = 1; i <= parts; i++) {
            const today = new Date();
            today.setMonth(today.getMonth() + (i - 1) * intervalMonths);
            const formattedDate = today.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            const isFirst = (i === 1);

            scheduleHtml += `
                <div class="flex items-center justify-between py-1.5 px-2 rounded ${isFirst ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-50 text-slate-700'}">
                    <div>
                        <strong>Part ${i}:</strong> ${isFirst ? '1st Installment (Paid Now)' : i + 'th Installment (Upcoming)'}
                    </div>
                    <div>
                        <span class="font-bold">₹${partAmt.toLocaleString('en-IN')}</span>
                        <span class="text-[10px] text-slate-500 ms-2">Due: ${isFirst ? 'Today' : formattedDate}</span>
                    </div>
                </div>
            `;
        }
        scheduleRowsContainer.innerHTML = scheduleHtml;

        // Auto set paid amount based on currently checked radio
        const selectedPlan = document.querySelector('input[name="payment_plan"]:checked')?.value || 'full';
        if (selectedPlan === 'full') {
            fieldPaidAmount.value = fullPayTotal;
            installmentScheduleBox.classList.add('hidden');
        } else {
            fieldPaidAmount.value = partAmt;
            installmentScheduleBox.classList.remove('hidden');
        }
    }

    courseSelector.addEventListener('change', calculateFees);

    document.querySelectorAll('input[name="payment_plan"]').forEach(radio => {
        radio.addEventListener('change', function() {
            calculateFees();
        });
    });

    // Form Submit Loader
    document.getElementById('offlineAdmissionForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitAdmissionBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing & Generating Receipt...';
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

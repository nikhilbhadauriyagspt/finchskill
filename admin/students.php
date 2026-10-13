<?php
require_once __DIR__ . '/includes/auth.php';
checkAdminAuth();

$msg = '';
$err = '';

// Run background seat reservation expiry and reminder check (throttled)
require_once __DIR__ . '/../cron-seat-reminders.php';
runSeatReminderEngine($pdo, false);

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $msg = "Candidate record removed successfully.";
    if ($_GET['msg'] === 'seat_updated') $msg = "Seat confirmation status updated successfully.";
    if ($_GET['msg'] === 'seat_reactivated') $msg = "Seat reservation has been re-activated for 7 days! Confirmation email dispatched to candidate.";
    if ($_GET['msg'] === 'seat_extended') $msg = "Seat reservation validity extended! Notification email dispatched to candidate.";
    if ($_GET['msg'] === 'seat_cancelled') $msg = "Seat reservation has been cancelled. Cancellation notice dispatched to candidate.";
    if ($_GET['msg'] === 'payment_updated') $msg = "Payment status updated successfully.";
    if ($_GET['msg'] === 'installment_collected') $msg = "Fee installment collected and marked as Paid successfully! Receipt generated.";
}

// ============================================================
// AJAX ENDPOINT: GET UNIFIED STUDENT 360 & FEE LEDGER DATA
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_ledger' && isset($_GET['student_id'])) {
    header('Content-Type: application/json');
    $sid = trim($_GET['student_id']);
    
    $s_stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? OR student_id = ? LIMIT 1");
    $s_stmt->execute([$sid, $sid]);
    $stu = $s_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stu) {
        echo json_encode(['success' => false, 'message' => 'Candidate record not found.']);
        exit;
    }

    // Admissions / Enrolled Courses (Match by linked admission_id, student_id, or email)
    $adm_stmt = $pdo->prepare("SELECT * FROM admissions WHERE (id = ? AND id > 0) OR (student_id = ? AND student_id != '') OR (email = ? AND email != '') ORDER BY id DESC");
    $adm_stmt->execute([(int)($stu['admission_id'] ?? 0), $stu['student_id'], $stu['email']]);
    $admissions = $adm_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Installments: match by student_db_id, student_id, or admission_id
    $adm_ids = array_filter(array_column($admissions, 'id'));
    $where_clauses = ["student_db_id = ?", "student_id = ?"];
    $where_params = [$stu['id'], $stu['student_id']];
    if (!empty($adm_ids)) {
        $in_adm = implode(',', array_fill(0, count($adm_ids), '?'));
        $where_clauses[] = "admission_id IN ($in_adm)";
        $where_params = array_merge($where_params, $adm_ids);
    }
    $ins_sql = "SELECT * FROM student_installments WHERE (" . implode(" OR ", $where_clauses) . ") ORDER BY due_date ASC, id ASC";
    $ins_stmt = $pdo->prepare($ins_sql);
    $ins_stmt->execute($where_params);
    $installments = $ins_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Totals
    $total_fees = 0.0;
    $total_paid = 0.0;
    $total_pending = 0.0;
    $total_seat_adjusted = 0.0;

    if (!empty($admissions)) {
        foreach ($admissions as $a) {
            $total_fees += (float)$a['fee_total'];
            $total_paid += (float)$a['fee_paid'];
            $total_pending += (float)$a['fee_pending'];
            $total_seat_adjusted += (float)($a['seat_fee_adjusted'] ?? 0);
        }
    } elseif (!empty($installments)) {
        $total_fees = (float)($installments[0]['total_course_fee'] ?? 0);
        $total_seat_adjusted = (float)($installments[0]['seat_fee_adjusted'] ?? 0);
        foreach ($installments as $ins) {
            if ($ins['status'] === 'Paid') {
                $total_paid += (float)$ins['installment_amount'];
            } else {
                $total_pending += (float)$ins['installment_amount'];
            }
        }
        // Synthesize virtual admission record so Enrolled Programs table is not blank
        $first = $installments[0];
        $admissions = [[
            'id' => $first['admission_id'] ?? 0,
            'student_id' => $stu['student_id'],
            'name' => $stu['name'],
            'course' => $first['course_title'] ?? $stu['booked_course'] ?? 'Enrolled Program',
            'enrollment_no' => $first['enrollment_no'] ?? '',
            'fee_total' => $total_fees,
            'seat_fee_adjusted' => $total_seat_adjusted,
            'fee_paid' => $total_paid,
            'fee_pending' => $total_pending,
            'payment_plan' => $first['installment_title'] ?? 'Full Course Fee Payment',
            'status' => ($total_pending <= 0) ? 'Approved' : 'Pending',
            'created_at' => $first['created_at'] ?? $stu['created_at']
        ]];
    }

    // Photo check
    $photo_url = (!empty($stu['photo']) && file_exists(__DIR__ . '/../' . $stu['photo'])) ? ('../' . $stu['photo']) : '';

    $admin_seat_fee_val = (float)getSetting('seat_booking_fee', '999');
    $seat_fee_display = ($stu['payment_status'] === 'Paid' || $stu['seat_status'] === 'Confirmed')
        ? (((float)$stu['payment_amount'] > 0 && (float)$stu['payment_amount'] <= 5000) ? (float)$stu['payment_amount'] : $admin_seat_fee_val)
        : 0.0;

    echo json_encode([
        'success' => true,
        'student' => [
            'id' => $stu['id'],
            'student_id' => $stu['student_id'],
            'name' => $stu['name'],
            'email' => $stu['email'],
            'phone' => $stu['phone'],
            'aadhaar' => $stu['aadhaar'],
            'photo' => $photo_url,
            'seat_status' => $stu['seat_status'],
            'payment_status' => $stu['payment_status'],
            'payment_id' => $stu['payment_id'],
            'payment_amount' => $seat_fee_display,
            'payment_date' => $stu['payment_date'],
            'seat_valid_until' => !empty($stu['seat_valid_until']) ? date('d M Y, h:i A', strtotime($stu['seat_valid_until'])) : null,
            'seat_cancelled_at' => !empty($stu['seat_cancelled_at']) ? date('d M Y, h:i A', strtotime($stu['seat_cancelled_at'])) : null,
            'seat_reactivated_at' => !empty($stu['seat_reactivated_at']) ? date('d M Y, h:i A', strtotime($stu['seat_reactivated_at'])) : null,
            'is_expired' => (!empty($stu['seat_valid_until']) && strtotime($stu['seat_valid_until']) < time() && empty($stu['admission_id'])),
            'booked_course' => $stu['booked_course'],
            'created_at' => date('d M Y, h:i A', strtotime($stu['created_at']))
        ],
        'financials' => [
            'total_fees' => $total_fees,
            'total_paid' => $total_paid,
            'total_pending' => $total_pending,
            'total_seat_adjusted' => $total_seat_adjusted,
            'formatted_fees' => '₹' . number_format($total_fees, 2),
            'formatted_paid' => '₹' . number_format($total_paid, 2),
            'formatted_pending' => '₹' . number_format($total_pending, 2),
            'formatted_seat_adjusted' => '₹' . number_format($total_seat_adjusted, 2)
        ],
        'admissions' => $admissions,
        'installments' => $installments
    ]);
    exit;
}

// ============================================================
// ADMIN ACTIONS BEFORE HEADER OUTPUT
// ============================================================
// 1. Delete Candidate
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT student_id, photo FROM students WHERE id = ?");
    $stmt->execute([$target_id]);
    $cand = $stmt->fetch();
    if ($cand) {
        if (!empty($cand['photo']) && file_exists(__DIR__ . '/../' . $cand['photo'])) {
            @unlink(__DIR__ . '/../' . $cand['photo']);
        }
        if (!empty($cand['student_id'])) {
            $pdo->prepare("DELETE FROM student_installments WHERE student_id = ?")->execute([$cand['student_id']]);
        }
    }

    $del = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $del->execute([$target_id]);
    header("Location: students.php?msg=deleted");
    exit;
}

// 2. Re-activate Seat Reservation (+7 Days & Email Notification)
if (isset($_GET['action']) && $_GET['action'] === 'reactivate_seat' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$target_id]);
    $cand = $stmt->fetch();
    if ($cand) {
        $val_stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'seat_validity_days' LIMIT 1");
        $days = (int)($val_stmt->fetchColumn() ?: 7);
        if ($days <= 0) $days = 7;
        $new_valid_until = date('Y-m-d H:i:s', strtotime("+{$days} days"));

        $upd = $pdo->prepare("UPDATE students SET 
            seat_status = 'Confirmed',
            payment_status = 'Paid',
            seat_valid_until = ?,
            seat_reminder_day2_at = NULL,
            seat_reminder_day4_at = NULL,
            seat_reminder_final_at = NULL,
            seat_cancelled_at = NULL,
            seat_reactivated_at = NOW(),
            seat_reactivated_by = ?
            WHERE id = ?");
        $admin_name = $_SESSION['admin_username'] ?? 'Admin';
        $upd->execute([$new_valid_until, $admin_name, $target_id]);

        require_once __DIR__ . '/../mail-helper.php';
        sendSeatReactivatedEmail($cand, $new_valid_until);

        header("Location: students.php?msg=seat_reactivated");
        exit;
    }
}

// 3. Extend Seat Validity (+X Days)
if (isset($_GET['action']) && $_GET['action'] === 'extend_seat' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $add_days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
    if ($add_days <= 0) $add_days = 7;

    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$target_id]);
    $cand = $stmt->fetch();
    if ($cand) {
        $base_ts = (!empty($cand['seat_valid_until']) && strtotime($cand['seat_valid_until']) > time()) 
            ? strtotime($cand['seat_valid_until']) 
            : time();
        $new_valid_until = date('Y-m-d H:i:s', strtotime("+{$add_days} days", $base_ts));

        $upd = $pdo->prepare("UPDATE students SET 
            seat_status = 'Confirmed',
            seat_valid_until = ?,
            seat_reminder_final_at = NULL,
            seat_cancelled_at = NULL,
            seat_reactivated_at = NOW(),
            seat_reactivated_by = ?
            WHERE id = ?");
        $admin_name = $_SESSION['admin_username'] ?? 'Admin';
        $upd->execute([$new_valid_until, $admin_name, $target_id]);

        require_once __DIR__ . '/../mail-helper.php';
        sendSeatReactivatedEmail($cand, $new_valid_until);

        header("Location: students.php?msg=seat_extended");
        exit;
    }
}

// 4. Cancel Seat Manually
if (isset($_GET['action']) && $_GET['action'] === 'cancel_seat' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$target_id]);
    $cand = $stmt->fetch();
    if ($cand) {
        $upd = $pdo->prepare("UPDATE students SET 
            seat_status = 'Cancelled',
            seat_cancelled_at = NOW()
            WHERE id = ?");
        $upd->execute([$target_id]);

        require_once __DIR__ . '/../mail-helper.php';
        sendSeatCancelledEmail($cand);

        header("Location: students.php?msg=seat_cancelled");
        exit;
    }
}

// 5. Toggle Seat Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_seat' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT seat_status, payment_date FROM students WHERE id = ?");
    $stmt->execute([$target_id]);
    $cand = $stmt->fetch();
    if ($cand) {
        $new_seat = ($cand['seat_status'] === 'Confirmed') ? 'Pending' : 'Confirmed';
        $new_valid = ($new_seat === 'Confirmed') ? date('Y-m-d H:i:s', strtotime('+7 days')) : null;
        $upd = $pdo->prepare("UPDATE students SET seat_status = ?, seat_valid_until = COALESCE(seat_valid_until, ?) WHERE id = ?");
        $upd->execute([$new_seat, $new_valid, $target_id]);
    }
    header("Location: students.php?msg=seat_updated");
    exit;
}

// 3. Toggle Payment Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_payment' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT payment_status FROM students WHERE id = ?");
    $stmt->execute([$target_id]);
    $cand = $stmt->fetch();
    if ($cand) {
        $new_pay = ($cand['payment_status'] === 'Paid') ? 'Unpaid' : 'Paid';
        $upd = $pdo->prepare("UPDATE students SET payment_status = ?, seat_status = CASE WHEN ? = 'Paid' THEN 'Confirmed' ELSE seat_status END WHERE id = ?");
        $upd->execute([$new_pay, $new_pay, $target_id]);
    }
    header("Location: students.php?msg=payment_updated");
    exit;
}

// 4. Collect Offline Installment Fee (Cash / UPI / Bank)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_collect_installment'])) {
    $inst_id = (int)($_POST['installment_id'] ?? 0);
    $pay_mode = trim($_POST['payment_mode'] ?? 'Cash');
    $pay_ref = trim($_POST['payment_ref'] ?? ('OFFLINE-' . strtoupper(substr(md5(uniqid()), 0, 8))));

    $i_stmt = $pdo->prepare("SELECT * FROM student_installments WHERE id = ?");
    $i_stmt->execute([$inst_id]);
    $inst = $i_stmt->fetch(PDO::FETCH_ASSOC);

    if ($inst && $inst['status'] !== 'Paid') {
        $receipt_no = "REC-" . strtoupper(substr(md5($inst_id . time() . rand(100,999)), 0, 8));
        
        $pdo->beginTransaction();

        $full_ref = "{$pay_mode}: {$pay_ref}";
        $upd_i = $pdo->prepare("UPDATE student_installments SET 
            status = 'Paid',
            paid_date = NOW(),
            payment_id = ?,
            receipt_no = ?
            WHERE id = ?");
        $upd_i->execute([$full_ref, $receipt_no, $inst_id]);

        $upd_a = $pdo->prepare("UPDATE admissions SET 
            fee_paid = fee_paid + ?,
            fee_pending = GREATEST(0, fee_pending - ?),
            payment_status = CASE WHEN fee_pending <= ? THEN 'Fully Paid' ELSE 'Partial Paid' END
            WHERE id = ?");
        $upd_a->execute([$inst['installment_amount'], $inst['installment_amount'], $inst['installment_amount'], $inst['admission_id']]);

        $a_stmt = $pdo->prepare("SELECT * FROM admissions WHERE id = ?");
        $a_stmt->execute([$inst['admission_id']]);
        $adm = $a_stmt->fetch(PDO::FETCH_ASSOC);

        $fee_log = $pdo->prepare("INSERT INTO fee_submissions 
            (name, contact, email, fee_type, purpose, course, receipt, status, is_read) 
            VALUES (?, ?, ?, 'Installment Fee', ?, ?, ?, 'Verified', 0)");
        $fee_log->execute([
            $adm['name'] ?? 'Student',
            $adm['mobile'] ?? '',
            $adm['email'] ?? '',
            "{$inst['installment_title']} ({$pay_mode}) - Enr: {$inst['enrollment_no']}",
            $inst['course_title'],
            "Ref: {$full_ref} | Receipt: {$receipt_no}"
        ]);

        $pdo->commit();

        header("Location: students.php?msg=installment_collected");
        exit;
    }
}

// Fetch Counts for KPI Cards
$kpi_stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified,
    SUM(CASE WHEN seat_status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN seat_status = 'Confirmed' AND (admission_id IS NULL OR admission_id = 0) AND seat_valid_until IS NOT NULL AND seat_valid_until > NOW() AND seat_valid_until <= DATE_ADD(NOW(), INTERVAL 2 DAY) THEN 1 ELSE 0 END) as expiring_soon,
    SUM(CASE WHEN seat_status IN ('Cancelled', 'Expired') THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) as paid,
    SUM(CASE WHEN payment_status = 'Unpaid' OR payment_status = 'Skipped' THEN 1 ELSE 0 END) as unpaid
FROM students");
$kpi = $kpi_stmt->fetch(PDO::FETCH_ASSOC);

// Search & Filter Query
$search = trim($_GET['search'] ?? '');
$filter_seat = trim($_GET['seat_status'] ?? '');
$filter_payment = trim($_GET['payment_status'] ?? '');

$query = "SELECT s.*, 
    (SELECT GROUP_CONCAT(DISTINCT enrollment_no SEPARATOR ', ') FROM admissions WHERE (id = s.admission_id OR student_id = s.student_id OR email = s.email) AND enrollment_no IS NOT NULL AND enrollment_no != '') as enrollment_numbers,
    (SELECT GROUP_CONCAT(DISTINCT course SEPARATOR ', ') FROM admissions WHERE (id = s.admission_id OR student_id = s.student_id OR email = s.email)) as enrolled_courses,
    (SELECT COALESCE(SUM(fee_total), 0) FROM admissions WHERE (id = s.admission_id OR student_id = s.student_id OR email = s.email)) as total_course_fee,
    (SELECT COALESCE(SUM(fee_paid), 0) FROM admissions WHERE (id = s.admission_id OR student_id = s.student_id OR email = s.email)) as total_fee_paid,
    (SELECT COALESCE(SUM(fee_pending), 0) FROM admissions WHERE (id = s.admission_id OR student_id = s.student_id OR email = s.email)) as total_fee_pending
    FROM students s WHERE 1=1";
$params = [];

if (!empty($search)) {
    $term = "%$search%";
    $query .= " AND (
        s.name LIKE ? 
        OR s.student_id LIKE ? 
        OR s.email LIKE ? 
        OR s.phone LIKE ? 
        OR s.aadhaar LIKE ? 
        OR s.booked_course LIKE ?
        OR s.student_id IN (SELECT student_id FROM admissions WHERE enrollment_no LIKE ? OR course LIKE ?)
    )";
    $params = array_merge($params, [$term, $term, $term, $term, $term, $term, $term, $term]);
}
if (!empty($filter_seat)) {
    if ($filter_seat === 'Expiring_Soon') {
        $query .= " AND s.seat_status = 'Confirmed' AND (s.admission_id IS NULL OR s.admission_id = 0) AND s.seat_valid_until IS NOT NULL AND s.seat_valid_until > NOW() AND s.seat_valid_until <= DATE_ADD(NOW(), INTERVAL 2 DAY)";
    } elseif ($filter_seat === 'Cancelled') {
        $query .= " AND s.seat_status IN ('Cancelled', 'Expired')";
    } else {
        $query .= " AND s.seat_status = ?";
        $params[] = $filter_seat;
    }
}
if (!empty($filter_payment)) {
    $query .= " AND s.payment_status = ?";
    $params[] = $filter_payment;
}

$query .= " ORDER BY s.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch admin seat booking fee
$sbf_stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'seat_booking_fee' LIMIT 1");
$admin_seat_fee = (float)($sbf_stmt->fetchColumn() ?: 999.00);

$page_title = "Student Directory & Fee Ledger";
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-4">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-md border border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-[#0e1e2e]">Student Master Directory &amp; Unified Fee Ledger</h2>
                <span class="px-2 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-600 rounded border border-slate-200">
                    <?= count($students) ?> Students Listed
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Manage student 360° profiles, track 7-day seat booking validities, re-activate expired seats, and inspect complete fee installment ledgers.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="fees.php" class="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:text-[#fe7c03] rounded-md transition-colors flex items-center gap-1 no-underline">
                <i class="fa-solid fa-receipt text-slate-400"></i>
                <span>Fee Submissions</span>
            </a>
            <a href="payment-settings.php" class="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:text-[#fe7c03] rounded-md transition-colors flex items-center gap-1 no-underline">
                <i class="fa-solid fa-credit-card text-slate-400"></i>
                <span>Gateway Setup</span>
            </a>
            <a href="../register.php" target="_blank" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors flex items-center gap-1 no-underline">
                <i class="fa-solid fa-user-plus text-[11px]"></i>
                <span>Public Register</span>
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

    <!-- KPI STATS CARDS (5-METRIC GRID) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <!-- Total Registered -->
        <a href="students.php" class="bg-white border border-slate-200 hover:border-slate-300 rounded-md p-3.5 no-underline transition-all block">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-slate-400">Total Registered</span>
                <span class="w-7 h-7 rounded bg-orange-50 text-[#fe7c03] flex items-center justify-center text-xs"><i class="fa-solid fa-users"></i></span>
            </div>
            <h4 class="text-xl font-bold text-[#0e1e2e]"><?= (int)($kpi['total'] ?? 0) ?></h4>
            <p class="text-[10px] text-slate-400 mt-1"><?= (int)($kpi['verified'] ?? 0) ?> Verified Candidates</p>
        </a>

        <!-- Confirmed Active Seats -->
        <a href="students.php?seat_status=Confirmed" class="bg-white border <?= $filter_seat === 'Confirmed' ? 'border-emerald-500 ring-1 ring-emerald-500' : 'border-slate-200 hover:border-emerald-300' ?> rounded-md p-3.5 no-underline transition-all block">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-slate-400">Confirmed Seats</span>
                <span class="w-7 h-7 rounded bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs"><i class="fa-solid fa-chair"></i></span>
            </div>
            <h4 class="text-xl font-bold text-emerald-700"><?= (int)($kpi['confirmed'] ?? 0) ?></h4>
            <p class="text-[10px] text-emerald-600 font-medium mt-1">₹<?= number_format($admin_seat_fee, 0) ?> 100% Adjusted</p>
        </a>

        <!-- Expiring Soon (Urgent <= 2 Days) -->
        <a href="students.php?seat_status=Expiring_Soon" class="bg-white border <?= $filter_seat === 'Expiring_Soon' ? 'border-amber-500 ring-1 ring-amber-500' : 'border-slate-200 hover:border-amber-300' ?> rounded-md p-3.5 no-underline transition-all block">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-amber-700">Expiring Soon</span>
                <span class="w-7 h-7 rounded bg-amber-50 text-amber-600 flex items-center justify-center text-xs"><i class="fa-solid fa-hourglass-end"></i></span>
            </div>
            <h4 class="text-xl font-bold text-amber-600"><?= (int)($kpi['expiring_soon'] ?? 0) ?></h4>
            <p class="text-[10px] text-amber-600 font-medium mt-1">Urgent: &le; 48 Hours Left</p>
        </a>

        <!-- Cancelled / Expired Seats -->
        <a href="students.php?seat_status=Cancelled" class="bg-white border <?= $filter_seat === 'Cancelled' ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-200 hover:border-rose-300' ?> rounded-md p-3.5 no-underline transition-all block">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-rose-700">Cancelled / Expired</span>
                <span class="w-7 h-7 rounded bg-rose-50 text-rose-600 flex items-center justify-center text-xs"><i class="fa-solid fa-ban"></i></span>
            </div>
            <h4 class="text-xl font-bold text-rose-600"><?= (int)($kpi['cancelled'] ?? 0) ?></h4>
            <p class="text-[10px] text-rose-500 font-medium mt-1">Admin Can Re-activate (+7D)</p>
        </a>

        <!-- Seat Fees Paid -->
        <a href="students.php?payment_status=Paid" class="bg-white border <?= $filter_payment === 'Paid' ? 'border-sky-500 ring-1 ring-sky-500' : 'border-slate-200 hover:border-sky-300' ?> rounded-md p-3.5 no-underline transition-all block">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-slate-400">Seat Fees Paid</span>
                <span class="w-7 h-7 rounded bg-sky-50 text-sky-600 flex items-center justify-center text-xs"><i class="fa-solid fa-receipt"></i></span>
            </div>
            <h4 class="text-xl font-bold text-sky-700"><?= (int)($kpi['paid'] ?? 0) ?></h4>
            <p class="text-[10px] text-sky-600 font-medium mt-1">Online &amp; Verified</p>
        </a>
    </div>

    <!-- UNIFIED FILTER & SEARCH BAR -->
    <div class="bg-white border border-slate-200 rounded-md p-3">
        <form method="GET" action="students.php" class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <!-- Seat Status -->
                <select name="seat_status" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-md px-2 py-1.5 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                    <option value="">All Seat Statuses</option>
                    <option value="Confirmed" <?= $filter_seat === 'Confirmed' ? 'selected' : '' ?>>Confirmed Seats (Active)</option>
                    <option value="Expiring_Soon" <?= $filter_seat === 'Expiring_Soon' ? 'selected' : '' ?>>⚠️ Expiring Soon (&le; 48h)</option>
                    <option value="Cancelled" <?= $filter_seat === 'Cancelled' ? 'selected' : '' ?>>🚫 Cancelled / Expired Seats</option>
                    <option value="Reserved" <?= $filter_seat === 'Reserved' ? 'selected' : '' ?>>Reserved (Unpaid)</option>
                    <option value="Pending" <?= $filter_seat === 'Pending' ? 'selected' : '' ?>>Pending Reservation</option>
                </select>

                <!-- Payment Status -->
                <select name="payment_status" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-md px-2 py-1.5 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                    <option value="">All Seat Payments</option>
                    <option value="Paid" <?= $filter_payment === 'Paid' ? 'selected' : '' ?>>Paid (₹<?= number_format($admin_seat_fee, 0) ?>)</option>
                    <option value="Unpaid" <?= $filter_payment === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                    <option value="Skipped" <?= $filter_payment === 'Skipped' ? 'selected' : '' ?>>Pay Later</option>
                </select>

                <?php if (!empty($filter_seat) || !empty($filter_payment) || !empty($search)): ?>
                    <a href="students.php" class="px-2 py-1 text-xs text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded transition-colors flex items-center gap-1 no-underline">
                        <i class="fa-solid fa-xmark text-[10px]"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>

            <!-- Global Unified Search Input -->
            <div class="relative min-w-[320px] flex-1 max-w-md">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" placeholder="Search Student ID (FS-...), Enrollment No, Mobile, Name..." value="<?= htmlspecialchars($search) ?>" class="w-full text-xs pl-9 pr-3 py-1.5 bg-white border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03] text-slate-800">
            </div>
        </form>
    </div>

    <!-- STUDENTS MASTER TABLE -->
    <div class="bg-white border border-slate-200 rounded-md overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[1260px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-4 py-3" style="width: 17%; min-width: 190px;">Candidate</th>
                        <th class="px-3 py-3" style="width: 10%; min-width: 110px;">Student ID</th>
                        <th class="px-4 py-3" style="width: 18%; min-width: 200px;">Seat Reservation</th>
                        <th class="px-4 py-3" style="width: 18%; min-width: 200px;">Enrolled Program &amp; ENR</th>
                        <th class="px-4 py-3" style="width: 12%; min-width: 130px;">Course Ledger</th>
                        <th class="px-4 py-3" style="width: 13%; min-width: 155px;">Contact Info</th>
                        <th class="px-3 py-3" style="width: 6%; min-width: 95px;">Registered</th>
                        <th class="px-4 py-3 text-right" style="width: 6%; min-width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                                <i class="fa-solid fa-users-slash text-2xl mb-1 text-slate-300 block"></i>
                                <span class="font-semibold text-slate-700 block">No students found</span>
                                <span class="text-[11px]">No student records match your search criteria. Try searching by Student ID or Enrollment Number.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $stu): ?>
                            <?php
                            $clean_phone = preg_replace('/[^0-9]/', '', $stu['phone']);
                            $wa_link = "https://wa.me/91" . substr($clean_phone, -10);
                            $photo_src = (!empty($stu['photo']) && file_exists(__DIR__ . '/../' . $stu['photo'])) ? '../' . $stu['photo'] : '';
                            $enr_list = !empty($stu['enrollment_numbers']) ? explode(',', $stu['enrollment_numbers']) : [];
                            $course_list = !empty($stu['enrolled_courses']) ? explode(',', $stu['enrolled_courses']) : [];
                            ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <!-- Candidate Info -->
                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-start gap-2.5">
                                        <?php if ($photo_src): ?>
                                            <img src="<?= htmlspecialchars($photo_src) ?>" alt="" class="w-9 h-9 rounded-full object-cover border border-slate-200 shrink-0">
                                        <?php else: ?>
                                            <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 text-slate-600 font-bold flex items-center justify-center text-xs shrink-0">
                                                <?= strtoupper(substr($stu['name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <div class="font-bold text-[#0e1e2e] leading-snug truncate" title="<?= htmlspecialchars($stu['name']) ?>"><?= htmlspecialchars($stu['name']) ?></div>
                                            <?php if ($stu['is_verified'] == 1): ?>
                                                <span class="text-[10px] text-emerald-600 font-semibold inline-flex items-center gap-1 mt-0.5">
                                                    <i class="fa-solid fa-circle-check text-[9px]"></i> Email Verified
                                                </span>
                                            <?php else: ?>
                                                <span class="text-[10px] text-amber-600 font-medium inline-flex items-center gap-1 mt-0.5">
                                                    <i class="fa-regular fa-clock text-[9px]"></i> Unverified
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Student ID -->
                                <td class="px-3 py-3 align-top whitespace-nowrap">
                                    <span class="px-2 py-1 text-[11px] font-mono font-bold rounded bg-orange-50 text-[#fe7c03] border border-orange-200 inline-block">
                                        <?= htmlspecialchars($stu['student_id']) ?>
                                    </span>
                                </td>

                                <!-- Reserved Course & Seat Status -->
                                <td class="px-4 py-3 align-top">
                                    <?php
                                    $has_admission = !empty($stu['admission_id']) || !empty($enr_list) || !empty($course_list);
                                    $is_cancelled = in_array($stu['seat_status'], ['Cancelled', 'Expired']);
                                    $is_confirmed = ($stu['seat_status'] === 'Confirmed');

                                    $valid_until_ts = !empty($stu['seat_valid_until']) ? strtotime($stu['seat_valid_until']) : null;
                                    $seconds_left = $valid_until_ts ? ($valid_until_ts - time()) : null;
                                    $days_left = $seconds_left !== null ? max(0, ceil($seconds_left / 86400)) : null;

                                    $paid_val = (float)($stu['payment_amount'] ?? 0);
                                    if ($paid_val > 5000 || $paid_val <= 0) {
                                        $paid_val = (float)$admin_seat_fee;
                                    }
                                    ?>
                                    <?php if (!empty($stu['booked_course'])): ?>
                                        <div class="font-semibold text-slate-800 leading-snug text-[11.5px] mb-1" title="<?= htmlspecialchars($stu['booked_course']) ?>">
                                            <?= htmlspecialchars($stu['booked_course']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-[10.5px] block mb-1">General Program</span>
                                    <?php endif; ?>

                                    <div class="flex flex-wrap items-center gap-1">
                                        <?php if ($has_admission): ?>
                                            <span class="px-1.5 py-0.5 text-[9.5px] font-bold rounded bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                                                <i class="fa-solid fa-circle-check text-[8px]"></i> Admission Complete
                                            </span>
                                        <?php elseif ($is_cancelled || ($seconds_left !== null && $seconds_left <= 0)): ?>
                                            <span class="px-1.5 py-0.5 text-[9.5px] font-bold rounded bg-rose-50 text-rose-700 border border-rose-200 inline-flex items-center gap-1" title="Validity lapsed on <?= $valid_until_ts ? date('d M Y, h:i A', $valid_until_ts) : 'N/A' ?>">
                                                <i class="fa-solid fa-ban text-[8px]"></i> Cancelled (Expired)
                                            </span>
                                        <?php elseif ($is_confirmed): ?>
                                            <?php if ($seconds_left !== null && $seconds_left <= 86400): ?>
                                                <span class="px-1.5 py-0.5 text-[9.5px] font-bold rounded bg-rose-100 text-rose-800 border border-rose-300 inline-flex items-center gap-1 animate-pulse" title="Valid until <?= date('d M Y, h:i A', $valid_until_ts) ?>">
                                                    <i class="fa-solid fa-hourglass-end text-[8px] text-rose-600"></i> Expires Today!
                                                </span>
                                            <?php elseif ($seconds_left !== null && $seconds_left <= 172800): ?>
                                                <span class="px-1.5 py-0.5 text-[9.5px] font-bold rounded bg-amber-50 text-amber-800 border border-amber-300 inline-flex items-center gap-1" title="Valid until <?= date('d M Y, h:i A', $valid_until_ts) ?>">
                                                    <i class="fa-solid fa-clock text-[8px] text-amber-600"></i> <?= $days_left ?>d Left
                                                </span>
                                            <?php else: ?>
                                                <span class="px-1.5 py-0.5 text-[9.5px] font-semibold rounded bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1" title="Valid until <?= $valid_until_ts ? date('d M Y, h:i A', $valid_until_ts) : '7 Days' ?>">
                                                    <i class="fa-solid fa-chair text-[8px]"></i> Active (<?= $days_left ?? 7 ?>d left)
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="px-1.5 py-0.5 text-[9.5px] font-medium rounded bg-amber-50 text-amber-700 border border-amber-200">
                                                Seat Pending
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($stu['payment_status'] === 'Paid' || $is_confirmed): ?>
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-emerald-600 text-white">
                                                ₹<?= number_format($paid_val, 0) ?> Paid
                                            </span>
                                            <a href="../registration-success.php?ref=<?= urlencode($stu['student_id']) ?>" target="_blank" class="px-1.5 py-0.5 text-[9px] font-semibold text-[#fe7c03] bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded inline-flex items-center gap-0.5 no-underline transition-colors" title="View Seat Confirmation Slip (PDF)">
                                                <i class="fa-solid fa-file-invoice text-[8px]"></i> Slip
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Enrolled Course & Enrollment Numbers -->
                                <td class="px-4 py-3 align-top">
                                    <?php if (!empty($enr_list)): ?>
                                        <?php foreach ($enr_list as $idx => $enr): ?>
                                            <?php $trimmed_enr = trim($enr); ?>
                                            <div class="mb-1.5 last:mb-0">
                                                <a href="../enrollment-slip.php?enr=<?= urlencode($trimmed_enr) ?>" target="_blank" class="px-2 py-0.5 text-[11px] font-mono font-bold rounded bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 inline-flex items-center gap-1 no-underline transition-colors" title="Open Enrollment Slip (PDF)">
                                                    <i class="fa-solid fa-graduation-cap text-[9px]"></i> <?= htmlspecialchars($trimmed_enr) ?>
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[8px] text-sky-400"></i>
                                                </a>
                                                <?php if (!empty($course_list[$idx])): ?>
                                                    <div class="text-[10.5px] font-medium text-slate-600 mt-0.5 leading-snug"><?= htmlspecialchars(trim($course_list[$idx])) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php elseif (!empty($course_list)): ?>
                                        <span class="text-[11px] text-slate-700 font-semibold"><?= htmlspecialchars(implode(', ', $course_list)) ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-[11px] italic">Not applied yet</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Financial Fee Ledger Summary -->
                                <td class="px-4 py-3 align-top font-mono">
                                    <?php if ((float)$stu['total_course_fee'] > 0): ?>
                                        <div class="text-slate-700 font-semibold text-[11px]">Total: ₹<?= number_format((float)$stu['total_course_fee'], 0) ?></div>
                                        <div class="text-emerald-700 font-bold text-[11px] mt-0.5">Paid: ₹<?= number_format((float)$stu['total_fee_paid'], 0) ?></div>
                                        <?php if ((float)$stu['total_fee_pending'] > 0): ?>
                                            <div class="text-rose-600 font-bold text-[11px] mt-0.5">Due: ₹<?= number_format((float)$stu['total_fee_pending'], 0) ?></div>
                                        <?php else: ?>
                                            <div class="mt-0.5"><span class="px-1.5 py-0.2 text-[9px] bg-emerald-50 text-emerald-700 border border-emerald-200 rounded font-bold">Cleared</span></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Contact / Phone / Email -->
                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs">
                                        <a href="tel:<?= htmlspecialchars($stu['phone']) ?>" class="hover:text-[#fe7c03] no-underline text-inherit">
                                            <?= htmlspecialchars($stu['phone']) ?>
                                        </a>
                                        <a href="<?= $wa_link ?>" target="_blank" class="text-emerald-600 hover:text-emerald-700 text-sm" title="WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                    <div class="text-[11px] text-slate-500 truncate max-w-[150px] mt-0.5" title="<?= htmlspecialchars($stu['email']) ?>"><?= htmlspecialchars($stu['email']) ?></div>
                                    <?php if (!empty($stu['aadhaar'])): ?>
                                        <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                            XXXX-XXXX-<?= substr($stu['aadhaar'], -4) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Date -->
                                <td class="px-3 py-3 align-top text-[11px] text-slate-500 whitespace-nowrap">
                                    <div><?= date('d M Y', strtotime($stu['created_at'])) ?></div>
                                    <div class="text-[10px] text-slate-400"><?= date('h:i A', strtotime($stu['created_at'])) ?></div>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 align-top text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        <!-- Re-activate Seat (If Cancelled, Expired, or Past Due) -->
                                        <?php if (($is_cancelled || ($seconds_left !== null && $seconds_left <= 0)) && !$has_admission): ?>
                                            <a href="students.php?action=reactivate_seat&id=<?= $stu['id'] ?>" onclick="return confirm('Re-activate seat reservation for \'<?= htmlspecialchars(addslashes($stu['name'])) ?>\' for 7 days?\nAn email notification will be sent immediately to the candidate.')" class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10.5px] inline-flex items-center gap-1 transition-colors shadow-sm no-underline" title="Re-activate Seat Reservation (+7 Days)">
                                                <i class="fa-solid fa-rotate-left text-[9px]"></i>
                                                <span>Re-activate</span>
                                            </a>
                                        <?php elseif ($is_confirmed && !$has_admission): ?>
                                            <!-- Extend Seat (+7 Days) -->
                                            <a href="students.php?action=extend_seat&id=<?= $stu['id'] ?>&days=7" onclick="return confirm('Extend seat reservation validity by +7 days for \'<?= htmlspecialchars(addslashes($stu['name'])) ?>\'?\nCandidate will receive an email.')" class="px-1.5 py-1 text-[10.5px] font-semibold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 rounded transition-colors no-underline inline-flex items-center gap-0.5" title="Extend by +7 Days">
                                                <i class="fa-solid fa-calendar-plus text-[9px]"></i>
                                                <span>+7D</span>
                                            </a>
                                            <!-- Cancel Seat -->
                                            <a href="students.php?action=cancel_seat&id=<?= $stu['id'] ?>" onclick="return confirm('Are you sure you want to cancel this seat reservation?\nA cancellation notice will be emailed to \'<?= htmlspecialchars(addslashes($stu['name'])) ?>\'.')" class="px-1.5 py-1 text-[10.5px] font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded transition-colors no-underline inline-flex items-center gap-0.5" title="Cancel Seat">
                                                <i class="fa-solid fa-ban text-[9px]"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Open Complete Ledger Modal -->
                                        <button type="button" class="px-2.5 py-1 rounded bg-[#fe7c03] hover:bg-[#ea6c00] text-white font-semibold text-[11px] inline-flex items-center gap-1 transition-colors view-ledger-btn shadow-sm"
                                                data-studentid="<?= htmlspecialchars($stu['student_id']) ?>"
                                                title="View 360° Profile, Enrolled Courses &amp; Fee Installments">
                                            <i class="fa-solid fa-receipt text-[10px]"></i>
                                            <span>View Ledger</span>
                                        </button>

                                        <!-- Delete -->
                                        <a href="students.php?action=delete&id=<?= $stu['id'] ?>" onclick="return confirm('Permanently delete candidate \'<?= htmlspecialchars(addslashes($stu['name'])) ?>\'?');" class="w-7 h-7 rounded border border-slate-200 bg-white text-slate-400 hover:text-rose-600 hover:border-rose-200 flex items-center justify-center transition-colors no-underline" title="Delete Candidate">
                                            <i class="fa-regular fa-trash-can text-[10px]"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ============================================================
     UNIFIED 360° STUDENT & FEE LEDGER MODAL
============================================================ -->
<div class="modal fade" id="studentLedgerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border border-slate-200 rounded-lg shadow-xl overflow-hidden">
            
            <!-- MODAL HEADER -->
            <div class="modal-header border-b border-slate-200 py-3 px-4 bg-[#0e1e2e] text-white">
                <div class="flex items-center gap-2">
                    <span class="w-7 text-center text-[#fe7c03] fs-5"><i class="fa-solid fa-id-card-clip"></i></span>
                    <div>
                        <h5 class="modal-title text-sm font-bold text-white uppercase tracking-wider mb-0">Student 360° Profile &amp; Financial Ledger</h5>
                        <p class="text-[11px] text-slate-300 mb-0">Full accounting ledger: seat reservations, course enrollments, and monthly installment breakdown</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal"></button>
            </div>

            <!-- MODAL BODY -->
            <div class="modal-body p-4 space-y-4 bg-slate-50/70" id="ledgerModalBody">
                <div class="text-center py-8 text-slate-400" id="ledgerLoading">
                    <i class="fa-solid fa-spinner fa-spin text-3xl text-[#fe7c03] mb-2 block"></i>
                    <span>Loading student ledger &amp; installment schedule...</span>
                </div>

                <div id="ledgerContent" style="display: none;" class="space-y-4">
                    
                    <!-- 1. STUDENT IDENTITY BANNER -->
                    <div class="bg-white border border-slate-200 rounded-lg p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div id="mLedgerPhotoContainer" class="shrink-0">
                                <img id="mLedgerPhoto" src="" alt="Passport" class="w-16 h-20 rounded object-cover border border-slate-200" style="display: none;">
                                <div id="mLedgerPhotoPlaceholder" class="w-16 h-20 rounded border border-slate-200 flex items-center justify-center bg-slate-100 text-slate-400 text-2xl">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-base font-bold text-[#0e1e2e] mb-0" id="mLedgerName">-</h4>
                                    <span class="px-2 py-0.5 text-xs font-mono font-bold rounded bg-orange-100 text-[#fe7c03] border border-orange-200" id="mLedgerStudentId">-</span>
                                </div>
                                <div class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-3">
                                    <span><i class="fa-solid fa-phone text-slate-400 me-1"></i> <strong id="mLedgerPhone" class="text-slate-700">-</strong></span>
                                    <span><i class="fa-solid fa-envelope text-slate-400 me-1"></i> <span id="mLedgerEmail">-</span></span>
                                    <span><i class="fa-solid fa-fingerprint text-slate-400 me-1"></i> <span class="font-mono" id="mLedgerAadhaar">-</span></span>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-1">
                                    <i class="fa-regular fa-calendar-check me-1"></i> Registered: <span id="mLedgerRegisteredDate">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- SEAT RESERVATION BADGE STRIP -->
                        <div class="bg-slate-50 border border-slate-200 rounded-md p-3 text-xs min-w-[270px]">
                            <div class="font-bold text-slate-700 mb-1 flex items-center justify-between">
                                <span><i class="fa-solid fa-chair text-[#fe7c03] me-1"></i> Seat Reservation:</span>
                                <span id="mLedgerSeatBadge">-</span>
                            </div>
                            <div class="text-slate-500 text-[11px] space-y-0.5">
                                <div>Reserved Course: <strong class="text-slate-800" id="mLedgerBookedCourse">General Batch</strong></div>
                                <div>Fee Paid: <strong class="text-emerald-700" id="mLedgerSeatFee">₹0.00</strong> (100% Adjusted)</div>
                                <div>Payment ID: <span class="font-mono text-slate-600" id="mLedgerSeatPaymentId">-</span></div>
                            </div>
                            <a id="mLedgerSeatSlipBtn" href="#" target="_blank" class="mt-2.5 w-full py-1.5 px-3 text-xs font-bold text-[#fe7c03] bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded text-center inline-flex items-center justify-center gap-1.5 no-underline transition-colors shadow-sm">
                                <i class="fa-solid fa-file-invoice"></i> View Seat Confirmation Slip (PDF)
                            </a>
                        </div>
                    </div>

                    <!-- 2. FINANCIAL SUMMARY METRIC CARDS -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-white border border-slate-200 rounded-md p-3">
                            <span class="text-[10.5px] font-bold uppercase text-slate-400 block mb-0.5">Total Course Fees</span>
                            <h4 class="text-base font-bold font-mono text-slate-800" id="mLedgerTotalFees">₹0.00</h4>
                            <span class="text-[10px] text-slate-400">All enrolled programs</span>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-md p-3">
                            <span class="text-[10.5px] font-bold uppercase text-slate-400 block mb-0.5">Seat Fee Adjusted</span>
                            <h4 class="text-base font-bold font-mono text-emerald-600" id="mLedgerSeatAdjusted">₹0.00</h4>
                            <span class="text-[10px] text-emerald-600">100% Deducted</span>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-md p-3">
                            <span class="text-[10.5px] font-bold uppercase text-slate-400 block mb-0.5">Total Paid Till Now</span>
                            <h4 class="text-base font-bold font-mono text-emerald-700" id="mLedgerTotalPaid">₹0.00</h4>
                            <span class="text-[10px] text-emerald-600">Verified receipts</span>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-md p-3">
                            <span class="text-[10.5px] font-bold uppercase text-slate-400 block mb-0.5">Remaining Balance</span>
                            <h4 class="text-base font-bold font-mono text-rose-600" id="mLedgerTotalPending">₹0.00</h4>
                            <span class="text-[10px] text-rose-500 font-medium">Scheduled installments</span>
                        </div>
                    </div>

                    <!-- 3. ENROLLED ADMISSIONS & ENROLLMENT NUMBERS -->
                    <div class="bg-white border border-slate-200 rounded-md p-3.5 space-y-2">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <h6 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider flex items-center gap-1 mb-0">
                                <i class="fa-solid fa-graduation-cap text-[#fe7c03]"></i> Enrolled Programs &amp; Unique Enrollment Numbers
                            </h6>
                            <span class="text-[11px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded" id="mAdmissionCount">0 Enrolled</span>
                        </div>
                        <div class="table-responsive">
                            <table class="w-full text-left text-xs min-w-[800px] border border-slate-200 rounded overflow-hidden">
                                <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] border-b border-slate-200">
                                    <tr>
                                        <th class="px-3 py-2" style="min-width: 190px;">Course Program</th>
                                        <th class="px-3 py-2" style="min-width: 140px;">Official Enrollment No.</th>
                                        <th class="px-3 py-2" style="min-width: 85px;">Total Fee</th>
                                        <th class="px-3 py-2" style="min-width: 90px;">Seat Adjusted</th>
                                        <th class="px-3 py-2" style="min-width: 85px;">Paid</th>
                                        <th class="px-3 py-2" style="min-width: 85px;">Balance</th>
                                        <th class="px-3 py-2" style="min-width: 80px;">Plan</th>
                                        <th class="px-3 py-2" style="min-width: 80px;">Status</th>
                                        <th class="px-3 py-2 text-right" style="min-width: 120px;">Official Document</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white" id="mLedgerAdmissionsList">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 4. INSTALLMENT BREAKDOWN SCHEDULE & OFFLINE COLLECTION -->
                    <div class="bg-white border border-slate-200 rounded-md p-3.5 space-y-2">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <h6 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider flex items-center gap-1 mb-0">
                                <i class="fa-solid fa-calendar-days text-emerald-600"></i> Monthly Installments Breakdown &amp; Receipt Ledger
                            </h6>
                            <span class="text-[11px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded" id="mInstallmentCount">0 Installments</span>
                        </div>
                        <div class="table-responsive">
                            <table class="w-full text-left text-xs min-w-[880px] border border-slate-200 rounded overflow-hidden">
                                <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] border-b border-slate-200">
                                    <tr>
                                        <th class="px-3 py-2" style="min-width: 180px;">Installment #</th>
                                        <th class="px-3 py-2" style="min-width: 140px;">Enrollment No.</th>
                                        <th class="px-3 py-2" style="min-width: 180px;">Course</th>
                                        <th class="px-3 py-2" style="min-width: 90px;">Amount</th>
                                        <th class="px-3 py-2" style="min-width: 95px;">Due Date</th>
                                        <th class="px-3 py-2" style="min-width: 95px;">Paid Date</th>
                                        <th class="px-3 py-2" style="min-width: 80px;">Status</th>
                                        <th class="px-3 py-2 text-right" style="min-width: 140px;">Receipt / Collection</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white" id="mLedgerInstallmentsList">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- MODAL FOOTER -->
            <div class="modal-footer border-t border-slate-200 py-2 px-4 bg-slate-50 flex items-center justify-between">
                <span class="text-xs text-slate-400 font-mono">Finchskills Accounting &bull; Real-time Database Sync</span>
                <button type="button" class="px-3.5 py-1.5 text-xs text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-100 font-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     OFFLINE INSTALLMENT COLLECTION MODAL
============================================================ -->
<div class="modal fade" id="collectFeeModal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" action="students.php" class="modal-content border border-slate-200 rounded-md shadow-lg">
            <input type="hidden" name="action_collect_installment" value="1">
            <input type="hidden" name="installment_id" id="cInstId" value="">

            <div class="modal-header border-b border-slate-200 py-2 px-3 bg-slate-50">
                <h6 class="modal-title text-xs font-bold text-[#0e1e2e] uppercase mb-0">Collect Installment Fee</h6>
                <button type="button" class="btn-close text-xs" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-3.5 space-y-3 text-xs">
                <div>
                    <label class="text-[11px] text-slate-500 font-semibold block mb-1">Installment:</label>
                    <div class="font-bold text-slate-800" id="cInstTitle">-</div>
                </div>
                <div>
                    <label class="text-[11px] text-slate-500 font-semibold block mb-1">Amount Due:</label>
                    <div class="font-bold font-mono text-base text-emerald-600" id="cInstAmount">₹0.00</div>
                </div>
                <div>
                    <label class="text-[11px] text-slate-500 font-semibold block mb-1">Payment Mode:</label>
                    <select name="payment_mode" class="w-full text-xs border border-slate-200 rounded p-1.5 focus:outline-none focus:border-[#fe7c03]">
                        <option value="Cash">Cash at Center</option>
                        <option value="UPI / QR">UPI (GPay / PhonePe / Paytm)</option>
                        <option value="Bank Transfer">Bank Transfer (NEFT / IMPS)</option>
                        <option value="Cheque">Bank Cheque / DD</option>
                    </select>
                </div>
                <div>
                    <label class="text-[11px] text-slate-500 font-semibold block mb-1">Reference / UTR No. (Optional):</label>
                    <input type="text" name="payment_ref" placeholder="e.g. UTR12345678 or Cash Receipt No" class="w-full text-xs border border-slate-200 rounded p-1.5 focus:outline-none focus:border-[#fe7c03]">
                </div>
            </div>

            <div class="modal-footer border-t border-slate-200 py-2 px-3 bg-slate-50 flex items-center justify-between">
                <button type="button" class="btn btn-sm btn-light py-1 text-xs" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-success py-1 px-3 text-xs fw-bold">
                    <i class="fa-solid fa-check me-1"></i> Confirm &amp; Mark Paid
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ledgerModal = new bootstrap.Modal(document.getElementById('studentLedgerModal'));
    const collectModal = new bootstrap.Modal(document.getElementById('collectFeeModal'));

    // Open Unified Ledger Modal
    document.querySelectorAll('.view-ledger-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const studentId = this.dataset.studentid;
            
            document.getElementById('ledgerLoading').style.display = 'block';
            document.getElementById('ledgerContent').style.display = 'none';
            ledgerModal.show();

            fetch('students.php?action=get_ledger&student_id=' + encodeURIComponent(studentId))
                .then(res => res.json())
                .then(data => {
                    document.getElementById('ledgerLoading').style.display = 'none';
                    if (!data.success) {
                        alert(data.message || 'Error fetching ledger');
                        ledgerModal.hide();
                        return;
                    }

                    document.getElementById('ledgerContent').style.display = 'block';

                    // 1. Profile Info
                    const s = data.student;
                    document.getElementById('mLedgerName').textContent = s.name;
                    document.getElementById('mLedgerStudentId').textContent = s.student_id;
                    document.getElementById('mLedgerPhone').textContent = s.phone;
                    document.getElementById('mLedgerEmail').textContent = s.email;
                    document.getElementById('mLedgerAadhaar').textContent = 'XXXX-XXXX-' + (s.aadhaar ? s.aadhaar.slice(-4) : '----');
                    document.getElementById('mLedgerRegisteredDate').textContent = s.created_at;

                    // Photo
                    const photoImg = document.getElementById('mLedgerPhoto');
                    const photoPlc = document.getElementById('mLedgerPhotoPlaceholder');
                    if (s.photo) {
                        photoImg.src = s.photo;
                        photoImg.style.display = 'block';
                        photoPlc.style.display = 'none';
                    } else {
                        photoImg.style.display = 'none';
                        photoPlc.style.display = 'flex';
                    }

                    // Seat Booking
                    document.getElementById('mLedgerBookedCourse').textContent = s.booked_course || 'General Batch';
                    document.getElementById('mLedgerSeatPaymentId').textContent = s.payment_id || 'Offline / Direct';
                    
                    let seatFeeVal = parseFloat(s.payment_amount || 0);
                    if (seatFeeVal > 5000 || seatFeeVal <= 0) seatFeeVal = <?= (float)$admin_seat_fee ?>;
                    document.getElementById('mLedgerSeatFee').textContent = (s.payment_status === 'Paid' || s.seat_status === 'Confirmed')
                        ? ('₹' + Number(seatFeeVal).toLocaleString('en-IN', {minimumFractionDigits: 2}))
                        : '₹0.00';

                    const seatBadge = document.getElementById('mLedgerSeatBadge');
                    if (s.seat_status === 'Confirmed') {
                        seatBadge.innerHTML = '<span class="px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-600 text-white">Confirmed</span>';
                    } else {
                        seatBadge.innerHTML = '<span class="px-2 py-0.5 text-[10px] font-bold rounded bg-amber-500 text-white">Pending</span>';
                    }

                    // Seat Confirmation Slip Button
                    const seatSlipBtn = document.getElementById('mLedgerSeatSlipBtn');
                    if (seatSlipBtn) {
                        seatSlipBtn.href = '../registration-success.php?ref=' + encodeURIComponent(s.student_id);
                    }

                    // 2. Financial Metrics
                    const f = data.financials;
                    document.getElementById('mLedgerTotalFees').textContent = f.formatted_fees;
                    document.getElementById('mLedgerSeatAdjusted').textContent = (f.total_seat_adjusted > 0 ? ('- ' + f.formatted_seat_adjusted) : '₹0.00');
                    document.getElementById('mLedgerTotalPaid').textContent = f.formatted_paid;
                    document.getElementById('mLedgerTotalPending').textContent = f.formatted_pending;

                    // 3. Admissions List
                    const admList = document.getElementById('mLedgerAdmissionsList');
                    admList.innerHTML = '';
                    document.getElementById('mAdmissionCount').textContent = data.admissions.length + ' Enrolled Course(s)';

                    if (data.admissions.length === 0) {
                        admList.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-slate-400">No course admissions submitted yet.</td></tr>';
                    } else {
                        data.admissions.forEach(a => {
                            const tr = document.createElement('tr');
                            const enrHtml = a.enrollment_no 
                                ? `<span class="px-2 py-0.5 text-[11px] font-mono font-bold rounded bg-sky-50 text-sky-700 border border-sky-200">${a.enrollment_no}</span>`
                                : '<span class="text-slate-400 text-[10.5px]">Pending</span>';
                            
                            const seatAdjHtml = (Number(a.seat_fee_adjusted) > 0)
                                ? `<span class="text-emerald-600 font-bold">- ₹${Number(a.seat_fee_adjusted).toLocaleString('en-IN', {minimumFractionDigits: 2})}</span>`
                                : '<span class="text-slate-400">₹0.00</span>';

                            const docLink = a.id
                                ? `<a href="../enrollment-slip.php?id=${encodeURIComponent(a.id)}" target="_blank" class="px-2.5 py-1 text-[10.5px] font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 rounded inline-flex items-center gap-1 no-underline transition-colors shadow-sm"><i class="fa-solid fa-file-invoice text-sky-600"></i> Slip (PDF)</a>`
                                : (a.enrollment_no 
                                    ? `<a href="../enrollment-slip.php?enr=${encodeURIComponent(a.enrollment_no)}" target="_blank" class="px-2.5 py-1 text-[10.5px] font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 rounded inline-flex items-center gap-1 no-underline transition-colors shadow-sm"><i class="fa-solid fa-file-invoice text-sky-600"></i> Slip (PDF)</a>`
                                    : '<span class="text-slate-400">-</span>');

                            tr.innerHTML = `
                                <td class="px-3 py-2 font-bold text-slate-800">${a.course}</td>
                                <td class="px-3 py-2">${enrHtml}</td>
                                <td class="px-3 py-2 font-mono">₹${Number(a.fee_total).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                                <td class="px-3 py-2 font-mono">${seatAdjHtml}</td>
                                <td class="px-3 py-2 font-mono font-bold text-emerald-600">₹${Number(a.fee_paid).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                                <td class="px-3 py-2 font-mono ${Number(a.fee_pending) > 0 ? 'text-rose-600 font-bold' : 'text-emerald-600'}">₹${Number(a.fee_pending).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                                <td class="px-3 py-2 capitalize font-medium text-slate-600">${a.payment_plan || 'N/A'}</td>
                                <td class="px-3 py-2"><span class="px-1.5 py-0.5 text-[10px] font-semibold rounded ${a.status === 'Approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}">${a.status}</span></td>
                                <td class="px-3 py-2 text-right">${docLink}</td>
                            `;
                            admList.appendChild(tr);
                        });
                    }

                    // 4. Installments List
                    const insList = document.getElementById('mLedgerInstallmentsList');
                    insList.innerHTML = '';
                    document.getElementById('mInstallmentCount').textContent = data.installments.length + ' Installments';

                    if (data.installments.length === 0) {
                        insList.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-slate-400">No installments scheduled.</td></tr>';
                    } else {
                        data.installments.forEach(ins => {
                            const tr = document.createElement('tr');
                            const enrBadge = ins.enrollment_no 
                                ? `<span class="font-mono text-sky-700 font-semibold">${ins.enrollment_no}</span>`
                                : '<span class="text-slate-400">-</span>';

                            let statusBadge = '';
                            let actionHtml = '';

                            if (ins.status === 'Paid') {
                                statusBadge = '<span class="px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-50 text-emerald-700 border border-emerald-200">Paid</span>';
                                const refUrl = ins.receipt_no ? ins.receipt_no : (ins.payment_id ? ins.payment_id : (ins.enrollment_no ? ins.enrollment_no : ''));
                                actionHtml = `
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        ${refUrl ? `<a href="../receipt.php?ref=${encodeURIComponent(refUrl)}" target="_blank" class="px-2.5 py-1 text-[10.5px] font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 rounded inline-flex items-center gap-1.5 no-underline shadow-sm transition-colors"><i class="fa-solid fa-receipt text-emerald-600"></i> View Receipt</a>` : '<span class="text-emerald-600 font-semibold text-xs"><i class="fa-solid fa-check"></i> Paid</span>'}
                                    </div>
                                `;
                            } else {
                                statusBadge = '<span class="px-2 py-0.5 text-[10px] font-bold rounded bg-amber-50 text-amber-700 border border-amber-200">Pending</span>';
                                actionHtml = `
                                    <button type="button" class="px-2.5 py-1 text-[10.5px] font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded transition-colors collect-btn shadow-sm"
                                            data-id="${ins.id}"
                                            data-title="${ins.installment_title}"
                                            data-amount="${ins.installment_amount}">
                                        <i class="fa-solid fa-money-bill-wave me-1"></i> Collect
                                    </button>
                                `;
                            }

                            tr.innerHTML = `
                                <td class="px-3 py-2 font-semibold text-slate-800">${ins.installment_title}</td>
                                <td class="px-3 py-2">${enrBadge}</td>
                                <td class="px-3 py-2 text-slate-600">${ins.course_title}</td>
                                <td class="px-3 py-2 font-mono font-bold text-slate-800">₹${Number(ins.installment_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                                <td class="px-3 py-2 text-slate-500">${ins.due_date ? new Date(ins.due_date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}) : '-'}</td>
                                <td class="px-3 py-2 text-slate-500">${ins.paid_date ? new Date(ins.paid_date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}) : '-'}</td>
                                <td class="px-3 py-2">${statusBadge}</td>
                                <td class="px-3 py-2 text-right">${actionHtml}</td>
                            `;
                            insList.appendChild(tr);
                        });

                        // Bind collect buttons
                        document.querySelectorAll('.collect-btn').forEach(cBtn => {
                            cBtn.addEventListener('click', function() {
                                document.getElementById('cInstId').value = this.dataset.id;
                                document.getElementById('cInstTitle').textContent = this.dataset.title;
                                document.getElementById('cInstAmount').textContent = '₹' + Number(this.dataset.amount).toLocaleString('en-IN', {minimumFractionDigits: 2});
                                collectModal.show();
                            });
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('ledgerLoading').style.display = 'none';
                    alert('Network error while retrieving student ledger.');
                    ledgerModal.hide();
                });
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

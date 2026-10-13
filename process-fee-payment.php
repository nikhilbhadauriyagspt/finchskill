<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail-helper.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$action = trim($data['action'] ?? '');

// ==========================================
// 1. CALCULATE FEES & INSTALLMENT BREAKDOWN
// ==========================================
if ($action === 'calculate_fee') {
    $course_title = trim($data['course'] ?? '');
    
    // Fetch course details
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE title = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$course_title]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        echo json_encode(['success' => false, 'message' => 'Course not found.']);
        exit;
    }

    // Parse Fee: extract digits e.g. "₹55,000" => 55000
    $raw_fee = preg_replace('/[^0-9]/', '', $course['fee'] ?? '');
    $base_fee = !empty($raw_fee) ? (float)$raw_fee : 35000.00;

    // Check if student has paid seat booking fee (₹999)
    $seat_fee_adjusted = 0.00;
    $student = null;
    $student_db_id = (int)($_SESSION['student_db_id'] ?? 0);
    $student_unique_id = trim($data['student_id'] ?? ($_SESSION['student_id'] ?? ''));

    if ($student_db_id > 0) {
        $s_stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
        $s_stmt->execute([$student_db_id]);
        $student = $s_stmt->fetch(PDO::FETCH_ASSOC);
    } elseif (!empty($student_unique_id)) {
        $s_stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? LIMIT 1");
        $s_stmt->execute([$student_unique_id]);
        $student = $s_stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($student) {
        if ($student['payment_status'] === 'Paid' || (float)($student['payment_amount'] ?? 0) > 0 || $student['seat_status'] === 'Confirmed') {
            $admin_seat_fee = (float)getSetting('seat_booking_fee', '999');
            $seat_fee_adjusted = (float)($student['payment_amount'] ?? 0) > 0 
                ? (float)$student['payment_amount'] 
                : $admin_seat_fee;
        }
    }

    // Parse course duration to get EXACT number of monthly installments
    // e.g. "6 Months" -> 6, "12 Months" -> 12, "3 Months" -> 3, "2 Months" -> 2, "11 Months" -> 11
    $dur_str = $course['duration'] ?? '';
    preg_match('/(\d+)\s*(month|year)/i', $dur_str, $matches);
    if (!empty($matches[1])) {
        $v = (int)$matches[1];
        $u = strtolower($matches[2]);
        $total_parts = (strpos($u, 'year') !== false) ? ($v * 12) : $v;
    } else {
        $total_parts = 3;
    }
    $total_parts = max(1, min(12, $total_parts));

    // Full Payment Option:
    // Base Fee - 10% Discount - Seat Fee Adjusted
    $discount_percent = (float)getSetting('full_payment_discount_percent', '10');
    $discount_amount = ($base_fee * $discount_percent) / 100.0;
    $full_pay_final = max(0, $base_fee - $discount_amount - $seat_fee_adjusted);

    // Installment Payment Option:
    // Base Fee - Seat Fee Adjusted = Net remaining payable across installments
    $net_installment_total = max(0, $base_fee - $seat_fee_adjusted);
    $part_amount = round($net_installment_total / $total_parts, 2);

    // Build installment schedule preview (1 month apart)
    $schedule = [];
    $accumulated = 0;
    for ($i = 1; $i <= $total_parts; $i++) {
        $due_date = ($i === 1) ? date('Y-m-d') : date('Y-m-d', strtotime("+" . ($i - 1) . " months"));
        
        // Exact balancing on the final installment
        if ($i === $total_parts) {
            $cur_amount = max(0, round($net_installment_total - $accumulated, 2));
        } else {
            $cur_amount = $part_amount;
            $accumulated += $cur_amount;
        }

        $schedule[] = [
            'part' => $i,
            'title' => ($i === 1) ? '1st Installment (Payable Today at Admission)' : ($i . 'th Monthly Installment'),
            'amount' => $cur_amount,
            'due_date' => date('d M Y', strtotime($due_date)),
            'raw_due_date' => $due_date,
            'is_now' => ($i === 1)
        ];
    }

    echo json_encode([
        'success' => true,
        'course' => [
            'id' => $course['id'],
            'title' => $course['title'],
            'duration' => $course['duration'],
            'raw_fee' => $base_fee,
            'formatted_fee' => '₹' . number_format($base_fee, 2)
        ],
        'seat_fee_adjusted' => $seat_fee_adjusted,
        'formatted_seat_adjusted' => '₹' . number_format($seat_fee_adjusted, 2),
        'full_payment' => [
            'discount_percent' => $discount_percent,
            'discount_amount' => $discount_amount,
            'formatted_discount' => '₹' . number_format($discount_amount, 2),
            'seat_adjusted' => $seat_fee_adjusted,
            'final_amount' => $full_pay_final,
            'formatted_final' => '₹' . number_format($full_pay_final, 2)
        ],
        'installment_payment' => [
            'total_parts' => $total_parts,
            'part_amount' => $part_amount,
            'formatted_part_amount' => '₹' . number_format($part_amount, 2),
            'seat_adjusted' => $seat_fee_adjusted,
            'net_payable' => $net_installment_total,
            'formatted_net_payable' => '₹' . number_format($net_installment_total, 2),
            'schedule' => $schedule
        ]
    ]);
    exit;
}

// ==========================================
// 2. VERIFY PAYMENT & FINALIZE ADMISSION
// ==========================================
if ($action === 'verify_course_fee') {
    $draft_token  = trim($data['draft_token'] ?? '');
    $admission_id = (int)($data['admission_id'] ?? 0);
    $payment_id   = trim($data['razorpay_payment_id'] ?? '');
    $plan_type    = trim($data['plan_type'] ?? 'full'); // 'full' or 'installment'
    $paid_amount  = (float)($data['paid_amount'] ?? 0);

    if (empty($payment_id) || $paid_amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment confirmation data.']);
        exit;
    }

    // Attempt to load draft application data if draft_token is present or in session
    $draft_data = null;
    $draft_file = null;
    if (!empty($draft_token)) {
        if (!empty($_SESSION['pending_admission']) && (!empty($_SESSION['pending_admission_token']) && $_SESSION['pending_admission_token'] === $draft_token)) {
            $draft_data = $_SESSION['pending_admission'];
        }
        $potential_file = __DIR__ . '/uploads/temp_drafts/' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $draft_token) . '.json';
        if (!$draft_data && file_exists($potential_file)) {
            $draft_data = json_decode(file_get_contents($potential_file), true);
            $draft_file = $potential_file;
        } elseif (file_exists($potential_file)) {
            $draft_file = $potential_file;
        }
    } elseif (!empty($_SESSION['pending_admission']) && $admission_id <= 0) {
        $draft_data = $_SESSION['pending_admission'];
    }

    // =========================================================================
    // BRANCH A: NEW ADMISSION CREATION (ONLY COMMITTED AFTER PAYMENT VERIFIED)
    // =========================================================================
    if ($draft_data) {
        $student_id       = trim($draft_data['student_id'] ?? '');
        $name             = trim($draft_data['name'] ?? '');
        $father_name      = trim($draft_data['father_name'] ?? '');
        $email            = trim($draft_data['email'] ?? '');
        $mobile           = trim($draft_data['mobile'] ?? '');
        $aadhaar          = trim($draft_data['aadhaar'] ?? '');
        $dob              = trim($draft_data['dob'] ?? '');
        $gender           = trim($draft_data['gender'] ?? '');
        $course_name      = trim($draft_data['course'] ?? '');
        $education        = trim($draft_data['education'] ?? '');
        $board_university = trim($draft_data['board_university'] ?? '');
        $passing_year     = trim($draft_data['passing_year'] ?? '');
        $percentage       = trim($draft_data['percentage'] ?? '');
        $state            = trim($draft_data['state'] ?? '');
        $city             = trim($draft_data['city'] ?? '');
        $post_office      = trim($draft_data['post_office'] ?? '');
        $pincode          = trim($draft_data['pincode'] ?? '');
        $address          = trim($draft_data['address'] ?? '');
        $marksheet10      = trim($draft_data['marksheet10'] ?? '');
        $marksheet12      = trim($draft_data['marksheet12'] ?? '');
        $aadhaar_card     = trim($draft_data['aadhaar_card'] ?? '');
        $photo            = trim($draft_data['photo'] ?? '');

        // Lookup course
        $c_stmt = $pdo->prepare("SELECT * FROM courses WHERE title = ? LIMIT 1");
        $c_stmt->execute([$course_name]);
        $course = $c_stmt->fetch(PDO::FETCH_ASSOC);

        $raw_fee = preg_replace('/[^0-9]/', '', $course['fee'] ?? '');
        $base_fee = !empty($raw_fee) ? (float)$raw_fee : 35000.00;

        // Check if student has paid seat booking fee
        $seat_fee_adjusted = 0.00;
        $student_db_id = (int)($_SESSION['student_db_id'] ?? 0);
        $student = null;
        if ($student_db_id > 0) {
            $s_stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
            $s_stmt->execute([$student_db_id]);
            $student = $s_stmt->fetch(PDO::FETCH_ASSOC);
        } elseif (!empty($student_id)) {
            $s_stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? LIMIT 1");
            $s_stmt->execute([$student_id]);
            $student = $s_stmt->fetch(PDO::FETCH_ASSOC);
        } elseif (!empty($email)) {
            $s_stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? LIMIT 1");
            $s_stmt->execute([$email]);
            $student = $s_stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($student) {
            if ($student['payment_status'] === 'Paid' || (float)($student['payment_amount'] ?? 0) > 0 || $student['seat_status'] === 'Confirmed') {
                $admin_seat_fee = (float)getSetting('seat_booking_fee', '999');
                $seat_fee_adjusted = (float)($student['payment_amount'] ?? 0) > 0 
                    ? (float)$student['payment_amount'] 
                    : $admin_seat_fee;
            }
        }

        $discount_percent = (float)getSetting('full_payment_discount_percent', '10');
        $discount_amount = ($plan_type === 'full') ? round(($base_fee * $discount_percent) / 100.0, 2) : 0.00;
        $final_net_course_total = max(0, $base_fee - $discount_amount - $seat_fee_adjusted);
        $pending_balance = max(0, $final_net_course_total - $paid_amount);

        $p_status = ($pending_balance <= 0) ? 'Fully Paid' : 'Partial Paid';
        $receipt_no = "REC-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

        // Generate official Enrollment Number
        $max_stmt = $pdo->query("SELECT MAX(id) as max_id FROM admissions");
        $next_id_val = ((int)$max_stmt->fetchColumn() ?: 0) + 1;
        $enrollment_no = "ENR-" . date('Y') . "-" . str_pad($next_id_val + 1000, 5, '0', STR_PAD_LEFT);

        try {
            $pdo->beginTransaction();

            // 1. Insert into admissions table for the FIRST TIME!
            $ins_adm = $pdo->prepare("INSERT INTO admissions 
                (student_id, enrollment_no, name, father_name, email, mobile, aadhaar, dob, gender, course, education, board_university, passing_year, percentage, state, city, post_office, pincode, address, marksheet10, marksheet12, aadhaar_card, photo, status, fee_total, seat_fee_adjusted, fee_paid, fee_pending, payment_plan, payment_status, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Approved', ?, ?, ?, ?, ?, ?, 0, NOW())");
            
            $ins_adm->execute([
                $student_id, $enrollment_no, $name, $father_name, $email, $mobile, $aadhaar, $dob, $gender,
                $course_name, $education, $board_university, $passing_year, $percentage,
                $state, $city, $post_office, $pincode, $address,
                $marksheet10, $marksheet12, $aadhaar_card, $photo,
                $base_fee, $seat_fee_adjusted, $paid_amount, $pending_balance, $plan_type, $p_status
            ]);
            $admission_id = (int)$pdo->lastInsertId();

            // 2. Link student account & finalize
            if ($student && !empty($student['id'])) {
                $upd_std = $pdo->prepare("UPDATE students SET 
                    admission_id = ?,
                    seat_status = 'Confirmed',
                    payment_status = 'Paid'
                    WHERE id = ?");
                $upd_std->execute([$admission_id, $student['id']]);
            } elseif (!empty($student_id)) {
                $upd_std = $pdo->prepare("UPDATE students SET 
                    admission_id = ?,
                    seat_status = 'Confirmed',
                    payment_status = 'Paid'
                    WHERE student_id = ?");
                $upd_std->execute([$admission_id, $student_id]);
            }

            // 3. Installments Schedule
            $next_due_date = null;
            $next_due_amount = 0;
            $total_parts = 1;

            if ($plan_type === 'full') {
                $ins_stmt = $pdo->prepare("INSERT INTO student_installments 
                    (student_db_id, student_id, admission_id, enrollment_no, course_title, total_course_fee, discount_amount, seat_fee_adjusted, final_payable, payment_type, total_parts, installment_no, installment_title, installment_amount, due_date, paid_date, status, payment_id, receipt_no) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'full', 1, 1, 'Full Course Fee Payment', ?, CURDATE(), NOW(), 'Paid', ?, ?)");
                $ins_stmt->execute([
                    ($student['id'] ?? null), ($student_id ?: 'FS-GUEST'), $admission_id, $enrollment_no, $course_name,
                    $base_fee, $discount_amount, $seat_fee_adjusted, $final_net_course_total, $paid_amount, $payment_id, $receipt_no
                ]);
            } else {
                $dur_str = $course['duration'] ?? '';
                preg_match('/(\d+)\s*(month|year)/i', $dur_str, $matches);
                if (!empty($matches[1])) {
                    $v = (int)$matches[1];
                    $u = strtolower($matches[2]);
                    $total_parts = (strpos($u, 'year') !== false) ? ($v * 12) : $v;
                } else {
                    $total_parts = 3;
                }
                $total_parts = max(1, min(12, $total_parts));

                $net_installment_total = max(0, $base_fee - $seat_fee_adjusted);
                $part_amount = round($net_installment_total / $total_parts, 2);
                $accumulated = 0;

                for ($i = 1; $i <= $total_parts; $i++) {
                    $due_d = ($i === 1) ? date('Y-m-d') : date('Y-m-d', strtotime("+" . ($i - 1) . " months"));
                    $is_p1 = ($i === 1);
                    $st = $is_p1 ? 'Paid' : 'Pending';
                    $p_id = $is_p1 ? $payment_id : null;
                    $r_no = $is_p1 ? $receipt_no : null;
                    $p_date = $is_p1 ? date('Y-m-d H:i:s') : null;

                    if ($i === $total_parts) {
                        $cur_amt = max(0, round($net_installment_total - $accumulated, 2));
                    } else {
                        $cur_amt = $part_amount;
                        $accumulated += $cur_amt;
                    }

                    if ($i === 2) {
                        $next_due_date = $due_d;
                        $next_due_amount = $cur_amt;
                    }

                    $ins_stmt = $pdo->prepare("INSERT INTO student_installments 
                        (student_db_id, student_id, admission_id, enrollment_no, course_title, total_course_fee, discount_amount, seat_fee_adjusted, final_payable, payment_type, total_parts, installment_no, installment_title, installment_amount, due_date, paid_date, status, payment_id, receipt_no) 
                        VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, 'installment', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $title_str = ($i === 1) ? '1st Installment (Payable at Admission)' : ($i . 'th Monthly Installment');
                    $ins_stmt->execute([
                        ($student['id'] ?? null), ($student_id ?: 'FS-GUEST'), $admission_id, $enrollment_no, $course_name,
                        $base_fee, $seat_fee_adjusted, $net_installment_total, $total_parts, $i, $title_str, $cur_amt, $due_d, $p_date, $st, $p_id, $r_no
                    ]);
                }
            }

            // 4. Log in fee_submissions table for accounting
            $fee_log = $pdo->prepare("INSERT INTO fee_submissions 
                (name, contact, email, fee_type, purpose, course, receipt, status, is_read) 
                VALUES (?, ?, ?, 'Course Admission Fee', ?, ?, ?, 'Verified', 0)");
            $purpose_txt = ($plan_type === 'full') ? "Full Course Fee (Paid in Full) - Enr: {$enrollment_no}" : "Course Fee Part 1 / Down Payment - Enr: {$enrollment_no}";
            $fee_log->execute([$name, $mobile, $email, $purpose_txt, $course_name, "Razorpay: {$payment_id} | Receipt: {$receipt_no} | Enr: {$enrollment_no}"]);

            $pdo->commit();

            // Clean up draft storage
            if ($draft_file && file_exists($draft_file)) {
                @unlink($draft_file);
            }
            unset($_SESSION['pending_admission'], $_SESSION['pending_admission_token']);

            // 5. Send automated confirmation and official receipt email to candidate
            $receiptDetails = [
                'receipt_no'        => $receipt_no,
                'enrollment_no'     => $enrollment_no,
                'plan_title'        => ($plan_type === 'full') ? 'Full Course Fee (10% Discount Applied)' : "Monthly Installment Plan ({$total_parts} Months)",
                'total_fee'         => $base_fee,
                'seat_fee_adjusted' => $seat_fee_adjusted,
                'discount_amount'   => $discount_amount,
                'paid_amount'       => $paid_amount,
                'payment_id'        => $payment_id,
                'pending_balance'   => $pending_balance,
                'next_due_date'     => $next_due_date,
                'next_due_amount'   => $next_due_amount
            ];

            sendCourseFeeReceiptEmail(
                $email,
                $name,
                $student_id ?: 'FS-STUDENT',
                $course_name,
                $receiptDetails
            );

            // 6. Send Confirmed Admission + Fee Notification to Admin with Documents
            $attachments = [];
            $upload_dir = __DIR__ . '/uploads/admissions/';
            if (!empty($marksheet10) && file_exists($upload_dir . $marksheet10)) {
                $attachments[] = ['path' => $upload_dir . $marksheet10, 'name' => '10th_Marksheet_' . $marksheet10];
            }
            if (!empty($marksheet12) && file_exists($upload_dir . $marksheet12)) {
                $attachments[] = ['path' => $upload_dir . $marksheet12, 'name' => '12th_Marksheet_' . $marksheet12];
            }
            if (!empty($aadhaar_card) && file_exists($upload_dir . $aadhaar_card)) {
                $attachments[] = ['path' => $upload_dir . $aadhaar_card, 'name' => 'Aadhaar_Card_' . $aadhaar_card];
            }
            if (!empty($photo)) {
                $p_path = file_exists($upload_dir . $photo) ? ($upload_dir . $photo) : (__DIR__ . '/' . $photo);
                if (file_exists($p_path)) {
                    $attachments[] = ['path' => $p_path, 'name' => 'Passport_Photo_' . basename($p_path)];
                }
            }

            $admin_subject = "New Confirmed Admission & Fee Paid: {$name} - {$course_name} ({$enrollment_no})";
            $admin_html = "
            <div style='font-family: Arial, sans-serif; max-width: 620px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;'>
                <div style='background: #0e1e2e; color: #fff; padding: 22px; text-align: center; border-bottom: 3px solid #fe7c03;'>
                    <h2 style='margin: 0; font-size: 20px;'>FINCHSKILLS INSTITUTE</h2>
                    <p style='margin: 5px 0 0; color: #fe7c03; font-weight: bold;'>New Confirmed Admission & Fee Received</p>
                </div>
                <div style='padding: 24px; background: #fff;'>
                    <div style='background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px;'>
                        <strong style='color: #166534; font-size: 14px;'>✓ Admission Confirmed & Fee Successfully Verified</strong>
                        <div style='color: #15803d; font-size: 13px; margin-top: 3px;'>Candidate has paid their course fee. Official application documents are attached.</div>
                    </div>

                    <table border='1' cellpadding='8' cellspacing='0' width='100%' style='border-collapse: collapse; border-color: #e2e8f0; font-size: 13.5px;'>
                        <tr><td width='35%' style='background:#f8fafc;'><b>Enrollment No</b></td><td><b style='color:#0e1e2e;'>{$enrollment_no}</b></td></tr>
                        <tr><td style='background:#f8fafc;'><b>Receipt No</b></td><td><b>{$receipt_no}</b></td></tr>
                        <tr><td style='background:#f8fafc;'><b>Amount Paid</b></td><td><b style='color:#16a34a;'>₹" . number_format($paid_amount, 2) . "</b> ({$plan_type})</td></tr>
                        <tr><td style='background:#f8fafc;'><b>Razorpay ID</b></td><td><code>{$payment_id}</code></td></tr>
                        <tr><td style='background:#f8fafc;'><b>Course Enrolled</b></td><td><b style='color:#fe7c03;'>{$course_name}</b></td></tr>
                        <tr><td style='background:#f8fafc;'><b>Student Name</b></td><td>{$name}</td></tr>
                        <tr><td style='background:#f8fafc;'><b>Father's Name</b></td><td>{$father_name}</td></tr>
                        <tr><td style='background:#f8fafc;'><b>Mobile (WhatsApp)</b></td><td>{$mobile}</td></tr>
                        <tr><td style='background:#f8fafc;'><b>Email</b></td><td>{$email}</td></tr>
                        <tr><td style='background:#f8fafc;'><b>Aadhaar Number</b></td><td>{$aadhaar}</td></tr>
                        <tr><td style='background:#f8fafc;'><b>DOB & Gender</b></td><td>{$dob} ({$gender})</td></tr>
                        <tr><td style='background:#f8fafc;'><b>Qualification</b></td><td>{$education} ({$board_university} - {$passing_year} - {$percentage})</td></tr>
                        <tr><td style='background:#f8fafc;'><b>Location</b></td><td>{$city}, {$state} - {$pincode}</td></tr>
                        <tr><td style='background:#f8fafc;'><b>Full Address</b></td><td>{$address}</td></tr>
                    </table>
                </div>
                <div style='background: #f8fafc; padding: 14px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                    Finchskills Institute Admin Notification &bull; Verified Fee Payment &bull; Attachments Attached
                </div>
            </div>";

            $smtp_cfg = getSmtpConfig();
            $admin_inbox = !empty($smtp_cfg['smtp_admin_email']) ? $smtp_cfg['smtp_admin_email'] : (!empty($smtp_cfg['smtp_username']) ? $smtp_cfg['smtp_username'] : 'finchskillsinstitute@gmail.com');
            sendFinchMail($admin_inbox, $admin_subject, $admin_html, ['attachments' => $attachments]);

            echo json_encode([
                'success'       => true,
                'message'       => 'Payment verified successfully! Admission confirmed.',
                'receipt_no'    => $receipt_no,
                'enrollment_no' => $enrollment_no,
                'admission_id'  => $admission_id,
                'course'        => $course_name,
                'paid_amount'   => $paid_amount
            ]);
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Admission processing error: ' . $e->getMessage()]);
            exit;
        }
    }

    // =========================================================================
    // BRANCH B: SUBSEQUENT INSTALLMENT PAYMENT FOR EXISTING ADMISSION
    // =========================================================================
    if ($admission_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Admission application session expired. Please re-submit your admission details.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM admissions WHERE id = ? LIMIT 1");
    $stmt->execute([$admission_id]);
    $admission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admission) {
        echo json_encode(['success' => false, 'message' => 'Admission record not found.']);
        exit;
    }

    $c_stmt = $pdo->prepare("SELECT * FROM courses WHERE title = ? LIMIT 1");
    $c_stmt->execute([$admission['course']]);
    $course = $c_stmt->fetch(PDO::FETCH_ASSOC);

    $base_fee = (float)($admission['fee_total'] ?? 0);
    if ($base_fee <= 0) {
        $raw_fee = preg_replace('/[^0-9]/', '', $course['fee'] ?? '');
        $base_fee = !empty($raw_fee) ? (float)$raw_fee : 35000.00;
    }
    $seat_fee_adjusted = (float)($admission['seat_fee_adjusted'] ?? 0);

    $enrollment_no = $admission['enrollment_no'] ?: ("ENR-" . date('Y') . "-" . str_pad($admission_id + 1000, 5, '0', STR_PAD_LEFT));
    $receipt_no = "REC-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

    try {
        $pdo->beginTransaction();

        $cur_pending = max(0, (float)$admission['fee_pending'] - $paid_amount);
        $new_p_status = ($cur_pending <= 0) ? 'Fully Paid' : 'Partial Paid';

        $upd_adm = $pdo->prepare("UPDATE admissions SET 
            fee_paid = fee_paid + ?,
            fee_pending = ?,
            payment_status = ?,
            status = 'Approved'
            WHERE id = ?");
        $upd_adm->execute([$paid_amount, $cur_pending, $new_p_status, $admission_id]);

        // Find next pending installment
        $inst_q = $pdo->prepare("SELECT id, installment_no, installment_title FROM student_installments WHERE admission_id = ? AND status = 'Pending' ORDER BY due_date ASC, id ASC LIMIT 1");
        $inst_q->execute([$admission_id]);
        $next_inst = $inst_q->fetch(PDO::FETCH_ASSOC);

        if ($next_inst) {
            $upd_inst = $pdo->prepare("UPDATE student_installments SET status = 'Paid', paid_date = NOW(), payment_id = ?, receipt_no = ? WHERE id = ?");
            $upd_inst->execute([$payment_id, $receipt_no, $next_inst['id']]);
        }

        // Check if there is still a next due installment
        $rem_inst_q = $pdo->prepare("SELECT installment_amount, due_date FROM student_installments WHERE admission_id = ? AND status = 'Pending' ORDER BY due_date ASC, id ASC LIMIT 1");
        $rem_inst_q->execute([$admission_id]);
        $rem_next = $rem_inst_q->fetch(PDO::FETCH_ASSOC);

        $next_due_date = $rem_next['due_date'] ?? null;
        $next_due_amount = (float)($rem_next['installment_amount'] ?? 0);

        // Log in fee_submissions
        $fee_log = $pdo->prepare("INSERT INTO fee_submissions 
            (name, contact, email, fee_type, purpose, course, receipt, status, is_read) 
            VALUES (?, ?, ?, 'Course Installment Fee', ?, ?, ?, 'Verified', 0)");
        $purpose_txt = "Installment Payment - Enr: {$enrollment_no}";
        $fee_log->execute([$admission['name'], $admission['mobile'], $admission['email'], $purpose_txt, $admission['course'], "Razorpay: {$payment_id} | Receipt: {$receipt_no} | Enr: {$enrollment_no}"]);

        $pdo->commit();

        $receiptDetails = [
            'receipt_no'        => $receipt_no,
            'enrollment_no'     => $enrollment_no,
            'plan_title'        => "Installment Payment (" . ($next_inst['installment_title'] ?? 'Scheduled Installment') . ")",
            'total_fee'         => $base_fee,
            'seat_fee_adjusted' => $seat_fee_adjusted,
            'discount_amount'   => 0,
            'paid_amount'       => $paid_amount,
            'payment_id'        => $payment_id,
            'pending_balance'   => $cur_pending,
            'next_due_date'     => $next_due_date,
            'next_due_amount'   => $next_due_amount
        ];

        sendCourseFeeReceiptEmail(
            $admission['email'],
            $admission['name'],
            $admission['student_id'] ?: 'FS-STUDENT',
            $admission['course'],
            $receiptDetails
        );

        sendAdminFeePaymentNotificationEmail(
            [
                'name'          => $admission['name'],
                'student_id'    => $admission['student_id'] ?: 'FS-STUDENT',
                'enrollment_no' => $enrollment_no,
                'mobile'        => $admission['mobile'],
                'email'         => $admission['email']
            ],
            [
                'title'    => $admission['course'],
                'duration' => $course['duration'] ?? 'N/A'
            ],
            $receiptDetails
        );

        echo json_encode([
            'success'       => true,
            'message'       => 'Installment payment verified successfully!',
            'receipt_no'    => $receipt_no,
            'enrollment_no' => $enrollment_no,
            'admission_id'  => $admission_id,
            'course'        => $admission['course'],
            'paid_amount'   => $paid_amount
        ]);
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Installment payment error: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action request.']);
exit;

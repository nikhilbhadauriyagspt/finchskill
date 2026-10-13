<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail-helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id       = trim($_POST['student_id'] ?? ($_SESSION['student_id'] ?? ''));
    $name             = trim($_POST['name'] ?? '');
    $father_name      = trim($_POST['father_name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $mobile           = trim($_POST['mobile'] ?? '');
    $aadhaar          = trim($_POST['aadhaar'] ?? '');
    $dob              = trim($_POST['dob'] ?? '');
    $gender           = trim($_POST['gender'] ?? '');
    $course           = trim($_POST['course'] ?? '');
    $education        = trim($_POST['education'] ?? '');
    $board_university = trim($_POST['board_university'] ?? '');
    $passing_year     = trim($_POST['passing_year'] ?? '');
    $percentage       = trim($_POST['percentage'] ?? '');
    $state            = trim($_POST['state'] ?? '');
    $city             = trim($_POST['city'] ?? '');
    $post_office      = trim($_POST['post_office'] ?? '');
    $pincode          = trim($_POST['pincode'] ?? '');
    $address          = trim($_POST['address'] ?? '');

    // 1. Handle Secure File Uploads into uploads/admissions/
    $upload_dir = __DIR__ . '/uploads/admissions/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    $marksheet10_file = '';
    $marksheet12_file = '';
    $aadhaar_file     = '';
    $photo_file       = '';

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

    // Photo: If new file uploaded, use it. Otherwise use existing photo from registration
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $photo_file = 'photo_' . time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_file);
    } elseif (!empty($_POST['existing_photo'])) {
        $photo_file = trim($_POST['existing_photo']);
    }

    // 2. Generate Unique Draft Reference (No database insertion yet!)
    // Admission record and emails are strictly generated ONLY when course fee is paid!
    $draft_token = 'DFT_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    $app_ref     = 'DFT-' . strtoupper(substr(md5($draft_token), 0, 8));

    $draft_data = [
        'draft_token'      => $draft_token,
        'app_ref'          => $app_ref,
        'student_id'       => $student_id,
        'name'             => $name,
        'father_name'      => $father_name,
        'email'            => $email,
        'mobile'           => $mobile,
        'aadhaar'          => $aadhaar,
        'dob'              => $dob,
        'gender'           => $gender,
        'course'           => $course,
        'education'        => $education,
        'board_university' => $board_university,
        'passing_year'     => $passing_year,
        'percentage'       => $percentage,
        'state'            => $state,
        'city'             => $city,
        'post_office'      => $post_office,
        'pincode'          => $pincode,
        'address'          => $address,
        'marksheet10'      => $marksheet10_file,
        'marksheet12'      => $marksheet12_file,
        'aadhaar_card'     => $aadhaar_file,
        'photo'            => $photo_file,
        'created_at'       => date('Y-m-d H:i:s')
    ];

    // Store in Session for active checkout
    $_SESSION['pending_admission'] = $draft_data;
    $_SESSION['pending_admission_token'] = $draft_token;

    // Also persist in temporary file store as fallback in case session drops during Razorpay
    $draft_dir = __DIR__ . '/uploads/temp_drafts';
    if (!is_dir($draft_dir)) {
        @mkdir($draft_dir, 0777, true);
    }
    @file_put_contents($draft_dir . '/' . $draft_token . '.json', json_encode($draft_data, JSON_PRETTY_PRINT));

    // Auto-cleanup stale drafts older than 48 hours
    if ($handle = @opendir($draft_dir)) {
        $now = time();
        while (false !== ($entry = readdir($handle))) {
            if ($entry !== '.' && $entry !== '..' && substr($entry, -5) === '.json') {
                $fpath = $draft_dir . '/' . $entry;
                if ($now - @filemtime($fpath) > 172800) { // 48 hrs
                    @unlink($fpath);
                }
            }
        }
        closedir($handle);
    }

    // 3. Return AJAX Response to step forward to Fee Payment (Step 6)
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_POST['ajax_submit']);

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success'      => true,
            'message'      => 'Documents uploaded successfully! Please proceed to fee payment to finalize your admission.',
            'draft_token'  => $draft_token,
            'admission_id' => 0, // Indicates pending payment
            'app_ref'      => $app_ref,
            'name'         => $name,
            'email'        => $email,
            'mobile'       => $mobile,
            'course'       => $course
        ]);
        exit;
    }

    // Standard redirect fallback
    header("Location: form-submission.php?step=6&token=" . urlencode($draft_token));
    exit;
} else {
    header("Location: form-submission.php");
    exit;
}

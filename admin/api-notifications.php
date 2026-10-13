<?php
if (session_status() === PHP_SESSION_NONE) {
    if (ini_get('session.cookie_path') !== '/') {
        @ini_set('session.cookie_path', '/');
    }
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../cron-seat-reminders.php';

header('Content-Type: application/json');

// Check admin authentication
if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Background Seat Reminder & Expiry Engine (throttled every 10 mins internally)
if (function_exists('runSeatReminderEngine')) {
    runSeatReminderEngine($pdo, false);
}

try {
    $last_adm_id = (int)($_GET['last_adm_id'] ?? 0);
    $last_fee_id = (int)($_GET['last_fee_id'] ?? 0);
    $last_inq_id = (int)($_GET['last_inq_id'] ?? 0);
    $last_std_id = (int)($_GET['last_std_id'] ?? 0);

    // 1. Current Max IDs in DB
    $max_adm = (int)$pdo->query("SELECT MAX(id) FROM admissions")->fetchColumn();
    $max_fee = (int)$pdo->query("SELECT MAX(id) FROM fee_submissions")->fetchColumn();
    $max_inq = (int)$pdo->query("SELECT MAX(id) FROM contact_inquiries")->fetchColumn();
    $max_std = (int)$pdo->query("SELECT MAX(id) FROM students")->fetchColumn();

    // 2. Unread Counts
    $unread_adm = (int)$pdo->query("SELECT COUNT(*) FROM admissions WHERE is_read = 0")->fetchColumn();
    $unread_fee = (int)$pdo->query("SELECT COUNT(*) FROM fee_submissions WHERE is_read = 0")->fetchColumn();
    $unread_inq = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE is_read = 0")->fetchColumn();
    $total_unread = $unread_adm + $unread_fee + $unread_inq;

    // 3. Detect Brand New Items
    $new_alerts = [];

    // Check new admissions
    if ($last_adm_id > 0 && $max_adm > $last_adm_id) {
        $stmt = $pdo->prepare("SELECT id, name, course, mobile, created_at FROM admissions WHERE id > ? ORDER BY id DESC LIMIT 5");
        $stmt->execute([$last_adm_id]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $new_alerts[] = [
                'type'     => 'admission',
                'id'       => $r['id'],
                'title'    => 'New Admission Application',
                'message'  => htmlspecialchars($r['name']) . ' applied for ' . htmlspecialchars($r['course']),
                'url'      => 'admissions.php',
                'time'     => date('h:i A', strtotime($r['created_at'] ?? 'now')),
                'icon'     => 'fa-id-card',
                'color'    => '#fe7c03',
                'bg'       => '#fff8f3'
            ];
        }
    }

    // Check new fees
    if ($last_fee_id > 0 && $max_fee > $last_fee_id) {
        $stmt = $pdo->prepare("SELECT id, name, course, purpose, receipt, created_at FROM fee_submissions WHERE id > ? ORDER BY id DESC LIMIT 5");
        $stmt->execute([$last_fee_id]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $new_alerts[] = [
                'type'     => 'fee',
                'id'       => $r['id'],
                'title'    => 'New Fee Payment Received',
                'message'  => htmlspecialchars($r['name']) . ' submitted ' . htmlspecialchars($r['purpose'] ?? 'Course Fee'),
                'url'      => 'fees.php',
                'time'     => date('h:i A', strtotime($r['created_at'] ?? 'now')),
                'icon'     => 'fa-receipt',
                'color'    => '#0d9488',
                'bg'       => '#f0fdfa'
            ];
        }
    }

    // Check new inquiries
    if ($last_inq_id > 0 && $max_inq > $last_inq_id) {
        $stmt = $pdo->prepare("SELECT id, name, subject, created_at FROM contact_inquiries WHERE id > ? ORDER BY id DESC LIMIT 5");
        $stmt->execute([$last_inq_id]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $new_alerts[] = [
                'type'     => 'inquiry',
                'id'       => $r['id'],
                'title'    => 'New Contact Inquiry',
                'message'  => htmlspecialchars($r['name']) . ': ' . htmlspecialchars(mb_strimwidth($r['subject'] ?? 'Website Inquiry', 0, 45, '...')),
                'url'      => 'contacts.php',
                'time'     => date('h:i A', strtotime($r['created_at'] ?? 'now')),
                'icon'     => 'fa-envelope',
                'color'    => '#0284c7',
                'bg'       => '#f0f9ff'
            ];
        }
    }

    // Check new student registrations
    if ($last_std_id > 0 && $max_std > $last_std_id) {
        $stmt = $pdo->prepare("SELECT id, student_id, name, phone, created_at FROM students WHERE id > ? ORDER BY id DESC LIMIT 5");
        $stmt->execute([$last_std_id]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $new_alerts[] = [
                'type'     => 'student',
                'id'       => $r['id'],
                'title'    => 'New Candidate Registered',
                'message'  => htmlspecialchars($r['name']) . ' (' . htmlspecialchars($r['student_id'] ?? 'ID') . ')',
                'url'      => 'students.php',
                'time'     => date('h:i A', strtotime($r['created_at'] ?? 'now')),
                'icon'     => 'fa-user-check',
                'color'    => '#8b5cf6',
                'bg'       => '#f5f3ff'
            ];
        }
    }

    // 4. Fetch Top 5 Recent Unread Items for Dropdown Menu
    $recent_items = [];
    $recent_adm = $pdo->query("SELECT id, name, course, created_at FROM admissions WHERE is_read = 0 ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recent_adm as $item) {
        $recent_items[] = [
            'title' => 'Admission: ' . $item['name'],
            'desc'  => $item['course'],
            'url'   => 'admissions.php',
            'icon'  => 'fa-id-card',
            'color' => '#fe7c03',
            'time'  => date('h:i A', strtotime($item['created_at'] ?? 'now'))
        ];
    }

    $recent_fee = $pdo->query("SELECT id, name, course, purpose, created_at FROM fee_submissions WHERE is_read = 0 ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recent_fee as $item) {
        $recent_items[] = [
            'title' => 'Fee: ' . $item['name'],
            'desc'  => $item['purpose'] ?? 'Course Fee Payment',
            'url'   => 'fees.php',
            'icon'  => 'fa-receipt',
            'color' => '#0d9488',
            'time'  => date('h:i A', strtotime($item['created_at'] ?? 'now'))
        ];
    }

    $recent_inq = $pdo->query("SELECT id, name, subject, created_at FROM contact_inquiries WHERE is_read = 0 ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recent_inq as $item) {
        $recent_items[] = [
            'title' => 'Inquiry: ' . $item['name'],
            'desc'  => $item['subject'] ?? 'New Message',
            'url'   => 'contacts.php',
            'icon'  => 'fa-envelope',
            'color' => '#0284c7',
            'time'  => date('h:i A', strtotime($item['created_at'] ?? 'now'))
        ];
    }

    // Seat Alerts: Expiring seats (<= 24 hours left, no admission submitted)
    $stmt_exp = $pdo->query("SELECT id, name, booked_course, seat_valid_until FROM students 
        WHERE seat_status IN ('Confirmed', 'Reserved') 
          AND payment_status = 'Paid' 
          AND (admission_id IS NULL OR admission_id = 0)
          AND seat_valid_until IS NOT NULL 
          AND seat_valid_until > NOW() 
          AND seat_valid_until <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
        ORDER BY seat_valid_until ASC LIMIT 3");
    $expiring_seats = $stmt_exp->fetchAll(PDO::FETCH_ASSOC);

    foreach ($expiring_seats as $es) {
        $hours_left = max(0, round((strtotime($es['seat_valid_until']) - time()) / 3600));
        $recent_items[] = [
            'title' => 'Seat Expiring (' . $hours_left . 'h): ' . $es['name'],
            'desc'  => ($es['booked_course'] ?: 'Reserved Seat') . ' - Exp: ' . date('d M h:i A', strtotime($es['seat_valid_until'])),
            'url'   => 'students.php?filter=expiring',
            'icon'  => 'fa-clock',
            'color' => '#dc2626',
            'time'  => $hours_left . 'h left'
        ];
    }

    $expiring_count = count($expiring_seats);
    $cancelled_today_count = (int)$pdo->query("SELECT COUNT(*) FROM students 
        WHERE seat_status IN ('Cancelled', 'Expired') 
          AND seat_cancelled_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();

    echo json_encode([
        'success'      => true,
        'max_ids'      => [
            'adm' => $max_adm,
            'fee' => $max_fee,
            'inq' => $max_inq,
            'std' => $max_std
        ],
        'counts'       => [
            'total_unread'    => $total_unread,
            'admissions'      => $unread_adm,
            'fees'            => $unread_fee,
            'inquiries'       => $unread_inq,
            'expiring_seats'  => $expiring_count,
            'cancelled_seats' => $cancelled_today_count
        ],
        'has_new'      => count($new_alerts) > 0,
        'new_alerts'   => $new_alerts,
        'recent_items' => $recent_items
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

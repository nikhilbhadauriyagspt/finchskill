<?php
/**
 * Finchskills Automated Seat Reservation Reminder & Expiry Engine
 * 
 * Flow:
 *  - Checks all confirmed seat bookings with 7-day validity.
 *  - Day 2 Reminder: Sent 2 days after booking (~5 days left).
 *  - Day 4 Reminder: Sent 4 days after booking (~3 days left).
 *  - Final Day Alert: Sent in the last 24 hours with urgent countdown.
 *  - Auto-Cancel: If 7 days pass without admission submission, sets status to 'Cancelled' and sends cancellation email.
 * 
 * Execution Modes:
 *  1. CLI: php cron-seat-reminders.php
 *  2. HTTP: https://domain.com/cron-seat-reminders.php?token=FS_CRON_2026
 *  3. Embedded: runSeatReminderEngine() in admin headers/notifications
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail-helper.php';

function runSeatReminderEngine($pdo, $force = false) {
    // Throttling: If called embedded in admin, don't run more than once every 10 minutes unless $force is true
    if (!$force) {
        $last_run = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'last_seat_cron_run' LIMIT 1")->fetchColumn();
        if ($last_run && (time() - strtotime($last_run)) < 600) {
            return [
                'success' => true,
                'skipped' => true,
                'message' => 'Engine ran recently (last run: ' . $last_run . '). Throttled for 10 mins.'
            ];
        }
    }

    // Update last run time
    $upd_run = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('last_seat_cron_run', NOW()) 
        ON DUPLICATE KEY UPDATE setting_value = NOW()");
    $upd_run->execute();

    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'total_active_seats' => 0,
        'day2_reminders_sent' => 0,
        'day4_reminders_sent' => 0,
        'final_reminders_sent' => 0,
        'seats_cancelled' => 0,
        'details' => []
    ];

    // Find all confirmed seat reservations without completed admission
    $stmt = $pdo->query("SELECT * FROM students 
        WHERE seat_status IN ('Confirmed', 'Reserved') 
          AND payment_status = 'Paid' 
          AND seat_valid_until IS NOT NULL 
          AND (admission_id IS NULL OR admission_id = 0)
        ORDER BY seat_valid_until ASC");
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $report['total_active_seats'] = count($candidates);
    $now = time();

    foreach ($candidates as $cand) {
        $sid = (int)$cand['id'];
        $name = $cand['name'];
        $email = $cand['email'];

        // Safety check: verify if an admission form was submitted with matching email or student_id
        $adm_chk = $pdo->prepare("SELECT id FROM admissions WHERE student_id = ? OR (email = ? AND email != '') LIMIT 1");
        $adm_chk->execute([$cand['student_id'], $email]);
        $existing_adm_id = (int)$adm_chk->fetchColumn();

        if ($existing_adm_id > 0) {
            // Student already submitted admission! Link it and skip reminders
            $pdo->prepare("UPDATE students SET admission_id = ? WHERE id = ?")->execute([$existing_adm_id, $sid]);
            $report['details'][] = "Candidate #{$sid} ({$name}) already has admission #{$existing_adm_id}. Linked successfully.";
            continue;
        }

        $valid_until_ts = strtotime($cand['seat_valid_until']);
        $payment_ts = !empty($cand['payment_date']) ? strtotime($cand['payment_date']) : strtotime($cand['created_at']);
        
        // If reactivated, measure days from reactivation
        if (!empty($cand['seat_reactivated_at'])) {
            $reactivated_ts = strtotime($cand['seat_reactivated_at']);
            if ($reactivated_ts > $payment_ts) {
                $payment_ts = $reactivated_ts;
            }
        }

        $seconds_left = $valid_until_ts - $now;
        $hours_left = max(0, round($seconds_left / 3600, 1));
        $days_left = max(0, ceil($seconds_left / 86400));
        $days_since_start = floor(($now - $payment_ts) / 86400);

        // ==============================================================
        // 1. AUTO-CANCEL / EXPIRE: 7 Days passed ($seconds_left <= 0)
        // ==============================================================
        if ($seconds_left <= 0) {
            $pdo->prepare("UPDATE students SET 
                seat_status = 'Cancelled',
                seat_cancelled_at = NOW() 
                WHERE id = ?")->execute([$sid]);

            sendSeatCancelledEmail($cand);

            $report['seats_cancelled']++;
            $report['details'][] = "CANCELLED: Candidate #{$sid} ({$name}) seat expired on " . $cand['seat_valid_until'] . ". Cancellation mail sent.";
            continue;
        }

        // ==============================================================
        // 2. FINAL DAY URGENT REMINDER: <= 24 Hours Remaining
        // ==============================================================
        if ($seconds_left <= 86400) {
            if (empty($cand['seat_reminder_final_at'])) {
                sendSeatReminderFinalDayEmail($cand, $hours_left);
                $pdo->prepare("UPDATE students SET seat_reminder_final_at = NOW() WHERE id = ?")->execute([$sid]);
                $report['final_reminders_sent']++;
                $report['details'][] = "FINAL DAY ALERT: Sent to #{$sid} ({$name}) with {$hours_left}h remaining.";
            }
            continue;
        }

        // ==============================================================
        // 3. DAY 4 URGENT REMINDER: >= 4 Days since booking (~3 days left)
        // ==============================================================
        if ($days_since_start >= 4) {
            if (empty($cand['seat_reminder_day4_at'])) {
                sendSeatReminderDay4Email($cand, $days_left);
                $pdo->prepare("UPDATE students SET seat_reminder_day4_at = NOW() WHERE id = ?")->execute([$sid]);
                $report['day4_reminders_sent']++;
                $report['details'][] = "DAY 4 REMINDER: Sent to #{$sid} ({$name}) with {$days_left} days left.";
            }
            continue;
        }

        // ==============================================================
        // 4. DAY 2 FRIENDLY REMINDER: >= 2 Days since booking (~5 days left)
        // ==============================================================
        if ($days_since_start >= 2) {
            if (empty($cand['seat_reminder_day2_at'])) {
                sendSeatReminderDay2Email($cand, $days_left);
                $pdo->prepare("UPDATE students SET seat_reminder_day2_at = NOW() WHERE id = ?")->execute([$sid]);
                $report['day2_reminders_sent']++;
                $report['details'][] = "DAY 2 REMINDER: Sent to #{$sid} ({$name}) with {$days_left} days left.";
            }
            continue;
        }
    }

    return [
        'success' => true,
        'report' => $report
    ];
}

// Only execute output & exit if accessed directly (not included/required by another script)
$is_direct = (count(debug_backtrace()) === 0);

if ($is_direct) {
    $token = $_GET['token'] ?? '';
    $auth_valid = $is_cli || ($token === 'FS_CRON_2026') || !empty($_SESSION['admin_logged_in']);

    if ($auth_valid) {
        $force = isset($_GET['force']) && $_GET['force'] == '1';
        $res = runSeatReminderEngine($pdo, $force || $is_cli);

        if ($is_cli) {
            echo "=== FINCHSKILLS SEAT REMINDER CRON ===\n";
            echo "Time: " . date('Y-m-d H:i:s') . "\n";
            print_r($res);
            exit(0);
        } else {
            header('Content-Type: application/json');
            echo json_encode($res, JSON_PRETTY_PRINT);
            exit;
        }
    } else {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

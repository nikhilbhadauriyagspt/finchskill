<?php
if (session_status() === PHP_SESSION_NONE) {
    if (ini_get('session.cookie_path') !== '/') {
        @ini_set('session.cookie_path', '/');
    }
    session_start();
}

require_once __DIR__ . '/../../db.php';

function checkAdminAuth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: login.php");
        exit;
    }
}

// Function to fetch total counts and unread notifications
function getAdminStats($pdo) {
    $stats = [];
    
    // Total & Unread Admissions
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread FROM admissions");
    $res = $stmt->fetch();
    $stats['total_admissions'] = (int)($res['total'] ?? 0);
    $stats['unread_admissions'] = (int)($res['unread'] ?? 0);

    // Total & Unread Fees
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread FROM fee_submissions");
    $res = $stmt->fetch();
    $stats['total_fees'] = (int)($res['total'] ?? 0);
    $stats['unread_fees'] = (int)($res['unread'] ?? 0);

    // Total & Unread Inquiries
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread FROM contact_inquiries");
    $res = $stmt->fetch();
    $stats['total_inquiries'] = (int)($res['total'] ?? 0);
    $stats['unread_inquiries'] = (int)($res['unread'] ?? 0);

    // Total Courses
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM courses");
    $stats['total_courses'] = (int)($stmt->fetchColumn() ?? 0);

    // Total Course Categories
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM course_categories");
    $stats['total_categories'] = (int)($stmt->fetchColumn() ?? 0);

    // Students / Registered Candidates
    $stmt = $pdo->query("SELECT COUNT(*) as total, 
                                SUM(CASE WHEN seat_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid 
                         FROM students");
    $res = $stmt->fetch();
    $stats['total_students'] = (int)($res['total'] ?? 0);
    $stats['confirmed_students'] = (int)($res['confirmed'] ?? 0);
    $stats['paid_students'] = (int)($res['paid'] ?? 0);

    $stats['total_unread'] = $stats['unread_admissions'] + $stats['unread_fees'] + $stats['unread_inquiries'];
    return $stats;
}

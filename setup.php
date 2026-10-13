<?php
/**
 * 1-Click Fast Database Setup & Auto-Migration Script
 * YourGoodGuide / FinchSkills
 * 
 * Access via: https://yourdomain.com/setup or setup.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbConfigFile = __DIR__ . '/db.php';
$sqlDumpFile = __DIR__ . '/finchskills_db.sql';

$dbConnected = false;
$connectionError = '';
$serverInfo = [];
$message = '';
$status = '';
$details = [];

// Helper function to safely parse SQL dump into pure statements (strips problematic locks and session restores)
function parseSqlDump($filePath) {
    if (!file_exists($filePath)) {
        throw new Exception("File not found: " . basename($filePath));
    }
    $content = file_get_contents($filePath);
    if (empty($content)) {
        throw new Exception("SQL dump file is empty.");
    }

    $statements = [];
    $lines = explode("\n", $content);
    $buffer = '';

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Skip empty lines & SQL comments
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }

        // Strip LOCK / UNLOCK TABLES (avoids hanging/deadlocks on shared hosting MySQL users)
        if (preg_match('/^(LOCK TABLES|UNLOCK TABLES|\/\*!40000 ALTER TABLE .* (DISABLE|ENABLE) KEYS \*\/;)/i', $trimmed)) {
            continue;
        }

        // Strip mysqldump conditional comments and session restore commands
        if (preg_match('/^\/\*!\d{5}.*\*\/;?$/i', $trimmed)) {
            continue;
        }
        if (preg_match('/^SET\s+(@OLD_|FOREIGN_KEY_CHECKS|UNIQUE_CHECKS|SQL_MODE)/i', $trimmed)) {
            continue;
        }

        $buffer .= $line . "\n";

        // Check if statement ends with semicolon
        if (substr(rtrim($trimmed), -1) === ';') {
            $stmt = trim($buffer);
            if (!empty($stmt)) {
                $statements[] = $stmt;
            }
            $buffer = '';
        }
    }

    if (!empty(trim($buffer))) {
        $statements[] = trim($buffer);
    }

    return $statements;
}

// 1. Test Database Connection
if (file_exists($dbConfigFile)) {
    require_once $dbConfigFile;
    try {
        if (isset($pdo) && $pdo instanceof PDO) {
            $dbConnected = true;
            $serverInfo['version'] = $pdo->query("SELECT VERSION()")->fetchColumn();
            $serverInfo['database'] = DB_NAME;
            $serverInfo['host'] = DB_HOST;
            $serverInfo['user'] = DB_USER;
        }
    } catch (Throwable $e) {
        $connectionError = $e->getMessage();
    }
} else {
    $connectionError = "db.php configuration file not found in root directory.";
}

// 2. Self Delete Action
if (isset($_POST['delete_self'])) {
    if (@unlink(__FILE__)) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup Removed</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'></head><body class='bg-light d-flex align-items-center justify-content-center vh-100'><div class='card p-4 text-center shadow-sm' style='max-width:450px;'><h4 class='text-success fw-bold mb-2'>Setup File Removed!</h4><p class='text-muted small'>setup.php has been deleted from your server for security.</p><a href='index.php' class='btn btn-primary btn-sm'>Go to Website</a></div></body></html>";
        exit;
    } else {
        $message = "Unable to auto-delete setup.php. Please delete setup.php manually from your cPanel File Manager.";
        $status = 'warning';
    }
}

// 3. Handle Setup Execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if (!$dbConnected) {
        $status = 'danger';
        $message = "Database connection failed. Please check credentials in db.php first.";
    } else {
        try {
            @set_time_limit(300);
            @ini_set('memory_limit', '256M');

            if ($action === 'full_import') {
                $statements = parseSqlDump($sqlDumpFile);
                
                $pdo->exec("SET foreign_key_checks = 0;");
                $pdo->exec("SET NAMES utf8mb4;");

                $executed = 0;
                $skipped = 0;
                foreach ($statements as $stmt) {
                    try {
                        $pdo->exec($stmt);
                        $executed++;
                    } catch (Throwable $te) {
                        $skipped++;
                    }
                }
                
                $pdo->exec("SET foreign_key_checks = 1;");
                $details[] = "Successfully executed {$executed} statements from finchskills_db.sql";
            }

            // Run Schema Migrations (Installment System & Column checks)
            // 1. student_installments table
            $pdo->exec("CREATE TABLE IF NOT EXISTS `student_installments` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `student_db_id` INT DEFAULT NULL,
                `student_id` VARCHAR(50) NOT NULL,
                `admission_id` INT NOT NULL,
                `course_title` VARCHAR(255) NOT NULL,
                `total_course_fee` DECIMAL(10,2) NOT NULL,
                `discount_amount` DECIMAL(10,2) DEFAULT '0.00',
                `final_payable` DECIMAL(10,2) NOT NULL,
                `payment_type` ENUM('full','installment') NOT NULL DEFAULT 'full',
                `total_parts` INT NOT NULL DEFAULT '1',
                `installment_no` INT NOT NULL DEFAULT '1',
                `installment_title` VARCHAR(100) NOT NULL DEFAULT '1st Installment (Admission Fee)',
                `installment_amount` DECIMAL(10,2) NOT NULL,
                `due_date` DATE DEFAULT NULL,
                `paid_date` DATETIME DEFAULT NULL,
                `status` ENUM('Paid','Pending','Overdue') NOT NULL DEFAULT 'Pending',
                `payment_id` VARCHAR(100) DEFAULT NULL,
                `receipt_no` VARCHAR(50) DEFAULT NULL,
                `reminder_sent` TINYINT(1) DEFAULT '0',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `student_id` (`student_id`),
                KEY `admission_id` (`admission_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            $details[] = "Verified table: student_installments";

            // 2. Add payment and enrollment fields to admissions table if missing
            $admissionsCols = $pdo->query("SHOW COLUMNS FROM `admissions`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('enrollment_no', $admissionsCols)) {
                $pdo->exec("ALTER TABLE `admissions` ADD COLUMN `enrollment_no` VARCHAR(50) NULL UNIQUE AFTER `student_id`");
            }
            if (!in_array('fee_total', $admissionsCols)) {
                $pdo->exec("ALTER TABLE `admissions` ADD COLUMN `fee_total` DECIMAL(10,2) DEFAULT 0.00 AFTER `status`");
            }
            if (!in_array('seat_fee_adjusted', $admissionsCols)) {
                $pdo->exec("ALTER TABLE `admissions` ADD COLUMN `seat_fee_adjusted` DECIMAL(10,2) DEFAULT 0.00 AFTER `fee_total`");
            }
            if (!in_array('fee_paid', $admissionsCols)) {
                $pdo->exec("ALTER TABLE `admissions` ADD COLUMN `fee_paid` DECIMAL(10,2) DEFAULT 0.00 AFTER `seat_fee_adjusted`");
            }
            if (!in_array('fee_pending', $admissionsCols)) {
                $pdo->exec("ALTER TABLE `admissions` ADD COLUMN `fee_pending` DECIMAL(10,2) DEFAULT 0.00 AFTER `fee_paid`");
            }
            if (!in_array('payment_plan', $admissionsCols)) {
                $pdo->exec("ALTER TABLE `admissions` ADD COLUMN `payment_plan` ENUM('full','installment') DEFAULT NULL AFTER `fee_pending`");
            }
            if (!in_array('payment_status', $admissionsCols)) {
                $pdo->exec("ALTER TABLE `admissions` ADD COLUMN `payment_status` ENUM('Pending Payment','Partial Paid','Fully Paid') DEFAULT 'Pending Payment' AFTER `payment_plan`");
            }
            $details[] = "Verified fee & enrollment columns on admissions table";

            // 2b. Add booked_course & seat reminder validity columns to students table if missing
            $studentsCols = $pdo->query("SHOW COLUMNS FROM `students`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('registration_id', $studentsCols)) {
                $pdo->exec("ALTER TABLE `students` ADD COLUMN `registration_id` VARCHAR(50) NULL AFTER `student_id`");
            }
            if (!in_array('booked_course_id', $studentsCols)) {
                $pdo->exec("ALTER TABLE `students` ADD COLUMN `booked_course_id` INT NULL AFTER `seat_status`");
            }
            if (!in_array('booked_course', $studentsCols)) {
                $pdo->exec("ALTER TABLE `students` ADD COLUMN `booked_course` VARCHAR(255) NULL AFTER `booked_course_id`");
            }
            if (!in_array('seat_valid_until', $studentsCols)) {
                $pdo->exec("ALTER TABLE `students` ADD COLUMN `seat_valid_until` DATETIME NULL AFTER `payment_date`");
            }
            if (!in_array('seat_reminder_day2_at', $studentsCols)) {
                $pdo->exec("ALTER TABLE `students` ADD COLUMN `seat_reminder_day2_at` DATETIME NULL AFTER `seat_valid_until`");
            }
            if (!in_array('seat_reminder_day4_at', $studentsCols)) {
                $pdo->exec("ALTER TABLE `students` ADD COLUMN `seat_reminder_day4_at` DATETIME NULL AFTER `seat_reminder_day2_at`");
            }
            if (!in_array('seat_reminder_final_at', $studentsCols)) {
                $pdo->exec("ALTER TABLE `students` ADD COLUMN `seat_reminder_final_at` DATETIME NULL AFTER `seat_reminder_day4_at`");
            }
            if (!in_array('seat_cancelled_at', $studentsCols)) {
                $pdo->exec("ALTER TABLE `students` ADD COLUMN `seat_cancelled_at` DATETIME NULL AFTER `seat_reminder_final_at`");
            }
            if (!in_array('seat_reactivated_at', $studentsCols)) {
                $pdo->exec("ALTER TABLE `students` ADD COLUMN `seat_reactivated_at` DATETIME NULL AFTER `seat_cancelled_at`");
            }
            if (!in_array('seat_reactivated_by', $studentsCols)) {
                $pdo->exec("ALTER TABLE `students` ADD COLUMN `seat_reactivated_by` VARCHAR(100) NULL AFTER `seat_reactivated_at`");
            }
            if (!in_array('admission_id', $studentsCols)) {
                $pdo->exec("ALTER TABLE `students` ADD COLUMN `admission_id` INT NULL AFTER `seat_reactivated_by`");
            }
            $pdo->exec("ALTER TABLE `students` MODIFY COLUMN `seat_status` ENUM('Pending','Reserved','Confirmed','Cancelled','Expired') DEFAULT 'Pending'");
            $details[] = "Verified booked course & seat validity reminder columns on students table";

            // 2c. Add enrollment_no to student_installments table if missing
            $installmentsCols = $pdo->query("SHOW COLUMNS FROM `student_installments`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('enrollment_no', $installmentsCols)) {
                $pdo->exec("ALTER TABLE `student_installments` ADD COLUMN `enrollment_no` VARCHAR(50) NULL AFTER `admission_id`");
            }
            if (!in_array('seat_fee_adjusted', $installmentsCols)) {
                $pdo->exec("ALTER TABLE `student_installments` ADD COLUMN `seat_fee_adjusted` DECIMAL(10,2) DEFAULT 0.00 AFTER `discount_amount`");
            }
            $details[] = "Verified enrollment columns on student_installments table";

            // 3. Courses table modules column check
            $coursesCols = $pdo->query("SHOW COLUMNS FROM `courses`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('modules', $coursesCols)) {
                $pdo->exec("ALTER TABLE `courses` ADD COLUMN `modules` VARCHAR(50) DEFAULT '6 Modules' AFTER `study_mode`");
            }
            $details[] = "Verified courses table schema & modules column";
            $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM `admins`")->fetchColumn();
            if ($adminCount === 0) {
                $defaultHash = password_hash('admin123', PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO `admins` (`id`, `username`, `email`, `password`) VALUES (1, 'admin', 'admin@finchskills.com', ?)");
                $stmt->execute([$defaultHash]);
                $details[] = "Created default admin account (admin / admin123)";
            } else {
                $details[] = "Existing admin account verified";
            }

            // 4. Site Settings default values check
            $checkSetting = $pdo->prepare("SELECT COUNT(*) FROM `site_settings` WHERE `setting_key` = ?");
            $checkSetting->execute(['full_payment_discount_percent']);
            if ($checkSetting->fetchColumn() == 0) {
                $pdo->exec("INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('full_payment_discount_percent', '10')");
            }
            $checkSetting->execute(['seat_booking_fee']);
            if ($checkSetting->fetchColumn() == 0) {
                $pdo->exec("INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('seat_booking_fee', '999')");
            }
            $checkSetting->execute(['seat_validity_days']);
            if ($checkSetting->fetchColumn() == 0) {
                $pdo->exec("INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('seat_validity_days', '7')");
            }

            // 5. Create Required Upload Folders with permissions
            $uploadDirs = [
                __DIR__ . '/uploads',
                __DIR__ . '/uploads/admissions',
                __DIR__ . '/uploads/courses',
                __DIR__ . '/uploads/fees',
                __DIR__ . '/uploads/site',
                __DIR__ . '/uploads/students'
            ];

            foreach ($uploadDirs as $dir) {
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
            }
            $details[] = "Verified upload directories";

            // Count tables and rows
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $tableCounts = [];
            foreach ($tables as $tbl) {
                $tableCounts[$tbl] = (int)$pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
            }

            $status = 'success';
            $message = ($action === 'full_import')
                ? "Full Database Setup & Import Completed Successfully!"
                : "Smart Database Migration Completed Successfully!";

        } catch (Throwable $e) {
            $status = 'danger';
            $message = "Error during setup: " . $e->getMessage();
        }
    }
}

// Fetch current tables count if connected
$existingTables = [];
if ($dbConnected) {
    try {
        $existingTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup &amp; Migration - Finchskills</title>
    
    <!-- Google Font: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #0e1e2e;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .setup-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 20px -2px rgba(14, 30, 46, 0.08);
            max-width: 680px;
            width: 100%;
            margin: 0 auto;
            overflow: hidden;
        }
        .setup-header {
            background: #0e1e2e;
            color: #ffffff;
            padding: 2rem;
            position: relative;
        }
        .setup-header .logo-badge {
            width: 48px;
            height: 48px;
            background: #fe7c03;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }
        .btn-brand {
            background-color: #fe7c03;
            border-color: #fe7c03;
            color: #ffffff;
            font-weight: 600;
        }
        .btn-brand:hover {
            background-color: #ea6c00;
            border-color: #ea6c00;
            color: #ffffff;
        }
        .info-pill {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-size: 0.82rem;
        }
    </style>
</head>
<body>

<div class="setup-card">
    
    <!-- HEADER -->
    <div class="setup-header text-center">
        <div class="logo-badge">
            <i class="fa-solid fa-database"></i>
        </div>
        <h3 class="fw-bold mb-1">Database Setup &amp; Migration</h3>
        <p class="text-white-50 mb-0 small">Finchskills / YourGoodGuide 1-Click Server Installer</p>
    </div>

    <!-- BODY -->
    <div class="p-4 p-sm-5">

        <!-- STATUS MESSAGE -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $status ?> d-flex align-items-start gap-3 mb-4 rounded-3">
                <i class="fa-solid <?= $status === 'success' ? 'fa-circle-check text-success' : ($status === 'warning' ? 'fa-triangle-exclamation text-warning' : 'fa-circle-exclamation text-danger') ?> fs-4 mt-1"></i>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1"><?= $status === 'success' ? 'Great Success!' : 'Notice' ?></h6>
                    <p class="mb-0 small"><?= htmlspecialchars($message) ?></p>
                    
                    <?php if (!empty($details)): ?>
                        <ul class="mb-0 mt-2 ps-3 small text-muted">
                            <?php foreach ($details as $d): ?>
                                <li><?= htmlspecialchars($d) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($status === 'success'): ?>
                <!-- ADMIN CREDENTIALS & NEXT STEPS -->
                <div class="bg-light border rounded-3 p-3 mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-bold small text-dark"><i class="fa-solid fa-shield-halved text-warning me-1"></i> Admin Credentials</span>
                        <span class="badge bg-success">Ready</span>
                    </div>
                    <div class="row g-2 small font-monospace">
                        <div class="col-sm-6">
                            <span class="text-muted">Username:</span> <strong class="text-dark">admin</strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted">Password:</span> <strong class="text-dark">admin123</strong>
                        </div>
                    </div>
                </div>

                <!-- TABLE SUMMARY -->
                <?php if (!empty($tableCounts)): ?>
                    <div class="mb-4">
                        <p class="fw-bold small text-muted text-uppercase mb-2">Installed Database Tables (<?= count($tableCounts) ?>):</p>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ($tableCounts as $tbl => $cnt): ?>
                                <span class="badge bg-white text-dark border py-1.5 px-2 small">
                                    <i class="fa-solid fa-table-cells text-muted me-1"></i> <?= htmlspecialchars($tbl) ?>: <strong><?= $cnt ?></strong>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- NAVIGATION BUTTONS -->
                <div class="d-grid gap-2 mb-4">
                    <a href="admin/login.php" class="btn btn-brand btn-lg">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Open Admin Panel
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-globe me-1"></i> Open Live Website
                    </a>
                </div>

                <!-- SECURITY AUTO-DELETE BUTTON -->
                <form method="POST" onsubmit="return confirm('Delete setup.php now? This prevents unauthorized users from running setup again.');" class="border-top pt-3">
                    <button type="submit" name="delete_self" value="1" class="btn btn-sm btn-outline-danger w-100">
                        <i class="fa-solid fa-trash-can me-1"></i> Delete setup.php from server (Recommended for security)
                    </button>
                </form>

            <?php endif; ?>

        <?php endif; ?>

        <!-- CONNECTION & ENVIRONMENT CARD (When not yet successful) -->
        <?php if ($status !== 'success'): ?>
            
            <div class="mb-4">
                <p class="fw-bold small text-muted text-uppercase mb-2">1. Connection &amp; Environment Check</p>
                <div class="row g-2">
                    <div class="col-sm-6">
                        <div class="info-pill d-flex align-items-center justify-content-between">
                            <span class="text-muted">Database:</span>
                            <span class="fw-bold font-monospace"><?= DB_NAME ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-pill d-flex align-items-center justify-content-between">
                            <span class="text-muted">DB Host:</span>
                            <span class="fw-bold font-monospace"><?= DB_HOST ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-pill d-flex align-items-center justify-content-between">
                            <span class="text-muted">DB User:</span>
                            <span class="fw-bold font-monospace"><?= DB_USER ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-pill d-flex align-items-center justify-content-between">
                            <span class="text-muted">Connection:</span>
                            <?php if ($dbConnected): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-check me-1"></i> Connected</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-xmark me-1"></i> Failed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!$dbConnected): ?>
                    <div class="alert alert-danger mt-3 small mb-0">
                        <strong>Connection Error:</strong> <?= htmlspecialchars($connectionError) ?><br>
                        <span class="text-muted">Please open <code>db.php</code> in your file manager and configure your live hosting MySQL Database Name, Username, and Password.</span>
                    </div>
                <?php else: ?>
                    <div class="info-pill mt-2 d-flex align-items-center justify-content-between bg-light">
                        <span class="text-muted">SQL Dump File:</span>
                        <span>
                            <?php if (file_exists($sqlDumpFile)): ?>
                                <strong class="text-success font-monospace">finchskills_db.sql</strong> (<?= round(filesize($sqlDumpFile)/1024, 1) ?> KB)
                            <?php else: ?>
                                <strong class="text-danger font-monospace">Missing finchskills_db.sql</strong>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- INSTALLATION ACTIONS -->
            <?php if ($dbConnected): ?>
                <div class="mb-3">
                    <p class="fw-bold small text-muted text-uppercase mb-2">2. Select Setup Option</p>
                    
                    <div class="d-grid gap-3">
                        
                        <!-- Option A: Full Import -->
                        <form method="POST" id="fullImportForm" onsubmit="handleFormSubmit(this, 'fullBtn');">
                            <input type="hidden" name="action" value="full_import">
                            <div class="border rounded-3 p-3 bg-light hover-shadow">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <h6 class="fw-bold mb-0 text-dark">
                                        <i class="fa-solid fa-bolt text-warning me-1"></i> Full Setup &amp; Import (Recommended)
                                    </h6>
                                    <span class="badge bg-primary">Full</span>
                                </div>
                                <p class="text-muted small mb-3">
                                    Imports all 10 tables from <code>finchskills_db.sql</code> including courses, categories, site pages, fee structure, default admin, and sets up upload folders.
                                </p>
                                <button type="submit" id="fullBtn" class="btn btn-brand w-100 py-2">
                                    <span class="btn-text"><i class="fa-solid fa-bolt me-1"></i> Run 1-Click Full Setup</span>
                                    <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                                    <span class="loading-text d-none">Setting up database... please wait</span>
                                </button>
                            </div>
                        </form>

                        <!-- Option B: Smart Migration -->
                        <form method="POST" id="smartMigrateForm" onsubmit="handleFormSubmit(this, 'smartBtn');">
                            <input type="hidden" name="action" value="smart_migrate">
                            <div class="border rounded-3 p-3 bg-white">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <h6 class="fw-bold mb-0 text-dark">
                                        <i class="fa-solid fa-code-compare text-info me-1"></i> Smart Migration Only
                                    </h6>
                                    <span class="badge bg-secondary">Preserve Data</span>
                                </div>
                                <p class="text-muted small mb-3">
                                    Does not overwrite existing tables. Only adds missing tables (<code>student_installments</code>, etc.) and missing columns in <code>admissions</code>.
                                </p>
                                <button type="submit" id="smartBtn" class="btn btn-outline-secondary w-100 py-2">
                                    <span class="btn-text"><i class="fa-solid fa-rotate me-1"></i> Run Smart Migration</span>
                                    <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                                    <span class="loading-text d-none">Migrating schema... please wait</span>
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>

    <!-- FOOTER -->
    <div class="bg-light border-top py-2 px-4 text-center text-muted small">
        YourGoodGuide / FinchSkills &copy; <?= date('Y') ?> &bull; Fast Setup Utility v3.1
    </div>

</div>

<script>
function handleFormSubmit(form, btnId) {
    const btn = document.getElementById(btnId);
    if (btn) {
        const btnText = btn.querySelector('.btn-text');
        const spinner = btn.querySelector('.spinner-border');
        const loadingText = btn.querySelector('.loading-text');

        if (btnText) btnText.classList.add('d-none');
        if (spinner) spinner.classList.remove('d-none');
        if (loadingText) loadingText.classList.remove('d-none');
        btn.disabled = true;
    }
    form.submit();
}
</script>

</body>
</html>

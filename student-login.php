<?php
if (session_status() === PHP_SESSION_NONE) {
    if (ini_get('session.cookie_path') !== '/') {
        @ini_set('session.cookie_path', '/');
    }
    @session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
require_once __DIR__ . '/db.php';

// If already logged in, redirect to target or profile
if (isset($_SESSION['student_logged_in']) && $_SESSION['student_logged_in'] === true) {
    $redirect = !empty($_GET['redirect']) ? $_GET['redirect'] : 'student-profile.php';
    header("Location: " . $redirect);
    exit;
}

$error = '';
$success = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'password_reset') {
    $success = "Your password has been reset successfully. Please log in with your new password.";
}

// Handle Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = "Please enter your Email / Student ID and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE (email = ? OR student_id = ?) LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $student = $stmt->fetch();

        if ($student) {
            if ($student['is_verified'] == 0) {
                $error = "Your account is not verified yet. Please <a href='register.php' class='alert-link'>verify your email</a> first.";
            } elseif (password_verify($password, $student['password'])) {
                // Login successful
                $_SESSION['student_logged_in'] = true;
                $_SESSION['student_db_id'] = $student['id'];
                $_SESSION['student_id'] = $student['student_id'];
                $_SESSION['student_name'] = $student['name'];
                $_SESSION['student_email'] = $student['email'];
                $_SESSION['student_photo'] = $student['photo'];

                $redirect = !empty($_GET['redirect']) ? $_GET['redirect'] : 'student-profile.php';
                header("Location: " . $redirect);
                exit;
            } else {
                $error = "Invalid password. Please check and try again.";
            }
        } else {
            $error = "No student account found with this Email or Student ID.";
        }
    }
}

$page_title = "Student Portal Login - Finchskills Institute";
include "header.php";
?>

<style>
.login-section {
    padding: 60px 0 90px;
    background: #fafbfc;
    min-height: 75vh;
    display: flex;
    align-items: center;
}
.login-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 38px 32px;
    box-shadow: none !important;
    max-width: 440px;
    margin: 0 auto;
    width: 100%;
}
.login-title {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.3px;
}
.login-label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.login-input {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 14px;
}
.login-input:focus {
    border-color: #fe7c03;
    box-shadow: 0 0 0 3px rgba(254, 124, 3, 0.1);
}
.password-wrap {
    position: relative;
}
.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    font-size: 15px;
}
.password-toggle:hover {
    color: #0f172a;
}
.login-btn {
    background: #fe7c03;
    color: #ffffff;
    border: none;
    border-radius: 9px;
    padding: 12px 20px;
    font-size: 14.5px;
    font-weight: 700;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}
.login-btn:hover {
    background: #e66f00;
    color: #ffffff;
}
</style>

<section class="login-section">
    <div class="container">
        <div class="login-card">

            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center mb-2 rounded-circle" style="width: 48px; height: 48px; background: #fff7ed; color: #fe7c03;">
                    <i class="fa-solid fa-graduation-cap fs-4"></i>
                </div>
                <h2 class="login-title mb-1">Student Portal Login</h2>
                <p class="text-muted small mb-0">Enter your credentials to access your student dashboard.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-4" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2 fs-6"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success py-2 px-3 small d-flex align-items-center mb-4" role="alert">
                    <i class="fa-solid fa-circle-check me-2 fs-6"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="student-login.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">

                <!-- IDENTIFIER -->
                <div class="mb-3">
                    <label class="login-label">Email or Student ID <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-regular fa-user"></i></span>
                        <input type="text" name="identifier" class="form-control login-input border-start-0" placeholder="e.g. rahul@example.com or FS-2026-XXXX" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <!-- PASSWORD WITH EYE PREVIEW TOGGLE -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="login-label mb-0">Password <span class="text-danger">*</span></label>
                        <a href="forgot-password.php" class="small text-decoration-none" style="color: #fe7c03; font-size: 12px;">Forgot Password?</a>
                    </div>
                    <div class="password-wrap">
                        <input type="password" name="password" id="loginPassword" class="form-control login-input pe-5" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('loginPassword', this)" title="Show/Hide Password">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <button type="submit" class="login-btn mt-4">
                    <span>Log In to Dashboard</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>

                <div class="text-center mt-3 small text-muted">
                    Don't have an account yet? <a href="register.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" class="fw-bold text-decoration-none" style="color: #fe7c03;">Registration &rarr;</a>
                </div>

            </form>

        </div>
    </div>
</section>

<script>
function togglePasswordVisibility(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const icon = btn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php include "footer.php"; ?>

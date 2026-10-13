<?php
if (session_status() === PHP_SESSION_NONE) {
    if (ini_get('session.cookie_path') !== '/') {
        @ini_set('session.cookie_path', '/');
    }
    session_start();
}
require_once __DIR__ . '/../db.php';

$error = '';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            session_write_close();
            header("Location: index.php");
            exit;
        } else {
            $error = 'Invalid username/email or password.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - YourGoodGuide</title>
    
    <!-- Google Fonts: Plus Jakarta Sans only -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body, input, button, select, textarea, div, span:not([class*="fa-"]) {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4 bg-slate-100 text-slate-800 antialiased">

<div class="w-full max-w-sm bg-white border border-slate-200 rounded-md p-6 sm:p-8 space-y-5">
    
    <!-- Brand Header -->
    <div class="text-center space-y-1">
        <div class="w-11 h-11 rounded-md bg-[#fe7c03] text-white flex items-center justify-center text-lg mx-auto mb-3">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <h2 class="text-lg font-bold text-[#0e1e2e] tracking-tight">Admin Console</h2>
        <p class="text-xs text-slate-400">Sign in to YourGoodGuide management portal</p>
    </div>

    <!-- Error Alert -->
    <?php if (!empty($error)): ?>
        <div class="p-2 text-xs bg-rose-50 text-rose-700 border border-rose-200 rounded-md flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="login.php" class="space-y-3.5 text-xs">
        <div>
            <label class="font-bold text-slate-700 block mb-1">Username or Email</label>
            <div class="relative">
                <i class="fa-solid fa-user absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i>
                <input type="text" name="username" placeholder="admin" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" class="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-md text-slate-800 text-xs focus:outline-none focus:border-[#fe7c03]">
            </div>
        </div>

        <div>
            <label class="font-bold text-slate-700 block mb-1">Password</label>
            <div class="relative">
                <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i>
                <input type="password" id="loginPass" name="password" placeholder="••••••••" required class="w-full pl-8 pr-8 py-2 border border-slate-200 rounded-md text-slate-800 text-xs focus:outline-none focus:border-[#fe7c03]">
                <button type="button" onclick="togglePass()" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-eye text-xs" id="passEye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="w-full py-2 px-4 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors flex items-center justify-center gap-1">
            <i class="fa-solid fa-right-to-bracket text-[11px]"></i>
            <span>Sign In to Dashboard</span>
        </button>

        <div class="text-center pt-2 border-t border-slate-100">
            <a href="../index.php" class="text-[11px] text-slate-400 hover:text-[#fe7c03] no-underline inline-flex items-center gap-1">
                <i class="fa-solid fa-arrow-left text-[9px]"></i>
                <span>Return to Public Website</span>
            </a>
        </div>
    </form>

</div>

<script>
function togglePass() {
    const pass = document.getElementById('loginPass');
    const eye = document.getElementById('passEye');
    if (pass.type === 'password') {
        pass.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        pass.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
}
</script>

</body>
</html>

<?php
require_once __DIR__ . '/auth.php';
checkAdminAuth();
$admin_stats = getAdminStats($pdo);
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');
$admin_initial = strtoupper(substr($admin_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-[#f8fafc]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Dashboard' ?> - Finchskills Admin</title>
    
    <!-- Google Fonts: Plus Jakarta Sans only -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap 5 CSS (for inner tables/modals compatibility) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            DEFAULT: '#fe7c03',
                            hover: '#ea6c00',
                            dark: '#0e1e2e',
                            navy: '#09131e',
                            border: '#1e3145',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        body, input, button, select, textarea, table, h1, h2, h3, h4, h5, h6, p, a, div, span:not([class*="fa-"]) {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        body {
            background-color: #f8fafc;
            color: #0e1e2e;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        /* Clean Flat Cards without heavy shadows */
        .stat-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #ffffff;
            padding: 1.25rem;
            box-shadow: none !important;
            transition: border-color 0.2s ease;
        }
        .stat-card:hover {
            border-color: #cbd5e1;
        }
        .stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        /* Bootstrap compatibility adjustments */
        .table > :not(caption) > * > * {
            background-color: transparent;
        }
        .card {
            box-shadow: none !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 6px !important;
        }
    </style>
</head>
<body class="h-full antialiased text-slate-800 bg-[#f8fafc]">

<!-- Modular Sidebar Component -->
<?php require_once __DIR__ . '/sidebar.php'; ?>

<!-- Main Wrapper -->
<div class="lg:pl-64 flex flex-col min-h-screen bg-[#f8fafc]">
    
    <!-- Topbar Header -->
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
        
        <!-- Left Section: Mobile Toggle & Title -->
        <div class="flex items-center gap-3">
            <button id="sidebarOpenBtn" type="button" class="lg:hidden inline-flex items-center justify-center p-2 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors">
                <i class="fa-solid fa-bars text-sm"></i>
            </button>
            
            <div>
                <div class="flex items-center gap-1 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                    <span>Admin</span>
                    <i class="fa-solid fa-angle-right text-[9px] text-slate-300"></i>
                    <span class="text-[#fe7c03]"><?= $page_title ?? 'Dashboard' ?></span>
                </div>
                <h1 class="text-base font-bold text-[#0e1e2e] leading-tight">
                    <?= $page_title ?? 'Dashboard' ?>
                </h1>
            </div>
        </div>

        <!-- Right Section: Live Site, Notifications, Profile -->
        <div class="flex items-center gap-2 sm:gap-3">
            
            <!-- View Site Button -->
            <a href="../index.php" target="_blank" class="hidden sm:inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-[#fff8f3] hover:text-[#fe7c03] border border-slate-200 rounded-md transition-colors no-underline">
                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                <span>View Site</span>
            </a>

            <!-- Notification Dropdown -->
            <div class="relative" id="notificationDropdownContainer">
                <button id="notificationDropdownBtn" type="button" class="relative p-2 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors">
                    <i class="fa-regular fa-bell text-base"></i>
                    <span id="headerBellDot" class="<?= ($admin_stats['total_unread'] ?? 0) > 0 ? '' : 'hidden ' ?>absolute top-1.5 right-1.5 flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#fe7c03] opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-[#fe7c03]"></span>
                    </span>
                </button>

                <!-- Notifications Menu -->
                <div id="notificationDropdownMenu" class="hidden absolute right-0 mt-1.5 w-72 sm:w-80 rounded-md bg-white border border-slate-200 z-50 py-1 shadow-lg">
                    <div class="flex items-center justify-between px-3 py-2 border-b border-slate-100 bg-slate-50">
                        <span class="text-xs font-bold text-[#0e1e2e]">Notifications</span>
                        <span id="headerNotifBadgeTotal" class="<?= ($admin_stats['total_unread'] ?? 0) > 0 ? '' : 'hidden ' ?>px-1.5 py-0.2 text-[10px] font-bold rounded bg-orange-100 text-[#fe7c03]">
                            <?= $admin_stats['total_unread'] ?? 0 ?> New
                        </span>
                    </div>

                    <div class="p-1 space-y-0.5">
                        <!-- Admissions -->
                        <a href="admissions.php" class="flex items-center justify-between p-2 rounded hover:bg-slate-50 transition-colors no-underline text-inherit">
                            <div class="flex items-center gap-2">
                                <span class="w-6 text-center text-[#fe7c03]"><i class="fa-solid fa-id-card text-xs"></i></span>
                                <div>
                                    <p class="text-xs font-semibold text-slate-800 leading-tight">Admissions</p>
                                    <p class="text-[10px] text-slate-400">Enrollment requests</p>
                                </div>
                            </div>
                            <span id="headerAdmBadge" class="<?= ($admin_stats['unread_admissions'] ?? 0) > 0 ? '' : 'hidden ' ?>px-1.5 py-0.2 text-[10px] font-bold rounded bg-orange-500 text-white">
                                <?= $admin_stats['unread_admissions'] ?? 0 ?>
                            </span>
                        </a>

                        <!-- Fees -->
                        <a href="fees.php" class="flex items-center justify-between p-2 rounded hover:bg-slate-50 transition-colors no-underline text-inherit">
                            <div class="flex items-center gap-2">
                                <span class="w-6 text-center text-teal-600"><i class="fa-solid fa-receipt text-xs"></i></span>
                                <div>
                                    <p class="text-xs font-semibold text-slate-800 leading-tight">Fee Submissions</p>
                                    <p class="text-[10px] text-slate-400">Receipts awaiting review</p>
                                </div>
                            </div>
                            <span id="headerFeeBadge" class="<?= ($admin_stats['unread_fees'] ?? 0) > 0 ? '' : 'hidden ' ?>px-1.5 py-0.2 text-[10px] font-bold rounded bg-teal-600 text-white">
                                <?= $admin_stats['unread_fees'] ?? 0 ?>
                            </span>
                        </a>

                        <!-- Inquiries -->
                        <a href="contacts.php" class="flex items-center justify-between p-2 rounded hover:bg-slate-50 transition-colors no-underline text-inherit">
                            <div class="flex items-center gap-2">
                                <span class="w-6 text-center text-sky-600"><i class="fa-solid fa-envelope text-xs"></i></span>
                                <div>
                                    <p class="text-xs font-semibold text-slate-800 leading-tight">Inquiries</p>
                                    <p class="text-[10px] text-slate-400">Contact form messages</p>
                                </div>
                            </div>
                            <span id="headerInqBadge" class="<?= ($admin_stats['unread_inquiries'] ?? 0) > 0 ? '' : 'hidden ' ?>px-1.5 py-0.2 text-[10px] font-bold rounded bg-sky-600 text-white">
                                <?= $admin_stats['unread_inquiries'] ?? 0 ?>
                            </span>
                        </a>
                    </div>

                    <!-- Live feed list -->
                    <div id="headerRecentAlertsContainer" class="border-t border-slate-100 p-1 hidden">
                        <p class="px-2 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Latest Alerts</p>
                        <div id="headerRecentAlertsList" class="space-y-1 max-h-48 overflow-y-auto custom-scrollbar"></div>
                    </div>
                </div>
            </div>

            <!-- Profile Dropdown -->
            <div class="relative" id="profileDropdownContainer">
                <button id="profileDropdownBtn" type="button" class="flex items-center gap-2 p-1.5 rounded-md hover:bg-slate-100 transition-colors">
                    <div class="w-7 h-7 rounded bg-[#fe7c03] text-white font-bold flex items-center justify-center text-xs">
                        <?= $admin_initial ?>
                    </div>
                    <span class="text-xs font-semibold text-[#0e1e2e] hidden sm:inline"><?= $admin_name ?></span>
                    <i class="fa-solid fa-angle-down text-[10px] text-slate-400"></i>
                </button>

                <!-- Profile Menu -->
                <div id="profileDropdownMenu" class="hidden absolute right-0 mt-1.5 w-48 rounded-md bg-white border border-slate-200 z-50 py-1">
                    <div class="px-3 py-2 border-b border-slate-100 bg-slate-50">
                        <p class="text-xs font-bold text-[#0e1e2e]"><?= $admin_name ?></p>
                        <p class="text-[10px] text-slate-400 truncate"><?= htmlspecialchars($_SESSION['admin_email'] ?? 'admin@finchskills.com') ?></p>
                    </div>
                    <div class="p-1">
                        <a href="settings.php" class="flex items-center gap-2 px-2 py-1.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#fe7c03] rounded transition-colors no-underline">
                            <i class="fa-solid fa-sliders text-xs w-4 text-slate-400"></i>
                            <span>Settings</span>
                        </a>
                        <a href="payment-settings.php" class="flex items-center gap-2 px-2 py-1.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#fe7c03] rounded transition-colors no-underline">
                            <i class="fa-solid fa-credit-card text-xs w-4 text-slate-400"></i>
                            <span>Payment Gateways</span>
                        </a>
                        <a href="logout.php" class="flex items-center gap-2 px-2 py-1.5 text-xs text-rose-600 hover:bg-rose-50 rounded transition-colors no-underline border-t border-slate-100 mt-1">
                            <i class="fa-solid fa-right-from-bracket text-xs w-4"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 p-4 sm:p-5 lg:p-6">
